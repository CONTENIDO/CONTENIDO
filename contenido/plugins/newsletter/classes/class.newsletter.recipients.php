<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Newsletter recipient class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.1
 * @author     Björn Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  2004-08-01
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Recipient management class
 */
class NewsletterRecipientCollection extends ItemCollection
{
    /**
     * Constructor Function
     * @param none
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg["tab"]["news_rcp"], "idnewsrcp");
        $this->_setItemClass("NewsletterRecipient");
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function NewsletterRecipientCollection()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    /**
     * Creates a new recipient
     * @param string  $sEMail        Specifies the e-mail adress
     * @param string  $sName         Specifies the recipient name (optional)
     * @param int     $iConfirmed    Specifies, if the recipient is confirmed (optional)
     * @param string  $sJoinID       Specifies additional recipient group ids to join (optional, e.g. 47,12,...)
     * @param int     $iMessageType  Specifies the message type for the recipient (0 = text, 1 = html)
     */
    public function create($sEMail, $sName = "", $iConfirmed = 0, $sJoinID = "", $iMessageType = 0)
    {
        global $client, $lang, $auth;

        $iConfirmed   = (int)$iConfirmed;
        $iMessageType = (int)$iMessageType;

        /* Check if the e-mail adress already exists */
        $email = strtolower($email); // e-mail always lower case
        $this->setWhere("idclient", $client);
        $this->setWhere("idlang", $lang);
        $this->setWhere("email", $sEMail);
        $this->query();

        if ($this->next()) {
            return $this->create($sEMail."_".substr(md5(rand()),0,10), $sName, 0, $sJoinID, $iMessageType); // 0: Deactivate 'confirmed'
        }
        $oItem = parent::createNewItem();
        $oItem->set("idclient", $client);
        $oItem->set("idlang", $lang);
        $oItem->set("name", $sName);
        $oItem->set("email", $sEMail);
        $oItem->set("hash", substr(md5(rand()),0,17) . uniqid("")); // Generating UID, 30 characters
        $oItem->set("confirmed", $iConfirmed);
        $oItem->set("news_type", $iMessageType);

        if ($iConfirmed) {
            $oItem->set("confirmeddate", date("Y-m-d H:i:s"), false);
        }
        $oItem->set("deactivated", 0);
        $oItem->set("created", date("Y-m-d H:i:s"), false);
        $oItem->set("author", $auth->auth["uid"]);
        $oItem->store();

        $iIDRcp = $oItem->get("idnewsrcp"); // Getting internal id of new recipient

        // Add this recipient to the default recipient group (if available)
        $oGroups       = new NewsletterRecipientGroupCollection();
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
     */
    public function delete($itemID)
    {
        $oAssociations = new NewsletterRecipientGroupMemberCollection();
        $oAssociations->setWhere("idnewsrcp", $itemID);
        $oAssociations->query();

        While ($oItem = $oAssociations->next()) {
            $oAssociations->delete($oItem->get("idnewsgroupmember"));
        }
        parent::delete($itemID);
    }

    /**
     * Purge method to delete recipients which hasn't been confirmed since over a month
     * @param  $timeframe int    Days after creation a not confirmed recipient will be removed
     * @return int             Count of deleted recipients
     */
    public function purge($timeframe)
    {
        global $client, $lang;

        $oRecipientCollection = new NewsletterRecipientCollection();

        // DATEDIFF(created, NOW()) > 30 would be better, but it's only available in MySQL V4.1.1 and above
        // Note, that, TO_DAYS or NOW may not be available in other database systems than MySQL
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
     * checkEMail returns true, if there is no recipient with the same e-mail address; otherwise false
     * @param  $email string    e-mail
     * @return recpient item if item with e-mail exists, false otherwise
     */
    public function emailExists($sEmail)
    {
        global $client, $lang;

        $oRecipientCollection = new NewsletterRecipientCollection();

        $oRecipientCollection->setWhere("idclient", $client);
        $oRecipientCollection->setWhere("idlang", $lang);
        $oRecipientCollection->setWhere("email", strtolower($sEmail));
        $oRecipientCollection->query();

        if ($oItem = $oRecipientCollection->next()) {
            return $oItem;
        } else {
            return false;
        }
    }

    /**
     * Sets a key for all recipients without key or an old key (len(key) <> 30)
     * @param none
     */
    public function updateKeys()
    {
        $this->setWhere("LENGTH(hash)", 30, "<>");
        $this->query();

        $iUpdated = $this->count();
        while ($oItem = $this->next()) {
            $oItem->set("hash", substr(md5(rand()),0,17) . uniqid("")); /* Generating UID, 30 characters */
            $oItem->store();
        }

        return $iUpdated;
    }
}


/**
 * Single Recipient Item
 */
class NewsletterRecipient extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["news_rcp"], "idnewsrcp");
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function NewsletterRecipient($mId = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }

    public function store()
    {
        global $auth;

        $this->set("lastmodified", date("Y-m-d H:i:s"), false);
        $this->set("modifiedby", $auth->auth["uid"]);
        $success = parent::store();

        // @todo do update below only if code from abve was successfull

        // Update name, email and newsletter type for recipients in pending newsletter jobs
        $sName  = $this->get("name");
        $sEmail = $this->get("email");
        if ($sName == "") {
            $sName = $sEmail;
        }
        $iNewsType = $this->get("news_type");

        $oLogs = new NewsletterLogCollection();
        $oLogs->setWhere("idnewsrcp", $this->get($this->primaryKey));
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
}

/** @deprecated 2012-03-01 Use NewsletterRecipientCollection instead */
class RecipientCollection extends NewsletterRecipientCollection {
    /** @deprecated 2012-03-01 Use NewsletterRecipientCollection instead */
    public function __construct()
    {
        cDeprecated("Use NewsletterRecipientCollection instead");
        $this->__construct();
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function RecipientCollection()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }
}

/** @deprecated 2012-03-01 Use NewsletterRecipient instead */
class Recipient extends NewsletterRecipient {
    /** @deprecated 2012-03-01 Use NewsletterRecipient instead */
    public function __construct()
    {
        cDeprecated("Use NewsletterRecipient instead");
        $this->__construct();
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function Recipient()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }
}
?>