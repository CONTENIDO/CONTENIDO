<?php
/*****************************************
* File      :   $RCSfile: class.frontend.groups.php,v $
* Project   :   Contenido
* Descr     :   Frontend groups class
* Modified  :   $Date: 2005/11/08 17:20:31 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.frontend.groups.php,v 1.6 2005/11/08 17:20:31 timo.hummel Exp $
******************************************/
cInclude("classes", "class.genericdb.php");

/**
 * Frontend group management class
 */
class FrontendGroupCollection extends ItemCollection {
	
	/**
     * Constructor Function
     * @param none
     */
	function FrontendGroupCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["frontendgroups"], "idfrontendgroup");
		$this->_setItemClass("FrontendGroup");		
	}

	/**
     * Loads an item by its ID (primary key)
     * @param $itemID integer Specifies the item ID to load
     */	
	function loadItem ($itemID)
	{
		$item = new FrontendGroup();
		$item->loadByPrimaryKey($itemID);
		return ($item);
	}

	/**
     * Creates a new group
     * @param $groupname string Specifies the groupname
     * @param $password string Specifies the password (optional)
     */		
	function create ($groupname)
	{
		global $client;
		
		$group = new FrontendGroup;
		
		$_arrInFilters = array('urlencode', 'htmlspecialchars', 'addslashes');

		$mangledGroupName = $group->_inFilter($groupname);		
		$this->select("idclient = '$client' AND groupname = '$mangledGroupName'");
		
		if ($obj = $this->next())
		{
			$groupname = $groupname. md5(rand());	
		}
		
		$item = parent::create();
		
		$item->set("idclient", $client);
		$item->set("groupname", $groupname);
		$item->store();
		
		return $item;
	}

   /*
   * Overridden delete method to remove groups from groupmember table
   * before deleting group
   *
   * @param $itemID int specifies the frontend user group
   */      
   function delete ($itemID)
   {
      $associations = New FrontendGroupMemberCollection;
      $associations->select("idfrontendgroup = '$itemID'");
      
      while ($item = $associations->next())
      {
         $associations->delete($item->get("idfrontendgroupmember"));   
      }
      parent::delete($itemID);   
   }	
}

/**
 * Single FrontendGroup Item
 */
class FrontendGroup extends Item {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function FrontendGroup()
	{
		global $cfg;
		
		parent::Item($cfg["tab"]["frontendgroups"], "idfrontendgroup");
	}

	
}


/**
 * Frontend group member management class
 */
class FrontendGroupMemberCollection extends ItemCollection {
	
	/**
     * Constructor Function
     * @param none
     */
	function FrontendGroupMemberCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["frontendgroupmembers"], "idfrontendgroupmember");
		$this->_setJoinPartner ('FrontendGroupCollection');
		$this->_setJoinPartner ('FrontendUserCollection');
		$this->_setItemClass("FrontendGroupMember");
	}

	/**
     * Loads an item by its ID (primary key)
     * @param $itemID integer Specifies the item ID to load
     */	
	function loadItem ($itemID)
	{
		$item = new FrontendGroupMember();
		$item->loadByPrimaryKey($itemID);
		return ($item);
	}

	/**
     * Creates a new association
     * @param $idfrontendgroup int specifies the frontend group
     * @param $idfrontenduser  int specifies the frontend user
     */		
	function create ($idfrontendgroup, $idfrontenduser)
	{
		$this->select("idfrontendgroup = '$idfrontendgroup' AND idfrontenduser = '$idfrontenduser'");

		if ($this->next())
		{
			return false;	
		}
		
		$item = parent::create();
		
		$item->set("idfrontenduser", $idfrontenduser);
		$item->set("idfrontendgroup", $idfrontendgroup);
		$item->store();
		
		return $item;
	}

	/**
     * Removes an association
     * @param $idfrontendgroup int specifies the frontend group
     * @param $idfrontenduser  int specifies the frontend user
     */		
	function remove ($idfrontendgroup, $idfrontenduser)
	{
		$this->select("idfrontendgroup = '$idfrontendgroup' AND idfrontenduser = '$idfrontenduser'");
		
		if ($item = $this->next())
		{
			$this->delete($item->get("idfrontendgroupmember"));	
		}		
	}
	
	/**
     * Returns all users in a single group
     * @param $idfrontendgroup int specifies the frontend group
	 * @param $asObjects boolean specifies if the function should return objects
	 * @return array FrontendUser items 
     */		
	function getUsersInGroup ($idfrontendgroup, $asObjects = true)
	{
		$this->select("idfrontendgroup = '$idfrontendgroup'");
        
		
		$objects = array();
		
		while ($item = $this->next())
		{
			if ($asObjects)
			{
				$user = new FrontendUser;
				$user->loadByPrimaryKey($item->get("idfrontenduser"));
				$objects[] = $user;
			} else {
				$objects[] = $item->get("idfrontenduser");
			}	
		}
		
		return ($objects);
	}	
	
}

/**
 * Single FrontendGroup Item
 */
class FrontendGroupMember extends Item {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function FrontendGroupMember()
	{
		global $cfg;
		
		parent::Item($cfg["tab"]["frontendgroupmembers"], "idfrontendgroupmember");
	}

	
}
?>