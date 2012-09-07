<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Action management class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.4
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2006-06-09
 *
 *   $Id: class.action.php 742 2008-08-27 11:06:12Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude('classes', 'class.genericdb.php');
cInclude('classes', 'contenido/class.area.php');

class cApiActionCollection extends ItemCollection
{
	/**
	 * Constructor
	 */
	function cApiActionCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg['tab']['actions'], 'idaction');
		$this->_setItemClass("cApiAction");
	}
	
	function create ($area, $name, $code = "", $location = "", $relevant = 1)
	{
		$item = parent::create();
		
		if (is_string($area))
		{
			$c = new cApiArea;
			$c->loadBy("name", $area);
			
			if ($c->virgin)
			{
				$area = 0;
				cWarning(__FILE__, __LINE__, "Could not resolve area [$area] passed to method [create], assuming 0");	
			} else {
				$area = $c->get("idarea");
			} 
		}
		
		$item->set("idarea", $area);
		$item->set("name", $name);
		$item->set("code", $code);	
		$item->set("location", $location);
		$item->set("relevant", $relevant);
		
		$item->store();
		
		return ($item);		
	}	
}

class cApiAction extends Item
{
	var $_objectInvalid;
	
	/**
	 * Constructor
	 *
	 * @param integer area to load
	 */
	function cApiAction($idaction = false)
	{
		global $cfg;
		$this->_objectInvalid = false;
		
		parent::Item($cfg['tab']['actions'], 'idaction');
		$this->setFilters(array("addslashes"), array("stripslashes"));

		if ($idaction !== false)
		{
			$this->loadByPrimaryKey($idaction);	
		}
		
		$this->_wantParameters = array();
	}
}

?>