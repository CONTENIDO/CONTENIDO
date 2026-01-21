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
     * @param int $newsJobId ID of corresponding newsletter send job
     * @param int $recipientId ID of recipient
     * @return NewsletterLog|false
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($newsJobId, $recipientId)
    {
        $this->resetQuery();
        $this->setWhere('idnewsjob', $newsJobId);
        $this->setWhere('idnewsrcp', $recipientId);
        $this->query();

        if ($oItem = $this->next()) {
            return $oItem;
        }

        $oRecipient = new NewsletterRecipient();
        if ($oRecipient->loadByPrimaryKey($recipientId)) {
            $oItem = $this->createNewItem();

            $oItem->set('idnewsjob', $newsJobId);
            $oItem->set('idnewsrcp', $recipientId);

            $sEMail = $oRecipient->get('email');
            $name = $oRecipient->get('name');

            if ($name == '') {
                $oItem->set('rcpname', $sEMail);
            } else {
                $oItem->set('rcpname', $name);
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
     * @param int $newsJobId ID of the corresponding newsletter dispatch job
     * @param int $newsId ID of newsletter
     * @return  int  Recipient count
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function initializeJob($newsJobId, $newsId)
    {
        $newsJobId = cSecurity::toInteger($newsJobId);
        $newsId = cSecurity::toInteger($newsId);

        $oNewsletter = new Newsletter();
        if (!$oNewsletter->loadByPrimaryKey($newsId)) {
            return 0;
        }

        $destination = $oNewsletter->get('send_to');
        $clientId = $oNewsletter->get('idclient');
        $languageId = $oNewsletter->get('idlang');
        // Table name alias for the flexSelect function below!
        $tableNameAlias = cString::toLowerCase('NewsletterRecipientCollection');

        $distinct = '';
        $from = '';
        $where = '';

        switch ($destination) {
            case 'all':
                $where = sprintf(
                    "`deactivated` = 0 AND `confirmed` = 1 AND idclient = %d AND `idlang` = %d",
                    $clientId,
                    $languageId
                );
                break;
            case 'default':
                $distinct = '`distinct`';
                $from = sprintf(
                    "`%s` AS `groups`, `%s` AS `groupmembers` ",
                    cDb::getTableName('news_groups'),
                    cDb::getTableName('news_groupmembers')
                );
                $where = $this->db->prepare(
                    ":table_name_alias.idclient = :client_id AND :table_name_alias.idlang = :language_id AND "
                    . ":table_name_alias.deactivated = 0 AND :table_name_alias.confirmed = 1 AND "
                    . ":table_name_alias.idnewsrcp = groupmembers.idnewsrcp AND "
                    . "groupmembers.idnewsgroup = groups.idnewsgroup AND "
                    . "groups.defaultgroup = 1 AND groups.idclient = :client_id AND groups.idlang = language_id",
                    [
                        'table_name_alias' => $tableNameAlias,
                        'client_id' => $clientId,
                        'language_id' => $languageId
                    ]
                );
                break;
            case 'selection':
                $groups = unserialize($oNewsletter->get('send_ids'));

                if (is_array($groups) && count($groups) > 0) {
                    $distinct = 'distinct';
                    $from = cDb::getTableName('news_groupmembers') . " AS groupmembers ";
                    $where = $this->db->prepare(
                        ":table_name_alias.idclient = :client_id AND :table_name_alias.idlang = :language_id AND "
                        . ":table_name_alias.deactivated = 0 AND :table_name_alias.confirmed = 1 AND "
                        . ":table_name_alias.idnewsrcp = groupmembers.idnewsrcp AND groupmembers.idnewsgroup",
                        [
                            'table_name_alias' => $tableNameAlias,
                            'client_id' => $clientId,
                            'language_id' => $languageId
                        ]
                    );
                    $where .= " IN ('" . implode("','", $groups) . "')";
                } else {
                    $destination = 'unknown';
                }
                break;
            case 'single':
                $id = $oNewsletter->get('send_ids');
                if (is_numeric($id)) {
                    $where = "idnewsrcp = $id";
                } else {
                    $destination = 'unknown';
                }
                break;
            default:
                $destination = 'unknown';
        }

        if ($destination == 'unknown') {
            return 0;
        }

        $oRecipients = new NewsletterRecipientCollection();
        $oRecipients->flexSelect($distinct, $from, $where);
        $numRecipients = $oRecipients->count();
        while ($oRecipient = $oRecipients->next()) {
            $this->create($newsJobId, $oRecipient->get($oRecipient->getPrimaryKeyName()));
        }

        return $numRecipients;
    }

    /**
     * Overridden delete function to update recipient count if removing recipient from the list
     *
     * @inheritDoc
     * @param int $id The newsletter log id.
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
     * @throws cException
     */
    public function deleteJob($id): bool
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
     * @param mixed $id The ID of item to load
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
            case 'idnewslog':
            case 'idnewsjob':
            case 'idnewsrcp':
            case 'rcpnewstype':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

    /**
     * @inheritDoc
     */
    public function getField($name, $safe = true)
    {
        $value = parent::getField($name, $safe);

        switch ($name) {
            case 'idnewslog':
            case 'idnewsjob':
            case 'idnewsrcp':
            case 'rcpnewstype':
                $value = cSecurity::toInteger($value);
                break;
        }

        return $value;
    }

}
