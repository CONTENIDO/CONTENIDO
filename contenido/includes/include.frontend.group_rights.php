<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Frontend group rights editor
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.5.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created  2002-03-02
 *   modified 2008-06-16, Holger Librenz, Hotfic: checking for illegal calls
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

// @TODO: check the code beneath is necessary
if ($_REQUEST['useplugin'] != "category") {
    die ('Illegal call!');
}

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