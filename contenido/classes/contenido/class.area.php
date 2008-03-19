<?php
/*****************************************
* File      :   $RCSfile: class.area.php,v $
* Project   :   Contenido
* Descr     :   Area management class
* Modified  :   $Date: 2004/08/04 07:56:18 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.area.php,v 1.2 2004/08/04 07:56:18 timo.hummel Exp $
******************************************/

cInclude('classes', 'class.genericdb.php');

class cApiAreaCollection extends ItemCollection
{
	/**
	 * Constructor
	 */
	function cApiAreaCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg['tab']['area'], 'idarea');
		$this->_setItemClass("cApiArea");
	}
}

class cApiArea extends Item
{
	/**
	 * Constructor
	 *
	 * @param integer area to load
	 */
	function cApiArea($idarea = false)
	{
		global $cfg;
		
		parent::Item($cfg['tab']['area'], 'idarea');
		$this->setFilters(array("addslashes"), array("stripslashes"));

		if ($idarea !== false)
		{
			$this->loadByPrimaryKey($idarea);	
		}
	}
	
	function create ($name, $parentid = 0, $relevant = 1, $online = 1)
	{
		$item = parent::create();
		
		$item->set("name", $name);
		$item->set("relevant", $relevant);
		$item->set("online", $online);	
		$item->set("parent_id", $parentid);
		
		$item->store();
		
		return ($item);		
	}
	
	function createAction ($area, $name, $code, $location, $relevant)
	{
		$ac = new cApiActionCollection;
		
		$a = $ac->create($area, $name, $code, $location, $relevant);
	}
	
	
}

?>