<?php
/*****************************************
* File      :   $RCSfile: class.action.php,v $
* Project   :   Contenido
* Descr     :   Action management class
* Modified  :   $Date: 2006/06/09 12:46:27 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.action.php,v 1.4 2006/06/09 12:46:27 timo.hummel Exp $
******************************************/

cInclude('classes', 'class.genericdb.php');
cInclude('classes', 'contenido/class.area.php');

class cApiActionCollection extends ItemCollection
{
	/**
	 * Constructor
	 */
	function cApiActionCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg['tab']['actions'], 'idaction');
		$this->_setItemClass("cApiAction");
	}
	
	function create ($area, $name, $code = "", $location = "", $relevant = 1)
	{
		$item = parent::create();
		
		if (is_string($area))
		{
			$c = new cApiArea;
			$c->loadBy("name", $area);
			
			if ($c->virgin)
			{
				$area = 0;
				cWarning(__FILE__, __LINE__, "Could not resolve area [$area] passed to method [create], assuming 0");	
			} else {
				$area = $c->get("idarea");
			} 
		}
		
		$item->set("idarea", $area);
		$item->set("name", $name);
		$item->set("code", $code);	
		$item->set("location", $location);
		$item->set("relevant", $relevant);
		
		$item->store();
		
		return ($item);		
	}	
}

class cApiAction extends Item
{
	var $_objectInvalid;
	
	/**
	 * Constructor
	 *
	 * @param integer area to load
	 */
	function cApiAction($idaction = false)
	{
		global $cfg;
		$this->_objectInvalid = false;
		
		parent::Item($cfg['tab']['actions'], 'idaction');
		$this->setFilters(array("addslashes"), array("stripslashes"));

		if ($idaction !== false)
		{
			$this->loadByPrimaryKey($idaction);	
		}
		
		$this->_wantParameters = array();
	}
}

?>