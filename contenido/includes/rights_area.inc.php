<?php
// declare new javascript variables;
echo"<script type=\"text/javascript\">
     var areatree=new Array();
</script>";

$debug = 0;

//set the areas which are in use for selecting these

$sql = "SELECT A.idarea, A.idaction, A.idcat, B.name, C.name FROM ".$cfg["tab"]["rights"]." AS A, ".$cfg["tab"]["area"]." AS B, ".$cfg["tab"]["actions"]." AS C WHERE user_id='$userid' AND idclient='$rights_client' AND idlang='$rights_lang' AND idcat='0' AND A.idaction = C.idaction AND A.idarea = B.idarea";
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



if(!isset($rights_perms)||$action==""||!isset($action)){

    //search for the permissions of this user
    $sql="SELECT perms FROM ".$cfg["tab"]["phplib_auth_user_md5"]." WHERE user_id='$userid'";
    
    $db->query($sql);
    $db->next_record();
    $rights_perms=$db->f("perms");

}






echo"<table style=\"border:0px; border-left:1px; border-bottom: 1px;border-color: ". $cfg["color"]["table_border"] . "; border-style: solid;\" cellspacing=\"0\" cellpadding=\"2\" >";

echo"<tr class=\"textg_medium\" style=\"background-color: ". $cfg["color"]["table_header"] .";\">";
echo"<th valign=\"top\" style=\"border: 0px; border-top:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"left\">";
echo"<input type=\"hidden\" name=\"area\" value=\"user_areas\">";
echo "&nbsp;</TH>";
echo"<th class=\"textg_medium\" valign=\"top\" style=\"border: 0px; border-top:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"left\">&nbsp;</th>";
echo"<th class=\"textg_medium\" valign=\"top\" style=\"font-weight:normal;border: 0px; border-top:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"center\">";

//checkbox for all rights
echo i18n("Check all")."<br><input type=\"checkbox\" name=\"checkall\" value=\"\" onClick=\"setRightsForAllAreas()\"></TH></TR>";

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

                        echo"<tr class=\"text_medium\" style=\"background-color: ". $bgColor .";\">";
                       
			          /* Extract names from the XML document. */
			          $main = $nav->getName($value2['location']);
                        
                       if ($debug)
                       {
                       	  $locationString = $value2["location"] . " " . $value2["perm"].  "-->".$main;
                       } else {
                          $locationString = $main;
                       }
                       
                       echo"<td valign=\"top\" style=\"border: 0px; border-bottom:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"left\">";
                       echo $locationString;
                       echo "</td>";
                        echo"<td valign=\"top\" style=\"border: 0px; border-bottom:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"center\">";
                       echo"<input type=\"checkbox\" name=\"rights_list[".$value2["perm"]."|fake_permission_action|0]\" value=\"x\" $checked>";
                       echo "</td>";
                        echo"<td valign=\"top\" style=\"border: 0px; border-bottom:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"center\">";
                       echo "<input type=\"checkbox\" name=\"checkall_$key\" value=\"\" onClick=\"setRightsForArea('$key')\">
                            </TD>";
                       echo"</TR>";

                        //set javscript array for areatree
                        echo"<script type=\"text/javascript\">
                              areatree[\"$key\"]=new Array();
                              areatree[\"$key\"][\"".$value2["perm"]."0\"]=\"rights_list[".$value2["perm"]."|fake_permission_action|0]\";
                             </script>";

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
                          echo"<tr class=\"text_medium\" style=\"background-color: ". $bgColor .";\">";
                          echo"<td valign=\"top\" style=\"border: 0px; border-top:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"left\">";
                          
                          if ($debug)
                          {
                         		echo"&nbsp;&nbsp;&nbsp;&nbsp; " . $value2["perm"] . " | ". $value3 . "-->".$lngAct[$value2["perm"]][$value3]."&nbsp;&nbsp;&nbsp;&nbsp;</td>";
                          } else {
                          		if ($lngAct[$value2["perm"]][$value3] == "")
                          		{
                          			echo "&nbsp;&nbsp;&nbsp;&nbsp; " . $value2["perm"] . "|" .$value3 ."&nbsp;&nbsp;&nbsp;&nbsp;</td>";
                          	   		
                          		} else {
                          			echo "&nbsp;&nbsp;&nbsp;&nbsp; " . $lngAct[$value2["perm"]][$value3]."&nbsp;&nbsp;&nbsp;&nbsp;</td>";
                          		}
                          }
                       
                            echo"<td valign=\"top\" style=\"border: 0px; border-top:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"center\">";
                            echo "<input type=\"checkbox\" id=\"rights_list[".$value2["perm"]."|$value3|0]\" name=\"rights_list[".$value2["perm"]."|$value3|0]\" value=\"x\" $checked>
                          </td>";
                          echo"<td valign=\"top\" style=\"border: 0px; border-top:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"center\">&nbsp;</td>";
                          echo"</TR>";

                          //set javscript array for areatree
                          echo"<script type=\"text/javascript\">
                                areatree[\"$key\"][\"".$value2["perm"]."$value3\"]=\"rights_list[".$value2["perm"]."|$value3|0]\";
                               </script>";

                 }
        }
        //checkbox for checking all actions fore this itemid





//        emptyRow();
//        emptyCell();


        


}
echo"</tr>";

$darkRow = !$darkRow;
if ($darkRow) {
               $bgColor = $cfg["color"]["table_dark"];
} else {
               $bgColor = $cfg["color"]["table_light"];
}

echo"<tr class=\"text_medium\" style=\"background-color: ". $bgColor .";\">";

echo"<td valign=\"top\" style=\"border: 0px; border-top:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"right\" colspan=\"3\">";
echo "
<a href=javascript:submitrightsform('','area')><img src=\"".$cfg['path']['images']."but_cancel.gif\" border=0></a><img src=\"images/spacer.gif\" width=\"20\"><a href=javascript:submitrightsform('user_edit','')><img src=\"".$cfg['path']['images']."but_ok.gif\" border=0></a>
</td>
</tr>

</table></form>";

function emptyCell (){
	global $cfg;
	
    echo "<TD><img src=\"".$cfg['path']['images']."space.gif\" width=3 height=1 border=0></TD>";
}
function emptyRow (){
    echo "  <tr>
          <td colspan=20><table cellpadding=0 cellspacing=0 border=0 width=100%><tr><td bgcolor=#666666 height=1><img src=\"$img_vz/leer.gif\" width=1 height=1></TD></TR></TABLE></td>
        </tr>";
}
?>
