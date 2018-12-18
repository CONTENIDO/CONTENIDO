<?php

/**
 * This file contains the menu frame backend page in frontend user management.
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

$oPage = new cGuiPage("frontend.user_menu");

$oUser = new cApiUser($auth->auth["uid"]);

// Set default values
if (!isset($_REQUEST["elemperpage"]) || !is_numeric($_REQUEST['elemperpage']) || $_REQUEST['elemperpage'] <= 0) {
    $_REQUEST["elemperpage"] = $oUser->getProperty("itemsperpage", $area);
}
if (!is_numeric($_REQUEST['elemperpage'])) {
    $_REQUEST['elemperpage'] = 25;
}

// Save user property
$oUser->setProperty("itemsperpage", $area, $_REQUEST["elemperpage"]);
unset($oUser);
if (!isset($_REQUEST["page"]) || !is_numeric($_REQUEST['page']) || $_REQUEST['page'] <= 0 || $_REQUEST["elemperpage"] == 0) {
    $_REQUEST["page"] = 1;
}

$aFieldSources = array(
    "username" => "base",
    "created" => "created",
    "modified" => "modified"
);

$aElementsPerPage = array(
    25 => 25,
    50 => 50,
    75 => 75,
    100 => 100
);

$aSortOrderOptions = array(
    "asc" => i18n("Ascending"),
    "desc" => i18n("Descending")
);

$bUsePlugins = getEffectiveSetting("frontendusers", "pluginsearch", "true");
$bUsePlugins = ($bUsePlugins == "false") ? false : true;

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

    if (cString::getStringLength($_sValidPlugins) > 0) {
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
                    foreach ($aVariableNames as $sVariableName => $name) {
                        if (in_array($sVariableName, $databaseFields)) {
                            $aFieldSources[$sVariableName] = $plugin;
                        }
                    }
                }
            }
        }
    }
}

$oFEUsers->setWhere("cApiFrontendUserCollection.idclient", $client);

if (cString::getStringLength($_REQUEST["filter"]) > 0) {
    if ($_REQUEST['searchin'] == "--all--" || $_REQUEST['searchin'] == "") {
        foreach ($aFieldSources as $variableName => $source) {
            $oFEUsers->setWhereGroup("filter", $variableName, $_REQUEST["filter"], "LIKE");
        }

        $oFEUsers->setInnerGroupCondition("filter", "OR");
    } else {
        $searchField = 'username';
        if (in_array($_REQUEST['searchin'], $databaseFields)) {
            $searchField = $_REQUEST['searchin'];
        }
        
        $oFEUsers->setWhere("cApiFrontendUserCollection." . $searchField, $_REQUEST["filter"], "LIKE");
    }
}

if ($_REQUEST['restrictgroup'] != "" && $_REQUEST['restrictgroup'] != "--all--") {
    $oFEUsers->link("cApiFrontendGroupMemberCollection");
    $oFEUsers->setWhere("cApiFrontendGroupMemberCollection.idfrontendgroup", $_REQUEST["restrictgroup"]);
}

$mPage = (int) $_REQUEST['page'];
$elemperpage = (int) $_REQUEST['elemperpage'];

$oFEUsers->query();
$fullTableCount = $oFEUsers->count();

if (cString::getStringLength($_REQUEST['sortby']) !== 0 && in_array($_REQUEST['sortby'], array_keys($aFieldSources))) {
    $sortBy = $_REQUEST['sortby'];
} else {
    $sortBy = 'username';
}

if (cString::getStringLength($_REQUEST['sortorder']) !== 0 && in_array($_REQUEST['sortorder'], array_keys($aSortOrderOptions))) {
    $sortOrder = $_REQUEST['sortorder'];
} else {
    $sortOrder = 'asc';
}

$oFEUsers->setOrder("cApiFrontendUserCollection." . $sortBy . " " . $sortOrder);
$oFEUsers->setLimit($elemperpage * ($mPage - 1), $elemperpage);

if ($elemperpage * ($mPage) >= $fullTableCount + $elemperpage && $mPage != 1) {
    $mPage--;
}

$oFEUsers->query();

$aUserTable = array();

while ($feuser = $oFEUsers->next()) {
    foreach ($aFieldSources as $key => $field) {
        $idfrontenduser = $feuser->get("idfrontenduser");

        $aUserTable[$idfrontenduser]['idfrontenduser'] = $idfrontenduser;

        switch ($field) {
            case "base":
                $aUserTable[$idfrontenduser][$key] = $feuser->get("username");
                break;
            case "created":
                $aUserTable[$idfrontenduser][$key] = $feuser->get("created");
                break;
            case "modified":
                $aUserTable[$idfrontenduser][$key] = $feuser->get("modified");
                break;
            default:
                if ($_REQUEST['filter'] != "") {
                    $aUserTable[$idfrontenduser][$key] = call_user_func("frontendusers_" . $field . "_getvalue", $key);
                }
                break;
        }
    }
}

$mlist = new cGuiMenu();
$iMenu = 0;

foreach ($aUserTable as $mkey => $params) {
    $idfrontenduser = $params["idfrontenduser"];
    $link = new cHTMLLink();
    $link->setMultiLink($area, "", $area, "");
    $link->setCustom("idfrontenduser", $idfrontenduser);

    $iMenu++;

    $delTitle = i18n("Delete user");
    $deletebutton = '<a title="' . $delTitle . '" data-username="' . conHtmlSpecialChars($params['username']) . '" data-idfrontenduser="' . $idfrontenduser . '" class="jsDelete" href="javascript:void(0)"><img src="' . $cfg['path']['images'] . 'delete.gif" border="0" title="' . $delTitle . '" alt="' . $delTitle . '"></a>';

    $mlist->setTitle($iMenu, conHtmlentities($params["username"]));
    $mlist->setLink($iMenu, $link);
    $mlist->setActions($iMenu, "delete", $deletebutton);
    $mlist->setImage($iMenu, "");

    if ($_GET['frontenduser'] == $idfrontenduser) {
        $mlist->setMarked($iMenu);
    }
}

$oPage->addScript('parameterCollector.js');

$message = i18n("Do you really want to delete the user %s?");
$oPage->set("s", "DELETE_MESSAGE", $message);

// generate current content for Object Pager
$oPagerLink = new cHTMLLink();
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setLink("main.php");
$oPagerLink->setCustom("elemperpage", $elemperpage);
$oPagerLink->setCustom("filter", $_REQUEST['filter']);
$oPagerLink->setCustom("sortby", $_REQUEST['sortby']);
$oPagerLink->setCustom("sortorder", $_REQUEST['sortorder']);
$oPagerLink->setCustom("searchin", $_REQUEST['searchin']);
$oPagerLink->setCustom("restrictgroup", $_REQUEST['restrictgroup']);
$oPagerLink->setCustom("frame", $frame);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);

$oPager = new cGuiObjectPager("25c6a67d-a3f1-4ea4-8391-446c131952c9", $fullTableCount, $_REQUEST['elemperpage'], $mPage, $oPagerLink, "page", $pagingLink);

// add slashes, to insert in javascript
$sPagerContent = $oPager->render(1);
$sPagerContent = str_replace('\\', '\\\\', $sPagerContent);
$sPagerContent = str_replace('\'', '\\\'', $sPagerContent);

$oPage->set("s", "MPAGE", $mpage);
$oPage->set("s", "PAGER_CONTENT", $sPagerContent);
$oPage->set("s", "PAGE", $mPage);
$oPage->set("s", "FORM", $mlist->render(false));
$oPage->render();
