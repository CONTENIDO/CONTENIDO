<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Category management class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * @con_notice Status: Test. Not for production use
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *
 *   $Id: class.category.php 528 2008-07-02 13:29:28Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

###############################################
# Using generic DB - use at your own risk !!
################################################

class CategoryCollection extends ItemCollection
{
	function CategoryCollection ()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["cat"], "idcat");
		
		$this->_setItemClass("CategoryItem");
	}
}

class CategoryItem extends Item
{
	/**
     * Constructor Function
     * @param $id int Specifies the ID to load
     */
	function CategoryItem ($id = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["cat"], "idcat");
		
		$this->setFilters(array(), array());
		
		if ($id !== false)
		{
			$this->loadByPrimaryKey($id);
		}
	}
	
	
	function loadByPrimaryKey ($key)
	{
		if (parent::loadByPrimaryKey($key)) {
    		/* Load all child language items */
    		$catlangs = new CategoryLanguageCollection;
    		$catlangs->select("idcat = '$key'");
    		
    		while ($item = $catlangs->next())
    		{
    			$this->lang[$item->get("idlang")] = $item;	
    		}
            return true;
        }
        return false;
	}	
}

class CategoryLanguageCollection extends ItemCollection
{
	function CategoryLanguageCollection ()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["cat_lang"], "idcatlang");
		
		$this->_setItemClass("CategoryLanguageItem");
	}
	
}

class CategoryLanguageItem extends Item
{
	function CategoryLanguageItem ()
	{
		global $cfg;
		parent::Item($cfg["tab"]["cat_lang"], "idcatlang");
		
		$this->setFilters(array(), array());
	}
}
?>