<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Area management class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.2
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2003-12-14
 *   modified 2007-07-19
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("classes", "class.genericdb.php");

class cApiModuleHistoryCollection extends ItemCollection
{
	/**
     * Constructor Function
     * @param none
     */
	function cApiModuleHistoryCollection ()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["mod_history"], "idmodhistory");
	}
	
	function create ($idmod)
	{
		global $idclient, $cfg, $auth;
		
		$db = new DB_Contenido;
		
		$sql = "SELECT idclient, name, description, input, output, template, type FROM ".$cfg["tab"]["mod"]." WHERE idmod = '$idmod'";
		$db->query($sql);
		
		if ($db->next_record())
		{
			$item = parent::create();
	
			$item->set("idclient", $db->f("idclient"));
			$item->set("idmod", $idmod);
			$item->set("name", $db->f("name"));
			$item->set("description", $db->f("description"));
			$item->set("input", $db->f("input"));
			$item->set("output", $db->f("output"));
			$item->set("template", $db->f("template"));
			$item->set("type", $db->f("type"));
			$item->set("changedby", $auth->auth["uid"]);
			$item->set("changed", time());
			$item->store();
			return ($item);	
		}
		
		return false;		
	}
	
	function loadItem ($itemID)
	{
		$item = new CapiModuleHistory();
		$item->loadByPrimaryKey($itemID);
		return ($item);
	}
	
	function delete ($id)
	{
		return parent::delete($id);
	}
}

class cApiModuleHistory extends Item
{
	/**
     * Constructor Function
     * @param $id int Specifies the ID to load
     */
	function cApiModuleHistory ()
	{
		global $cfg;
		parent::Item($cfg["tab"]["mod_history"], "idmodhistory");
	}	
}

?>