 <?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Group Rights
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
 *   created  unknown
 *   modified 2008-06-26, Dominik Ziegler, add security fix
 *   modified 2008-07-29, Bilal Arslan, moved inline html to template
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 * 
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


//notice $oTpl is filled and generated in file rights.inc.php this file renders $oTpl to browser
include_once($cfg['path']['contenido'].'includes/grouprights.inc.php');

//set the areas which are in use fore selecting these
$possible_area = "'".implode("','", $area_tree[$perm->showareas("lay")])."'";
$sql = "SELECT A.idarea, A.idaction, A.idcat, B.name, C.name FROM ".$cfg["tab"]["rights"]." AS A, ".$cfg["tab"]["area"]." AS B, ".$cfg["tab"]["actions"]." AS C WHERE user_id='".Contenido_Security::escapeDB($groupid, $db)."' AND idclient='".Contenido_Security::toInteger($rights_client)."' AND A.type = 1 AND idlang='".Contenido_Security::toInteger($rights_lang)."' AND B.idarea IN ($possible_area) AND idcat!='0' AND A.idaction = C.idaction AND A.idarea = C.idarea AND A.idarea = B.idarea";
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

// declare temp variables
$sJsBefore = '';
$sJsAfter = '';
$sJsExternal = '';
$sTable = '';

$sJsBefore .= "var itemids=new Array();
     var actareaids=new Array(); \n";

if (($perm->have_perm_area_action($area, $action)) && ($action == "group_edit"))
{
	saverights();
}else {
	if (!$perm->have_perm_area_action($area, $action))
	{
		$notification->displayNotification("error", i18n("Permission denied"));
	}
}


$colspan=0;

$oTable = new Table($cfg["color"]["table_border"], "solid", 0, 2, $cfg["color"]["table_header"], $cfg["color"]["table_light"], $cfg["color"]["table_dark"], 0, 0);

$sTable .= $oTable->start_table();
$sTable .= $oTable->header_row();
$sTable .= $oTable->header_cell(i18n("Layout name"));
$sTable .= $oTable->header_cell(i18n("Description")); 

$possible_areas=array();
$sCheckboxesRow = '';
$aSecondHeaderRow = array();

// look for possible actions   in mainarea []
foreach($right_list["lay"] as $value2)
{
	//if there are some actions
	if(is_array($value2["action"]))
	//set the areas that are in use
	foreach($value2["action"] as $key3 => $value3)
	{
		$possible_areas[$value2["perm"]]="";
		$colspan++;
		//set  the possible areas and actions for this areas
		$sJsBefore .= "actareaids[\"$value3|".$value2["perm"]."\"]=\"x\";\n"; 
		
		//checkbox for the whole action
		$sTable .= $oTable->header_cell($lngAct[$value2["perm"]][$value3]);
		array_push($aSecondHeaderRow, "<input type=\"checkbox\" name=\"checkall_".$value2["perm"]."_$value3\" value=\"\" onClick=\"setRightsFor('".$value2["perm"]."','$value3','')\">");

	}
}

        //checkbox for all rights
$sTable .= $oTable->header_cell(i18n("Check all"));  
array_push($aSecondHeaderRow, "<input type=\"checkbox\" name=\"checkall\" value=\"\" onClick=\"setRightsForAll()\">");
$sTable .= $oTable->end_row();

$colspan++;

$sTable .= $oTable->header_row();
$sTable .= $oTable->header_cell('&nbsp', 'center', '', '', 0);
$sTable .= $oTable->header_cell('&nbsp', 'center', '', '', 0);

// Put the checkbox in the table
foreach($aSecondHeaderRow as $value){
	$sTable .= $oTable->header_cell($value, "center", "", "", 0);
}
$sTable .= $oTable->end_row();

//Select the itemid´s
$sql = "SELECT * FROM ".$cfg["tab"]["lay"]." WHERE idclient='".Contenido_Security::toInteger($rights_client)."' ORDER BY name";
$db->query($sql);


while ($db->next_record()) {

	$sTplName     = htmlentities($db->f("name"));
	$sDescription = htmlentities($db->f("description"));


	$sTable .= $oTable->row();
	$sTable .= $oTable->cell($sTplName, "", "", " class=\"td_rights0\"", false);
	$sTable .= $oTable->cell($sDescription, "", "", " class=\"td_rights1\" style=\"white-space:normal; \"", false);


	//set javscript array for itemids
	$sJsBefore .= "itemids[\"".$db->f("idlay")."\"]=\"x\";\n";

	// look for possible actions in mainarea[]
	foreach($right_list["lay"] as $value2)
	{

		//if there area some
		if(is_array($value2["action"]))
		foreach($value2["action"] as $key3 => $value3)
		{
		//does the user have the right
		if(in_array($value2["perm"]."|$value3|".$db->f("idlay"),array_keys($rights_list_old)))
		$checked="checked=\"checked\"";
		else
		$checked="";
		
		
		//set the checkbox    the name consits of      areait+actionid+itemid
		//"<input type=\"checkbox\"  name=\"rights_list[".$value2["perm"]."|$value3|".$db->f("idlay")."]\" value=\"x\" $checked>
		 $sTable .= $oTable->cell("<input type=\"checkbox\"  name=\"rights_list[".$value2["perm"]."|$value3|".$db->f("idlay")."]\" value=\"x\" $checked>", "", "", " class=\"td_rights3\"", false );
	
		}
	
	
	}
//checkbox for checking all actions fore this itemid
	$sTable .= $oTable->cell("<input type=\"checkbox\" name=\"checkall_".$value2["perm"]."_".$value3."_".$db->f("idlay")."\" value=\"\" onClick=\"setRightsFor('".$value2["perm"]."','$value3','".$db->f("idlay")."')\">","", "", " class=\"td_rights3\"", false);
	$sTable .= $oTable->end_row();

}

$sTable .= $oTable->end_row();
$sTable .= $oTable->row();
$sTable .= $oTable->sumcell("<a href=javascript:submitrightsform('','area')><img src=\"".$cfg['path']['images']."but_cancel.gif\" border=0></a><img src=\"images/spacer.gif\" width=\"20\"> <a href=javascript:submitrightsform('group_edit','')><img src=\"".$cfg['path']['images']."but_ok.gif\" border=0></a>","right");
$sTable .= $oTable->end_row();
$sTable .= $oTable->end_table();


// generate Template
$oTpl->set('s', 'JS_SCRIPT_BEFORE', $sJsBefore);
$oTpl->set('s', 'JS_SCRIPT_AFTER', $sJsAfter);
$oTpl->set('s', 'RIGHTS_CONTENT', $sTable);
$oTpl->set('s', 'EXTERNAL_SCRIPTS', $sJsExternal);
$oTpl->generate('templates/standard/'.$cfg['templates']['rights_inc']);

?>
