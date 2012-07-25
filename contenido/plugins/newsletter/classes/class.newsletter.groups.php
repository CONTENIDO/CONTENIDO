<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Recipient groups class
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
 * Recipient group management class
 */
class NewsletterRecipientGroupCollection extends ItemCollection
{
    /**
     * Constructor Function
     * @param none
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg["tab"]["news_groups"], "idnewsgroup");
        $this->_setItemClass("NewsletterRecipientGroup");
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function NewsletterRecipientGroupCollection()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    /**
     * Creates a new group
     * @param $groupname string Specifies the groupname
     * @param $defaultgroup integer Specfies, if group is default group (optional)
     */
    public function create($groupname, $defaultgroup = 0)
    {
        global $client, $lang;

        $client = cSecurity::toInteger($client);
        $lang   = cSecurity::toInteger($lang);

        $group = new NewsletterRecipientGroup();

        #$_arrInFilters = array('urlencode', 'htmlspecialchars', 'addslashes');

        $mangledGroupName = $group->_inFilter($groupname);
        $this->setWhere("idclient", $client);
        $this->setWhere("idlang",     $lang);
        $this->setWhere("groupname", $mangledGroupName);
        $this->query();

        if ($obj = $this->next()) {
            $groupname = $groupname . md5(rand());
        }

        $item = parent::createNewItem();

        $item->set("idclient", $client);
        $item->set("idlang", $lang);
        $item->set("groupname", $groupname);
        $item->set("defaultgroup", $defaultgroup);
        $item->store();

        return $item;
    }

    /**
     * Overridden delete method to remove groups from groupmember table
     * before deleting group
     *
     * @param $itemID int specifies the newsletter recipient group
     */
    public function delete($itemID)
    {
        $oAssociations = new NewsletterRecipientGroupMemberCollection;
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
class NewsletterRecipientGroup extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["news_groups"], "idnewsgroup");
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function NewsletterRecipientGroup($mId = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }

    /**
     * Overriden store() method to ensure, that there is only one default group
     **/
    public function store()
    {
        global $client, $lang;

        $client = cSecurity::toInteger($client);
        $lang   = cSecurity::toInteger($lang);

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
}


/**
 * Recipient group member management class
 */
class NewsletterRecipientGroupMemberCollection extends ItemCollection
{
    /**
     * Constructor Function
     * @param none
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg["tab"]["news_groupmembers"], "idnewsgroupmember");
        $this->_setJoinPartner ('NewsletterRecipientGroupCollection');
        $this->_setJoinPartner ('NewsletterRecipientCollection');
        $this->_setItemClass("NewsletterRecipientGroupMember");
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function NewsletterRecipientGroupMemberCollection()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    /**
     * Creates a new association
     * @param $idrecipientgroup int specifies the newsletter group
     * @param $idrecipient  int specifies the newsletter user
     */
    public function create($idrecipientgroup, $idrecipient)
    {
        $idrecipientgroup = cSecurity::toInteger($idrecipientgroup);
        $idrecipient      = cSecurity::toInteger($idrecipient);

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
     * @param $idrecipientgroup int specifies the newsletter group
     * @param $idrecipient  int specifies the newsletter user
     */
    public function remove($idrecipientgroup, $idrecipient)
    {
        $idrecipientgroup = cSecurity::toInteger($idrecipientgroup);
        $idrecipient      = cSecurity::toInteger($idrecipient);

        $this->setWhere("idnewsgroup", $idrecipientgroup);
        $this->setWhere("idnewsrcp", $idrecipient);
        $this->query();

        if ($oItem = $this->next()) {
            $this->delete($oItem->get("idnewsgroupmember"));
        }
    }

    /**
     * Removes all associations from any newsletter group
     * @param $idrecipient  int specifies the newsletter recipient
     */
    public function removeRecipientFromGroups($idrecipient)
    {
        $idrecipient = cSecurity::toInteger($idrecipient);

        $this->setWhere("idnewsrcp", $idrecipient);
        $this->query();

        while ($oItem = $this->next()) {
            $this->delete($oItem->get("idnewsgroupmember"));
        }
    }

    /**
     * Removes all associations of a newsletter group
     * @param $idgroup  int specifies the newsletter recipient group
     */
    public function removeGroup($idgroup)
    {
        $idgroup = cSecurity::toInteger($idgroup);

        $this->setWhere("idnewsgroup", $idgroup);
        $this->query();

        while ($oItem = $this->next()) {
            $this->delete($oItem->get("idnewsgroupmember"));
        }
    }

    /**
     * Returns all recipients in a single group
     * @param $idrecipientgroup int specifies the newsletter group
     * @param $asObjects boolean specifies if the function should return objects
     * @return array RecipientRecipient items
     */
    public function getRecipientsInGroup($idrecipientgroup, $asObjects = true)
    {
        $idrecipientgroup = cSecurity::toInteger($idrecipientgroup);

        $this->setWhere("idnewsgroup", $idrecipientgroup);
        $this->query();

        $aObjects = array();

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
class NewsletterRecipientGroupMember extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["news_groupmembers"], "idnewsgroupmember");
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function NewsletterRecipientGroupMember($mId = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }
}

/** @deprecated 2012-03-01 Use NewsletterRecipientGroupCollection instead */
class RecipientGroupCollection extends NewsletterRecipientGroupCollection {
    /** @deprecated 2012-03-01 Use NewsletterRecipientGroupCollection instead */
    public function __construct()
    {
        cDeprecated("Use NewsletterRecipientGroupCollection instead");
        parent::__construct();
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function RecipientGroupCollection()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }
}

/** @deprecated 2012-03-01 Use NewsletterRecipientGroup instead */
class RecipientGroup extends NewsletterRecipientGroup {
    /** @deprecated 2012-03-01 Use NewsletterRecipientGroup instead */
    public function __construct()
    {
        cDeprecated("Use NewsletterRecipientGroup instead");
        parent::__construct();
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function RecipientGroup()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }
}

/** @deprecated 2012-03-01 Use NewsletterRecipientGroupMemberCollection instead */
class RecipientGroupMemberCollection extends NewsletterRecipientGroupMemberCollection {
    /** @deprecated 2012-03-01 Use NewsletterRecipientGroupMemberCollection instead */
    public function __construct()
    {
        cDeprecated("Use NewsletterRecipientGroupMemberCollection instead");
        parent::__construct();
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function RecipientGroupMemberCollection()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }
}

/** @deprecated 2012-03-01 Use NewsletterRecipientGroupMember instead */
class RecipientGroupMember extends NewsletterRecipientGroupMember {
    /** @deprecated 2012-03-01 Use NewsletterRecipientGroupMember instead */
    public function __construct()
    {
        cDeprecated("Use NewsletterRecipientGroupMember instead");
        parent::__construct();
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function RecipientGroup()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }
}
?>