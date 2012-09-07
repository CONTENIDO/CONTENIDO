<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Article access class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.2
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2007-05-25
 *
 *   $Id: class.articlelanguage.php 742 2008-08-27 11:06:12Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("classes", "class.genericdb.php");
cInclude("classes", "contenido/class.article.php");

class cApiArticleLanguageCollection extends ItemCollection
{
	function cApiArticleLanguageCollection ($select = false)
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["art_lang"], "idartlang");
		$this->_setItemClass("cApiArticleLanguage");
		$this->_setJoinPartner("cApiArticleCollection");
		
		if ($select !== false)
		{
			$this->select($select);	
		}
	}
}

class cApiArticleLanguage extends Item
{
	function cApiArticleLanguage ($idartlang = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["art_lang"], "idartlang");
		$this->setFilters(array(), array());
		
		if ($idartlang !== false)
		{
			$this->loadByPrimaryKey($idartlang);	
		}
	}
	
}

?>