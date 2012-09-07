<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
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
 *   $Id: class.containerconfig.php 742 2008-08-27 11:06:12Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("classes", "class.genericdb.php");

class cApiContainerConfigurationCollection extends ItemCollection
{
	function cApiContainerConfigurationCollection ($select = false)
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["container_conf"], "idcontainerc");
		$this->_setItemClass("cApiContainerConfiguration");
		
		if ($select !== false)
		{
			$this->select($select);	
		}
	}
	
	function create ($idtplcfg, $number, $container)
	{
		$item = parent::create();
		$item->set("idtplcfg", $idtplcfg);
		$item->set("number", $number);
		$item->set("container", $container);
		$item->store();	
	}
	
}

class cApiContainerConfiguration extends Item
{
	function cApiContainerConfiguration ($idcontainerc = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["container_conf"], "idcontainerc");
		$this->setFilters(array(), array());
		
		if ($idcontainerc !== false)
		{
			$this->loadByPrimaryKey($idcontainerc);	
		}
	}
}

?>