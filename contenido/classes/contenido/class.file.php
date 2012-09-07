<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Area management class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.2
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2004-08-04
 *
 *   $Id: class.file.php 742 2008-08-27 11:06:12Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


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