<?php
/*****************************************
* File      :   $RCSfile: class.layout.php,v $
* Project   :   Contenido
* Descr     :   Template access class
* Modified  :   $Date: 2004/08/04 09:00:54 $
*
* © four for business AG, www.4fb.de
*
* $Id$
******************************************/
cInclude("classes", "class.genericdb.php");

class cApiLayoutCollection extends ItemCollection
{
	function cApiLayoutCollection ()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["lay"], "idlay");
		$this->_setItemClass("cApiLayout");
	}
	
	function create ($title)
	{
		global $client;
		$item = parent::create();
		$item->set("name", $title);
		$item->set("idclient", $client);
		$item->store();
		return ($item);	
	}
}

class cApiLayout extends Item
{
	function cApiLayout ()
	{
		global $cfg;
		parent::Item($cfg["tab"]["lay"], "idlay");
		$this->setFilters(array(), array());
	}
}
 
?>