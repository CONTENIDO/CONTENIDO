<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Rights for str
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
 *   $Id: rights_str.inc.php 634 2008-07-28 16:42:07Z bilal.arslan $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

//notice $oTpl is filled and generated in file rights.inc.php this file renders $oTpl to browser
include_once($cfg['path']['contenido'].'includes/rights.inc.php');
//set the areas which are in use fore selecting these
$possible_area = "'".implode("','", $area_tree[$perm->showareas("str")])."'";
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
$sJsExternal = '';
$sTable = '';

        $sJsExternal .= '<script type="text/javascript" src="scripts/addImageTags.js"></script>'."\n";
        $sJsExternal .= '<script type="text/javascript" src="scripts/expandCollapse.js"></script>'."\n";
 
        // declare new javascript variables;
        $sJsBefore .= "
              var itemids=new Array(); \n
              var actareaids=new Array(); \n";

        $colspan=0;

        $table = new Table($cfg["color"]["table_border"], "solid", 0, 2, $cfg["color"]["table_header"], $cfg["color"]["table_light"], $cfg["color"]["table_dark"], 0, 0);

        $sTable .= $table->start_table();
        $sTable .= $table->header_row();
        $sTable .= $table->header_cell(i18n("Category"),"left");
		$sTable .= $table->header_cell("&nbsp;","left");
        $possible_areas=array();
        $aSecondHeaderRow = array();

        // look for possible actions   in mainarea []   in str and con
        foreach($right_list["str"] as $value2)
        {
               //if there are some actions
               if(is_array($value2["action"]))
                 foreach($value2["action"] as $key3 => $value3)
                 {       //set the areas that are in use

                        # HACK!
                        if ($value3 != "str_newtree")
                        {
                         $possible_areas[$value2["perm"]]="";

                         $colspan++;
                         //set  the possible areas and actions for this areas
                         $sJsBefore .= "actareaids[\"$value3|".$value2["perm"]."\"]=\"x\";\n";

                         $sTable .= $table->header_cell($lngAct[$value2["perm"]][$value3]);
                         array_push($aSecondHeaderRow, "<input type=\"checkbox\" name=\"checkall_".$value2["perm"]."_$value3\" value=\"\" onClick=\"setRightsFor('".$value2["perm"]."','$value3','')\">");
                        }
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

        $sql = "SELECT A.idcat, level, name,parentid FROM ".$cfg["tab"]["cat_tree"]." AS A, ".$cfg["tab"]["cat"]." AS B, ".$cfg["tab"]["cat_lang"]." AS C WHERE A.idcat=B.idcat AND B.idcat=C.idcat
                AND C.idlang='".Contenido_Security::toInteger($rights_lang)."' AND B.idclient='".Contenido_Security::toInteger($rights_client)."' ORDER BY idtree";
        $db->query($sql);

        $counter=array();
        $parentid="leer";
        
        $aRowname = array();
        $iLevel = 0;
        
        while ($db->next_record()) {

                if ($db->f("level") == 0 && $db->f("preid") != 0) {
                    $sTable .= $table->row();
					$sTable .= $table->sumcell("&nbsp;","right");
					$sTable .= $table->end_row();
                }else {
                        if ($db->f("level") < $iLevel) {
                            $iDistance = $iLevel-$db->f("level");

                            for ($i = 0; $i < $iDistance; $i++) {
                                array_pop($aRowname);
                            }
                            $iLevel = $db->f("level");
                        }
                        
                        if ($db->f("level") >= $iLevel) {
                            if ($db->f("level") == $iLevel) {
                                array_pop($aRowname);
                            } else {
                                $iLevel = $db->f("level");
                            }
                            array_push($aRowname, $db->f("idcat"));
                        }
                
                        //find out parentid for inheritance
                        //if parentid is the same increase the counter
                        if($parentid==$db->f("parentid")) {
                           $counter[$parentid]++;
                        }else{
                           $parentid=$db->f("parentid");
                           // if these parentid is in use increase the counter
                           if(isset($counter[$parentid])){
                                 $counter[$parentid]++;
                           }else{
                                 $counter[$parentid]=0;
                           }


                        }
                        //set javscript array for itemids
                        $sJsAfter.="itemids[\"".$db->f("idcat")."\"]=\"x\";\n";

						$spaces='<img src="images/spacer.gif" height="1" width="'.($db->f("level")*15).'"><a><img src="images/spacer.gif" width="7" id="'.implode('_', $aRowname).'_img"></a>';

                        $sTable .= $table->row("id=\"".implode('_', $aRowname)."\"");
                        $sTable .= $table->cell('<img src="images/spacer.gif" height="1" width="'.($db->f("level")*15).'"><a><img src="images/spacer.gif" width="7" id="'.implode('_', $aRowname).'_img"></a> '.$db->f("name"),"", "", " class=\"td_rights0\"", false);
                        $sTable .= $table->cell("<a href=\"javascript:rightsInheritanceUp('$parentid','$counter[$parentid]')\" class=\"action\"><img border=\"0\" src=\"images/pfeil_links.gif\"></a>    <a href=\"javascript:rightsInheritanceDown('".$db->f("idcat")."')\" class=\"action\"><img border=\"0\" src=\"images/pfeil_runter.gif\"></a>","", "", " class=\"td_rights1\"", false);

                        // look for possible actions in mainarea[]

                        foreach($right_list["str"] as $value2){

                                //if there area some
                                if(is_array($value2["action"]))
                                  foreach($value2["action"] as $key3 => $value3)
                                  {
                                        # HACK!
                                        if ($value3 != "str_newtree")
                                        {
                                           //does the user have the right
                                           if(in_array($value2["perm"]."|$value3|".$db->f("idcat"),array_keys($rights_list_old))) {
                                               $checked="checked=\"checked\"";
                                           } else {
                                               $checked="";
                                            }

                                           //set the checkbox    the name consits of      areaid+actionid+itemid        the    id  =  parebntid+couter for these parentid+areaid+actionid
                                           $sTable .= $table->cell("<input type=\"checkbox\" id=\"str_".$parentid."_".$counter[$parentid]."_".$value2["perm"]."_$value3\" name=\"rights_list[".$value2["perm"]."|$value3|".$db->f("idcat")."]\" value=\"x\" $checked>","", "", " class=\"td_rights2\"", false);

                                  }
                                  }
                        }

                         //checkbox for checking all actions fore this itemid
                         $sTable .= $table->cell("<input type=\"checkbox\" name=\"checkall_".$value2["perm"]."_".$value3."_".$db->f("idcat")."\" value=\"\" onClick=\"setRightsFor('".$value2["perm"]."','$value3','".$db->f("idcat")."')\">","", "", " class=\"td_rights3\"", false);
						 $sTable .= $table->end_row();
				}
}
$sTable .= $table->end_row();
$sTable .= $table->row();
$sTable .= $table->sumcell("<a href=javascript:submitrightsform('','area')><img src=\"".$cfg['path']['images']."but_cancel.gif\" border=0></a><img src=\"images/spacer.gif\" width=\"20\"> <a href=javascript:submitrightsform('user_edit','')><img src=\"".$cfg['path']['images']."but_ok.gif\" border=0></a>","right");
$sTable .= $table->end_row();
$sTable .= $table->end_table();

$sJsAfter .= "
    aTranslations = new Object();
    aTranslations['pfeil_links.gif'] = '".i18n("Apply rights for this category to all categories on the same level or above")."';
    aTranslations['pfeil_runter.gif'] = '".i18n("Apply rights for this category to all categories below the current category")."';
    setImageTags(aTranslations);
        
    init('".i18n("Open category")."', '".i18n("Close category")."');\n";

$oTpl->set('s', 'JS_SCRIPT_BEFORE', $sJsBefore);
$oTpl->set('s', 'JS_SCRIPT_AFTER', $sJsAfter);
$oTpl->set('s', 'RIGHTS_CONTENT', $sTable);
$oTpl->set('s', 'EXTERNAL_SCRIPTS', $sJsExternal);
$oTpl->generate('templates/standard/'.$cfg['templates']['rights_inc']);
?>