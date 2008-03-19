<?php
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