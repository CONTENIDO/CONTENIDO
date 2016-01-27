<?php
/**
 * This file contains the Newsletter log class.
 *
 * @package Plugin
 * @subpackage Newsletter
 * @author Bjoern Behrens
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Newsletter log class.
 *
 * @package Plugin
 * @subpackage Newsletter
 */
class NewsletterLogCollection extends ItemCollection {

    /**
     * Constructor Function
     *
     * @param none
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg["tab"]["news_log"], "idnewslog");
        $this->_setItemClass("NewsletterLog");
    }

    /**
     * Creates a single new log item
     *
     * @param $idnewsjob integer ID of corresponding newsletter send job
     * @param $idnewsrcp integer ID of recipient
     * @param $rcp_name string Name of the recipient (-> recipient may be
     *        deleted)
     * @param $rcp_email string E-Mail of the recipient (-> recipient may be
     *        deleted)
     */
    public function create($idnewsjob, $idnewsrcp) {

        $this->resetQuery();
        $this->setWhere("idnewsjob", $idnewsjob);
        $this->setWhere("idnewsrcp", $idnewsrcp);
        $this->query();

        if ($oItem = $this->next()) {
            return $oItem;
        }

        $oRecipient = new NewsletterRecipient();
        if ($oRecipient->loadByPrimaryKey($idnewsrcp)) {
            $oItem = $this->createNewItem();

            $oItem->set("idnewsjob", $idnewsjob);
            $oItem->set("idnewsrcp", $idnewsrcp);

            $sEMail = $oRecipient->get("email");
            $sName = $oRecipient->get("name");

            if ($sName == "") {
                $oItem->set("rcpname", $sEMail);
            } else {
                $oItem->set("rcpname", $sName);
            }

            $oItem->set("rcpemail", $sEMail);
            $oItem->set("rcphash", $oRecipient->get("hash"));
            $oItem->set("rcpnewstype", $oRecipient->get("news_type"));
            $oItem->set("status", "pending");
            $oItem->set("created", date('Y-m-d H:i:s'), false);
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
     * @param  int  $idnewsjob ID of corresponding newsletter dispatch job
     * @param  int  $idnews ID of newsletter
     * @return  int  Recipient count
     */
    public function initializeJob($idnewsjob, $idnews) {
        global $cfg;

        $idnewsjob = cSecurity::toInteger($idnewsjob);
        $idnews = cSecurity::toInteger($idnews);

        $oNewsletter = new Newsletter();
        if ($oNewsletter->loadByPrimaryKey($idnews)) {
            $sDestination = $oNewsletter->get("send_to");
            $iIDClient = $oNewsletter->get("idclient");
            $iIDLang = $oNewsletter->get("idlang");

            switch ($sDestination) {
                case "all":
                    $sDistinct = "";
                    $sFrom = "";
                    $sSQL = "deactivated='0' AND confirmed='1' AND idclient='" . $iIDClient . "' AND idlang='" . $iIDLang . "'";
                    break;
                case "default":
                    $sDistinct = "distinct";
                    $sFrom = $cfg["tab"]["news_groups"] . " AS groups, " . $cfg["tab"]["news_groupmembers"] . " AS groupmembers ";
                    $sSQL = $cfg['tab']['news_rcp'] . ".idclient = '" . $iIDClient . "' AND " . $cfg['tab']['news_rcp'] . ".idlang = '" . $iIDLang . "' AND " . $cfg['tab']['news_rcp'] . ".deactivated = '0' AND " . $cfg['tab']['news_rcp'] . ".confirmed = '1' AND " . $cfg['tab']['news_rcp'] . ".idnewsrcp = groupmembers.idnewsrcp AND " . "groupmembers.idnewsgroup = groups.idnewsgroup AND " . "groups.defaultgroup = '1' AND groups.idclient = '" . $iIDClient . "' AND " . "groups.idlang = '" . $iIDLang . "'";
                    break;
                case "selection":
                    $aGroups = unserialize($oNewsletter->get("send_ids"));

                    if (is_array($aGroups) && count($aGroups) > 0) {
                        $sGroups = "'" . implode("','", $aGroups) . "'";

                        $sDistinct = "distinct";
                        $sFrom = $cfg["tab"]["news_groupmembers"] . " AS groupmembers ";
                        $sSQL = "newsletterrecipientcollection.idclient = '" . $iIDClient . "' AND newsletterrecipientcollection.idlang = '" . $iIDLang . "' AND newsletterrecipientcollection.deactivated = '0' AND newsletterrecipientcollection.confirmed = '1' AND newsletterrecipientcollection.idnewsrcp = groupmembers.idnewsrcp AND " . "groupmembers.idnewsgroup IN (" . $sGroups . ")";
                    } else {
                        $sDestination = "unknown";
                    }
                    break;
                case "single":
                    $iID = $oNewsletter->get("send_ids");
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
     * Overriden delete function to update recipient count if removing recipient
     * from the list
     *
     * @param int $idnewslog ID
     */
    public function delete($idnewslog) {
        $idnewslog = cSecurity::toInteger($idnewslog);

        $oLog = new NewsletterLog($idnewslog);
        $iIDNewsJob = $oLog->get("idnewsjob");
        unset($oLog);

        $oJob = new NewsletterJob($iIDNewsJob);
        $oJob->set("rcpcount", $oJob->get("rcpcount") - 1);
        $oJob->store();
        unset($oJob);

        parent::delete($idnewslog);
    }

    public function deleteJob($idnewsjob) {
        $idnewsjob = cSecurity::toInteger($idnewsjob);
        $this->setWhere("idnewsjob", $idnewsjob);
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
class NewsletterLog extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg["tab"]["news_log"], "idnewslog");
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

	/**
     * Userdefined setter for newsletter logs fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe Flag to run defined inFilter on passed value
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idnewsjob':
                $value = (int) $value;
                break;
			case 'idnewsrcp':
                $value = (int) $value;
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

}

?>