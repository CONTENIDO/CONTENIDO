<?php
/**
 * This file contains the Recipient groups class.
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
 * Recipient group management class.
 *
 * @package Plugin
 * @subpackage Newsletter
 * @method NewsletterRecipientGroup createNewItem
 * @method NewsletterRecipientGroup next
 */
class NewsletterRecipientGroupCollection extends ItemCollection {
    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        $cfg = cRegistry::getConfig();
        parent::__construct($cfg["tab"]["news_groups"], "idnewsgroup");
        $this->_setItemClass("NewsletterRecipientGroup");
    }

    /**
     * Creates a new group
     *
     * @param $groupname    string Specifies the groupname
     * @param $defaultgroup integer Specifies, if group is default group
     *                      (optional)
     *
     * @return Item
     * @throws cException
     */
    public function create($groupname, $defaultgroup = 0) {
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();
        $group = new NewsletterRecipientGroup();

        // _arrInFilters = ['urlencode', 'htmlspecialchars', 'addslashes'];

        $mangledGroupName = $group->_inFilter($groupname);
        $this->setWhere("idclient", $client);
        $this->setWhere("idlang", $lang);
        $this->setWhere("groupname", $mangledGroupName);
        $this->query();

        if ($obj = $this->next()) {
            $groupname = $groupname . md5(rand());
        }

        $item = $this->createNewItem();
        $item->set("idclient", $client);
        $item->set("idlang", $lang);
        $item->set("groupname", $groupname);
        $item->set("defaultgroup", $defaultgroup);
        $item->store();

        return $item;
    }

    /**
     * Overridden delete method to remove groups from group member table
     * before deleting group
     *
     * @param $itemID int specifies the newsletter recipient group
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function delete($itemID) {
        $oAssociations = new NewsletterRecipientGroupMemberCollection();
        $oAssociations->setWhere("idnewsgroup", $itemID);
        $oAssociations->query();

        while ($oItem = $oAssociations->next()) {
            $oAssociations->delete($oItem->get("idnewsgroupmember"));
        }
        parent::delete($itemID);
    }

}

/**
 * Single RecipientGroup Item
 */
class NewsletterRecipientGroup extends Item {
    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($mId = false) {
        $cfg = cRegistry::getConfig();
        parent::__construct($cfg["tab"]["news_groups"], "idnewsgroup");
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Overridden store() method to ensure, that there is only one default group
     *
     * @throws cException
     */
    public function store() {
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();

        if ($this->get("defaultgroup") == 1) {
            $oItems = new NewsletterRecipientGroupCollection();
            $oItems->setWhere("idclient", $client);
            $oItems->setWhere("idlang", $lang);
            $oItems->setWhere("defaultgroup", 1);
            $oItems->setWhere("idnewsgroup", $this->get("idnewsgroup"), "<>");
            $oItems->query();

            while ($oItem = $oItems->next()) {
                $oItem->set("defaultgroup", 0);
                $oItem->store();
            }
        }
        return parent::store();
    }

    /**
     * User-defined setter for newsletter recipient group fields.
     *
     * @param string $name
     * @param mixed  $value
     * @param bool   $bSafe Flag to run defined inFilter on passed value
     *
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idlang':
            case 'idclient':
                $value = (int) $value;
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

}

/**
 * Recipient group member management class
 *
 * @method NewsletterRecipientGroupMember createNewItem
 * @method NewsletterRecipientGroupMember next
 */
class NewsletterRecipientGroupMemberCollection extends ItemCollection {
    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg["tab"]["news_groupmembers"], "idnewsgroupmember");
        $this->_setJoinPartner('NewsletterRecipientGroupCollection');
        $this->_setJoinPartner('NewsletterRecipientCollection');
        $this->_setItemClass("NewsletterRecipientGroupMember");
    }

    /**
     * Creates a new association
     *
     * @param $idrecipientgroup int specifies the newsletter group
     * @param $idrecipient      int specifies the newsletter user
     *
     * @return bool|Item
     * @throws cDbException
     * @throws cException
     */
    public function create($idrecipientgroup, $idrecipient) {
        $this->setWhere("idnewsgroup", $idrecipientgroup);
        $this->setWhere("idnewsrcp", $idrecipient);
        $this->query();

        if ($this->next()) {
            return false;
        }

        $oItem = parent::createNewItem();

        $oItem->set("idnewsrcp", $idrecipient);
        $oItem->set("idnewsgroup", $idrecipientgroup);
        $oItem->store();

        return $oItem;
    }

    /**
     * Removes an association
     *
     * @param $idrecipientgroup int specifies the newsletter group
     * @param $idrecipient      int specifies the newsletter user
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function remove($idrecipientgroup, $idrecipient) {
        $idrecipientgroup = cSecurity::toInteger($idrecipientgroup);
        $idrecipient = cSecurity::toInteger($idrecipient);

        $this->setWhere("idnewsgroup", $idrecipientgroup);
        $this->setWhere("idnewsrcp", $idrecipient);
        $this->query();

        if ($oItem = $this->next()) {
            $this->delete($oItem->get("idnewsgroupmember"));
        }
    }

    /**
     * Removes all associations from any newsletter group
     *
     * @param $idrecipient int specifies the newsletter recipient
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function removeRecipientFromGroups($idrecipient) {
        $idrecipient = cSecurity::toInteger($idrecipient);

        $this->setWhere("idnewsrcp", $idrecipient);
        $this->query();

        while ($oItem = $this->next()) {
            $this->delete($oItem->get("idnewsgroupmember"));
        }
    }

    /**
     * Removes all associations of a newsletter group
     *
     * @param $idgroup int specifies the newsletter recipient group
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function removeGroup($idgroup) {
        $idgroup = cSecurity::toInteger($idgroup);

        $this->setWhere("idnewsgroup", $idgroup);
        $this->query();

        while ($oItem = $this->next()) {
            $this->delete($oItem->get("idnewsgroupmember"));
        }
    }

    /**
     * Returns all recipients in a single group
     *
     * @param $idrecipientgroup int specifies the newsletter group
     * @param $asObjects        boolean specifies if the function should return objects
     *
     * @return array RecipientRecipient items
     * @throws cDbException
     * @throws cException
     */
    public function getRecipientsInGroup($idrecipientgroup, $asObjects = true) {
        $idrecipientgroup = cSecurity::toInteger($idrecipientgroup);

        $this->setWhere("idnewsgroup", $idrecipientgroup);
        $this->query();

        $aObjects = [];

        while ($oItem = $this->next()) {
            if ($asObjects) {
                $oRecipient = new NewsletterRecipient();
                $oRecipient->loadByPrimaryKey($oItem->get("idnewsrcp"));

                $aObjects[] = $oRecipient;
            } else {
                $aObjects[] = $oItem->get("idnewsrcp");
            }
        }

        return ($aObjects);
    }

}

/**
 * Single RecipientGroup Item
 */
class NewsletterRecipientGroupMember extends Item {
    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($mId = false) {
        $cfg = cRegistry::getConfig();
        parent::__construct($cfg["tab"]["news_groupmembers"], "idnewsgroupmember");
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * User-defined setter for newsletter recipient group member fields.
     *
     * @param string $name
     * @param mixed  $value
     * @param bool   $bSafe Flag to run defined inFilter on passed value
     *
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idnewsrcp':
            case 'idnewsgroup':
                $value = (int) $value;
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

}
