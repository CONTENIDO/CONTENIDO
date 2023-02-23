<?php

/**
 * This file contains the left top frame backend page for rights management.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $tpl;

$auth = cRegistry::getAuth();
$perm = cRegistry::getPerm();
$sess = cRegistry::getSession();
$area = cRegistry::getArea();
$cfg = cRegistry::getConfig();

$page = isset($_REQUEST['page']) ? abs(cSecurity::toInteger($_REQUEST['page'])) : 1;
$elemPerPage = cSecurity::toInteger($_REQUEST['elemperpage'] ?? '0');
$sortby = cSecurity::toString($_REQUEST['sortby'] ?? '');
$sortorder = cSecurity::toString($_REQUEST['sortorder'] ?? '');
$filter = cSecurity::toString($_REQUEST['filter'] ?? '');
$restrict = cSecurity::toString($_REQUEST['restrict'] ?? '');

$oUser = new cApiUser($auth->auth["uid"]);
if ($elemPerPage < 0) {
    $elemPerPage = $oUser->getProperty("itemsperpage", $area);
    if ((int) $elemPerPage <= 0) {
        $oUser->setProperty("itemsperpage", $area, 25);
        $elemPerPage = 25;
    }
} else {
    $oUser->setProperty("itemsperpage", $area, $elemPerPage);
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

$limit = [
    "2" => i18n("All"),
    "1" => i18n("Frontend only"),
    "3" => i18n("Backend only")
];

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
$aSortByOptions = ["username" => i18n("User name"), "realname" => i18n("Name")];

$aSortOrderOptions = ["asc" => i18n("Ascending"), "desc" => i18n("Descending")];

$listOptionId = "listoption";
$tpl->set('s', 'LISTOPLINK', $listOptionId);
$oListOptionRow = new cGuiFoldingRow("5498dbba-ed4a-4618-8e49-3a3635396e22", i18n("List options"), $listOptionId);
$oListOptionRow->setExpanded('true');
$oSelectItemsPerPage = new cHTMLSelectElement("elemperpage");
$oSelectItemsPerPage->autoFill([25 => 25, 50 => 50, 75 => 75, 100 => 100]);
$oSelectItemsPerPage->setDefault($elemPerPage);

$oSelectSortBy = new cHTMLSelectElement("sortby");
$oSelectSortBy->autoFill($aSortByOptions);
$oSelectSortBy->setDefault($sortby);

$oSelectSortOrder = new cHTMLSelectElement("sortorder");
$oSelectSortOrder->autoFill($aSortOrderOptions);
$oSelectSortOrder->setDefault($sortorder);

$oTextboxFilter = new cHTMLTextbox("filter", $filter, 20);
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
$oPagerLink->setCustom("elemperpage", $elemPerPage);
$oPagerLink->setCustom("filter", $filter);
$oPagerLink->setCustom("sortby", $sortby);
$oPagerLink->setCustom("sortorder", $sortorder);
$oPagerLink->setCustom("frame", 2);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);

$pagerID = "pager";
$oPager = new cGuiObjectPager("44b41691-0dd4-443c-a594-66a8164e25fd", $iItemCount, $elemPerPage, $page, $oPagerLink, "page", $pagerID);
$oPager->setExpanded('true');
$tpl->set('s', 'PAGINGLINK', $pagerID);
$tpl->set('s', 'PAGING', $oPager->render());


$tpl->generate($cfg['path']['templates'] . $cfg['templates']['rights_left_top']);
