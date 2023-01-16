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
$oPage = new cGuiPage("newsletter_jobs_menu", "newsletter");
$oMenu = new cGuiMenu();
$oJobs = new NewsletterJobCollection();
$oUser = new cApiUser($auth->auth["uid"]);

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
    ],
    "created" => [
        "field" => "created",
        "caption" => i18n("Created", 'newsletter'),
        "type" => "base,sort"
    ],
    "status" => [
        "field" => "status",
        "caption" => i18n("Status", 'newsletter'),
        "type" => "base,sort"
    ],
];
// Not needed, as no sort/search, but keep as memo: $aFields["cronjob"] =
// ["field" => "use_cronjob", "caption" => i18n("Use cronjob",
// 'newsletter'), "type" => "base"];

// ################################
// Check external input
// ################################

// Note, default is DESC (as default sortby is "created" date)
$requestSortOrder = !isset($_REQUEST['sortorder']) || $_REQUEST['sortorder'] !== 'ASC' ? 'DESC' : 'ASC';
$requestSortBy = $_REQUEST['sortby'] ?? '';
$requestElemPerPage = $_REQUEST['elemperpage'] ?? '';
$requestPage = cSecurity::toInteger($_REQUEST['page'] ?? '0');
$requestSearchIn = $_REQUEST['searchin'] ?? '';
$requestSelAuthor = $_REQUEST['selAuthor'] ?? '';
$requestFilter = $_REQUEST['filter'] ?? '';
$requestRestrictGroup = $_REQUEST['restrictgroup'] ?? '';

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
unset($oUser);

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
    $requestSortBy = "created";
}
if (!$bSearchInFound) {
    $requestSearchIn = "--all--";
}

// Author
if (empty($requestSelAuthor)) {
    $requestSelAuthor = $auth->auth["uid"];
}

// Free memory
unset($oUser);

// ################################
// Get data
// ################################

$oJobs->setWhere("idclient", $client);
$oJobs->setWhere("idlang", $lang);
$oJobs->setWhere("author", $requestSelAuthor);

if ($requestFilter != "") {
    if ($requestSearchIn == "--all--" || $requestSearchIn == "") {
        foreach ($aFields as $sKey => $aData) {
            if (cString::findFirstPos($aData["type"], "search") !== false) {
                $oJobs->setWhereGroup("filter", $aData["field"], $requestFilter, "LIKE");
            }
        }
        $oJobs->setInnerGroupCondition("filter", "OR");
    } else {
        $oJobs->setWhere($requestSearchIn, $requestFilter, "LIKE");
    }
}

if ($requestElemPerPage > 0) {
    $oJobs->query();

    // Getting item count without limit (for page function) - better idea anyone
    // (performance)?
    $iItemCount = $oJobs->count();

    if ($requestElemPerPage * ($requestPage) >= $iItemCount + $requestElemPerPage && $requestPage != 1) {
        $requestPage--;
    }
    $oJobs->setLimit($requestElemPerPage * ($requestPage - 1), $requestElemPerPage);
} else {
    $iItemCount = 0;
}

$oJobs->setOrder($requestSortBy . " " . $requestSortOrder);
$oJobs->query();

// Output data
$oMenu = new cGuiMenu();
$iMenu = 0;
$sDateFormat = getEffectiveSetting("dateformat", "full", "d.m.Y H:i");

// Store messages for repeated use (speeds performance, as i18n translation is
// only needed once)
$aMsg = [
    "DelTitle" => i18n("Delete dispatch job", 'newsletter'),
    "DelDescr" => i18n("Do you really want to delete the following newsletter dispatch job:<br>", 'newsletter'),
    "SendTitle" => i18n("Run job", 'newsletter'),
    "SendDescr" => i18n("Do you really want to run the following job:<br>", 'newsletter'),
];


while ($oJob = $oJobs->next()) {
    $iMenu++;
    $iID = cSecurity::toInteger($oJob->get("idnewsjob"));
    $sName = $oJob->get("name") . " (" . date($sDateFormat, strtotime($oJob->get("created"))) . ")";

    // Create the link to show the newsletter job
    $oLnk = new cHTMLLink();
    $oLnk->setClass('show_item')
        ->setLink('javascript:;')
        ->setAttribute('data-action', 'news_job_show');
    $oMenu->setLink($iMenu, $oLnk);

    $oMenu->setId($iMenu, $iID);
    $oMenu->setTitle($iMenu, $sName);

    switch ($oJob->get("status")) {
        case 1:
            // Pending
            if ($oJob->get("cronjob") == 0) {
                // Standard job can be run if user has the right to do so
                if ($perm->have_perm_area_action($area, "news_job_run")) {
                    $oImage = new cHTMLImage($cfg['path']['images'] . 'newsletter_16.gif', 'vAlignMiddle');
                    $oImage->setAlt($aMsg["SendTitle"]);
                    $oSend = new cHTMLLink();
                    $oSend->setLink('javascript:;')
                        ->setAlt($aMsg["SendTitle"])
                        ->setAttribute('data-action', 'news_job_run')
                        ->setContent($oImage->render());
                    $oMenu->setActions($iMenu, 'send', $oSend->render());
                }
            } elseif ($oJob->get("cronjob") == 1) {
                // It's a cronjob job - no manual sending, show it blue
                $oLnk->updateAttributes([
                    "style" => "color:#0000FF"
                ]);
            }

            if ($perm->have_perm_area_action($area, "news_job_delete")) {
                // Job may be deleted, if user has the right to do so
                $oImage = new cHTMLImage($cfg['path']['images'] . 'delete.gif', 'vAlignMiddle');
                $oImage->setAlt($aMsg["DelTitle"]);
                $oDelete = new cHTMLLink();
                $oDelete->setLink('javascript:;')
                    ->setAlt($aMsg["DelTitle"])
                    ->setAttribute('data-action', 'news_job_delete')
                    ->setContent($oImage->render());
                $oMenu->setActions($iMenu, 'delete', $oDelete->render());
            }
            break;
        case 2:
            // Sending job
            if ($perm->have_perm_area_action($area, "news_job_run")) {
                // User may try to start sending, again - if he has the right to
                // do so
                $oImage = new cHTMLImage($cfg['path']['images'] . 'newsletter_16.gif', 'vAlignMiddle');
                $oImage->setAlt($aMsg["SendTitle"]);
                $oSend = new cHTMLLink();
                $oSend->setLink('javascript:;')
                    ->setAlt($aMsg["SendTitle"])
                    ->setAttribute('data-action', 'news_job_run')
                    ->setContent($oImage->render());
                $oMenu->setActions($iMenu, 'send', $oSend->render());
            }

            $oLnk->updateAttributes([
                "style" => "color:#da8a00"
            ]);

            // Delete disabled
            $oImage = new cHTMLImage($cfg['path']['images'] . 'delete_inact.gif', 'vAlignMiddle');
            $oImage->setAlt(i18n("Can't delete the job while it's running", "newsletter"));
            $oMenu->setActions($iMenu, 'delete', $oImage->render());
            break;
        case 9:
            // Job finished, don't do anything
            $oLnk->updateAttributes([
                "style" => "color:#808080"
            ]);

            if ($perm->have_perm_area_action($area, "news_job_delete")) {
                // You have the right, but you can't delete the job after
                // sending
                $oImage = new cHTMLImage($cfg['path']['images'] . 'delete_inact.gif', 'vAlignMiddle');
                $oImage->setAlt(i18n("Can't delete the job after it's been sent", "newsletter"));
                $oMenu->setActions($iMenu, 'delete', $oImage->render());
            }
            break;
    }

    $oMenu->setLink($iMenu, $oLnk);
}

$oPage->addScript('parameterCollector.js?v=4ff97ee40f1ac052f634e7e8c2f3e37e');

// generate current content for Object Pager
$sPagerId = '0ed6d632-6adf-4f09-a0c6-1e38ab60e303';
$oPagerLink = new cHTMLLink();
$oPagerLink->setLink("main.php");
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setCustom("selAuthor", $requestSelAuthor);
$oPagerLink->setCustom("elemperpage", $requestElemPerPage);
$oPagerLink->setCustom("filter", $requestFilter);
$oPagerLink->setCustom("restrictgroup", $requestRestrictGroup);
$oPagerLink->setCustom("sortby", $requestSortBy);
$oPagerLink->setCustom("sortorder", $requestSortOrder);
$oPagerLink->setCustom("searchin", $requestSearchIn);
$oPagerLink->setCustom("frame", $frame);
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

$sRefreshPager = <<<JS
<script type="text/javascript">
var sNavigation = '{$sPagerContent}';
// Activate time to refresh pager folding row in left top
var oTimer = window.setInterval(function() {
    fncSetPager('{$sPagerId}', '{$requestPage}');
}, 200);
</script>
JS;

$oPage->addScript($sRefreshPager);

// Generate template
$oTpl = new cTemplate();
$oTpl->set('s', 'SEND_MESSAGE', $aMsg["SendDescr"]);
$oTpl->set('s', 'DELETE_MESSAGE', $aMsg["DelDescr"]);
$sTemplate = $oTpl->generate(cRegistry::getBackendPath() . $cfg['path']['plugins'] . 'newsletter/templates/standard/template.newsletter_jobs_menu.html', true);

$oPage->setContent([$oMenu, $sTemplate]);
$oPage->render();
