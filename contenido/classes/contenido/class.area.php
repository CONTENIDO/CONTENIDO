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
 *   created 2004-08-04
 *
 *   $Id: class.area.php 742 2008-08-27 11:06:12Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude('classes', 'class.genericdb.php');

class cApiAreaCollection extends ItemCollection
{
	/**
	 * Constructor
	 */
	function cApiAreaCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg['tab']['area'], 'idarea');
		$this->_setItemClass("cApiArea");
	}
}

class cApiArea extends Item
{
	/**
	 * Constructor
	 *
	 * @param integer area to load
	 */
	function cApiArea($idarea = false)
	{
		global $cfg;
		
		parent::Item($cfg['tab']['area'], 'idarea');
		$this->setFilters(array("addslashes"), array("stripslashes"));

		if ($idarea !== false)
		{
			$this->loadByPrimaryKey($idarea);	
		}
	}
	
	function create ($name, $parentid = 0, $relevant = 1, $online = 1)
	{
		$item = parent::create();
		
		$item->set("name", $name);
		$item->set("relevant", $relevant);
		$item->set("online", $online);	
		$item->set("parent_id", $parentid);
		
		$item->store();
		
		return ($item);		
	}
	
	function createAction ($area, $name, $code, $location, $relevant)
	{
		$ac = new cApiActionCollection;
		
		$a = $ac->create($area, $name, $code, $location, $relevant);
	}
	
	
}

?>