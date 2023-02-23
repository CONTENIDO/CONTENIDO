<?php

/**
 * This file contains the Frontend group list.
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

$requestElemPerPage = $_REQUEST['elemperpage'] ?? '';
$requestPage = cSecurity::toInteger($_REQUEST['page'] ?? '0');
$requestSortOrder = !isset($_REQUEST['sortorder']) || $_REQUEST['sortorder'] !== 'DESC' ? 'ASC' : 'DESC';
$requestFilter = $_REQUEST['filter'] ?? '';
$requestSortBy = $_REQUEST['sortby'] ?? '';
$requestSearchIn = $_REQUEST['searchin'] ?? '';
$requestIdRecipientGoup = cSecurity::toInteger($_REQUEST['idrecipientgroup'] ?? '0');

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

// Don't need to check sort by and search in criteria - just set it
$requestSortBy = "groupname"; // Default sort by field, possible values see above
$requestSearchIn = "--all--";

// Free memory
unset($oUser);
unset($oClient);

// ################################
// Get data
// ################################
$oRcpGroups = new NewsletterRecipientGroupCollection();
$oRcpGroups->setWhere("idclient", $client);
$oRcpGroups->setWhere("idlang", $lang);

if ($requestFilter != "") {
    if ($requestSearchIn == "--all--" || $requestSearchIn == "") {
        foreach ($aFields as $sKey => $aData) {
            if (cString::findFirstPos($aData["type"], "search") !== false) {
                $oRcpGroups->setWhereGroup("filter", $aData["field"], $requestFilter, "LIKE");
            }
        }
        $oRcpGroups->setInnerGroupCondition("filter", "OR");
    } else {
        $oRcpGroups->setWhere($requestSearchIn, $requestFilter, "LIKE");
    }
}

if ($requestElemPerPage > 0) {
    // Getting item count without limit (for page function) - better idea anyone
    // (performance)?
    $oRcpGroups->query();
    $iItemCount = $oRcpGroups->count();

    if ($requestElemPerPage * ($requestPage) >= $iItemCount + $requestElemPerPage && $requestPage != 1) {
        $requestPage--;
    }

    $oRcpGroups->setLimit($requestElemPerPage * ($requestPage - 1), $requestElemPerPage);
} else {
    $iItemCount = 0;
}

$oRcpGroups->setOrder("defaultgroup DESC, " . $requestSortBy . " " . $requestSortOrder);
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
        ->setLink('javascript:void(0)')
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
        $oDelete->setLink('javascript:void(0)')
            ->setAlt($aMsg["DelTitle"])
            ->setAttribute('data-action', 'recipientgroup_delete')
            ->setContent($oImage->render());
        $oMenu->setActions($iMenu, 'delete', $oDelete->render());
    }
}

// $oPage->addScript('cfoldingrow.js', '<script type="text/javascript"
// src="scripts/cfoldingrow.js"></script>');
$oPage->addScript('parameterCollector.js');

// Generate current content for Object Pager
$sPagerId = "0ed6d632-6adf-4f09-a0c6-1e38ab60e305";
$oPagerLink = new cHTMLLink();
$oPagerLink->setLink("main.php");
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setCustom("elemperpage", $requestElemPerPage);
$oPagerLink->setCustom("filter", $requestFilter);
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
$oTpl->set('s', 'DELETE_MESSAGE', $aMsg["DelDescr"]);
$sTemplate = $oTpl->generate($cfg['templates']['newsletter_recipients_group_menu'], true);

$oPage->setContent([$oMenu, $sTemplate]);
$oPage->render();
