<?php
/*****************************************
* File      :   $RCSfile: include.recipients_menu.php,v $
* Project   :   Contenido
* Descr     :   Recipient user list
* Modified  :   $Date: 2007/06/19 23:18:38 $
*
* © four for business AG, www.4fb.de, updated by HerrB (25.06.2005)
*
* $Id: include.recipients_menu.php,v 1.22 2007/06/19 23:18:38 bjoern.behrens Exp $
******************************************/
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "widgets/class.widgets.foldingrow.php");
cInclude("classes", "widgets/class.widgets.pager.php");
cInclude("classes", "contenido/class.user.php");
cInclude("classes", "contenido/class.client.php");
cInclude("classes", "class.ui.php");
cInclude("classes", "class.newsletter.recipients.php");

$oPage       = new cPage;
$oMenu       = new UI_Menu;
$sLocation   = $sess->url("main.php?area=$area&frame=$frame");

// Updating keys, if activated; all recipients of all clients!
if (getSystemProperty("newsletter", "updatekeys")) {
	$updatedrecipients = $recipients->updateKeys();
	$notis = $notification->returnNotification("info", sprintf(i18n("%d recipients, with no or incompatible key has been updated. Deactivate update function."),$updatedrecipients));
}


//Update purgetimeframe if submitted
$sRefreshTop = '';
$oClient = new cApiClient($client);
$iTimeframe = $oClient->getProperty("newsletter", "purgetimeframe");
if (isset($_REQUEST["purgetimeframe"]) && is_numeric($_REQUEST["purgetimeframe"]) && $_REQUEST["purgetimeframe"] > 0 
    && $_REQUEST["purgetimeframe"] != $iTimeframe && $perm->have_perm_area_action($area, "recipients_delete")) 
{
    $oClient->setProperty("newsletter", "purgetimeframe", $_REQUEST["purgetimeframe"]);
    $sRefreshTop = '<script language="JavaScript">parent.left_top.purgetimeframe = '.$_REQUEST["purgetimeframe"].'</script>';
}

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

if (!isset($_REQUEST['restrictgroup']) || !is_numeric($_REQUEST['restrictgroup'])) {
	$_REQUEST['restrictgroup'] = "--all--";
}
if (!isset($_REQUEST["page"]) || !is_numeric($_REQUEST['page']) || $_REQUEST['page'] <= 0 || $_REQUEST["elemperpage"] == 0) {
	$_REQUEST["page"] = 1;
}
if ($_REQUEST["sortorder"] != "DESC") {
	$_REQUEST["sortorder"]  = "ASC";
}

// Initialization
$aFields = array();
$aFields["name"]  		= array("field" => "name", "caption" => i18n("Name"), "type" => "base,sort,search");
$aFields["email"] 		= array("field" => "email", "caption" => i18n("E-Mail"), "type" => "base,sort,search");
$aFields["confirmed"]	= array("field" => "confirmed", "caption" => i18n("Confirmed"), "type" => "base");
$aFields["deactivated"] = array("field" => "deactivated", "caption" => i18n("Deactivated"), "type" => "base");

$sMsgDelTitle   = i18n("Delete recipient");
$sMsgDelDescr   = i18n("Do you really want to delete the following recipient:<br>");

$oRecipients = new RecipientCollection;
$oRecipients->setWhere("recipientcollection.idclient", $client);
$oRecipients->setWhere("recipientcollection.idlang", $lang);

// sort by and sort order
$oRecipients->setOrder("recipientcollection.".$_REQUEST["sortby"]." ".$_REQUEST["sortorder"]);

// Show group
if ($_REQUEST["restrictgroup"] != "--all--") 
	{
	$oRecipients->link("RecipientGroupMemberCollection");
	$oRecipients->setWhere("RecipientGroupMemberCollection.idnewsgroup", $_REQUEST["restrictgroup"]);
	}
// Search for
if ($_REQUEST["filter"] != "") 
	{
	if ($_REQUEST["searchin"] == "--all--" || $_REQUEST["searchin"] == "") 
		{
		foreach ($aFields as $sKey => $aData) 
			{
			if (strpos($aData["type"], "search") !== false) 
				{
				$oRecipients->setWhereGroup("filter", "recipientcollection.".$aData["field"], $_REQUEST["filter"], "LIKE");
				}
			}
		$oRecipients->setInnerGroupCondition("filter", "OR");
		} 
	else 
		{
		$oRecipients->setWhere("recipientcollection.".$_REQUEST["searchin"], $_REQUEST["filter"], "LIKE");
		}
	}

    // Items / page
if ($_REQUEST["elemperpage"] > 0) 
	{
	$oRecipients->query();
	$iItemCount = $oRecipients->count();
    if ($_REQUEST["elemperpage"]*($_REQUEST["page"]) >= $iItemCount+$_REQUEST["elemperpage"] && $_REQUEST["page"]  != 1) {
        $_REQUEST["page"]--;
    }
	$oRecipients->setLimit($_REQUEST["elemperpage"] * ($_REQUEST["page"] - 1), $_REQUEST["elemperpage"]);
	} 
else 
	{
	$iItemCount = 0;
	}
    
$oRecipients->query();

// Output data
$oMenu	= new UI_Menu;
$iMenu	= 0;

while ($oRecipient = $oRecipients->next())
{
	$iMenu++;
	$idnewsrcp = $oRecipient->get("idnewsrcp");
	            	
	$sName = $oRecipient->get("name");
	if (empty($sName))
	{
		$sName = $oRecipient->get("email");
	}

	$oLnk = new cHTMLLink;
	$oLnk->setMultiLink($area, "", $area, "");
	$oLnk->setCustom("idrecipient", $idnewsrcp);

	if ($oRecipient->get("deactivated") == 1 || $oRecipient->get("confirmed") == 0)
	{
		$oLnk->updateAttributes(array("style" => "color:#A20000"));
	}

	$oMenu->setTitle($iMenu, $sName);
	$oMenu->setLink($iMenu, $oLnk);	
	
	if ($perm->have_perm_area_action("recipients", "recipients_delete"))
	{
		$oMenu->setActions($iMenu, "delete", '<a title="'.$sMsgDelTitle.'" href="javascript://" onclick="showDelMsg('.$idnewsrcp.',\''.addslashes($sName).'\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$sMsgDelTitle.'" alt="'.$sMsgDelTitle.'"></a>');
	}
}

$execScript = '
    <script type="text/javascript">       
        /* Session-ID */
        var sid = "'.$sess->id.'";

        /* Create messageBox instance */
        box = new messageBox("", "", "", 0, 0);

        function showDelMsg(lngId, strElement) {
            box.confirm("'.$sMsgDelTitle.'", "'.$sMsgDelDescr.'<b>" + strElement + "</b>", "deleteRecipient(\'" + lngId + "\')");
        }

        /* Function for deleting recipients */
        function deleteRecipient(idrecipient) {
            oForm = top.content.left.left_top.document.getElementById("htmlnewsletter");

            url  = "main.php?area=recipients";
            url += "&action=recipients_delete";
            url += "&frame=4";
            url += "&idrecipient=" + idrecipient;
            url += "&contenido=" + sid;
            url += get_registered_parameters();
            url += "&restrictgroup=" + oForm.restrictgroup.value;
            url += "&sortby=" + oForm.sortby.value;
            url += "&sortorder=" + oForm.sortorder.value;
            url += "&filter=" + oForm.filter.value;
            url += "&elemperpage=" + oForm.elemperpage.value;

            parent.parent.right.right_bottom.location.href = url;
        }

		</script>';

$oPage->setMargin(0);
$oPage->addScript('messagebox', '<script type="text/javascript" src="scripts/messageBox.js.php?contenido='.$sess->id.'"></script>');
$oPage->addScript('exec', $execScript);
$oPage->addScript('cfoldingrow.js', '<script language="JavaScript" src="scripts/cfoldingrow.js"></script>');
$oPage->addScript('parameterCollector.js', '<script language="JavaScript" src="scripts/parameterCollector.js"></script>');
$oPage->addScript('refreshTop', $sRefreshTop);

//generate current content for Object Pager
$oPagerLink = new cHTMLLink;
$oPagerLink->setLink("main.php");
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setCustom("elemperpage", $_REQUEST["elemperpage"]);
$oPagerLink->setCustom("filter", $_REQUEST["filter"]);
$oPagerLink->setCustom("restrictgroup", $_REQUEST["restrictgroup"]);
$oPagerLink->setCustom("sortby", $_REQUEST["sortby"]);
$oPagerLink->setCustom("sortorder", $_REQUEST["sortorder"]);
$oPagerLink->setCustom("searchin", $_REQUEST["searchin"]);
$oPagerLink->setCustom("frame", $frame);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);

$oPager = new cObjectPager("0ed6d632-6adf-4f09-a0c6-1e38ab60e304", $iItemCount, $_REQUEST["elemperpage"], $_REQUEST["page"], $oPagerLink, "page", $PagingLinkRec);

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
            var oPager = left_top.document.getElementById(\'0ed6d632-6adf-4f09-a0c6-1e38ab60e304\');
            if (oPager) {
                oInsert = oPager.firstChild;
                oInsert.innerHTML = sNavigation;
                left_top.recipients_listoptionsform_curPage = '.$_REQUEST["page"].';
                left_top.toggle_pager(\'0ed6d632-6adf-4f09-a0c6-1e38ab60e304\');
            }
        }
    </script>';
$oPage->addScript('refreshpager', $sRefreshPager); 

$oPage->setContent(array('<table border="0" cellspacing="0" cellpadding="0" width="100%">', '</table>', $oMenu->render(false)));
$oPage->render();

?>