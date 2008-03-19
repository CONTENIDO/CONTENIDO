<?php
/*****************************************
* File      :   $RCSfile: class.category.php,v $
* Project   :   Contenido
* Descr     :   Category access class
* Modified  :   $Date: 2005/08/30 09:24:19 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.category.php,v 1.4 2005/08/30 09:24:19 timo.hummel Exp $
******************************************/
cInclude("classes", "class.genericdb.php");

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