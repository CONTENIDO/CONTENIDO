<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Frontend user editor
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.1.7
 * @author     Björn Behrens (HerrB)
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2007-01-01, Björn Behrens (HerrB)
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *
 *   $Id: include.recipients_edit.php 665 2008-08-10 15:20:20Z HerrB $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "class.ui.php");
cInclude("classes", "class.htmlelements.php");
cInclude("classes", "class.newsletter.groups.php"); // loads also class.newsletter.recipients.php
cInclude("classes", "class.properties.php");

$oPage      	= new cPage;
$oRecipients	= new RecipientCollection;

if (is_array($cfg['plugins']['recipients'])) {
	foreach ($cfg['plugins']['recipients'] as $plugin) {
		plugin_include("recipients", $plugin."/".$plugin.".php");	
	}
}

// Note, that the object name has to be $recipient for plugins
if ($action == "recipients_create" && $perm->have_perm_area_action($area, $action)) {
	$recipient = $oRecipients->create("mail@domain.tld"," ".i18n("-- new recipient --"));
	$oPage->setReload();		
} elseif ($action == "recipients_delete" && $perm->have_perm_area_action($area, $action)) {
	$oRecipients->delete($idrecipient);
	$recipient = new Recipient;	
	$oPage->setReload();
} elseif ($action == "recipients_purge" && $perm->have_perm_area_action($area, "recipients_delete")) {
    $oClient = new cApiClient($client);
    $timeframe = $oClient->getProperty("newsletter", "purgetimeframe");
	if (!$timeframe) {
		$timeframe = 30;
	}
	$purgedrecipients = $oRecipients->purge($timeframe);
	/* backslashdollar: There is a problem translating \$ - it is either not recognized or translated correctly (using poEdit) */
	if ($purgedrecipients > 0) {
		$sNotis = $notification->messageBox("info", sprintf(str_replace("backslashdollar", "\$", i18n("%1backslashdollard recipients, which hasn't been confirmed since more than %2backslashdollard days has been removed.")),$purgedrecipients,$timeframe),0);
	} else {
	    $sNotis = $notification->messageBox("info", sprintf(str_replace("backslashdollar", "\$", i18n("There are no recipients, which hasn't been confirmed since more than %2backslashdollard days has been removed.")), 0, $timeframe),0);
	}
	
	$recipient = new Recipient;	
	$oPage->setReload();
} else {
	$recipient = new Recipient($idrecipient);
}

if ($recipient->virgin == false && $recipient->get("idclient") == $client && $recipient->get("idlang") == $lang) {
	if ($action == "recipients_save" && $perm->have_perm_area_action($area, $action)) {
		$oPage->setReload();
		$aMessages = array();

		$name			= stripslashes($name);
		$email			= stripslashes($email);
		$confirmed		= (int)$confirmed;
		$deactivated	= (int)$deactivated;
		$newstype		= (int)$newstype;
		
		$recipient->set("name", $name);

		if (!isValidMail($email))
		{
			$aMessages[] = i18n("Please specify a valid e-mail address");
		} else {
			$email	= strtolower($email); // e-mail always in lower case
			if ($recipient->get("email") != $email) {
					$oRecipients->resetQuery();
					$oRecipients->setWhere("email", $email);
					$oRecipients->setWhere("idclient", $client);
					$oRecipients->setWhere("idlang", $lang);
					$oRecipients->setWhere($recipient->primaryKey, $recipient->get($recipient->primaryKey), "!=");
					$oRecipients->query();
	    			
	    			if ($oRecipients->next()) {
	    				$aMessages[] = i18n("Could not set new e-mail adress: Other recipient with same e-mail address already exists");
	    			} else {
	    				$recipient->set("email", $email);
		    		}
			}
		}
					
		if ($recipient->get("confirmed") != $confirmed && $confirmed) {
			$recipient->set("confirmeddate", date("Y-m-d H:i:s"), false);
		} elseif (!$confirmed) {
			$recipient->set("confirmeddate", "0000-00-00 00:00:00", false);	
		}
		$recipient->set("confirmed",	$confirmed);
		$recipient->set("deactivated",	$deactivated);
		$recipient->set("news_type",	$newstype);
		
		// Check out if there are any plugins
		if (is_array($cfg['plugins']['recipients'])) {
			foreach ($cfg['plugins']['recipients'] as $plugin) {
				if (function_exists("recipients_".$plugin."_wantedVariables") && function_exists("recipients_".$plugin."_store")) {
					$wantVariables = call_user_func("recipients_".$plugin."_wantedVariables");
        			
					if (is_array($wantVariables)) {
						$varArray = array();
						
						foreach ($wantVariables as $value) {
							$varArray[$value] = stripslashes($GLOBALS[$value]);	
						}	
					}
					$store = call_user_func("recipients_".$plugin."_store", $varArray);
				}
			}
		}
    	
		$recipient->store();
		
		// Remove group associations
		if (isset($_REQUEST["ckbRemove"])) {
			$oGroupMembers = new RecipientGroupMemberCollection;
			
			foreach ($_REQUEST["ckbRemove"] as $iGroupMemberID) {
				if (is_numeric($iGroupMemberID)) {
					$oGroupMembers->delete($iGroupMemberID);
				}
			}
		}
	}
	
	if (count($aMessages) > 0) {
		$sNotis = $notification->returnNotification("warning", implode("<br>", $aMessages)) . "<br>";
	}
	
	$oForm = new UI_Table_Form("properties");
	$oForm->setVar("frame",	$frame);
	$oForm->setVar("area",	$area);
	$oForm->setVar("action", "recipients_save");
	$oForm->setVar("idrecipient", $recipient->get("idnewsrcp"));

	$oForm->addHeader(i18n("Edit recipient"));

	$oTxtName 			= new cHTMLTextbox("name", 	$recipient->get("name"), 40);
	$oTxtEMail 			= new cHTMLTextbox("email", $recipient->get("email"), 40);
	$oCkbConfirmed 		= new cHTMLCheckbox("confirmed", "1");
	$oCkbConfirmed->setChecked($recipient->get("confirmed"));
	$oCkbDeactivated 	= new cHTMLCheckbox("deactivated", "1");
	$oCkbDeactivated->setChecked($recipient->get("deactivated"));
	
	$oSelNewsType 		= new cHTMLSelectElement("newstype");
	$oOption 			= new cHTMLOptionElement(i18n("Text only"), "0");
	$oSelNewsType->addOptionElement(0, $oOption);
	$oOption 			= new cHTMLOptionElement(i18n("HTML and text"), "1");
	$oSelNewsType->addOptionElement(1, $oOption);
	$oSelNewsType->setDefault($recipient->get("news_type"));
	
	$oForm->add(i18n("Name"), 			$oTxtName->render());
	$oForm->add(i18n("E-Mail"), 		$oTxtEMail->render());
	$oForm->add(i18n("Confirmed"), 		$oCkbConfirmed->toHTML(false) . " (" . $recipient->get("confirmeddate") . ")");
	$oForm->add(i18n("Deactivated"), 	$oCkbDeactivated->toHTML(false));
	$oForm->add(i18n("Message type"),	$oSelNewsType->render());
	
	$aPluginOrder = trim_array(explode(",",getSystemProperty("plugin", "recipients-pluginorder")));
	
	// Check out if there are any plugins
	if (is_array($aPluginOrder)) {
		foreach ($aPluginOrder as $sPlugin) {
			if (function_exists("recipients_".$sPlugin."_getTitle") &&
			    function_exists("recipients_".$sPlugin."_display")) {
    				$aPluginTitle	= call_user_func("recipients_".$sPlugin."_getTitle");
    				$aPluginDisplay	= call_user_func("recipients_".$sPlugin."_display", $recipient);
    			
    				if (is_array($aPluginTitle) && is_array($aPluginDisplay)) {
    					foreach ($aPluginTitle as $sKey => $sValue) {
    						$oForm->add($sValue, $aPluginDisplay[$sKey]);	
    					}
    				} else {
    					if (is_array($aPluginTitle) || is_array($aPluginDisplay)) {
    						$oForm->add(i18n("WARNING"), sprintf(i18n("The plugin %s delivered an array for the displayed titles, but did not return an array for the contents."), $sPlugin));
    					} else {
    						$oForm->add($aPluginTitle, $aPluginDisplay);
					}
				}
			}
		}
	}
	
	$oGroupList = new UI_List;
	$oGroupList->setWidth("100%");
	$oGroupList->setBorder(1);

	$oAssocGroups = new RecipientGroupMemberCollection;
	$oAssocGroups->link("RecipientGroupCollection");
	$oAssocGroups->setWhere("recipientgroupmembercollection.idnewsrcp", $recipient->get("idnewsrcp"));
	$oAssocGroups->setOrder("recipientgroupcollection.groupname");
	$oAssocGroups->query();
	
	if ($oAssocGroups->count() == 0)
	{
		$oGroupList->setCell(0, 1, i18n("Recipient is not member of any group"));
	} else {
		// Headline
		$oGroupList->setCell(0, 1, "<strong>".i18n("Groupname")."</strong>");
		$oImgDel = new cHTMLImage("images/delete.gif");
		$oGroupList->setCell(0, 2, $oImgDel->render());
		$oGroupList->setCellAlignment(0, 2, "right");
		
		// Data
		while ($oAssocGroup = $oAssocGroups->next())
		{
			$oGroup = $oAssocGroups->fetchObject("RecipientGroupCollection");
			
			$oCkbRemove = new cHTMLCheckbox("ckbRemove[]", $oAssocGroup->get("idnewsgroupmember"));
			echo ($oGroup->get("idnewsgroupmember"));
			$oGroupList->setCell($oAssocGroup->get("idnewsgroupmember"), 1, $oGroup->get("groupname"));
			$oGroupList->setCell($oAssocGroup->get("idnewsgroupmember"), 2, $oCkbRemove->toHTML(false));
			$oGroupList->setCellAlignment($oAssocGroup->get("idnewsgroupmember"), 2, "right");
		}
	}
			
	$oForm->add(i18n("Associated Groups"), $oGroupList->render());
	
	$oForm->add(i18n("Author"), $classuser->getUserName($recipient->get("author")) . " (". $recipient->get("created").")" ); 
	$oForm->add(i18n("Last modified by"), $classuser->getUserName($recipient->get("modifiedby")). " (". $recipient->get("lastmodified").")" );
	
	$oPage->setContent($sNotis . $oForm->render(true));
} else {
	$oPage->setContent($sNotis . "");	
}

$oPage->render();

?>
