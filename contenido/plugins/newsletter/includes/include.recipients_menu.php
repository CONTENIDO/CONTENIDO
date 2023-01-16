<?php
/**
 * This file contains the Recipient user list.
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
$oPage = new cGuiPage("recipients_menu", "newsletter");
$oMenu = new cGuiMenu();
$oClient = new cApiClient($client);
$oUser = new cApiUser($auth->auth["uid"]);
// $sLocation = $sess->url("main.php?area=$area&frame=$frame");

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
    "email" => [
        "field" => "email",
        "caption" => i18n("E-Mail", 'newsletter'),
        "type" => "base,sort,search"
    ],
    "confirmed" => [
        "field" => "confirmed",
        "caption" => i18n("Confirmed", 'newsletter'),
        "type" => "base"
    ],
    "deactivated" => [
        "field" => "deactivated",
        "caption" => i18n("Deactivated", 'newsletter'),
        "type" => "base"
    ],
];

// ################################
// Store settings
// ################################

$requestTxtPurgeTimeframe = cSecurity::toInteger($_REQUEST['txtPurgeTimeframe'] ?? '0');

// Update purgetimeframe if submitted
// $sRefreshTop = '';
$iTimeframe = $oClient->getProperty("newsletter", "purgetimeframe");
if ($requestTxtPurgeTimeframe > 0 && $requestTxtPurgeTimeframe != $iTimeframe && $perm->have_perm_area_action($area, "recipients_delete")) {
    $oClient->setProperty("newsletter", "purgetimeframe", $requestTxtPurgeTimeframe);
    // $sRefreshTop = '<script
    // type="text/javascript">Con.getFrame('left_top').purgetimeframe =
    // '.$requestTxtPurgeTimeframe.'</script>';
}

// ################################
// Check external input
// ################################

$requestElemPerPage = $_REQUEST['elemperpage'] ?? '';
$requestPage = cSecurity::toInteger($_REQUEST['page'] ?? '0');
$requestSortOrder = !isset($_REQUEST['sortorder']) || $_REQUEST['sortorder'] !== 'DESC' ? 'ASC' : 'DESC';
$requestFilter = $_REQUEST['filter'] ?? '';
$requestSortBy = $_REQUEST['sortby'] ?? '';
$requestSearchIn = $_REQUEST['searchin'] ?? '';
$requestRestrictGroup = cSecurity::toInteger($_REQUEST['restrictgroup'] ?? '0');
$requestIdRecipient = (isset($_REQUEST['idrecipient'])) ? cSecurity::toInteger($_REQUEST['idrecipient']) : 0;

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

if ($requestRestrictGroup <= 0) {
    $requestRestrictGroup = "--all--";
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
    $requestSortBy = "name"; // Default sort by field, possible values see
                                      // above
}
if (!$bSearchInFound) {
    $requestSearchIn = "--all--";
}

// Free memory
unset($oUser);
unset($oClient);

// ################################
// Get data
// ################################
$oRecipients = new NewsletterRecipientCollection();

// Updating keys, if activated; all recipients of all clients!
if (getSystemProperty("newsletter", "updatekeys")) {
    $iUpdatedRecipients = $oRecipients->updateKeys();
    $oPage->displayOk(sprintf(i18n("%d recipients, with no or incompatible key has been updated. Deactivate update function.", 'newsletter'), $iUpdatedRecipients));
}

$oRecipients->setWhere("idclient", $client);
$oRecipients->setWhere("idlang", $lang);

// sort by and sort order
$oRecipients->setOrder($requestSortBy . " " . $requestSortOrder);

// Show group
if ($requestRestrictGroup != "--all--") {
    $oRecipients->link("RecipientGroupMemberCollection");
    $oRecipients->setWhere("idnewsgroup", $requestRestrictGroup);
}

// Search for
if ($requestFilter != "") {
    if ($requestSearchIn == "--all--" || $requestSearchIn == "") {
        foreach ($aFields as $sKey => $aData) {
            if (cString::findFirstPos($aData["type"], "search") !== false) {
                $oRecipients->setWhereGroup("filter", $aData["field"], $requestFilter, "LIKE");
            }
        }
        $oRecipients->setInnerGroupCondition("filter", "OR");
    } else {
        $oRecipients->setWhere($requestSearchIn, $requestFilter, "LIKE");
    }
}

// Items / page
if ($requestElemPerPage > 0) {
    // Getting item count without limit (for page function) - better idea anyone
    // (performance)?
    $oRecipients->query();
    $iItemCount = $oRecipients->count();

    if ($requestElemPerPage * ($requestPage) >= $iItemCount + $requestElemPerPage && $requestPage != 1) {
        $requestPage--;
    }

    $oRecipients->setLimit($requestElemPerPage * ($requestPage - 1), $requestElemPerPage);
} else {
    $iItemCount = 0;
}

$oRecipients->query();

// Output data
$oMenu = new cGuiMenu();
$iMenu = 0;

// Store messages for repeated use (speeds performance, as i18n translation is
// only needed once)
$aMsg = [
    "DelTitle" => i18n("Delete recipient", 'newsletter'),
    "DelDescr" => i18n("Do you really want to delete the following recipient:<br>", 'newsletter'),
];

while ($oRecipient = $oRecipients->next()) {
    $iMenu++;
    $idnewsrcp = cSecurity::toInteger($oRecipient->get("idnewsrcp"));

    $sName = $oRecipient->get("name");
    if (empty($sName)) {
        $sName = $oRecipient->get("email");
    }

    // Show recipient
    $oLnk = new cHTMLLink();
    $oLnk->setClass('show_item')
        ->setLink('javascript:;')
        ->setAttribute('data-action', 'recipients_show');
    if ($oRecipient->get("deactivated") == 1 || $oRecipient->get("confirmed") == 0) {
        $oLnk->updateAttributes([
            "style" => "color:#A20000"
        ]);
    }
    $oMenu->setLink($iMenu, $oLnk);

    $oMenu->setId($iMenu, $idnewsrcp);
    $oMenu->setTitle($iMenu, $sName);

    if ($requestIdRecipient == $idnewsrcp) {
        $oMenu->setMarked($iMenu);
    }

    if ($perm->have_perm_area_action("recipients", "recipients_delete")) {
        // Delete recipient
        $oImage = new cHTMLImage($cfg['path']['images'] . 'delete.gif', 'vAlignMiddle');
        $oImage->setAlt($aMsg["DelTitle"]);
        $oDelete = new cHTMLLink();
        $oDelete->setLink('javascript:;')
            ->setAlt($aMsg["DelTitle"])
            ->setAttribute('data-action', 'recipients_delete')
            ->setContent($oImage->render());
        $oMenu->setActions($iMenu, 'delete', $oDelete->render());
    }
}

$oPage->addScript('parameterCollector.js?v=4ff97ee40f1ac052f634e7e8c2f3e37e');
// $oPage->addScript('refreshTop', $sRefreshTop);

// generate current content for Object Pager�
$sPagerId = '0ed6d632-6adf-4f09-a0c6-1e38ab60e304';
$oPagerLink = new cHTMLLink();
$oPagerLink->setLink("main.php");
$oPagerLink->setTargetFrame('left_bottom');
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

// add slashes, to insert in javascript
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
$oTpl->set('s', 'DELETE_MESSAGE', $aMsg["DelDescr"]);
$sTemplate = $oTpl->generate(cRegistry::getBackendPath() . $cfg['path']['plugins'] . 'newsletter/templates/standard/template.recipients_menu.html', true);

$oPage->setContent([$oMenu, $sTemplate]);
$oPage->render();
