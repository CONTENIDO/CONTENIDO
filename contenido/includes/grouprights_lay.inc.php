 <?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Group Rights
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


// Notice $oTpl is filled and generated in file rights.inc.php this file renders $oTpl to browser
include_once($cfg['path']['contenido'].'includes/grouprights.inc.php');

// Set the areas which are in use fore selecting these
$possible_area = "'".implode("','", $area_tree[$perm->showareas("lay")])."'";
$sql = "SELECT A.idarea, A.idaction, A.idcat, B.name, C.name FROM ".$cfg["tab"]["rights"]." AS A, ".$cfg["tab"]["area"]." AS B, ".$cfg["tab"]["actions"]." AS C WHERE user_id='".cSecurity::escapeDB($groupid, $db)."' AND idclient='".cSecurity::toInteger($rights_client)."' AND A.type = 1 AND idlang='".cSecurity::toInteger($rights_lang)."' AND B.idarea IN ($possible_area) AND idcat!='0' AND A.idaction = C.idaction AND A.idarea = C.idarea AND A.idarea = B.idarea";
$db->query($sql);
$rights_list_old = array();
while ($db->next_record()) { //set a new rights list fore this user
    $rights_list_old[$db->f(3)."|".$db->f(4)."|".$db->f("idcat")] = "x";
}

if (($perm->have_perm_area_action($area, $action)) && ($action == "user_edit")) {
    saverights();
} else {
    if (!$perm->have_perm_area_action($area, $action)) {
        $notification->displayNotification("error", i18n("Permission denied"));
    }
}

// Declare temp variables
$sJsBefore = '';
$sJsAfter = '';
$sJsExternal = '';
$sTable = '';

$sJsBefore .= "var itemids=new Array();
     var actareaids=new Array(); \n";

if (($perm->have_perm_area_action($area, $action)) && ($action == "group_edit")) {
    saverights();
} else {
    if (!$perm->have_perm_area_action($area, $action)) {
        $notification->displayNotification("error", i18n("Permission denied"));
    }
}


$colspan = 0;

$possible_areas = array();
$sCheckboxesRow = '';
$aSecondHeaderRow = array();

//Init Table
$oTable = new cHTMLTable;
$oTable->updateAttributes(array("class" => "generic", "cellspacing" => "0", "cellpadding" => "2"));
$objHeaderRow = new cHTMLTableRow;
$objHeaderItem = new cHTMLTableHead;
$objFooterRow = new cHTMLTableRow;
$objFooterItem = new cHTMLTableData;
$objRow = new cHTMLTableRow;
$objItem = new cHTMLTableData;

//table header
//1. zeile
$headeroutput = "";
$items = "";
$objHeaderItem->updateAttributes(array("class" => "center", "valign" => "top", "align" => "center"));
$objHeaderItem->setContent(i18n("Layout name"));
$items .= $objHeaderItem->render();
$objHeaderItem->advanceID();
$objHeaderItem->setContent(i18n("Description"));
$items .= $objHeaderItem->render();
$objHeaderItem->advanceID();
// look for possible actions   in mainarea []
foreach ($right_list["lay"] as $value2){
    //if there are some actions
    if(is_array($value2["action"])) {
        //set the areas that are in use
        foreach ($value2["action"] as $key3 => $value3) {
            $possible_areas[$value2["perm"]]="";
            $colspan++;
            //set  the possible areas and actions for this areas
            $sJsBefore .= "actareaids[\"$value3|".$value2["perm"]."\"]=\"x\";\n";

            //checkbox for the whole action
            $objHeaderItem->setContent($lngAct[$value2["perm"]][$value3]);
			$items .= $objHeaderItem->render();
			$objHeaderItem->advanceID();
            array_push($aSecondHeaderRow, "<input type=\"checkbox\" name=\"checkall_".$value2["perm"]."_$value3\" value=\"\" onClick=\"setRightsFor('".$value2["perm"]."','$value3','')\">");
        }
    }
}

array_push($aSecondHeaderRow, "<input type=\"checkbox\" name=\"checkall\" value=\"\" onClick=\"setRightsForAll()\">");
// Checkbox for all rights
$objHeaderItem->setContent(i18n("Check all"));
$items .= $objHeaderItem->render();
$objHeaderItem->advanceID();
$colspan++;

$objHeaderRow->updateAttributes(array("class" => "textw_medium"));
$objHeaderRow->setContent($items);
$items = "";
$headeroutput .= $objHeaderRow->render();
$objHeaderRow->advanceID();
//2. zeile
$objHeaderItem->updateAttributes(array("class" => "center", "valign" => "", "align" => "center", "style" => "border-top-width: 0px;"));
$objHeaderItem->setContent("&nbsp;");
$items .= $objHeaderItem->render();
$objHeaderItem->advanceID();
$objHeaderItem->setContent("&nbsp;");
$items .= $objHeaderItem->render();
$objHeaderItem->advanceID();
foreach ($aSecondHeaderRow as $value) {
	$objHeaderItem->setContent($value);
	$items .= $objHeaderItem->render();
	$objHeaderItem->advanceID();
}
$objHeaderRow->updateAttributes(array("class" => "textw_medium"));
$objHeaderRow->setContent($items);
$items = "";
$headeroutput .= $objHeaderRow->render();
$objHeaderRow->advanceID();
//table content
$output = "";
//Select the itemid´s
$sql = "SELECT * FROM ".$cfg["tab"]["lay"]." WHERE idclient='".cSecurity::toInteger($rights_client)."' ORDER BY name";
$db->query($sql);

while ($db->next_record()) {
    $sTplName     = htmlentities($db->f("name"));
    $sDescription = htmlentities($db->f("description"));

	$objItem->updateAttributes(array("class" => "td_rights0"));
    $objItem->setContent($sTplName ? $sTplName : "&nbsp;");
	$items .= $objItem->render();
	$objItem->advanceID();
	$objItem->updateAttributes(array("class" => "td_rights1", "style" => "white-space:normal;"));
	$objItem->setContent($sDescription ? $sDescription : "&nbsp;");
	$items .= $objItem->render();
	$objItem->advanceID();

    //set javscript array for itemids
    $sJsBefore .= "itemids[\"".$db->f("idlay")."\"]=\"x\";\n";

    // look for possible actions in mainarea[]
    foreach ($right_list["lay"] as $value2) {
        //if there area some
        if (is_array($value2["action"])) {
            foreach ($value2["action"] as $key3 => $value3) {
                //does the user have the right
                if (in_array($value2["perm"]."|$value3|".$db->f("idlay"), array_keys($rights_list_old))) {
                    $checked = "checked=\"checked\"";
                } else {
                    $checked = "";
                }

                //set the checkbox the name consits of areait+actionid+itemid
                //"<input type=\"checkbox\" name=\"rights_list[".$value2["perm"]."|$value3|".$db->f("idlay")."]\" value=\"x\" $checked>
				$objItem->updateAttributes(array("class" => "td_rights3", "style" => ""));
				$objItem->setContent("<input type=\"checkbox\"  name=\"rights_list[".$value2["perm"]."|$value3|".$db->f("idlay")."]\" value=\"x\" $checked>", "", "", " class=\"td_rights3\"");
				$items .= $objItem->render();
				$objItem->advanceID();
            }
        }
    }

    // checkbox for checking all actions fore this itemid
	$objItem->setContent("<input type=\"checkbox\" name=\"checkall_".$value2["perm"]."_".$value3."_".$db->f("idlay")."\" value=\"\" onClick=\"setRightsFor('".$value2["perm"]."','$value3','".$db->f("idlay")."')\">","", "", " class=\"td_rights3\"");
	$items .= $objItem->render();
	$objItem->advanceID();

	$objRow->setContent($items);
	$items = "";
	$output .= $objRow->render();
	$objRow->advanceID();

}
//table footer
$footeroutput = "";
$objItem->updateAttributes(array("class" => "","valign" => "top", "align" => "right", "colspan" => "8"));
$objItem->setContent("<a href=javascript:submitrightsform('','area')><img src=\"".$cfg['path']['images']."but_cancel.gif\" border=0></a><img src=\"images/spacer.gif\" width=\"20\"><a href=javascript:submitrightsform('group_edit','')><img src=\"".$cfg['path']['images']."but_ok.gif\" border=0></a>");
$items = $objItem->render();
$objItem->advanceID();
$objFooterRow->setContent($items);
$items = "";
$footeroutput = $objFooterRow->render();
$objFooterRow->advanceID();

$oTable->setContent($headeroutput.$output.$footeroutput);
$sTable = stripslashes($oTable->render());
//Table end

// generate Template
$oTpl->set('s', 'JS_SCRIPT_BEFORE', $sJsBefore);
$oTpl->set('s', 'JS_SCRIPT_AFTER', $sJsAfter);
$oTpl->set('s', 'RIGHTS_CONTENT', $sTable);
$oTpl->set('s', 'EXTERNAL_SCRIPTS', $sJsExternal);
$oTpl->generate('templates/standard/'.$cfg['templates']['rights_inc']);

?>