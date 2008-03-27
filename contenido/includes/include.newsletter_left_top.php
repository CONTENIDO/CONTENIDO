<?php
/*****************************************
* File      :   $RCSfile: include.newsletter_left_top.php,v $
* Project   :   Contenido
* Descr     :   Left top pane
* Modified  :   $Date: 2007/06/19 23:18:38 $
*
* © four for business AG, www.4fb.de, modified by HerrB
*
* $Id: include.newsletter_left_top.php,v 1.10 2007/06/19 23:18:38 bjoern.behrens Exp $
******************************************/

cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "class.ui.php");
cInclude("classes", "widgets/class.widgets.foldingrow.php");
cInclude("classes", "widgets/class.widgets.pager.php");
cInclude("classes", "class.newsletter.php");
cInclude("classes", "class.newsletter.groups.php");
cInclude("classes", "contenido/class.user.php");
cInclude("classes", "contenido/class.clientslang.php");


##################################
# Getting values for sorting, etc.
##################################
$oUser = new cApiUser($auth->auth["uid"]);
$oClientLang 	= new cApiClientLanguage(false, $client, $lang);

// Items per Page
if (!isset($_REQUEST["elemperpage"]) || !is_numeric($_REQUEST["elemperpage"]) || $_REQUEST["elemperpage"] < 0){
	$_REQUEST["elemperpage"] = $oUser->getProperty("itemsperpage", $area);
}
if (!is_numeric($_REQUEST["elemperpage"])){
	$_REQUEST["elemperpage"] = 25;
}
if ($_REQUEST["elemperpage"] > 0){ 
	$oUser->setProperty("itemsperpage", $area, $_REQUEST["elemperpage"]);
}
// Current page
if (!isset($_REQUEST["page"]) || !is_numeric($_REQUEST["page"]) || $_REQUEST["page"] <= 0 || $_REQUEST["elemperpage"] == 0) {
	$_REQUEST["page"] = 1;
}

// Sort order
if ($_REQUEST["sortorder"] != "DESC") {
	$_REQUEST["sortorder"]  = "ASC";
}
// Sort by
$_REQUEST["sortby"] = 'Name';

// HTML Newsletter: idcat containing HTML newsletter template articles (stored per client/lang property)
if ($_REQUEST["selHTMLTemplateCat"] == "" || !$perm->have_perm_area_action($area, "news_html_settings")) {
	$_REQUEST["selHTMLTemplateCat"] = $oClientLang->getProperty("newsletter", "html_template_idcat");
}
if (!is_numeric($_REQUEST["selHTMLTemplateCat"]) || $_REQUEST["selHTMLTemplateCat"] < 0) {
	$_REQUEST["selHTMLTemplateCat"] = 0;
}

// HTML Newsletter: idcat containing HTML newsletter articles (stored per client/lang property)
if ($_REQUEST["selHTMLNewsletterCat"] == "" || !$perm->have_perm_area_action($area, "news_html_settings")) {
	$_REQUEST["selHTMLNewsletterCat"] = $oClientLang->getProperty("newsletter", "html_newsletter_idcat");
}
if (!is_numeric($_REQUEST["selHTMLNewsletterCat"]) || $_REQUEST["selHTMLNewsletterCat"] < 0) {
	$_REQUEST["selHTMLNewsletterCat"] = 0;
}

// HTML Newsletter: Deactivate HTML newsletters, if necessary idcats have not been specified
if ($_REQUEST["selHTMLTemplateCat"] == 0 || $_REQUEST["selHTMLNewsletterCat"] == 0) {
	unset ($_REQUEST["ckbHTMLNewsletter"]);
}

// Send test destination
if (!isset($_REQUEST["selTestDestination"]) || !is_numeric($_REQUEST["selTestDestination"]) || $_REQUEST["selTestDestination"] < 0) {
	$_REQUEST["selTestDestination"] = $oUser->getProperty("newsletter", "test_idnewsgrp");
}
if (!is_numeric($_REQUEST["selTestDestination"]) || !$perm->have_perm_area_action($area, "news_send_test")) {
	$_REQUEST["selTestDestination"] = 0; // E-Mail address of current user
} 


############
# 0. BUTTONS
############
// Newsletter
$imgNewsletterId='img_newsletter';
$tpl->set('s', 'INEWSLETTER', $imgNewsletterId);
$buttonRow  = '<a style="margin-right:5px;" href="javascript://" onclick="toggleContainer(\''.$imgNewsletterId.'\');reloadLeftBottomAndTransportFormVars(document.newsletter_listoptionsform);">';
$buttonRow .= '<img onmouseover="hoverEffect(\''.$imgNewsletterId.'\', \'in\')" onmouseout="hoverEffect(\''.$imgNewsletterId.'\', \'out\')" alt="'.i18n("Newsletter").'" title="'.i18n("Newsletter").'" name="'.$imgNewsletterId.'" id="'.$imgNewsletterId.'" src="'.$cfg["path"]["images"].'newsletter_on.gif"/>';
$buttonRow .= '</a>';
// Dispatch
$imgDispatchId='img_dispatch';
$tpl->set('s', 'IDISPATCH', $imgDispatchId);
$buttonRow .= '<a style="margin-right:5px;" href="javascript://" onclick="toggleContainer(\''.$imgDispatchId.'\');reloadLeftBottomAndTransportFormVars(document.dispatch_listoptionsform);">';
$buttonRow .= '<img onmouseover="hoverEffect(\''.$imgDispatchId.'\', \'in\')" onmouseout="hoverEffect(\''.$imgDispatchId.'\', \'out\')" alt="'.i18n("Dispatch").'" title="'.i18n("Dispatch").'" name="'.$imgDispatchId.'" id="'.$imgDispatchId.'" src="'.$cfg["path"]["images"].'newsletter_dispatch_on.gif"/>';
$buttonRow .= '</a>';
// Recipients
$imgRecipientId='img_recipient';
$tpl->set('s', 'IRECIPIENTS', $imgRecipientId);
$buttonRow .= '<a style="margin-right:5px;" href="javascript://" onclick="toggleContainer(\''.$imgRecipientId.'\');reloadLeftBottomAndTransportFormVars(document.recipients_listoptionsform);">';
$buttonRow .= '<img onmouseover="hoverEffect(\''.$imgRecipientId.'\', \'in\')" onmouseout="hoverEffect(\''.$imgRecipientId.'\', \'out\')" alt="'.i18n("Recipients").'" title="'.i18n("Recipients").'" id="'.$imgRecipientId.'" src="'.$cfg["path"]["images"].'newsletter_recipients_on.gif"/>';
$buttonRow .= '</a>';
// Recipient Groups
$imgRecipientGroupId='img_recipientgroup';
$tpl->set('s', 'IRECIPIENTGROUP', $imgRecipientGroupId);
$buttonRow .= '<a style="margin-right:5px;" href="javascript://" onclick="toggleContainer(\''.$imgRecipientGroupId.'\');reloadLeftBottomAndTransportFormVars(groups_listoptionsform);">';
$buttonRow .= '<img onmouseover="hoverEffect(\''.$imgRecipientGroupId.'\', \'in\')" onmouseout="hoverEffect(\''.$imgRecipientGroupId.'\', \'out\')" alt="'.i18n("Recipient groups").'" title="'.i18n("Recipient groups").'" id="'.$imgRecipientGroupId.'" src="'.$cfg["path"]["images"].'newsletter_recipientgroups_on.gif"/>';
$buttonRow .= '</a>';

$tpl->set('s', 'BUTTONROW', $buttonRow);


##############################
# 1. NEWSLETTER
##############################
$oPage			= new cPage;
$oMenu			= new UI_Menu;

$lIDCatArt		= (int)$oClientLang->getProperty("newsletter", "idcatart"); // Get idCatArt to check, if we may send a test newsletter

if (!is_object($oDB)) {
	$oDB = new DB_Contenido; // We have really to send a special SQL statement - we need a DB object
}

$sSendTestTitle		= i18n("Send test newsletter");
$sSendTestTitleOff	= i18n("Send test newsletter (disabled, check newsletter sender e-mail address and handler article selection)");
$sCurrentUser		= $oUser->get("realname"). " (" . $oUser->get("email") . ")";
$sAddJobTitle		= i18n("Add newsletter dispatch job");
$sAddJobTitleOff	= i18n("Add newsletter dispatch job (disabled, check newsletter sender e-mail address and handler article selection)");
$sCopyTitle			= i18n("Duplicate newsletter");
$sDelTitle			= i18n("Delete newsletter");
$sDelDescr 			= i18n("Do you really want to delete the following newsletter:<br>");

unset ($oUser); // Object not needed anymore

##########################
# 1.2 Actions folding row
##########################
$actionLink="actionlink"; // ID for HTML element
$oActionRow = new cFoldingRow("28cf9b31-e6d7-4657-a9a7-db31478e7a5c",i18n("Actions"), $actionLink);
$tpl->set('s', 'ACTIONLINK', $actionLink);

if ($perm->have_perm_area_action($area, "news_create"))
{
	// Create the link to add a newsletter
	$sContent  = '<div style="padding: 4px; padding-left: 17px; margin-bottom:2px; background: '.$cfg['color']['table_dark'].';">'.chr(10);
	
	$oLink = new cHTMLLink;
	$oLink->setMultiLink("news","","news","news_create");
	$oLink->setContent('<img style="margin-right: 4px;" src="'.$cfg["path"]["images"] . 'folder_new.gif" align="middle">'.i18n("Create newsletter"));
	
	$sContent .= $oLink->render();
	$sContent .= '</div>'.chr(10);
	$oActionRow->setContentData($sContent);
}
else
{
	$oActionRow->setContentData("");
}



###########################
# 1.2 Settings folding row
###########################
$settingsLink="settingslink";
$oSettingsRow = new cFoldingRow("d64baf0a-aea9-47b3-8490-54a00fce02b5",i18n("Settings"), $settingsLink);
$tpl->set('s', 'SETTINGSLINK', $settingsLink);

// HTML Newsletter: Enabled option
$bHTMLNewsletter = false;
if ($oClientLang->getProperty("newsletter", "html_newsletter") == "true") {
	$bHTMLNewsletter = true;
}
$oCkbHTMLNewsletter 		= new cHTMLCheckbox("ckbHTMLNewsletter", "enabled", "", $bHTMLNewsletter);

// HTML Newsletter: Template and newsletter category
// Note, that in PHP 5 it is not possible to have a truely working copy of an object
// so, we are filling two almost identical objects with the same data ("clone" may work, but is not available in PHP4 ...)
$oSelHTMLTemplateIDCat		= new cHTMLSelectElement("selHTMLTemplateCat");
$oSelHTMLNewsletterIDCat	= new cHTMLSelectElement("selHTMLNewsletterCat");

$oOptionTemplate			= new cHTMLOptionElement("--".i18n("Please select")."--", 0);
$oSelHTMLTemplateIDCat->addOptionElement(0, $oOptionTemplate);
$oOptionNewsletter			= new cHTMLOptionElement("--".i18n("Please select")."--", 0);
$oSelHTMLNewsletterIDCat->addOptionElement(0, $oOptionNewsletter);

$sSQL  = "SELECT tblCat.idcat AS idcat, tblCatLang.name AS name, tblCatTree.level AS level, ";
$sSQL .= "tblCatLang.visible AS visible, tblCatLang.public AS public FROM ";
$sSQL .= $cfg["tab"]["cat"]." AS tblCat, ".$cfg["tab"]["cat_lang"]." AS tblCatLang, ";
$sSQL .= $cfg["tab"]["cat_tree"]." AS tblCatTree ";
$sSQL .= "WHERE tblCat.idclient = '".$client."' AND tblCatLang.idlang = '".$lang."' AND ";
$sSQL .= "tblCatLang.idcat = tblCat.idcat AND tblCatTree.idcat = tblCat.idcat ";
$sSQL .= "ORDER BY tblCatTree.idtree";

$oDB->query($sSQL);

while ($oDB->next_record()) {
	$sSpaces = "&nbsp;&nbsp;";

	for ($i = 0; $i < $oDB->f("level"); $i ++) {
		$sSpaces .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	}

	$oOptionTemplate	= new cHTMLOptionElement($sSpaces.$oDB->f("name"), $oDB->f("idcat"));
	$oOptionNewsletter	= new cHTMLOptionElement($sSpaces.$oDB->f("name"), $oDB->f("idcat"));
	if ($oDB->f("visible") == 0 || $oDB->f("public") == 0) {
		$oOptionTemplate->setStyle("color: #666666;");
		$oOptionNewsletter->setStyle("color: #666666;");
	}
	$oSelHTMLTemplateIDCat->addOptionElement($oDB->f("idcat"), $oOptionTemplate);
	$oSelHTMLNewsletterIDCat->addOptionElement($oDB->f("idcat"), $oOptionNewsletter);
}
$oSelHTMLTemplateIDCat->setStyle("width: 220px;");
$oSelHTMLTemplateIDCat->setDefault($_REQUEST["selHTMLTemplateCat"]);

$oSelHTMLNewsletterIDCat->setStyle("width: 220px;");
$oSelHTMLNewsletterIDCat->setDefault($_REQUEST["selHTMLNewsletterCat"]);

# Disable HTML options, if user has no rights
if (!$perm->have_perm_area_action($area, "news_html_settings"))
{
	$oSelHTMLTemplateIDCat->setDisabled("disabled");
	$oSelHTMLNewsletterIDCat->setDisabled("disabled");
	$oCkbHTMLNewsletter->setDisabled("disabled");
}

// Destination for sending test newsletter
$oSelTestDestination	= new cHTMLSelectElement("selTestDestination");

$oOption = new cHTMLOptionElement(i18n("My mail address"), 0);
$oSelTestDestination->addOptionElement(0, $oOption);

$oRcpGroups = new RecipientGroupCollection;
$oRcpGroups->setWhere("idclient", $client);
$oRcpGroups->setWhere("idlang", $lang);
$oRcpGroups->setOrder("groupname");
$oRcpGroups->query();

$sSendTestTarget = "";
while ($oRcpGroup = $oRcpGroups->next())
{
	$iID = $oRcpGroup->get($oRcpGroup->primaryKey);
	
	if ($_REQUEST["selTestDestination"] == $iID) {
		$sSendTestTarget = sprintf(i18n("Recipient group: %s"), $oRcpGroup->get("groupname"));
	}
	
	$oOption = new cHTMLOptionElement($oRcpGroup->get("groupname"), $iID);
	$oSelTestDestination->addOptionElement($iID, $oOption);
}

if ($sSendTestTarget == "")
{
	$_REQUEST["selTestDestination"] = 0;
	$sSendTestTarget = $sCurrentUser;
} 

$oSelTestDestination->setStyle("width: 220px;");
$oSelTestDestination->setDefault($_REQUEST["selTestDestination"]);
if (!$perm->have_perm_area_action($area, "news_send_test"))
{
	$oSelTestDestination->setDisabled("disabled"); // No right to send somewhere else than to yourself
}
$sSendTestDescr = sprintf(i18n("Do you really want to send the newsletter to:<br><strong>%s</strong>"), $sSendTestTarget);

$oBtnSubmitSettings = new cHTMLButton("submit", i18n("Save"));

$sContent  = '<div style="border-bottom: 0px solid #B3B3B3; padding-left:17px; background: '.$cfg['color']['table_dark'].';">'.chr(10);
$sContent .= '  <form target="left_bottom" onsubmit="append_registered_parameters(this);" id="htmlnewsletter" name="htmlnewsletter" method="get" action="main.php?1">'.chr(10);
$sContent .= '   <input type="hidden" name="area" value="'.$area.'">'.chr(10);
$sContent .= '   <input type="hidden" name="frame" value="2">'.chr(10);
$sContent .= '   <input type="hidden" name="contenido" value="'.$sess->id.'">'.chr(10);
$sContent .= '   <input type="hidden" name="elemperpage" value="'.$_REQUEST["elemperpage"].'">'.chr(10);
$sContent .= '   <input type="hidden" name="sortby" value="'.$_REQUEST["sortby"].'">'.chr(10);
$sContent .= '   <input type="hidden" name="sortorder" value="'.$_REQUEST["sortorder"].'">'.chr(10);
$sContent .= '   <input type="hidden" name="restrictgroup" value="'.$_REQUEST["restrictgroup"].'">'.chr(10);
$sContent .= '   <input type="hidden" name="filter" value="'.$_REQUEST["filter"].'">'.chr(10);
$sContent .= '   <input type="hidden" name="searchin" value="'.$_REQUEST["searchin"].'">'.chr(10);
$sContent .= '   <input type="hidden" name="action_html" value="save_newsletter_properties">'.chr(10);
$sContent .= '   <table>'.chr(10);
$sContent .= '      <tr>'.chr(10);
$sContent .= '         <td>'.$oCkbHTMLNewsletter->toHTML(false).' '.i18n("Enable HTML Newsletter").'</td>'.chr(10);
$sContent .= '      </tr>'.chr(10);
$sContent .= '      <tr>'.chr(10);
$sContent .= '         <td>'.i18n("HTML Template Category:").'<br />'.$oSelHTMLTemplateIDCat->render().'</td>'.chr(10);
$sContent .= '      </tr>'.chr(10);
$sContent .= '      <tr>'.chr(10);
$sContent .= '         <td>'.i18n("HTML Newsletter Category:").'<br />'.$oSelHTMLNewsletterIDCat->render().'</td>'.chr(10);
$sContent .= '      </tr>'.chr(10);
$sContent .= '      <tr>'.chr(10);
$sContent .= '         <td>'.i18n("Send test destination:").'<br />'.$oSelTestDestination->render().'</td>'.chr(10);
$sContent .= '      </tr>'.chr(10);
$sContent .= '      <tr>'.chr(10);
$sContent .= '         <td style="text-align: right;">'.$oBtnSubmitSettings->render().'</td>'.chr(10);
$sContent .= '      </tr>'.chr(10);
$sContent .= '    </table>'.chr(10);
$sContent .= '  </form>'.chr(10);
$sContent .= '</div>'.chr(10);
$oSettingsRow->setContentData($sContent);

###############################
# 1.3 List options folding row
###############################
// Items per Page
$oSelectItemsPerPage = new cHTMLSelectElement("elemperpage");
$oSelectItemsPerPage->autoFill(array(0 => i18n("-- All --"), 25 => 25, 50 => 50, 75 => 75, 100 => 100));
$oSelectItemsPerPage->setDefault($_REQUEST["elemperpage"]);
// Sort By
$oSelectSortBy = new cHTMLSelectElement("sortby");
$oOption = new cHTMLOptionElement("Name", "name");
$oSelectSortBy->addOptionElement($sKey, $oOption);
$oSelectSortBy->setDefault($_REQUEST["sortby"]);
// Sort Order
$oSelectSortOrder = new cHTMLSelectElement("sortorder");
$oSelectSortOrder->autoFill(array("ASC" => i18n("Ascending"), "DESC" => i18n("Descending")));
$oSelectSortOrder->setDefault($_REQUEST["sortorder"]);
// Search For
$oTextboxFilter = new cHTMLTextbox("filter", $_REQUEST["filter"], 16);
// Search In
$oSelectSearchIn = new cHTMLSelectElement("searchin");
$oOption = new cHTMLOptionElement(i18n("-- All fields --"), "--all--");
$oSelectSearchIn->addOptionElement("all", $oOption);
$oOption = new cHTMLOptionElement("Name", "name");
$oSelectSearchIn->addOptionElement($sKey, $oOption);
$oSelectSearchIn->setDefault($_REQUEST["searchin"]);
// Submit Button
$oSubmit = new cHTMLButton("submit", i18n("Apply"));

$sContentNewsletter  = '<div style="border-bottom: 0px solid #B3B3B3; padding-left: 17px; background: '.$cfg['color']['table_dark'].';">'.chr(10);
$sContentNewsletter .= '<form target="left_bottom" onsubmit="reloadLeftBottomAndTransportFormVars(this);"  id="newsletter_listoptionsform" name="newsletter_listoptionsform" method="get" action="">'.chr(10);
$sContentNewsletter .= '   <input type="hidden" name="area" value="'.$area.'">'.chr(10);
$sContentNewsletter .= '   <input type="hidden" name="frame" value="2">'.chr(10);
$sContentNewsletter .= '   <input type="hidden" name="contenido" value="'.$sess->id.'">'.chr(10);
$sContentNewsletter .= '   <table>'.chr(10);
$sContentNewsletter .= '      <tr>'.chr(10);
$sContentNewsletter .= '         <td>'. i18n("Items / page").'</td>'.chr(10);
$sContentNewsletter .= '         <td>'.$oSelectItemsPerPage->render().'</td>'.chr(10);
$sContentNewsletter .= '      </tr>'.chr(10);
$sContentNewsletter .= '      <tr>'.chr(10);
$sContentNewsletter .= '         <td>'. i18n("Sort by").'</td>'.chr(10);
$sContentNewsletter .= '         <td>'.$oSelectSortBy->render().'</td>'.chr(10);
$sContentNewsletter .= '      </tr>'.chr(10);
$sContentNewsletter .= '      <tr>'.chr(10);
$sContentNewsletter .= '         <td>'. i18n("Sort order").'</td>'.chr(10);
$sContentNewsletter .= '         <td>'.$oSelectSortOrder->render().'</td>'.chr(10);
$sContentNewsletter .= '      </tr>'.chr(10);
$sContentNewsletter .= '      <tr>'.chr(10);
$sContentNewsletter .= '         <td>'. i18n("Search for").'</td>'.chr(10);
$sContentNewsletter .= '         <td>'.$oTextboxFilter->render().'</td>'.chr(10);
$sContentNewsletter .= '      </tr>'.chr(10);
$sContentNewsletter .= '      <tr>'.chr(10);
$sContentNewsletter .= '         <td>'. i18n("Search in").'</td>'.chr(10);
$sContentNewsletter .= '         <td>'.$oSelectSearchIn->render().'</td>'.chr(10);
$sContentNewsletter .= '      </tr>'.chr(10);
$sContentNewsletter .= '      <tr>'.chr(10);
$sContentNewsletter .= '         <td>&nbsp;</td>'.chr(10);
$sContentNewsletter .= '         <td>'.$oSubmit->render().'</td>'.chr(10);
$sContentNewsletter .= '      </tr>'.chr(10);
$sContentNewsletter .= '    </table>'.chr(10);
$sContentNewsletter .= '</form>'.chr(10);
$sContentNewsletter .= '</div>'.chr(10);

// to template
$listOptionLink="listoption";
$oListOptionRow = new cFoldingRow("9d0968be-601d-44f8-a666-99d51c9c777d",i18n("List options"), $listOptionLink);
$oListOptionRow->setContentData($sContentNewsletter);
$tpl->set('s', 'LISTOPTIONLINK', $listOptionLink);

// Request data
$oNewsletters = new NewsletterCollection;
$oNewsletters->setWhere("idclient", $client);
$oNewsletters->setWhere("idlang", $lang);

if ($_REQUEST["filter"] != "") {
	if ($_REQUEST["searchin"] == "--all--" || $_REQUEST["searchin"] == "") {
		foreach ($aFields as $sKey => $aData) {
			if (strpos($aData["type"], "search") !== false) {
				$oNewsletters->setWhereGroup("filter", $aData["field"], $_REQUEST["filter"], "LIKE");
			}
		}
		$oNewsletters->setInnerGroupCondition("filter", "OR");
	} else {
		$oNewsletters->setWhere($_REQUEST["searchin"], $_REQUEST["filter"], "LIKE");
	}
}

if ($_REQUEST["elemperpage"] > 0) {
	$oNewsletters->query();
	$iItemCount = $oNewsletters->count(); // Getting item count without limit (for page function) - better idea anyone (performance)?
	
	$oNewsletters->setLimit($_REQUEST["elemperpage"] * ($_REQUEST["page"] - 1), $_REQUEST["elemperpage"]);
} else {
	$iItemCount = 0;
}

$oNewsletters->setOrder("welcome DESC, " . $_REQUEST["sortby"]." ".$_REQUEST["sortorder"]);
$oNewsletters->query();

// Output data
//$oMenu	= new UI_Menu;
$iMenu	= 0;
while ($oNewsletter = $oNewsletters->next()) {
	$idnewsletter = $oNewsletter->get("idnews");
	$iMenu++;
	
	$sName = $oNewsletter->get("name");
	if ($oNewsletter->get("welcome")) {
		$sName = $sName . "*";
	}

	// Create the link to show/edit the newsletter
	$oLnk = new cHTMLLink;
	$oLnk->setMultiLink($area, "", $area, "");
	$oLnk->setCustom("idnewsletter", $idnewsletter);
	
	$oMenu->setTitle($iMenu, $sName);
	$oMenu->setLink($iMenu, $oLnk);
	
	if ($perm->have_perm_area_action($area, "news_add_job") ||
	    $perm->have_perm_area_action($area, "news_create") ||
	    $perm->have_perm_area_action($area, "news_save"))
	{
		// Rights: If you are able to add a job, you should be able to test it
		//         If you are able to add or change a newsletter, you should be able to test it
		// Usability: If no e-mail has been specified, you can't send a test newsletter
		if (isValidMail($oNewsletter->get("newsfrom")) && $lIDCatArt > 0)
		{
			$sLnkSendTest = '<a title="'.$sSendTestTitle.'" href="javascript://" onclick="showSendTestMsg('.$idnewsletter.')"><img src="'.$cfg['path']['images'].'newsletter_sendtest_16.gif" border="0" title="'.$sSendTestTitle.'" alt="'.$sSendTestTitle.'" /></a>';
		} else {
			$sLnkSendTest = '<img src="'.$cfg['path']['images'].'newsletter_sendtest_16_off.gif" border="0" title="'.$sSendTestTitleOff.'" alt="'.$sSendTestTitleOff.'" />';
		}
		$oMenu->setActions($iMenu, 'test', $sLnkSendTest);
	}
	
	if ($perm->have_perm_area_action($area, "news_add_job"))
	{
		if (isValidMail($oNewsletter->get("newsfrom")) && $lIDCatArt > 0)
		{
			$oLnkAddJob = new Link;
			$oLnkAddJob->setMultiLink("news","","news","news_add_job");
			$oLnkAddJob->setCustom("idnewsletter", $idnewsletter);
			$oLnkAddJob->setAlt($sAddJobTitle);
			$oLnkAddJob->setContent('<img src="'.$cfg['path']['images'].'newsletter_dispatch_16.gif" border="0" title="'.$sAddJobTitle.'" alt="'.$sAddJobTitle.'">');
			
			$sLnkAddJob = $oLnkAddJob->render();
		} else {
			$sLnkAddJob = '<img src="'.$cfg['path']['images'].'newsletter_dispatch_16_off.gif" border="0" title="'.$sAddJobTitleOff.'" alt="'.$sAddJobTitleOff.'" />';
		}
		
		$oMenu->setActions($iMenu, 'dispatch', $sLnkAddJob);
	}
	
	if ($perm->have_perm_area_action($area, "news_create"))
	{
		$oLnkCopy = new Link;
		$oLnkCopy->setMultiLink("news", "", "news", "news_duplicate");
		$oLnkCopy->setCustom("idnewsletter", $idnewsletter);
		$oLnkCopy->setAlt($sCopyTitle);
		$oLnkCopy->setContent('<img src="'.$cfg['path']['images'].'but_copy.gif" border="0" title="'.$sCopyTitle.'" alt="'.$sCopyTitle.'">');
	
		$oMenu->setActions($iMenu, 'copy', $oLnkCopy->render());
	}
	
	if ($perm->have_perm_area_action($area, "news_delete"))
	{ 
		$sDelete = '<a title="'.$sDelTitle.'" href="javascript://" onclick="showDelMsg('.$idnewsletter.',\''.addslashes($sName).'\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$sDelTitle.'" alt="'.$sDelTitle.'"></a>';
			
		$oMenu->setActions($iMenu, 'delete', $sDelete);
	}
	$oMenu->setImage($iMenu, "images/newsletter.gif");	
}

#########################
# 1.4 Paging folding row
#########################
$oPagerLink = new cHTMLLink;
$oPagerLink->setLink("main.php");
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setCustom("elemperpage", $_REQUEST["elemperpage"]);
$oPagerLink->setCustom("filter", $_REQUEST["filter"]);
$oPagerLink->setCustom("restrictgroup", $_REQUEST["restrictgroup"]);
$oPagerLink->setCustom("sortby", $_REQUEST["sortby"]);
$oPagerLink->setCustom("sortorder", $_REQUEST["sortorder"]);
$oPagerLink->setCustom("searchin", $_REQUEST["searchin"]);
$oPagerLink->setCustom("restrictgroup", $_REQUEST["restrictgroup"]);
$oPagerLink->setCustom("frame", 2);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);

$pagerl="pagerlink";
$tpl->set('s', 'PAGINGLINK', $pagerl);
$oPager = new cObjectPager("0ed6d632-6adf-4f09-a0c6-1e38ab60e302", $iItemCount, $_REQUEST["elemperpage"], $_REQUEST["page"], $oPagerLink, 'page', $pagerl);

##############################
# 2. DISPATCH
##############################
//$oPage	= new cPage;
//$oMenu	= new UI_Menu;
$oJobs	= new cNewsletterJobCollection;

// Check, set and save values
if ($_REQUEST["selAuthor"] == "") {
	$_REQUEST["selAuthor"] = $auth->auth["uid"];
}

// Items per page (value stored per area in user property)
$oUser = new cApiUser($auth->auth["uid"]);
if (!isset($_REQUEST["elemperpage"]) || !is_numeric($_REQUEST["elemperpage"]) || $_REQUEST["elemperpage"] < 0) {
	$_REQUEST["elemperpage"] = $oUser->getProperty("itemsperpage", $area);
}
if (!is_numeric($_REQUEST["elemperpage"])) {
	$_REQUEST["elemperpage"] = 25;
}
if ($_REQUEST["elemperpage"] > 0) { 
	// -- All -- will not be stored, as it may be impossible to change this back to something more useful
	$oUser->setProperty("itemsperpage", $area, $_REQUEST["elemperpage"]);
}
unset ($oUser);

// Current page
if (!isset($_REQUEST["page"]) || !is_numeric($_REQUEST["page"]) || $_REQUEST["page"] <= 0 || $_REQUEST["elemperpage"] == 0) {
	$_REQUEST["page"] = 1;
}

// Sort order
if ($_REQUEST["sortorder"] != "ASC") {
	$_REQUEST["sortorder"]  = "DESC";
}

// Initialization
$aFields = array();
$aFields["name"]	= array("field" => "name", "caption" => i18n("Name"), "type" => "base,sort,search");
$aFields["created"]	= array("field" => "created", "caption" => i18n("Created"), "type" => "base,sort");
$aFields["status"]	= array("field" => "status", "caption" => i18n("Status"), "type" => "base,sort");
$aFields["cronjob"]	= array("field" => "use_cronjob", "caption" => i18n("Use cronjob"), "type" => "base");
$sDelTitle		= i18n("Delete dispatch job");
$sDelDescr		= i18n("Do you really want to delete the following newsletter dispatch job:<br>");
$sSendTitle		= i18n("Run job");
$sSendDescr		= i18n("Do you really want to run the following job:<br>");
$oSelAuthor = new cHTMLSelectElement("selAuthor");

// This query is not possible using genericdb as the class id is always included...
$sSQL = "SELECT DISTINCT author, authorname FROM ".$cfg["tab"]["news_jobs"]." ORDER BY authorname";
$db->query($sSQL);

$aItems = array();
$bUserInTheList = false;
while ($db->next_record()) {
	if ($db->f("author") == $auth->auth["uid"]) {
		$bUserInTheList = true;
	}
	$aItems[] = array($db->f("author"), urldecode($db->f("authorname")));
}
$oSelAuthor->autoFill($aItems);
if (!$bUserInTheList) {
	$oOption = new cHTMLOptionElement($auth->auth["uname"], $auth->auth["uid"]);
	$oSelAuthor->addOptionElement($auth->auth["uid"], $oOption);
}
$oSelAuthor->setDefault($_REQUEST["selAuthor"]);

$oSelectItemsPerPage = new cHTMLSelectElement("elemperpage");
$oSelectItemsPerPage->autoFill(array(0 => i18n("-- All --"), 25 => 25, 50 => 50, 75 => 75, 100 => 100));
$oSelectItemsPerPage->setDefault($_REQUEST["elemperpage"]);

$oSelectSortBy = new cHTMLSelectElement("sortby");
foreach ($aFields as $sKey => $aData) {
	if (strpos($aData["type"], "sort") !== false) {
		if ($_REQUEST["sortby"] == "") {
			$_REQUEST["sortby"] = "created"; // Usually $sKey, but I'd like to get it sort by created as default...
		}
		$oOption = new cHTMLOptionElement($aData["caption"], $sKey);
		$oSelectSortBy->addOptionElement($sKey, $oOption);
	}
}	
$oSelectSortBy->setDefault($_REQUEST["sortby"]);

$oSelectSortOrder = new cHTMLSelectElement("sortorder");
$oSelectSortOrder->autoFill(array("ASC" => i18n("Ascending"), "DESC" => i18n("Descending")));
$oSelectSortOrder->setDefault($_REQUEST["sortorder"]);

$oTextboxFilter = new cHTMLTextbox("filter", $_REQUEST["filter"], 16);

$oSelectSearchIn = new cHTMLSelectElement("searchin");
$oOption = new cHTMLOptionElement(i18n("-- All fields --"), "--all--");
$oSelectSearchIn->addOptionElement("all", $oOption);

foreach ($aFields as $sKey => $aData) {
	if (strpos($aData["type"], "search") !== false) {
		$oOption = new cHTMLOptionElement($aData["caption"], $sKey);
		$oSelectSearchIn->addOptionElement($sKey, $oOption);
	}
}
$oSelectSearchIn->setDefault($_REQUEST["searchin"]);

$oSubmit = new cHTMLButton("submit", i18n("Apply"));
// DISPATCH
$sContent  = '<div style="border-bottom: 0px solid #B3B3B3; padding-left: 17px; background: '.$cfg['color']['table_dark'].';">'.chr(10);
$sContent .= '<form target="left_bottom" onsubmit="reloadLeftBottomAndTransportFormVars(this);"  id="dispatch_listoptionsform" name="dispatch_listoptionsform" method="get" action="">'.chr(10);
$sContent .= '   <input type="hidden" name="area" value="news_jobs">'.chr(10);
$sContent .= '   <input type="hidden" name="frame" value="2">'.chr(10);
$sContent .= '   <input type="hidden" name="contenido" value="'.$sess->id.'">'.chr(10);
$sContent .= '   <table>'.chr(10);
$sContent .= '      <tr>'.chr(10);
$sContent .= '         <td>'. i18n("Author").'</td>'.chr(10);
$sContent .= '         <td>'.$oSelAuthor->render().'</td>'.chr(10);
$sContent .= '      </tr>'.chr(10);
$sContent .= '      <tr>'.chr(10);
$sContent .= '         <td>'. i18n("Items / page").'</td>'.chr(10);
$sContent .= '         <td>'.$oSelectItemsPerPage->render().'</td>'.chr(10);
$sContent .= '      </tr>'.chr(10);
$sContent .= '      <tr>'.chr(10);
$sContent .= '         <td>'. i18n("Sort by").'</td>'.chr(10);
$sContent .= '         <td>'.$oSelectSortBy->render().'</td>'.chr(10);
$sContent .= '      </tr>'.chr(10);
$sContent .= '      <tr>'.chr(10);
$sContent .= '         <td>'. i18n("Sort order").'</td>'.chr(10);
$sContent .= '         <td>'.$oSelectSortOrder->render().'</td>'.chr(10);
$sContent .= '      </tr>'.chr(10);
$sContent .= '      <tr>'.chr(10);
$sContent .= '         <td>'. i18n("Search for").'</td>'.chr(10);
$sContent .= '         <td>'.$oTextboxFilter->render().'</td>'.chr(10);
$sContent .= '      </tr>'.chr(10);
$sContent .= '      <tr>'.chr(10);
$sContent .= '         <td>'. i18n("Search in").'</td>'.chr(10);
$sContent .= '         <td>'.$oSelectSearchIn->render().'</td>'.chr(10);
$sContent .= '      </tr>'.chr(10);
$sContent .= '      <tr>'.chr(10);
$sContent .= '         <td>&nbsp;</td>'.chr(10);
$sContent .= '         <td>'.$oSubmit->render().'</td>'.chr(10);
$sContent .= '      </tr>'.chr(10);
$sContent .= '    </table>'.chr(10);
$sContent .= '</form>'.chr(10);
$sContent .= '</div>'.chr(10);

// to template
$listOptionLinkDisp="listoptiondisp";
$oListOptionRowDisp = new cFoldingRow("dfa6cc00-0acf-11db-9cd8-0800200c9a66",i18n("List options"), $listOptionLinkDisp);
$oListOptionRowDisp->setContentData($sContent);
$tpl->set('s', 'LISTOPTIONLINKDISP', $listOptionLinkDisp);

// Request data
$oJobs->setWhere("idclient", $client);
$oJobs->setWhere("idlang", $lang);
$oJobs->setWhere("author", $_REQUEST["selAuthor"]);

if ($_REQUEST["filter"] != "") {
	if ($_REQUEST["searchin"] == "--all--" || $_REQUEST["searchin"] == "") {
		foreach ($aFields as $sKey => $aData) {
			if (strpos($aData["type"], "search") !== false) {
				$oJobs->setWhereGroup("filter", $aData["field"], $_REQUEST["filter"], "LIKE");
			}
		}
		$oJobs->setInnerGroupCondition("filter", "OR");
	} else {
		$oJobs->setWhere($_REQUEST["searchin"], $_REQUEST["filter"], "LIKE");
	}
}

if ($_REQUEST["elemperpage"] > 0) {
	$oJobs->query();
	$iItemCount = $oJobs->count(); // Getting item count without limit (for page function) - better idea anyone (performance)?
	
	$oJobs->setLimit($_REQUEST["elemperpage"] * ($_REQUEST["page"] - 1), $_REQUEST["elemperpage"]);
} else {
	$iItemCount = 0;
}

$oJobs->setOrder($_REQUEST["sortby"]." ".$_REQUEST["sortorder"]);
$oJobs->query();

// Output data
$oMenu			= new UI_Menu;
$iMenu			= 0;
$sDateFormat	= getEffectiveSetting("backend", "timeformat", "d.m.Y H:i");

// Prepare "send link" template
$sTplSend = '<a title="'.$sSendTitle.'" href="javascript://" onclick="showSendMsg(\'{ID}\',\'{NAME}\')"><img src="'.$cfg['path']['images'].'newsletter_16.gif" border="0" title="'.$sSendTitle.'" alt="'.$sSendTitle.'"></a>';

while ($oJob = $oJobs->next())
{
	$iMenu++;
	$iID	= $oJob->get("idnewsjob");
	$sName	= $oJob->get("name") . " (" . date($sDateFormat, strtotime($oJob->get("created"))) .")";

	$oLnk = new cHTMLLink;
	$oLnk->setMultiLink($area, "", $area, "");
	$oLnk->setCustom("idnewsjob", $iID);

	$oMenu->setImage($iMenu, "images/newsletter_16.gif");
	$oMenu->setTitle($iMenu, $sName);
	
	switch ($oJob->get("status"))
	{
		case 1:
			// Pending
			if ($oJob->get("cronjob") == 0)
			{
				// Standard job can be run if user has the right to do so
				if ($perm->have_perm_area_action($area, "news_job_run"))
				{
					$sLnkSend = str_replace('{ID}',   $iID, $sTplSend);
					$sLnkSend = str_replace('{NAME}', addslashes($sName), $sLnkSend);
				 
					$oMenu->setActions($iMenu, 'send', $sLnkSend);
				}
			} else if ($oJob->get("cronjob") == 1) {
				// It's a cronjob job - no manual sending, show it blue
				$oLnk->updateAttributes(array("style" => "color:#0000FF"));
			}
			
			if ($perm->have_perm_area_action($area, "news_job_delete")) {
				// Job may be deleted, if user has the right to do so
				$oMenu->setActions($iMenu, 'delete', '<a title="'.$sDelTitle.'" href="javascript://" onclick="showDelMsg('.$iID.',\''.addslashes($sName).'\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$sDelTitle.'" alt="'.$sDelTitle.'"></a>');
			}
			break;
		case 2:
			// Sending job
			if ($perm->have_perm_area_action($area, "news_job_run"))
			{
				// User may try to start sending, again - if he has the right to do so
				$sLnkSend = str_replace('{ID}',   $iID, $sTplSend);
				$sLnkSend = str_replace('{NAME}', addslashes($sName), $sLnkSend);
				 
				$oMenu->setActions($iMenu, 'send', $sLnkSend);
			}
			
			$oLnk->updateAttributes(array("style" => "color:#da8a00"));
			
			$sDelete = '<img src="'.$cfg['path']['images'].'delete_inact.gif" border="0" title="'.$sDelTitle.'" alt="'.$sDelTitle.'">';
			break;
		case 9:
			// Job finished, don't do anything
			$oLnk->updateAttributes(array("style" => "color:#808080"));
			
			if ($perm->have_perm_area_action($area, "news_job_delete")) {
				// You have the right, but you can't delete the job after sending
				$oMenu->setActions($iMenu, 'delete', '<img src="'.$cfg['path']['images'].'delete_inact.gif" border="0" title="'.$sDelTitle.'" alt="'.$sDelTitle.'">');
			}
			break;
	}
	
	$oMenu->setLink($iMenu, $oLnk);
}

$oPagerLink = new cHTMLLink;
$oPagerLink->setLink("main.php");
$oPagerLink->setCustom("selAuthor", $_REQUEST["selAuthor"]);
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

$pagerlDisp="pagerlinkdisp";
$tpl->set('s', 'PAGINGLINKDISP', $pagerlDisp);
$oPagerDisp     = new cObjectPager("89e76440-0ad0-11db-9cd8-0800200c9a66", $iItemCount, $_REQUEST["elemperpage"], $_REQUEST["page"], $oPagerLink, "page", $pagerlDisp);


##############################
# 3. Recipients
##############################
// Updating keys, if activated; all recipients of all clients!
if (getSystemProperty("newsletter", "updatekeys")) {
	$updatedrecipients = $recipients->updateKeys();
	$notis = $notification->returnNotification("info", sprintf(i18n("%d recipients, with no or incompatible key has been updated. Deactivate update function."),$updatedrecipients));
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

$sContent  =  '<div style="padding: 4px; padding-left: 17px; border-bottom: 0px solid black; background: '.$cfg['color']['table_dark'].';">'.chr(10);

// Create a link to add a recipient 
if ($perm->have_perm_area_action("recipients", "recipients_create"))
{
	$oLink = new cHTMLLink;
	$oLink->setMultiLink("recipients","","recipients","recipients_create");
	$oLink->setContent('<img style="padding-right: 4px;" src="'.$cfg["path"]["images"] . 'folder_new.gif" align="middle">'.i18n("Create recipient").'</a>');
	
	$sContent .= $oLink->render().'<br />'.chr(10);
}

// Create a link to import recipients
if ($perm->have_perm_area_action("recipients", "recipients_create"))
{
	$oLink = new cHTMLLink;
	$oLink->setMultiLink("recipients", "", "recipients_import", "recipients_import");
	$oLink->setContent('<img style="padding-right: 4px;" src="'.$cfg["path"]["images"] . 'importieren.gif" align="middle">'.i18n("Import recipients").'</a>');

	$sContent .= $oLink->render().'<br />'.chr(10);
}

// Create a link to purge subscribed but not confirmed recipients
if ($perm->have_perm_area_action($area, "recipients_delete"))
{
	$oClient = new cApiClient($client);
	$iTimeframe = $oClient->getProperty("newsletter", "purgetimeframe");
	if (!$iTimeframe || !is_numeric($iTimeframe)) 
	{
		$iTimeframe = 30;
	}
	if (isset($_REQUEST["purgetimeframe"]) && is_numeric($_REQUEST["purgetimeframe"]) && $_REQUEST["purgetimeframe"] > 0 && $_REQUEST["purgetimeframe"] != $iTimeframe) 
	{
		$iTimeframe = $_REQUEST["purgetimeframe"];
	}
	unset ($oClient);
	
	$oLink = new cHTMLLink;
	$oLink->setLink('javascript:showPurgeMsg("'.i18n('Purge recipients').'", "'.sprintf(i18n("Do you really want to remove recipients, that have not been confirmed since %s days and over?"), '"+purgetimeframe+"').'")');

	$oLink->setContent('<img style="padding-right: 4px;" src="'.$cfg["path"]["images"] . 'delete.gif" align="middle">'.i18n("Purge recipients").'</a>');

	$sContent .= $oLink->render();
}
$sContent .= '</div>'.chr(10);
$tpl->set('s', 'VALUE_PURGETIMEFRAME', $iTimeframe);

#########################
# 3.1 Actions folding row
#########################
// to template
$ActionLinkRec="actionrec";
$oListActionRowRec = new cFoldingRow("f0d7bf80-e73e-11d9-8cd6-0800200c9a66",i18n("Actions"), $ActionLinkRec);
$oListActionRowRec->setContentData($sContent);
$tpl->set('s', 'ACTIONLINKREC', $ActionLinkRec);

############################
# 3.2 Settings folding row
############################
// Get purge time settings
$oClient = new cApiClient($client);

$iTimeframe = $oClient->getProperty("newsletter", "purgetimeframe");
if (!$iTimeframe || !is_numeric($iTimeframe)) {
	$iTimeframe = 30;
}
if (isset($_REQUEST["purgetimeframe"]) && is_numeric($_REQUEST["purgetimeframe"]) && $_REQUEST["purgetimeframe"] > 0 && $_REQUEST["purgetimeframe"] != $iTimeframe) {
	$iTimeframe = $_REQUEST["purgetimeframe"];
	$oClient->setProperty("newsletter", "purgetimeframe", $iTimeframe);
}
unset ($oClient);

$oTxtTimeframe 	= new cHTMLTextbox("purgetimeframe", $iTimeframe, 5);
$oBtnSubmitOptions 	= new cHTMLButton("submit", i18n("Save"));

$sContent  = '<div style="border-bottom: 0px solid #B3B3B3; padding-left: 17px; background: '.$cfg['color']['table_dark'].';">'.chr(10);
$sContent .= '<form target="left_bottom" onsubmit="append_registered_parameters(this);" id="options" name="options" method="get" action="main.php?1">'.chr(10);
$sContent .= '   <input type="hidden" name="area" value="recipients">'.chr(10);
$sContent .= '   <input type="hidden" name="frame" value="2">'.chr(10);
$sContent .= '   <input type="hidden" name="contenido" value="'.$sess->id.'">'.chr(10);
$sContent .= '   <input type="hidden" name="elemperpage" value="'.$_REQUEST["elemperpage"].'">'.chr(10);
$sContent .= '   <input type="hidden" name="sortby" value="'.$_REQUEST["sortby"].'">'.chr(10);
$sContent .= '   <input type="hidden" name="sortorder" value="'.$_REQUEST["sortorder"].'">'.chr(10);
$sContent .= '   <input type="hidden" name="restrictgroup" value="'.$_REQUEST["restrictgroup"].'">'.chr(10);
$sContent .= '   <input type="hidden" name="filter" value="'.$_REQUEST["filter"].'">'.chr(10);
$sContent .= '   <input type="hidden" name="searchin" value="'.$_REQUEST["searchin"].'">'.chr(10);
$sContent .= '   <table>'.chr(10);
$sContent .= '      <tr>'.chr(10);
$sContent .= '         <td>'. i18n("Purge timeframe").':</td>'.chr(10);
$sContent .= '         <td>'.$oTxtTimeframe->render().'&nbsp;'.i18n("days").'</td>'.chr(10);
$sContent .= '      </tr>'.chr(10);
$sContent .= '      <tr>'.chr(10);
$sContent .= '         <td>&nbsp;</td>'.chr(10);
$sContent .= '         <td>'.$oBtnSubmitOptions->render().'</td>'.chr(10);
$sContent .= '      </tr>'.chr(10);
$sContent .= '    </table>'.chr(10);
$sContent .= '</form>'.chr(10);
$sContent .= '</div>'.chr(10);

// to template
$SettingsLinkRec="settingsrec";
$oSettingsRowRec = new cFoldingRow("5ddbe820-e6f1-11d9-8cd6-0800200c9a66",i18n("Settings"), $SettingsLinkRec);
$oSettingsRowRec->setContentData($sContent);
$tpl->set('s', 'SETTINGSLINKREC', $SettingsLinkRec);

###############################
# 3.3 List options folding row
###############################
$oSelItemsPerPage = new cHTMLSelectElement("elemperpage");
$oSelItemsPerPage->autoFill(array(0 => i18n("-- All --"), 25 => 25, 50 => 50, 75 => 75, 100 => 100));
$oSelItemsPerPage->setDefault($_REQUEST["elemperpage"]);

$oSelSortBy = new cHTMLSelectElement("sortby");
foreach ($aFields as $sKey => $aData) {
	if (strpos($aData["type"], "sort") !== false) {
		if ($_REQUEST["sortby"] == "") {
			$_REQUEST["sortby"] = $aData["field"];
		}
		$oOption = new cHTMLOptionElement($aData["caption"], $aData["field"]);
		$oSelSortBy->addOptionElement($aData["field"], $oOption);
	}
}	
$oSelSortBy->setDefault($_REQUEST["sortby"]);

$oSelSortOrder = new cHTMLSelectElement("sortorder");
$oSelSortOrder->autoFill(array("ASC" => i18n("Ascending"), "DESC" => i18n("Descending")));
$oSelSortOrder->setDefault($_REQUEST["sortorder"]);

$oSelRestrictGroup = new cHTMLSelectElement("restrictgroup");
$oOption = new cHTMLOptionElement(i18n("-- All groups --"), "--all--");
$oSelRestrictGroup->addOptionElement("all", $oOption);

// Fetch recipient groups
$oRGroups = new RecipientGroupCollection;
$oRGroups->setWhere("idclient", $client);
$oRGroups->setWhere("idlang", $lang);
$oRGroups->setOrder("defaultgroup DESC, groupname ASC");
$oRGroups->query();

$i = 1;
while ($oRGroup = $oRGroups->next()) {
	if ($oRGroup->get("defaultgroup") == 1) {
		$sGroupname = $oRGroup->get("groupname") . "*";
	} else {
		$sGroupname = $oRGroup->get("groupname");
	}
	$oOption = new cHTMLOptionElement($sGroupname, $oRGroup->get("idnewsgroup"));
	$oSelRestrictGroup->addOptionElement($i, $oOption);
    $i++;
}

$oSelRestrictGroup->setDefault($_REQUEST["restrictgroup"]);

$oTxtFilter = new cHTMLTextbox("filter", $_REQUEST["filter"], 16);

$oSelSearchIn = new cHTMLSelectElement("searchin");
$oOption = new cHTMLOptionElement(i18n("-- All fields --"), "--all--");
$oSelSearchIn->addOptionElement("all", $oOption);

foreach ($aFields as $sKey => $aData) {
	if (strpos($aData["type"], "search") !== false) {
		$oOption = new cHTMLOptionElement($aData["caption"], $aData["field"]);
		$oSelSearchIn->addOptionElement($aData["field"], $oOption);
	}
}
$oSelSearchIn->setDefault($_REQUEST["searchin"]);

$oBtnSubmitFilter = new cHTMLButton("submit", i18n("Apply"));


$sContent  = '<div style="border-bottom: 0px solid #B3B3B3; padding-left: 17px; background: '.$cfg['color']['table_dark'].';">'.chr(10);
$sContent .= '<form target="left_bottom" onsubmit="reloadLeftBottomAndTransportFormVars(this);"  id="recipients_listoptionsform" name="recipients_listoptionsform" method="get" action="">'.chr(10);
$sContent .= '   <input type="hidden" name="area" value="recipients">'.chr(10);
$sContent .= '   <input type="hidden" name="frame" value="2">'.chr(10);
$sContent .= '   <input type="hidden" name="contenido" value="'.$sess->id.'">'.chr(10);
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
$sContent .= '         <td>'. i18n("Show group").'</td>'.chr(10);
$sContent .= '         <td>'.$oSelRestrictGroup->render().'</td>'.chr(10);
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
$sContent .= '         <td>'.$oBtnSubmitFilter->render().'</td>'.chr(10);
$sContent .= '      </tr>'.chr(10);
$sContent .= '    </table>'.chr(10);
$sContent .= '</form>'.chr(10);
$sContent .= '</div>'.chr(10);

// Request data
$oRecipients = new RecipientCollection;
$oRecipients->setWhere("recipientcollection.idclient", $client);
$oRecipients->setWhere("recipientcollection.idlang", $lang);

if ($_REQUEST["restrictgroup"] != "--all--") {
	$oRecipients->link("RecipientGroupMemberCollection");
	$oRecipients->setWhere("RecipientGroupMemberCollection.idnewsgroup", $_REQUEST["restrictgroup"]);
}

if ($_REQUEST["filter"] != "") {
	if ($_REQUEST["searchin"] == "--all--" || $_REQUEST["searchin"] == "") {
		foreach ($aFields as $sKey => $aData) {
			if (strpos($aData["type"], "search") !== false) {
				$oRecipients->setWhereGroup("filter", "recipientcollection.".$aData["field"], $_REQUEST["filter"], "LIKE");
			}
		}
		$oRecipients->setInnerGroupCondition("filter", "OR");
	} else {
		$oRecipients->setWhere("recipientcollection.".$_REQUEST["searchin"], $_REQUEST["filter"], "LIKE");
	}
}

if ($_REQUEST["elemperpage"] > 0) {
	$oRecipients->query();
	$iItemCount = $oRecipients->count(); // Getting item count without limit (for page function) - better idea anyone (performance)?
	
	$oRecipients->setLimit($_REQUEST["elemperpage"] * ($_REQUEST["page"] - 1), $_REQUEST["elemperpage"]);
} else {
	$iItemCount = 0;
}

$oRecipients->setOrder("recipientcollection.".$_REQUEST["sortby"]." ".$_REQUEST["sortorder"]);
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

	$oMenu->setImage($iMenu, "images/users.gif");
	$oMenu->setTitle($iMenu, $sName);
	$oMenu->setLink($iMenu, $oLnk);	
	
	if ($perm->have_perm_area_action("recipients", "recipients_delete"))
	{
		$oMenu->setActions($iMenu, "delete", '<a title="'.$sMsgDelTitle.'" href="javascript://" onclick="showDelMsg('.$idnewsrcp.',\''.addslashes($sName).'\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$sMsgDelTitle.'" alt="'.$sMsgDelTitle.'"></a>');
	}
}

// to template
$ListOptionsLinkRec="listoptionsrec";
$oListOptionsRec = new cFoldingRow("5ddbe820-e6f1-11d9-8cd6-0800200c9a66",i18n("List options"), $ListOptionsLinkRec);
$oListOptionsRec->setContentData($sContent);
$tpl->set('s', 'LISTOPTIONLINKREC', $ListOptionsLinkRec);

#############
# 3.4 Paging
#############
$oPagerLink = new cHTMLLink;
$oPagerLink->setLink("main.php");
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

// to template
$PagingLinkRec="pagingrec";
$tpl->set('s', 'PAGINGLINKREC', $PagingLinkRec);
$oPagerRec = new cObjectPager("0ed6d632-6adf-4f09-a0c6-1e38ab60e301", $iItemCount, $_REQUEST["elemperpage"], $_REQUEST["page"], $oPagerLink, "page", $PagingLinkRec);

###################
# 4 Recipientgroup
###################
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

// Initialization
$aFields = array();
$aFields["name"]  		= array("field" => "groupname", "caption" => i18n("Name"), "type" => "base,sort,search");
$sDelTitle	= i18n("Delete recipient group");
$sDelDescr	= i18n("Do you really want to delete the following newsletter recipient group:<br>"); 

###################
# 4.1 Actions
###################
$sContent  =  '<div style="padding: 4px; padding-left: 17px; border-bottom: 0px solid black; background: '.$cfg['color']['table_dark'].';">'.chr(10);

// Create a link to add a group
if ($perm->have_perm_area_action("recipientgroups", "recipientgroup_create"))
{
	$oLnk = new cHTMLLink;
	$oLnk->setMultiLink("recipientgroups","","recipientgroups","recipientgroup_create");
	$oLnk->setContent('<img style="padding-right: 4px;" src="'.$cfg["path"]["images"] . 'folder_new.gif" align="middle">'.i18n("Create group").'</a>');
	$sContent .= $oLnk->render().'<br />'."\n";
}

$sContent .= '</div>'."\n";
$ActionLinkGroup="actiongroup";
$oListActionRowGroup = new cFoldingRow("f0d7bf80-e73e-11d9-8cd6-0800200c9a67",i18n("Actions"), $ActionLinkGroup);
$oListActionRowGroup->setContentData($sContent);
$tpl->set('s', 'ACTIONLINKGROUP', $ActionLinkGroup);

###################
# 4.2 List Options
###################
$oSelItemsPerPage = new cHTMLSelectElement("elemperpage");
$oSelItemsPerPage->autoFill(array(0 => i18n("-- All --"), 25 => 25, 50 => 50, 75 => 75, 100 => 100));
$oSelItemsPerPage->setDefault($_REQUEST["elemperpage"]);

$oSelSortBy = new cHTMLSelectElement("sortby");
foreach ($aFields as $sKey => $aData) {
	if (strpos($aData["type"], "sort") !== false) {
		if ($_REQUEST["sortby"] == "") {
			$_REQUEST["sortby"] = $aData["field"];
		}
		$oOption = new cHTMLOptionElement($aData["caption"], $aData["field"]);
		$oSelSortBy->addOptionElement($aData["field"], $oOption);
	}
}	
$oSelSortBy->setDefault($_REQUEST["sortby"]);

$oSelSortOrder = new cHTMLSelectElement("sortorder");
$oSelSortOrder->autoFill(array("ASC" => i18n("Ascending"), "DESC" => i18n("Descending")));
$oSelSortOrder->setDefault($_REQUEST["sortorder"]);

$oTxtFilter = new cHTMLTextbox("filter", $_REQUEST["filter"], 16);

$oSelSearchIn = new cHTMLSelectElement("searchin");
$oOption = new cHTMLOptionElement(i18n("-- All fields --"), "--all--");
$oSelSearchIn->addOptionElement("all", $oOption);

foreach ($aFields as $sKey => $aData) {
	if (strpos($aData["type"], "search") !== false) {
		$oOption = new cHTMLOptionElement($aData["caption"], $aData["field"]);
		$oSelSearchIn->addOptionElement($aData["field"], $oOption);
	}
}
$oSelSearchIn->setDefault($_REQUEST["searchin"]);

$oBtnSubmitFilter = new cHTMLButton("submit", i18n("Apply"));

$sContent  = '<div style="border-bottom: 0px solid #B3B3B3; padding-left: 17px; background: '.$cfg['color']['table_dark'].';">'.chr(10);
$sContent .= '<form target="left_bottom" onsubmit="reloadLeftBottomAndTransportFormVars(this);" id="groups_listoptionsform" name="groups_listoptionsform" method="get" action="">'.chr(10);
$sContent .= '   <input type="hidden" name="area" value="recipientgroups">'.chr(10);
$sContent .= '   <input type="hidden" name="frame" value="2">'.chr(10);
$sContent .= '   <input type="hidden" name="contenido" value="'.$sess->id.'">'.chr(10);
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
$sContent .= '         <td>'.$oBtnSubmitFilter->render().'</td>'.chr(10);
$sContent .= '      </tr>'.chr(10);
$sContent .= '    </table>'.chr(10);
$sContent .= '</form>'.chr(10);
$sContent .= '</div>'.chr(10);

// Request data
$oRcpGroups = new RecipientGroupCollection;
$oRcpGroups->setWhere("idclient", $client);
$oRcpGroups->setWhere("idlang", $lang);

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

if ($_REQUEST["elemperpage"] > 0) {
	$oRcpGroups->query();
	$iItemCount = $oRcpGroups->count(); // Getting item count without limit (for page function) - better idea anyone (performance)?
	
	$oRcpGroups->setLimit($_REQUEST["elemperpage"] * ($_REQUEST["page"] - 1), $_REQUEST["elemperpage"]);
} else {
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

// to template
$ListOptionsLinkGroup="listoptionsgroup";
$oListOptionRowGroup = new cFoldingRow("79efc1fc-111d-11dc-8314-0800200c9a66",i18n("List options"), $ListOptionsLinkGroup);
$oListOptionRowGroup->setContentData($sContent);
$tpl->set('s', 'LISTOPTIONLINKGROUP', $ListOptionsLinkGroup);

###################
# 4.3 Paging
###################
$oPagerLink = new cHTMLLink;
$oPagerLink->setLink("main.php");
$oPagerLink->setCustom("elemperpage", $_REQUEST["elemperpage"]);
$oPagerLink->setCustom("filter", $_REQUEST["filter"]);
$oPagerLink->setCustom("sortby", $_REQUEST["sortby"]);
$oPagerLink->setCustom("sortorder", $_REQUEST["sortorder"]);
$oPagerLink->setCustom("searchin", $_REQUEST["searchin"]);
$oPagerLink->setCustom("frame", $frame);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);

// to template
$PagingLinkGroup="paginggroup";
$tpl->set('s', 'PAGINGLINKGROUP', $PagingLinkGroup);
$oPagerGroup = new cObjectPager("1d27e488-1120-11dc-8314-0800200c9a66", $iItemCount, $_REQUEST["elemperpage"], $_REQUEST["page"], $oPagerLink, "page", $PagingLinkGroup);


#######################
# Container Newsletter
#######################
$containerNewsletterId='cont_newsletter';
$containerNewsletter  = '<div id="'.$containerNewsletterId.'">';
$containerNewsletter .= '<table border="0" cellspacing="0" cellpadding="0" width="100%">';
if ($perm->have_perm_area_action($area, "news_create")) {
    $containerNewsletter .= $oActionRow->render();
}
if ($perm->have_perm_area_action($area, "news_html_settings")) {
    $containerNewsletter .= $oSettingsRow->render();
}
$containerNewsletter .= $oListOptionRow->render();
$containerNewsletter .= $oPager->render();
$containerNewsletter .= '</table>';
$containerNewsletter .= '</div>';
$tpl->set('s', 'CNEWSLETTER', $containerNewsletter);
$tpl->set('s', 'ID_CNEWSLETTER', $containerNewsletterId);

#######################
# Container Dispatch
#######################
$containerDispatchId='cont_dispatch';
$containerDispatch .= '<div id="'.$containerDispatchId.'">';
$containerDispatch .= '<table border="0" cellspacing="0" cellpadding="0" width="100%">';
$containerDispatch .= $oListOptionRowDisp->render(); 
$containerDispatch .= $oPagerDisp->render();
$containerDispatch .= '</table>';
$containerDispatch .= '</div>';
$tpl->set('s', 'CDISPATCH', $containerDispatch);
$tpl->set('s', 'ID_CDISPATCH', $containerDispatchId);

#######################
# Container Recipients
#######################
$containerRecipientId='cont_recipients';
$containerRecipient = '<div id="'.$containerRecipientId.'">';
$containerRecipient .= '<table border="0" cellspacing="0" cellpadding="0" width="100%">';
if ($perm->have_perm_area_action($area, "recipients_delete")) {
    $containerRecipient .= $oListActionRowRec->render();
}
$containerRecipient .= $oSettingsRowRec->render();
$containerRecipient .= $oListOptionsRec->render();
$containerRecipient .= $oPagerRec->render();
$containerRecipient .= '</table>';
$containerRecipient .= '</div>';
$tpl->set('s', 'CRECIPIENTS', $containerRecipient);
$tpl->set('s', 'ID_CRECIPIENTS', $containerRecipientId);

###########################
# Container Recipientgroup
###########################
$containerRecipientGroupId='cont_recipientgroup';
$containerRecipientGroup  = '<div id="'.$containerRecipientGroupId.'">';
$containerRecipientGroup .= '<table border="0" cellspacing="0" cellpadding="0" width="100%">';
if ($perm->have_perm_area_action("recipientgroups", "recipientgroup_create")) {
    $containerRecipientGroup .= $oListActionRowGroup->render();
}
$containerRecipientGroup .= $oListOptionRowGroup->render();
$containerRecipientGroup .= $oPagerGroup->render();
$containerRecipientGroup .= '</table>';
$containerRecipientGroup .= '</div>';
$tpl->set('s', 'CRECIPIENTGROUP', $containerRecipientGroup);
$tpl->set('s', 'ID_CRECIPIENTGROUP', $containerRecipientGroupId);

$tpl->set('s', 'SESSID', $sess->id);
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['newsletter_left_top']);

?>