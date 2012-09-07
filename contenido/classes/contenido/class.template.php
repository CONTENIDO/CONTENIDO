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
 * @version    1.4
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2004-08-04
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: class.template.php 353 2008-06-27 12:12:11Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.genericdb.php");
cInclude("classes", "class.security.php");

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
		$sql = "UPDATE ".$cfg["tab"]["tpl"]." SET defaulttemplate = 0 WHERE idclient = '" . Contenido_Security::toInteger($client) . "'";
		$db->query($sql);
		
		$sql = "UPDATE ".$cfg["tab"]["tpl"]." SET defaulttemplate = 1 WHERE idtpl = '" . Contenido_Security::toInteger($idtpl) . "'";
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