<?php

/**
 * This file contains the backend page for creating modules.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $tpl;

// Display critical error if client or language does not exist
$client = cSecurity::toInteger(cRegistry::getClientId());
$lang = cSecurity::toInteger(cRegistry::getLanguageId());
if (($client < 1 || !cRegistry::getClient()->isLoaded()) || ($lang < 1 || !cRegistry::getLanguage()->isLoaded())) {
    $message = $client && !cRegistry::getClient()->isLoaded() ? i18n('No Client selected') : i18n('No language selected');
    $oPage = new cGuiPage("mod_new");
    $oPage->displayCriticalError($message);
    $oPage->render();
    return;
}

$auth = cRegistry::getAuth();
$perm = cRegistry::getPerm();
$sess = cRegistry::getSession();
$area = cRegistry::getArea();
$cfg = cRegistry::getConfig();


$elemPerPage = cSecurity::toInteger($_REQUEST['elemperpage'] ?? '0');
$page = cSecurity::toInteger($_REQUEST['page'] ?? '1');
$sortby = cSecurity::toString($_REQUEST['sortby'] ?? '');
$sortorder = cSecurity::toString($_REQUEST['sortorder'] ?? '');
$filter = cSecurity::toString($_REQUEST['filter'] ?? '');
$filterType = cSecurity::toString($_REQUEST['filtertype'] ?? '');
$searchIn = cSecurity::toString($_REQUEST['searchin'] ?? '');

// no value found in request for items per page -> get form db or set default
$oUser = new cApiUser($auth->auth['uid']);
if ($elemPerPage < 0) {
    $elemPerPage = $oUser->getProperty('itemsperpage', $area);
}
unset($oUser);

if ($page <= 0 || $elemPerPage == 0) {
    $page = 1;
}

$tpl->reset();

$strActions = '';
$strAddLink = '';
$strSyncLink = '';

// New module link
if ($perm->have_perm_area_action("mod_edit", "mod_new")) {
    $str = sprintf(
        '<a class="addfunction" href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>',
        'right_top', $sess->url("main.php?area=mod_edit&frame=3"),
        'right_bottom', $sess->url("main.php?area=mod_edit&action=mod_new&frame=4"),
        i18n("New module")
    );
    $strAddLink = '<div class="top_left_action">' . $str . '</div>';
} else {
    $strAddLink = '<div class="top_left_action"><a class="addfunction_disabled" href="#">' . i18n("No permission to create modules") . '</a> </div>';
}
if ($perm->have_perm_area_action("mod_edit", "mod_sync")) {
    $str = sprintf(
        '<a class="syncronizefunction" href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>',
        'right_top', $sess->url("main.php?area=mod_edit&frame=3"),
        'right_bottom', $sess->url("main.php?area=mod_edit&action=mod_sync&frame=4"),
        i18n("Synchronize modules")
    );
    $strSyncLink = '<div class="top_left_action top_left_action_next">' . $str . '</div>';
} else {
    $strSyncLink = '<div class="top_left_action top_left_action_next"><a class="syncronizefunction_disabled" href="#">' . i18n("No permission to synchronize modules") . '</a> </div>';
}

// List Options
$aSortByOptions = ["name" => i18n("Name"), "type" => i18n("Type")];
$aSortOrderOptions = ["asc" => i18n("Ascending"), "desc" => i18n("Descending")];
$listOpLink = "listoptions";
$oListOptionRow = new cGuiFoldingRow("e9ddf415-4b2d-4a75-8060-c3cd88b6ff98", i18n("List options"), $listOpLink);
$oSelectItemsPerPage = new cHTMLSelectElement("elemperpage");
$oSelectItemsPerPage->autoFill([0 => i18n("-- All --"), 5 => 5, 25 => 25, 50 => 50, 75 => 75, 100 => 100]);
$oSelectItemsPerPage->setDefault($elemPerPage);
$oSelectSortBy = new cHTMLSelectElement("sortby");
$oSelectSortBy->autoFill($aSortByOptions);
$oSelectSortBy->setDefault($sortby);
$oSelectSortOrder = new cHTMLSelectElement("sortorder");
$oSelectSortOrder->autoFill($aSortOrderOptions);
$oSelectSortOrder->setDefault($sortorder);

$oSelectSearchIn = new cHTMLSelectElement("searchin");
// CON-1910
$oSelectSearchIn->autoFill(['' => i18n("-- All --"),
    'name' => i18n("Module name"),
    'description' => i18n("Description"),
    'type' => i18n("Type"),
    'input' => i18n("Input"),
    'output' => i18n("Output")
]);

$oSelectSearchIn->setDefault($searchIn);

// build list with filter types
$aFilterType = [];
$aFilterType["--all--"] = i18n("-- All --");
$aFilterType["--wotype--"] = i18n("-- Without type --");

$oModuleColl = new cApiModuleCollection();
$aTypes = $oModuleColl->getAllTypesByIdclient($client);
foreach ($aTypes as $type) {
    if (trim($type) != "") {
        $aFilterType[$type] = $type;
    }
}

$oSelectTypeFilter = new cHTMLSelectElement("filtertype");
$oSelectTypeFilter->autoFill($aFilterType);
$oSelectTypeFilter->setDefault($filterType);
$oTextboxFilter = new cHTMLTextbox("filter", stripslashes($filter), 15);
$oTextboxFilter->setClass('text_small vAlignMiddle');

$tplModFilter = new cTemplate();
$tplModFilter->set("s", "PAGE", $page);
$tplModFilter->set("s", "ITEMS_PER_PAGE", $oSelectItemsPerPage->render());
$tplModFilter->set("s", "SORT_BY", $oSelectSortBy->render());
$tplModFilter->set("s", "SORT_ORDER", $oSelectSortOrder->render());
$tplModFilter->set("s", "TYPE_FILTER", $oSelectTypeFilter->render());
$tplModFilter->set("s", "SEARCH_FOR", $oTextboxFilter->render());
$tplModFilter->set("s", "SEARCH_IN", $oSelectSearchIn->render());
$oListOptionRow->setContentData($tplModFilter->generate($cfg["path"]["templates"] . $cfg["templates"]["mod_left_top_filter"], true));

// Pager
$cApiModuleCollection = new cApiModuleCollection();
$cApiModuleCollection->setWhere("idclient", $client);

$cApiModuleCollection->query();
$iItemCount = $cApiModuleCollection->count();

$oPagerLink = new cHTMLLink();
$pagerl = "pagerlink";
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setLink("main.php");
$oPagerLink->setCustom("elemperpage", $elemPerPage);
$oPagerLink->setCustom("filter", stripslashes($filter));
$oPagerLink->setCustom("sortby", $sortby);
$oPagerLink->setCustom("sortorder", $sortorder);
$oPagerLink->setCustom("frame", 2);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);
$oPager = new cGuiObjectPager("02420d6b-a77e-4a97-9395-7f6be480f497", $iItemCount, $elemPerPage, $page, $oPagerLink, "page", $pagerl);

$strActions = $strAddLink . $strSyncLink . '<table class="generic" border="0" cellspacing="0" cellpadding="0" width="100%">' . $oListOptionRow->render() . $oPager->render() . '</table>';

$tpl->set('s', 'PAGINGLINK', $pagerl);
$tpl->set('s', 'ACTION', $strActions);
$tpl->set('s', 'LISTOPLINK', $listOpLink);

// generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['mod_left_top']);
