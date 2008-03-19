<?php
/*****************************************
* File      :   $RCSfile: include.grouprights_menu.php,v $
* Project   :   Contenido
* Descr     :   Contenido Groups Menu
*
* Author    :   Timo A. Hummel
*               
* Created   :   30.05.2003
* Modified  :   $Date: 2006/04/28 09:20:54 $
*
* © four for business AG, www.4fb.de
*
* $Id: include.grouprights_menu.php,v 1.11 2006/04/28 09:20:54 timo.hummel Exp $
******************************************/



$tpl->reset();
$tpl->set('s', 'SID', $sess->id);

if (($action == "group_delete") && ($perm->have_perm_area_action($area, $action))) {

   $sql = "DELETE FROM "
             .$cfg["tab"]["groups"].	
          " WHERE
             group_id = \"" .$groupid."\"";
   $db->query($sql);
   
   $sql = "DELETE FROM ".
   			$cfg["tab"]["groupmembers"].
   			" WHERE group_id = \"" .$groupid."\"";
   			
   $db->query($sql);
          
          
   $sql = "DELETE FROM ".
   			$cfg["tab"]["rights"].
   			" WHERE user_id = \"" .$groupid."\"";
   			
   $db->query($sql);          
}

$sql = "SELECT
            groupname, group_id, perms
        FROM
            ".$cfg["tab"]["groups"]."
        ORDER BY
            groupname ASC";


if ($restriction == 1)
{
	 $sql = "SELECT
            A.groupname AS groupname, A.group_id as group_id, A.perms as perms
        FROM
            ".$cfg["tab"]["groups"]." AS A,
            ".$cfg["tab"]["rights"]." AS B,
			".$cfg["tab"]["actions"]." AS C
        WHERE
        	C.name = 'front_allow' AND
			B.user_id = A.group_id AND
        	C.idaction = B.idaction AND
			A.perms LIKE ''
		GROUP BY
			group_id
        ORDER BY
            groupname ASC";
            
}

if ($restriction == 3)
{
	 $sql = "SELECT
            A.groupname AS groupname, A.group_id as group_id, A.perms as perms
        FROM
            ".$cfg["tab"]["groups"]." AS A,
            ".$cfg["tab"]["rights"]." AS B,
			".$cfg["tab"]["actions"]." AS C
        WHERE
        	C.name NOT LIKE 'front_allow' AND
			B.user_id = A.group_id AND
        	C.idaction = B.idaction AND
			A.perms NOT LIKE ''
		GROUP BY
			group_id
        ORDER BY
            groupname ASC";
}
$db->query($sql);


// Empty Row
$bgcolor = '#FFFFFF';
$tpl->set('s', 'PADDING_LEFT', '10');
$thisperm = split(",", $auth->auth["perm"]);

$accessibleClients = $classclient->getAccessibleClients();

while ($db->next_record())
{

    $groupperm = split(",", $db->f("perms"));

    $allow = false; 
    
    // Sysadmin check
    if (in_array("sysadmin", $thisperm))
    {
        $allow = true;
    }

    // Admin check
    foreach ($accessibleClients as $key => $value)
    {
        if (in_array("client[".$key."]", $groupperm))
        {
            $allow = true;
        }
    }
    
    // Group check
    foreach ($groupperm as $localperm)
    {
        
        if (in_array($localperm, $thisperm))
        {
            $allow = true;
        }

    }

    if ($allow == true)
    {

        $dark = !$dark;
        if ($dark) {
            $bgColor = $cfg["color"]["table_dark"];
        } else {
            $bgColor = $cfg["color"]["table_light"];
        }

        $groupid = $db->f("group_id");
        $groupname = $db->f("groupname");
        
        $groupname = substr($groupname, 4);
        

        $tmp_mstr = '<a href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
        $area = "groups";
        $mstr = sprintf($tmp_mstr, 'right_top',
                                       $sess->url("main.php?area=$area&frame=3&groupid=$groupid"),
                                       'right_bottom',
                                       $sess->url("main.php?area=groups_overview&frame=4&groupid=$groupid"),
                                       $groupname);
                                       
        $mstr2 = sprintf($tmp_mstr, 'right_top',
                                       $sess->url("main.php?area=$area&frame=3&groupid=$groupid"),
                                       'right_bottom',
                                       $sess->url("main.php?area=groups_overview&frame=4&groupid=$groupid"),
                                       '<img src="images/spacer.gif" border="0" width="15">');                                       

        if ($perm->have_perm_area_action('groups',"groups_delete") ) { 
        	    $message = sprintf(i18n("Do you really want to delete the group %s?"),htmlspecialchars($groupname));
                $deletebutton = "<a onClick=\"event.cancelBubble=true;check=confirm('".$message."'); if (check==true) { location.href='".$sess->url("main.php?area=groups&action=group_delete&frame=$frame&groupid=$groupid&del=")."#deletethis'};\" href=\"#\"><img src=\"".$cfg['path']['images']."delete.gif\" border=\"0\" width=\"13\" height=\"13\" alt=\"".$lngUpl["delfolder"]."\" title=\"".$lngUpl["delgroup"]."\"></a>";
            } else {
                $deletebutton = "";
            }
        
        if ($_GET['groupid'] == $groupid) {
            $bgColor = $cfg['color']['table_light_active'];
            $tpl->set('d', 'ID_MARKED', 'marked');
        } else {
            $tpl->set('d', 'ID_MARKED', '');
        }
        
        $tpl->set('d', 'BGCOLOR', $bgColor);
        $tpl->set('d', 'TEXT', $mstr);
        $tpl->set('d', 'ICON', $mstr2);
        
        if ($perm->have_perm_area_action('groups',"groups_delete") ) {
 		$delTitle = i18n("Delete group");
    	$delDescr = sprintf(i18n("Do you really want to delete the following group:<br><br>%s<br>"),htmlspecialchars($groupname));
        

    	$tpl->set('d', 'DELETE', '<a title="'.$delTitle.'" href="javascript://" onclick="box.confirm(\''.$delTitle.'\', \''.$delDescr.'\', \'deleteGroup(\\\''.$groupid.'\\\')\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$delTitle.'" alt="'.$delTitle.'"></a>');
        } else {
        	$tpl->set('d', 'DELETE', '&nbsp;');
        }

        $tpl->next();
    }
}



# Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['grouprights_menu']);

?>
