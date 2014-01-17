<?php
/**
 * This file contains the backend page for template rights management.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
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
// set the areas which are in use fore selecting these
$possible_area = "'" . implode("','", $area_tree[$perm->showareas("tpl")]) . "'";
$sql = "SELECT A.idarea, A.idaction, A.idcat, B.name, C.name
        FROM " . $cfg["tab"]["rights"] . " AS A, " . $cfg["tab"]["area"] . " AS B, " . $cfg["tab"]["actions"] . " AS C
        WHERE user_id = '" . $db->escape($userid) . "'
        AND idclient = " . cSecurity::toInteger($rights_client) . " AND A.type = 0 AND idlang = " . cSecurity::toInteger($rights_lang) . "
        AND B.idarea IN ($possible_area) AND idcat != 0 AND A.idaction = C.idaction AND A.idarea = C.idarea AND A.idarea = B.idarea";
$db->query($sql);

$rights_list_old = array();
while ($db->nextRecord()) { // set a new rights list fore this user
    $rights_list_old[$db->f(3) . "|" . $db->f(4) . "|" . $db->f("idcat")] = "x";
}

if (($perm->have_perm_area_action("user_overview", $action)) && ($action == "user_edit")) {
    $ret = saveRights();
    if ($ret === true) {
        $sMessage = $notification->returnNotification('info', i18n('Changes saved'));
    }
} else {
    if (!$perm->have_perm_area_action("user_overview", $action)) {
        $sMessage = $notification->returnNotification("error", i18n("Permission denied"));
    }
}

$sJsBefore = '';
$sJsAfter = '';
$sTable = '';

$sJsBefore .= "var itemids = [];
               var actareaids = [];\n";

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
// 1. zeile
$headeroutput = "";
$items = "";
$objHeaderItem->updateAttributes(array(
    "class" => "center",
    "valign" => "top",
    "align" => "left"
));
$objHeaderItem->setContent(i18n("Template name"));
$items .= $objHeaderItem->render();
$objHeaderItem->advanceID();

$aSecondHeaderRow = array();
$possible_areas = array();

// look for possible actions in mainarea []
foreach ($right_list["tpl"] as $value2) {
    // if there are some actions
    if (is_array($value2["action"])) {
        foreach ($value2["action"] as $key3 => $value3) {
            // set the areas that are in use
            $possible_areas[$value2["perm"]] = "";

            // set the possible areas and actions for this areas
            $sJsBefore .= "actareaids[\"$value3|" . $value2["perm"] . "\"]=\"x\"\n";

            // checkbox for the whole action
            $objHeaderItem->updateAttributes(array(
                "class" => "center",
                "valign" => "top",
                "align" => "center"
            ));
            $objHeaderItem->setContent($lngAct[$value2["perm"]][$value3] ? $lngAct[$value2["perm"]][$value3] : "&nbsp;");
            $items .= $objHeaderItem->render();
            $objHeaderItem->advanceID();
            $aSecondHeaderRow[] = "<input type=\"checkbox\" name=\"checkall_" . $value2["perm"] . "_$value3\" value=\"\" onClick=\"setRightsFor('" . $value2["perm"] . "', '$value3', '')\">";
        }
    }
}

// checkbox for all rights
$objHeaderItem->setContent(i18n("Check all"));
$items .= $objHeaderItem->render();
$objHeaderItem->advanceID();
$aSecondHeaderRow[] = '<input type="checkbox" name="checkall" value="" onclick="setRightsForAll()">';

$objHeaderRow->updateAttributes(array(
    "class" => "textw_medium"
));
$objHeaderRow->setContent($items);
$items = "";
$headeroutput .= $objHeaderRow->render();
$objHeaderRow->advanceID();

// 2. zeile
$objHeaderItem->updateAttributes(array(
    "class" => "center",
    "valign" => "",
    "align" => "center",
    "style" => "border-top-width: 0px;"
));
$objHeaderItem->setContent("&nbsp;");
$items .= $objHeaderItem->render();
$objHeaderItem->advanceID();
foreach ($aSecondHeaderRow as $value) {
    $objHeaderItem->setContent($value);
    $items .= $objHeaderItem->render();
    $objHeaderItem->advanceID();
}
$objHeaderRow->updateAttributes(array(
    "class" => "textw_medium"
));
$objHeaderRow->setContent($items);
$items = "";
$headeroutput .= $objHeaderRow->render();
$objHeaderRow->advanceID();

// table content
$output = "";
// Select the itemids
$sql = "SELECT * FROM " . $cfg["tab"]["tpl"] . " WHERE idclient='" . cSecurity::toInteger($rights_client) . "' ORDER BY name";
$db->query($sql);

while ($db->nextRecord()) {
    $tplname = conHtmlentities($db->f("name"));
    $description = conHtmlentities($db->f("description"));

    $objItem->updateAttributes(array(
        "class" => "td_rights0"
    ));
    $objItem->setContent($tplname);
    $objItem->setAttribute($description);
    $items .= $objItem->render();
    $objItem->advanceID();

    // set javscript array for itemids
    $sJsAfter .= "itemids[\"" . $db->f("idtpl") . "\"]=\"x\";\n";

    // look for possible actions in mainarea[]
    foreach ($right_list["tpl"] as $value2) {
        // if there area some
        if (is_array($value2["action"])) {
            foreach ($value2["action"] as $key3 => $value3) {
                // does the user have the right
                if (in_array($value2["perm"] . "|$value3|" . $db->f("idtpl"), array_keys($rights_list_old))) {
                    $checked = "checked=\"checked\"";
                } else {
                    $checked = "";
                }

                // set the checkbox the name consits of areait+actionid+itemid
                $objItem->updateAttributes(array(
                    "class" => "td_rights2"
                ));
                $objItem->setContent("<input type=\"checkbox\" name=\"rights_list[" . $value2["perm"] . "|$value3|" . $db->f("idtpl") . "]\" value=\"x\" $checked>");
                $items .= $objItem->render();
                $objItem->advanceID();
            }
        }
    }

    // checkbox for checking all actions fore this itemid
    $objItem->updateAttributes(array(
        "class" => "td_rights3"
    ));
    $objItem->setContent("<input type=\"checkbox\" name=\"checkall_" . $value2["perm"] . "_" . $value3 . "_" . $db->f("idtpl") . "\" value=\"\" onClick=\"setRightsFor('" . $value2["perm"] . "', '$value3', '" . $db->f("idtpl") . "')\">");
    $items .= $objItem->render();
    $objItem->advanceID();

    $objRow->setContent($items);
    $items = "";
    $output .= $objRow->render();
    $objRow->advanceID();
}

// table footer
$footeroutput = "";
$objItem->updateAttributes(array(
    "class" => "",
    "valign" => "top",
    "align" => "right",
    "colspan" => "8"
));
$objItem->setContent("<a href=\"javascript:submitrightsform('', 'area');\"><img src=\"" . $cfg['path']['images'] . "but_cancel.gif\" border=0></a><img src=\"images/spacer.gif\" width=\"20\"> <a href=\"javascript:submitrightsform('user_edit', '');\"><img src=\"" . $cfg['path']['images'] . "but_ok.gif\" border=0></a>");
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
