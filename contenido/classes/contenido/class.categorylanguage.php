<?php
/*****************************************
* File      :   $RCSfile: class.categorylanguage.php,v $
* Project   :   Contenido
* Descr     :   Category access class
* Modified  :   $Date: 2005/11/08 16:24:39 $
*
* © four for business AG, www.4fb.de
*
* $Id$
******************************************/
cInclude("classes", "class.genericdb.php");

class cApiCategoryLanguageCollection extends ItemCollection
{
	function cApiCategoryLanguageCollection ($select = false)
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["cat_lang"], "idcatlang");
		$this->_setItemClass("cApiCategoryLanguage");
		$this->_setJoinPartner("cApiCategoryCollection");
		
		if ($select !== false)
		{
			$this->select($select);	
		}
	}
}

class cApiCategoryLanguage extends Item
{
	function cApiCategoryLanguage ($idcat = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["cat_lang"], "idcatlang");
		$this->setFilters(array(), array());
		
		if ($idcat !== false)
		{
			$this->loadByPrimaryKey($idcat);	
		}
	}
	
	function setField ($field, $value)
	{
		switch ($field)
		{
			case "name":
				$this->setField("urlname", $value);
				break;
			case "urlname":
				$value = htmlspecialchars(capiStrCleanURLCharacters($value), ENT_QUOTES);
				break;	
		}
		
		parent::setField($field, $value);
	}
	
	function assignTemplate ($idtpl)
	{
		$c_tplcfg = new cApiTemplateConfigurationCollection;
		
		if ($this->get("idtplcfg") != 0)
		{
			/* Remove old template first */
			$c_tplcfg->delete($this->get("idtplcfg"));	
		}
		
		$tplcfg = $c_tplcfg->create($idtpl);

		$this->set("idtplcfg", $tplcfg->get("idtplcfg"));
		$this->store();
		
		return ($tplcfg);
	}
	
	function getTemplate ()
	{
		$c_tplcfg = new cApiTemplateConfiguration($this->get("idtplcfg"));
		return ($c_tplcfg->get("idtpl"));
	}
	
	function hasStartArticle ()
	{
		cInclude("includes", "functions.str.php");
		return strHasStartArticle($this->get("idcat"), $this->get("idlang"));	
	}
	
}

?>