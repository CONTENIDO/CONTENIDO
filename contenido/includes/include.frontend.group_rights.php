<?php
/*****************************************
* File      :   $RCSfile: include.frontend.group_rights.php,v $
* Project   :   Contenido
* Descr     :   Frontend group rights editor
* Modified  :   $Date: 2006/09/22 08:57:55 $
*
* © four for business AG, www.4fb.de
*
* $Id: include.frontend.group_rights.php,v 1.5 2006/09/22 08:57:55 bjoern.behrens Exp $
******************************************/
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "class.ui.php");
cInclude("classes", "class.frontend.permissions.php");

$page = new cPage;

if (!in_array($useplugin, $cfg['plugins']['frontendlogic']))
{
	$page->setContent(i18n("Invalid plugin"));
	
} else {
	
	cInclude("plugins", "frontendlogic/$useplugin/".$useplugin.".php");
    
    $className = "frontendlogic_".$useplugin;
	$class = new $className;
	$perms = new FrontendPermissionCollection;
	
	
	$rights = new UI_Table_Form("rights");
	$rights->setVar("area", $area);
	$rights->setVar("frame", $frame);
	$rights->setVar("useplugin", $useplugin);
	$rights->setVar("idfrontendgroup", $idfrontendgroup);
	$rights->setVar("action", "fegroups_save_perm");

	$actions = $class->listActions();
	$items = $class->listItems();
	
	if ($action == "fegroups_save_perm")
	{
		$myitems = $items;
		$myitems["__GLOBAL__"] = "__GLOBAL__";
		
   		foreach ($actions as $action => $text)
   		{
   			foreach ($myitems as $item => $text)
			{
				
    			if ($item === "__GLOBAL__")
    			{
    				$varname = "action_$action";
    			} else {
    				$varname = "item_".$item."_$action";	
    			}
    			
    			if ($_POST[$varname] == 1)
    			{
    				$perms->setPerm($idfrontendgroup, $useplugin, $action, $item);
    			} else {
    				$perms->removePerm($idfrontendgroup, $useplugin, $action, $item);
    			}
    		}
		}
		
	}	
	
	$rights->addHeader(sprintf(i18n("Permissions for plugin '%s'"), $class->getFriendlyName()));
	
	foreach ($actions as $key => $action)
	{
		$check[$key] = new cHTMLCheckbox("action_$key", 1);
		$check[$key]->setLabelText($action." ".i18n("(All)"));
		
		if ($perms->checkPerm($idfrontendgroup, $useplugin, $key, "__GLOBAL__"))
		{
			$check[$key]->setChecked(true);
		}
	}
	
	$rights->add(i18n("Global rights"), $check);

    foreach ($actions as $key => $action)
    {
    	unset($check);
    	
    	if (count($items) > 0)
    	{
	    	foreach ($items as $item => $value)
	    	{
		    	$check[$item] = new cHTMLCheckbox("item_".$item."_".$key, 1);
	    		$check[$item]->setLabelText($value);
	    		
	    		if ($perms->checkPerm($idfrontendgroup, $useplugin, $key, $item))
	    		{
	    			$check[$item]->setChecked(true);
	    		}    		
	    	
	    	}
	    	
	    	$rights->add($action, $check);
    	} else {
    		$rights->add($action, i18n("No items found"));	
    	}
    }	
	
	$page->setContent($rights->render());
}

$page->render();
?>