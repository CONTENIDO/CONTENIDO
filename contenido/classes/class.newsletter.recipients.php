<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Newsletter recipient class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.4
 * @author     Björn Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2004-08-01
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *
 *   $Id: class.newsletter.recipients.php 531 2008-07-02 13:30:54Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.newsletter.groups.php");
cInclude("classes", "class.newsletter.logs.php");

/**
 * Recipient management class
 */
class RecipientCollection extends ItemCollection
{
	/**
	* Constructor Function
	* @param none
	*/
	function RecipientCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["news_rcp"], "idnewsrcp");
		$this->_setItemClass("Recipient");
	}

	/**
	* Loads an item by its ID (primary key)
	* @param $itemID integer Specifies the item ID to load
	*/	
	function loadItem ($itemID)
	{
		$oItem = new Recipient();
		$oItem->loadByPrimaryKey($itemID);
		return ($oItem);
	}

	/**
	* Creates a new recipient
	* @param string	$sEMail 		Specifies the e-mail adress
	* @param string	$sName 			Specifies the recipient name (optional)
	* @param int	$iConfirmed 	Specifies, if the recipient is confirmed (optional)
	* @param string	$sJoinID 		Specifies additional recipient group ids to join (optional, e.g. 47,12,...)
	* @param int	$iMessageType 	Specifies the message type for the recipient (0 = text, 1 = html)
	*/		
	function create ($sEMail, $sName = "", $iConfirmed = 0, $sJoinID = "", $iMessageType = 0)
	{
		global $client, $lang, $auth;
		
		$iConfirmed   = (int)$iConfirmed;
		$iMessageType = (int)$iMessageType;
		
		/* Check if the e-mail adress already exists */
		$email = strtolower($email); // e-mail always lower case
		$this->setWhere("idclient", $client);
		$this->setWhere("idlang", 	$lang);
		$this->setWhere("email", 	$sEMail);
		$this->query();

		if ($this->next())
		{
			return $this->create($sEMail."_".substr(md5(rand()),0,10), $sName, 0, $sJoinID, $iMessageType); // 0: Deactivate 'confirmed'
		}
		$oItem = parent::create();
		$oItem->set("idclient", 	$client);
		$oItem->set("idlang",		$lang);
		$oItem->set("name",			$sName);
		$oItem->set("email",		$sEMail);
		$oItem->set("hash",			substr(md5(rand()),0,17) . uniqid("")); // Generating UID, 30 characters
		$oItem->set("confirmed",	$iConfirmed);
		$oItem->set("news_type",	$iMessageType);
		
		if ($iConfirmed) {
			$oItem->set("confirmeddate", date("Y-m-d H:i:s"), false);
		}
		$oItem->set("deactivated",	0);
		$oItem->set("created", 		date("Y-m-d H:i:s"), false);
  		$oItem->set("author",		$auth->auth["uid"]);
		$oItem->store();

		$iIDRcp = $oItem->get("idnewsrcp"); // Getting internal id of new recipient
		
		// Add this recipient to the default recipient group (if available)
		$oGroups 		= new RecipientGroupCollection;
		$oGroupMembers 	= new RecipientGroupMemberCollection;

		$oGroups->setWhere("idclient",		$client);
		$oGroups->setWhere("idlang", 		$lang);
		$oGroups->setWhere("defaultgroup",	1);
		$oGroups->query();
			
		while ($oGroup = $oGroups->next())
		{
			$iIDGroup = $oGroup->get("idnewsgroup");
			$oGroupMembers->create($iIDGroup, $iIDRcp);
		}

		// Add to other recipient groups as well? Do so!
		if ($sJoinID != "")
		{
			$aJoinID = explode(",", $sJoinID);

			if (count($aJoinID) > 0)
			{
				foreach ($aJoinID as $iIDGroup)
				{
					$oGroupMembers->create($iIDGroup, $iIDRcp);
				}		
			}
		}
		
		return $oItem;
	}

	/*
	* Overridden delete method to remove recipient from groupmember table
	* before deleting recipient
	*
	* @param $itemID int specifies the recipient
	*/		
	function delete ($itemID)
	{
		$oAssociations = New RecipientGroupMemberCollection;
		$oAssociations->setWhere("idnewsrcp", $itemID);
		$oAssociations->query();
		
		While ($oItem = $oAssociations->next())
		{
			$oAssociations->delete($oItem->get("idnewsgroupmember"));	
		}
		parent::delete($itemID);
	}

	/*
	* Purge method to delete recipients which hasn't been confirmed since over a month
	* @param  $timeframe int	Days after creation a not confirmed recipient will be removed
	* @return int 			Count of deleted recipients	
	*/		
	function purge ($timeframe) {
		global $client, $lang;
		
		$oRecipientCollection = new RecipientCollection;

		// DATEDIFF(created, NOW()) > 30 would be better, but it's only available in MySQL V4.1.1 and above
		// Note, that, TO_DAYS or NOW may not be available in other database systems than MySQL
		$oRecipientCollection->setWhere("idclient", $client);
		$oRecipientCollection->setWhere("idlang", 	$lang);
		$oRecipientCollection->setWhere("confirmed", 0);
		$oRecipientCollection->setWhere("(TO_DAYS(NOW()) - TO_DAYS(created))", $timeframe, ">");
		$oRecipientCollection->query();
		
		while ($oItem = $oRecipientCollection->next())
		{
			$oRecipientCollection->delete($oItem->get("idnewsrcp"));
		}
		return $oRecipientCollection->count();
	}

	/*
	* checkEMail returns true, if there is no recipient with the same e-mail address; otherwise false
	* @param  $email string	e-mail
	* @return recpient item if item with e-mail exists, false otherwise
	*/		
	function emailExists ($sEmail)
	{
		global $client, $lang;
		
		$oRecipientCollection = new RecipientCollection;

		$oRecipientCollection->setWhere("idclient", $client);
		$oRecipientCollection->setWhere("idlang", 	$lang);  
      	$oRecipientCollection->setWhere("email", 	strtolower($sEmail));
      	$oRecipientCollection->query();
      
      	if ($oItem = $oRecipientCollection->next())
      	{
			return $oItem;
		} else {
			return false;
		}
	}

	/**
	* Sets a key for all recipients without key or an old key (len(key) <> 30)
	* @param none
	*/		
	function updateKeys()
	{
		
		$this->setWhere("LENGTH(hash)", 30, "<>");
		$this->query();
		
		$iUpdated = $this->count();
		while ($oItem = $this->next())
		{
			$oItem->set("hash", substr(md5(rand()),0,17) . uniqid("")); /* Generating UID, 30 characters */  		
			$oItem->store();
		}
		
		return $iUpdated;
	}
}

/**
 * Single Recipient Item
 */
class Recipient extends Item
{
	
	/**
	* Constructor Function
	* @param string $table The table to use as information source
	*/
	function Recipient($idnewsrcp = false)
	{
		global $cfg;
		
		parent::Item($cfg["tab"]["news_rcp"], "idnewsrcp");
		
		if ($idnewsrcp !== false)
		{
			$this->loadByPrimaryKey($idnewsrcp);	
		}
	}
	
	/**
	* Checks if the given md5 matches the md5(email) in the database
	* @param $md5email string md5 of E-Mail to check
	* @return boolean True if the hash matches, false otherwise
	* @deprecated 4.6.15 - 10.08.2006
	*/		
	function checkMD5Email ($md5email)
	{
		if ($md5email == md5($this->get("email")))
		{
			return true;
		} else {
			return false;
		}
	}
	
	function store ()
	{
		global $auth;
		
		$this->set("lastmodified", date("Y-m-d H:i:s"), false);
  		$this->set("modifiedby", $auth->auth["uid"]);
  		parent::store();

  		// Update name, email and newsletter type for recipients in pending newsletter jobs  		
  		$sName		= $this->get("name");
  		$sEmail		= $this->get("email");
		if ($sName == "") {
			$sName 	= $sEmail;
		}
		$iNewsType	= $this->get("news_type");
		
		$oLogs = new cNewsletterLogCollection;
		$oLogs->setWhere("idnewsrcp",	$this->get($this->primaryKey));
		$oLogs->setWhere("status",		"pending");
		$oLogs->query();
				
		while ($oLog = $oLogs->next())
		{
			$oLog->set("rcpname",		$sName);
			$oLog->set("rcpemail",		$sEmail);
			$oLog->set("rcpnewstype",	$iNewsType);
			$oLog->store();
		}
	}	
}

?>