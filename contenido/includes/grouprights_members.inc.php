<?php
/*****************************************
* File      :   $RCSfile: grouprights_members.inc.php,v $
* Project   :   Contenido
* Descr     :   Contenido Group Member Edit Page
*
* Author    :   Timo A. Hummel
*               
* Created   :   03.06.2003
* Modified  :   $Date: 2006/07/07 09:47:28 $
*
* © four for business AG, www.4fb.de
*
* $Id: grouprights_members.inc.php,v 1.13 2006/07/07 09:47:28 andreas.lindner Exp $
******************************************/

$db2 = new DB_Contenido;
$tpl3 = new Template;

if(!$perm->have_perm_area_action($area,$action))
{
  $notification->displayNotification("error", i18n("Permission denied"));
} else {
    if ( !isset($groupid) )
    {

    } else {
    	if (($action == "group_deletemember") &&( $perm->have_perm_area_action($area, $action)))
    	{
            $aDeleteMembers = array();
            if (!is_array($_POST['user_in_group'])) {
                if ($_POST['user_in_group'] > 0) {
                    array_push($aDeleteMembers, $_POST['user_in_group']);
                }
            } else {
                $aDeleteMembers = $_POST['user_in_group'];
            }
            
            foreach ($aDeleteMembers as $idgroupuser) {
                $idgroupuser = (int) $idgroupuser;
            
                $sql = "DELETE FROM "
    		 			.$cfg["tab"]["groupmembers"]."
    				WHERE idgroupuser = '". $idgroupuser ."'";
                $db->query($sql);
            }
    	}
    	
        if (($action == "group_addmember") && ($perm->have_perm_area_action($area, $action)))
        {
               		if (is_array($newmember))
               		{
               			foreach ($newmember as $key => $value)
               			{
               				$myUser = new User();
                   			
                   			if (!$myUser->loadUserByUserID($value))
                   			{
                   				$myUser->loadUserByUserName($value);
                   			}
                   			
                   			if ($myUser->getField("user_id") == "")
                   			{
     
                   			} else {
                   				
                   				$sql = "SELECT * FROM
                   						".$cfg["tab"]["groupmembers"]." WHERE
                   						group_id = '".$groupid."' AND
        								user_id = '".$myUser->getField("user_id")."'";
    							$db->query($sql);
    							if (!$db->next_record())
    							{
                       				$nextid = $db->nextid($cfg["tab"]["groupmembers"]);
                       				$sql = "INSERT INTO
            								 ".$cfg["tab"]["groupmembers"]."
            								SET idgroupuser = '".$nextid."',
            									group_id = '".$groupid."',
            									user_id = '".$myUser->getField("user_id")."'";
            									
            						$db->query($sql);
                       			
    								if ($notiAdded == "")
    								{
    									$notiAdded .= $myUser->getField("realname");
    								} else {
    									$notiAdded .= ", ".$myUser->getField("realname");
    								}
    							} else {
    								if ($notiAlreadyExisting == "")
    								{
    									$notiAlreadyExisting .= $myUser->getField("realname");
    								} else {
    									$notiAlreadyExisting .= ", ".$myUser->getField("realname");
    								}
    							
    							}
                   			}
                   		}
               		}
        }
    	$tab1 = $cfg["tab"]["groupmembers"];
    	$tab2 = $cfg["tab"]["phplib_auth_user_md5"];
    	
    	$sortby = getEffectiveSetting ("backend","sort_backend_users_by","");
    	
    	if ($sortby!='') {	
    		$sql = "select ".$tab1.".idgroupuser, ".$tab1.".user_id FROM ".$tab1." 
    				INNER JOIN ".$tab2." ON ".$tab1.".user_id = ".$tab2.".user_id WHERE
    				group_id = '".$groupid."' order by ".$tab2.".".$sortby;
    	} else {
    		#Show previous behaviour by default
    	    $sql = "select ".$tab1.".idgroupuser, ".$tab1.".user_id FROM ".$tab1." 
    				INNER JOIN ".$tab2." ON ".$tab1.".user_id = ".$tab2.".user_id WHERE
    				group_id = '".$groupid."' order by ".$tab2.".realname, ".$tab2.".username";
    	}

        $db->query($sql);
        
        $sInGroupOptions = '';
        $aAddedUsers = array();
        $myUser = new User();
        
        while ($db->next_record())
        {
        	$bgColor = !$bgColor;
        	
        	if ($bgColor) {
        		$color = $cfg["color"]["table_light"];
        	} else {
        		$color = $cfg["color"]["table_dark"];
        	}

    		$myUser->loadUserByUserID($db->f("user_id"));
    	    $aAddedUsers[] = $myUser->getField("username");
            
            $sOptionLabel = $myUser->getField("realname").' ('.$myUser->getField("username").')';
            $sOptionValue = $db->f("idgroupuser");
            $sInGroupOptions .= '<option value="'.$sOptionValue.'">'.$sOptionLabel.'</option>'."\n";
        }
        
        $tpl3->set('s', 'IN_GROUP_OPTIONS', $sInGroupOptions);
     
        $bgColor = !$bgColor;
        
        if ($bgColor) {
            $color = $cfg["color"]["table_light"];
        } else {
            $color = $cfg["color"]["table_dark"];
        }

        $userlist = new Users;
        $users = $userlist->getAccessibleUsers(split(',',$auth->auth["perm"]));
        
        $sortby = getEffectiveSetting ("backend","sort_backend_users_by","");
        if ($sortby!='') {
            //Sort user list by given criteria
            unset($users2);
            $sql = "SELECT * FROM ".$cfg["tab"]["phplib_auth_user_md5"]." ORDER BY ".$sortby;
            $db->query($sql);
            while ($db->next_record()) {
                $users2[$db->f("user_id")] = $users[$db->f("user_id")];
            }
            $users = $users2;
        }

        $bAddedUser = false;
        $sNonGroupOptions = '';
        if ( is_array($users) ) {
            
            foreach ($users as $key => $value) {
                if (!in_array($value["username"], $aAddedUsers))
                {
                    $bAddedUser = true;
                    
                    $sOptionLabel = $value["realname"] . " (".$value["username"].")";
                    $sOptionValue = $key;
                    $sNonGroupOptions .= '<option value="'.$sOptionValue.'">'.$sOptionLabel.'</option>'."\n";
                }
            }
            
        } 
        $tpl3->set('s', 'NON_GROUP_OPTIONS', $sNonGroupOptions);

        $tpl3->set('s', 'CATNAME', i18n("Manage group members"));
        $tpl3->set('s', 'BGCOLOR',  $cfg["color"]["table_header"]);
        $tpl3->set('s', 'BGCOLOR_CONTENT',  $cfg["color"]["table_dark"]);
        $tpl3->set('s', 'BORDERCOLOR', $cfg["color"]["table_border"]);
        $tpl3->set('s', 'CATFIELD', "&nbsp;");
        $tpl3->set('s', 'FORM_ACTION', $sess->url('main.php'));
        $tpl3->set('s', 'AREA', $area);
        $tpl3->set('s', 'GROUPID', $groupid);
        $tpl3->set('s', 'FRAME', $frame);
        $tpl3->set('s', 'IDLANG', $lang);
        $tpl3->set('s', 'RECORD_ID_NAME', 'groupid');
        $tpl3->set('s', 'ADD_ACTION', 'group_addmember');
        $tpl3->set('s', 'DELETE_ACTION', 'group_deletemember');
        $tpl3->set('s', 'STANDARD_ACTION', 'group_addmember');
        $tpl3->set('s', 'IN_GROUP_VALUE', $_POST['filter_in']);
        $tpl3->set('s', 'NON_GROUP_VALUE', $_POST['filter_non']);
        $tpl3->set('s', 'DISPLAY_OK', 'none');
        $tpl3->set('s', 'RELOADSCRIPT', '');

        # Generate template
        $tpl3gen = $tpl3->generate($cfg['path']['templates'] . $cfg['templates']['grouprights_memberselect'],true);
        echo $tpl3gen;
    }
}
?>
