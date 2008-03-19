<?php
/*****************************************
* File      :   $RCSfile: class.framefile.php,v $
* Project   :   Contenido
* Descr     :   Frame Files management class
* Modified  :   $Date: 2004/08/04 07:56:18 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.framefile.php,v 1.2 2004/08/04 07:56:18 timo.hummel Exp $
******************************************/

cInclude('classes', 'class.genericdb.php');
cInclude('classes', 'contenido/class.area.php');

class cApiFrameFileCollection extends ItemCollection
{
	/**
	 * Constructor
	 */
	function cApiFrameFileCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg['tab']['framefiles'], 'idframefile');
		$this->_setItemClass("cApiFrameFile");
	}
}

class cApiFrameFile extends Item
{
	/**
	 * Constructor
	 *
	 * @param integer area to load
	 */
	function cApiFrameFile($idframefile = false)
	{
		global $cfg;
		
		$this->setFilters(array("addslashes"), array("stripslashes"));

		if ($idframefile !== false)
		{
			$this->loadByPrimaryKey($idframefile);	
		}
	}
	
	function create ($area, $idframe, $idfile)
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
		$item->set("idfile", $idfile);
		$item->set("idframe", $idframe);
		
		$item->store();
		
		return ($item);		
	}
}

?>