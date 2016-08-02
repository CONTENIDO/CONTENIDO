<?php

/**
 * This file contains the left top frame backend page in frontend user management.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$tpl = new cTemplate();

$user = new cApiUser($auth->auth["uid"]);

// Set default values
$oUser = new cApiUser($auth->auth["uid"]);
if (!isset($_REQUEST["elemperpage"]) || !is_numeric($_REQUEST['elemperpage']) || $_REQUEST['elemperpage'] <= 0) {
    $_REQUEST["elemperpage"] = $oUser->getProperty("itemsperpage", $area);
}
if (!is_numeric($_REQUEST['elemperpage'])) {
    $_REQUEST['elemperpage'] = 25;
}
$oUser->setProperty("itemsperpage", $area, $_REQUEST["elemperpage"]);
unset($oUser);

if (!isset($_REQUEST["page"]) || !is_numeric($_REQUEST['page']) || $_REQUEST['page'] <= 0 || $_REQUEST["elemperpage"] == 0) {
    $_REQUEST["page"] = 1;
}

$aFieldsToSearch = array(
    "--all--" => i18n("-- All fields --"),
    "username" => i18n("Username")
);
$aFieldsToSort = array(
    "username" => i18n("Username"),
    "created" => i18n("Created"),
    "modified" => i18n("Modified")
);

$aFieldSources = array();
$aFieldSources["username"] = "base";
$aFieldSources["created"] = "created";
$aFieldSources["modified"] = "modified";

$bUsePlugins = getEffectiveSetting("frontendusers", "pluginsearch", "true");
$bUsePlugins = ($bUsePlugins == "false") ? false : true;

if (is_array($cfg['plugins']['frontendusers'])) {
    foreach ($cfg['plugins']['frontendusers'] as $plugin) {
        plugin_include("frontendusers", $plugin . "/" . $plugin . ".php");
    }
}

$oFEUsers = new cApiFrontendUserCollection();

$databaseFields = array();

// query the collection and fetch the first available item
$oFEUsers->query();
$sampleItem = $oFEUsers->next();

// fetch available fields from database item
if ($sampleItem) {
    $databaseFields = array_keys($sampleItem->toArray());
}

if ($bUsePlugins == true && is_array($cfg['plugins']['frontendusers'])) {
    foreach ($cfg['plugins']['frontendusers'] as $plugin) {
        plugin_include("frontendusers", $plugin . "/" . $plugin . ".php");
    }

    $_sValidPlugins = getEffectiveSetting("frontendusers", "pluginsearch_valid_plugins", '');
    $_aValidPlugins = array();

    if (strlen($_sValidPlugins) > 0) {
        $_aValidPlugins = explode(',', $_sValidPlugins);
    }

    $_iCountValidPlugins = sizeof($_aValidPlugins);

    foreach ($cfg['plugins']['frontendusers'] as $plugin) {
        if ($_iCountValidPlugins == 0 || in_array($plugin, $_aValidPlugins)) {
            if (function_exists("frontendusers_" . $plugin . "_wantedVariables")
                && function_exists("frontendusers_" . $plugin . "_canonicalVariables")
                && function_exists("frontendusers_" . $plugin . "_getvalue")) {

                $aVariableNames = call_user_func("frontendusers_" . $plugin . "_canonicalVariables");

                if (is_array($aVariableNames)) {
                    $aTmp = array_merge($aFieldsToSearch, $aVariableNames);
                    $aFieldsToSearch = $aTmp;

                    $aTmp2 = array_merge($aFieldsToSort, $aVariableNames);
                    $aFieldsToSort = $aTmp2;

                    foreach ($aVariableNames as $sVariableName => $name) {
                        if (in_array($databaseFields, $sVariableName)) {
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

$aSortOrderOptions = array(
    "asc" => i18n("Ascending"),
    "desc" => i18n("Descending")
);

/*
 * Buttons
 */
$userlink = new cHTMLLink();
$userlink->setCLink("frontend", 2, "");

$grouplink = new cHTMLLink();
$grouplink->setCLink("frontendgroups", 2, "");

$userlink = "javascript:execFilter(2);";
$grouplink = "javascript:Con.multiLink('left_bottom', 'main.php?area=frontendgroups&frame=2&action=&contenido=" . $sess->id . "')";

// Init view by javascript (decide which tab is activated)
$imgUserId = 'img_user';
$tpl->set('s', 'IUSER', $imgUserId);

$buttonRow .= '
<a class="selectuserfunction" href="' . $userlink . '" onclick="toggleContainer(\'' . $imgUserId . '\');">
    <img onmouseover="hoverEffect(\'' . $imgUserId . '\', \'in\')" onmouseout="hoverEffect(\'' . $imgUserId . '\', \'out\')" alt="' . i18n("Frontend users") . '" title="' . i18n("Frontend users") . '" id="' . $imgUserId . '" src="' . $cfg["path"]["images"] . 'users.gif">
</a>';

// Frontend Groups
$imgGroupId = 'img_group';
$tpl->set('s', 'IGROUP', $imgGroupId);
$buttonRow .= '
<a class="selectgroupfunction" href="' . $grouplink . '" onclick="toggleContainer(\'' . $imgGroupId . '\');">
    <img onmouseover="hoverEffect(\'' . $imgGroupId . '\', \'in\')" onmouseout="hoverEffect(\'' . $imgGroupId . '\', \'out\')" alt="' . i18n("Frontend groups") . '" title="' . i18n("Frontend groups") . '" id="' . $imgGroupId . '" src="' . $cfg["path"]["images"] . 'groups.gif">
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
    $user->setProperty("expandstate", $sActionUuid, 'false');
} else if (isset($_GET['actionrow']) && $_GET['actionrow'] == 'expanded') {
    $oActionRow->setExpanded(true);
    $user->setProperty("expandstate", $sActionUuid, 'true');
}

$tpl->set('s', 'ACTIONLINK', $actionLink);
$oLink = new cHTMLLink();
if ((int) $client > 0) {
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
$oLink->setClass("addfunction");
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
    $user->setProperty("expandstate", $sListOptionId, 'false');
} else if (isset($_GET['filterrow']) && $_GET['filterrow'] == 'expanded') {
    $oActionRow->setExpanded(true);
    $user->setProperty("expandstate", $sListOptionId, 'true');
}

$tpl->set('s', 'LISTOPTIONLINK', $listOptionLink);
$oSelectItemsPerPage = new cHTMLSelectElement("elemperpage");
$oSelectItemsPerPage->autoFill(array(
    25 => 25,
    50 => 50,
    75 => 75,
    100 => 100
));
$oSelectItemsPerPage->setDefault($_REQUEST["elemperpage"]);

asort($aFieldsToSort);
asort($aFieldsToSearch);

$oSelectSortBy = new cHTMLSelectElement("sortby");
$oSelectSortBy->autoFill($aFieldsToSort);
$oSelectSortBy->setDefault($_REQUEST["sortby"]);

$oSelectSortOrder = new cHTMLSelectElement("sortorder");
$oSelectSortOrder->autoFill($aSortOrderOptions);
$oSelectSortOrder->setDefault($_REQUEST["sortorder"]);

$oSelectSearchIn = new cHTMLSelectElement("searchin");
$oSelectSearchIn->autoFill($aFieldsToSearch);
$oSelectSearchIn->setDefault($_REQUEST["searchin"]);

$fegroups = new cApiFrontendGroupCollection();
$fegroups->setWhere("idclient", $client);
$fegroups->query();

$aFEGroups = array(
    "--all--" => i18n("-- All Groups --")
);

while ($fegroup = $fegroups->next()) {
    $aFEGroups[$fegroup->get("idfrontendgroup")] = $fegroup->get("groupname");
}

$oSelectRestrictGroup = new cHTMLSelectElement("restrictgroup");
$oSelectRestrictGroup->autoFill($aFEGroups);
$oSelectRestrictGroup->setDefault($_REQUEST["restrictgroup"]);
$oTextboxFilter = new cHTMLTextbox("filter", $_REQUEST["filter"], 20);
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

if (strlen($_REQUEST["filter"]) > 0) {
    if ($_REQUEST['searchin'] == "--all--" || $_REQUEST['searchin'] == "") {
        foreach ($aFieldSources as $variableName => $source) {
            $oFEUsers->setWhereGroup("filter", $variableName, $_REQUEST["filter"], "LIKE");
        }

        $oFEUsers->setInnerGroupCondition("filter", "OR");
    } else {
        $searchField = 'username';
        if (in_array($databaseFields, $_REQUEST['searchin'])) {
            $searchField = $_REQUEST['searchin'];
        }

        $oFEUsers->setWhere("cApiFrontendUserCollection." . $searchField, $_REQUEST["filter"], "LIKE");
    }
}

if ($_REQUEST["restrictgroup"] != "" && $_REQUEST["restrictgroup"] != "--all--") {
    $oFEUsers->link("cApiFrontendGroupMemberCollection");
    $oFEUsers->setWhere("cApiFrontendGroupMemberCollection.idfrontendgroup", $_REQUEST["restrictgroup"]);
}

$mPage = (int) $_REQUEST["page"];
$elemperpage = (int) $_REQUEST["elemperpage"];

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
$oPagerLink->setCustom("elemperpage", $elemperpage);
$oPagerLink->setCustom("filter", $_REQUEST["filter"]);
$oPagerLink->setCustom("sortby", $_REQUEST["sortby"]);
$oPagerLink->setCustom("sortorder", $_REQUEST["sortorder"]);
$oPagerLink->setCustom("searchin", $_REQUEST["searchin"]);
$oPagerLink->setCustom("restrictgroup", $_REQUEST["restrictgroup"]);
$oPagerLink->setCustom("frame", 2);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);

$oPager = new cGuiObjectPager("25c6a67d-a3f1-4ea4-8391-446c131952c9", $iItemCount, $elemperpage, $mPage, $oPagerLink, "page", $pagingLink);
$oPager->setExpanded(true);

/*
 * Groups create Groups
 */
$link = new cHTMLLink();
$menu = new cGuiMenu();
if ((int) $client > 0) {
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
$menu->setImage("-2", $cfg["path"]["images"] . "folder_new.gif");
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

$tpl->set('s', 'PAGE', $mPage);
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['frontend_left_top']);
