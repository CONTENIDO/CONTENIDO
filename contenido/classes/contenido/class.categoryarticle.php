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
 * @version    1.3
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2005-08-30
 *
 *   $Id: class.categoryarticle.php 742 2008-08-27 11:06:12Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("classes", "class.genericdb.php");

class cApiCategoryArticleCollection extends ItemCollection
{
	function cApiCategoryArticleCollection ($select = false)
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["cat_art"], "idcatart");
		$this->_setItemClass("cApiCategoryArticle");
		$this->_setJoinPartner("cApiCategoryCollection");
		$this->_setJoinPartner("cApiArticleCollection");
		
		if ($select !== false)
		{
			$this->select($select);	
		}
	}
}

class cApiCategoryArticle extends Item
{
	function cApiCategoryArticle ($idcatart = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["cat_art"], "idcatart");
		$this->setFilters(array(), array());
		
		if ($idcatart !== false)
		{
			$this->loadByPrimaryKey($idcatart);	
		}
	}
	
}

?>