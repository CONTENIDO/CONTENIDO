<?php

/**
 * This file contains the Frontend user list.
 *
 * @package    Plugin
 * @subpackage Newsletter
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cAuth $auth
 * @var cPermission $perm
 * @var cSession $sess
 * @var array $cfg
 * @var string $area
 * @var int $client
 * @var int $lang
 * @var int $frame
 */

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
$aFields = [
    "name" => [
        "field" => "name",
        "caption" => i18n("Name", 'newsletter'),
        "type" => "base,sort,search"
    ]
];

$requestSortOrder = !isset($_REQUEST['sortorder']) || $_REQUEST['sortorder'] !== 'DESC' ? 'ASC' : 'DESC';
$requestIdNewsletter = cSecurity::toInteger($_REQUEST['idnewsletter'] ?? '0');
$requestActionHtml = $_REQUEST['action_html'] ?? '';
$requestElemPerPage = $_REQUEST['elemperpage'] ?? '';
$requestPage = cSecurity::toInteger($_REQUEST['page'] ?? '0');
$requestSortBy = $_REQUEST['sortby'] ?? '';
$requestSearchIn = $_REQUEST['searchin'] ?? '';
$requestFilter = $_REQUEST['filter'] ?? '';
$requestRestrictGroup = $_REQUEST['restrictgroup'] ?? '';
$requestSelTestDestination = cSecurity::toInteger($_REQUEST['selTestDestination'] ?? '0');

// ################################
// Store settings/Get basic data
// ################################
if ($requestActionHtml == 'save_newsletter_properties' && $perm->have_perm_area_action($area, "news_html_settings")) {
    // Storing settings
    if (isset($_REQUEST['ckbHTMLNewsletter'])) {
        $oClientLang->setProperty("newsletter", "html_newsletter", "true");
    } else {
        $oClientLang->setProperty("newsletter", "html_newsletter", "false");
    }
    $oClientLang->setProperty("newsletter", "html_template_idcat", cSecurity::toInteger($_REQUEST['selHTMLTemplateCat'] ?? '0'));
    $oClientLang->setProperty("newsletter", "html_newsletter_idcat", cSecurity::toInteger($_REQUEST['selHTMLNewsletterCat'] ?? '0'));
    $oUser->setProperty("newsletter", "test_idnewsgrp_lang" . $lang, $requestSelTestDestination);
} else {
    // No settings to be stored, get current settings (language sepcific, as
    // lang is client specific, lang is sufficient)
    $requestSelTestDestination = cSecurity::toInteger($oUser->getProperty("newsletter", "test_idnewsgrp_lang" . $lang));
}
// Default value: Current user mail
$sSendTestTarget = ($oUser->get("realname") ?? '') . " (" . $oUser->get("email") . ")";

// ################################
// Check external input
// ################################
// Items per page (value stored per area in user property)
if (!is_numeric($requestElemPerPage) || $requestElemPerPage < 0) {
    $requestElemPerPage = $oUser->getProperty("itemsperpage", $area);
}
if (!is_numeric($requestElemPerPage)) {
    // This is the case, if the user property has never been set (first time
    // user)
    $requestElemPerPage = 25;
}
if ($requestElemPerPage > 0) {
    // -- All -- will not be stored, as it may be impossible to change this back
    // to something more useful
    $oUser->setProperty("itemsperpage", $area, $requestElemPerPage);
}

if ($requestPage <= 0 || $requestElemPerPage == 0) {
    $requestPage = 1;
}

// Check sort by and search in criteria
$bSortByFound = false;
$bSearchInFound = false;
foreach ($aFields as $sKey => $aData) {
    if ($aData["field"] == $requestSortBy && cString::findFirstPos($aData["type"], "sort") !== false) {
        $bSortByFound = true;
    }
    if ($aData["field"] == $requestSearchIn && cString::findFirstPos($aData["type"], "search") !== false) {
        $bSearchInFound = true;
    }
}
if (!$bSortByFound) {
    $requestSortBy = "name";
}
if (!$bSearchInFound) {
    $requestSearchIn = "--all--";
}

// Free memory
unset($oClientLang, $oUser);

// ################################
// Get data
// ################################
$oNewsletters = new NewsletterCollection();
$oNewsletters->setWhere("idclient", $client);
$oNewsletters->setWhere("idlang", $lang);

if ($requestFilter != "") {
    if ($requestSearchIn == "--all--" || $requestSearchIn == "") {
        foreach ($aFields as $sKey => $aData) {
            if (cString::findFirstPos($aData["type"], "search") !== false) {
                $oNewsletters->setWhereGroup("filter", $aData["field"], $requestFilter, "LIKE");
            }
        }
        $oNewsletters->setInnerGroupCondition("filter", "OR");
    } else {
        $oNewsletters->setWhere($requestSearchIn, $requestFilter, "LIKE");
    }
}

if ($requestElemPerPage > 0) {
    // Getting item count without limit (for page function) - better idea anyone
    // (performance)?
    $oNewsletters->query();
    $iItemCount = $oNewsletters->count();

    if ($requestElemPerPage * ($requestPage) >= $iItemCount + $requestElemPerPage && $requestPage != 1) {
        $requestPage--;
    }

    $oNewsletters->setLimit($requestElemPerPage * ($requestPage - 1), $requestElemPerPage);
} else {
    $iItemCount = 0;
}

$oNewsletters->setOrder("welcome DESC, " . $requestSortBy . " " . $requestSortOrder);
$oNewsletters->query();

// Output data
$oMenu = new cGuiMenu();
$iMenu = 0;

// Store messages for repeated use (speeds performance, as i18n translation is
// only needed once)
$aMsg = [
    "DelTitle" => i18n("Delete newsletter", 'newsletter'),
    "DelDescr" => i18n("Do you really want to delete the following newsletter:<br>", 'newsletter'),
    "SendTestTitle" => i18n("Send test newsletter", 'newsletter'),
    "SendTestTitleOff" => i18n("Send test newsletter (disabled, check newsletter sender e-mail address and handler article selection)", 'newsletter'),
    "AddJobTitle" => i18n("Add newsletter dispatch job", 'newsletter'),
    "AddJobTitleOff" => i18n("Add newsletter dispatch job (disabled, check newsletter sender e-mail address and handler article selection)", 'newsletter'),
    "CopyTitle" => i18n("Duplicate newsletter"),
];

while ($oNewsletter = $oNewsletters->next()) {
    $idnewsletter = cSecurity::toInteger($oNewsletter->get("idnews"));
    $iMenu++;

    $sName = (cString::getStringLength(trim($oNewsletter->get("name"))) > 0) ? $oNewsletter->get("name") : i18n("-- New newsletter --", 'newsletter');
    if ($oNewsletter->get("welcome")) {
        $sName = $sName . "*";
    }

    // Create the link to show/edit the newsletter
    $oLnk = new cHTMLLink();
    $oLnk->setClass('show_item')
        ->setLink('javascript:void(0)')
        ->setAttribute('data-action', 'news_show');
    $oMenu->setLink($iMenu, $oLnk);

    $oMenu->setId($iMenu, $idnewsletter);
    $oMenu->setTitle($iMenu, $sName);

    if ($requestIdNewsletter == $idnewsletter) {
        $oMenu->setMarked($iMenu);
    }

    if ($perm->have_perm_area_action($area, "news_add_job") || $perm->have_perm_area_action($area, "news_create") || $perm->have_perm_area_action($area, "news_save")) {
        // Rights: If you are able to add a job, you should be able to test it
        // If you are able to add or change a newsletter, you should be able to
        // test it
        // Usability: If no e-mail has been specified, you can't send a test
        // newsletter
        if (isValidMail($oNewsletter->get("newsfrom")) && $lIDCatArt > 0) {
            $oImage = new cHTMLImage($cfg['path']['images'] . 'newsletter_sendtest_16.gif');
            $oImage->setAlt($aMsg["SendTestTitle"]);
            $oSendTest = new cHTMLLink();
            $oSendTest->setLink('javascript:void(0)')
                ->setClass('con_img_button')
                ->setAlt($aMsg["SendTestTitle"])
                ->setAttribute('data-action', 'news_send_test')
                ->setContent($oImage->render());
        } else {
            $oSendTest = new cHTMLImage($cfg['path']['images'] . 'newsletter_sendtest_16_off.gif', 'con_img_button_off');
            $oSendTest->setAlt($aMsg["SendTestTitleOff"]);
        }
        $oMenu->setActions($iMenu, 'test', $oSendTest->render());
    }

    if ($perm->have_perm_area_action($area, "news_add_job")) {
        if (isValidMail($oNewsletter->get("newsfrom")) && $lIDCatArt > 0) {
            $oImage = new cHTMLImage($cfg['path']['images'] . 'newsletter_dispatch_16.gif');
            $oImage->setAlt($aMsg["AddJobTitle"]);
            $oAddJob = new cHTMLLink();
            $oAddJob->setLink('javascript:void(0)')
                ->setClass('con_img_button')
                ->setAlt($aMsg["AddJobTitle"])
                ->setAttribute('data-action', 'news_add_job')
                ->setContent($oImage->render());
        } else {
            $oAddJob = new cHTMLImage($cfg['path']['images'] . 'newsletter_dispatch_16_off.gif', 'con_img_button_off');
            $oAddJob->setAlt($aMsg["AddJobTitleOff"]);
        }
        $oMenu->setActions($iMenu, 'dispatch', $oAddJob->render());
    }

    if ($perm->have_perm_area_action($area, "news_create")) {
        $oImage = new cHTMLImage($cfg['path']['images'] . 'but_copy.gif');
        $oImage->setAlt($aMsg["CopyTitle"]);
        $oCopy = new cHTMLLink();
        $oCopy->setLink('javascript:void(0)')
            ->setClass('con_img_button')
            ->setAlt($aMsg["CopyTitle"])
            ->setAttribute('data-action', 'news_duplicate')
            ->setContent($oImage->render());
        $oMenu->setActions($iMenu, 'copy', $oCopy->render());
    }

    if ($perm->have_perm_area_action($area, "news_delete")) {
        $oImage = new cHTMLImage($cfg['path']['images'] . 'delete.gif');
        $oImage->setAlt($aMsg["DelTitle"]);

        $oDelete = new cHTMLLink();
        $oDelete->setLink('javascript:void(0)')
            ->setClass('con_img_button')
            ->setAlt($aMsg["DelTitle"])
            ->setAttribute('data-action', 'news_delete')
            ->setContent($oImage->render());
        $oMenu->setActions($iMenu, 'delete', $oDelete->render());
    }
}

// Check destination for sending test newsletter
if ($requestSelTestDestination > 0 && $perm->have_perm_area_action($area, "news_send_test")) {
    $oRcpGroups = new NewsletterRecipientGroupCollection();
    $oRcpGroups->setWhere("idclient", $client);
    $oRcpGroups->setWhere("idlang", $lang);
    $oRcpGroups->setWhere($oRcpGroups->getPrimaryKeyName(), $requestSelTestDestination);
    $oRcpGroups->query();

    if ($oRcpGroup = $oRcpGroups->next()) {
        $sSendTestTarget = sprintf(i18n("Recipient group: %s", 'newsletter'), $oRcpGroup->get("groupname"));
    }
    unset($oRcpGroups);
}

$aMsg["SendTestDescr"] = sprintf(i18n("Do you really want to send the newsletter to:<br><strong>%s</strong>", 'newsletter'), $sSendTestTarget);

$oPage->addScript('parameterCollector.js');

// Generate current content for Object Pager
$sPagerId = "0ed6d632-6adf-4f09-a0c6-1e38ab60e302";
$oPagerLink = new cHTMLLink();
$oPagerLink->setLink("main.php");
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setCustom("elemperpage", $requestElemPerPage);
$oPagerLink->setCustom("filter", $requestFilter);
$oPagerLink->setCustom("restrictgroup", $requestRestrictGroup);
$oPagerLink->setCustom("sortby", $requestSortBy);
$oPagerLink->setCustom("sortorder", $requestSortOrder);
$oPagerLink->setCustom("searchin", $requestSearchIn);
$oPagerLink->setCustom("restrictgroup", $requestRestrictGroup);
$oPagerLink->setCustom("frame", 2);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);
// Note, that after the "page" parameter no "pagerlink" parameter is specified -
// it is not used, as the JS below only uses the INNER html and the "pagerlink"
// parameter is
// set by ...left_top.html for the foldingrow itself
$oPager = new cGuiObjectPager($sPagerId, $iItemCount, $requestElemPerPage, $requestPage, $oPagerLink, "page");

// Add slashes, to insert in javascript
$sPagerContent = $oPager->render(1);
$sPagerContent = str_replace('\\', '\\\\', $sPagerContent);
$sPagerContent = str_replace('\'', '\\\'', $sPagerContent);

// Send new object pager to left_top
$oPage->addScript('setPager.js');

$sScript = <<<JS
<script type="text/javascript">
function checkSelection(strValue) {
    if (strValue == "selection") {
        document.getElementById("groupselect").disabled = false;
    } else {
        document.getElementById("groupselect").disabled = true;
    }
}

var sNavigation = '{$sPagerContent}';
// Activate time to refresh pager folding row in left top
var oTimer = window.setInterval(function() {
    fncSetPager('{$sPagerId}', '{$requestPage}');
}, 200);
</script>
JS;

$oPage->addScript($sScript);

// Generate template
$oTpl = new cTemplate();
$oTpl->set('s', 'SEND_TEST_MESSAGE', $aMsg["SendTestDescr"]);
$oTpl->set('s', 'DELETE_MESSAGE', $aMsg["DelDescr"]);
$sTemplate = $oTpl->generate($cfg['templates']['newsletter_newsletter_menu'], true);

$oPage->setContent([$oMenu, $sTemplate]);
$oPage->render();
