<?php
/*****************************************
* File      :   $RCSfile: class.article.php,v $
* Project   :   Contenido
* Descr     :   Category access class
* Modified  :   $Date: 2004/08/04 07:56:18 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.article.php,v 1.2 2004/08/04 07:56:18 timo.hummel Exp $
******************************************/
cInclude("classes", "class.genericdb.php");
cInclude("includes", "functions.str.php");

class cApiArticleCollection extends ItemCollection
{
	function cApiArticleCollection ($select = false)
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["art"], "idart");
		$this->_setItemClass("cApiArticle");
		
		if ($select !== false)
		{
			$this->select($select);	
		}
	}
}

class cApiArticle extends Item
{
	function cApiArticle ($idart = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["art"], "idart");
		$this->setFilters(array(), array());
		
		if ($idart !== false)
		{
			$this->loadByPrimaryKey($idart);	
		}
	}
	
}

?>