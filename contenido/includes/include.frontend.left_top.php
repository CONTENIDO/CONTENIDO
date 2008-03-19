<?php
/*****************************************
* File      :   $RCSfile: include.frontend.left_top.php,v $
* Project   :   Contenido
* Descr     :   Left top pane
* Modified  :   $Date: 2004/01/14 15:20:03 $
*
* © four for business AG, www.4fb.de
*
* $Id: include.frontend.left_top.php,v 1.1 2004/01/14 15:20:03 timo.hummel Exp $
******************************************/

cInclude("classes", "class.htmlelements.php");
cInclude("classes", "class.todo.php");
cInclude("classes", "class.frontend.users.php");
cInclude("classes", "class.frontend.groups.php");
cInclude("classes", "contenido/class.user.php");
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "widgets/class.widgets.foldingrow.php");
cInclude("classes", "widgets/class.widgets.pager.php");
cInclude("classes", "class.ui.php");
$tpl->reset();

$oPage = new cPage;

$tpl->set('s', '{SESSID}', $sess->id);

/* Set default values */
$oUser = new cApiUser($auth->auth["uid"]);
if (!isset($_REQUEST["elemperpage"]) || !is_numeric($_REQUEST['elemperpage']) || $_REQUEST['elemperpage'] <= 0) {
	$_REQUEST["elemperpage"] = $oUser->getProperty("itemsperpage", $area);
}
if (!is_numeric($_REQUEST['elemperpage'])) {
	$_REQUEST['elemperpage'] = 25;
}
$oUser->setProperty("itemsperpage", $area, $_REQUEST["elemperpage"]);
unset ($oUser);

if (!isset($_REQUEST["page"]) || !is_numeric($_REQUEST['page']) || $_REQUEST['page'] <= 0 || $_REQUEST["elemperpage"] == 0) {
	$_REQUEST["page"] = 1;
}

$aFieldsToSearch = array("--all--" => i18n("-- All fields --"), "username" => i18n("Username"));
$aFieldsToSort = array("username" => i18n("Username"));

$aFieldSources = array();
$aFieldSources["username"] = "base"; 

$bUsePlugins = getEffectiveSetting("frontendusers", "pluginsearch", "true");

if ($bUsePlugins == "false")
{
	$bUsePlugins = false;	
} else {
	$bUsePlugins = true;	
}


if (is_array($cfg['plugins']['frontendusers']))
{
	foreach ($cfg['plugins']['frontendusers'] as $plugin)
	{
		plugin_include("frontendusers", $plugin."/".$plugin.".php");
	}
}

if ($bUsePlugins == true)
{
	if (is_array($cfg['plugins']['frontendusers']))
	{
		$_sValidPlugins = getEffectiveSetting("frontendusers", "pluginsearch_valid_plugins", '');
		$_aValidPlugins = array();
		if (strlen($_sValidPlugins)>0) {
			$_aValidPlugins = explode(',', $_sValidPlugins);
		}
		$_iCountValidPlugins = sizeof($_aValidPlugins);
		foreach ($cfg['plugins']['frontendusers'] as $plugin)
		{
			if ($_iCountValidPlugins == 0 || in_array($plugin, $_aValidPlugins)) {
				if (function_exists("frontendusers_".$plugin."_wantedVariables") && function_exists("frontendusers_".$plugin."_canonicalVariables"))
				{
					$aVariableNames = call_user_func("frontendusers_".$plugin."_canonicalVariables");
					
					if (is_array($aVariableNames))
					{
						$aTmp = array_merge($aFieldsToSearch, $aVariableNames);
						$aFieldsToSearch = $aTmp; 
						
						$aTmp2 = array_merge($aFieldsToSort, $aVariableNames);
						$aFieldsToSort = $aTmp2;
						
						foreach ($aVariableNames as $sVariableName => $name)
						{
							$aFieldSources[$sVariableName] = $plugin;	
						}
					}
				}
			}
		}
	}
}


$aSortOrderOptions = array ("asc" => i18n("Ascending"), "desc" => i18n("Descending"));

###########
# Buttons
###########
$userlink = new cHTMLLink;
$userlink->setCLink("frontend", 2, "");

$grouplink = new cHTMLLink;
$grouplink->setCLink("frontendgroups", 2, "");

$userlink="javascript:conMultiLink('left_bottom','main.php?area=frontend&frame=2&action=&contenido=".$sess->id."')";
$grouplink="javascript:conMultiLink('left_bottom','main.php?area=frontendgroups&frame=2&action=&contenido=".$sess->id."')";

// Frontend users
$imgUserId='img_user';
$tpl->set('s', 'IUSER', $imgUserId);
$buttonRow .= '<a style="margin-right:5px;" href="'.$userlink.'" onclick="toggleContainer(\''.$imgUserId.'\');">';
$buttonRow .= '<img onmouseover="hoverEffect(\''.$imgUserId.'\', \'in\')" onmouseout="hoverEffect(\''.$imgUserId.'\', \'out\')" alt="'.i18n("Frontend users").'" title="'.i18n("Frontend users").'" id="'.$imgUserId.'" src="'.$cfg["path"]["images"].'users.gif"/>';
$buttonRow .= '</a>';
// Frontend Groups
$imgGroupId='img_group';
$tpl->set('s', 'IGROUP', $imgGroupId);
$buttonRow .= '<a style="margin-right:5px;" href="'.$grouplink.'" onclick="toggleContainer(\''.$imgGroupId.'\');">';
$buttonRow .= '<img onmouseover="hoverEffect(\''.$imgGroupId.'\', \'in\')" onmouseout="hoverEffect(\''.$imgGroupId.'\', \'out\')" alt="'.i18n("Frontend groups").'" title="'.i18n("Frontend groups").'" id="'.$imgGroupId.'" src="'.$cfg["path"]["images"].'groups.gif"/>';
$buttonRow .= '</a>';

$tpl->set('s', 'BUTTONROW', $buttonRow);

################
# Users Actions
################
$actionLink="actionlink";
$oActionRow = new cFoldingRow("28cf9b31-e6d7-4657-a9a7-db31478e7a5c",i18n("Actions"), $actionLink);
$tpl->set('s', 'ACTIONLINK', $actionLink);
$oLink = new cHTMLLink;
$oLink->setMultiLink("frontend","","frontend","frontend_create");
$oLink->setClass("addfunction");
$oLink->setContent(i18n("Create user"));
$oLink->setStyle('margin-left: 17px;margin-top:5px');
$oActionRow->setContentData($oLink->render());

######################
# Users List Options
######################
$listOptionLink="listoptionlink";
$oListOptionRow = new cFoldingRow("f081b6ab-370d-4fd8-984f-6b38590fe48b", i18n("List options"), $listOptionLink);
$tpl->set('s', 'LISTOPTIONLINK', $listOptionLink);									
$oSelectItemsPerPage = new cHTMLSelectElement("elemperpage");
$oSelectItemsPerPage->autoFill(array(25 => 25, 50 => 50, 75 => 75, 100 => 100));
$oSelectItemsPerPage->setDefault($_REQUEST["elemperpage"]);

asort($aFieldsToSort);
asort($aFieldsToSearch);

$oSelectSortBy = new cHTMLSelectElement("sortby");
$oSelectSortBy->autoFill($aFieldsToSort);
$oSelectSortBy->setDefault($_REQUEST["sortby"]);

$oSelectSortOrder = new cHTMLSelectElement("sortorder");
$oSelectSortOrder->autoFill($aSortOrderOptions);
$oSelectSortOrder->setDefault($_REQUEST["sortorder"]);

$oSelectSearchIn = new cHTMLSelectElement("searchin");
$oSelectSearchIn->autoFill($aFieldsToSearch);
$oSelectSearchIn->setDefault($_REQUEST["searchin"]);

$fegroups = new FrontendGroupCollection;
$fegroups->setWhere("idclient", $client);
$fegroups->query();

$aFEGroups = array("--all--" => i18n("-- All Groups --"));

while ($fegroup = $fegroups->next())
{
	$aFEGroups[$fegroup->get("idfrontendgroup")] = $fegroup->get("groupname");	
}

$oSelectRestrictGroup = new cHTMLSelectElement("restrictgroup");
$oSelectRestrictGroup->autoFill($aFEGroups);
$oSelectRestrictGroup->setDefault($_REQUEST["restrictgroup"]);
$oTextboxFilter = new cHTMLTextbox("filter", $_REQUEST["filter"], 20);


$content = '<div style="margin-left: 17px;">';
// Ye stuff will be done in javascript on apply button
$content .= '<form action="javascript:execFilter(\''.$sess->id.'\');" id="filter" name="filter" method="get">';
$content .= '<table>';
$content .= '<input type="hidden" name="area" value="'.$area.'">';
$content .= '<input type="hidden" name="frame" value="2">';
$content .= '<input type="hidden" name="contenido" value="'.$sess->id.'">';
$content .= '<tr>';
$content .= '<td>'. i18n("Items / page").'</td>';
$content .= '<td>'.$oSelectItemsPerPage->render().'</td>';
$content .= '</tr>';
$content .= '<tr>';
$content .= '<td>'. i18n("Sort by").'</td>';
$content .= '<td>'.$oSelectSortBy->render().'</td>';
$content .= '</tr>';
$content .= '<tr>';
$content .= '<td>'. i18n("Sort order").'</td>';
$content .= '<td>'.$oSelectSortOrder->render().'</td>';
$content .= '</tr>';
$content .= '<tr>';
$content .= '<td>'. i18n("Show group").'</td>';
$content .= '<td>'.$oSelectRestrictGroup->render().'</td>';
$content .= '</tr>';
$content .= '<tr>';
$content .= '<td>'. i18n("Search for").'</td>';
$content .= '<td>'.$oTextboxFilter->render().'</td>';
$content .= '</tr>';
$content .= '<tr>';
$content .= '<td>'. i18n("Search in").'</td>';
$content .= '<td>'.$oSelectSearchIn->render().'</td>';
$content .= '</tr>';
$content .= '<tr>';
$content .= '<td>&nbsp;</td>';
$content .= '<td><input type="button" value="'.i18n("Apply").'" onclick="javascript:execFilter(\''.$sess->id.'\');"</td>';
$content .= '</tr>';
$content .= '</table>';
$content .= '</form>';
$content .= '</div>';
$oListOptionRow->setContentData($content);
$oFEUsers = new FrontendUserCollection;
$oFEUsers->setWhere("FrontendUserCollection.idclient", $client);

##############################
# Process request parameters
##############################
if (strlen($_REQUEST["filter"]) > 0 && $bUsePlugins == false)
{
	$oFEUsers->setWhere("FrontendUsercollection.username", $_REQUEST["filter"], "diacritics");
}

if ($_REQUEST["restrictgroup"] != "" && $_REQUEST["restrictgroup"] != "--all--")
{
	$oFEUsers->link("FrontendGroupMemberCollection");
	$oFEUsers->setWhere("FrontendGroupMemberCollection.idfrontendgroup", $_REQUEST["restrictgroup"]);
}

$mPage 			= $_REQUEST["page"];
$elemperpage	= $_REQUEST["elemperpage"];

if ($bUsePlugins == false)
{
	$oFEUsers->query();
	
	$iFullTableCount = $oFEUsers->count();
	
	$oFEUsers->setOrder(implode(" ", array($oSelectSortBy->getDefault(), $oSelectSortOrder->getDefault())));
	$oFEUsers->setLimit($elemperpage * ($mPage - 1), $elemperpage);
}

$oFEUsers->query();

$aUserTable = array();

while ($feuser = $oFEUsers->next())
{
	foreach ($aFieldSources as $key => $field)
	{
		$idfrontenduser = $feuser->get("idfrontenduser");

		$aUserTable[$idfrontenduser]["idfrontenduser"] = $idfrontenduser;
		
		switch ($field)
		{
			case "base":
				$aUserTable[$idfrontenduser][$key] = $feuser->get("username");
				break;	
			default:
				if ($_REQUEST["filter"] != "")
				{
					$aUserTable[$idfrontenduser][$key] = call_user_func("frontendusers_".$field."_getvalue", $key);
				}
				break;
		}
	}
	
	if ($_REQUEST["filter"] != "")
	{
		if ($_REQUEST["searchin"] == "--all--" || $_REQUEST["searchin"] == "")
		{
			$found = false;
			
			foreach ($aUserTable[$idfrontenduser] as $key => $value)
			{
				if (stripos($value, $_REQUEST["filter"]) !== false)
				{
					$found = true;
				}
			}
			
			if ($found == false)
			{
				unset($aUserTable[$idfrontenduser]);
			}			
			
		} else {
			if (stripos($aUserTable[$idfrontenduser][$_REQUEST["searchin"]], $_REQUEST["filter"]) === false)
			{
				unset($aUserTable[$idfrontenduser]);
			}
		}
	}
}

if ($_REQUEST["sortorder"] == "desc")
{
	$sortorder = SORT_DESC;	
} else {
	$sortorder = SORT_ASC;
}

if ($_REQUEST["sortby"])
{
	$aUserTable = array_csort($aUserTable, $_REQUEST["sortby"], $sortorder);
} else {
	$aUserTable = array_csort($aUserTable, "username", $sortorder);
}

$mlist = new UI_Menu;
$iMenu = 0;
$iItemCount = 0;

foreach ($aUserTable as $mkey => $params)
{
	$idfrontenduser = $params["idfrontenduser"];
	$link = new cHTMLLink;
    $link->setMultiLink($area, "", $area, "");
    $link->setCustom("idfrontenduser", $idfrontenduser);
    
    $iItemCount++;
    
	if (($iItemCount > ($elemperpage * ($mPage - 1)) && $iItemCount < (($elemperpage * $mPage) + 1)) || $bUsePlugins == false)
	{    
    	$iMenu++;
    	
		$message = sprintf(i18n("Do you really want to delete the user %s?"), htmlspecialchars($params["username"]));
	        		
		$delTitle = i18n("Delete user");
		$deletebutton = '<a title="'.$delTitle.'" href="javascript://" onclick="box.confirm(\''.$delTitle.'\', \''.$message.'\', \'deleteFrontenduser('.$idfrontenduser.')\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$delTitle.'" alt="'.$delTitle.'"></a>';
							
    	$mlist->setTitle($iMenu, $params["username"]);
    	$mlist->setLink($iMenu, $link);		
    	$mlist->setActions($iMenu, "delete", $deletebutton); 
    	$mlist->setImage($iMenu, "images/users.gif");		
	}
}

if ($bUsePlugins == false)
{
	$iItemCount = $iFullTableCount;
}

###############
# Users Paging
###############
$pagingLink="paginglink";
$tpl->set('s', 'PAGINGLINK', $pagingLink);

$oPagerLink = new cHTMLLink;
$oPagerLink->setLink("main.php");
$oPagerLink->setCustom("elemperpage", $elemperpage);
$oPagerLink->setCustom("filter", $_REQUEST["filter"]);
$oPagerLink->setCustom("sortby", $_REQUEST["sortby"]);
$oPagerLink->setCustom("sortorder", $_REQUEST["sortorder"]);
$oPagerLink->setCustom("searchin", $_REQUEST["searchin"]);
$oPagerLink->setCustom("restrictgroup", $_REQUEST["restrictgroup"]);
$oPagerLink->setCustom("frame", $frame);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);
$oPager = new cObjectPager("25c6a67d-a3f1-4ea4-8391-446c131952c9", $iItemCount, $elemperpage, $mPage, $oPagerLink, "page", $pagingLink);


######################
# Groups create Groups
######################
$link = new Link;
$menu = new UI_Menu;
$link->setMultiLink("frontendgroups","","frontendgroups","frontendgroup_create");
$menu->setImage("-2", $cfg["path"]["images"] . "folder_new.gif");	
$menu->setLink("-2", $link);
$menu->setTitle("-2", i18n("Create group"));
$menu->setLink("10", $link);
$menu->setTitle("10", "");
$menu->setImage("10", "");
$menu->setRowmark(false);


#######################
# Container Users
#######################
$containerUsersId='cont_users';
$containerUsers  = '<div id="'.$containerUsersId.'">';
$containerUsers .= '<table border="0" cellspacing="0" cellpadding="0" width="100%">';
$containerUsers .= $oActionRow->render();
$containerUsers .= $oListOptionRow->render();
$containerUsers .= $oPager->render();
$containerUsers .= '</table>';
$containerUsers .= '</div>';
$tpl->set('s', 'CUSERS', $containerUsers);
$tpl->set('s', 'ID_USERS', $containerUsersId);

#######################
# Container Groups
#######################
$containerGroupsId='cont_groups';
$containerGroups  = '<div id="'.$containerGroupsId.'" style="padding-left:15px; border-top: 1px solid #B3B3B3;padding-top: 5px;">';
$containerGroups .= '<span style="margin-left:15px">'.$menu->render(false).'</span>';
$containerGroups .= '</div>';
$tpl->set('s', 'CGROUPS', $containerGroups);
$tpl->set('s', 'ID_GROUPS', $containerGroupsId);

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['admin_frontend']);

?>