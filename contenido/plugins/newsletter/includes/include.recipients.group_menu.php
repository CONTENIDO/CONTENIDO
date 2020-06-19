<?php
/**
 * This file contains the Frontend group list.
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
$oPage = new cGuiPage("recipients.group_menu", "newsletter");
$oMenu = new cGuiMenu();
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
        "field" => "groupname",
        "caption" => i18n("Name", 'newsletter'),
        "type" => "base,sort,search"
    ]
];

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

// Don't need to check sort by and search in criteria - just set it
$_REQUEST["sortby"] = "groupname"; // Default sort by field, possible values see
                                   // above
$_REQUEST["searchin"] = "--all--";

$requestIdRecipientGoup = (isset($_REQUEST['idrecipientgroup'])) ? cSecurity::toInteger($_REQUEST['idrecipientgroup']) : 0;

// Free memory
unset($oUser);
unset($oClient);

// ################################
// Get data
// ################################
$oRcpGroups = new NewsletterRecipientGroupCollection();
$oRcpGroups->setWhere("idclient", $client);
$oRcpGroups->setWhere("idlang", $lang);

if ($_REQUEST["filter"] != "") {
    if ($_REQUEST["searchin"] == "--all--" || $_REQUEST["searchin"] == "") {
        foreach ($aFields as $sKey => $aData) {
            if (cString::findFirstPos($aData["type"], "search") !== false) {
                $oRcpGroups->setWhereGroup("filter", $aData["field"], $_REQUEST["filter"], "LIKE");
            }
        }
        $oRcpGroups->setInnerGroupCondition("filter", "OR");
    } else {
        $oRcpGroups->setWhere($_REQUEST["searchin"], $_REQUEST["filter"], "LIKE");
    }
}

if ($_REQUEST["elemperpage"] > 0) {
    // Getting item count without limit (for page function) - better idea anyone
    // (performance)?
    $oRcpGroups->query();
    $iItemCount = $oRcpGroups->count();

    if ($_REQUEST["elemperpage"] * ($_REQUEST["page"]) >= $iItemCount + $_REQUEST["elemperpage"] && $_REQUEST["page"] != 1) {
        $_REQUEST["page"]--;
    }

    $oRcpGroups->setLimit($_REQUEST["elemperpage"] * ($_REQUEST["page"] - 1), $_REQUEST["elemperpage"]);
} else {
    $iItemCount = 0;
}

$oRcpGroups->setOrder("defaultgroup DESC, " . $_REQUEST["sortby"] . " " . $_REQUEST["sortorder"]);
$oRcpGroups->query();

// Output data
$oMenu = new cGuiMenu();
$iMenu = 0;

// Store messages for repeated use (speeds performance, as i18n translation is
// only needed once)
$aMsg = [
    "DelTitle" => i18n("Delete recipient group", 'newsletter'),
    "DelDescr" => i18n("Do you really want to delete the following newsletter recipient group:<br>", 'newsletter'),
];

while ($oRcpGroup = $oRcpGroups->next()) {
    $iMenu++;
    $iIDGroup = cSecurity::toInteger($oRcpGroup->get("idnewsgroup"));

    $sName = $oRcpGroup->get("groupname");
    if ($oRcpGroup->get("defaultgroup")) {
        $sName = $sName . "*";
    }

    // Show recipient group
    $oLnk = new cHTMLLink();
    $oLnk->setClass('show_item')
        ->setLink('javascript:;')
        ->setAttribute('data-action', 'recipientgroup_show');
    $oMenu->setLink($iMenu, $oLnk);

    $oMenu->setId($iMenu, $iIDGroup);
    $oMenu->setTitle($iMenu, $sName);

    if ($requestIdRecipientGoup == $iIDGroup) {
        $oMenu->setMarked($iMenu);
    }

    if ($perm->have_perm_area_action($area, 'recipientgroup_delete')) {
        // Delete recipient group
        $oImage = new cHTMLImage($cfg['path']['images'] . 'delete.gif', 'vAlignMiddle');
        $oImage->setAlt($aMsg["DelTitle"]);
        $oDelete = new cHTMLLink();
        $oDelete->setLink('javascript:;')
            ->setAlt($aMsg["DelTitle"])
            ->setAttribute('data-action', 'recipientgroup_delete')
            ->setContent($oImage->render());
        $oMenu->setActions($iMenu, 'delete', $oDelete->render());
    }
}

// $oPage->addScript('cfoldingrow.js', '<script type="text/javascript"
// src="scripts/cfoldingrow.js"></script>');
$oPage->addScript('parameterCollector.js?v=4ff97ee40f1ac052f634e7e8c2f3e37e');

// Generate current content for Object Pager
$sPagerId = "0ed6d632-6adf-4f09-a0c6-1e38ab60e305";
$oPagerLink = new cHTMLLink();
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

// Generate template
$oTpl = new cTemplate();
$oTpl->set('s', 'DELETE_MESSAGE', $aMsg["DelDescr"]);
$sTemplate = $oTpl->generate(cRegistry::getBackendPath() . $cfg['path']['plugins'] . 'newsletter/templates/standard/template.recipients.group_menu.html', true);

$oPage->setContent([$oMenu, $sTemplate]);
$oPage->render();
