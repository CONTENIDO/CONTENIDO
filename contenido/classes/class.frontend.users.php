<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Frontend user class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend classes
 * @version    1.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  unknown
 *   modified 2008-06-30, Frederic Schneider, add security fix
 *   modified 2011-03-14, Murat Purc, adapted to new GenericDB partly ported to PHP 5, formatting
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Frontend user management class
 */
class FrontendUserCollection extends ItemCollection
{
    /**
     * Constructor Function
     * @param none
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg["tab"]["frontendusers"], "idfrontenduser");
        $this->_setItemClass("FrontendUser");
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function FrontendUserCollection()
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }

    /**
     * Checks if a specific user already exists
     * @param $sUsername string specifies the username to search for
     */
    public function userExists($sUsername)
    {
        global $client;

        $oFrontendUserCollection = new FrontendUserCollection();

        $oFrontendUserCollection->setWhere("idclient", $client);
        $oFrontendUserCollection->setWhere("username", strtolower($sUsername));
        $oFrontendUserCollection->query();

        if ($oItem = $oFrontendUserCollection->next()) {
            return ($oItem);
        } else {
            return false;
        }
    }

    /**
     * Creates a new user
     * @param $username string Specifies the username
     * @param $password string Specifies the password (optional)
     */
    public function create($username, $password = "")
    {
        global $client, $auth;

        // Check if the username already exists
        $this->select("idclient='".Contenido_Security::toInteger($client)."' AND username='".urlencode($username)."'");

        if ($this->next()) {
            return $this->create($username."_".substr(md5(rand()),0,10), $password);
        }

        $item = parent::create();
        $item->set("idclient", $client);
        $item->set("username", $username);
        $item->set("password", $password);
        $item->set("created", date("Y-m-d H:i:s"), false);
        $item->set("author", $auth->auth["uid"]);
        $item->set("active", 0);

        $item->store();

        // Put this user into the default groups
        $fegroups = new cApiFrontendGroupCollection();
        $fegroups->select("idclient = '".Contenido_Security::toInteger($client)."' AND defaultgroup='1'");

        $members = new cApiFrontendGroupMemberCollection();

        $iduser = $item->get("idfrontenduser");

        while ($fegroup = $fegroups->next()) {
            $idgroup = $fegroup->get("idfrontendgroup");
            $members->create($idgroup, $iduser);
        }

        return $item;
    }

    /**
     * Overridden delete method to remove user from groupmember table
     * before deleting user
     *
     * @param $itemID int specifies the frontend user
     */
    public function delete($itemID)
    {
        $associations = new cApiFrontendGroupMemberCollection();
        $associations->select("idfrontenduser = '$itemID'");

        while ($item = $associations->next()) {
            $associations->delete($item->get("idfrontendgroupmember"));
        }
        parent::delete($itemID);
    }
}


/**
 * Single FrontendUser Item
 */
class FrontendUser extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["frontendusers"], "idfrontenduser");
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function FrontendUser($mId = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct($mId);
    }

    /**
     * Overridden setField method to md5 the password
     * Sets the value of a specific field
     *
     * @param string $field Specifies the field to set
     * @param string $value Specifies the value to set
     */
    public function setField($field, $value, $safe = true)
    {
        if ($field == "password") {
            parent::setField($field, md5($value), $safe);
        } else {
            parent::setField($field, $value, $safe);
        }
    }

    /**
     * setRawPassword: Sets the password to a raw value
     * without md5 encoding.
     *
     * @param string $password Raw password
     */
    public function setRawPassword($password)
    {
        return parent::setField("password", $password);
    }

    /**
     * Checks if the given password matches the password in the database
     * @param $password string Password to check
     * @return boolean True if the password is correct, false otherwise
     */
    public function checkPassword($password)
    {
        if (md5($password) == $this->get("password")) {
            return true;
        } else {
            return false;
        }
    }

    public function store()
    {
        global $auth;

        $this->set("modified", date("Y-m-d H:i:s"), false);
        $this->set("modifiedby", $auth->auth["uid"]);
        return parent::store();
    }

    public function getGroupsForUser()
    {
        $FrontendGroupMemberCollection = new cApiFrontendGroupMemberCollection();
        $FrontendGroupMemberCollection->setWhere("idfrontenduser", $this->get("idfrontenduser"));
        $FrontendGroupMemberCollection->query();

        $groups = array();

        while ($FrontendGroupMember = $FrontendGroupMemberCollection->next()) {
            $groups[] = $FrontendGroupMember->get("idfrontendgroup");
        }

        return ($groups);
    }
}

?>