<?php
/**
 * This file contains the Left top pane.
 *
 * @package Plugin
 * @subpackage Newsletter
 * @author Bjoern Behrens
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $oTpl, $oDB;

$auth = cRegistry::getAuth();
$perm = cRegistry::getPerm();
$client = cRegistry::getClientId();
$lang = cRegistry::getLanguageId();
$cfg = cRegistry::getConfig();
$area = cRegistry::getArea();
$sess = cRegistry::getSession();


// ####################################
// Initialization
// ####################################
if (!is_object($oTpl)) {
    $oTpl = new cTemplate();
}
if (!is_object($oDB)) {
    $oDB = cRegistry::getDb(); // We have really to send a special SQL statement
                               // - we need a DB object
}

$oUser = new cApiUser($auth->auth["uid"]);
$oClient = new cApiClient($client);
$oClientLang = new cApiClientLanguage(false, $client, $lang);

// ####################################
// 0. BUTTONS
// ####################################
// Newsletter
$sId = 'img_newsletter';
$oTpl->set('s', 'INEWSLETTER', $sId);

$sButtonRow = '';
// News
if ($perm->have_perm_area_action('news')) {
    $sButtonRow .= '<a href="javascript://" onclick="toggleContainer(\'' . $sId . '\');reloadLeftBottomAndTransportFormVars(document.newsletter_listoptionsform);">';
    $sButtonRow .= '<img onmouseover="hoverEffect(\'' . $sId . '\', \'in\')" onmouseout="hoverEffect(\'' . $sId . '\', \'out\')" alt="' . i18n("Newsletter", 'newsletter') . '" title="' . i18n("Newsletter", 'newsletter') . '" name="' . $sId . '" id="' . $sId . '" src="' . $cfg["path"]["images"] . 'newsletter_on.gif">';
    $sButtonRow .= '</a>';
}

// Job dispatch
$sId = 'img_dispatch';
$oTpl->set('s', 'IDISPATCH', $sId);
if ($perm->have_perm_area_action('news_jobs')) {
    $sButtonRow .= '<a href="javascript://" onclick="toggleContainer(\'' . $sId . '\');reloadLeftBottomAndTransportFormVars(document.dispatch_listoptionsform);">';
    $sButtonRow .= '<img onmouseover="hoverEffect(\'' . $sId . '\', \'in\')" onmouseout="hoverEffect(\'' . $sId . '\', \'out\')" alt="' . i18n("Dispatch", 'newsletter') . '" title="' . i18n("Dispatch", 'newsletter') . '" name="' . $sId . '" id="' . $sId . '" src="' . $cfg["path"]["images"] . 'newsletter_dispatch_on.gif">';
    $sButtonRow .= '</a>';
}

// Recipients
$sId = 'img_recipient';
$oTpl->set('s', 'IRECIPIENTS', $sId);
if ($perm->have_perm_area_action('recipients')) {
    $sButtonRow .= '<a href="javascript://" onclick="toggleContainer(\'' . $sId . '\');reloadLeftBottomAndTransportFormVars(document.recipients_listoptionsform);">';
    $sButtonRow .= '<img onmouseover="hoverEffect(\'' . $sId . '\', \'in\')" onmouseout="hoverEffect(\'' . $sId . '\', \'out\')" alt="' . i18n("Recipients", 'newsletter') . '" title="' . i18n("Recipients", 'newsletter') . '" id="' . $sId . '" src="' . $cfg["path"]["images"] . 'newsletter_recipients_on.gif">';
    $sButtonRow .= '</a>';
}

// Recipient groups
$sId = 'img_recipientgroup';
$oTpl->set('s', 'IRECIPIENTGROUP', $sId);
if ($perm->have_perm_area_action('recipientgroups')) {
    $sButtonRow .= '<a href="javascript://" onclick="toggleContainer(\'' . $sId . '\');reloadLeftBottomAndTransportFormVars(groups_listoptionsform);">';
    $sButtonRow .= '<img onmouseover="hoverEffect(\'' . $sId . '\', \'in\')" onmouseout="hoverEffect(\'' . $sId . '\', \'out\')" alt="' . i18n("Recipient groups", 'newsletter') . '" title="' . i18n("Recipient groups", 'newsletter') . '" id="' . $sId . '" src="' . $cfg["path"]["images"] . 'newsletter_recipientgroups_on.gif">';
    $sButtonRow .= '</a>';
}

$sButtonRow = '
<div class="news_section news_section_buttons">
    ' . $sButtonRow . '
</div>
';
$oTpl->set('s', 'BUTTONROW', $sButtonRow);
unset($sButtonRow);

// ####################################
// 1. NEWSLETTER
// ####################################

// ####################################
// 1.1 Newsletter: Actions folding row
// ####################################
$sLink = "actionlink"; // ID for HTML element
$oActionsRow = new cGuiFoldingRow("28cf9b31-e6d7-4657-a9a7-db31478e7a5c", i18n("Actions", 'newsletter'), $sLink);
$oTpl->set('s', 'ACTIONLINK', $sLink);

if ($perm->have_perm_area_action("news", "news_create")) {
    // Create the link to add a newsletter
    $sContent = '<div class="news_section news_section_create">' . "\n";

    $oLink = new cHTMLLink();
    $oLink->setMultiLink("news", "", "news", "news_create");
    $oLink->setContent('<img src="' . $cfg["path"]["images"] . 'folder_new.gif" alt="">' . i18n("Create newsletter", 'newsletter'));

    $sContent .= $oLink->render() . '</div>' . "\n";
    $oActionsRow->setContentData($sContent);
} else {
    $oActionsRow->setContentData("");
}

// ####################################
// 1.2 Newsletter: Settings folding row
// ####################################
$sLink = "settingslink";
$oSettingsRow = new cGuiFoldingRow("d64baf0a-aea9-47b3-8490-54a00fce02b5", i18n("Settings", 'newsletter'), $sLink);
$oTpl->set('s', 'SETTINGSLINK', $sLink);

// HTML Newsletter: Template and newsletter category
// Note, that in PHP 5 it is not possible to have a truely working copy of an
// object
// so, we are filling two almost identical objects with the same data ("clone"
// may work, but is not available in PHP4 ...)
$oSelHTMLTemplateIDCat = new cHTMLSelectElement("selHTMLTemplateCat");

$oSelHTMLNewsletterIDCat = new cHTMLSelectElement("selHTMLNewsletterCat");

$oOptionTemplate = new cHTMLOptionElement("--" . i18n("Please select", 'newsletter') . "--", 0);
$oSelHTMLTemplateIDCat->addOptionElement(0, $oOptionTemplate);
$oOptionNewsletter = new cHTMLOptionElement("--" . i18n("Please select", 'newsletter') . "--", 0);
$oSelHTMLNewsletterIDCat->addOptionElement(0, $oOptionNewsletter);

$sSQL = "SELECT tblCat.idcat AS idcat, tblCatLang.name AS name, tblCatTree.level AS level, ";
$sSQL .= "tblCatLang.visible AS visible, tblCatLang.public AS public FROM ";
$sSQL .= $cfg["tab"]["cat"] . " AS tblCat, " . $cfg["tab"]["cat_lang"] . " AS tblCatLang, ";
$sSQL .= $cfg["tab"]["cat_tree"] . " AS tblCatTree ";
$sSQL .= "WHERE tblCat.idclient = '" . cSecurity::toInteger($client) . "' AND tblCatLang.idlang = '" . cSecurity::toInteger($lang) . "' AND ";
$sSQL .= "tblCatLang.idcat = tblCat.idcat AND tblCatTree.idcat = tblCat.idcat ";
$sSQL .= "ORDER BY tblCatTree.idtree";

$oDB->query($sSQL);

while ($oDB->nextRecord()) {
    $sSpaces = cHTMLOptionElement::indent($oDB->f("level"), 0);
    $oOptionTemplate = new cHTMLOptionElement($sSpaces . $oDB->f("name"), $oDB->f("idcat"));
    $oOptionNewsletter = new cHTMLOptionElement($sSpaces . $oDB->f("name"), $oDB->f("idcat"));
    if ($oDB->f("visible") == 0 || $oDB->f("public") == 0) {
        $oOptionTemplate->setStyle("color:#666666;");
        $oOptionNewsletter->setStyle("color:#666666;");
    }

    $oSelHTMLTemplateIDCat->addOptionElement($oDB->f("idcat"), $oOptionTemplate);
    $oSelHTMLNewsletterIDCat->addOptionElement($oDB->f("idcat"), $oOptionNewsletter);
}

// Get html template category
$iHTMLTemplateIDCat = (int) $oClientLang->getProperty("newsletter", "html_template_idcat");
if ($iHTMLTemplateIDCat < 0) {
    $iHTMLTemplateIDCat = 0;
}
$oSelHTMLTemplateIDCat->setDefault($iHTMLTemplateIDCat);

// Get html newsletter article category
$iHTMLNewsletterIDCat = (int) $oClientLang->getProperty("newsletter", "html_newsletter_idcat");
if ($iHTMLNewsletterIDCat < 0) {
    $iHTMLNewsletterIDCat = 0;
}
$oSelHTMLNewsletterIDCat->setDefault($iHTMLNewsletterIDCat);

// Global HTML newsletter option
$bHTMLNewsletter = false;
if ($iHTMLTemplateIDCat > 0 && $iHTMLNewsletterIDCat > 0 && $oClientLang->getProperty("newsletter", "html_newsletter") == "true") {
    // If necessary idcats are not specified or the option is disabled,
    // then HTML are not used
    $bHTMLNewsletter = true;
}
$oCkbHTMLNewsletter = new cHTMLCheckbox("ckbHTMLNewsletter", "enabled", "", $bHTMLNewsletter);

// Disable HTML options, if user has no rights
if (!$perm->have_perm_area_action($area, "news_html_settings")) {
    $oSelHTMLTemplateIDCat->setDisabled(true);
    $oSelHTMLNewsletterIDCat->setDisabled(true);
    $oCkbHTMLNewsletter->setDisabled(true);
}

// Destination for sending test newsletter
$oSelTestDestination = new cHTMLSelectElement("selTestDestination");

$oOption = new cHTMLOptionElement(i18n("My E-Mail address", 'newsletter'), 0);
$oSelTestDestination->addOptionElement(0, $oOption);

$oRcpGroups = new NewsletterRecipientGroupCollection();
$oRcpGroups->setWhere("idclient", (int) $client);
$oRcpGroups->setWhere("idlang", (int) $lang);
$oRcpGroups->setOrder("groupname");
$oRcpGroups->query();

$bTestTargetFound = false;
// Get client and language specific test destination. As lang is client
// specific, lang is sufficient
$iTestDestination = (int) $oUser->getProperty("newsletter", "test_idnewsgrp_lang" . $lang);
while ($oRcpGroup = $oRcpGroups->next()) {
    $iID = $oRcpGroup->get($oRcpGroup->getPrimaryKeyName());

    if ($iTestDestination == $iID) {
        $bTestTargetFound = true;
    }

    $oOption = new cHTMLOptionElement($oRcpGroup->get("groupname"), $iID);
    $oSelTestDestination->addOptionElement($iID, $oOption);
}
unset($oRcpGroups);

if (!$bTestTargetFound) {
    // Currently specified test target doesn't exist anymore, get back to "my
    // mail"
    $iTestDestination = 0;
}
if (!$perm->have_perm_area_action($area, "news_send_test")) {
    // No right to send somewhere else than to yourself
    $iTestDestination = 0;
    $oSelTestDestination->setDisabled(true);
}
$oSelTestDestination->setDefault($iTestDestination);

$oBtnSave = new cHTMLButton("submit", i18n("Save", 'newsletter'));

$sContent = '
<div class="news_section news_section_settings">
    <form target="left_bottom" onsubmit="append_registered_parameters(this);" id="htmlnewsletter" name="htmlnewsletter" method="get" action="main.php?1">
        <input type="hidden" name="area" value="' . $area . '">
        <input type="hidden" name="frame" value="2">
        <input type="hidden" name="contenido" value="' . $sess->id . '">
        <input type="hidden" name="elemperpage" value="' . $_REQUEST["elemperpage"] . '">
        <input type="hidden" name="sortby" value="' . $_REQUEST["sortby"] . '">
        <input type="hidden" name="sortorder" value="' . $_REQUEST["sortorder"] . '">
        <input type="hidden" name="restrictgroup" value="' . $_REQUEST["restrictgroup"] . '">
        <input type="hidden" name="filter" value="' . $_REQUEST["filter"] . '">
        <input type="hidden" name="searchin" value="' . $_REQUEST["searchin"] . '">
        <input type="hidden" name="action_html" value="save_newsletter_properties">
        <table>
            <tr>
                <td>' . $oCkbHTMLNewsletter->toHtml(false) . ' <label for="' . $oCkbHTMLNewsletter->getID() . '">' . i18n("Enable HTML newsletter", 'newsletter') . '</label></td>
            </tr>
            <tr>
                <td><label for="' . $oSelHTMLTemplateIDCat->getID() . '">' . i18n("HTML template category:", 'newsletter') . '</label><br> ' . $oSelHTMLTemplateIDCat->render() . '</td>
            </tr>
            <tr>
                <td><label for="' . $oSelHTMLNewsletterIDCat->getID() . '">' . i18n("HTML newsletter category:", 'newsletter') . '</label><br> ' . $oSelHTMLNewsletterIDCat->render() . '</td>
            </tr>
            <tr>
                <td><label for="' . $oSelTestDestination->getID() . '">' . i18n("Send test destination:", 'newsletter') . '</label><br>' . $oSelTestDestination->render() . '</td>
            </tr>
            <tr>
                <td>' . $oBtnSave->render() . '</td>
            </tr>
        </table>
    </form>
</div>
';
$oSettingsRow->setContentData($sContent);

// ####################################
// 1.3 Newsletter: List options folding row
// ####################################
// Items per Page
$iItemsPerPage = (int) $oUser->getProperty("itemsperpage", "news"); // Also used
                                                                   // in query
                                                                   // below
if ($iItemsPerPage == 0) {
    $iItemsPerPage = 25; // All can't be saved
}

$oSelItemsPerPage = new cHTMLSelectElement("elemperpage");
$oSelItemsPerPage->autoFill(array(
    0 => i18n("-- All --", 'newsletter'),
    25 => 25,
    50 => 50,
    75 => 75,
    100 => 100
));
$oSelItemsPerPage->setDefault($iItemsPerPage);

// Sort By
$oSelSortBy = new cHTMLSelectElement("sortby");
$oSelSortBy->autoFill(array(
    "name" => i18n("Name", 'newsletter')
));
$oSelSortBy->setDefault("name");

// Sort Order
$oSelSortOrder = new cHTMLSelectElement("sortorder");
$oSelSortOrder->autoFill(array(
    "ASC" => i18n("Ascending", 'newsletter'),
    "DESC" => i18n("Descending", 'newsletter')
));
$oSelSortOrder->setDefault("ASC");

// Search For
$oTextboxFilter = new cHTMLTextbox("filter", "", 16);
$oTextboxFilter->setClass("text_medium text");

// Search In
$oSelSearchIn = new cHTMLSelectElement("searchin");

$oOption = new cHTMLOptionElement(i18n("-- All fields --", 'newsletter'), "--all--");
$oSelSearchIn->addOptionElement("all", $oOption);
$oOption = new cHTMLOptionElement("Name", "name");
$oSelSearchIn->addOptionElement($sKey, $oOption);
$oSelSearchIn->setDefault("name");

// Apply button
$oBtnApply = new cHTMLButton("submit", i18n("Apply", 'newsletter'));

$sContent = '
<div class="news_section news_section_listoptions">
    <form target="left_bottom" onsubmit="reloadLeftBottomAndTransportFormVars(this);" id="newsletter_listoptionsform" name="newsletter_listoptionsform" method="get" action="">
        <input type="hidden" name="area" value="news">
        <input type="hidden" name="frame" value="2">
        <input type="hidden" name="contenido" value="' . $sess->id . '">
        <table>
            <tr>
                <td class="col_1"><label for="' . $oSelItemsPerPage->getID() . '">' . i18n("Items / page", 'newsletter') . '</label></td>
                <td class="col_2">' . $oSelItemsPerPage->render() . '</td>
            </tr>
            <tr>
                <td class="col_1"><label for="' . $oSelSortBy->getID() . '">' . i18n("Sort by", 'newsletter') . '</label></td>
                <td class="col_2">' . $oSelSortBy->render() . '</td>
            </tr>
            <tr>
                <td class="col_1"><label for="' . $oSelSortOrder->getID() . '">' . i18n("Sort order", 'newsletter') . '</label></td>
                <td class="col_2">' . $oSelSortOrder->render() . '</td>
            </tr>
            <tr>
                <td class="col_1"><label for="' . $oTextboxFilter->getID() . '">' . i18n("Search for", 'newsletter') . '</label></td>
                <td class="col_2">' . $oTextboxFilter->render() . '</td>
            </tr>
            <tr>
                <td class="col_1"><label for="' . $oSelSearchIn->getID() . '">' . i18n("Search in", 'newsletter') . '</label></td>
                <td class="col_2">' . $oSelSearchIn->render() . '</td>
            </tr>
            <tr>
                <td class="col_1">&nbsp;</td>
                <td class="col_2">' . $oBtnApply->render() . '</td>
            </tr>
        </table>
    </form>
</div>
';

// To template
$sLink = "listoption";
$oListOptionsRow = new cGuiFoldingRow("9d0968be-601d-44f8-a666-99d51c9c777d", i18n("List options", 'newsletter'), $sLink);
$oListOptionsRow->setContentData($sContent);
$oTpl->set('s', 'LISTOPTIONLINK', $sLink);

// ####################################
// 1.4 Newsletter: Paging folding row
// ####################################

// Add paging folding row (current page = 1) to get HTML paging container (later
// on updated by ...menu.php)
$oPagerLink = new cHTMLLink();
$oPagerLink->setLink("main.php");
$oPagerLink->setTargetFrame("left_bottom");
$oPagerLink->setCustom("elemperpage", $iItemsPerPage);
$oPagerLink->setCustom("filter", "");
// $oPagerLink->setCustom("restrictgroup", $_REQUEST["restrictgroup"]);
$oPagerLink->setCustom("sortby", "name");
$oPagerLink->setCustom("sortorder", "ASC");
$oPagerLink->setCustom("searchin", "name");
$oPagerLink->setCustom("frame", "2");
$oPagerLink->setCustom("area", "news");
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);

$sLink = "pagerlink";
$oTpl->set('s', 'PAGINGLINK', $sLink);
// $oPagerRow = new cObjectPager("0ed6d632-6adf-4f09-a0c6-1e38ab60e302",
// $iItemCount, $iItemsPerPage, 1, $oPagerLink, 'page', $sLink);
$oPagerRow = new cGuiObjectPager("0ed6d632-6adf-4f09-a0c6-1e38ab60e302", 0, 1, 1, $oPagerLink, 'page', $sLink);

// ####################################
// Newsletter: Container
// ####################################
$sContainerId = 'cont_newsletter';
$sContainer = '';
if ($perm->have_perm_area_action("news", "news_create")) {
    $sContainer .= $oActionsRow->render();
}
if ($perm->have_perm_area_action("news", "news_html_settings")) {
    $sContainer .= $oSettingsRow->render();
}
$sContainer .= $oListOptionsRow->render();
$sContainer .= $oPagerRow->render();

$sContainer = '
<div id="' . $sContainerId . '">
    <table class="borderless strong_headline" border="0" cellspacing="0" cellpadding="0" width="100%">
    ' . $sContainer . '
    </table>
</div>
';
$oTpl->set('s', 'CNEWSLETTER', $sContainer);
$oTpl->set('s', 'ID_CNEWSLETTER', $sContainerId);

// ####################################
// 2. Job dispatch
// ####################################
// Specify fields for search, sort and validation. Design makes enhancements
// using plugins possible (currently not implemented). If you are changing
// things here,
// remember to update include. ... menu.php, also.
// field: Field name in the db
// caption: Shown field name (-> user)
// base: Elements from core code (other type may be: "plugin")
// sort: Element can be used to be sorted by
// search: Element can be used to search in
$aFields = array();
$aFields["name"] = array(
    "field" => "name",
    "caption" => i18n("Name", 'newsletter'),
    "type" => "base,sort,search"
);
$aFields["created"] = array(
    "field" => "created",
    "caption" => i18n("Created", 'newsletter'),
    "type" => "base,sort"
);
$aFields["status"] = array(
    "field" => "status",
    "caption" => i18n("Status", 'newsletter'),
    "type" => "base,sort"
);
$aFields["cronjob"] = array(
    "field" => "use_cronjob",
    "caption" => i18n("Use cronjob", 'newsletter'),
    "type" => "base"
);

// ####################################
// 2.1 Job dispatch: List options folding row
// ####################################
// Author
$oSelAuthor = new cHTMLSelectElement("selAuthor");

// Get possible authors/users from available jobs
// For this query genericdb can't be used, as the class id is always included
// (distinct won't work)
$sSQL = "SELECT DISTINCT author, authorname FROM " . $cfg["tab"]["news_jobs"] . " ORDER BY authorname";
$oDB->query($sSQL);

$aItems = array();
$bUserInList = false;
while ($oDB->nextRecord()) {
    if ($oDB->f("author") == $auth->auth["uid"]) {
        $bUserInList = true;
    }
    $aItems[] = array(
        $oDB->f("author"),
        $oDB->f("authorname")
    );
}
$oSelAuthor->autoFill($aItems);

if (!$bUserInList) {
    // Current ser hasn't sent newsletter jobs, yet - add him to the list (it's
    // the default author)
    $oOption = new cHTMLOptionElement($auth->auth["uname"], $auth->auth["uid"]);
    $oSelAuthor->addOptionElement($auth->auth["uid"], $oOption);
}
$oSelAuthor->setDefault($auth->auth["uid"]);

// Items per page
$iItemsPerPage = (int) $oUser->getProperty("itemsperpage", "news_jobs"); // Used
                                                                        // also
                                                                        // below
                                                                        // in
                                                                        // query
if ($iItemsPerPage == 0) {
    $iItemsPerPage = 25; // All can't be saved
}

$oSelItemsPerPage = new cHTMLSelectElement("elemperpage");
$oSelItemsPerPage->autoFill(array(
    0 => i18n("-- All --", 'newsletter'),
    25 => 25,
    50 => 50,
    75 => 75,
    100 => 100
));
$oSelItemsPerPage->setDefault($iItemsPerPage);

// Sort by
$oSelSortBy = new cHTMLSelectElement("sortby");
foreach ($aFields as $sKey => $aData) {
    if (cString::findFirstPos($aData["type"], "sort") !== false) {
        $oOption = new cHTMLOptionElement($aData["caption"], $sKey);
        $oSelSortBy->addOptionElement($sKey, $oOption);
    }
}
$oSelSortBy->setDefault("created");

// Sort order
$oSelSortOrder = new cHTMLSelectElement("sortorder");
$oSelSortOrder->autoFill(array(
    "ASC" => i18n("Ascending", 'newsletter'),
    "DESC" => i18n("Descending", 'newsletter')
));
$oSelSortOrder->setDefault("DESC");

// Filter
$oTxtFilter = new cHTMLTextbox("filter", "", 16);
$oTxtFilter->setClass("text_medium text");

// Search in
$oSelSearchIn = new cHTMLSelectElement("searchin");
$oOption = new cHTMLOptionElement(i18n("-- All fields --", 'newsletter'), "--all--");
$oSelSearchIn->addOptionElement("all", $oOption);

foreach ($aFields as $sKey => $aData) {
    if (cString::findFirstPos($aData["type"], "search") !== false) {
        $oOption = new cHTMLOptionElement($aData["caption"], $sKey);
        $oSelSearchIn->addOptionElement($sKey, $oOption);
    }
}
$oSelSearchIn->setDefault("--all--");

$oBtnApply = new cHTMLButton("submit", i18n("Apply", 'newsletter'));

$sContent = '
<div class="news_section news_section_dispatch_listoptions">
    <form target="left_bottom" onsubmit="reloadLeftBottomAndTransportFormVars(this);" id="dispatch_listoptionsform" name="dispatch_listoptionsform" method="get" action="">
        <input type="hidden" name="area" value="news_jobs">
        <input type="hidden" name="frame" value="2">
        <input type="hidden" name="contenido" value="' . $sess->id . '">
        <table>
            <tr>
                <td class="col_1"><label for="' . $oSelAuthor->getID() . '">' . i18n("Author", 'newsletter') . '</label></td>
                <td class="col_2">' . $oSelAuthor->render() . '</td>
            </tr>
            <tr>
                <td class="col_1"><label for="' . $oSelItemsPerPage->getID() . '">' . i18n("Items / page", 'newsletter') . '</label></td>
                <td class="col_2">' . $oSelItemsPerPage->render() . '</td>
            </tr>
            <tr>
                <td class="col_1"><label for="' . $oSelSortBy->getID() . '">' . i18n("Sort by", 'newsletter') . '</label></td>
                <td class="col_2">' . $oSelSortBy->render() . '</td>
            </tr>
            <tr>
                <td class="col_1"><label for="' . $oSelSortOrder->getID() . '">' . i18n("Sort order", 'newsletter') . '</label></td>
                <td class="col_2">' . $oSelSortOrder->render() . '</td>
            </tr>
            <tr>
                <td class="col_1"><label for="' . $oTxtFilter->getID() . '">' . i18n("Search for", 'newsletter') . '</label></td>
                <td class="col_2">' . $oTxtFilter->render() . '</td>
            </tr>
            <tr>
                <td class="col_1"><label for="' . $oSelSearchIn->getID() . '">' . i18n("Search in", 'newsletter') . '</label></td>
                <td class="col_2">' . $oSelSearchIn->render() . '</td>
            </tr>
            <tr>
                <td class="col_1">&nbsp;</td>
                <td class="col_2">' . $oBtnApply->render() . '</td>
            </tr>
        </table>
    </form>
</div>
';

// To template
$sLink = "listoptiondisp";
$oListOptionsRow = new cGuiFoldingRow("dfa6cc00-0acf-11db-9cd8-0800200c9a66", i18n("List options", 'newsletter'), $sLink);
$oListOptionsRow->setContentData($sContent);
$oTpl->set('s', 'LISTOPTIONLINKDISP', $sLink);

// ####################################
// 2.2 Job dispatch: Paging folding row
// ####################################
$oPagerLink = new cHTMLLink();
$oPagerLink->setLink("main.php");
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setCustom("selAuthor", $auth->auth["uid"]);
$oPagerLink->setCustom("elemperpage", $iItemsPerPage);
$oPagerLink->setCustom("filter", "");
// $oPagerLink->setCustom("restrictgroup", $_REQUEST["restrictgroup"]);
$oPagerLink->setCustom("sortby", "created");
$oPagerLink->setCustom("sortorder", "DESC");
$oPagerLink->setCustom("searchin", "--all--");
$oPagerLink->setCustom("frame", "2"); // HIER!!! Stimmt das?
$oPagerLink->setCustom("area", "news_jobs");
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);

$sLink = "pagerlinkdisp";
$oTpl->set('s', 'PAGINGLINKDISP', $sLink);
// $oPagerRow = new cObjectPager("0ed6d632-6adf-4f09-a0c6-1e38ab60e303",
// $iItemCount, $iItemsPerPage, 1, $oPagerLink, "page", $sLink);
$oPagerRow = new cGuiObjectPager("0ed6d632-6adf-4f09-a0c6-1e38ab60e303", 0, 1, 1, $oPagerLink, 'page', $sLink);

// ####################################
// Job dispatch: Container
// ####################################
$sContainerId = 'cont_dispatch';
$sContainer = '<div id="' . $sContainerId . '">
    <table class="borderless strong_headline" border="0" cellspacing="0" cellpadding="0" width="100%">
        ' . $oListOptionsRow->render() . '
        ' . $oPagerRow->render() . '
    </table>
</div>
';
$oTpl->set('s', 'CDISPATCH', $sContainer);
$oTpl->set('s', 'ID_CDISPATCH', $sContainerId);

// ####################################
// 3. Recipients
// ####################################
// See comment at 2. Job dispatch
$aFields = array();
$aFields["name"] = array(
    "field" => "name",
    "caption" => i18n("Name", 'newsletter'),
    "type" => "base,sort,search"
);
$aFields["email"] = array(
    "field" => "email",
    "caption" => i18n("E-Mail", 'newsletter'),
    "type" => "base,sort,search"
);
$aFields["confirmed"] = array(
    "field" => "confirmed",
    "caption" => i18n("Confirmed", 'newsletter'),
    "type" => "base"
);
$aFields["deactivated"] = array(
    "field" => "deactivated",
    "caption" => i18n("Deactivated", 'newsletter'),
    "type" => "base"
);

// ####################################
// 3.1 Recipients: Actions folding row
// ####################################
$sContent = '';

// Create a link to add a recipient
if ($perm->have_perm_area_action("recipients", "recipients_create")) {
    $oLink = new cHTMLLink();
    $oLink->setMultiLink("recipients", "", "recipients", "recipients_create");
    $oLink->setContent('<img src="' . $cfg["path"]["images"] . 'folder_new.gif">' . i18n("Create recipient", 'newsletter') . '</a>');
    $sContent .= $oLink->render() . '<br>' . "\n";
}

// Create a link to import recipients
if ($perm->have_perm_area_action("recipients", "recipients_create")) {
    $oLink = new cHTMLLink();
    $oLink->setMultiLink("recipients", "", "recipients_import", "recipients_import");
    $oLink->setContent('<img src="' . $cfg["path"]["images"] . 'importieren.gif">' . i18n("Import recipients", 'newsletter') . '</a>');
    $sContent .= $oLink->render() . '<br>' . "\n";
}

$iTimeframe = (int) $oClient->getProperty("newsletter", "purgetimeframe");
if ($iTimeframe <= 0) {
    $iTimeframe = 30;
}

// Create a link to purge subscribed but not confirmed recipients
if ($perm->have_perm_area_action("recipients", "recipients_delete")) {
    $oLink = new cHTMLLink();
    $oLink->setLink("javascript:showPurgeMsg('" . i18n('Purge recipients', 'newsletter') . "', '" . sprintf(i18n('Do you really want to remove recipients, that have not been confirmed since %s days and over?', 'newsletter'), $iTimeframe) . "')");
    $oLink->setContent('<img src="' . $cfg["path"]["images"] . 'delete.gif">' . i18n("Purge recipients", 'newsletter') . '</a>');
    $sContent .= $oLink->render();
}

$sContent = '
<div class="news_section news_section_recipients_actions">
' . $sContent . '
</div>
';

$oTpl->set('s', 'VALUE_PURGETIMEFRAME', $iTimeframe);

// To template
$sLink = "actionrec";
$oListActionsRow = new cGuiFoldingRow("f0d7bf80-e73e-11d9-8cd6-0800200c9a66", i18n("Actions", 'newsletter'), $sLink);
$oListActionsRow->setContentData($sContent);
$oTpl->set('s', 'ACTIONLINKREC', $sLink);

// ####################################
// 3.2 Recipients: Settings folding row
// ####################################
$oTxtTimeframe = new cHTMLTextbox("txtPurgeTimeframe", $iTimeframe, 5);
$oBtnSave = new cHTMLButton("submit", i18n("Save", 'newsletter'));

$sContent = '
<div class="news_section news_section_recipients_settings">
    <form target="left_bottom" onsubmit="purgetimeframe = document.options.txtPurgeTimeframe.value; append_registered_parameters(this);" id="options" name="options" method="get" action="main.php?1">
        <input type="hidden" name="area" value="recipients">
        <input type="hidden" name="frame" value="2">
        <input type="hidden" name="contenido" value="' . $sess->id . '">
        <input type="hidden" name="elemperpage" value="' . $_REQUEST["elemperpage"] . '">
        <input type="hidden" name="sortby" value="' . $_REQUEST["sortby"] . '">
        <input type="hidden" name="sortorder" value="' . $_REQUEST["sortorder"] . '">
        <input type="hidden" name="restrictgroup" value="' . $_REQUEST["restrictgroup"] . '">
        <input type="hidden" name="filter" value="' . $_REQUEST["filter"] . '">
        <input type="hidden" name="searchin" value="' . $_REQUEST["searchin"] . '">
        <table>
            <tr>
                <td class="col_1"><label for="' . $oTxtTimeframe->getID() . '">' . i18n("Purge timeframe", 'newsletter') . ':</label></td>
                <td class="col_2">' . $oTxtTimeframe->render() . '&nbsp;' . i18n("days", 'newsletter') . '</td>
            </tr>
            <tr>
                <td class="col_1">&nbsp;</td>
                <td class="col_2">' . $oBtnSave->render() . '</td>
            </tr>
        </table>
    </form>
</div>
';

// To template
$sLink = "settingsrec";
$oSettingsRow = new cGuiFoldingRow("5ddbe820-e6f1-11d9-8cd6-0800200c9a69", i18n("Settings", 'newsletter'), $sLink);
$oSettingsRow->setContentData($sContent);
$oTpl->set('s', 'SETTINGSLINKREC', $sLink);

// ####################################
// 3.3 Recipients: List options folding row
// ####################################
$iItemsPerPage = (int) $oUser->getProperty("itemsperpage", "recipients");
if ($iItemsPerPage == 0) {
    $iItemsPerPage = 25; // All can't be saved
}

$oSelItemsPerPage = new cHTMLSelectElement("elemperpage");
$oSelItemsPerPage->autoFill(array(
    0 => i18n("-- All --", 'newsletter'),
    25 => 25,
    50 => 50,
    75 => 75,
    100 => 100
));
$oSelItemsPerPage->setDefault($iItemsPerPage);

$oSelSortBy = new cHTMLSelectElement("sortby");
foreach ($aFields as $sKey => $aData) {
    if (cString::findFirstPos($aData["type"], "sort") !== false) {
        $oOption = new cHTMLOptionElement($aData["caption"], $aData["field"]);
        $oSelSortBy->addOptionElement($aData["field"], $oOption);
    }
}
$oSelSortBy->setDefault("name");

$oSelSortOrder = new cHTMLSelectElement("sortorder");
$oSelSortOrder->autoFill(array(
    "ASC" => i18n("Ascending", 'newsletter'),
    "DESC" => i18n("Descending", 'newsletter')
));
$oSelSortOrder->setDefault("ASC");

$oSelRestrictGroup = new cHTMLSelectElement("restrictgroup");
$oOption = new cHTMLOptionElement(i18n("-- All groups --", 'newsletter'), "--all--");
$oSelRestrictGroup->addOptionElement("all", $oOption);

// Fetch recipient groups
$oRGroups = new NewsletterRecipientGroupCollection();
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

$oSelRestrictGroup->setDefault("--all--");

$oTxtFilter = new cHTMLTextbox("filter", "", 16);
$oTxtFilter->setClass("text_medium text");

$oSelSearchIn = new cHTMLSelectElement("searchin");
$oOption = new cHTMLOptionElement(i18n("-- All fields --", 'newsletter'), "--all--");
$oSelSearchIn->addOptionElement("all", $oOption);

foreach ($aFields as $sKey => $aData) {
    if (cString::findFirstPos($aData["type"], "search") !== false) {
        $oOption = new cHTMLOptionElement($aData["caption"], $aData["field"]);
        $oSelSearchIn->addOptionElement($aData["field"], $oOption);
    }
}
$oSelSearchIn->setDefault("--all--");

$oBtnApply = new cHTMLButton("submit", i18n("Apply", 'newsletter'));

$sContent = '
<div class="news_section news_section_recipients_listoptions">
    <form target="left_bottom" onsubmit="reloadLeftBottomAndTransportFormVars(this);" id="recipients_listoptionsform" name="recipients_listoptionsform" method="get" action="">
        <input type="hidden" name="area" value="recipients">
        <input type="hidden" name="frame" value="2">
        <input type="hidden" name="contenido" value="' . $sess->id . '">
        <table>
            <tr>
                <td class="col_1"><label for="' . $oSelItemsPerPage->getID() . '">' . i18n("Items / page", 'newsletter') . '</label></td>
                <td class="col_2">' . $oSelItemsPerPage->render() . '</td>
            </tr>
            <tr>
                <td class="col_1"><label for="' . $oSelSortBy->getID() . '">' . i18n("Sort by", 'newsletter') . '</label></td>
                <td class="col_2">' . $oSelSortBy->render() . '</td>
            </tr>
            <tr>
                <td class="col_1"><label for="' . $oSelSortOrder->getID() . '">' . i18n("Sort order", 'newsletter') . '</label></td>
                <td class="col_2">' . $oSelSortOrder->render() . '</td>
            </tr>
            <tr>
                <td class="col_1"><label for="' . $oSelRestrictGroup->getID() . '">' . i18n("Show group", 'newsletter') . '</label></td>
                <td class="col_2">' . $oSelRestrictGroup->render() . '</td>
            </tr>
            <tr>
                <td class="col_1"><label for="' . $oTxtFilter->getID() . '">' . i18n("Search for", 'newsletter') . '</label></td>
                <td class="col_2">' . $oTxtFilter->render() . '</td>
            </tr>
            <tr>
                <td class="col_1"><label for="' . $oSelSearchIn->getID() . '">' . i18n("Search in", 'newsletter') . '</label></td>
                <td class="col_2">' . $oSelSearchIn->render() . '</td>
            </tr>
            <tr>
                <td class="col_1">&nbsp;</td>
                <td class="col_2">' . $oBtnApply->render() . '</td>
            </tr>
        </table>
    </form>
</div>
';

// To template
$sLink = "listoptionsrec";
$oListOptionsRow = new cGuiFoldingRow("5ddbe820-e6f1-11d9-8cd6-0800200c9a66", i18n("List options", 'newsletter'), $sLink);
$oListOptionsRow->setContentData($sContent);
$oTpl->set('s', 'LISTOPTIONLINKREC', $sLink);

// ####################################
// 3.4 Recipients: Paging
// ####################################
$oPagerLink = new cHTMLLink();
$oPagerLink->setLink("main.php");
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setCustom("elemperpage", $iItemsPerPage);
$oPagerLink->setCustom("filter", "");
$oPagerLink->setCustom("restrictgroup", "--all--");
$oPagerLink->setCustom("sortby", "name");
$oPagerLink->setCustom("sortorder", "ASC");
$oPagerLink->setCustom("searchin", "--all--");
$oPagerLink->setCustom("frame", "2");
$oPagerLink->setCustom("area", "recipients");
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);

// To template
$sLink = "pagingrec";
$oTpl->set('s', 'PAGINGLINKREC', $sLink);
// $oPagerRow = new cObjectPager("0ed6d632-6adf-4f09-a0c6-1e38ab60e304",
// $iItemCount, $iItemsPerPage, 1, $oPagerLink, "page", $sLink);
$oPagerRow = new cGuiObjectPager("0ed6d632-6adf-4f09-a0c6-1e38ab60e304", 0, 1, 1, $oPagerLink, 'page', $sLink);

// ####################################
// Recipients: Container
// ####################################
$sContainerId = 'cont_recipients';
$sContainer = '';
if ($perm->have_perm_area_action('recipients', "recipients_delete") || $perm->have_perm_area_action("recipients", "recipients_create")) {
    $sContainer .= $oListActionsRow->render();
}
$sContainer .= $oSettingsRow->render();
$sContainer .= $oListOptionsRow->render();
$sContainer .= $oPagerRow->render();

$sContainer = '
<div id="' . $sContainerId . '">
<table class="borderless strong_headline" border="0" cellspacing="0" cellpadding="0" width="100%">
' . $sContainer . '
</table>
</div>
';
$oTpl->set('s', 'CRECIPIENTS', $sContainer);
$oTpl->set('s', 'ID_CRECIPIENTS', $sContainerId);

// ####################################
// 4 Recipient groups
// ####################################
// See comment at 2. Job dispatch
$aFields = array();
$aFields["name"] = array(
    "field" => "groupname",
    "caption" => i18n("Name", 'newsletter'),
    "type" => "base,sort,search"
);

// ####################################
// 4.1 Recipient groups: Actions
// ####################################
$sContent = '';

// Create a link to add a group
if ($perm->have_perm_area_action("recipientgroups", "recipientgroup_create")) {
    $oLnk = new cHTMLLink();
    $oLnk->setMultiLink("recipientgroups", "", "recipientgroups", "recipientgroup_create");
    $oLnk->setContent('<img src="' . $cfg["path"]["images"] . 'folder_new.gif" align="middle">' . i18n("Create group", 'newsletter') . '</a>');
    $sContent .= $oLnk->render() . '<br>' . "\n";
}

$sContent = '
<div class="news_section news_section_recipients_groups">
' . $sContent . '
</div>
';

$sLink = "actiongroup";
$oListActionsRow = new cGuiFoldingRow("f0d7bf80-e73e-11d9-8cd6-0800200c9a67", i18n("Actions", 'newsletter'), $sLink);
$oListActionsRow->setContentData($sContent);
$oTpl->set('s', 'ACTIONLINKGROUP', $sLink);

// ####################################
// 4.2 Recipient groups: List Options
// ####################################
$iItemsPerPage = (int) $oUser->getProperty("itemsperpage", "recipientgroups");
if ($iItemsPerPage == 0) {
    $iItemsPerPage = 25; // All can't be saved
}

$oSelItemsPerPage = new cHTMLSelectElement("elemperpage");
$oSelItemsPerPage->autoFill(array(
    0 => i18n("-- All --", 'newsletter'),
    25 => 25,
    50 => 50,
    75 => 75,
    100 => 100
));
$oSelItemsPerPage->setDefault($iItemsPerPage);

$oSelSortBy = new cHTMLSelectElement("sortby");
foreach ($aFields as $sKey => $aData) {
    if (cString::findFirstPos($aData["type"], "sort") !== false) {
        $oOption = new cHTMLOptionElement($aData["caption"], $aData["field"]);
        $oSelSortBy->addOptionElement($aData["field"], $oOption);
    }
}
$oSelSortBy->setDefault("name");

$oSelSortOrder = new cHTMLSelectElement("sortorder");
$oSelSortOrder->autoFill(array(
    "ASC" => i18n("Ascending", 'newsletter'),
    "DESC" => i18n("Descending", 'newsletter')
));
$oSelSortOrder->setDefault("ASC");

$oTxtFilter = new cHTMLTextbox("filter", "", 16);
$oTxtFilter->setClass("text_medium text");

$oSelSearchIn = new cHTMLSelectElement("searchin");
$oOption = new cHTMLOptionElement(i18n("-- All fields --", 'newsletter'), "--all--");
$oSelSearchIn->addOptionElement("all", $oOption);

foreach ($aFields as $sKey => $aData) {
    if (cString::findFirstPos($aData["type"], "search") !== false) {
        $oOption = new cHTMLOptionElement($aData["caption"], $aData["field"]);
        $oSelSearchIn->addOptionElement($aData["field"], $oOption);
    }
}
$oSelSearchIn->setDefault("--all--");

$oBtnApply = new cHTMLButton("submit", i18n("Apply", 'newsletter'));

$sContent = '
<div class="news_section news_section_recipients_groups_listactions">
    <form target="left_bottom" onsubmit="reloadLeftBottomAndTransportFormVars(this);" id="groups_listoptionsform" name="groups_listoptionsform" method="get" action="">
        <input type="hidden" name="area" value="recipientgroups">
        <input type="hidden" name="frame" value="2">
        <input type="hidden" name="contenido" value="' . $sess->id . '">
        <table>
            <tr>
                <td class="col_1"><label for="' . $oSelItemsPerPage->getID() . '">' . i18n("Items / page", 'newsletter') . '</label></td>
                <td class="col_2">' . $oSelItemsPerPage->render() . '</td>
            </tr>
            <tr>
                <td class="col_1"><label for="' . $oSelSortBy->getID() . '">' . i18n("Sort by", 'newsletter') . '</label></td>
                <td class="col_2">' . $oSelSortBy->render() . '</td>
            </tr>
            <tr>
                <td class="col_1"><label for="' . $oSelSortOrder->getID() . '">' . i18n("Sort order", 'newsletter') . '</label></td>
                <td class="col_2">' . $oSelSortOrder->render() . '</td>
            </tr>
            <tr>
                <td class="col_1"><label for="' . $oTxtFilter->getID() . '">' . i18n("Search for", 'newsletter') . '</label></td>
                <td class="col_2">' . $oTxtFilter->render() . '</td>
            </tr>
            <tr>
                <td class="col_1"><label for="' . $oSelSearchIn->getID() . '">' . i18n("Search in", 'newsletter') . '</label></td>
                <td class="col_2">' . $oSelSearchIn->render() . '</td>
            </tr>
            <tr>
                <td class="col_1">&nbsp;</td>
                <td class="col_2">' . $oBtnApply->render() . '</td>
            </tr>
        </table>
    </form>
</div>
';

// To template
$sLink = "listoptionsgroup";
$oListOptionsRow = new cGuiFoldingRow("79efc1fc-111d-11dc-8314-0800200c9a66", i18n("List options", 'newsletter'), $sLink);
$oListOptionsRow->setContentData($sContent);
$oTpl->set('s', 'LISTOPTIONLINKGROUP', $sLink);

// ####################################
// 4.3 Recipient groups: Paging
// ####################################
$oPagerLink = new cHTMLLink();
$oPagerLink->setLink("main.php");
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setCustom("elemperpage", $iItemsPerPage);
$oPagerLink->setCustom("filter", "");
$oPagerLink->setCustom("sortby", "name");
$oPagerLink->setCustom("sortorder", "ASC");
$oPagerLink->setCustom("searchin", "--all--");
$oPagerLink->setCustom("frame", "2");
$oPagerLink->setCustom("area", "recipientgroups");
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);

// To template
$sLink = "paginggroup";
$oTpl->set('s', 'PAGINGLINKGROUP', $sLink);
// $oPagerRow = new cObjectPager("0ed6d632-6adf-4f09-a0c6-1e38ab60e305",
// $iItemCount, $iItemsPerPage, 1, $oPagerLink, "page", $sLink);
$oPagerRow = new cGuiObjectPager("0ed6d632-6adf-4f09-a0c6-1e38ab60e305", 0, 1, 1, $oPagerLink, 'page', $sLink);

// ####################################
// Recipient Groups: Container
// ####################################
$sContainerId = 'cont_recipientgroup';
$sContainer = '';
if ($perm->have_perm_area_action("recipientgroups", "recipientgroup_create")) {
    $sContainer .= $oListActionsRow->render();
}
$sContainer .= $oListOptionsRow->render();
$sContainer .= $oPagerRow->render();

$sContainer = '<div id="' . $sContainerId . '">
<table class="borderless strong_headline" border="0" cellspacing="0" cellpadding="0" width="100%">
' . $sContainer . '
</table>
</div>
';
$oTpl->set('s', 'CRECIPIENTGROUP', $sContainer);
$oTpl->set('s', 'ID_CRECIPIENTGROUP', $sContainerId);

$oTpl->generate(cRegistry::getBackendPath() . $cfg['path']['plugins'] . 'newsletter/templates/standard/template.newsletter_left_top.html');
