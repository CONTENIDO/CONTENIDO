<?php

/**
 * This file contains the left top frame backend page for rights management.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$oUser = new cApiUser($auth->auth["uid"]);
if (!isset($elemperpage) || !is_numeric($elemperpage) || $elemperpage < 0) {
    $elemperpage = $oUser->getProperty("itemsperpage", $area);
    $_REQUEST['elemperpage'] = $elemperpage;
    if ((int) $elemperpage <= 0) {
        $oUser->setProperty("itemsperpage", $area, 25);
        $elemperpage = 25;
        $_REQUEST['elemperpage'] = 25;
    }
} else {
    $oUser->setProperty("itemsperpage", $area, $elemperpage);
    $_REQUEST['elemperpage'] = $elemperpage;
}

// The following lines unset all right objects since I don't know (or I was unable
// to find out) if they are global and/or session variables - so if you are
// switching between groups and user management, we are safe.
unset($right_list);
unset($rights_list_old);
unset($rights_perms);
$right_list = "";
$rights_list_old = "";
$rights_perms = "";

$tpl->set('s', 'ID', 'restrict');
$tpl->set('s', 'CLASS', 'text_medium');
$tpl->set('s', 'OPTIONS', '');

$tpl2 = new cTemplate();
$tpl2->set('s', 'NAME', 'restrict');
$tpl2->set('s', 'CLASS', 'text_medium');
$tpl2->set('s', 'OPTIONS', 'onchange="userChangeRestriction()"');

$limit = array(
    "2" => i18n("All"),
    "1" => i18n("Frontend only"),
    "3" => i18n("Backend only")
);

foreach ($limit as $key => $value) {
    $selected = ($restrict == $key) ? "selected" : "";
    $tpl2->set('d', 'VALUE', $key);
    $tpl2->set('d', 'CAPTION', $value);
    $tpl2->set('d', 'SELECTED', $selected);
    $tpl2->next();
}

$select = $tpl2->generate($cfg["path"]["templates"] . $cfg['templates']['generic_select'], true);

$tpl->set('s', 'ACTION', '');

$tmp_mstr = '<div class="leftTopAction">
              <a class="addfunction" href="javascript:Con.multiLink(\'%s\', \'%s\')">%s</a></div>';
$area = "user";
$mstr = sprintf($tmp_mstr, 'right_bottom', $sess->url("main.php?area=user_create&frame=4"), i18n("Create user"));

if ($perm->have_perm_area_action('user_create', "user_createuser")) {
    $tpl->set('s', 'NEWUSER', $mstr);
} else {
    $tpl->set('s', 'NEWUSER', '');
}
$tpl->set('s', 'CAPTION', '');

/*
 * List Options
 */
$aSortByOptions = array("username" => i18n("User name"), "realname" => i18n("Name"));

$aSortOrderOptions = array("asc" => i18n("Ascending"), "desc" => i18n("Descending"));

$listOptionId = "listoption";
$tpl->set('s', 'LISTOPLINK', $listOptionId);
$oListOptionRow = new cGuiFoldingRow("5498dbba-ed4a-4618-8e49-3a3635396e22", i18n("List options"), $listOptionId);
$oListOptionRow->setExpanded('true');
$oSelectItemsPerPage = new cHTMLSelectElement("elemperpage");
$oSelectItemsPerPage->autoFill(array(25 => 25, 50 => 50, 75 => 75, 100 => 100));
$oSelectItemsPerPage->setDefault($_REQUEST["elemperpage"]);

$oSelectSortBy = new cHTMLSelectElement("sortby");
$oSelectSortBy->autoFill($aSortByOptions);
$oSelectSortBy->setDefault($_REQUEST["sortby"]);

$oSelectSortOrder = new cHTMLSelectElement("sortorder");
$oSelectSortOrder->autoFill($aSortOrderOptions);
$oSelectSortOrder->setDefault($_REQUEST["sortorder"]);

$oTextboxFilter = new cHTMLTextbox("filter", $_REQUEST["filter"], 20);
$oTextboxFilter->setStyle('width:114px;');

$tplFilter = new cTemplate();
$tplFilter->set("s", "AREA", $area);
$tplFilter->set("s", "ITEMS_PER_PAGE", $oSelectItemsPerPage->render());
$tplFilter->set("s", "SORT_BY", $oSelectSortBy->render());
$tplFilter->set("s", "SORT_ORDER", $oSelectSortOrder->render());
$tplFilter->set("s", "FILTER_USER", $oTextboxFilter->render());
$oListOptionRow->setContentData($tplFilter->generate($cfg["path"]["templates"] . $cfg["templates"]["rights_left_top_filter"], true));
$tpl->set('s', 'LISTOPTIONS', $oListOptionRow->render());

/*
 * Paging
 */
$cApiUserCollection = new cApiUserCollection;
$cApiUserCollection->query();
$iItemCount = $cApiUserCollection->count();

$oPagerLink = new cHTMLLink;
$oPagerLink->setLink("main.php");
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setCustom("elemperpage", $elemperpage);
$oPagerLink->setCustom("filter", $_REQUEST["filter"]);
$oPagerLink->setCustom("sortby", $_REQUEST["sortby"]);
$oPagerLink->setCustom("sortorder", $_REQUEST["sortorder"]);
$oPagerLink->setCustom("frame", 2);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);

$pagerID = "pager";
$oPager = new cGuiObjectPager("44b41691-0dd4-443c-a594-66a8164e25fd", $iItemCount, $elemperpage, $page, $oPagerLink, "page", $pagerID);
$oPager->setExpanded('true');
$tpl->set('s', 'PAGINGLINK', $pagerID);
$tpl->set('s', 'PAGING', $oPager->render());


$tpl->generate($cfg['path']['templates'] . $cfg['templates']['rights_left_top']);
