<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Frame Files management class
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
 *   $Id: class.framefile.php 742 2008-08-27 11:06:12Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


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