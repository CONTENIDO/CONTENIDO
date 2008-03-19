<?php
/*****************************************
* File      :   $RCSfile: class.template.php,v $
* Project   :   Contenido
* Descr     :   Template access class
* Modified  :   $Date: 2004/08/04 09:00:54 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.template.php,v 1.4 2004/08/04 09:00:54 timo.hummel Exp $
******************************************/
cInclude("classes", "class.genericdb.php");

class cApiTemplateCollection extends ItemCollection
{
	function cApiTemplateCollection ($select = false)
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["tpl"], "idtpl");
		$this->_setItemClass("cApiTemplate");
		
		if ($select !== false)
		{
			$this->select($select);	
		}
	}

	function setDefaultTemplate ($idtpl)
	{
		global $cfg, $client;
		
		$db = new DB_Contenido;
		$sql = "UPDATE ".$cfg["tab"]["tpl"]." SET defaulttemplate = 0 WHERE idclient = '$client'";
		$db->query($sql);
		
		$sql = "UPDATE ".$cfg["tab"]["tpl"]." SET defaulttemplate = 1 WHERE idtpl = '$idtpl'";
		$db->query($sql);
	}	
}

class cApiTemplate extends Item
{
	function cApiTemplate ($idtpl = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["tpl"], "idtpl");
		$this->setFilters(array(), array());
		
		if ($idtpl !== false)
		{
			$this->loadByPrimaryKey($idtpl);	
		}
	}
}
?>