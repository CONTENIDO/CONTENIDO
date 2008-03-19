<?php
/*****************************************
* File      :   $RCSfile: include.frontend.user_edit.php,v $
* Project   :   Contenido
* Descr     :   Frontend user editor
* Modified  :   $Date: 2007/05/28 18:26:04 $
*
* © four for business AG, www.4fb.de
*
* $Id: include.frontend.user_edit.php,v 1.19 2007/05/28 18:26:04 bjoern.behrens Exp $
******************************************/
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "class.ui.php");
cInclude("classes", "class.htmlelements.php");
cInclude("classes", "class.frontend.users.php");
cInclude("classes", "class.frontend.groups.php");
cInclude("classes", "class.properties.php");

$page = new cPage;

if ($idfrontenduser) {
    $sReloadScript = "<script type=\"text/javascript\">
                         var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
                         if (left_bottom) {
                             var href = left_bottom.location.href;
                             href = href.replace(/&frontenduser.*/, '');
                             left_bottom.location.href = href+'&frontenduser='+".$idfrontenduser.";

                         }
                     </script>";
} else {
    $sReloadScript = "";
}
                 

$feusers = new FrontendUserCollection;

if (is_array($cfg['plugins']['frontendusers']))
{
	foreach ($cfg['plugins']['frontendusers'] as $plugin)
	{
		plugin_include("frontendusers", $plugin."/".$plugin.".php");	
	}
}

$feuser = new FrontendUser;
$feuser->loadByPrimaryKey($idfrontenduser);

$oFEGroupMemberCollection = new FrontendGroupMemberCollection;
$oFEGroupMemberCollection->setWhere('idfrontenduser', $idfrontenduser);
$oFEGroupMemberCollection->addResultField('idfrontendgroup');
$oFEGroupMemberCollection->query();

# Fetch all groups the user belongs to (no goup, one group, more than one group).
# The array $aFEGroup can be used in frontenduser plugins to display selfdefined user properties group dependent.
$aFEGroup = array();
while($oFEGroup = $oFEGroupMemberCollection->next())
{
	$aFEGroup[] = $oFEGroup->get("idfrontendgroup");
}

if ($action == "frontend_create" && $perm->have_perm_area_action("frontend", "frontend_create"))
{
		$feuser = $feusers->create(" ".i18n("-- new user --"));
		$idfrontenduser = $feuser->get("idfrontenduser");
		$page->addScript('reload', $sReloadScript);
		
}

if ($action == "frontend_delete" && $perm->have_perm_area_action("frontend", "frontend_delete"))
{
	$feusers->delete($idfrontenduser);

	$iterator = $_cecRegistry->getIterator("Contenido.Permissions.FrontendUser.AfterDeletion");
	
	while ($chainEntry = $iterator->next())
	{
		$chainEntry->execute($idfrontenduser);
	}

	$idfrontenduser = 0;
	$feuser = new FrontendUser;	
	$page->addScript('reload', $sReloadScript);
}


if ($feuser->virgin == false && $feuser->get("idclient") == $client)
{
	if ($action == "frontend_save_user")
	{
		$page->addScript('reload', $sReloadScript);
		$messages = array();
		
		if ($feuser->get("username") != stripslashes($username))
		{
    		$feusers->select("username = '$username' and idclient='$client'");
    		if ($feusers->next())
    		{
    			$messages[] = i18n("Could not set new username: Username already exists");	
    		} else {
    			$feuser->set("username", stripslashes($username));
    		}
		}
		
		if ($newpd != $newpd2)
		{
			$messages[] = i18n("Could not set new password: Passwords don't match");
		} else {
			if ($newpd != "")
			{
				$feuser->set("password", $newpd);
			}
		}
		
		$feuser->set("active", $active);

    	/* Check out if there are any plugins */
    	if (is_array($cfg['plugins']['frontendusers']))
    	{
    		foreach ($cfg['plugins']['frontendusers'] as $plugin)
    		{
    			if (function_exists("frontendusers_".$plugin."_wantedVariables") &&
    				function_exists("frontendusers_".$plugin."_store"))
    			{
    				# check if user belongs to a specific group 
					# if true store values defined in frontenduser plugin
    				if (function_exists("frontendusers_".$plugin."_checkUserGroup"))
    				{
    					$bCheckUserGroup = call_user_func("frontendusers_".$plugin."_checkUserGroup");
    				}else
    				{
    					$bCheckUserGroup = true;
    				}
    				
    				if ($bCheckUserGroup)
    				{
            			$wantVariables = call_user_func("frontendusers_".$plugin."_wantedVariables");
            			
            			if (is_array($wantVariables))
            			{
            				$varArray = array();
            				
            				foreach ($wantVariables as $value)
            				{
            					$varArray[$value] = stripslashes($GLOBALS[$value]);	
            				}	
            			}
            			$store = call_user_func("frontendusers_".$plugin."_store", $varArray);
    				}
    			}
    		}
    	}
    	
    	$feuser->store();		
	}
	
	if (count($messages) > 0)
	{
		$notis = $notification->returnNotification("warning", implode("<br>", $messages)) . "<br>";
	}
	
	
	$form = new UI_Table_Form("properties");
	$form->setVar("frame", $frame);
    $form->setVar("area", $area);
    $form->setVar("action", "frontend_save_user");
    $form->setVar("idfrontenduser", $idfrontenduser);

	$form->addHeader(i18n("Edit user"));
	
	$username = new cHTMLTextbox("username", $feuser->get("username"),40);
	$newpw    = new cHTMLPasswordBox("newpd","",40);
	$newpw2   = new cHTMLPasswordBox("newpd2","",40);
	$active   = new cHTMLCheckbox("active","1");
	$active->setChecked($feuser->get("active"));
	
	$form->add(i18n("User name"), $username->render());
	$form->add(i18n("New password"), $newpw->render());
	$form->add(i18n("New password (again)"), $newpw2->render());
	$form->add(i18n("Active"), $active->toHTML(false));
	
	$pluginOrder = trim_array(explode(",",getSystemProperty("plugin", "frontendusers-pluginorder")));
	
	/* Check out if there are any plugins */
	if (is_array($pluginOrder))
	{
		foreach ($pluginOrder as $plugin)
		{
			if (function_exists("frontendusers_".$plugin."_getTitle") &&
				function_exists("frontendusers_".$plugin."_display"))
			{
				# check if user belongs to a specific group 
				# if true display frontenduser plugin
				if (function_exists("frontendusers_".$plugin."_checkUserGroup"))
				{
					$bCheckUserGroup = call_user_func("frontendusers_".$plugin."_checkUserGroup");
				}else
				{
					$bCheckUserGroup = true;
				}
				
				if ($bCheckUserGroup)
				{
        			$plugTitle = call_user_func("frontendusers_".$plugin."_getTitle");
        			$display = call_user_func("frontendusers_".$plugin."_display", $feuser);
        			
        			if (is_array($plugTitle) && is_array($display))
        			{
        				foreach ($plugTitle as $key => $value)
        				{
        					$form->add($value, $display[$key]);	
        				}
        			} else {
        				if (is_array($plugTitle) || is_array($display))
        				{
        					$form->add(i18n("WARNING"), sprintf(i18n("The plugin %s delivered an array for the displayed titles, but did not return an array for the contents."), $plugin));
        				} else {
        					$form->add($plugTitle, $display);
    					}
    				}
				}
			}
		}

		$arrGroups = $feuser->getGroupsForUser();
		
		if (count($arrGroups) > 0) {
			$sql = "SELECT groupname FROM ".$cfg['tab']['frontendgroups']." WHERE idfrontendgroup IN (".implode(',', $arrGroups).")"; 
				
			$db->query($sql);
			$arrGroups = array();
			
			while ($db->next_record()) {				
				$arrGroups[] = $db->f('groupname');
			}
			
			asort($arrGroups);
			
			$sTemp = implode('<br/>', $arrGroups);
		} else {
			$sTemp = i18n("none");
		}
		
		$form->add(i18n("Group membership"), $sTemp ); 
		
		$form->add(i18n("Author"), $classuser->getUserName($feuser->get("author")) . " (". $feuser->get("created").")" ); 
		$form->add(i18n("Last modified by"), $classuser->getUserName($feuser->get("modifiedby")). " (". $feuser->get("modified").")" );
		
	}
	$page->setContent(	$notis .
						$form->render(true));
	$page->addScript('reload', $sReloadScript);
} else {
	$page->setContent("");	
}

$page->render();
?>
