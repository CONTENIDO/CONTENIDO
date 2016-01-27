<?php

/**
 * This file contains the backend page for area rights management.
 *
 * @package Core
 * @subpackage Backend
 * @author Unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// notice $oTpl is filled and generated in file include.rights.php this file
// renders $oTpl to browser
include_once(cRegistry::getBackendPath() . 'includes/include.rights.php');
$debug = (cDebug::getDefaultDebuggerName() != cDebug::DEBUGGER_DEVNULL);

// set the areas which are in use for selecting these

$sql = "SELECT A.idarea, A.idaction, A.idcat, B.name, C.name
        FROM " . $cfg["tab"]["rights"] . " AS A, " . $cfg["tab"]["area"] . " AS B, " . $cfg["tab"]["actions"] . " AS C
        WHERE user_id = '" . $db->escape($userid) . "' AND idclient = " . cSecurity::toInteger($rights_client) . "
        AND idlang = " . cSecurity::toInteger($rights_lang) . " AND idcat = 0 AND A.idaction = C.idaction AND A.idarea = B.idarea";
$db->query($sql);

$rights_list_old = array();
while ($db->nextRecord()) { // set a new rights list for this user
    $rights_list_old[$db->f(3) . "|" . $db->f(4) . "|" . $db->f("idcat")] = "x";
}

if (($perm->have_perm_area_action("user_overview", $action)) && ($action == "user_edit")) {
    $ret = saveRights();
    if ($ret === true) {
        $sMessage = $notification->returnNotification('ok', i18n('Changes saved'));
    }
} else {
    if (!$perm->have_perm_area_action("user_overview", $action)) {
        $sMessage = $notification->returnNotification("error", i18n("Permission denied"));
    }
}

// declare new template variables
$sJsBefore = '';
$sJsAfter = '';
$sJsExternal = '';
$sTable = '';

$sJsBefore .= "var areatree = new Array();\n";

if (!isset($rights_perms) || $action == "" || !isset($action)) {
    // search for the permissions of this user
    $sql = "SELECT perms FROM " . $cfg['tab']['user'] . " WHERE user_id='$userid'";

    $db->query($sql);
    $db->nextRecord();
    $rights_perms = $db->f("perms");
}

// Init Table
$oTable = new cHTMLTable();
$oTable->updateAttributes(array(
    "class" => "generic",
    "cellspacing" => "0",
    "cellpadding" => "2"
));
$objHeaderRow = new cHTMLTableRow();
$objHeaderItem = new cHTMLTableHead();
$objFooterRow = new cHTMLTableRow();
$objFooterItem = new cHTMLTableData();
$objRow = new cHTMLTableRow();
$objItem = new cHTMLTableData();

// table header
$headeroutput = "";
$aTh = array(
    array(
        "&nbsp;",
        "&nbsp;",
        i18n("Check all")
    ),
    array(
        "&nbsp;",
        "&nbsp;",
        '<input type="checkbox" name="checkall" value="" onclick="setRightsForAllAreas()">'
    )
);

foreach ($aTh as $i => $tr) {
    $items = "";
    foreach ($tr as $td) {
        if ($i == 1) {
            $objHeaderItem->updateAttributes(array(
                "class" => "center",
                "align" => "center",
                "valign" => "",
                "style" => "border-top-width: 0px;"
            ));
        } else {
            $objHeaderItem->updateAttributes(array(
                "class" => "center",
                "align" => "center",
                "valign" => "top"
            ));
        }
        $objHeaderItem->setContent($td);
        $items .= $objHeaderItem->render();
        $objHeaderItem->advanceID();
    }
    $objHeaderRow->updateAttributes(array(
        "class" => "textw_medium"
    ));
    $objHeaderRow->setContent($items);
    $headeroutput .= $objHeaderRow->render();
    $objHeaderRow->advanceID();
}

// table content
$output = "";
$nav = new cGuiNavigation();
foreach ($right_list as $key => $value) {
    // look for possible actions in mainarea
    foreach ($value as $key2 => $value2) {
        $items = "";
        if ($key == $key2) {
            // does the user have the right
            if (in_array($value2["perm"] . "|fake_permission_action|0", array_keys($rights_list_old))) {
                $checked = 'checked="checked"';
            } else {
                $checked = "";
            }

            // Extract names from the XML document.
            $main = $nav->getName(str_replace('/overview', '/main', $value2['location']));

            if ($debug) {
                $locationString = $value2["location"] . " " . $value2["perm"] . "-->" . $main;
            } else {
                $locationString = $main;
            }

            $objItem->updateAttributes(array(
                "class" => "td_rights1"
            ));
            $objItem->setContent($locationString);
            $items .= $objItem->render();
            $objItem->advanceID();

            $objItem->updateAttributes(array(
                "class" => "td_rights2"
            ));
            $objItem->setContent("<input type=\"checkbox\" name=\"rights_list[" . $value2["perm"] . "|fake_permission_action|0]\" value=\"x\" $checked>");
            $items .= $objItem->render();
            $objItem->advanceID();

            $objItem->updateAttributes(array(
                "class" => "td_rights2"
            ));
            $objItem->setContent("<input type=\"checkbox\" name=\"checkall_$key\" value=\"\" onclick=\"setRightsForArea('$key')\">");
            $items .= $objItem->render();
            $objItem->advanceID();

            $objRow->setContent($items);
            $items = "";
            $output .= $objRow->render();
            $objRow->advanceID();
            // set javscript array for areatree
            $sJsBefore .= "areatree[\"$key\"] = new Array();
                           areatree[\"$key\"][\"" . $value2["perm"] . "0\"] = \"rights_list[" . $value2["perm"] . "|fake_permission_action|0]\"\n";
        }

        // if there area some
        if (is_array($value2["action"])) {
            foreach ($value2["action"] as $key3 => $value3) {
                $idaction = $value3;
                // does the user have the right
                if (in_array($value2["perm"] . "|$idaction|0", array_keys($rights_list_old))) {
                    $checked = 'checked="checked"';
                } else {
                    $checked = "";
                }

                // set the checkbox the name consits of areait+actionid+itemid
                $sCellContent = '';
                if ($debug) {
                    $sCellContent = "&nbsp;&nbsp;&nbsp;&nbsp; " . $value2["perm"] . " | " . $value3 . "-->" . $lngAct[$value2["perm"]][$value3] . "&nbsp;&nbsp;&nbsp;&nbsp;";
                } else {
                    if ($lngAct[$value2["perm"]][$value3] == "") {
                        $sCellContent = "&nbsp;&nbsp;&nbsp;&nbsp; " . $value2["perm"] . "|" . $value3 . "&nbsp;&nbsp;&nbsp;&nbsp;";
                    } else {
                        $sCellContent = "&nbsp;&nbsp;&nbsp;&nbsp; " . $lngAct[$value2["perm"]][$value3] . "&nbsp;&nbsp;&nbsp;&nbsp;";
                    }
                }

                $objItem->updateAttributes(array(
                    "class" => "td_rights1"
                ));
                $objItem->setContent($sCellContent);
                $items .= $objItem->render();
                $objItem->advanceID();

                $objItem->updateAttributes(array(
                    "class" => "td_rights2"
                ));
                $objItem->setContent("<input type=\"checkbox\" id=\"rights_list[" . $value2["perm"] . "|$value3|0]\" name=\"rights_list[" . $value2["perm"] . "|$value3|0]\" value=\"x\" $checked>");
                $items .= $objItem->render();
                $objItem->advanceID();

                $objItem->updateAttributes(array(
                    "class" => "td_rights2"
                ));
                $objItem->setContent("&nbsp;");
                $items .= $objItem->render();
                $objItem->advanceID();

                $objRow->setContent($items);
                $items = "";
                $output .= $objRow->render();
                $objRow->advanceID();
                // set javscript array for areatree
                $sJsBefore .= "areatree[\"$key\"][\"" . $value2["perm"] . "$value3\"]=\"rights_list[" . $value2["perm"] . "|$value3|0]\";\n";
            }
        }
    }
}

// table footer
$footeroutput = "";
$objItem->updateAttributes(array(
    "class" => "",
    "valign" => "top",
    "align" => "right",
    "colspan" => "3"
));
$objItem->setContent("<a href=\"javascript:submitrightsform('', 'area');\"><img src=\"" . $cfg['path']['images'] . "but_cancel.gif\" border=0></a><img src=\"images/spacer.gif\" width=\"20\"><a href=\"javascript:submitrightsform('user_edit', '');\"><img src=\"" . $cfg['path']['images'] . "but_ok.gif\" border=0></a>");
$items = $objItem->render();
$objItem->advanceID();
$objFooterRow->setContent($items);
$items = "";
$footeroutput = $objFooterRow->render();
$objFooterRow->advanceID();

$oTable->setContent($headeroutput . $output . $footeroutput);
$sTable = stripslashes($oTable->render());
// Table end
$oTpl->set('s', 'NOTIFICATION_SAVE_RIGHTS', $sMessage);
$oTpl->set('s', 'JS_SCRIPT_BEFORE', $sJsBefore);
$oTpl->set('s', 'JS_SCRIPT_AFTER', $sJsAfter);
$oTpl->set('s', 'RIGHTS_CONTENT', $sTable);
$oTpl->set('s', 'EXTERNAL_SCRIPTS', $sJsExternal);

$oTpl->generate('templates/standard/' . $cfg['templates']['rights']);
