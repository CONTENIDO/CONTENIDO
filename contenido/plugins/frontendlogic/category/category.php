<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0
 * @author     Andreas Lindner, Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 
 *
 *   $Id: 
 * }}
 * 
 */
 
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("classes", "class.frontend.logic.php");
cInclude("classes", "contenido/class.categorylanguage.php");
cInclude("classes", "contenido/class.categorytree.php");
cInclude("classes", "contenido/class.category.php");

class frontendlogic_category extends FrontendLogic
{
	function getFriendlyName ()
	{
		return i18n("Category", "frontendlogic_category");	
	}
	
	function listActions ()
	{
		$actions = array();
		$actions["access"] = i18n("Access category", "frontendlogic_category");
		
		return ($actions);	
	}
	
	function listItems ()
	{
		global $lang;
		
		$cApiCategoryCollection = new cApiCategoryCollection;
		$cApiCategoryCollection->link("cApiCategoryLanguageCollection");
		
		$cApiCategoryCollection->setWhere("cApiCategoryLanguageCollection.idlang", $lang);
		$cApiCategoryCollection->setWhere("cApiCategoryLanguageCollection.public", 0);
		$cApiCategoryCollection->link("cApiCategoryTreeCollection");
		$cApiCategoryCollection->setOrder("cApiCategoryTreeCollection.idtree asc");
		
		$cApiCategoryCollection->query();
		
		$items = array();
		
		while ($cApiCategory = $cApiCategoryCollection->next())
		{
			$cApiCategoryLanguage = $cApiCategoryCollection->fetchObject("cApiCategoryLanguageCollection");
			$cApiCategoryTree = $cApiCategoryCollection->fetchObject("cApiCategoryTreeCollection");
			$items[$cApiCategoryLanguage->get("idcatlang")] = 
				'<span style="padding-left: '.($cApiCategoryTree->get("level")*10).'px;">'.htmldecode($cApiCategoryLanguage->get("name")).'</span>';
				
		}
		
		return ($items);
	}
}
?>