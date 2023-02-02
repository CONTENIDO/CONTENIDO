<?php
/**
 * This file contains the Newsletter Collection class.
 *
 * @package Plugin
 * @subpackage Newsletter
 * @author Bjoern Behrens
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 *
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Newsletter Collection class.
 *
 * @package Plugin
 * @subpackage Newsletter
 * @method NewsletterRecipient createNewItem
 * @method NewsletterRecipient|bool next
 */
class NewsletterRecipientCollection extends ItemCollection {
    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        parent::__construct(cRegistry::getDbTableName('news_rcp'), 'idnewsrcp');
        $this->_setItemClass('NewsletterRecipient');
    }

    /**
     * Creates a new recipient
     *
     * @param string $sEMail       Specifies the e-mail address
     * @param string $sName        Specifies the recipient name (optional)
     * @param int    $iConfirmed   Specifies, if the recipient is confirmed
     *                             (optional)
     * @param string $sJoinID      Specifies additional recipient group ids to join
     *                             (optional, e.g. 47,12,...)
     * @param int    $iMessageType Specifies the message type for the recipient (0 = text, 1 = html)
     *
     * @return Item
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($sEMail, $sName = "", $iConfirmed = 0, $sJoinID = "", $iMessageType = 0) {
        $client = cSecurity::toInteger(cRegistry::getClientId());
        $lang = cSecurity::toInteger(cRegistry::getLanguageId());
        $auth = cRegistry::getAuth();

        // Check if the e-mail address already exists
        $email = cString::toLowerCase($sEMail); // e-mail always lower case
        $this->setWhere("idclient", $client);
        $this->setWhere("idlang", $lang);
        $this->setWhere("email", $email);
        $this->query();

        if ($this->next()) {
            // 0: Deactivate 'confirmed'
            return $this->create($email . "_" . cString::getPartOfString(md5(rand()), 0, 10), $sName, 0, $sJoinID, $iMessageType);
        }
        $oItem = $this->createNewItem();
        $oItem->set("idclient", $client);
        $oItem->set("idlang", $lang);
        $oItem->set("name", $sName);
        $oItem->set("email", $email);
        // Generating UID, 30 characters
        $oItem->set("hash", cString::getPartOfString(md5(rand()), 0, 17) . uniqid(""));
        $oItem->set("confirmed", $iConfirmed);
        $oItem->set("news_type", $iMessageType);

        if ($iConfirmed) {
            $oItem->set("confirmeddate", date("Y-m-d H:i:s"), false);
        }
        $oItem->set("deactivated", 0);
        $oItem->set("created", date('Y-m-d H:i:s'), false);
        $oItem->set("author", $auth->auth["uid"]);
        $oItem->store();

        // Getting internal id of new recipient
        $iIDRcp = $oItem->get("idnewsrcp");

        // Add this recipient to the default recipient group (if available)
        $oGroups = new NewsletterRecipientGroupCollection();
        $oGroupMembers = new NewsletterRecipientGroupMemberCollection();

        $oGroups->setWhere("idclient", $client);
        $oGroups->setWhere("idlang", $lang);
        $oGroups->setWhere("defaultgroup", 1);
        $oGroups->query();

        while ($oGroup = $oGroups->next()) {
            $iIDGroup = $oGroup->get("idnewsgroup");
            $oGroupMembers->create($iIDGroup, $iIDRcp);
        }

        // Add to other recipient groups as well? Do so!
        if ($sJoinID != "") {
            $aJoinID = explode(",", $sJoinID);

            if (count($aJoinID) > 0) {
                foreach ($aJoinID as $iIDGroup) {
                    $oGroupMembers->create($iIDGroup, $iIDRcp);
                }
            }
        }

        return $oItem;
    }

    /**
     * Overridden delete method to remove recipient from groupmember table
     * before deleting recipient
     *
     * @param $itemID int specifies the recipient
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function delete($itemID) {
        $oAssociations = new NewsletterRecipientGroupMemberCollection();
        $oAssociations->setWhere("idnewsrcp", $itemID);
        $oAssociations->query();

        While ($oItem = $oAssociations->next()) {
            $oAssociations->delete($oItem->get("idnewsgroupmember"));
        }
        parent::delete($itemID);
    }

    /**
     * Purge method to delete recipients which hasn't been confirmed since over
     * a month
     *
     * @param $timeframe int Days after creation a not confirmed recipient will
     *                   be removed
     *
     * @return int Count of deleted recipients
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function purge($timeframe) {
        $client = cSecurity::toInteger(cRegistry::getClientId());
        $lang = cSecurity::toInteger(cRegistry::getLanguageId());

        $oRecipientCollection = new NewsletterRecipientCollection();

        // DATEDIFF(created, NOW()) > 30 would be better, but it's only
        // available in MySQL V4.1.1 and above
        // Note, that, TO_DAYS or NOW may not be available in other database
        // systems than MySQL
        $oRecipientCollection->setWhere("idclient", $client);
        $oRecipientCollection->setWhere("idlang", $lang);
        $oRecipientCollection->setWhere("confirmed", 0);
        $oRecipientCollection->setWhere("(TO_DAYS(NOW()) - TO_DAYS(created))", $timeframe, ">");
        $oRecipientCollection->query();

        while ($oItem = $oRecipientCollection->next()) {
            $oRecipientCollection->delete($oItem->get("idnewsrcp"));
        }
        return $oRecipientCollection->count();
    }

    /**
     * checkEMail returns true, if there is no recipient with the same e-mail
     * address; otherwise false
     *
     * @param $sEmail string e-mail
     *
     * @return NewsletterRecipient|false recipient item if item with e-mail exists, false otherwise
     * @throws cException
     */
    public function emailExists($sEmail) {
        $client = cSecurity::toInteger(cRegistry::getClientId());
        $lang = cSecurity::toInteger(cRegistry::getLanguageId());

        $oRecipientCollection = new NewsletterRecipientCollection();
        $oRecipientCollection->setWhere("idclient", $client);
        $oRecipientCollection->setWhere("idlang", $lang);
        $oRecipientCollection->setWhere("email", cString::toLowerCase($sEmail));
        $oRecipientCollection->query();

        if ($oItem = $oRecipientCollection->next()) {
            return $oItem;
        } else {
            return false;
        }
    }

    /**
     * Sets a key for all recipients without key or an old key (len(key) <> 30)
     *
     * @return int
     * @throws cDbException
     * @throws cException
     */
    public function updateKeys() {
        $this->setWhere("LENGTH(hash)", 30, "<>");
        $this->query();

        $iUpdated = $this->count();
        while ($oItem = $this->next()) {
            // Generating UID, 30 characters
            $oItem->set("hash", cString::getPartOfString(md5(rand()), 0, 17) . uniqid(""));
            $oItem->store();
        }

        return $iUpdated;
    }

}

/**
 * Single Recipient Item
 */
class NewsletterRecipient extends Item {
    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($mId = false) {
        parent::__construct(cRegistry::getDbTableName('news_rcp'), 'idnewsrcp');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * @return bool
     * @throws cDbException
     * @throws cException
     */
    public function store() {
        $auth = cRegistry::getAuth();

        $this->set("lastmodified", date('Y-m-d H:i:s'), false);
        $this->set("modifiedby", $auth->auth["uid"]);
        $success = parent::store();

        // @todo do update below only if code from above was successfully

        // Update name, email and newsletter type for recipients in pending
        // newsletter jobs
        $sName = $this->get("name");
        $sEmail = $this->get("email");
        if ($sName == "") {
            $sName = $sEmail;
        }
        $iNewsType = $this->get("news_type");

        $oLogs = new NewsletterLogCollection();
        $oLogs->setWhere("idnewsrcp", $this->get($this->getPrimaryKeyName()));
        $oLogs->setWhere("status", "pending");
        $oLogs->query();

        while ($oLog = $oLogs->next()) {
            $oLog->set("rcpname", $sName);
            $oLog->set("rcpemail", $sEmail);
            $oLog->set("rcpnewstype", $iNewsType);
            $oLog->store();
        }

        return $success;
    }

    /**
     * User-defined setter for newsletter recipients fields.
     *
     * @param string $name
     * @param mixed  $value
     * @param bool   $bSafe Flag to run defined inFilter on passed value
     *
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'news_type':
            case 'confirmed':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

}
