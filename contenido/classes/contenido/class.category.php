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
 * @version    1.4
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2005-08-30
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


class cApiCategoryCollection extends ItemCollection
{
	function cApiCategoryCollection ($select = false)
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["cat"], "idcat");
		$this->_setItemClass("cApiCategory");
		
		if ($select !== false)
		{
			$this->select($select);	
		}
	}

}

class cApiCategory extends Item
{
	function cApiCategory ($idcat = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["cat"], "idcat");
		$this->setFilters(array(), array());
		
		if ($idcat !== false)
		{
			$this->loadByPrimaryKey($idcat);	
		}
	}
}

?>