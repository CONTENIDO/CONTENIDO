<?php
/**
 * This file contains the backend page for creating modules.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Olaf Niemann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$oUser = new cApiUser($auth->auth["uid"]);
if (!isset($_REQUEST["elemperpage"]) || !is_numeric($_REQUEST['elemperpage']) || $_REQUEST['elemperpage'] < 0) {
    $_REQUEST["elemperpage"] = $oUser->getProperty("itemsperpage", $area);
}

$tpl->reset();

// New module link
$str = '';
if ((int) $client > 0) {
    if ($perm->have_perm_area_action("mod_edit", "mod_new")) {
        $str = '<div class="leftTopAction"><a class="addfunction" target="right_bottom" href="' . $sess->url("main.php?area=mod_edit&frame=4&action=mod_new") . '">' . i18n("New module") . '</a> </div>';
    } else {
        $str = '<div class="leftTopAction"><a class="addfunction_disabled" href="#">' . i18n("No permission to create modules") . '</a> </div>';
    }
    if ($perm->have_perm_area_action("mod_edit", "mod_sync")) {
        $strSync = '<div class="leftTopAction leftTopActionNext"><a class="syncronizefunction" target="right_bottom" href="' . $sess->url("main.php?area=mod_edit&frame=4&action=mod_sync") . '">' . i18n("Synchronize modules") . '</a></div>';
    } else {
        $strSync = '<div class="leftTopAction leftTopActionNext"><a class="syncronizefunction_disabled" href="#">' . i18n("No permission to synchronize modules") . '</a> </div>';
    }
} else {
    $str = '<div class="leftTopAction">' . i18n('No client selected') . '</div>';
}

// Only show other options, if there is a active client
if ((int) $client > 0) {
    // List Options
    $aSortByOptions = array("name" => i18n("Name"), "type" => i18n("Type"));
    $aSortOrderOptions = array("asc" => i18n("Ascending"), "desc" => i18n("Descending"));
    $listoplink = "listoptions";
    $oListOptionRow = new cGuiFoldingRow("e9ddf415-4b2d-4a75-8060-c3cd88b6ff98", i18n("List options"), $listoplink);
    $tpl->set('s', 'LISTOPLINK', $listoplink);
    $oSelectItemsPerPage = new cHTMLSelectElement("elemperpage");
    $oSelectItemsPerPage->autoFill(array(0 => i18n("-- All --"), 5 => 5, 25 => 25, 50 => 50, 75 => 75, 100 => 100));
    $oSelectItemsPerPage->setDefault($_REQUEST["elemperpage"]);
    $oSelectSortBy = new cHTMLSelectElement("sortby");
    $oSelectSortBy->autoFill($aSortByOptions);
    $oSelectSortBy->setDefault($_REQUEST["sortby"]);
    $oSelectSortOrder = new cHTMLSelectElement("sortorder");
    $oSelectSortOrder->autoFill($aSortOrderOptions);
    $oSelectSortOrder->setDefault($_REQUEST["sortorder"]);

    $oSelectSearchIn = new cHTMLSelectElement("searchin");
    $oSelectSearchIn->autoFill(array('' => i18n("-- All --"),
        'name' => i18n("Module name"),
        'description' => i18n("Description"),
        'type' => i18n("Type"),
        'input' => i18n("Input"),
        'output' => i18n("Output")));

    $oSelectSearchIn->setDefault($_REQUEST["searchin"]);

    // build list with filter types
    $aFilterType = array();
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
    $oSelectTypeFilter->setDefault($_REQUEST["filtertype"]);
    $oTextboxFilter = new cHTMLTextbox("filter", stripslashes($_REQUEST["filter"]), 15);
    $oTextboxFilter->setClass('text_small vAlignMiddle');

    $tplModFilter = new cTemplate();
    $tplModFilter->set("s", "PAGE", $_REQUEST["page"]);
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
    $oPagerLink->setCustom("elemperpage", $elemperpage);
    $oPagerLink->setCustom("filter", stripslashes($_REQUEST["filter"]));
    $oPagerLink->setCustom("sortby", $_REQUEST["sortby"]);
    $oPagerLink->setCustom("sortorder", $_REQUEST["sortorder"]);
    $oPagerLink->setCustom("frame", 2);
    $oPagerLink->setCustom("area", $area);
    $oPagerLink->enableAutomaticParameterAppend();
    $oPagerLink->setCustom("contenido", $sess->id);
    $oPager = new cGuiObjectPager("02420d6b-a77e-4a97-9395-7f6be480f497", $iItemCount, $_REQUEST["elemperpage"], $_REQUEST["page"], $oPagerLink, "page", $pagerl);

    $tpl->set('s', 'PAGINGLINK', $pagerl);

    $tpl->set('s', 'ACTION', $str . $strSync . '<table class="generic" border="0" cellspacing="0" cellpadding="0" width="100%">' . $oListOptionRow->render() . $oPager->render() . '</table>');
} else {
    $tpl->set('s', 'PAGINGLINK', '');
    $tpl->set('s', 'ACTION', $str);
    $tpl->set('s', 'LISTOPLINK', '');
}

// generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['mod_left_top']);

?>