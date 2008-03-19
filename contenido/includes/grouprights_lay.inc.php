<?php
//set the areas which are in use fore selecting these
$possible_area = "'".implode("','", $area_tree[$perm->showareas("lay")])."'";
$sql = "SELECT A.idarea, A.idaction, A.idcat, B.name, C.name FROM ".$cfg["tab"]["rights"]." AS A, ".$cfg["tab"]["area"]." AS B, ".$cfg["tab"]["actions"]." AS C WHERE user_id='$groupid' AND idclient='$rights_client' AND A.type = 1 AND idlang='$rights_lang' AND B.idarea IN ($possible_area) AND idcat!='0' AND A.idaction = C.idaction AND A.idarea = C.idarea AND A.idarea = B.idarea";
$db->query($sql);
$rights_list_old = array ();
while ($db->next_record()) { //set a new rights list fore this user
$rights_list_old[$db->f(3)."|".$db->f(4)."|".$db->f("idcat")] = "x";
}

// declare new javascript variables;
echo"<script type=\"text/javascript\">
     var itemids=new Array();
     var actareaids=new Array();
</script>";

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
echo"<input type=\"hidden\" name=\"area\" value=\"groups_layout\">";
echo"<table style=\"border:0px; border-left:1px; border-bottom: 1px;border-color: ". $cfg["color"]["table_border"] . "; border-style: solid;\" cellspacing=\"0\" cellpadding=\"2\" >";
echo"<tr class=\"textw_medium\" style=\"background-color: ". $cfg["color"]["table_header"] .";\">";
echo"<th class=\"textg_medium\" valign=\"top\" style=\"border: 0px; border-top:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"left\">";
echo i18n("Layout name")."</TH>";
echo"<th class=\"textg_medium\" valign=\"top\" style=\"border: 0px; border-top:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"left\">";
echo i18n("Description")."</TH>";

$possible_areas=array();
$sCheckboxesRow = '';
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
		echo"<script type=\"text/javascript\">actareaids[\"$value3|".$value2["perm"]."\"]=\"x\";</script>";

		//checkbox for the whole action
		echo"<th class=\"textg_medium\" valign=\"top\" style=\"border: 0px; border-top:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"center\">";
		echo $lngAct[$value2["perm"]][$value3]."</TH>";
        $sCheckboxesRow .= "<td class=\"textg_medium\" valign=\"top\" style=\"border: 0px; border-top:0px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"center\" valign=\"bottom\"><input type=\"checkbox\" name=\"checkall_".$value2["perm"]."_$value3\" value=\"\" onClick=\"setRightsFor('".$value2["perm"]."','$value3','')\"></td>";
	}
}

        //checkbox for all rights
        echo"<th class=\"textg_medium\" valign=\"top\" style=\"border: 0px; border-top:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"center\">";
        echo i18n('Check all')."</TH></TR>";
        $colspan++;

        echo "<tr style=\"background-color: ". $cfg["color"]["table_header"] .";\">
                    <td class=\"textg_medium\" valign=\"top\" style=\"border: 0px; border-top:0px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"left\">&nbsp;</td>
                    <td class=\"textg_medium\" valign=\"top\" style=\"border: 0px; border-top:0px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"left\">&nbsp;</td>
                    ".$sCheckboxesRow."
                    <td class=\"textg_medium\" valign=\"top\" style=\"border: 0px; border-top:0px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"center\"><input type=\"checkbox\" name=\"checkall\" value=\"\" onClick=\"setRightsForAll()\"></td>
              </tr>";

//Select the itemid´s
$sql = "SELECT * FROM ".$cfg["tab"]["lay"]." WHERE idclient='$rights_client' ORDER BY name";
$db->query($sql);


while ($db->next_record()) {

$tplname     = htmlentities($db->f("name"));
$description = htmlentities($db->f("description"));

$darkrow = !$darkrow;

if ($darkrow)
{
$bgcolor =  $cfg["color"]["table_dark"];
} else {
$bgcolor =  $cfg["color"]["table_light"];
}
echo"<tr class=\"text_medium\" style=\"background-color: ". $bgcolor .";\">";
echo"<td valign=\"top\" style=\"border: 0px; border-bottom:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"left\">";
echo "$tplname</TD>";
echo"<td valign=\"top\" style=\"border: 0px; border-bottom:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"left\">";

echo"$description&nbsp;</TD>";

//set javscript array for itemids
echo"<script type=\"text/javascript\">
                     itemids[\"".$db->f("idlay")."\"]=\"x\";
             </script>";

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

echo"<td valign=\"top\" style=\"border: 0px; border-bottom:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"center\">";
//set the checkbox    the name consits of      areait+actionid+itemid
echo "<input type=\"checkbox\"  name=\"rights_list[".$value2["perm"]."|$value3|".$db->f("idlay")."]\" value=\"x\" $checked>
                          </TD>";


}
}
//checkbox for checking all actions fore this itemid
echo"<td valign=\"top\" style=\"border: 0px; border-bottom:1px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"center\">";
echo "<input type=\"checkbox\" name=\"checkall_".$value2["perm"]."_".$value3."_".$db->f("idlay")."\" value=\"\" onClick=\"setRightsFor('".$value2["perm"]."','$value3','".$db->f("idlay")."')\"></TH>";

}


echo"</tr>";
$darkrow = !$darkrow;

if ($darkrow)
{
$bgcolor =  $cfg["color"]["table_dark"];
} else {
$bgcolor =  $cfg["color"]["table_light"];
}
echo"<tr class=\"text_medium\" style=\"background-color: ". $bgcolor .";\">";
echo"<td valign=\"top\" style=\"border: 0px; border-top:0px; border-right:1px; border-color: " . $cfg["color"]["table_border"] . "; border-style: solid;\" align=\"right\" colspan=\"6\">";
echo "
<a href=javascript:submitrightsform('','area')><img src=\"".$cfg['path']['images']."but_cancel.gif\" border=0></a><img src=\"images/spacer.gif\" width=\"20\"> <a href=javascript:submitrightsform('group_edit','')><img src=\"".$cfg['path']['images']."but_ok.gif\" border=0></a>
</td>
</tr>

</table></form>";











?>
