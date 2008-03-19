<?php
/*****************************************
* File      :   $RCSfile: class.container.php,v $
* Project   :   Contenido
* Descr     :   Template access class
* Modified  :   $Date: 2004/08/04 07:56:18 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.container.php,v 1.3 2004/08/04 07:56:18 timo.hummel Exp $
******************************************/
cInclude("classes", "class.genericdb.php");

class cApiContainerCollection extends ItemCollection
{
	function cApiContainerCollection ($select = false)
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["container"], "idcontainer");
		$this->_setItemClass("cApiContainer");
		
		if ($select !== false)
		{
			$this->select($select);	
		}
	}
	
	function clearAssignments ($idtpl)
	{
		$this->select("idtpl = '$idtpl'");
		
		while ($item = $this->next())
		{
			$this->delete($item->get("idcontainer"));
		}
	}
	
	function assignModule ($idtpl, $number, $module)
	{
		$this->select("idtpl = '$idtpl' AND number = '$number'");
		
		if ($item = $this->next())
		{
			$item->set("module", $module);
			$item->store();	
		} else {
			$this->create($idtpl, $number, $module);
		}
	}
	
	function create ($idtpl, $number, $module)
	{
		$item = parent::create();
		$item->set("idtpl", $idtpl);
		$item->set("number", $number);
		$item->set("module", $module);
		$item->store();
	}

}

class cApiContainer extends Item
{
	function cApiContainer ($idcontainer = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["container"], "idcontainer");
		$this->setFilters(array(), array());
		
		if ($idcontainer !== false)
		{
			$this->loadByPrimaryKey($idcontainer);	
		}
	}
}

?>