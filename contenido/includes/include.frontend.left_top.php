<?php

/**
 * This file contains the left top frame backend page in frontend user management.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $auth, $area, $cfg, $client, $perm, $sess;

// Display critical error if no valid client is selected
if ($client < 1) {
    $oPage = new cGuiPage('frontend_left_top');
    $oPage->displayCriticalError(i18n("No Client selected"));
    $oPage->render();
    return;
}

$tpl = new cTemplate();

$oUser = new cApiUser($auth->auth["uid"]);

$buttonRow = '';

$requestElemPerPage = cSecurity::toInteger($_REQUEST['elemperpage'] ?? '0');
$requestPage = cSecurity::toInteger($_REQUEST['page'] ?? '0');
$requestFilter = $_REQUEST['filter'] ?? '';
$requestSortBy = $_REQUEST['sortby'] ?? '';
$requestSortOrder = $_REQUEST['sortorder'] ?? '';
$requestSearchIn = $_REQUEST['searchin'] ?? '';
$requestRestrictGroup = $_REQUEST['restrictgroup'] ?? '';

// Set default values
if ($requestElemPerPage <= 0) {
    $requestElemPerPage = cSecurity::toInteger($oUser->getProperty("itemsperpage", $area));
}
if (!is_numeric($requestElemPerPage)) {
    $requestElemPerPage = 25;
}
$oUser->setProperty("itemsperpage", $area, $requestElemPerPage);

if ($requestPage <= 0 || $requestElemPerPage == 0) {
    $requestPage = 1;
}

$aFieldsToSearch = [
    "--all--" => i18n("-- All fields --"),
    "username" => i18n("Username")
];
$aFieldsToSort = [
    "username" => i18n("Username"),
    "created" => i18n("Created"),
    "modified" => i18n("Modified")
];

$aFieldSources = [
    "username" => "base",
    "created" => "created",
    "modified" => "modified"
];

$bUsePlugins = getEffectiveSetting("frontendusers", "pluginsearch", "true");
$bUsePlugins = ($bUsePlugins == "false") ? false : true;

$oFEUsers = new cApiFrontendUserCollection();

$databaseFields = [];

// query the collection and fetch the first available item
$oFEUsers->query();
$sampleItem = $oFEUsers->next();

// fetch available fields from database item
if ($sampleItem) {
    $databaseFields = array_keys($sampleItem->toArray());
}

if ($bUsePlugins == true && cHasPlugins('frontendusers')) {
    cIncludePlugins('frontendusers');

    $_sValidPlugins = getEffectiveSetting("frontendusers", "pluginsearch_valid_plugins", '');
    $_aValidPlugins = [];

    if (cString::getStringLength($_sValidPlugins) > 0) {
        $_aValidPlugins = explode(',', $_sValidPlugins);
    }

    $_iCountValidPlugins = sizeof($_aValidPlugins);

    foreach ($cfg['plugins']['frontendusers'] as $plugin) {
        if ($_iCountValidPlugins == 0 || in_array($plugin, $_aValidPlugins)) {
            if (function_exists('frontendusers_' . $plugin . '_wantedVariables')
                && function_exists('frontendusers_' . $plugin . '_canonicalVariables')
                && function_exists('frontendusers_' . $plugin . '_getvalue')) {

                $aVariableNames = call_user_func('frontendusers_' . $plugin . '_canonicalVariables');

                if (is_array($aVariableNames)) {
                    $aTmp = array_merge($aFieldsToSearch, $aVariableNames);
                    $aFieldsToSearch = $aTmp;

                    $aTmp2 = array_merge($aFieldsToSort, $aVariableNames);
                    $aFieldsToSort = $aTmp2;

                    foreach ($aVariableNames as $sVariableName => $name) {
                        if (in_array($sVariableName, $databaseFields)) {
                            $aFieldSources[$sVariableName] = $plugin;
                            $aFieldsToSort[$sVariableName] = $name;
                            $aFieldsToSearch[$sVariableName] = $name;
                        }
                    }
                }
            }
        }
    }
}

$aSortOrderOptions = [
    "asc" => i18n("Ascending"),
    "desc" => i18n("Descending")
];

/*
 * Buttons
 */

// Init view by javascript (decide which tab is activated)
$imgUserId = 'img_user';
$tpl->set('s', 'IUSER', $imgUserId);
$buttonRow .= '
<a class="selectuserfunction" href="javascript:void(0)" data-action="switch_frontenduser">
    <img onmouseover="hoverEffect(\'' . $imgUserId . '\', \'in\')" onmouseout="hoverEffect(\'' . $imgUserId . '\', \'out\')" alt="' . i18n("Frontend users") . '" title="' . i18n("Frontend users") . '" id="' . $imgUserId . '" src="' . $cfg['path']['images'] . 'users.gif">
</a>';

// Frontend Groups
$imgGroupId = 'img_group';
$tpl->set('s', 'IGROUP', $imgGroupId);
$buttonRow .= '
<a class="selectgroupfunction" href="javascript:void(0)" data-action="switch_frontendgroup">
    <img onmouseover="hoverEffect(\'' . $imgGroupId . '\', \'in\')" onmouseout="hoverEffect(\'' . $imgGroupId . '\', \'out\')" alt="' . i18n("Frontend groups") . '" title="' . i18n("Frontend groups") . '" id="' . $imgGroupId . '" src="' . $cfg['path']['images'] . 'groups.gif">
</a>
';

$tpl->set('s', 'BUTTONROW', $buttonRow);

if (isset($_GET['view']) && $_GET['view'] == $imgGroupId) {
    $tpl->set('s', 'IINIT', $imgGroupId);
} else {
    $tpl->set('s', 'IINIT', $imgUserId);
}

/*
 * Users Actions
 */
$actionLink = "actionlink";
$sActionUuid = '28cf9b31-e6d7-4657-a9a7-db31478e7a5c';

$oActionRow = new cGuiFoldingRow($sActionUuid, i18n("Actions"), $actionLink);
if (isset($_GET['actionrow']) && $_GET['actionrow'] == 'collapsed') {
    $oActionRow->setExpanded(false);
    $oUser->setProperty("expandstate", $sActionUuid, 'false');
} elseif (isset($_GET['actionrow']) && $_GET['actionrow'] == 'expanded') {
    $oActionRow->setExpanded(true);
    $oUser->setProperty("expandstate", $sActionUuid, 'true');
}

$tpl->set('s', 'ACTIONLINK', $actionLink);
$oLink = new cHTMLLink();
if ((int)$client > 0) {
    if ($perm->have_perm_area_action($area, "frontend_create")) {
        $oLink->setMultiLink("frontend", "", "frontend", "frontend_create");
        $oLink->setContent(i18n("Create user"));
    } else {
        $oLink->setLink("#");
        $oLink->setContent(i18n("No permission to create users"));
    }
} else {
    $oLink->setLink('');
    $oLink->setContent(i18n("No Client selected"));
}
$oLink->setClass("con_func_button addfunction");
$oLink->setStyle('margin-left: 17px;margin-top:5px');
$oActionRow->setContentData($oLink->render());

/*
 * Users List Options
 */
$sListOptionId = 'f081b6ab-370d-4fd8-984f-6b38590fe48b';
$listOptionLink = "listoptionlink";
$oListOptionRow = new cGuiFoldingRow($sListOptionId, i18n("List options"), $listOptionLink);
$oListOptionRow->setExpanded(true);

if (isset($_GET['filterrow']) && $_GET['filterrow'] == 'collapsed') {
    $oActionRow->setExpanded(false);
    $oUser->setProperty("expandstate", $sListOptionId, 'false');
} elseif (isset($_GET['filterrow']) && $_GET['filterrow'] == 'expanded') {
    $oActionRow->setExpanded(true);
    $oUser->setProperty("expandstate", $sListOptionId, 'true');
}

$tpl->set('s', 'LISTOPTIONLINK', $listOptionLink);
$oSelectItemsPerPage = new cHTMLSelectElement("elemperpage");
$oSelectItemsPerPage->autoFill([
    25 => 25,
    50 => 50,
    75 => 75,
    100 => 100
]);
$oSelectItemsPerPage->setDefault($requestElemPerPage);

asort($aFieldsToSort);
asort($aFieldsToSearch);

$oSelectSortBy = new cHTMLSelectElement("sortby");
$oSelectSortBy->autoFill($aFieldsToSort);
$oSelectSortBy->setDefault($requestSortBy);

$oSelectSortOrder = new cHTMLSelectElement("sortorder");
$oSelectSortOrder->autoFill($aSortOrderOptions);
$oSelectSortOrder->setDefault($requestSortOrder);

$oSelectSearchIn = new cHTMLSelectElement("searchin");
$oSelectSearchIn->autoFill($aFieldsToSearch);
$oSelectSearchIn->setDefault($requestSearchIn);

$fegroups = new cApiFrontendGroupCollection();
$fegroups->setWhere('idclient', $client);
$fegroups->addResultFields(['idfrontendgroup', 'groupname']);
$fegroups->query();
$fetchFields = ['idfrontendgroup' => 'idfrontendgroup', 'groupname' => 'groupname'];

$aFEGroups = [
    "--all--" => i18n("-- All Groups --")
];

foreach ($fegroups->fetchTable($fetchFields) as $entry) {
    $aFEGroups[$entry['idfrontendgroup']] = $entry['groupname'];
}

$oSelectRestrictGroup = new cHTMLSelectElement("restrictgroup");
$oSelectRestrictGroup->autoFill($aFEGroups);
$oSelectRestrictGroup->setDefault($requestRestrictGroup);
$oTextboxFilter = new cHTMLTextbox("filter", $requestFilter, 20);
$oTextboxFilter->setClass("text_medium");

$tplFilter = new cTemplate();
$tplFilter->set("s", "ITEMS_PER_PAGE", $oSelectItemsPerPage->render());
$tplFilter->set("s", "SORT_BY", $oSelectSortBy->render());
$tplFilter->set("s", "SORT_ORDER", $oSelectSortOrder->render());
$tplFilter->set("s", "FILTER_GROUP", $oSelectRestrictGroup->render());
$tplFilter->set("s", "FILTER_USER", $oTextboxFilter->render());
$tplFilter->set("s", "SEARCH_IN", $oSelectSearchIn->render());
$oListOptionRow->setContentData($tplFilter->generate($cfg['path']['templates'] . $cfg['templates']['frontend_left_top_filter'], true));

$oFEUsers = new cApiFrontendUserCollection();
$oFEUsers->setWhere("cApiFrontendUserCollection.idclient", $client);

/*
 * Process request parameters
 */

if (cString::getStringLength($requestFilter) > 0) {
    if ($requestSearchIn == "--all--" || $requestSearchIn == "") {
        foreach ($aFieldSources as $variableName => $source) {
            $oFEUsers->setWhereGroup("filter", $variableName, $requestFilter, "LIKE");
        }

        $oFEUsers->setInnerGroupCondition("filter", "OR");
    } else {
        $searchField = 'username';
        if (in_array($requestSearchIn, $databaseFields)) {
            $searchField = $requestSearchIn;
        }

        $oFEUsers->setWhere("cApiFrontendUserCollection." . $searchField, $requestFilter, "LIKE");
    }
}

if ($requestRestrictGroup != "" && $requestRestrictGroup != "--all--") {
    $oFEUsers->link("cApiFrontendGroupMemberCollection");
    $oFEUsers->setWhere("cApiFrontendGroupMemberCollection.idfrontendgroup", $requestRestrictGroup);
}

$oFEUsers->query();
$iItemCount = $oFEUsers->count();

/*
 * Users Paging
 */
$pagingLink = "paginglink";
$tpl->set('s', 'PAGINGLINK', $pagingLink);

$oPagerLink = new cHTMLLink();
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setLink("main.php");
$oPagerLink->setCustom("elemperpage", $requestElemPerPage);
$oPagerLink->setCustom("filter", $requestFilter);
$oPagerLink->setCustom("sortby", $requestSortBy);
$oPagerLink->setCustom("sortorder", $requestSortOrder);
$oPagerLink->setCustom("searchin", $requestSearchIn);
$oPagerLink->setCustom("restrictgroup", $requestRestrictGroup);
$oPagerLink->setCustom("frame", 2);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);

$oPager = new cGuiObjectPager("25c6a67d-a3f1-4ea4-8391-446c131952c9", $iItemCount, $requestElemPerPage, $requestPage, $oPagerLink, "page", $pagingLink);
$oPager->setExpanded(true);

/*
 * Groups create Groups
 */
$link = new cHTMLLink();
$menu = new cGuiMenu();
if ((int)$client > 0) {
    if ($perm->have_perm_area_action("frontendgroups", "frontendgroup_create")) {
        $link->setLink('javascript:Con.multiLink(\'right_bottom\', \'' . $sess->url("main.php?area=frontendgroups&frame=4&action=frontendgroup_create") . '\');');
        $menu->setTitle("-2", i18n("Create group"));
    } else {
        $link->setLink('#');
        $menu->setTitle("-2", i18n("No permission to create groups"));
    }
} else {
    $link->setLink('');
    $menu->setTitle("-2", i18n("No Client selected"));
}
$menu->setImage("-2", $cfg['path']['images'] . "folder_new.gif");
$menu->setLink("-2", $link);
$menu->setLink("10", $link);
$menu->setTitle("10", "");
$menu->setImage("10", "");
$menu->setRowmark(false);

/*
 * Container Users
 */
$containerUsersId = 'cont_users';
$containerUsers = '<div id="' . $containerUsersId . '">';
$containerUsers .= '<table class="foldingrow">';
$containerUsers .= $oActionRow->render();
$containerUsers .= $oListOptionRow->render();
$containerUsers .= $oPager->render();
$containerUsers .= '</table>';
$containerUsers .= '</div>';
$tpl->set('s', 'CUSERS', $containerUsers);
$tpl->set('s', 'ID_USERS', $containerUsersId);

/*
 * Container Groups
 */
$containerGroupsId = 'cont_groups';
$containerGroups = '<div id="' . $containerGroupsId . '"';
$containerGroups .= '<span>' . $menu->render(false) . '</span>';
$containerGroups .= '</div>';
$tpl->set('s', 'CGROUPS', $containerGroups);
$tpl->set('s', 'ID_GROUPS', $containerGroupsId);

$tpl->set('s', 'PAGE', $requestPage);
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['frontend_left_top']);
