<?php
/**
 * This file contains the menu frame (overview) backend page for module management.
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

if (!(int) $client > 0) {
    // If there is no client selected, display empty page
    $oPage = new cGuiPage("mod_overview");
    $oPage->render();
    return;
}

// Now build bottom with list
$cApiModuleCollection = new cApiModuleCollection();
$classmodule = new cApiModule();
$oPage = new cGuiPage("mod_overview");
$searchOptions = array();

// no value found in request for items per page -> get form db or set default
$oUser = new cApiUser($auth->auth["uid"]);
if (!isset($_REQUEST["elemperpage"]) || !is_numeric($_REQUEST['elemperpage']) || $_REQUEST['elemperpage'] < 0) {
    $_REQUEST["elemperpage"] = $oUser->getProperty("itemsperpage", $area);
}
if (!is_numeric($_REQUEST["elemperpage"])) {
    $_REQUEST["elemperpage"] = 0;
}
if ($_REQUEST["elemperpage"] > 0) {
    // -- All -- will not be stored, as it may be impossible to change this back to something more useful
    $oUser->setProperty("itemsperpage", $area, $_REQUEST["elemperpage"]);
}
unset($oUser);

if (!isset($_REQUEST["page"]) || !is_numeric($_REQUEST['page']) || $_REQUEST['page'] <= 0 || $_REQUEST["elemperpage"] == 0) {
    $_REQUEST["page"] = 1;
}


// Build list for left_bottom considering filter values
$mlist = new cGuiMenu();
$sOptionModuleCheck = getSystemProperty("system", "modulecheck");
$sOptionForceCheck = getEffectiveSetting("modules", "force-menu-check", "false");
$iMenu = 0;

$searchOptions['elementPerPage'] = $_REQUEST['elemperpage'];

$searchOptions['orderBy'] = 'name';
if ($_REQUEST['sortby'] == 'type') {
    $searchOptions['orderBy'] = 'type';
}

$searchOptions['sortOrder'] = 'asc';
if ($_REQUEST['sortorder'] == "desc") {
    $searchOptions['sortOrder'] = 'desc';
}

$searchOptions['moduleType'] = '%%';
if ($_REQUEST['filtertype'] == '--wotype--') {
    $searchOptions['moduleType'] = '';
}

if (!empty($_REQUEST['filtertype']) && $_REQUEST['filtertype'] != '--wotype--' && $_REQUEST['filtertype'] != '--all--') {
    $searchOptions['moduleType'] = $db->escape($_REQUEST['filtertype']);
}

$searchOptions['filter'] = $db->escape($_REQUEST['filter']);

//search in
$searchOptions['searchIn'] = 'all';
if ($_REQUEST['searchin'] == 'name' || $_REQUEST['searchin'] == 'description' || $_REQUEST['searchin'] == 'type' || $_REQUEST['searchin'] == 'input' || $_REQUEST['searchin'] == 'output') {
    $searchOptions['searchIn'] = $_REQUEST['searchin'];
}

$searchOptions['selectedPage'] = $_REQUEST['page'];

$contenidoModulSearch = new cModuleSearch($searchOptions);

$allModules = $contenidoModulSearch->getModules();

if ($_REQUEST["elemperpage"] > 0) {
    $iItemCount = $contenidoModulSearch->getModulCount();
} else {
    $iItemCount = 0;
}

foreach ($allModules as $idmod => $module) {
    //$cApiModule = $cApiModuleCollection->next())

    if ($perm->have_perm_item($area, $idmod) ||
        $perm->have_perm_area_action("mod_translate", "mod_translation_save") ||
        $perm->have_perm_area_action_item("mod_translate", "mod_translation_save", $idmod)
    ) {

        //$idmod = $cApiModule->get("idmod");

        $link = new cHTMLLink;
        $link->setMultiLink("mod", "", "mod_edit", "");
        $link->setCustom("idmod", $idmod);
        $link->updateAttributes(array(
            "alt" => htmlentities($module['description']),
            "title" => htmlentities($module['description'])
        ));

        $moduleName = (strlen(trim($module['name'])) > 0) ? $module['name'] : i18n("- Unnamed module -");
        $sName = cString::stripSlashes(conHtmlSpecialChars($moduleName)); //$cApiModule->get("name");
        $descr = cString::stripSlashes(conHtmlSpecialChars($module ['description']));

        // Do not check modules (or don't force it) - so, let's take a look into the database
        $sModuleError = $module['error']; //$cApiModule->get("error");

        if ($sModuleError == "none") {
            $colName = $sName;
        } else if ($sModuleError == "input" || $sModuleError == "output") {
            $colName = '<span class="moduleError">' . $sName . '</span>';
        } else {
            $colName = '<span class="moduleCriticalError">' . $sName . '</span>';
        }

        $iMenu++;

        $mlist->setTitle($iMenu, $colName);
        $mlist->setTooltip($iMenu, ($descr == "") ? '' : $descr);
        if ($perm->have_perm_area_action_item("mod_edit", "mod_edit", $idmod) ||
            $perm->have_perm_area_action_item("mod_translate", "mod_translation_save", $idmod)) {
            $mlist->setLink($iMenu, $link);
        }

        $inUse = $classmodule->moduleInUse($idmod);

        $deletebutton = "";

        if ($inUse) {
            $inUseString = i18n("For more information about usage click on this button");
            $mlist->setActions($iMenu, 'inuse', '
                <a href="javascript:;" rel="' . $idmod . '" class="in_used_mod"><img src="' . $cfg['path']['images'] . 'exclamation.gif" border="0" title="' . $inUseString . '" alt="' . $inUseString . '"></a>');
            $delDescription = i18n("Module can not be deleted, because it is already in use!");
        } else {
            $mlist->setActions($iMenu, 'inuse', '<img src="./images/spacer.gif" border="0" width="16">');
            if ($perm->have_perm_area_action_item("mod", "mod_delete", $idmod)) {
                $delTitle = i18n("Delete module");
                $delDescr = sprintf(i18n("Do you really want to delete the following module:<br /><br />%s<br />"), $sName);
                $deletebutton = '
                    <a title="' . $delTitle . '" href="javascript:void(0)" onclick="Con.showConfirmation(&quot;' . $delDescr . '&quot;, function() { deleteModule(' . $idmod . '); });return false;" >
                        <img src="' . $cfg['path']['images'] . 'delete.gif" border="0" title="' . $delTitle . '" alt="' . $delTitle . '">
                    </a>';
            } else {
                $delDescription = i18n("No permissions");
            }
        }

        if ($deletebutton == "") {
            //$deletebutton = '<img src="images/spacer.gif" width="16" height="16">';
            $deletebutton = '
                <img src="' . $cfg['path']['images'] . 'delete_inact.gif" border="0" title="' . $delDescription . '" alt="' . $delDescription . '">';
        }

        $todo = new TODOLink("idmod", $idmod, "Module: $sName", "");

        $mlist->setActions($iMenu, "todo", $todo->render());
        $mlist->setActions($iMenu, "delete", $deletebutton);

        if ($_GET['idmod'] == $idmod) {
            $mlist->setMarked($iMenu);
        }
        //$mlist->setImage($iMenu, "images/but_module.gif");
        //$mlist->setImage($iMenu, 'images/spacer.gif', 5);
    }
}

$oPage->addScript("cfoldingrow.js");
$oPage->addScript("parameterCollector.js");
$oPage->set("s", "FORM", $mlist->render(false));

//generate current content for Object Pager
$oPagerLink = new cHTMLLink;
$pagerl = "pagerlink";
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setLink("main.php");
$oPagerLink->setCustom("elemperpage", $elemperpage);
$oPagerLink->setCustom("filter", stripslashes($_REQUEST["filter"]));
$oPagerLink->setCustom("sortby", $_REQUEST["sortby"]);
$oPagerLink->setCustom("sortorder", $_REQUEST["sortorder"]);
$oPagerLink->setCustom("frame", $frame);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);
$oPager = new cGuiObjectPager("02420d6b-a77e-4a97-9395-7f6be480f497", $iItemCount, $_REQUEST["elemperpage"], $_REQUEST["page"], $oPagerLink, "page", $pagerl);

//add slashes, to insert in javascript
$sPagerContent = $oPager->render(true);
$sPagerContent = str_replace('\\', '\\\\', $sPagerContent);
$sPagerContent = str_replace('\'', '\\\'', $sPagerContent);

//send new object pager to left_top
$sRefreshPager = <<<JS
<script type="text/javascript">
(function(Con, $) {
    var sNavigation = '{$sPagerContent}',
        left_top = Con.getFrame('left_top'), oPager, oInsert;
    if (left_top) {
        oPager = left_top.document.getElementById('02420d6b-a77e-4a97-9395-7f6be480f497');
        if (oPager) {
            oInsert = oPager.firstChild;
            oInsert.innerHTML = sNavigation;
            left_top.toggle_pager('02420d6b-a77e-4a97-9395-7f6be480f497');
        }
    }
})(Con, Con.$);
</script>
JS;

$oPage->addScript($sRefreshPager);

$oPage->render();
