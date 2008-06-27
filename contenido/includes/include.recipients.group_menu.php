<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Frontend group list
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.6.0
 * @author     Bj�rn Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "widgets/class.widgets.foldingrow.php");
cInclude("classes", "widgets/class.widgets.pager.php");
cInclude("classes", "contenido/class.user.php");
cInclude("classes", "class.ui.php");
cInclude("classes", "class.newsletter.groups.php");

$oPage 		= new cPage;
$oMenu 		= new UI_Menu;

// Set default values
$oUser = new cApiUser($auth->auth["uid"]);
if (!isset($_REQUEST["elemperpage"]) || !is_numeric($_REQUEST['elemperpage']) || $_REQUEST['elemperpage'] < 0) {
	$_REQUEST["elemperpage"] = $oUser->getProperty("itemsperpage", $area);
}
if (!is_numeric($_REQUEST['elemperpage'])) {
	$_REQUEST['elemperpage'] = 25;
}
if ($_REQUEST["elemperpage"] > 0) { 
	// -- All -- will not be stored, as it may be impossible to change this back to something more useful
	$oUser->setProperty("itemsperpage", $area, $_REQUEST["elemperpage"]);
}
unset ($oUser);

if (!isset($_REQUEST["page"]) || !is_numeric($_REQUEST['page']) || $_REQUEST['page'] <= 0 || $_REQUEST["elemperpage"] == 0) {
	$_REQUEST["page"] = 1;
}
if ($_REQUEST["sortorder"] != "DESC") {
	$_REQUEST["sortorder"]  = "ASC";
}

$sDelTitle	= i18n("Delete recipient group");
$sDelDescr	= i18n("Do you really want to delete the following newsletter recipient group:<br>"); 


$_REQUEST["sortby"] = 'groupname';

$oRcpGroups = new RecipientGroupCollection;
$oRcpGroups->setWhere("idclient", $client);
$oRcpGroups->setWhere("idlang", $lang);

$aFields = array();
$aFields["name"]  		= array("field" => "groupname", "caption" => i18n("Name"), "type" => "base,sort,search");

if ($_REQUEST["filter"] != "") {
	if ($_REQUEST["searchin"] == "--all--" || $_REQUEST["searchin"] == "") {
		foreach ($aFields as $sKey => $aData) {
			if (strpos($aData["type"], "search") !== false) {
				$oRcpGroups->setWhereGroup("filter", $aData["field"], $_REQUEST["filter"], "LIKE");
			}
		}
		$oRcpGroups->setInnerGroupCondition("filter", "OR");
	} else {
		$oRcpGroups->setWhere($_REQUEST["searchin"], $_REQUEST["filter"], "LIKE");
	}
}

if ($_REQUEST["elemperpage"] > 0) 
{
    $oRcpGroups->query();
    $iItemCount = $oRcpGroups->count(); // Getting item count without limit (for page function) - better idea anyone (performance)?

    if ($_REQUEST["elemperpage"]*($_REQUEST["page"]) >= $iItemCount+$_REQUEST["elemperpage"] && $_REQUEST["page"]  != 1) {
        $_REQUEST["page"]--;
    }
 
    $oRcpGroups->setLimit($_REQUEST["elemperpage"] * ($_REQUEST["page"] - 1), $_REQUEST["elemperpage"]);
} 
else 
{
    $iItemCount = 0;
}
    
$oRcpGroups->setOrder("defaultgroup DESC, ".$_REQUEST["sortby"]." ".$_REQUEST["sortorder"]);
$oRcpGroups->query();

$oMenu	= new UI_Menu;
$iMenu		= 0;

while ($oRcpGroup = $oRcpGroups->next())
{
	$iMenu++;
	$iIDGroup = $oRcpGroup->get("idnewsgroup");
	            	
	$sName = $oRcpGroup->get("groupname");
	if ($oRcpGroup->get("defaultgroup")) {
		$sName = $sName . "*";
	}

	// Create the link to show/edit the recipient group
	$oLnk = new cHTMLLink;
	$oLnk->setMultiLink("recipientgroups","","recipientgroups","");
	$oLnk->setCustom("idrecipientgroup", $iIDGroup);

	$oMenu->setImage($iMenu, $cfg["path"]["images"] . "groups.gif");
	$oMenu->setTitle($iMenu, $sName);
	$oMenu->setLink($iMenu, $oLnk);
	
	if ($perm->have_perm_area_action($area, recipientgroup_delete))
	{
		$oMenu->setActions($iMenu, 'delete', '<a title="'.$sDelTitle.'" href="javascript://" onclick="showDelMsg('.$iIDGroup.',\''.addslashes($sName).'\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$sDelTitle.'" alt="'.$sDelTitle.'"></a>');
	} 
}

$execScript = '
    <script type="text/javascript">       
        /* Session-ID */
        var sid = "'.$sess->id.'";

        /* Create messageBox instance */
        box = new messageBox("", "", "", 0, 0);

        function showDelMsg(lngId, strElement) {
            box.confirm("'.$sDelTitle.'", "'.$sDelDescr.'<b>" + strElement + "</b>", "deleteRecipientGroup(\'" + lngId + "\')");
        }

        /* Function for deleting recipient groups */
        function deleteRecipientGroup(idrecipientgroup) {
        		oForm = top.content.left.left_top.document.getElementById("groups_listoptionsform");

            url  = "main.php?area=recipientgroups";
            url += "&action=recipientgroup_delete";
            url += "&frame=4";
            url += "&idrecipientgroup=" + idrecipientgroup;
            url += "&contenido=" + sid;
            url += get_registered_parameters();
            url += "&sortby=" + oForm.sortby.value;
            url += "&sortorder=" + oForm.sortorder.value;
            url += "&filter=" + oForm.filter.value;
            url += "&elemperpage=" + oForm.elemperpage.value;

            parent.parent.right.right_bottom.location.href = url;
        }

		</script>';        

$oPage->setMargin(0);
$oPage->addScript('messagebox', '<script type="text/javascript" src="scripts/messageBox.js.php?contenido='.$sess->id.'"></script>');
$oPage->addScript('delete', $execScript);
$oPage->addScript('cfoldingrow.js', '<script language="JavaScript" src="scripts/cfoldingrow.js"></script>');
$oPage->addScript('parameterCollector.js', '<script language="JavaScript" src="scripts/parameterCollector.js"></script>');

//generate current content for Object Pager
$oPagerLink = new cHTMLLink;
$oPagerLink->setLink("main.php");
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setCustom("elemperpage", $_REQUEST["elemperpage"]);
$oPagerLink->setCustom("filter", $_REQUEST["filter"]);
$oPagerLink->setCustom("sortby", $_REQUEST["sortby"]);
$oPagerLink->setCustom("sortorder", $_REQUEST["sortorder"]);
$oPagerLink->setCustom("searchin", $_REQUEST["searchin"]);
$oPagerLink->setCustom("frame", $frame);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);

$oPager = new cObjectPager("0ed6d632-6adf-4f09-a0c6-1e38ab60e305", $iItemCount, $_REQUEST["elemperpage"], $_REQUEST["page"], $oPagerLink, "page", $PagingLinkGroup);

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
            var oPager = left_top.document.getElementById(\'0ed6d632-6adf-4f09-a0c6-1e38ab60e305\');
            if (oPager) {
                oInsert = oPager.firstChild;
                oInsert.innerHTML = sNavigation;
                left_top.groups_listoptionsform_curPage = '.$_REQUEST["page"].';
                left_top.toggle_pager(\'0ed6d632-6adf-4f09-a0c6-1e38ab60e305\');
            }
        }
    </script>';
    
$oPage->addScript('refreshpager', $sRefreshPager);

$oPage->setContent(array('<table border="0" cellspacing="0" cellpadding="0" width="100%">', '</table>', $oMenu->render(false)));
$oPage->render();

?>