<?php
/*****************************************
* File      :   $RCSfile: include.frontend.group_edit.php,v $
* Project   :   Contenido
* Descr     :   Frontend group editor
* Modified  :   $Date: 2004/01/14 17:30:48 $
*
* © four for business AG, www.4fb.de
*
* $Id: include.frontend.group_edit.php,v 1.2 2004/01/14 17:30:48 timo.hummel Exp $
******************************************/
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "class.ui.php");
cInclude("classes", "class.htmlelements.php");
cInclude("classes", "class.frontend.users.php");
cInclude("classes", "class.frontend.groups.php");

$page = new cPage;

if ($idfrontendgroup) {
    $sReloadScript = "<script type=\"text/javascript\">
                         var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
                         if (left_bottom) {
                             var href = left_bottom.location.href;
                             left_bottom.location.href = href+'&idfrontendgroup='+".$idfrontendgroup.";

                         }
                     </script>";
} else {
    $sReloadScript = '';
}

$fegroups 		= new FrontendGroupCollection;

if (is_array($cfg['plugins']['frontendgroups']))
{
	foreach ($cfg['plugins']['frontendgroups'] as $plugin)
	{
		plugin_include("frontendgroups", $plugin."/".$plugin.".php");
	}
}

$fegroup 		= new FrontendGroup;
$groupmembers	= new FrontendGroupMemberCollection;
$fegroup->loadByPrimaryKey($idfrontendgroup);

if ($action == "frontendgroup_create" && $perm->have_perm_area_action($area, $action))
{
   $fegroup = $fegroups->create(" ".i18n("-- new group --"));
   $idfrontendgroup = $fegroup->get("idfrontendgroup");   
} else if ($action == "frontendgroups_user_delete" && $perm->have_perm_area_action($area, $action))
{
   $groupmembers->remove($idfrontendgroup, $idfrontenduser);
} else if ($action == "frontendgroup_delete" && $perm->have_perm_area_action($area, $action))
{
   $fegroups->delete($idfrontendgroup);
   $idfrontendgroup= 0;
   $fegroup = new FrontendGroup;   
}

if ($fegroup->virgin == false && $fegroup->get("idclient") == $client)
{
	if ($action == "frontendgroup_save_group" && $perm->have_perm_area_action($area, $action))
	{
		$messages = array();
		
		if ($fegroup->get("groupname") != stripslashes($groupname))
		{
    		$fegroups->select("groupname = '$groupname' and idclient='$client'");
    		if ($fegroups->next())
    		{
    			$messages[] = i18n("Could not set new group name: Group already exists");	
    		} else {
    			$fegroup->set("groupname", stripslashes($groupname));
    		}
		}
		
		if (count($adduser) > 0)
		{
			foreach ($adduser as $add)
			{
				$groupmembers->create($idfrontendgroup, $add);
			}	
		}
    	
        //Reset all default groups
        if ($defaultgroup == 1) {
            $sSql = 'UPDATE '.$cfg["tab"]["frontendgroups"].' SET defaultgroup = 0 WHERE idclient='.$client.';';
            $db->query($sSql);
        }
    	$fegroup->set("defaultgroup", $defaultgroup);
    	
		/* Check out if there are any plugins */
		if (is_array($cfg['plugins']['frontendgroups']))
		{
			foreach ($cfg['plugins']['frontendgroups'] as $plugin)
			{
				if (function_exists("frontendgroups_".$plugin."_wantedVariables") &&
					function_exists("frontendgroups_".$plugin."_store"))
				{
					$wantVariables = call_user_func("frontendgroups_".$plugin."_wantedVariables");

					if (is_array($wantVariables))
					{
						$varArray = array();

						foreach ($wantVariables as $value)
						{
							$varArray[$value] = stripslashes($GLOBALS[$value]);
						}
					}
					$store = call_user_func("frontendgroups_".$plugin."_store", $varArray);
				}
			}
		}
    	
    	$fegroup->store();		
	}
	
	if (count($messages) > 0)
	{
		$notis = $notification->returnNotification("warning", implode("<br>", $messages)) . "<br>";
	}
	
	
	$form = new UI_Table_Form("properties");
	$form->setVar("frame", $frame);
	$form->setVar("area", $area);
	$form->setVar("action", "frontendgroup_save_group");
	$form->setVar("idfrontendgroup", $idfrontendgroup);

	$form->addHeader(i18n("Edit group"));
	
	$feusers = new FrontendUserCollection;
	$feusers->select("idclient='$client'");
	
	$addedusers = $groupmembers->getUsersInGroup($idfrontendgroup,false, true);
	$addeduserobjects = $groupmembers->getUsersInGroup($idfrontendgroup,true, true);

	$addeduserlist = new UI_List;
	$addeduserlist->setWidth("100%");
	$addeduserlist->setBorder(0);
    
	$del = new Link;
	$del->setCLink("frontendgroups", 4, "frontendgroups_user_delete");
	$del->setContent('<img src="images/delete.gif">');
	$del->setCustom("idfrontendgroup", $idfrontendgroup);
	
	$cells = array();
	foreach ($addeduserobjects as $addeduserobject)
	{
		$cells[$addeduserobject->get("idfrontenduser")] = $addeduserobject->get("username");
	}
	
	asort($cells);
	
	foreach ($cells as $idfrontenduser => $name)
	{
		$del->setCustom("idfrontenduser", $idfrontenduser);

		$addeduserlist->setCell($idfrontenduser,1,'<img align="left" src="images/users.gif">'.$name);
		$addeduserlist->setCell($idfrontenduser,2,$del->render());
		$addeduserlist->setCellAlignment($idfrontenduser,2, "right");
	}
    
	
	if (count($addeduserobjects) == 0)
	{
		$addeduserlist->setCell(0,1,i18n("No users are added to this group yet"));	
	}
		
    $filter = htmlspecialchars($filter); 

	$oInputFilter = new cHTMLTextbox('filter', $filter, '30', '50', 'filter');
	$oInputFilter->setStyle('width:400px;');
	
	$items = array();
	while ($feuser = $feusers->next())
	{
		$idfrontenduser = $feuser->get("idfrontenduser");
		
		$bShowEntry = false;
		$sUsername = $feuser->get("username");

		if ($filter == '') {
			$bShowEntry = true;						
		} elseif (strpos(strtolower($sUsername), strtolower($filter)) !== FALSE) {
			$bShowEntry = true;
		}
	
		if (!in_array($idfrontenduser,$addedusers) && $bShowEntry)
		{
			$items[$idfrontenduser] = $sUsername;
		}	
	}
	
	asort($items);
	
	$select = new cHTMLSelectElement("adduser[]");
	$select->setSize(20);
	$select->setStyle("width: 400px;");
	$select->setMultiSelect();
	$select->autoFill($items);
	
	$groupname = new cHTMLTextbox("groupname", $fegroup->get("groupname"),40);
	
	$defaultgroup = new cHTMLCheckbox("defaultgroup", "1");
	$defaultgroup->setChecked($fegroup->get("defaultgroup"));
	
	$form->add(i18n("Group name"), $groupname->render());
	$form->add(i18n("Default group"), $defaultgroup->toHTML(false));
	$form->add(i18n("Users in Group"), $addeduserlist->render());
	$form->add(i18n("Filter users"), $oInputFilter->render());
	$form->add(i18n("Add users"), $select->render()."<br>".i18n("Note: Hold ctrl to select multiple items."));

	$pluginOrder = trim_array(explode(",",getSystemProperty("plugin", "frontendgroups-pluginorder")));

	/* Check out if there are any plugins */
	if (is_array($pluginOrder))
	{
		foreach ($pluginOrder as $plugin)
		{
			if (function_exists("frontendgroups_".$plugin."_getTitle") &&
				function_exists("frontendgroups_".$plugin."_display"))
			{

				$plugTitle = call_user_func("frontendgroups_".$plugin."_getTitle");
				$display = call_user_func("frontendgroups_".$plugin."_display", $fegroup);

				if (is_array($plugTitle) && is_array($display))
				{
					foreach ($plugTitle as $key => $value)
					{
						$form->add($value, $display[$key]);
					}
				} else {
					if (is_array($plugTitle) || is_array($display))
					{
						$form->add("WARNING", "The plugin $plugin delivered an array for the displayed titles, but did not return an array for the contents.");
					} else {
						$form->add($plugTitle, $display);
					}
				}
			}
		}
	}

	$page->setContent($notis . $form->render(true));
} else {
	$page->setContent("");	
}

$page->addScript('reload', $sReloadScript);
$page->render();
?>
