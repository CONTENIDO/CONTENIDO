<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Frontend user list
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.1.9
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: include.frontend.user_menu.php 347 2008-06-27 10:37:33Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.htmlelements.php");
cInclude("classes", "class.todo.php");
cInclude("classes", "class.frontend.users.php");
cInclude("classes", "contenido/class.user.php");
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "widgets/class.widgets.foldingrow.php");
cInclude("classes", "widgets/class.widgets.pager.php");
cInclude("classes", "class.ui.php");

$oPage = new cPage;
//
///* Set default values */
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

$oListOptionRow = new cFoldingRow("f081b6ab-370d-4fd8-984f-6b38590fe48b", i18n("List options"));
									
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

$oFEUsers = new FrontendUserCollection;
$oFEUsers->setWhere("FrontendUserCollection.idclient", $client);

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

$iFullTableCount = 0;
if ($bUsePlugins == false)
{
	$oFEUsers->query();
	
	$iFullTableCount = $oFEUsers->count();
	
	$oFEUsers->setOrder(implode(" ", array($oSelectSortBy->getDefault(), $oSelectSortOrder->getDefault())));
	$oFEUsers->setLimit($elemperpage * ($mPage - 1), $elemperpage);
} else {
    $oFEUsers->query();
    $iFullTableCount = $oFEUsers->count();
}

if ($_REQUEST["elemperpage"]*($_REQUEST["page"]) >= $iFullTableCount+$_REQUEST["elemperpage"] && $_REQUEST["page"]  != 1) {
    $_REQUEST["page"]--;
    $mPage--;
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
    	$mlist->setImage($iMenu, "");	

        if ($_GET['frontenduser'] == $idfrontenduser) {
            $mlist->setExtra($iMenu, 'id="marked" ');
        }        
	}
}

if ($bUsePlugins == false)
{
	$iItemCount = $iFullTableCount;
}

$deleteScript = '<script>
      var sid = "'.$sess->id.'";
      var box = new messageBox("", "", "", 0, 0);

      function deleteFrontenduser(idfrontenduser) 
        {
  			form = parent.left_top.document.getElementById("filter");
        url  = \'main.php?area=frontend\';
        url += \'&action=frontend_delete\';
        url += \'&frame=4\';
        url += \'&idfrontenduser=\' + idfrontenduser;
        url += \'&contenido=\' + sid;
        url += get_registered_parameters();
        url += \'&sortby=\' +form.sortby.value;
  			url += \'&sortorder=\' +form.sortorder.value;
  			url += \'&filter=\' +form.filter.value;
  			url += \'&searchin=\' +form.searchin.value;
  			url += \'&elemperpage=\' +form.elemperpage.value;
  			url += \'&restrictgroup=\' +form.restrictgroup.value;
  			url += \'&page=\' +\''.$mPage.'\';
  			parent.parent.right.right_bottom.location.href = url;
        }
        </script>';

$sInitRowMark = "<script type=\"text/javascript\">
                 if (document.getElementById('marked')) {
                     row.markedRow = document.getElementById('marked');
                 }
            </script>";

$oPage->setMargin(0);
$oPage->addScript('messagebox', '<script type="text/javascript" src="scripts/messageBox.js.php?contenido='.$sess->id.'"></script>');
$oPage->addScript('delete', $deleteScript);
//$oPage->addScript('cfoldingrow.js', '<script language="JavaScript" src="scripts/cfoldingrow.js"></script>');
$oPage->addScript('parameterCollector.js', '<script language="JavaScript" src="scripts/parameterCollector.js"></script>');

//generate current content for Object Pager
$oPagerLink = new cHTMLLink;
$oPagerLink->setTargetFrame('left_bottom');
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

$oPager = new cObjectPager("25c6a67d-a3f1-4ea4-8391-446c131952c9", $iItemCount, $_REQUEST['elemperpage'], $mPage, $oPagerLink, "page", $pagingLink);

//add slashes, to insert in javascript
$sPagerContent = $oPager->render(1);
$sPagerContent = str_replace('\\', '\\\\', $sPagerContent);
$sPagerContent = str_replace('\'', '\\\'', $sPagerContent);

//send new object pager to left_top
$sRefreshPager = '
    <script type="text/javascript">
        var sNavigation = \''.$sPagerContent.'\';
        var left_top = parent.left_top;
        if (left_top.document) {
            var oPager = left_top.document.getElementById(\'25c6a67d-a3f1-4ea4-8391-446c131952c9\');

            if (oPager) {
                oInsert = oPager.firstChild;
                oInsert.innerHTML = sNavigation;
                 left_top.iPage = '.$_REQUEST["page"].';
                 left_top.toggle_pager(\'25c6a67d-a3f1-4ea4-8391-446c131952c9\');
            }
        }
    </script>';
    
$oPage->addScript('refreshpager', $sRefreshPager); 
$oPage->setContent($mlist->render(false).$sInitRowMark);
$oPage->render();

?>