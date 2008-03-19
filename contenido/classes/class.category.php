<?php
/*****************************************
* File      :   $RCSfile: class.category.php,v $
* Project   :   Contenido
* Descr     :   Category management class
* Modified  :   $Date: 2004/03/19 16:45:57 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.category.php,v 1.1 2004/03/19 16:45:57 timo.hummel Exp $
******************************************/

/* Status: Test. Not for production use */

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
		parent::loadByPrimaryKey($key);
		
		/* Load all child language items */
		$catlangs = new CategoryLanguageCollection;
		$catlangs->select("idcat = '$key'");
		
		while ($item = $catlangs->next())
		{
			$this->lang[$item->get("idlang")] = $item;	
		}
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