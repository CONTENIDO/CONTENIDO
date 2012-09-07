<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Recipient groups class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.3
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
 *   $Id: class.newsletter.groups.php 412 2008-06-30 11:52:48Z dominik.ziegler $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.newsletter.recipients.php");

/**
 * Recipient group management class
 */
class RecipientGroupCollection extends ItemCollection
{
	/**
	* Constructor Function
	* @param none
	*/
	function RecipientGroupCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["news_groups"], "idnewsgroup");
		$this->_setItemClass("RecipientGroup");
	}

	/**
	* Loads an item by its ID (primary key)
	* @param $itemID integer Specifies the item ID to load
	*/
	function loadItem ($itemID)
	{
		$oItem = new RecipientGroup($itemID);
		return ($oItem);
	}

	/**
	* Creates a new group
	* @param $groupname string Specifies the groupname
	* @param $defaultgroup integer Specfies, if group is default group (optional)
	*/
	function create ($groupname, $defaultgroup = 0)
	{
		global $client, $lang;
		
		$client = Contenido_Security::toInteger($client);
		$lang	= Contenido_Security::toInteger($lang);

		$group = new RecipientGroup;

		$_arrInFilters = array('urlencode', 'htmlspecialchars', 'addslashes');

		$mangledGroupName = $group->_inFilter($groupname);
		$this->setWhere("idclient", $client);
		$this->setWhere("idlang", 	$lang);
		$this->setWhere("groupname", $mangledGroupName);
		$this->query();

		if ($obj = $this->next())
		{
			$groupname = $groupname . md5(rand());
		}

		$item = parent::create();

		$item->set("idclient", $client);
		$item->set("idlang", $lang);
		$item->set("groupname", $groupname);
		$item->set("defaultgroup", $defaultgroup);
		$item->store();

		return $item;
	}

	/*
	* Overridden delete method to remove groups from groupmember table
	* before deleting group
	*
	* @param $itemID int specifies the newsletter recipient group
	*/
	function delete ($itemID) {
		$oAssociations = new RecipientGroupMemberCollection;
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
class RecipientGroup extends Item {

	/**
	* Constructor Function
	* @param string $table The table to use as information source
	*/
	function RecipientGroup($idnewsgroup = false)
	{
		global $cfg;

		parent::Item($cfg["tab"]["news_groups"], "idnewsgroup");
		
		if ($idnewsgroup !== false)
		{
			$this->loadByPrimaryKey($idnewsgroup);	
		}
	}
	
	/**
	 * Overriden store() method to ensure, that there is only one default group
	 **/
	function store()
	{
		global $client, $lang;
		
		$client = Contenido_Security::toInteger($client);
		$lang 	= Contenido_Security::toInteger($lang);
		
		if ($this->get("defaultgroup") == 1)
		{
			$oItems = new RecipientGroupCollection;
			$oItems->setWhere("idclient", $client);
			$oItems->setWhere("idlang", $lang);
			$oItems->setWhere("defaultgroup", 1);
			$oItems->setWhere("idnewsgroup", $this->get("idnewsgroup"), "<>");
			$oItems->query();

			while ($oItem = $oItems->next())
			{
				$oItem->set("defaultgroup", 0);
				$oItem->store();
			}
		}
		parent::store();
	}
}

/**
 * Recipient group member management class
 */
class RecipientGroupMemberCollection extends ItemCollection {

	/**
	* Constructor Function
	* @param none
	*/
	function RecipientGroupMemberCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["news_groupmembers"], "idnewsgroupmember");
		$this->_setJoinPartner ('RecipientGroupCollection');
		$this->_setJoinPartner ('RecipientCollection');
		$this->_setItemClass("RecipientGroupMember");
	}

	/**
	* Loads an item by its ID (primary key)
	* @param $itemID integer Specifies the item ID to load
	*/
	function loadItem ($itemID)
	{
		$oItem = new RecipientGroupMember();
		$oItem->loadByPrimaryKey($itemID);
		return ($oItem);
	}

	/**
	* Creates a new association
	* @param $idrecipientgroup int specifies the newsletter group
	* @param $idrecipient  int specifies the newsletter user
	*/
	function create ($idrecipientgroup, $idrecipient)
	{
		$idrecipientgroup	= Contenido_Security::toInteger($idrecipientgroup);
		$idrecipient 		= Contenido_Security::toInteger($idrecipient);
	
		$this->setWhere("idnewsgroup", $idrecipientgroup);
		$this->setWhere("idnewsrcp", $idrecipient);
		$this->query();

		if ($this->next())
		{
			return false;
		}

		$oItem = parent::create();

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
	function remove ($idrecipientgroup, $idrecipient)
	{
		$idrecipientgroup	= Contenido_Security::toInteger($idrecipientgroup);
		$idrecipient 		= Contenido_Security::toInteger($idrecipient);
		
		$this->setWhere("idnewsgroup", $idrecipientgroup);
		$this->setWhere("idnewsrcp", $idrecipient);
		$this->query();

		if ($oItem = $this->next())
		{
			$this->delete($oItem->get("idnewsgroupmember"));
		}
	}

	/**
	* Removes all associations from any newsletter group
	* @param $idrecipient  int specifies the newsletter recipient
	*/
	function removeRecipientFromGroups ($idrecipient)
	{
		$idrecipient = Contenido_Security::toInteger($idrecipient);
		
		$this->setWhere("idnewsrcp", $idrecipient);
		$this->query();

		while ($oItem = $this->next())
		{
			$this->delete($oItem->get("idnewsgroupmember"));
		}
	}

		/**
	* Removes all associations of a newsletter group
	* @param $idgroup  int specifies the newsletter recipient group
	*/
	function removeGroup ($idgroup)
	{
		$idgroup = Contenido_Security::toInteger($idgroup);
		
		$this->setWhere("idnewsgroup", $idgroup);
		$this->query();

		while ($oItem = $this->next())
		{
			$this->delete($oItem->get("idnewsgroupmember"));
		}
	}

	/**
	* Returns all recipients in a single group
	* @param $idrecipientgroup int specifies the newsletter group
	* @param $asObjects boolean specifies if the function should return objects
	* @return array RecipientRecipient items
	*/
	function getRecipientsInGroup ($idrecipientgroup, $asObjects = true)
	{
		$idrecipientgroup = Contenido_Security::toInteger($idrecipientgroup);
	
		$this->setWhere("idnewsgroup", $idrecipientgroup);
		$this->query();

		$aObjects = array();

		while ($oItem = $this->next())
		{
			if ($asObjects)
			{
				$oRecipient = new Recipient;
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
class RecipientGroupMember extends Item
{
	/**
	* Constructor Function
	* @param string $table The table to use as information source
	*/
	function RecipientGroupMember()
	{
		global $cfg;

		parent::Item($cfg["tab"]["news_groupmembers"], "idnewsgroupmember");
	}
}

?>