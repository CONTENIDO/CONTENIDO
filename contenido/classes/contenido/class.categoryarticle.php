<?php
/*****************************************
* File      :   $RCSfile: class.categoryarticle.php,v $
* Project   :   Contenido
* Descr     :   Category access class
* Modified  :   $Date: 2005/08/30 09:24:19 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.categoryarticle.php,v 1.3 2005/08/30 09:24:19 timo.hummel Exp $
******************************************/
cInclude("classes", "class.genericdb.php");

class cApiCategoryArticleCollection extends ItemCollection
{
	function cApiCategoryArticleCollection ($select = false)
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["cat_art"], "idcatart");
		$this->_setItemClass("cApiCategoryArticle");
		$this->_setJoinPartner("cApiCategoryCollection");
		$this->_setJoinPartner("cApiArticleCollection");
		
		if ($select !== false)
		{
			$this->select($select);	
		}
	}
}

class cApiCategoryArticle extends Item
{
	function cApiCategoryArticle ($idcatart = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["cat_art"], "idcatart");
		$this->setFilters(array(), array());
		
		if ($idcatart !== false)
		{
			$this->loadByPrimaryKey($idcatart);	
		}
	}
	
}

?>