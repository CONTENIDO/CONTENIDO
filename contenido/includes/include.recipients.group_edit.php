<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Recipient group editor
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.1.1
 * @author     Björn Behrens (HerrB)
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2004-08-01, Björn Behrens (HerrB)
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *
 *   $Id: include.recipients.group_edit.php 665 2008-08-10 15:20:20Z HerrB $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "widgets/class.widgets.foldingrow.php");
cInclude("classes", "widgets/class.widgets.pager.php");
cInclude("classes", "class.newsletter.recipients.php");
cInclude("classes", "class.newsletter.groups.php");
cInclude("classes", "class.ui.php");

// Initialization
$oPage			= new cPage;
$oRGroups		= new RecipientGroupCollection;
$oRGroupMembers	= new RecipientGroupMemberCollection;
$oRGroup		= new RecipientGroup;

$aFields = array();
$aFields["name"]  		= array("field" => "name",			"caption" => i18n("Name"), 			"type" => "base,sort,search");
$aFields["email"] 		= array("field" => "email",			"caption" => i18n("E-Mail"), 		"type" => "base,sort,search");
$aFields["confirmed"]	= array("field" => "confirmed",		"caption" => i18n("Confirmed"), 	"type" => "base");
$aFields["deactivated"] = array("field" => "deactivated",	"caption" => i18n("Deactivated"), 	"type" => "base");

if ($action == "recipientgroup_create" && $perm->have_perm_area_action($area, $action))
{
	$oRGroup = $oRGroups->create(" ".i18n("-- new group --"));
	$_REQUEST["idrecipientgroup"] = $oRGroup->get("idnewsgroup");
	$oPage->setReload();
	$sRefreshLeftTopScript = '<script type="text/javascript">top.content.left.left_top.refreshGroupOption(\''.$_REQUEST["idrecipientgroup"].'\', \'add\')</script>';
	$oPage->addScript('refreshlefttop', $sRefreshLeftTopScript);
} elseif ($action == "recipientgroup_delete" && $perm->have_perm_area_action($area, $action)) {
	$oRGroups->delete($_REQUEST["idrecipientgroup"]);
	$sRefreshLeftTopScript = '<script type="text/javascript">top.content.left.left_top.refreshGroupOption(\''.$_REQUEST["idrecipientgroup"].'\', \'remove\')</script>';
	$oPage->addScript('refreshlefttop', $sRefreshLeftTopScript);
	
	$_REQUEST["idrecipientgroup"] = 0;
	$oRGroup = new RecipientGroup;
	$oPage->setReload();
} else {
	$oRGroup->loadByPrimaryKey($_REQUEST["idrecipientgroup"]);
}

if ($oRGroup->virgin == false && $oRGroup->get("idclient") == $client && $oRGroup->get("idlang") == $lang)
{
	if ($action == "recipientgroup_save_group" && $perm->have_perm_area_action($area, $action))
	{
		// Saving changes
		$aMessages	= array();
		$bReload 	= false;
		
		$sGroupName = stripslashes($_REQUEST["groupname"]);
		if ($oRGroup->get("groupname") != $sGroupName)
		{
			$oRGroups->resetQuery();
			$oRGroups->setWhere("groupname",	$sGroupName);
			$oRGroups->setWhere("idclient",		$client);
			$oRGroups->setWhere("idlang",		$lang);
			$oRGroups->setWhere($oRGroup->primaryKey, $oRGroup->get($oRGroup->primaryKey), "!=");
			$oRGroups->query();
			
	 		if ($oRGroups->next()) {
	 			$aMessages[] = i18n("Could not set new group name: Group already exists");	
	 		} else {
	 			$bReload = true;
	 			
	 			$oRGroup->set("groupname", $sGroupName);
	 		}
		}
		
		if (count($_REQUEST["adduser"]) > 0)
		{
			foreach ($_REQUEST["adduser"] as $iRcpID)
			{
				if (is_numeric($iRcpID)) {
					$oRGroupMembers->create($_REQUEST["idrecipientgroup"], $iRcpID);
				}
			}	
		}
	 	
	 	if ($oRGroup->get("defaultgroup") != (int)$_REQUEST["defaultgroup"])
	 	{
	 		$bReload = true;
	 		$oRGroup->set("defaultgroup", $_REQUEST["defaultgroup"]);
	 	}
	 	
	 	$oRGroup->store();
	 	
	 	if ($bReload) {
	 		$oPage->setReload();
	 	}

		// Removing users from group (if specified)
		//print_r ($_REQUEST["deluser"]);
		if ($perm->have_perm_area_action($area, "recipientgroup_recipient_delete") && is_array($_REQUEST["deluser"]))
		{
			foreach ($_REQUEST["deluser"] as $iRcpID)
			{
				if (is_numeric($iRcpID)) {
					echo "yo: " . $iRcpID;
					$oRGroupMembers->remove($_REQUEST["idrecipientgroup"], $iRcpID);
				}
			}
		}
		  
		$sRefreshLeftTopScript = '<script type="text/javascript">top.content.left.left_top.refreshGroupOption(\''.$_REQUEST["idrecipientgroup"].'\', \'remove\');
									top.content.left.left_top.refreshGroupOption(\''.$_REQUEST["idrecipientgroup"].'\', \'add\', \''.$sGroupName.'\');</script>';
		$oPage->addScript('refreshlefttop', $sRefreshLeftTopScript);
	}
	
	if (count($aMessages) > 0) {
		$sNotis = $notification->returnNotification("warning", implode("<br>", $aMessages)) . "<br>";
	}
	
	// Set default values
	$oUser = new cApiUser($auth->auth["uid"]);
	if (!isset($_REQUEST["member_elemperpage"]) || !is_numeric($_REQUEST["member_elemperpage"]) || $_REQUEST["member_elemperpage"] < 0) {
		$_REQUEST["member_elemperpage"] = $oUser->getProperty("itemsperpage", $area."_edit_member");
	}
	if (!is_numeric($_REQUEST["member_elemperpage"])) {
		$_REQUEST["member_elemperpage"] = 25;
	}
	if ($_REQUEST["member_elemperpage"] > 0) { 
		// -- All -- will not be stored, as it may be impossible to change this back to something more useful
		$oUser->setProperty("itemsperpage", $area."_edit_member", $_REQUEST["member_elemperpage"]);
	}
	
	if (!isset($_REQUEST["outsider_elemperpage"]) || !is_numeric($_REQUEST["outsider_elemperpage"]) || $_REQUEST["outsider_elemperpage"] < 0) {
		$_REQUEST["outsider_elemperpage"] = $oUser->getProperty("itemsperpage", $area."_edit_outsider");
	}
	if (!is_numeric($_REQUEST["outsider_elemperpage"])) {
		$_REQUEST["outsider_elemperpage"] = 25;
	}
	if ($_REQUEST["outsider_elemperpage"] > 0) { 
		// -- All -- will not be stored, as it may be impossible to change this back to something more useful
		$oUser->setProperty("itemsperpage", $area."_edit_outsider", $_REQUEST["outsider_elemperpage"]);
	}
	unset ($oUser);

	if (!isset($_REQUEST["member_page"]) || !is_numeric($_REQUEST["member_page"]) || $_REQUEST["member_page"] <= 0 || $_REQUEST["member_elemperpage"] == 0) {
		$_REQUEST["member_page"] = 1;
	}
	if ($_REQUEST["member_sortorder"] != "DESC") {
		$_REQUEST["member_sortorder"]  = "ASC";
	}
	if (!isset($_REQUEST["outsider_page"]) || !is_numeric($_REQUEST["outsider_page"]) || $_REQUEST["outsider_page"] <= 0 || $_REQUEST["outsider_elemperpage"] == 0) {
		$_REQUEST["outsider_page"] = 1;
	}
	if ($_REQUEST["outsider_sortorder"] != "DESC") {
		$_REQUEST["outsider_sortorder"]  = "ASC";
	}
	
	// Output form
	$oForm = new UI_Table_Form("properties", "main.php?1", "get"); // Use "get" for folding rows...
	$oForm->setVar("frame", $frame);
	$oForm->setVar("area", $area);
	$oForm->setVar("action", "recipientgroup_save_group");
	$oForm->setVar("idrecipientgroup", $_REQUEST["idrecipientgroup"]);
	$oForm->setSubmitJS("append_registered_parameters(this);");

	$oForm->addHeader(i18n("Edit group"));
	
	$oTxtGroupName = new cHTMLTextbox("groupname", $oRGroup->get("groupname"),40);
	$oForm->add(i18n("Group name"), $oTxtGroupName->render());
	
	$oCkbDefault = new cHTMLCheckbox("defaultgroup", "1");
	$oCkbDefault->setChecked($oRGroup->get("defaultgroup"));
	$oForm->add(i18n("Default group"), $oCkbDefault->toHTML(false));
			
	// Member list options folding row
	$oMemberListOptionRow = new cFoldingRow("a91f5540-52db-11db-b0de-0800200c9a66",i18n("Member list options"));
	
	$oSelItemsPerPage = new cHTMLSelectElement("member_elemperpage");
	$oSelItemsPerPage->autoFill(array(0 => i18n("-- All --"), 25 => 25, 50 => 50, 75 => 75, 100 => 100));
	$oSelItemsPerPage->setDefault($_REQUEST["member_elemperpage"]);
	
	$oSelSortBy = new cHTMLSelectElement("member_sortby");
	foreach ($aFields as $sKey => $aData) {
		if (strpos($aData["type"], "sort") !== false) {
			if ($_REQUEST["member_sortby"] == "") {
				$_REQUEST["member_sortby"] = $aData["field"];
			}
			$oOption = new cHTMLOptionElement($aData["caption"], $aData["field"]);
			$oSelSortBy->addOptionElement($aData["field"], $oOption);
		}
	}	
	$oSelSortBy->setDefault($_REQUEST["member_sortby"]);
	
	$oSelSortOrder = new cHTMLSelectElement("member_sortorder");
	$oSelSortOrder->autoFill(array("ASC" => i18n("Ascending"), "DESC" => i18n("Descending")));
	$oSelSortOrder->setDefault($_REQUEST["member_sortorder"]);
		
	$oTxtFilter = new cHTMLTextbox("member_filter", $_REQUEST["member_filter"], 16);
	
	$oSelSearchIn = new cHTMLSelectElement("member_searchin");
	$oOption = new cHTMLOptionElement(i18n("-- All fields --"), "--all--");
	$oSelSearchIn->addOptionElement("all", $oOption);
	
	foreach ($aFields as $sKey => $aData) {
		if (strpos($aData["type"], "search") !== false) {
			$oOption = new cHTMLOptionElement($aData["caption"], $aData["field"]);
			$oSelSearchIn->addOptionElement($aData["field"], $oOption);
		}
	}
	$oSelSearchIn->setDefault($_REQUEST["member_searchin"]);
	
	$oSubmit = new cHTMLButton("submit", i18n("Apply"));
	
	$sContent  = '<div style="border-bottom: 1px solid black; background: '.$cfg['color']['table_dark'].';">'.chr(10);
	$sContent .= '   <table>'.chr(10);
	$sContent .= '      <tr>'.chr(10);
	$sContent .= '         <td>'. i18n("Items / page").'</td>'.chr(10);
	$sContent .= '         <td>'.$oSelItemsPerPage->render().'</td>'.chr(10);
	$sContent .= '      </tr>'.chr(10);
	$sContent .= '      <tr>'.chr(10);
	$sContent .= '         <td>'. i18n("Sort by").'</td>'.chr(10);
	$sContent .= '         <td>'.$oSelSortBy->render().'</td>'.chr(10);
	$sContent .= '      </tr>'.chr(10);
	$sContent .= '      <tr>'.chr(10);
	$sContent .= '         <td>'. i18n("Sort order").'</td>'.chr(10);
	$sContent .= '         <td>'.$oSelSortOrder->render().'</td>'.chr(10);
	$sContent .= '      </tr>'.chr(10);
	$sContent .= '      <tr>'.chr(10);
	$sContent .= '         <td>'. i18n("Search for").'</td>'.chr(10);
	$sContent .= '         <td>'.$oTxtFilter->render().'</td>'.chr(10);
	$sContent .= '      </tr>'.chr(10);
	$sContent .= '      <tr>'.chr(10);
	$sContent .= '         <td>'. i18n("Search in").'</td>'.chr(10);
	$sContent .= '         <td>'.$oSelSearchIn->render().'</td>'.chr(10);
	$sContent .= '      </tr>'.chr(10);
	$sContent .= '      <tr>'.chr(10);
	$sContent .= '         <td>&nbsp;</td>'.chr(10);
	$sContent .= '         <td>'.$oSubmit->render().'</td>'.chr(10);
	$sContent .= '      </tr>'.chr(10);
	$sContent .= '   </table>'.chr(10);
	$sContent .= '</div>'.chr(10);
	$oMemberListOptionRow->setContentData($sContent);
			
	// Members
	$oAddedRecipientList = new UI_List;
	$oAddedRecipientList->setWidth("100%");
	$oAddedRecipientList->setBorder(1);

	$oAddedRecipientList->setCell(0, 1, "<strong>".i18n("Name")."</strong>");
	$oImgDel = new cHTMLImage("images/but_invert_selection.gif");
	$sLnkDelIcon = '<a title="'.i18n("Check all").'" href="javascript://" onclick="fncCheckDel(\'deluser[]\');">'.$oImgDel->render().'</a>';
	$oAddedRecipientList->setCell(0, 2, $sLnkDelIcon);
	$oAddedRecipientList->setCellAlignment(0, 2, "right");
	
	$oInsiders = new RecipientCollection;
	
	$oInsiders->link("RecipientGroupMemberCollection");
	$oInsiders->setWhere("recipientcollection.idclient", $client);
	$oInsiders->setWhere("recipientcollection.idlang", $lang);
	$oInsiders->setWhere("RecipientGroupMemberCollection.idnewsgroup", $_REQUEST["idrecipientgroup"]);

	// Get insiders for outsiders list (*sigh!*)
	// TODO: Ask user to have at least mySQL 4.1...
	$oInsiders->query();
	
	$aInsiders = array();
	if ($oInsiders->count() > 0)
	{
		while ($oInsider = $oInsiders->next())
		{
			$aInsiders[] = $oInsider->get($oInsider->primaryKey);
		}
	}

	// Filter
	if ($_REQUEST["member_filter"] != "") {
		if ($_REQUEST["member_searchin"] == "--all--" || $_REQUEST["member_searchin"] == "") {
			foreach ($aFields as $sKey => $aData) {
				if (strpos($aData["type"], "search") !== false) {
					$oInsiders->setWhereGroup("filter", "recipientcollection.".$aData["field"], $_REQUEST["member_filter"], "LIKE");
				}
			}
			$oInsiders->setInnerGroupCondition("filter", "OR");
		} else {
			$oInsiders->setWhere("recipientcollection.".$_REQUEST["member_searchin"], $_REQUEST["member_filter"], "LIKE");
		}
	}

	// If elemperpage is something else than "all", get item count based on filters
	if ($_REQUEST["member_elemperpage"] > 0) {
		$oInsiders->query();
		$iMembers = $oInsiders->count(); // Getting item count without limit (for page function) - better idea anybody (performance)?
	
		$oInsiders->setLimit($_REQUEST["member_elemperpage"] * ($_REQUEST["member_page"] - 1), $_REQUEST["member_elemperpage"]);
	} else {
		$iMembers = 0;
	}
	
	// Get data
	$sSortSQL = "recipientcollection.".$_REQUEST["member_sortby"]." ".$_REQUEST["member_sortorder"];
	if ($_REQUEST["member_sortby"] == "name")
	{
		// Name field may be empty, add email as sort criteria
		$sSortSQL .= ", email ".$_REQUEST["member_sortorder"];
	}
	
	$oInsiders->setOrder($sSortSQL);
	$oInsiders->query();
		
	$iItems = $oInsiders->count();
	if ($iItems == 0 && $_REQUEST["member_filter"] == "" && ($_REQUEST["member_elemperpage"] == 0 || $iMembers == 0)) {
		$oAddedRecipientList->setCell(1, 1, i18n("No recipients are added to this group yet"));
		$oAddedRecipientList->setCell(1, 2, '&nbsp;');
	} else if ($iItems == 0) {
		$oAddedRecipientList->setCell(1, 1, i18n("No recipients found"));
		$oAddedRecipientList->setCell(1, 2, '&nbsp;');
	} else {
		while ($oRcp = $oInsiders->next())
		{
			$iID = $oRcp->get("idnewsrcp");
			
			$sName  = $oRcp->get("name");
			$sEMail = $oRcp->get("email");
			if (empty($sName)) {
				$sName = $sEMail;
			}
			$oAddedRecipientList->setCell($iID, 1, $sName." (".$sEMail.")");
			
			if ($perm->have_perm_area_action($area, "recipientgroup_recipient_delete"))
			{
				$oCkbDel = new cHTMLCheckbox("deluser[]", $iID);
				$oAddedRecipientList->setCell($iID, 2, $oCkbDel->toHTML(false));
			} else {
				$oAddedRecipientList->setCell($iID, 2, "&nbsp;");
			}
			$oAddedRecipientList->setCellAlignment($iID, 2, "right");
		}
	}
	
	// Member list pager (-> below data, as iMembers is needed)
	$oPagerLink = new cHTMLLink;
	$oPagerLink->setLink("main.php");
	$oPagerLink->setCustom("member_elemperpage", $_REQUEST["member_elemperpage"]);
	$oPagerLink->setCustom("member_filter", $_REQUEST["member_filter"]);
	$oPagerLink->setCustom("member_sortby", $_REQUEST["member_sortby"]);
	$oPagerLink->setCustom("member_sortorder", $_REQUEST["member_sortorder"]);
	$oPagerLink->setCustom("member_searchin", $_REQUEST["member_searchin"]);
	$oPagerLink->setCustom("outsider_elemperpage", $_REQUEST["outsider_elemperpage"]);
	$oPagerLink->setCustom("outsider_filter", $_REQUEST["outsider_filter"]);
	$oPagerLink->setCustom("outsider_sortby", $_REQUEST["outsider_sortby"]);
	$oPagerLink->setCustom("outsider_sortorder", $_REQUEST["outsider_sortorder"]);
	$oPagerLink->setCustom("outsider_searchin", $_REQUEST["outsider_searchin"]);
	$oPagerLink->setCustom("idrecipientgroup", $_REQUEST["idrecipientgroup"]);
	$oPagerLink->setCustom("frame", $frame);
	$oPagerLink->setCustom("area", $area);
	#$oPagerLink->enableAutomaticParameterAppend();
	$oPagerLink->setCustom("contenido", $sess->id);
	
	$oMemberPager = new cObjectPager("d82a3ff0-52d9-11db-b0de-0800200c9a66", $iMembers, $_REQUEST["member_elemperpage"], $_REQUEST["member_page"], $oPagerLink, "member_page");
	$oMemberPager->setCaption(i18n("Member navigation"));
	
	$oForm->add(i18n("Recipients in group"), '<table border="0" cellspacing="0" cellpadding="0" width="100%">'.
											 $oMemberListOptionRow->render().
											 $oMemberPager->render().
											 '<tr><td>'.$oAddedRecipientList->render().'</td></tr></table>');
	unset ($oInsiders);
	unset ($oMemberListOptionRow);
	unset ($oMemberPager);
	unset ($oAddedRecipientList);
	
	// Outsiders
	// Outsider list options folding row
	$oOutsiderListOptionRow = new cFoldingRow("ca633b00-52e9-11db-b0de-0800200c9a66",i18n("Outsider list options"));
	
	$oSelItemsPerPage = new cHTMLSelectElement("outsider_elemperpage");
	$oSelItemsPerPage->autoFill(array(0 => i18n("-- All --"), 25 => 25, 50 => 50, 75 => 75, 100 => 100));
	$oSelItemsPerPage->setDefault($_REQUEST["outsider_elemperpage"]);
	
	$oSelSortBy = new cHTMLSelectElement("outsider_sortby");
	foreach ($aFields as $sKey => $aData) {
		if (strpos($aData["type"], "sort") !== false) {
			if ($_REQUEST["outsider_sortby"] == "") {
				$_REQUEST["outsider_sortby"] = $aData["field"];
			}
			$oOption = new cHTMLOptionElement($aData["caption"], $aData["field"]);
			$oSelSortBy->addOptionElement($aData["field"], $oOption);
		}
	}	
	$oSelSortBy->setDefault($_REQUEST["outsider_sortby"]);
	
	$oSelSortOrder = new cHTMLSelectElement("outsider_sortorder");
	$oSelSortOrder->autoFill(array("ASC" => i18n("Ascending"), "DESC" => i18n("Descending")));
	$oSelSortOrder->setDefault($_REQUEST["outsider_sortorder"]);
		
	$oTxtFilter = new cHTMLTextbox("outsider_filter", $_REQUEST["outsider_filter"], 16);
	
	$oSelSearchIn = new cHTMLSelectElement("outsider_searchin");
	$oOption = new cHTMLOptionElement(i18n("-- All fields --"), "--all--");
	$oSelSearchIn->addOptionElement("all", $oOption);
	
	foreach ($aFields as $sKey => $aData) {
		if (strpos($aData["type"], "search") !== false) {
			$oOption = new cHTMLOptionElement($aData["caption"], $aData["field"]);
			$oSelSearchIn->addOptionElement($aData["field"], $oOption);
		}
	}
	$oSelSearchIn->setDefault($_REQUEST["outsider_searchin"]);
	
	$oSubmit = new cHTMLButton("submit", i18n("Apply"));
	
	$sContent  = '<div style="border-bottom: 1px solid black; background: '.$cfg['color']['table_dark'].';">'.chr(10);
	$sContent .= '   <table>'.chr(10);
	$sContent .= '      <tr>'.chr(10);
	$sContent .= '         <td>'. i18n("Items / page").'</td>'.chr(10);
	$sContent .= '         <td>'.$oSelItemsPerPage->render().'</td>'.chr(10);
	$sContent .= '      </tr>'.chr(10);
	$sContent .= '      <tr>'.chr(10);
	$sContent .= '         <td>'. i18n("Sort by").'</td>'.chr(10);
	$sContent .= '         <td>'.$oSelSortBy->render().'</td>'.chr(10);
	$sContent .= '      </tr>'.chr(10);
	$sContent .= '      <tr>'.chr(10);
	$sContent .= '         <td>'. i18n("Sort order").'</td>'.chr(10);
	$sContent .= '         <td>'.$oSelSortOrder->render().'</td>'.chr(10);
	$sContent .= '      </tr>'.chr(10);
	$sContent .= '      <tr>'.chr(10);
	$sContent .= '         <td>'. i18n("Search for").'</td>'.chr(10);
	$sContent .= '         <td>'.$oTxtFilter->render().'</td>'.chr(10);
	$sContent .= '      </tr>'.chr(10);
	$sContent .= '      <tr>'.chr(10);
	$sContent .= '         <td>'. i18n("Search in").'</td>'.chr(10);
	$sContent .= '         <td>'.$oSelSearchIn->render().'</td>'.chr(10);
	$sContent .= '      </tr>'.chr(10);
	$sContent .= '      <tr>'.chr(10);
	$sContent .= '         <td>&nbsp;</td>'.chr(10);
	$sContent .= '         <td>'.$oSubmit->render().'</td>'.chr(10);
	$sContent .= '      </tr>'.chr(10);
	$sContent .= '   </table>'.chr(10);
	$sContent .= '</div>'.chr(10);
	$oOutsiderListOptionRow->setContentData($sContent);	
	
	// TODO: Try to enhance genericdb to get this working with the usual objects...
	$oOutsiders = new RecipientCollection;
	
	# This requires mySQL V4.1, at least...
	# TODO: Add mySQL server version switch
	#$sSQL = "idclient = '".$client."' AND idlang = '".$lang."' AND ". 
	#		"idnewsrcp NOT IN (SELECT idnewsrcp FROM ".$cfg["tab"]["news_groupmembers"]." WHERE idnewsgroup = '".$_REQUEST["idrecipientgroup"]."')";
	
	// TODO: This works with every mySQL version but may be problematic, if a group
	// contains a lot of members (e.g. Oracle can't handle more than 1000 items in the brackets) 
	$sSQL = "idclient = '".$client."' AND idlang = '".$lang."' AND ". 
			"idnewsrcp NOT IN ('" . implode ("','", $aInsiders) . "')";
			
	if ($_REQUEST["outsider_filter"] != "") {
		$sSQLSearchIn = "";
		if ($_REQUEST["outsider_searchin"] == "--all--" || $_REQUEST["outsider_searchin"] == "") {
			foreach ($aFields as $sKey => $aData) {
				if (strpos($aData["type"], "search") !== false) {
					if ($sSQLSearchIn !== "") {
						$sSQLSearchIn .= " OR ";
					}
					$sSQLSearchIn .= $aData["field"] . " LIKE '%" . urlencode($_REQUEST["outsider_filter"]) . "%'"; 
				}
			}
		} else {
			$sSQLSearchIn .= urlencode($_REQUEST["outsider_searchin"]) . " LIKE '%" . urlencode($_REQUEST["outsider_filter"]) . "%'";
		}
		$sSQL .= " AND (" . $sSQLSearchIn . ")";
	}

	// If elemperpage is something else than "all", get item count based on filters
	if ($_REQUEST["outsider_elemperpage"] > 0) {
		$oOutsiders->flexSelect("", "", $sSQL, "");
		$iOutsiders = $oOutsiders->count(); // Getting item count without limit (for page function) - better idea anyone (performance)?
	
		$sSQLLimit = " LIMIT " . $_REQUEST["outsider_elemperpage"] * ($_REQUEST["outsider_page"] - 1) . ", " . $_REQUEST["outsider_elemperpage"];
	} else {
		$iMembers = 0;
		$sSQLLimit = "";
	}
	
	// Get data
	$sSQLSort = " ORDER BY ".urlencode($_REQUEST["outsider_sortby"])." ".$_REQUEST["outsider_sortorder"];
	if ($_REQUEST["outsider_sortby"] == "name")
	{
		// Name field may be empty, add email as sort criteria
		$sSQLSort .= ", email ".$_REQUEST["outsider_sortorder"];
	}
	
	$sSQL .= $sSQLSort . $sSQLLimit;
	$oOutsiders->flexSelect("", "", $sSQL, "");
	
	$aItems = array();
	while ($oRecipient = $oOutsiders->next())
	{
		$sName 	= $oRecipient->get("name");
		$sEMail = $oRecipient->get("email");

		if (empty($sName)) {
			$sName = $sEMail;
		}
		$aItems[] = array($oRecipient->get("idnewsrcp"), $sName." (".$sEMail.")");	
	}
	
	$oSelUser = new cHTMLSelectElement("adduser[]");
	$oSelUser->setSize(25);
	$oSelUser->setStyle("width: 100%;");
	$oSelUser->setMultiSelect();
	$oSelUser->autoFill($aItems);
	
	// Outsider list pager (-> below data, as iOutsiders is needed)
	$oPagerLink = new cHTMLLink;
	$oPagerLink->setLink("main.php");
	$oPagerLink->setCustom("member_elemperpage", $_REQUEST["member_elemperpage"]);
	$oPagerLink->setCustom("member_filter", $_REQUEST["member_filter"]);
	$oPagerLink->setCustom("member_sortby", $_REQUEST["member_sortby"]);
	$oPagerLink->setCustom("member_sortorder", $_REQUEST["member_sortorder"]);
	$oPagerLink->setCustom("member_searchin", $_REQUEST["member_searchin"]);
	$oPagerLink->setCustom("outsider_elemperpage", $_REQUEST["outsider_elemperpage"]);
	$oPagerLink->setCustom("outsider_filter", $_REQUEST["outsider_filter"]);
	$oPagerLink->setCustom("outsider_sortby", $_REQUEST["outsider_sortby"]);
	$oPagerLink->setCustom("outsider_sortorder", $_REQUEST["outsider_sortorder"]);
	$oPagerLink->setCustom("outsider_searchin", $_REQUEST["outsider_searchin"]);
	$oPagerLink->setCustom("idrecipientgroup", $_REQUEST["idrecipientgroup"]);
	$oPagerLink->setCustom("frame", $frame);
	$oPagerLink->setCustom("area", $area);
	#$oPagerLink->enableAutomaticParameterAppend();
	$oPagerLink->setCustom("contenido", $sess->id);
	
	$oOutsiderPager = new cObjectPager("4d3a7330-52eb-11db-b0de-0800200c9a66", $iOutsiders, $_REQUEST["outsider_elemperpage"], $_REQUEST["outsider_page"], $oPagerLink, "outsider_page");
	$oOutsiderPager->setCaption(i18n("Outsider navigation"));
	
	$oForm->add(i18n("Add recipients"), '<table border="0" cellspacing="0" cellpadding="0" width="100%">'.
										$oOutsiderListOptionRow->render().
										$oOutsiderPager->render().
										'<tr><td>'.$oSelUser->render().'<br />'.i18n("Note: Hold &lt;Ctrl&gt; to<br>select multiple items.").'</td></tr></table>');	
	unset ($oOutsiders);
	unset ($oOutsiderListOptionRow);
	unset ($oOutsiderPager);
		
	$sDelMarkScript = '    <script type="text/javascript">
        /* Function to select all ckbDel boxes */
        function fncCheckDel(elementname) {
			var aBoxes = document.getElementsByName(elementname);

            if (aBoxes.length > 0) {
                for (var i = 0; i < aBoxes.length; i++) {
                    if (aBoxes[i].checked) {
                       aBoxes[i].checked = false;
                    } else {
                       aBoxes[i].checked = true;
                    }
                }
            }
        }
    </script>'; 
	
	$oPage->addScript('DelMarkScript', $sDelMarkScript);
	$oPage->addScript('cfoldingrow.js', '<script language="JavaScript" src="scripts/cfoldingrow.js"></script>');
	$oPage->addScript('parameterCollector.js', '<script language="JavaScript" src="scripts/parameterCollector.js"></script>');
	
	$oPage->setContent($sNotis.$oForm->render(true));
} else {
	$oPage->setContent($sNotis."");	
}
$oPage->render();

?>