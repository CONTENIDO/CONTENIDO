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
 * @version    1.0.2
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
 *   modified 2008-07-28, Bilal Arslan, moved inline html to template
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id: grouprights_area.inc.php 1157 2010-05-20 14:10:43Z xmurrix $:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


//notice $oTpl is filled and generated in file rights.inc.php this file renders $oTpl to browser
include_once($cfg['path']['contenido'].'includes/grouprights.inc.php');
$debug = 0;
// declare new Template variables
$sJsBefore = '';
$sJsAfter = '';
$sJsExternal = '';
$sTable = '';

// declare new javascript variables;
$sJsBefore .= "var areatree=new Array();\n";

//set the areas which are in use fore selecting these

$sql = "SELECT A.idarea, A.idaction, A.idcat, B.name, C.name FROM ".$cfg["tab"]["rights"]." AS A, ".$cfg["tab"]["area"]." AS B, ".$cfg["tab"]["actions"]." AS C WHERE user_id='".Contenido_Security::escapeDB($groupid, $db)."' AND idclient='".Contenido_Security::toInteger($rights_client)."' AND idlang='".Contenido_Security::toInteger($rights_lang)."' AND idcat='0' AND A.idaction = C.idaction AND A.idarea = B.idarea";
$db->query($sql);
$rights_list_old = array ();
while ($db->next_record()) { //set a new rights list fore this user
   $rights_list_old[$db->f(3)."|".$db->f(4)."|".$db->f("idcat")] = "x";
}

if (($perm->have_perm_area_action($area, $action)) && ($action == "group_edit"))
{
    saverights();
} else {
    if (!$perm->have_perm_area_action($area, $action))
    {
    $notification->displayNotification("error", i18n("Permission denied"));
    }
}

if(!isset($rights_perms)||$action==""||!isset($action))
{
    //search for the permissions of this user
    $sql="SELECT perms FROM ".$cfg["tab"]["groups"]." WHERE group_id='".Contenido_Security::escapeDB($groupid, $db)."'";
    
    $db->query($sql);
    $db->next_record();
    $rights_perms=$db->f("perms");
}

$oTable = new Table($cfg["color"]["table_border"], "solid", 0, 2, $cfg["color"]["table_header"], $cfg["color"]["table_light"], $cfg["color"]["table_dark"], 0, 0);

$sTable .= $oTable->start_table();

$sTable .= $oTable->header_row();
$sTable .= $oTable->header_cell("&nbsp;","left");
$sTable .= $oTable->header_cell("&nbsp;","left");
$sTable .= $oTable->header_cell(i18n("Check all"),"left");
$sTable .= $oTable->end_row();

//checkbox for all rights
$sTable .= $oTable->header_row();
$sTable .= $oTable->header_cell('&nbsp',"center", '', '', 0);
$sTable .= $oTable->header_cell('&nbsp',"center", '', '', 0);
$sTable .= $oTable->header_cell("<input type=\"checkbox\" name=\"checkall\" value=\"\" onClick=\"setRightsForAllAreas()\">", "center", '', '', 0);
$sTable .= $oTable->end_row();

//Select the itemid´s
if ($xml->load($cfg['path']['xml'] . $cfg['lang'][$belang]) == false)
{
	if ($xml->load($cfg['path']['xml'] . 'lang_en_US.xml') == false)
	{
		die("Unable to load any XML language file");
	}
}

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

					/* Extract names from the XML document. */
			          $main = $nav->getName(str_replace('/overview', '/main', $value2['location']));
                        
                       if ($debug)
                       {
                       	  $locationString = $value2["location"] . " " . $value2["perm"].  "-->".$main;
                       } else {
                          $locationString = $main;
                       }
					   
					   $sTable .= $oTable->row();
					   $sTable .= $oTable->cell($locationString,"", "", " class=\"td_rights1\"", false);
					   $sTable .= $oTable->cell("<input type=\"checkbox\" name=\"rights_list[".$value2["perm"]."|fake_permission_action|0]\" value=\"x\" $checked>" ,"", "", " class=\"td_rights2\"", false);
					   $sTable .= $oTable->cell("<input type=\"checkbox\" name=\"checkall_$key\" value=\"\" onClick=\"setRightsForArea('$key')\">","", "", " class=\"td_rights2\"", false);
                       $sTable .= $oTable->end_row();

                        //set javscript array for areatree
                        $sJsBefore .= "
								areatree[\"$key\"]=new Array();
								areatree[\"$key\"][\"".$value2["perm"]."0\"]=\"rights_list[".$value2["perm"]."|fake_permission_action|0]\";\n";
						
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
							$sTable .= $oTable->row();
							$sTable .= $oTable->cell($sCellContent,"left", "", " class=\"td_rights1\"", false);
							$sTable .= $oTable->cell("<input type=\"checkbox\" id=\"rights_list[".$value2["perm"]."|$value3|0]\" name=\"rights_list[".$value2["perm"]."|$value3|0]\" value=\"x\" $checked>", false);
							$sTable .= $oTable->cell("&nbsp;", false);
	                        $sTable .= $oTable->end_row();
							
                          //set javscript array for areatree
                          $sJsBefore .= "areatree[\"$key\"][\"".$value2["perm"]."$value3\"]=\"rights_list[".$value2["perm"]."|$value3|0]\";";

                 }
        }

}

//checkbox for checking all actions fore this itemid
$sTable .= $oTable->row();
$sTable .= $oTable->sumcell("<a href=javascript:submitrightsform('','area')><img src=\"".$cfg['path']['images']."but_cancel.gif\" border=0></a><img src=\"images/spacer.gif\" width=\"20\"><a href=javascript:submitrightsform('group_edit','')><img src=\"".$cfg['path']['images']."but_ok.gif\" border=0></a>","right");
$sTable .= $oTable->end_row();
$sTable .= $oTable->end_table();

$oTpl->set('s', 'JS_SCRIPT_BEFORE', $sJsBefore);
$oTpl->set('s', 'JS_SCRIPT_AFTER', $sJsAfter);
$oTpl->set('s', 'RIGHTS_CONTENT', $sTable);
$oTpl->set('s', 'EXTERNAL_SCRIPTS', $sJsExternal);
$oTpl->generate('templates/standard/'.$cfg['templates']['rights_inc']);

?>
