<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Edit form for layout
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-01-24
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "contenido/class.layout.php");
cInclude("classes", "class.htmlvalidator.php");
cInclude("classes", "class.ui.php");
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("external", "edit_area/class.edit_area.php");

if (!isset($idlay)) $idlay = 0;

$page = new cPage;
$layout = new cApiLayout;

if ($idlay != 0)
{
	$layout->loadByPrimaryKey($idlay);	
}

if ($action == "lay_new")
{
	if (!$perm->have_perm_area_action_anyitem($area, $action))
	{
		$notification->displayNotification("error", i18n("Permission denied"));	
	} else {
		$layouts = new cApiLayoutCollection;	
		$layout = $layouts->create(i18n("-- New Layout --"));
	}
}

if ($refreshtemplates != "")
{
		/* Update all templates for containers with mode fixed and mandatory */
		$sql = "SELECT idtpl FROM ".$cfg["tab"]["tpl"]." WHERE idlay = '".Contenido_Security::toInteger($idlay)."'";
		$db->query($sql);
		
		$fillTemplates = array();
		
		while ($db->next_record())
		{
			$fillTemplates[] = $db->f("idtpl");
		}
		
		foreach ($fillTemplates as $fillTemplate)
		{
			tplAutoFillModules($fillTemplate);
		}
}

if (!$layout->virgin)
{
	$msg = "";
	
    $tpl->reset();

	$idlay = $layout->get("idlay");
	$code = $layout->get("code");
	$name = $layout->get("name");
	$description = $layout->get("description");
	
	/* Search for duplicate containers */
	tplPreparseLayout($idlay);
	$ret = tplBrowseLayoutForContainers($idlay);
	
	if (strlen($ret) != 0)
	{
		$containers = explode("&", $ret);
		
		$types = array();
	
		foreach ($containers as $value)
		{
			if ($value != "") {
				$container[$value] = 0;
			
				/* Search for old-style CMS_CONTAINER[x] */
				$container[$value] += substr_count($code,"CMS_CONTAINER[$value]");

				/* Search for the new-style containers */
				$count = preg_match_all("/<container( +)id=\\\\\"$value\\\\\"(.*)>(.*)<\/container>/i", addslashes($code), $matches);

				$container[$value] += $count;
			
				if (is_array(tplGetContainerTypes($idlay, $value))) {
					$types = array_merge($types, tplGetContainerTypes($idlay, $value));
				}
			}
		}
		
		$types = array_unique($types);
		$layout->setProperty("layout", "used-types", implode($types, ";"));
		
		$msg = "";
		
		foreach ($container as $key => $value)
		{
			if ($value > 1)
			{
				$msg .= sprintf(i18n("Container %s was defined %s times"), $key, $value)."<br>"; 	
			}	
		}
	}

	/* Try to validate html */
	if (getEffectiveSetting("layout", "htmlvalidator", "true") == "true" && $code !== "")
	{
		$v = new cHTMLValidator;
		$v->validate($code);

		if (!$v->tagExists("body") && !$v->tagExists("BODY"))
		{
			$msg .= sprintf(i18n("The body tag does not exist in the layout. This is a requirement for the in-site editing."));
			$msg .= "<br>";	
		}
		
		if (!$v->tagExists("head") && !$v->tagExists("HEAD"))
		{
			$msg .= sprintf(i18n("The head tag does not exist in the layout. This is a requirement for the in-site editing."));
			$msg .= "<br>";	
		}
			
		foreach ($v->missingNodes as $value)
		{
			$idqualifier = "";
			
			$attr = array();
			
			if ($value["name"] != "")
			{
				$attr["name"] = "name '".$value["name"]."'";
			}
			
			if ($value["id"] != "")
			{
				$attr["id"] = "id '".$value["id"]."'";
			}
			
			$idqualifier = implode(", ",$attr);
			
			if ($idqualifier != "")
			{
				$idqualifier = "($idqualifier)";	
			}
			$msg .= sprintf(i18n("Tag '%s' %s has no end tag (start tag is on line %s char %s)"), $value["tag"], $idqualifier, $value["line"],$value["char"]);
			$msg .= "<br>";
	
		}
		
	}
	
	if ($msg != "")
	{
		$notification->displayNotification("warning", $msg);
	}
	
	$form = new UI_Table_Form("module");
	$form->addHeader(i18n("Edit Layout"));
	$form->setWidth("100%");
	$form->setVar("area", $area);
	$form->setVar("action", "lay_edit");
	$form->setVar("frame", $frame);
	$form->setVar("idlay", $idlay);
	
	$tb_name = new cHTMLTextbox("layname", $name, 60);
	$ta_description = new cHTMLTextarea("description", $description,100, 10);
	$ta_description->setStyle("font-family: monospace;width: 100%;");
	$ta_description->updateAttributes(array("wrap" => "off"));
	
	$ta_code = new cHTMLTextarea("code", htmlspecialchars($code), 100,20, 'code');
	$ta_code->setStyle("font-family: monospace;width: 100%;");
	$ta_code->updateAttributes(array("wrap" => "off"));
	
	$cb_refresh = new cHTMLCheckbox("refreshtemplates", i18n("On save, apply default modules to new containers"));
	
	$form->add(i18n("Name"),$tb_name);
	$form->add(i18n("Description"),$ta_description);
	$form->add(i18n("Code"),$ta_code);
	$form->add(i18n("Options"), $cb_refresh);
	
    # Set static pointers
    $tpl->set('s', 'ACTION',    $sess->url("main.php?area=$area&frame=$frame&action=lay_edit"));
    $tpl->set('s', 'IDLAY',     $idlay);
    $tpl->set('s', 'DESCR',     $description);
    $tpl->set('s', 'CLASS', 'code_sfullwidth');
    $tpl->set('s', 'NAME',      htmlspecialchars($name));
    
    # Set dynamic pointers
    $tpl->set('d', 'CAPTION', i18n("Code").':');
    $tpl->set('d', 'VALUE',   htmlspecialchars($code));
    $tpl->set('d', 'CLASS', 'code_fullwidth');
    $tpl->set('d', 'NAME',    'code');
    $tpl->next();
    
    $oEditArea = new EditArea('code', 'html', substr(strtolower($belang), 0, 2), true, $cfg);
    $page->addScript('editarea', $oEditArea->renderScript());
    
    $sScript = '<script type="text/javascript">
                            if (document.getElementById(\'scroll\')) {
                                document.getElementById(\'scroll\').onmousedown = resizer.triggerClickOn;
                                document.getElementById(\'scroll\').onmouseup = resizer.triggerClickOff;
								document.getElementById(\'scroll\').style.paddingTop=\'4px\';
								document.getElementById(\'scroll\').style.paddingBottom=\'5px\';
                            }
                        </script>';
    
	$page->setContent($form->render().$sScript);    


} else {
	$page->setContent("foo");	
}

$page->setSubnav("idlay=$idlay", "lay");
$page->setReload();
$page->render();
?>