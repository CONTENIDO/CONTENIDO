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

$aFieldsToSearch = array(
    "--all--" => i18n("-- All fields --"),
    "username" => i18n("Username")
);
$aFieldsToSort = array(
    "username" => i18n("Username"),
    "created" => i18n("Created"),
    "modified" => i18n("Modified")
);

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

// @TODO  Why do we have to include plugins, if they should not be used?
if (is_array($cfg['plugins']['frontendusers'])) {
    foreach ($cfg['plugins']['frontendusers'] as $plugin) {
        plugin_include("frontendusers", $plugin . "/" . $plugin . ".php");
    }
}

if ($bUsePlugins == true) {
    if (is_array($cfg['plugins']['frontendusers'])) {
        $_sValidPlugins = getEffectiveSetting("frontendusers", "pluginsearch_valid_plugins", '');
        $_aValidPlugins = array();
        if (strlen($_sValidPlugins) > 0) {
            $_aValidPlugins = explode(',', $_sValidPlugins);
        }
        $_iCountValidPlugins = sizeof($_aValidPlugins);
        foreach ($cfg['plugins']['frontendusers'] as $plugin) {
            if ($_iCountValidPlugins == 0 || in_array($plugin, $_aValidPlugins)) {
                if (function_exists("frontendusers_" . $plugin . "_wantedVariables") && function_exists("frontendusers_" . $plugin . "_canonicalVariables")) {
                    $aVariableNames = call_user_func("frontendusers_" . $plugin . "_canonicalVariables");

                    if (is_array($aVariableNames)) {
                        $aTmp = array_merge($aFieldsToSearch, $aVariableNames);
                        $aFieldsToSearch = $aTmp;

                        $aTmp2 = array_merge($aFieldsToSort, $aVariableNames);
                        $aFieldsToSort = $aTmp2;

                        foreach ($aVariableNames as $sVariableName => $name) {
                            $aFieldSources[$sVariableName] = $plugin;
                        }
                    }
                }
            }
        }
    }
}

// Elements per page
$oSelectItemsPerPage = new cHTMLSelectElement("elemperpage");
$oSelectItemsPerPage->autoFill($aElementsPerPage);
$oSelectItemsPerPage->setDefault($_REQUEST["elemperpage"]);

asort($aFieldsToSort);
asort($aFieldsToSearch);

// Sort by filter
$oSelectSortBy = new cHTMLSelectElement("sortby");
$oSelectSortBy->autoFill($aFieldsToSort);
$oSelectSortBy->setDefault($_REQUEST["sortby"]);

// Sort order filter
$oSelectSortOrder = new cHTMLSelectElement("sortorder");
$oSelectSortOrder->autoFill($aSortOrderOptions);
$oSelectSortOrder->setDefault($_REQUEST["sortorder"]);

// Search in filter
$oSelectSearchIn = new cHTMLSelectElement("searchin");
$oSelectSearchIn->autoFill($aFieldsToSearch);
$oSelectSearchIn->setDefault($_REQUEST["searchin"]);

// Frontend groups filter
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

// Search text filter
$oTextboxFilter = new cHTMLTextbox("filter", $_REQUEST["filter"], 20);

$oFEUsers = new cApiFrontendUserCollection();
$oFEUsers->setWhere("cApiFrontendUserCollection.idclient", $client);

if (strlen($_REQUEST["filter"]) > 0 && $bUsePlugins == false) {
    $oFEUsers->setWhere("cApiFrontendUserCollection.username", $_REQUEST["filter"], "diacritics");
}

if ($_REQUEST["restrictgroup"] != "" && $_REQUEST["restrictgroup"] != "--all--") {
    $oFEUsers->link("cApiFrontendGroupMemberCollection");
    $oFEUsers->setWhere("cApiFrontendGroupMemberCollection.idfrontendgroup", $_REQUEST["restrictgroup"]);
}

$mPage = $_REQUEST["page"];
$elemperpage = $_REQUEST["elemperpage"];

$iFullTableCount = 0;
if ($bUsePlugins == false) {
    $oFEUsers->query();

    $iFullTableCount = $oFEUsers->count();

    $oFEUsers->setOrder(implode(" ", array(
        $oSelectSortBy->getDefault(),
        $oSelectSortOrder->getDefault()
    )));
} else {
    $oFEUsers->query();
    $iFullTableCount = $oFEUsers->count();
}

$oFEUsers->setLimit($elemperpage * ($mPage - 1), $elemperpage);

if ($_REQUEST["elemperpage"] * ($_REQUEST["page"]) >= $iFullTableCount + $_REQUEST["elemperpage"] && $_REQUEST["page"] != 1) {
    $_REQUEST["page"]--;
    $mPage--;
}

$oFEUsers->query();

$aUserTable = array();

while ($feuser = $oFEUsers->next()) {
    foreach ($aFieldSources as $key => $field) {
        $idfrontenduser = $feuser->get("idfrontenduser");

        $aUserTable[$idfrontenduser]["idfrontenduser"] = $idfrontenduser;

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
                if ($_REQUEST["filter"] != "") {
                    $aUserTable[$idfrontenduser][$key] = call_user_func("frontendusers_" . $field . "_getvalue", $key);
                }
                break;
        }
    }

    if ($_REQUEST["filter"] != "") {
        if ($_REQUEST["searchin"] == "--all--" || $_REQUEST["searchin"] == "") {
            $found = false;

            foreach ($aUserTable[$idfrontenduser] as $key => $value) {
                if (stripos($value, $_REQUEST["filter"]) !== false) {
                    $found = true;
                }
            }

            if ($found == false) {
                unset($aUserTable[$idfrontenduser]);
            }
        } else {
            if (stripos($aUserTable[$idfrontenduser][$_REQUEST["searchin"]], $_REQUEST["filter"]) === false) {
                unset($aUserTable[$idfrontenduser]);
            }
        }
    }
}

$sortorder = ($_REQUEST["sortorder"] == "desc") ? SORT_DESC : SORT_ASC;
$sortby = ($_REQUEST["sortby"]) ? $_REQUEST["sortby"] : "username";

$aUserTable = cArray::csort($aUserTable, $sortby, $sortorder);

$mlist = new cGuiMenu();
$iMenu = 0;
$iItemCount = 0;

foreach ($aUserTable as $mkey => $params) {
    $idfrontenduser = $params["idfrontenduser"];
    $link = new cHTMLLink();
    $link->setMultiLink($area, "", $area, "");
    $link->setCustom("idfrontenduser", $idfrontenduser);

    $iItemCount++;

    if (($iItemCount > ($elemperpage * ($mPage - 1)) && $iItemCount < (($elemperpage * $mPage) + 1)) || $bUsePlugins == false) {
        $iMenu++;

        $delTitle = i18n("Delete user");
        $deletebutton = '<a title="' . $delTitle . '" data-username="' . conHtmlSpecialChars($params["username"]) . '" data-idfrontenduser="' . $idfrontenduser . '" class="jsDelete" href="javascript:void(0)"><img src="' . $cfg['path']['images'] . 'delete.gif" border="0" title="' . $delTitle . '" alt="' . $delTitle . '"></a>';

        $mlist->setTitle($iMenu, conHtmlentities($params["username"]));
        $mlist->setLink($iMenu, $link);
        $mlist->setActions($iMenu, "delete", $deletebutton);
        $mlist->setImage($iMenu, "");

        if ($_GET['frontenduser'] == $idfrontenduser) {
            $mlist->setMarked($iMenu);
        }
    }
}

if ($bUsePlugins == false) {
    $iItemCount = $iFullTableCount;
}

// $oPage->addScript('cfoldingrow.js', '<script type="text/javascript"
// src="scripts/cfoldingrow.js"></script>');
$oPage->addScript('parameterCollector.js');

$message = i18n("Do you really want to delete the user %s?");
$oPage->set("s", "DELETE_MESSAGE", $message);

// generate current content for Object Pager
$oPagerLink = new cHTMLLink();
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setLink("main.php");
$oPagerLink->setCustom("elemperpage", $elemperpage);
$oPagerLink->setCustom("filter", $_REQUEST["filter"]);
$oPagerLink->setCustom("sortby", $_REQUEST["sortby"]);
$oPagerLink->setCustom("sortorder", $_REQUEST["sortorder"]);
$oPagerLink->setCustom("searchin", $_REQUEST["searchin"]);
$oPagerLink->setCustom("restrictgroup", $_REQUEST["restrictgroup"]);
$oPagerLink->setCustom("frame", $frame);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);

$oPager = new cGuiObjectPager("25c6a67d-a3f1-4ea4-8391-446c131952c9", $iItemCount, $_REQUEST['elemperpage'], $mPage, $oPagerLink, "page", $pagingLink);

// add slashes, to insert in javascript
$sPagerContent = $oPager->render(1);
$sPagerContent = str_replace('\\', '\\\\', $sPagerContent);
$sPagerContent = str_replace('\'', '\\\'', $sPagerContent);

$oPage->set("s", "MPAGE", $mpage);
$oPage->set("s", "PAGER_CONTENT", $sPagerContent);
$oPage->set("s", "PAGE", $_REQUEST['page']);
$oPage->set("s", "FORM", $mlist->render(false));
$oPage->render();
