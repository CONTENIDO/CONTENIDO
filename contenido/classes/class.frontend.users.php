<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Frontend user class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.1.7
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-30, Frederic Schneider, add security fix
 *
 *   $Id: class.frontend.users.php 425 2008-06-30 14:53:17Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.genericdb.php");
cInclude("classes", "class.frontend.groups.php");

/**
 * Frontend user management class
 */
class FrontendUserCollection extends ItemCollection {
	
	/**
     * Constructor Function
     * @param none
     */
	function FrontendUserCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["frontendusers"], "idfrontenduser");
		$this->_setItemClass("FrontendUser");
	}

	/**
     * Loads an item by its ID (primary key)
     * @param $itemID integer Specifies the item ID to load
     */	
	function loadItem ($itemID)
	{
		$item = new FrontendUser();
		$item->loadByPrimaryKey($itemID);
		return ($item);
	}

	/**
     * Checks if a specific user already exists
     * @param $sUsername string specifies the username to search for
     */	
	function userExists ($sUsername)
	{
		global $client;
		
		$oFrontendUserCollection = new FrontendUserCollection;
		
		$oFrontendUserCollection->setWhere("idclient", $client);	
		$oFrontendUserCollection->setWhere("username", strtolower($sUsername));
		$oFrontendUserCollection->query();
		
		if ($oItem = $oFrontendUserCollection->next())
		{
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
	function create ($username, $password = "")
	{
		global $client, $auth;
		
		/* Check if the username already exists */
		$this->select("idclient='".Contenido_Security::toInteger($client)."' AND username='".urlencode($username)."'");

		if ($this->next())
		{
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
		
		/* Put this user into the default groups */
		$fegroups = new FrontendGroupCollection;
		$fegroups->select("idclient = '".Contenido_Security::toInteger($client)."' AND defaultgroup='1'");

		$members = new FrontendGroupMemberCollection;
			
		$iduser = $item->get("idfrontenduser");
			
		while ($fegroup = $fegroups->next())
		{
			$idgroup = $fegroup->get("idfrontendgroup");			
			$members->create($idgroup, $iduser);
		}
		
		return $item;
	}

   /*
   * Overridden delete method to remove user from groupmember table
   * before deleting user
   *
   * @param $itemID int specifies the frontend user
   */      
   function delete ($itemID)
   {
      $associations = New FrontendGroupMemberCollection;
      $associations->select("idfrontenduser = '$itemID'");
      
      while ($item = $associations->next())
      {
         $associations->delete($item->get("idfrontendgroupmember"));   
      }
      parent::delete($itemID);   
   }	
}

/**
 * Single FrontendUser Item
 */
class FrontendUser extends Item {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function FrontendUser()
	{
		global $cfg;
		
		parent::Item($cfg["tab"]["frontendusers"], "idfrontenduser");
	}

	/**
     * Overridden setField method to md5 the password
     * Sets the value of a specific field
	 *
	 * @param string $field Specifies the field to set
	 * @param string $value Specifies the value to set
     */
    function setField ($field, $value, $safe = true)
    {
    	if ($field == "password")
    	{
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
    function setRawPassword($password)
    {
   		return parent::setField("password", $password);
    }		
	
	/**
     * Checks if the given password matches the password in the database
     * @param $password string Password to check
	 * @return boolean True if the password is correct, false otherwise
     */		
	function checkPassword ($password)
	{
		if (md5($password) == $this->get("password"))
		{
			return true;
		} else {
			return false;
		}
	}
	
	function store ()
	{
		global $auth;
		
		$this->set("modified", date("Y-m-d H:i:s"), false);
  		$this->set("modifiedby", $auth->auth["uid"]);
  		parent::store();
	}
	
	function getGroupsForUser ()
	{
		$FrontendGroupMemberCollection = new FrontendGroupMemberCollection;
		$FrontendGroupMemberCollection->setWhere("idfrontenduser", $this->get("idfrontenduser"));
		$FrontendGroupMemberCollection->query();
		
		$groups = array();
		
		while ($FrontendGroupMember = $FrontendGroupMemberCollection->next())
		{
			$groups[] = $FrontendGroupMember->get("idfrontendgroup");	
		}
		
		return ($groups);
	}
	
}

?>