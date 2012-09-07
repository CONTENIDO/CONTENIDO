<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Language management class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.4
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2007-05-25
 *
 *   $Id: class.lang.php 742 2008-08-27 11:06:12Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude('classes', 'class.genericdb.php');


class cApiLanguageCollection extends ItemCollection
{
/**
	 * Constructor
	 */
	function cApiLanguageCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["lang"], "idlang");
		$this->_setItemClass("cApiLanguage");
		$this->_setJoinPartner("cApiClientLanguageCollection");
	}	
}

class cApiLanguage extends Item
{
	function cApiLanguage ($idlang = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["lang"], "idlang");
		
		if ($idlang !== false)
		{
			$this->loadByPrimaryKey($idlang);	
		}
	}
}

?>