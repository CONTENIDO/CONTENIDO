<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Layout class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2004-08-07
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


class cApiLayoutCollection extends ItemCollection
{
	function cApiLayoutCollection ()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["lay"], "idlay");
		$this->_setItemClass("cApiLayout");
	}
	
	function create ($title)
	{
		global $client;
		$item = parent::create();
		$item->set("name", $title);
		$item->set("idclient", $client);
		$item->store();
		return ($item);	
	}
}

class cApiLayout extends Item
{
	function cApiLayout ()
	{
		global $cfg;
		parent::Item($cfg["tab"]["lay"], "idlay");
		$this->setFilters(array(), array());
	}
}
 
?>