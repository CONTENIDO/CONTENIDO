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
		$sql = "DELETE FROM "
		 			.$cfg["tab"]["groupmembers"]."
				WHERE idgroupuser = '". $idgroupuser ."'";
		$db->query($sql);
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

               		if ($notiAdded != "")
               		{
               			$notification->displayNotification("info", i18n("The following users were added to this group").": ".$notiAdded);
               		}               		
               		if ($notiAlreadyExisting != "")
               		{
               			$notification->displayNotification("warning", i18n("The following users are already existing in the group and where not added").": ".$notiAlreadyExisting);
               		}

               		
           		}

        
 }    
        


    $tpl->reset();
    

	$tab1 = $cfg["tab"]["groupmembers"];
	$tab2 = $cfg["tab"]["phplib_auth_user_md5"];
	
	$sortby = getEffectiveSetting ("backend","sort_backend_users_by","");
	
	if ($sortby!='') {	
		$sql = "select ".$tab1.".idgroupuser, ".$tab1.".user_id FROM ".$tab1." 
				INNER JOIN ".$tab2." ON ".$tab1.".user_id = ".$tab2.".user_id WHERE
				group_id = '".$groupid."' order by ".$tab2.".".$sortby;
	} else {
		#Show previous behaviour by default
	    $sql = "select idgroupuser, user_id FROM ". $cfg["tab"]["groupmembers"] ." WHERE
	            group_id = '".$groupid."'";
	}

    $db->query($sql);

    $form = '<form name="group_properties" method="post" action="'.$sess->url("main.php?").'">
                 '.$sess->hidden_session().'
                 <input type="hidden" name="area" value="'.$area.'">
                 <input type="hidden" name="action" value="group_addmember">
                 <input type="hidden" name="frame" value="'.$frame.'">
				 <input type="hidden" name="groupid" value="'.$groupid.'">
                 <input type="hidden" name="idlang" value="'.$lang.'">';
                 
 
    
    
    $tpl->set('s', 'FORM', $form);
    $tpl->set('s', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    $tpl->set('s', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('s', 'SUBMITTEXT', i18n("Save changes"));
    $tpl->set('s', 'CANCELTEXT', i18n("Discard changes"));
    $tpl->set('s', 'CANCELLINK', $sess->url("main.php?area=$area&frame=4&groupid=$groupid"));

    
        $form = '<form name="group_properties" method="post" action="'.$sess->url("main.php?").'">
                 '.$sess->hidden_session().'
                 <input type="hidden" name="area" value="'.$area.'">
                 <input type="hidden" name="action" value="group_addmember">
                 <input type="hidden" name="frame" value="'.$frame.'">
				 <input type="hidden" name="groupid" value="'.$groupid.'">
                 <input type="hidden" name="idlang" value="'.$lang.'">';
                 
 
    
    
    $tpl3->set('s', 'FORM', $form);
    $tpl3->set('s', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    $tpl3->set('s', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl3->set('s', 'SUBMITTEXT', i18n("Save changes"));
    $tpl3->set('s', 'CANCELTEXT', i18n("Discard changes"));
    $tpl3->set('s', 'CANCELLINK', $sess->url("main.php?area=$area&frame=4&groupid=$groupid"));

  
    $tpl->set('d', 'CATNAME', i18n("Group member"));
    $tpl->set('d', 'CLASS', 'text_medium');
    $tpl->set('d', 'BGCOLOR',  $cfg["color"]["table_header"]);
    $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    $tpl->set('d', 'CATFIELD', i18n("Action"));
	$tpl->set('d', 'BRDB', 0);
	$tpl->set('d', 'BRDT', 1);
    $tpl->next();
    
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
    	
    	$delete = '<a href="'.$sess->url("main.php?area=$area&frame=4&groupid=$groupid&action=group_deletemember&idgroupuser=".$db->f("idgroupuser")).'">'.i18n("Delete").'</a>';
    	
		$myUser->loadUserByUserID($db->f("user_id"));
	    $tpl->set('d', 'CATNAME', $myUser->getField("realname"). " (".$myUser->getField("username").")");
	    $tpl->set('d', 'CLASS', 'text_medium');
	    $tpl->set('d', 'BGCOLOR', $color);
	    $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]); 
	    $tpl->set('d', 'CATFIELD', $delete);
	    $tpl->next();
	    
	    $aAddedUsers[] = $myUser->getField("username");
    }
 
     	$bgColor = !$bgColor;
    	
    	if ($bgColor) {
    		$color = $cfg["color"]["table_light"];
    	} else {
    		$color = $cfg["color"]["table_dark"];
    	}

	        	
	    $tpl2 = new Template;
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

	    $tpl2->set('s', 'NAME', 'newmember[]');
        $tpl2->set('s', 'CLASS', 'text_medium');
        
		$bAddedUser = false;
        if ( is_array($users) ) {

            $filter = htmlspecialchars($filter); 
            
			foreach ($users as $key => $value) {
				$bShowEntry = false;

				if ($filter == '') {
					$bShowEntry = true;						
				} elseif (strpos(strtolower($value["realname"]), strtolower($filter)) !== FALSE) {
					$bShowEntry = true;
				} elseif (strpos(strtolower($value["username"]), strtolower($filter)) !== FALSE) {
					$bShowEntry = true;
				}

				if (!in_array($value["username"], $aAddedUsers) && $bShowEntry)
				{
					$tpl2->set('d', 'VALUE', $key);
					$tpl2->set('d', 'CAPTION', $value["realname"] . " (".$value["username"].")");
	                $tpl2->next();
	                $bAddedUser = true;
				}
            }
            
        } 
        
        if ($bAddedUser == false)
        {
				$tpl2->set('d', 'VALUE', "nousers");
				$tpl2->set('d', 'CAPTION', i18n("No users found"));
                $tpl2->next();
                $tpl2->set('s', 'OPTIONS', 'size=10 multiple="multiple" disabled="disabled"');        	
        } else {
                if (count($users) > 20)
		        {
					$tpl2->set('s', 'OPTIONS', 'size=20 multiple="multiple"');        	
		        } else {
		        	$tpl2->set('s', 'OPTIONS', 'size=10 multiple="multiple"');
		        }	
        }
        $select = $tpl2->generate($cfg['path']['templates'] . $cfg['templates']['generic_select'],true);
        
        $sFilterInput = '<input type="text" class="text_medium" id="filter" style="width:220px;" maxlength="30" name="filter" value="'.$filter.'"/>';
        
		$tpl3->set('d', 'CATNAME', i18n("Add group member"));
    	$tpl3->set('d', 'CLASS', 'text_medium');
    	$tpl3->set('d', 'BGCOLOR',  $cfg["color"]["table_header"]);
    	$tpl3->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    	$tpl3->set('d', 'CATFIELD', "&nbsp;");
		$tpl3->set('d', 'BRDB', 0);
		$tpl3->set('d', 'BRDT', 1);
    	$tpl3->next();
    
 	    $tpl3->set('d', 'CATNAME', i18n("Filter users").':');
	    $tpl3->set('d', 'CLASS', 'text_medium');
	    $tpl3->set('d', 'BGCOLOR', $color);
	    $tpl3->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]); 
	    $tpl3->set('d', 'CATFIELD', $sFilterInput);
		$tpl3->set('d', 'BRDB', 1);
		$tpl3->set('d', 'BRDT', 0);
	    $tpl3->next();

 	    $tpl3->set('d', 'CATNAME', i18n("Choose user").':');
	    $tpl3->set('d', 'CLASS', 'text_medium');
	    $tpl3->set('d', 'BGCOLOR', $color);
	    $tpl3->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]); 
	    $tpl3->set('d', 'CATFIELD', $select);
		$tpl3->set('d', 'BRDB', 1);
		$tpl3->set('d', 'BRDT', 0);
	    $tpl3->next();

    # Generate template
		$tpl3gen = $tpl3->generate($cfg['path']['templates'] . $cfg['templates']['grouprights_memberselect'],true);
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['grouprights_memberlist']);
    echo $tpl3gen;
    
    
}
}
?>
