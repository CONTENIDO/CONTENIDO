<?php
/*****************************************
* File      :   $RCSfile: class.articlelanguage.php,v $
* Project   :   Contenido
* Descr     :   Article access class
* Modified  :   $Date: 2007/05/25 08:06:29 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.articlelanguage.php,v 1.4 2007/05/25 08:06:29 bjoern.behrens Exp $
******************************************/
cInclude("classes", "class.genericdb.php");
cInclude("classes", "contenido/class.article.php");

class cApiArticleLanguageCollection extends ItemCollection
{
	function cApiArticleLanguageCollection ($select = false)
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["art_lang"], "idartlang");
		$this->_setItemClass("cApiArticleLanguage");
		$this->_setJoinPartner("cApiArticleCollection");
		
		if ($select !== false)
		{
			$this->select($select);	
		}
	}
}

class cApiArticleLanguage extends Item
{
	function cApiArticleLanguage ($idartlang = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["art_lang"], "idartlang");
		$this->setFilters(array(), array());
		
		if ($idartlang !== false)
		{
			$this->loadByPrimaryKey($idartlang);	
		}
	}
	
}

?>