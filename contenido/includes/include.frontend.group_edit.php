<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Frontend group editor
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.2.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2007-06-16, Holger Librenz, Hotfix: check for illegal calls added
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if (isset($_REQUEST['cfg']) || isset($_REQUEST['contenido_path'])) {
    die ('Illegal call!');
}

cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "class.ui.php");
cInclude("classes", "class.htmlelements.php");
cInclude("classes", "class.frontend.users.php");
cInclude("classes", "class.frontend.groups.php");
$page = new cPage;
$fegroups 		= new FrontendGroupCollection;

if (is_array($cfg['plugins']['frontendgroups']))
{
	foreach ($cfg['plugins']['frontendgroups'] as $plugin)
	{
		plugin_include("frontendgroups", $plugin."/".$plugin.".php");
	}
}

$fegroup 		= new FrontendGroup;
$groupmembers	= new FrontendGroupMemberCollection;
$fegroup->loadByPrimaryKey($idfrontendgroup);
$sRefreshRightTopLinkJs = "";

if ($action == "frontendgroup_create" && $perm->have_perm_area_action($area, $action))
{
   $fegroup = $fegroups->create(" ".i18n("-- new group --"));
   $idfrontendgroup = $fegroup->get("idfrontendgroup");
   $sRefreshRightTopLink = $sess->url('main.php?frame=3&area='.$area.'&idfrontendgroup='.$idfrontendgroup);
   $sRefreshRightTopLink = "conMultiLink('right_top', '".$sRefreshRightTopLink."')";
   $sRefreshRightTopLinkJs = "<script type=\"text/javascript\">".$sRefreshRightTopLink."</script>";

} else if ($action == "frontendgroups_user_delete" && $perm->have_perm_area_action($area, $action)) {
    $aDeleteMembers = array();
    if (!is_array($_POST['user_in_group'])) {
        if ($_POST['user_in_group'] > 0) {
            array_push($aDeleteMembers, $_POST['user_in_group']);
        }
    } else {
        $aDeleteMembers = $_POST['user_in_group'];
    }
    foreach ($aDeleteMembers as $idfrontenduser) {
        $groupmembers->remove($idfrontendgroup, $idfrontenduser);
    }

    # also save other variables
    $action = "frontendgroup_save_group";
} else if ($action == "frontendgroup_user_add" && $perm->have_perm_area_action($area, $action)) {
    if (count($newmember) > 0)
    {
        foreach ($newmember as $add)
        {
            $groupmembers->create($idfrontendgroup, $add);
        }
    }

    # also save other variables
    $action = "frontendgroup_save_group";
} else if ($action == "frontendgroup_delete" && $perm->have_perm_area_action($area, $action))
{
   $fegroups->delete($idfrontendgroup);
   $idfrontendgroup= 0;
   $fegroup = new FrontendGroup;
}

if ($action != '') {
    $sReloadScript = "<script type=\"text/javascript\">
                         var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
                         if (left_bottom) {
                             var href = left_bottom.location.href;
                             href = href.replace(/&idfrontendgroup[^&]*/, '');
                             href = href.replace(/&action[^&]*/, '');
                             left_bottom.location.href = href+'&idfrontendgroup='+".$idfrontendgroup.";
                             top.content.left.left_top.refresh();
                         }
                     </script>";
} else {
    $sReloadScript = '';
}

if ($fegroup->virgin == false && $fegroup->get("idclient") == $client)
{
	if ($action == "frontendgroup_save_group" && $perm->have_perm_area_action($area, $action))
	{
		$messages = array();

		if ($fegroup->get("groupname") != stripslashes($groupname))
		{
    		$fegroups->select("groupname = '$groupname' and idclient='$client'");
    		if ($fegroups->next())
    		{
    			$messages[] = i18n("Could not set new group name: Group already exists");
    		} else {
    			$fegroup->set("groupname", stripslashes($groupname));
    		}
		}

        //Reset all default groups
        if ($defaultgroup == 1) {
            $sSql = 'UPDATE '.$cfg["tab"]["frontendgroups"].' SET defaultgroup = 0 WHERE idclient='.$client.';';
            $db->query($sSql);
        }
    	$fegroup->set("defaultgroup", $defaultgroup);

		/* Check out if there are any plugins */
		if (is_array($cfg['plugins']['frontendgroups']))
		{
			foreach ($cfg['plugins']['frontendgroups'] as $plugin)
			{
				if (function_exists("frontendgroups_".$plugin."_wantedVariables") &&
					function_exists("frontendgroups_".$plugin."_store"))
				{
					$wantVariables = call_user_func("frontendgroups_".$plugin."_wantedVariables");

					if (is_array($wantVariables))
					{
						$varArray = array();

						foreach ($wantVariables as $value)
						{
							$varArray[$value] = stripslashes($GLOBALS[$value]);
						}
					}
					$store = call_user_func("frontendgroups_".$plugin."_store", $varArray);
				}
			}
		}

    	$fegroup->store();
	}

	if (count($messages) > 0)
	{
		$notis = $notification->returnNotification("warning", implode("<br>", $messages)) . "<br>";
	}

    $tpl->reset();

	$feusers = new FrontendUserCollection;
	$feusers->select("idclient='$client'");

	$addedusers = $groupmembers->getUsersInGroup($idfrontendgroup,false);
	$addeduserobjects = $groupmembers->getUsersInGroup($idfrontendgroup,true);

	$cells = array();
	foreach ($addeduserobjects as $addeduserobject)
	{
        if ((int)$addeduserobject->get("idfrontenduser") != 0) {
            $cells[$addeduserobject->get("idfrontenduser")] = $addeduserobject->get("username");
        }
	}

	asort($cells);

    $sInGroupOptions = '';
	foreach ($cells as $idfrontenduser => $name)
	{
        $sInGroupOptions .= '<option value="'.$idfrontenduser.'">'.$name.'</option>'."\n";
	}
    $tpl->set('s', 'IN_GROUP_OPTIONS', $sInGroupOptions);

	$items = array();
	while ($feuser = $feusers->next())
	{
		$idfrontenduser = $feuser->get("idfrontenduser");
		$sUsername = $feuser->get("username");

		if (!in_array($idfrontenduser,$addedusers))
		{
			$items[$idfrontenduser] = $sUsername;
		}
	}

	asort($items);

    $sNonGroupOptions = '';
	foreach ($items as $idfrontenduser => $name)
	{
        $sNonGroupOptions .= '<option value="'.$idfrontenduser.'">'.$name.'</option>'."\n";
	}
    $tpl->set('s', 'NON_GROUP_OPTIONS', $sNonGroupOptions);

	$groupname = new cHTMLTextbox("groupname", $fegroup->get("groupname"),40);

	$defaultgroup = new cHTMLCheckbox("defaultgroup", "1");
	$defaultgroup->setChecked($fegroup->get("defaultgroup"));

    $tpl->set('d', 'BGCOLOR',  $cfg["color"]["table_header"]);
    $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    $tpl->set('d', 'LABEL', i18n("Group name"));
    $tpl->set('d', 'INPUT', $groupname->render());
    $tpl->next();

    $tpl->set('d', 'BGCOLOR',  $cfg["color"]["table_header"]);
    $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    $tpl->set('d', 'LABEL', i18n("Default group"));
    $tpl->set('d', 'INPUT', $defaultgroup->toHTML(false));
    $tpl->next();

	$pluginOrder = trim_array(explode(",",getSystemProperty("plugin", "frontendgroups-pluginorder")));

	/* Check out if there are any plugins */
	if (is_array($pluginOrder))
	{
		foreach ($pluginOrder as $plugin)
		{
			if (function_exists("frontendgroups_".$plugin."_getTitle") &&
				function_exists("frontendgroups_".$plugin."_display"))
			{

				$plugTitle = call_user_func("frontendgroups_".$plugin."_getTitle");
				$display = call_user_func("frontendgroups_".$plugin."_display", $fegroup);

				if (is_array($plugTitle) && is_array($display))
				{
					foreach ($plugTitle as $key => $value)
					{
                        $tpl->set('d', 'BGCOLOR',  $cfg["color"]["table_header"]);
                        $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
                        $tpl->set('d', 'LABEL', $value);
                        $tpl->set('d', 'INPUT', $display[$key]);
                        $tpl->next();
					}
				} else {
					if (is_array($plugTitle) || is_array($display))
					{
                        $tpl->set('d', 'BGCOLOR',  $cfg["color"]["table_header"]);
                        $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
                        $tpl->set('d', 'LABEL', "WARNING");
                        $tpl->set('d', 'INPUT', "The plugin $plugin delivered an array for the displayed titles, but did not return an array for the contents.");
                        $tpl->next();
					} else {
                        $tpl->set('d', 'BGCOLOR',  $cfg["color"]["table_header"]);
                        $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
                        $tpl->set('d', 'LABEL', $plugTitle);
                        $tpl->set('d', 'INPUT', $display);
                        $tpl->next();
					}
				}
			}
		}
	}

    $tpl->set('s', 'CATNAME', i18n("Edit group"));
    $tpl->set('s', 'BGCOLOR',  $cfg["color"]["table_header"]);
    $tpl->set('s', 'BGCOLOR_CONTENT',  $cfg["color"]["table_dark"]);
    $tpl->set('s', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    $tpl->set('s', 'CATFIELD', "&nbsp;");
    $tpl->set('s', 'FORM_ACTION', $sess->url('main.php'));
    $tpl->set('s', 'AREA', $area);
    $tpl->set('s', 'GROUPID', $idfrontendgroup);
    $tpl->set('s', 'FRAME', $frame);
    $tpl->set('s', 'IDLANG', $lang);
    $tpl->set('s', 'STANDARD_ACTION', 'frontendgroup_save_group');
    $tpl->set('s', 'ADD_ACTION', 'frontendgroup_user_add');
    $tpl->set('s', 'DELETE_ACTION', 'frontendgroups_user_delete');
    $tpl->set('s', 'DISPLAY_OK', 'block');
    $tpl->set('s', 'IN_GROUP_VALUE', $_POST['filter_in']);
    $tpl->set('s', 'NON_GROUP_VALUE', $_POST['filter_non']);
    $tpl->set('s', 'RECORD_ID_NAME', 'idfrontendgroup');
    $tpl->set('s', 'RELOADSCRIPT', $sReloadScript.$sRefreshRightTopLinkJs);

    $tpl = $tpl->generate($cfg['path']['templates'] . $cfg['templates']['grouprights_memberselect']);
} else {
    $page = new UI_Page;
    $page->setContent("");
    $page->addScript('reload', $sReloadScript);
    $page->render();
}
?>