<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Frontend permissions class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.5.1
 * @author     unknowm
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
 *   $Id: class.frontend.permissions.php 425 2008-06-30 14:53:17Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.genericdb.php");

/**
 * Frontend user management class
 */
class FrontendPermissionCollection extends ItemCollection
{
	
	var $_FrontendPermission;
	
	/**
     * Constructor Function
     * @param none
     */
	function FrontendPermissionCollection()
	{
		global $cfg;
		$this->_FrontendPermission = new FrontendPermission;
		
		parent::ItemCollection($cfg["tab"]["frontendpermissions"], "idfrontendpermission");
	}

	/**
     * Loads an item by its ID (primary key)
     * @param $itemID integer Specifies the item ID to load
     */	
	function loadItem ($itemID)
	{
		$item = new FrontendPermission();
		$item->loadByPrimaryKey($itemID);
		return ($item);
	}

	/**
     * Creates a new permission entry
     * @param $group string Specifies the frontend group
     * @param $plugin string Specifies the plugin
	 * @param $action string Specifies the action
	 * @param $item   string Specifies the item
     */		
	function create ($group, $plugin, $action, $mitem)
	{
		global $lang;
		
		
		
		if (!$this->checkPerm($group, $plugin, $action, $mitem))
		{
			$item = parent::create();
    		$item->set("idlang", $lang);
    		$item->set("idfrontendgroup", $group);
    		$item->set("plugin", $plugin);
    		$item->set("action", $action);
    		$item->set("item", $mitem);
      		
    		$item->store();
		}
		
		return $item;
	}
	
	function setPerm ($group, $plugin, $action, $item)
	{
		$this->create($group, $plugin, $action, $item);	
	}
	
	function checkPerm ($group, $plugin, $action, $item, $uselang = false)
	{
		global $lang;
		
		if ($uselang !== false)
		{
			$checklang = $uselang;	
		} else {
			$checklang = $lang;
		}
		
		$group  = $this->_FrontendPermission->_inFilter($group);
		$plugin = $this->_FrontendPermission->_inFilter($plugin);
		$action = $this->_FrontendPermission->_inFilter($action);
		$item   = $this->_FrontendPermission->_inFilter($item);

		/* 
		 * Check for global permisson
		 */
		$this->select("idlang = '$lang' AND idfrontendgroup = '$group' AND plugin = '$plugin' AND action = '$action' AND item = '__GLOBAL__'");

		if ($this->next())
		{
			return true;
		}

		/* 
		 * Check for item permisson
		 */
		 		
		$this->select("idlang = '$lang' AND idfrontendgroup = '$group' AND plugin = '$plugin' AND action = '$action' AND item = '$item'");

		if ($this->next())
		{
			return true;	
		} else {
			return false;
		}
	}
	
	function removePerm ($group, $plugin, $action, $item, $uselang = false)
	{
		global $lang;
		
		if ($uselang !== false)
		{
			$checklang = $uselang;	
		} else {
			$checklang = $lang;
		}
		
		$group  = $this->_FrontendPermission->_inFilter($group);
		$plugin = $this->_FrontendPermission->_inFilter($plugin);
		$action = $this->_FrontendPermission->_inFilter($action);
		$item   = $this->_FrontendPermission->_inFilter($item);
		
		$this->select("idlang = '$lang' AND idfrontendgroup = '$group' AND plugin = '$plugin' AND action = '$action' AND item = '$item'");
		
		if ($myitem = $this->next())
		{
			$this->delete($myitem->get("idfrontendpermission"));	
		}
	}	
	
}

/**
 * Single FrontendPermission Item
 */
class FrontendPermission extends Item {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function FrontendPermission()
	{
		global $cfg;
		
		parent::Item($cfg["tab"]["frontendpermissions"], "idfrontendpermission");
	}

}
?>