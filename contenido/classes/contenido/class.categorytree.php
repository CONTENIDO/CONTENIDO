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
 *   created 2005-08-30
 *
 *   $Id: class.categorytree.php 742 2008-08-27 11:06:12Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("classes", "class.genericdb.php");

class cApiCategoryTreeCollection extends ItemCollection
{
	function cApiCategoryTreeCollection ($select = false)
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["cat_tree"], "idtree");
		$this->_setJoinPartner("cApiCategoryCollection");
		$this->_setItemClass("cApiTree");
		
		if ($select !== false)
		{
			$this->select($select);	
		}
	}

}

class cApiTree extends Item
{
	function cApiTree ($idtree = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["cat_tree"], "idtree");
		$this->setFilters(array(), array());
		
		if ($idtree !== false)
		{
			$this->loadByPrimaryKey($idtree);	
		}
	}
}

?>