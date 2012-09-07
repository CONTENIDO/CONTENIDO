<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Category access class
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
 *   $Id: class.article.php 742 2008-08-27 11:06:12Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("classes", "class.genericdb.php");
cInclude("includes", "functions.str.php");

class cApiArticleCollection extends ItemCollection
{
	function cApiArticleCollection ($select = false)
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["art"], "idart");
		$this->_setItemClass("cApiArticle");
		
		if ($select !== false)
		{
			$this->select($select);	
		}
	}
}

class cApiArticle extends Item
{
	function cApiArticle ($idart = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["art"], "idart");
		$this->setFilters(array(), array());
		
		if ($idart !== false)
		{
			$this->loadByPrimaryKey($idart);	
		}
	}
	
}

?>