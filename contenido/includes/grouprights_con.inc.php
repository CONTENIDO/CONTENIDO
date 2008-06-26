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
 * @version    1.0.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-26, Dominik Ziegler, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

//set the areas which are in use fore selecting these
$possible_area = "'".implode("','", $area_tree[$perm->showareas("con")])."'";
$sql = "SELECT A.idarea, A.idaction, A.idcat, B.name, C.name FROM ".$cfg["tab"]["rights"]." AS A, ".$cfg["tab"]["area"]." AS B, ".$cfg["tab"]["actions"]." AS C WHERE user_id='".Contenido_Security::escapeDB($groupid, $db)."' AND idclient='".Contenido_Security::toInteger($rights_client)."' AND A.type = 1 AND idlang='".Contenido_Security::toInteger($rights_lang)."' AND B.idarea IN ($possible_area) AND idcat!='0' AND A.idaction = C.idaction AND A.idarea = C.idarea AND A.idarea = B.idarea";
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
        echo '<script type="text/javascript" src="scripts/addImageTags.js"></script>';
        echo '<script type="text/javascript" src="scripts/expandCollapse.js"></script>';

        // declare new javascript variables;
        echo"<script type=\"text/javascript\">
              var itemids=new Array();
              var actareaids=new Array();
        </script>";

        $colspan=0;
        echo "<br>";
        
        echo"<table style=\"border:0px; border-left:1px; border-bottom: 1px;border-color: ". $cfg["color"]["table_border"] . "; border-style: solid;\" cellspacing=\"0\" cellpadding=\"2\" >";
       
        echo"<tr class=\"textw_medium\" style=\"background-color: ". $cfg["color"]["table_header"] .";\">";
        echo"<th class=\"textg_medium\" valign=\"top\" style=\"border: 0px; border-top:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"left\">";
        echo i18n("Category")."</TH>";
		echo"<th class=\"textg_medium\" valign=\"top\" style=\"border: 0px; border-top:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"left\">&nbsp;</TH>";
		
        $possible_areas=array();


        $sCheckboxesRow = '';
        // look for possible actions   in mainarea []   in str and con
        foreach($right_list["con"] as $value2)
        {
               //if there are some actions
               if(is_array($value2["action"]))
                 foreach($value2["action"] as $key3 => $value3)
                 {  
                    if ((in_array($value3, $aViewRights) && !$bExclusive) || 
                        (!in_array($value3, $aViewRights) && $bExclusive) ||
                        (count($aViewRights) == 0)) {
                        //set the areas that are in use
                         $possible_areas[$value2["perm"]]="";


                         $colspan++;
                         //set  the possible areas and actions for this areas

                         //checkbox for the whole action
                         echo"<th class=\"textg_medium\" valign=\"top\" style=\"border: 0px; border-top:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"center\" valign=\"bottom\">";
                         echo"<script type=\"text/javascript\">
                               actareaids[\"$value3|".$value2["perm"]."\"]=\"x\";
                              </script>"; 
                         echo $lngAct[$value2["perm"]][$value3]."</TH>";
                         $sCheckboxesRow .= "<td class=\"textg_medium\" valign=\"top\" style=\"border: 0px; border-top:0px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"center\" valign=\"bottom\"><input type=\"checkbox\" name=\"checkall_".$value2["perm"]."_$value3\" value=\"\" onClick=\"setRightsFor('".$value2["perm"]."','$value3','')\"></td>";
                    }
                 }
        }

        //checkbox for all rights
        echo"<th class=\"textg_medium\" valign=\"top\" style=\"border: 0px; border-top:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"center\">";
        echo i18n('Check all')."</TH></TR>";
        
        echo "<tr style=\"background-color: ". $cfg["color"]["table_header"] .";\">
                    <td class=\"textg_medium\" valign=\"top\" style=\"border: 0px; border-top:0px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"left\">&nbsp;</td>
                    <td class=\"textg_medium\" valign=\"top\" style=\"border: 0px; border-top:0px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"left\">&nbsp;</td>
                    ".$sCheckboxesRow."
                    <td class=\"textg_medium\" valign=\"top\" style=\"border: 0px; border-top:0px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"center\"><input type=\"checkbox\" name=\"checkall\" value=\"\" onClick=\"setRightsForAll()\"></td>
              </tr>";
        
        
        $colspan++;

        $sql = "SELECT A.idcat, level, name,parentid FROM ".$cfg["tab"]["cat_tree"]." AS A, ".$cfg["tab"]["cat"]." AS B, ".$cfg["tab"]["cat_lang"]." AS C WHERE A.idcat=B.idcat AND B.idcat=C.idcat AND C.idlang='".Contenido_Security::toInteger($rights_lang)."' AND B.idclient='".Contenido_Security::toInteger($rights_client)."' ORDER BY idtree";

        $db->query($sql);
        $counter=array();
        $parentid="leer";
        $sScript = '';
        
        $aRowname = array();
        $iLevel = 0;
        
        while ($db->next_record()) {

                if ($db->f("level") == 0 && $db->f("preid") != 0) {
                        echo "<TR><TD colspan=13>&nbsp;</TD></TR>";
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
                        if($parentid==$db->f("parentid")){

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

                        $spaces='<img src="images/spacer.gif" height="1" width="'.($db->f("level")*15).'"><a><img src="images/spacer.gif" width="7" id="'.implode('_', $aRowname).'_img"></a>';
                        
                        #$spaces = "";
						#for ($i=0; $i<$db->f("level"); $i++) {
                        #     $spaces = $spaces . "&nbsp;&nbsp;&nbsp;&nbsp;";
                        #}

                        $darkRow = !$darkRow;
                        if ($darkRow) {
                            $bgColor = $cfg["color"]["table_dark"];
                        } else {
                            $bgColor = $cfg["color"]["table_light"];
                        }

                        echo"<tr class=\"text_medium\" id=\"".implode('_', $aRowname)."\" style=\"background-color: ". $bgColor .";\">";
						echo"<td class=\"td_rights0\">";
                        $sScript.="itemids[\"".$db->f("idcat")."\"]=\"x\";\n";

                        echo "$spaces ".$db->f("name")."</td>";
                        echo"<td class=\"td_rights1\">";
                        echo "<a href=\"javascript:rightsInheritanceUp('$parentid','$counter[$parentid]')\" class=\"action\"><img border=\"0\" src=\"images/pfeil_links.gif\"></a><img src=\"images/spacer.gif\" width=\"3\"><a href=\"javascript:rightsInheritanceDown('".$db->f("idcat")."')\" class=\"action\"><img border=\"0\" src=\"images/pfeil_runter.gif\"></a></TD>";

                        // look for possible actions in mainarea[]

                        foreach($right_list["con"] as $value2){
                            //if there area some
                            if(is_array($value2["action"]))
                              foreach($value2["action"] as $key3 => $value3)
                              {
                                 if ((in_array($value3, $aViewRights) && !$bExclusive) || 
                                    (!in_array($value3, $aViewRights) && $bExclusive) ||
                                    (count($aViewRights) == 0)) {
                                       //does the user have the right
                                       if(in_array($value2["perm"]."|$value3|".$db->f("idcat"),array_keys($rights_list_old)))
                                           $checked="checked=\"checked\"";
                                       else
                                           $checked="";

                                       //set the checkbox    the name consits of      areaid+actionid+itemid        the    id  =  parebntid+couter for these parentid+areaid+actionid
                                       echo"<td class=\"td_rights2\"><input type=\"checkbox\" id=\"str_".$parentid."_".$counter[$parentid]."_".$value2["perm"]."_$value3\" name=\"rights_list[".$value2["perm"]."|$value3|".$db->f("idcat")."]\" value=\"x\" $checked></td>";
                                }
                              }
                        }

                         //checkbox for checking all actions fore this itemid
                         echo"<td class=\"td_rights3\"><input type=\"checkbox\" name=\"checkall_".$value2["perm"]."_".$value3."_".$db->f("idcat")."\" value=\"\" onClick=\"setRightsFor('".$value2["perm"]."','$value3','".$db->f("idcat")."')\"></td>";
                }

}

echo"</tr>";
                        $darkRow = !$darkRow;
                        if ($darkRow) {
                            $bgColor = $cfg["color"]["table_dark"];
                        } else {
                            $bgColor = $cfg["color"]["table_light"];
                        }

                        $colspan = $colspan+2;
                        
echo"<tr class=\"text_medium\" style=\"background-color: ". $bgColor .";\">";
echo"<td valign=\"top\" style=\"border: 0px; border-top:0px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"right\" nowrap colspan=$colspan>
<a href=javascript:submitrightsform('','area')><img src=\"".$cfg['path']['images']."but_cancel.gif\" border=0></a><img src=\"images/spacer.gif\" width=\"20\"> <a href=javascript:submitrightsform('group_edit','')><img src=\"".$cfg['path']['images']."but_ok.gif\" border=0></a>
</td>
</tr>

</table></form>
<script type=\"text/javascript\">$sScript</script>";
echo "<script type=\"text/javascript\">
    aTranslations = new Object();
    aTranslations['pfeil_links.gif'] = '".i18n("Apply rights for this category to all categories on the same level or above")."';
    aTranslations['pfeil_runter.gif'] = '".i18n("Apply rights for this category to all categories below the current category")."';
    setImageTags(aTranslations);
    
    init('".i18n("Open category")."', '".i18n("Close category")."');
</script>";
echo "</body></html>";
?>
