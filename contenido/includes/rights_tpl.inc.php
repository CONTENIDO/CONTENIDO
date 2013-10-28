<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Rights for Template
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2008-07-03, Timo Trautmann, moved inline html to template
 *
 *   $Id: rights_tpl.inc.php 558 2008-07-03 16:38:34Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

//notice $oTpl is filled and generated in file rights.inc.php this file renders $oTpl to browser
include_once($cfg['path']['contenido'].'includes/rights.inc.php');
//set the areas which are in use fore selecting these
$possible_area = "'".implode("','", $area_tree[$perm->showareas("tpl")])."'";
$sql = "SELECT A.idarea, A.idaction, A.idcat, B.name, C.name FROM ".$cfg["tab"]["rights"]." AS A, ".$cfg["tab"]["area"]." AS B, ".$cfg["tab"]["actions"]." AS C WHERE user_id='".Contenido_Security::escapeDB($userid, $db)."'
        AND idclient='".Contenido_Security::escapeDB($rights_client, $db)."' AND A.type = 0 AND idlang='".Contenido_Security::escapeDB($rights_lang, $db)."' AND B.idarea IN ($possible_area) AND idcat!='0' AND A.idaction = C.idaction AND A.idarea = C.idarea AND A.idarea = B.idarea";
$db->query($sql);

$rights_list_old = array ();
while ($db->next_record()) { //set a new rights list fore this user
   $rights_list_old[$db->f(3)."|".$db->f(4)."|".$db->f("idcat")] = "x";
}

if (($perm->have_perm_area_action($area, $action)) && ($action == "user_edit"))
{
    saverights();
}else {
    if (!$perm->have_perm_area_action($area, $action))
    {
    $notification->displayNotification("error", i18n("Permission denied"));
    }
}

$sJsBefore = '';
$sJsAfter = '';
$sTable = '';

$sJsBefore .= "var itemids=new Array();\n
               var actareaids=new Array();\n";

// declare new javascript variables;
$colspan=0;

$table = new Table($cfg["color"]["table_border"], "solid", 0, 2, $cfg["color"]["table_header"], $cfg["color"]["table_light"], $cfg["color"]["table_dark"], 0, 0);

$sTable .= $table->start_table();
$sTable .= $table->header_row();
$sTable .= $table->header_cell(i18n("Template name"));
$sTable .= $table->header_cell(i18n("Description"));

$aSecondHeaderRow = array();
$possible_areas=array();

// look for possible actions   in mainarea []
foreach($right_list["tpl"] as $value2)
{
               //if there are some actions
               if(is_array($value2["action"]))
                 foreach($value2["action"] as $key3 => $value3)
                 {       //set the areas that are in use
                         $possible_areas[$value2["perm"]]="";

                         $colspan++;
                         //set  the possible areas and actions for this areas

                         $sJsBefore .= "actareaids[\"$value3|".$value2["perm"]."\"]=\"x\"\n";

                         //checkbox for the whole action
                         $sTable .= $table->header_cell($lngAct[$value2["perm"]][$value3]);
                         array_push($aSecondHeaderRow, "<input type=\"checkbox\" name=\"checkall_".$value2["perm"]."_$value3\" value=\"\" onClick=\"setRightsFor('".$value2["perm"]."','$value3','')\">");
                 }
}

//checkbox for all rights
$sTable .= $table->header_cell(i18n("Check all"));
array_push($aSecondHeaderRow, "<input type=\"checkbox\" name=\"checkall\" value=\"\" onClick=\"setRightsForAll()\">");
$sTable .= $table->end_row();
$colspan++;

$sTable .= $table->header_row();
$sTable .= $table->header_cell('&nbsp',"center", '', '', 0);
$sTable .= $table->header_cell('&nbsp',"center", '', '', 0);

foreach ($aSecondHeaderRow as $value) {
    $sTable .= $table->header_cell($value,"center", '', '', 0);
}
$sTable .= $table->end_row();


//Select the itemid´s
$sql = "SELECT * FROM ".$cfg["tab"]["tpl"]." WHERE idclient='".Contenido_Security::toInteger($rights_client)."' ORDER BY name";
$db->query($sql);

while ($db->next_record()) {

        $tplname     = conHtmlentities($db->f("name"));
        $description = conHtmlentities($db->f("description"));

        $sTable .= $table->row();
        $sTable .= $table->cell($tplname,"", "", " class=\"td_rights0\"", false);
        $sTable .= $table->cell($description,"", "", " class=\"td_rights1\" style=\"white-space:normal;\"", false); 

        //set javscript array for itemids
        $sJsAfter.="itemids[\"".$db->f("idtpl")."\"]=\"x\";\n";

        // look for possible actions in mainarea[]
        foreach($right_list["tpl"] as $value2)
              {

               //if there area some
               if(is_array($value2["action"]))
                 foreach($value2["action"] as $key3 => $value3)
                 {
                            //does the user have the right
                            if(in_array($value2["perm"]."|$value3|".$db->f("idtpl"),array_keys($rights_list_old))) {
                                $checked="checked=\"checked\"";
                            } else {
                                $checked="";
                            }

                          //set the checkbox    the name consits of      areait+actionid+itemid
                          $sTable .= $table->cell("<input type=\"checkbox\" name=\"rights_list[".$value2["perm"]."|$value3|".$db->f("idtpl")."]\" value=\"x\" $checked>","", "", " class=\"td_rights2\"", false);

                 }
        }
        //checkbox for checking all actions fore this itemid
        $sTable .= $table->cell("<input type=\"checkbox\" name=\"checkall_".$value2["perm"]."_".$value3."_".$db->f("idtpl")."\" value=\"\" onClick=\"setRightsFor('".$value2["perm"]."','$value3','".$db->f("idtpl")."')\">","", "", " class=\"td_rights3\"", false);
        $sTable .= $table->end_row();
}
$sTable .= $table->end_row();
$sTable .= $table->row();
$sTable .= $table->sumcell("<a href=javascript:submitrightsform('','area')><img src=\"".$cfg['path']['images']."but_cancel.gif\" border=0></a><img src=\"images/spacer.gif\" width=\"20\"> <a href=javascript:submitrightsform('user_edit','')><img src=\"".$cfg['path']['images']."but_ok.gif\" border=0></a>","right");
$sTable .= $table->end_row();
$sTable .= $table->end_table();

$oTpl->set('s', 'JS_SCRIPT_BEFORE', $sJsBefore);
$oTpl->set('s', 'JS_SCRIPT_AFTER', $sJsAfter);
$oTpl->set('s', 'RIGHTS_CONTENT', $sTable);
$oTpl->set('s', 'EXTERNAL_SCRIPTS', $sJsExternal);
$oTpl->generate('templates/standard/'.$cfg['templates']['rights_inc']);
?>