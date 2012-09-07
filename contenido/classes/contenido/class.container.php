<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Template access class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.3
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2004-08-04
 *
 *   $Id: class.container.php 742 2008-08-27 11:06:12Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("classes", "class.genericdb.php");

class cApiContainerCollection extends ItemCollection
{
	function cApiContainerCollection ($select = false)
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["container"], "idcontainer");
		$this->_setItemClass("cApiContainer");
		
		if ($select !== false)
		{
			$this->select($select);	
		}
	}
	
	function clearAssignments ($idtpl)
	{
		$this->select("idtpl = '$idtpl'");
		
		while ($item = $this->next())
		{
			$this->delete($item->get("idcontainer"));
		}
	}
	
	function assignModule ($idtpl, $number, $module)
	{
		$this->select("idtpl = '$idtpl' AND number = '$number'");
		
		if ($item = $this->next())
		{
			$item->set("module", $module);
			$item->store();	
		} else {
			$this->create($idtpl, $number, $module);
		}
	}
	
	function create ($idtpl, $number, $module)
	{
		$item = parent::create();
		$item->set("idtpl", $idtpl);
		$item->set("number", $number);
		$item->set("module", $module);
		$item->store();
	}

}

class cApiContainer extends Item
{
	function cApiContainer ($idcontainer = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["container"], "idcontainer");
		$this->setFilters(array(), array());
		
		if ($idcontainer !== false)
		{
			$this->loadByPrimaryKey($idcontainer);	
		}
	}
}

?>