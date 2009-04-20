<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Rights Area
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
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *   modified 2008-07-03, Timo Trautmann, moved inline html to template
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if ( $_REQUEST['cfg'] ) { 
	die('Illegal call');
}

//notice $oTpl is filled and generated in file rights.inc.php this file renders $oTpl to browser
include_once($cfg['path']['contenido'].'includes/rights.inc.php');
$debug = 0;

//set the areas which are in use for selecting these

$sql = "SELECT A.idarea, A.idaction, A.idcat, B.name, C.name FROM ".$cfg["tab"]["rights"]." AS A, ".$cfg["tab"]["area"]." AS B, ".$cfg["tab"]["actions"]." AS C WHERE user_id='".Contenido_Security::escapeDB($userid, $db)."' AND idclient='".Contenido_Security::toInteger($rights_client)."' AND idlang='".Contenido_Security::toInteger($rights_lang)."' AND idcat='0' AND A.idaction = C.idaction AND A.idarea = B.idarea";
$db->query($sql);

$rights_list_old = array ();
while ($db->next_record()) { //set a new rights list fore this user
   $rights_list_old[$db->f(3)."|".$db->f(4)."|".$db->f("idcat")] = "x";
}

if (($perm->have_perm_area_action($area, $action)) && ($action == "user_edit"))
{
    saverights();
} else {
    if (!$perm->have_perm_area_action($area, $action))
    {
    	$notification->displayNotification("error", i18n("Permission denied"));
    }
}

$sJsBefore = '';
$sJsAfter = '';
$sJsExternal = '';
$sTable = '';

$sJsBefore .= "var areatree=new Array();\n";

if(!isset($rights_perms)||$action==""||!isset($action)){

    //search for the permissions of this user
    $sql="SELECT perms FROM ".$cfg["tab"]["phplib_auth_user_md5"]." WHERE user_id='$userid'";
    
    $db->query($sql);
    $db->next_record();
    $rights_perms=$db->f("perms");

}

$table = new Table($cfg["color"]["table_border"], "solid", 0, 2, $cfg["color"]["table_header"], $cfg["color"]["table_light"], $cfg["color"]["table_dark"], 0, 0);

$sTable .= $table->start_table();

$sTable .= $table->header_row();
$sTable .= $table->header_cell("&nbsp;","left");
$sTable .= $table->header_cell("&nbsp;","left");
$sTable .= $table->header_cell(i18n("Check all"),"left");
$sTable .= $table->end_row();

$sTable .= $table->header_row();
$sTable .= $table->header_cell('&nbsp',"center", '', '', 0);
$sTable .= $table->header_cell('&nbsp',"center", '', '', 0);
$sTable .= $table->header_cell("<input type=\"checkbox\" name=\"checkall\" value=\"\" onClick=\"setRightsForAllAreas()\">", "center", '', '', 0);
$sTable .= $table->end_row();

$nav = new Contenido_Navigation;
foreach($right_list as $key => $value){
        // look for possible actions in mainarea
        foreach($value as $key2 =>$value2)
              {
               if($key==$key2){
                       //does the user have the right
                                     if(in_array($value2["perm"]."|fake_permission_action|0",array_keys($rights_list_old)))
                              $checked="checked=\"checked\"";
                       else
                              $checked="";

                        $darkRow = !$darkRow;
                        if ($darkRow) {
                            $bgColor = $cfg["color"]["table_dark"];
                        } else {
                            $bgColor = $cfg["color"]["table_light"];
                        }
						
                       
			          /* Extract names from the XML document. */
			           $main = $nav->getName(str_replace('/overview', '/main', $value2['location']));

                       if ($debug)
                       {
                       	  $locationString = $value2["location"] . " " . $value2["perm"].  "-->".$main;
                       } else {
                          $locationString = $main;
                       }

                       $sTable .= $table->row();
					   $sTable .= $table->cell($locationString,"", "", " class=\"td_rights1\"", false);
					   $sTable .= $table->cell("<input type=\"checkbox\" name=\"rights_list[".$value2["perm"]."|fake_permission_action|0]\" value=\"x\" $checked>" ,"", "", " class=\"td_rights2\"", false);
					   $sTable .= $table->cell("<input type=\"checkbox\" name=\"checkall_$key\" value=\"\" onClick=\"setRightsForArea('$key')\">","", "", " class=\"td_rights2\"", false);
                       $sTable .= $table->end_row();

                        //set javscript array for areatree
                        $sJsBefore .= "
                              areatree[\"$key\"]=new Array();
                              areatree[\"$key\"][\"".$value2["perm"]."0\"]=\"rights_list[".$value2["perm"]."|fake_permission_action|0]\"\n";
						}

               //if there area some
               if(is_array($value2["action"]))
                 foreach($value2["action"] as $key3 => $value3)
                 {
                                             $idaction = $value3;
                          //does the user have the right
                          if(in_array($value2["perm"]."|$idaction|0",array_keys($rights_list_old)))
                              $checked="checked=\"checked\"";
                          else
                              $checked="";

                            $darkRow = !$darkRow;
                            if ($darkRow) {
                                $bgColor = $cfg["color"]["table_dark"];
                            } else {
                                $bgColor = $cfg["color"]["table_light"];
                            }
					   
                          //set the checkbox    the name consits of      areait+actionid+itemid
						  $sCellContent = '';
                          if ($debug)
                          {
                         		$sCellContent = "&nbsp;&nbsp;&nbsp;&nbsp; " . $value2["perm"] . " | ". $value3 . "-->".$lngAct[$value2["perm"]][$value3]."&nbsp;&nbsp;&nbsp;&nbsp;";
                          } else {
                          		if ($lngAct[$value2["perm"]][$value3] == "")
                          		{
                          			$sCellContent = "&nbsp;&nbsp;&nbsp;&nbsp; " . $value2["perm"] . "|" .$value3 ."&nbsp;&nbsp;&nbsp;&nbsp;";
                          	   		
                          		} else {
                          			$sCellContent = "&nbsp;&nbsp;&nbsp;&nbsp; " . $lngAct[$value2["perm"]][$value3]."&nbsp;&nbsp;&nbsp;&nbsp;";
                          		}
                          }
						  
							$sTable .= $table->row();
						    $sTable .= $table->cell($sCellContent,"left", "", " class=\"td_rights1\"", false);
						    $sTable .= $table->cell("<input type=\"checkbox\" id=\"rights_list[".$value2["perm"]."|$value3|0]\" name=\"rights_list[".$value2["perm"]."|$value3|0]\" value=\"x\" $checked>", false);
						    $sTable .= $table->cell("&nbsp;", false);
	                        $sTable .= $table->end_row();

                          //set javscript array for areatree
                          $sJsBefore .= "areatree[\"$key\"][\"".$value2["perm"]."$value3\"]=\"rights_list[".$value2["perm"]."|$value3|0]\"\n";

                 }
        }
        //checkbox for checking all actions fore this itemid
}

$sTable .= $table->row();
$sTable .= $table->sumcell("<a href=javascript:submitrightsform('','area')><img src=\"".$cfg['path']['images']."but_cancel.gif\" border=0></a><img src=\"images/spacer.gif\" width=\"20\"><a href=javascript:submitrightsform('user_edit','')><img src=\"".$cfg['path']['images']."but_ok.gif\" border=0></a>","right");
$sTable .= $table->end_row();
$sTable .= $table->end_table();

$oTpl->set('s', 'JS_SCRIPT_BEFORE', $sJsBefore);
$oTpl->set('s', 'JS_SCRIPT_AFTER', $sJsAfter);
$oTpl->set('s', 'RIGHTS_CONTENT', $sTable);
$oTpl->set('s', 'EXTERNAL_SCRIPTS', $sJsExternal);
$oTpl->generate('templates/standard/'.$cfg['templates']['rights_inc']);
?>
