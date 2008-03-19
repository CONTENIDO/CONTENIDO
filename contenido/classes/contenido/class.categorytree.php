<?php
/*****************************************
* File      :   $RCSfile: class.categorytree.php,v $
* Project   :   Contenido
* Descr     :   Category access class
* Modified  :   $Date: 2005/08/30 09:24:19 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.categorytree.php,v 1.3 2005/08/30 09:24:19 timo.hummel Exp $
******************************************/
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