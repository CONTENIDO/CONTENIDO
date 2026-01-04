<?php

/**
 * This file contains the Newsletter log class.
 *
 * @package    Plugin
 * @subpackage Newsletter
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Newsletter log class.
 *
 * @package    Plugin
 * @subpackage Newsletter
 * @extends ItemCollection<NewsletterLog>
 */
class NewsletterLogCollection extends ItemCollection
{
    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('news_log'), 'idnewslog');
        $this->_setItemClass('NewsletterLog');
    }

    /**
     * Creates a single new log item
     *
     * @param int $idnewsjob ID of corresponding newsletter send job
     * @param int $idnewsrcp ID of recipient
     * @return NewsletterLog|false
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($idnewsjob, $idnewsrcp)
    {
        $this->resetQuery();
        $this->setWhere('idnewsjob', $idnewsjob);
        $this->setWhere('idnewsrcp', $idnewsrcp);
        $this->query();

        if ($oItem = $this->next()) {
            return $oItem;
        }

        $oRecipient = new NewsletterRecipient();
        if ($oRecipient->loadByPrimaryKey($idnewsrcp)) {
            $oItem = $this->createNewItem();

            $oItem->set('idnewsjob', $idnewsjob);
            $oItem->set('idnewsrcp', $idnewsrcp);

            $sEMail = $oRecipient->get('email');
            $sName = $oRecipient->get('name');

            if ($sName == '') {
                $oItem->set('rcpname', $sEMail);
            } else {
                $oItem->set('rcpname', $sName);
            }

            $oItem->set('rcpemail', $sEMail);
            $oItem->set('rcphash', $oRecipient->get('hash'));
            $oItem->set('rcpnewstype', $oRecipient->get('news_type'));
            $oItem->set('status', "pending");
            $oItem->set('created', date('Y-m-d H:i:s'), false);
            $oItem->store();

            return $oItem;
        } else {
            return false;
        }
    }

    /**
     * Gets all active recipients as specified for the newsletter and adds for
     * every recipient a log item
     *
     * @param int $idnewsjob ID of corresponding newsletter dispatch job
     * @param int $idnews ID of newsletter
     *
     * @return  int  Recipient count
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function initializeJob($idnewsjob, $idnews)
    {
        $idnewsjob = cSecurity::toInteger($idnewsjob);
        $idnews = cSecurity::toInteger($idnews);

        $oNewsletter = new Newsletter();
        if ($oNewsletter->loadByPrimaryKey($idnews)) {
            $sDestination = $oNewsletter->get('send_to');
            $iIDClient = $oNewsletter->get('idclient');
            $iIDLang = $oNewsletter->get('idlang');
            $nrc = new NewsletterRecipientCollection();
            $nrcClassName = cString::toLowerCase(get_class($nrc));

            switch ($sDestination) {
                case "all":
                    $sDistinct = "";
                    $sFrom = "";
                    $sSQL = "deactivated='0' AND confirmed='1' AND idclient='" . $iIDClient . "' AND idlang='" . $iIDLang . "'";
                    break;
                case "default":
                    $sDistinct = "distinct";
                    $sFrom = cDb::getTableName('news_groups') . " AS groups, " . cDb::getTableName('news_groupmembers') . " AS groupmembers ";
                    $sSQL = $nrcClassName . ".idclient = '" . $iIDClient . "' AND " . $nrcClassName . ".idlang = '" . $iIDLang . "' AND " . $nrcClassName . ".deactivated = '0' AND " . $nrcClassName . ".confirmed = '1' AND " . $nrcClassName . ".idnewsrcp = groupmembers.idnewsrcp AND " . "groupmembers.idnewsgroup = groups.idnewsgroup AND " . "groups.defaultgroup = '1' AND groups.idclient = '" . $iIDClient . "' AND " . "groups.idlang = '" . $iIDLang . "'";
                    break;
                case "selection":
                    $aGroups = unserialize($oNewsletter->get('send_ids'));

                    if (is_array($aGroups) && count($aGroups) > 0) {
                        $sGroups = "'" . implode("','", $aGroups) . "'";

                        $sDistinct = "distinct";
                        $sFrom = cDb::getTableName('news_groupmembers') . " AS groupmembers ";
                        $sSQL = "newsletterrecipientcollection.idclient = '" . $iIDClient . "' AND newsletterrecipientcollection.idlang = '" . $iIDLang . "' AND newsletterrecipientcollection.deactivated = '0' AND newsletterrecipientcollection.confirmed = '1' AND newsletterrecipientcollection.idnewsrcp = groupmembers.idnewsrcp AND " . "groupmembers.idnewsgroup IN (" . $sGroups . ")";
                    } else {
                        $sDestination = "unknown";
                    }
                    break;
                case "single":
                    $iID = $oNewsletter->get('send_ids');
                    if (is_numeric($iID)) {
                        $sDistinct = "";
                        $sFrom = "";
                        $sSQL = "idnewsrcp = '" . $iID . "'";
                    } else {
                        $sDestination = "unknown";
                    }
                    break;
                default:
                    $sDestination = "unknown";
            }
            unset($oNewsletter);

            if ($sDestination == "unknown") {
                return 0;
            } else {
                $oRecipients = new NewsletterRecipientCollection();
                $oRecipients->flexSelect($sDistinct, $sFrom, $sSQL, "", "", "");

                $iRecipients = $oRecipients->count();

                while ($oRecipient = $oRecipients->next()) {
                    $this->create($idnewsjob, $oRecipient->get($oRecipient->getPrimaryKeyName()));
                }

                return $iRecipients;
            }
        } else {
            return 0;
        }
    }

    /**
     * Overridden delete function to update recipient count if removing recipient from the list
     *
     * @inheritDoc
     * @param int $id ID
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function delete($id)
    {
        $id = cSecurity::toInteger($id);

        $newsletterJobId = (new NewsletterLog($id))->get('idnewsjob');

        $newsletterJob = new NewsletterJob($newsletterJobId);
        $newsletterJob->set('rcpcount', $newsletterJob->get('rcpcount') - 1);
        $newsletterJob->store();

        return parent::delete($id);
    }

    /**
     * @param int $id
     * @return bool
     * @throws cException
     */
    public function deleteJob($id)
    {
        $this->setWhere('idnewsjob', cSecurity::toInteger($id));
        $this->query();

        while ($oItem = $this->next()) {
            $this->delete($oItem->get($oItem->getPrimaryKeyName()));
        }

        return true;
    }

}

/**
 * Single NewsletterLog Item
 */
class NewsletterLog extends Item
{
    /**
     * Constructor Function
     *
     * @param mixed $id Specifies the ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('news_log'), 'idnewslog');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * User-defined setter for newsletter logs fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idnewsrcp':
            case 'idnewsjob':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

}
