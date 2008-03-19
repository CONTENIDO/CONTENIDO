<?php
/*****************************************
* File      :   $RCSfile: class.containerconfig.php,v $
* Project   :   Contenido
* Descr     :   Template access class
* Modified  :   $Date: 2004/08/04 07:56:18 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.containerconfig.php,v 1.3 2004/08/04 07:56:18 timo.hummel Exp $
******************************************/
cInclude("classes", "class.genericdb.php");

class cApiContainerConfigurationCollection extends ItemCollection
{
	function cApiContainerConfigurationCollection ($select = false)
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["container_conf"], "idcontainerc");
		$this->_setItemClass("cApiContainerConfiguration");
		
		if ($select !== false)
		{
			$this->select($select);	
		}
	}
	
	function create ($idtplcfg, $number, $container)
	{
		$item = parent::create();
		$item->set("idtplcfg", $idtplcfg);
		$item->set("number", $number);
		$item->set("container", $container);
		$item->store();	
	}
	
}

class cApiContainerConfiguration extends Item
{
	function cApiContainerConfiguration ($idcontainerc = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["container_conf"], "idcontainerc");
		$this->setFilters(array(), array());
		
		if ($idcontainerc !== false)
		{
			$this->loadByPrimaryKey($idcontainerc);	
		}
	}
}

?>