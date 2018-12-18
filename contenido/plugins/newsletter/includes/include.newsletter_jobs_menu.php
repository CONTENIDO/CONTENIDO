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
// Not needed, as no sort/search, but keep as memo: $aFields["cronjob"] =
// array("field" => "use_cronjob", "caption" => i18n("Use cronjob",
// 'newsletter'), "type" => "base");

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
unset($oUser);

$_REQUEST["page"] = (int) $_REQUEST["page"];
if ($_REQUEST["page"] <= 0 || $_REQUEST["elemperpage"] == 0) {
    $_REQUEST["page"] = 1;
}
// Sort order
if ($_REQUEST["sortorder"] != "ASC") {
    $_REQUEST["sortorder"] = "DESC"; // Note, default is DESC (as default sortby
                                     // is "created" date)
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
    $_REQUEST["sortby"] = "created"; // Default sort by field, possible values
                                     // see above
}
if (!$bSearchInFound) {
    $_REQUEST["searchin"] = "--all--";
}

// Author
if ($_REQUEST["selAuthor"] == "") {
    $_REQUEST["selAuthor"] = $auth->auth["uid"];
}

// Free memory
unset($oUser);

// ################################
// Get data
// ################################

$oJobs->setWhere("idclient", $client);
$oJobs->setWhere("idlang", $lang);
$oJobs->setWhere("author", $_REQUEST["selAuthor"]);

if ($_REQUEST["filter"] != "") {
    if ($_REQUEST["searchin"] == "--all--" || $_REQUEST["searchin"] == "") {
        foreach ($aFields as $sKey => $aData) {
            if (cString::findFirstPos($aData["type"], "search") !== false) {
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

    // Getting item count without limit (for page function) - better idea anyone
    // (performance)?
    $iItemCount = $oJobs->count();

    if ($_REQUEST["elemperpage"] * ($_REQUEST["page"]) >= $iItemCount + $_REQUEST["elemperpage"] && $_REQUEST["page"] != 1) {
        $_REQUEST["page"]--;
    }
    $oJobs->setLimit($_REQUEST["elemperpage"] * ($_REQUEST["page"] - 1), $_REQUEST["elemperpage"]);
} else {
    $iItemCount = 0;
}

$oJobs->setOrder($_REQUEST["sortby"] . " " . $_REQUEST["sortorder"]);
$oJobs->query();

// Output data
$oMenu = new cGuiMenu();
$iMenu = 0;
$sDateFormat = getEffectiveSetting("dateformat", "full", "d.m.Y H:i");

// Store messages for repeated use (speeds performance, as i18n translation is
// only needed once)
$aMsg = array();
$aMsg["DelTitle"] = i18n("Delete dispatch job", 'newsletter');
$aMsg["DelDescr"] = i18n("Do you really want to delete the following newsletter dispatch job:<br>", 'newsletter');

$aMsg["SendTitle"] = i18n("Run job", 'newsletter');
$aMsg["SendDescr"] = i18n("Do you really want to run the following job:<br>", 'newsletter');

// Prepare "send link" template
$sTplSend = '<a title="' . $aMsg["SendTitle"] . '" href="javascript://" onclick="showSendMsg(\'{ID}\',\'{NAME}\')"><img alt="" src="' . $cfg['path']['images'] . 'newsletter_16.gif" border="0" title="' . $aMsg["SendTitle"] . '" alt="' . $aMsg["SendTitle"] . '"></a>';

while ($oJob = $oJobs->next()) {
    $iMenu++;
    $iID = $oJob->get("idnewsjob");
    $sName = $oJob->get("name") . " (" . date($sDateFormat, strtotime($oJob->get("created"))) . ")";

    $oLnk = new cHTMLLink();
    $oLnk->setMultiLink($area, "", $area, "");
    $oLnk->setCustom("idnewsjob", $iID);

    // Is at present redundant
    // HerrB: No, it's just not used/set...
    // $oMenu->setImage($iMenu, "images/newsletter_16.gif");
    $oMenu->setTitle($iMenu, $sName);

    switch ($oJob->get("status")) {
        case 1:
            // Pending
            if ($oJob->get("cronjob") == 0) {
                // Standard job can be run if user has the right to do so
                if ($perm->have_perm_area_action($area, "news_job_run")) {
                    $sLnkSend = str_replace('{ID}', $iID, $sTplSend);
                    $sLnkSend = str_replace('{NAME}', addslashes($sName), $sLnkSend);

                    $oMenu->setActions($iMenu, 'send', $sLnkSend);
                }
            } elseif ($oJob->get("cronjob") == 1) {
                // It's a cronjob job - no manual sending, show it blue
                $oLnk->updateAttributes(array(
                    "style" => "color:#0000FF"
                ));
            }

            if ($perm->have_perm_area_action($area, "news_job_delete")) {
                // Job may be deleted, if user has the right to do so
                $oMenu->setActions($iMenu, 'delete', '<a title="' . $aMsg["DelTitle"] . '" href="javascript://" onclick="showDelMsg(' . $iID . ',\'' . addslashes($sName) . '\')"><img alt="" src="' . $cfg['path']['images'] . 'delete.gif" border="0" title="' . $aMsg["DelTitle"] . '" alt="' . $aMsg["DelTitle"] . '"></a>');
            }
            break;
        case 2:
            // Sending job
            if ($perm->have_perm_area_action($area, "news_job_run")) {
                // User may try to start sending, again - if he has the right to
                // do so
                $sLnkSend = str_replace('{ID}', $iID, $sTplSend);
                $sLnkSend = str_replace('{NAME}', addslashes($sName), $sLnkSend);

                $oMenu->setActions($iMenu, 'send', $sLnkSend);
            }

            $oLnk->updateAttributes(array(
                "style" => "color:#da8a00"
            ));

            $sDelete = '< alt="" img src="' . $cfg['path']['images'] . 'delete_inact.gif" border="0" title="' . i18n("Can't delete the job while it's running", "newsletter") . '" alt="' . i18n("Can't delete the job while it's running", "newsletter") . '">';
            break;
        case 9:
            // Job finished, don't do anything
            $oLnk->updateAttributes(array(
                "style" => "color:#808080"
            ));

            if ($perm->have_perm_area_action($area, "news_job_delete")) {
                // You have the right, but you can't delete the job after
                // sending
                $oMenu->setActions($iMenu, 'delete', '<img alt="" src="' . $cfg['path']['images'] . 'delete_inact.gif" border="0" title="' . i18n("Can't delete the job after it's been sent", "newsletter") . '" alt="' . i18n("Can't delete the job after it's been sent", "newsletter") . '">');
            }
            break;
    }

    $oMenu->setLink($iMenu, $oLnk);
}

$sExecScript = <<<JS
<script type="text/javascript">
function showSendMsg(lngId, strElement) {
    Con.showConfirmation("{$aMsg["SendDescr"]}<b>" + strElement + "</b>", function() {
        runJob(lngId);
    });
}

function showDelMsg(lngId, strElement) {
    Con.showConfirmation("{$aMsg["DelDescr"]}<b>" + strElement + "</b>", function() {
        deleteJob(lngId);
    });
}

// Function for running job
function runJob(idnewsjob) {
    var oForm = Con.getFrame("left_top").document.getElementById("dispatch_listoptionsform");

    var url = "main.php?area=news_jobs";
    url += "&action=news_job_run";
    url += "&frame=4";
    url += "&idnewsjob=" + idnewsjob;
    url += "&contenido=" + Con.sid;
    url += get_registered_parameters();
    url += "&selAuthor=" + oForm.selAuthor.value;
    url += "&sortby=" + oForm.sortby.value;
    url += "&sortorder=" + oForm.sortorder.value;
    url += "&filter=" + oForm.filter.value;
    url += "&elemperpage=" + oForm.elemperpage.value;

    Con.getFrame("right_bottom").location.href = url;
}

// Function for deleting job
function deleteJob(idnewsjob) {
    var oForm = Con.getFrame("left_top").document.getElementById("dispatch_listoptionsform");

    var url = "main.php?area=news_jobs";
    url += "&action=news_job_delete";
    url += "&frame=4";
    url += "&idnewsjob=" + idnewsjob;
    url += "&contenido=" + Con.sid;
    url += get_registered_parameters();
    url += "&selAuthor=" + oForm.selAuthor.value;
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

// generate current content for Object Pager
$sPagerId = '0ed6d632-6adf-4f09-a0c6-1e38ab60e303';
$oPagerLink = new cHTMLLink();
$oPagerLink->setLink("main.php");
$oPagerLink->setTargetFrame('left_bottom');
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
