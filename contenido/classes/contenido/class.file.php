<?php
/*****************************************
* File      :   $RCSfile: class.file.php,v $
* Project   :   Contenido
* Descr     :   Files management class
* Modified  :   $Date: 2004/08/04 07:56:18 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.file.php,v 1.2 2004/08/04 07:56:18 timo.hummel Exp $
******************************************/

cInclude('classes', 'class.genericdb.php');
cInclude('classes', 'contenido/class.area.php');
cInclude('classes', 'contenido/class.framefile.php');

class cApiFileCollection extends ItemCollection
{
	/**
	 * Constructor
	 */
	function cApiFileCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg['tab']['files'], 'idfile');
		$this->_setItemClass("cApiFile");
	}
}

class cApiFile extends Item
{
	/**
	 * Constructor
	 *
	 * @param integer area to load
	 */
	function cApiFile($idfile = false)
	{
		global $cfg;
		
		$this->setFilters(array("addslashes"), array("stripslashes"));

		if (idfile !== false)
		{
			$this->loadByPrimaryKey($idfile);	
		}
	}
	
	function create ($area, $filename, $filetype = "main")
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
		$item->set("filename", $filename);
		
		if ($filetype != "main")
		{
			$item->set("filetype", "inc");
		} else {
			$item->set("filetype", "main");
		}
		
		$item->store();
		
		return ($item);		
	}
	
}

?>