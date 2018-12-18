<?php
/**
 * This file contains the Frontend user list.
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

// ################################
// Initialization
// ################################
$oPage = new cGuiPage("newsletter_menu", "newsletter");
$oUser = new cApiUser($auth->auth["uid"]);
$oClientLang = new cApiClientLanguage(false, $client, $lang);

// Get idCatArt to check, if we may send a test newsletter
$lIDCatArt = (int) $oClientLang->getProperty("newsletter", "idcatart");

// Specify fields for search, sort and validation. Design makes enhancements
// using plugins possible (currently not implemented). If you are changing
// things here,
// remember to update include.newsletter_left_top.php, also.
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

// ################################
// Store settings/Get basic data
// ################################
if (isset($_REQUEST['action_html']) && $_REQUEST['action_html'] == 'save_newsletter_properties' && $perm->have_perm_area_action($area, "news_html_settings")) {
    // Storing settings
    if (isset($_REQUEST["ckbHTMLNewsletter"])) {
        $oClientLang->setProperty("newsletter", "html_newsletter", "true");
    } else {
        $oClientLang->setProperty("newsletter", "html_newsletter", "false");
    }
    $oClientLang->setProperty("newsletter", "html_template_idcat", (int) $_REQUEST["selHTMLTemplateCat"]);
    $oClientLang->setProperty("newsletter", "html_newsletter_idcat", (int) $_REQUEST["selHTMLNewsletterCat"]);
    $oUser->setProperty("newsletter", "test_idnewsgrp_lang" . $lang, (int) $_REQUEST["selTestDestination"]);
} else {
    // No settings to be stored, get current settings (language sepcific, as
    // lang is client specific, lang is sufficient)
    $_REQUEST["selTestDestination"] = (int) $oUser->getProperty("newsletter", "test_idnewsgrp_lang" . $lang);
}
// Default value: Current user mail
$sSendTestTarget = $oUser->get("realname") . " (" . $oUser->get("email") . ")";

// ################################
// Check external input
// ################################
// Items per page (value stored per area in user property)
if (!isset($_REQUEST["elemperpage"]) || !is_numeric($_REQUEST["elemperpage"]) || $_REQUEST["elemperpage"] < 0) {
    $_REQUEST["elemperpage"] = $oUser->getProperty("itemsperpage", $area);
}
if (!is_numeric($_REQUEST["elemperpage"])) {
    // This is the case, if the user property has never been set (first time
    // user)
    $_REQUEST["elemperpage"] = 25;
}
if ($_REQUEST["elemperpage"] > 0) {
    // -- All -- will not be stored, as it may be impossible to change this back
    // to something more useful
    $oUser->setProperty("itemsperpage", $area, $_REQUEST["elemperpage"]);
}
$_REQUEST["page"] = (int) $_REQUEST["page"];
if ($_REQUEST["page"] <= 0 || $_REQUEST["elemperpage"] == 0) {
    $_REQUEST["page"] = 1;
}
// Sort order
if ($_REQUEST["sortorder"] != "DESC") {
    $_REQUEST["sortorder"] = "ASC";
}

// Check sort by and search in criteria
$bSortByFound = false;
$bSearchInFound = false;
foreach ($aFields as $sKey => $aData) {
    if ($aData["field"] == $_REQUEST["sortby"] && cString::findFirstPos($aData["type"], "sort") !== false) {
        $bSortByFound = true;
    }
    if ($aData["field"] == $_REQUEST["searchin"] && cString::findFirstPos($aData["type"], "search") !== false) {
        $bSearchInFound = true;
    }
}

if (!$bSortByFound) {
    $_REQUEST["sortby"] = "name"; // Default sort by field, possible values see
                                  // above
}
if (!$bSearchInFound) {
    $_REQUEST["searchin"] = "--all--";
}

// Free memory
unset($oClientLang, $oUser);

// ################################
// Get data
// ################################
$oNewsletters = new NewsletterCollection();
$oNewsletters->setWhere("idclient", $client);
$oNewsletters->setWhere("idlang", $lang);

if ($_REQUEST["filter"] != "") {
    if ($_REQUEST["searchin"] == "--all--" || $_REQUEST["searchin"] == "") {
        foreach ($aFields as $sKey => $aData) {
            if (cString::findFirstPos($aData["type"], "search") !== false) {
                $oNewsletters->setWhereGroup("filter", $aData["field"], $_REQUEST["filter"], "LIKE");
            }
        }
        $oNewsletters->setInnerGroupCondition("filter", "OR");
    } else {
        $oNewsletters->setWhere($_REQUEST["searchin"], $_REQUEST["filter"], "LIKE");
    }
}

if ($_REQUEST["elemperpage"] > 0) {
    // Getting item count without limit (for page function) - better idea anyone
    // (performance)?
    $oNewsletters->query();
    $iItemCount = $oNewsletters->count();

    if ($_REQUEST["elemperpage"] * ($_REQUEST["page"]) >= $iItemCount + $_REQUEST["elemperpage"] && $_REQUEST["page"] != 1) {
        $_REQUEST["page"]--;
    }

    $oNewsletters->setLimit($_REQUEST["elemperpage"] * ($_REQUEST["page"] - 1), $_REQUEST["elemperpage"]);
} else {
    $iItemCount = 0;
}

$oNewsletters->setOrder("welcome DESC, " . $_REQUEST["sortby"] . " " . $_REQUEST["sortorder"]);
$oNewsletters->query();

// Output data
$oMenu = new cGuiMenu();
$iMenu = 0;

// Store messages for repeated use (speeds performance, as i18n translation is
// only needed once)
$aMsg = array();
$aMsg["DelTitle"] = i18n("Delete newsletter", 'newsletter');
$aMsg["DelDescr"] = i18n("Do you really want to delete the following newsletter:<br>", 'newsletter');
$aMsg["SendTestTitle"] = i18n("Send test newsletter", 'newsletter');
$aMsg["SendTestTitleOff"] = i18n("Send test newsletter (disabled, check newsletter sender e-mail address and handler article selection)", 'newsletter');
$aMsg["AddJobTitle"] = i18n("Add newsletter dispatch job", 'newsletter');
$aMsg["AddJobTitleOff"] = i18n("Add newsletter dispatch job (disabled, check newsletter sender e-mail address and handler article selection)", 'newsletter');
$aMsg["CopyTitle"] = i18n("Duplicate newsletter");

while ($oNewsletter = $oNewsletters->next()) {
    $idnewsletter = $oNewsletter->get("idnews");
    $iMenu++;

    $sName = (cString::getStringLength(trim($oNewsletter->get("name"))) > 0) ? $oNewsletter->get("name") : i18n("-- New newsletter --", 'newsletter');
    if ($oNewsletter->get("welcome")) {
        $sName = $sName . "*";
    }

    // Create the link to show/edit the newsletter
    $oLnk = new cHTMLLink();
    $oLnk->setMultiLink($area, "", $area, "");
    $oLnk->setCustom("idnewsletter", $idnewsletter);

    $oMenu->setTitle($iMenu, $sName);
    $oMenu->setLink($iMenu, $oLnk);

    if ($perm->have_perm_area_action($area, "news_add_job") || $perm->have_perm_area_action($area, "news_create") || $perm->have_perm_area_action($area, "news_save")) {
        // Rights: If you are able to add a job, you should be able to test it
        // If you are able to add or change a newsletter, you should be able to
        // test it
        // Usability: If no e-mail has been specified, you can't send a test
        // newsletter
        if (isValidMail($oNewsletter->get("newsfrom")) && $lIDCatArt > 0) {
            $sLnkSendTest = '<a title="' . $aMsg["SendTestTitle"] . '" href="javascript://" onclick="showSendTestMsg(' . $idnewsletter . ')"><img src="' . $cfg['path']['images'] . 'newsletter_sendtest_16.gif" border="0" title="' . $aMsg["SendTestTitle"] . '" alt="' . $aMsg["SendTestTitle"] . '"></a>';
        } else {
            $sLnkSendTest = '<img src="' . $cfg['path']['images'] . 'newsletter_sendtest_16_off.gif" border="0" title="' . $aMsg["SendTestTitleOff"] . '" alt="' . $aMsg["SendTestTitleOff"] . '">';
        }
        $oMenu->setActions($iMenu, 'test', $sLnkSendTest);
    }

    if ($perm->have_perm_area_action($area, "news_add_job")) {
        if (isValidMail($oNewsletter->get("newsfrom")) && $lIDCatArt > 0) {
            $oLnkAddJob = new cHTMLLink();
            $oLnkAddJob->setMultiLink("news", "", "news", "news_add_job");
            $oLnkAddJob->setCustom("idnewsletter", $idnewsletter);
            $oLnkAddJob->setAlt($aMsg["AddJobTitle"]);
            $oLnkAddJob->setContent('<img src="' . $cfg['path']['images'] . 'newsletter_dispatch_16.gif" border="0" title="' . $aMsg["AddJobTitle"] . '" alt="' . $aMsg["AddJobTitle"] . '">');

            $sLnkAddJob = $oLnkAddJob->render();
        } else {
            $sLnkAddJob = '<img src="' . $cfg['path']['images'] . 'newsletter_dispatch_16_off.gif" border="0" title="' . $aMsg["AddJobTitleOff"] . '" alt="' . $aMsg["AddJobTitleOff"] . '">';
        }

        $oMenu->setActions($iMenu, 'dispatch', $sLnkAddJob);
    }

    if ($perm->have_perm_area_action($area, "news_create")) {
        $oLnkCopy = new cHTMLLink();
        $oLnkCopy->setMultiLink("news", "", "news", "news_duplicate");
        $oLnkCopy->setCustom("idnewsletter", $idnewsletter);
        $oLnkCopy->setAlt($aMsg["CopyTitle"]);
        $oLnkCopy->setContent('<img src="' . $cfg['path']['images'] . 'but_copy.gif" border="0" title="' . $aMsg["CopyTitle"] . '" alt="' . $aMsg["CopyTitle"] . '">');

        $oMenu->setActions($iMenu, 'copy', $oLnkCopy->render());
    }

    if ($perm->have_perm_area_action($area, "news_delete")) {
        $sDelete = '<a title="' . $aMsg["DelTitle"] . '" href="javascript://" onclick="showDelMsg(' . $idnewsletter . ',\'' . addslashes($sName) . '\')"><img src="' . $cfg['path']['images'] . 'delete.gif" border="0" title="' . $aMsg["DelTitle"] . '" alt="' . $aMsg["DelTitle"] . '"></a>';
        $oMenu->setActions($iMenu, 'delete', $sDelete);
    }
}

// Check destination for sending test newsletter
if ($_REQUEST["selTestDestination"] > 0 && $perm->have_perm_area_action($area, "news_send_test")) {
    $oRcpGroups = new NewsletterRecipientGroupCollection();
    $oRcpGroups->setWhere("idclient", $client);
    $oRcpGroups->setWhere("idlang", $lang);
    $oRcpGroups->setWhere($oRcpGroups->getPrimaryKeyName(), $_REQUEST["selTestDestination"]);
    $oRcpGroups->query();

    if ($oRcpGroup = $oRcpGroups->next()) {
        $sSendTestTarget = sprintf(i18n("Recipient group: %s", 'newsletter'), $oRcpGroup->get("groupname"));
    }
    unset($oRcpGroups);
}

$aMsg["SendTestDescr"] = sprintf(i18n("Do you really want to send the newsletter to:<br><strong>%s</strong>", 'newsletter'), $sSendTestTarget);

$sExecScript = <<<JS
<script type="text/javascript">
function showSendTestMsg(lngId) {
    Con.showConfirmation("{$aMsg["SendTestDescr"]}", function() {
        sendTestNewsletter(lngId);
    });
}

function showDelMsg(lngId, strElement) {
    Con.showConfirmation("{$aMsg["DelDescr"]}<b>" + strElement + "</b>", function() {
        deleteNewsletter(lngId);
    });
}

function checkSelection(strValue) {
    if (strValue == "selection") {
        document.getElementById("groupselect").disabled = false;
    } else {
        document.getElementById("groupselect").disabled = true;
    }
}

// Function for sending test newsletter
function sendTestNewsletter(idnewsletter) {
    var oForm = Con.getFrame("left_top").document.getElementById("newsletter_listoptionsform");

    var url = "main.php?area=news";
    url += "&action=news_send_test";
    url += "&frame=4";
    url += "&idnewsletter=" + idnewsletter;
    url += "&contenido=" + Con.sid;
    url += get_registered_parameters();
    url += "&sortby=" + oForm.sortby.value;
    url += "&sortorder=" + oForm.sortorder.value;
    url += "&filter=" + oForm.filter.value;
    url += "&elemperpage=" + oForm.elemperpage.value;

    Con.getFrame("right_bottom").location.href = url;
}

// Function for deleting newsletters
function deleteNewsletter(idnewsletter) {
    var oForm = Con.getFrame("left_top").document.getElementById("newsletter_listoptionsform");

    var url = "main.php?area=news";
    url += "&action=news_delete";
    url += "&frame=4";
    url += "&idnewsletter=" + idnewsletter;
    url += "&contenido=" + Con.sid;
    url += get_registered_parameters();
    url += "&sortby=" + oForm.sortby.value;
    url += "&sortorder=" + oForm.sortorder.value;
    url += "&filter=" + oForm.filter.value;
    url += "&elemperpage=" + oForm.elemperpage.value;

    Con.getFrame("right_bottom").location.href = url;
}
</script>
JS;

// Messagebox JS has to be included before ExecScript!
$oPage->addScript($sExecScript);
$oPage->addScript('parameterCollector.js');

// Generate current content for Object Pager
$sPagerId = "0ed6d632-6adf-4f09-a0c6-1e38ab60e302";
$oPagerLink = new cHTMLLink();
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
// Note, that after the "page" parameter no "pagerlink" parameter is specified -
// it is not used, as the JS below only uses the INNER html and the "pagerlink"
// parameter is
// set by ...left_top.html for the foldingrow itself
$oPager = new cGuiObjectPager($sPagerId, $iItemCount, $_REQUEST["elemperpage"], $_REQUEST["page"], $oPagerLink, "page");

// Add slashes, to insert in javascript
$sPagerContent = $oPager->render(1);
$sPagerContent = str_replace('\\', '\\\\', $sPagerContent);
$sPagerContent = str_replace('\'', '\\\'', $sPagerContent);

// Send new object pager to left_top
$oPage->addScript('setPager.js');

$sRefreshPager = <<<JS
<script type="text/javascript">
var sNavigation = '{$sPagerContent}';
// Activate time to refresh pager folding row in left top
var oTimer = window.setInterval(function() {
    fncSetPager('{$sPagerId}', '{$_REQUEST["page"]}');
}, 200);
</script>
JS;

$oPage->addScript($sRefreshPager);

$oPage->setContent($oMenu);
$oPage->render();
