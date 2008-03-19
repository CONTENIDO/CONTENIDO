<?php
/*****************************************
* File      :   $RCSfile: class.templateconfig.php,v $
* Project   :   Contenido
* Descr     :   Template access class
* Modified  :   $Date: 2004/08/04 07:56:18 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.templateconfig.php,v 1.3 2004/08/04 07:56:18 timo.hummel Exp $
******************************************/
cInclude("classes", "class.genericdb.php");


class cApiTemplateConfigurationCollection extends ItemCollection
{
	function cApiTemplateConfigurationCollection ($select = false)
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["tpl_conf"], "idtplcfg");
		$this->_setItemClass("cApiTemplateConfiguration");
		
		if ($select !== false)
		{
			$this->select($select);	
		}
	}
	
	function create ($idtpl)
	{
		$item = parent::create();
		$item->set("idtpl", $idtpl);
		$item->store();
		
		return ($item);
	}
	
}

class cApiTemplateConfiguration extends Item
{
	function cApiTemplateConfiguration ($idtplcfg = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["tpl_conf"], "idtplcfg");
		$this->setFilters(array(), array());
		
		if ($idtplcfg !== false)
		{
			$this->loadByPrimaryKey($idtplcfg);	
		}
	}
}

?>