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
 *   $Id: include.newsletter_edit.php 665 2008-08-10 15:20:20Z HerrB $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.newsletter.php");
cInclude("classes", "class.newsletter.jobs.php");
cInclude("classes", "class.newsletter.groups.php");
cInclude("classes", "contenido/class.clientslang.php");
cInclude("classes", "contenido/class.articlelanguage.php");
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "class.ui.php");
cInclude("classes", "class.htmlelements.php");
cInclude("classes", "contenido/class.user.php");

// Initialization
$oPage			= new cPage;
$oRcpGroups 	= new RecipientGroupCollection;	
$oClientLang 	= new cApiClientLanguage(false, $client, $lang);
$oNewsletters 	= new NewsletterCollection;

// Include plugins
if (is_array($cfg['plugins']['newsletters'])) {
	foreach ($cfg['plugins']['newsletters'] as $plugin) {
		plugin_include("newsletters", $plugin."/".$plugin.".php");	
	}
}

if ($action == "news_create" && $perm->have_perm_area_action($area, "news_create"))
{
	// Create new newsletter
	$oNewsletter 	= $oNewsletters->create(i18n("-- new newsletter --"));
	$idnewsletter	= $oNewsletter->get("idnews");
	$oPage->setSubnav("idnewsletter=$idnewsletter", "news");
	$oPage->setReload();
	
	// Populating default values
	$oNewsletter->set("newsfrom", 		$oClientLang->getProperty("newsletter", "newsfrom"));
	$oNewsletter->set("newsfromname",	$oClientLang->getProperty("newsletter", "newsfromname"));
	
	$sValue = $oClientLang->getProperty("newsletter", "sendto");
	if ($sValue == "") {
		$sValue = "all";
	}
	$oNewsletter->set("send_to", $sValue);
	
	$oNewsletter->set("send_ids", $oClientLang->getProperty("newsletter", "sendids"));

	$iValue = $oClientLang->getProperty("newsletter", "use_cronjob");
	if (!is_numeric($iValue)) {
		$iValue = 0;
	}
	$oNewsletter->set("use_cronjob", $iValue);
	
	$iValue = $oClientLang->getProperty("newsletter", "dispatch");
	if (!is_numeric($iValue)) {
		$iValue = 0;
	}
	$oNewsletter->set("dispatch", $iValue);
	
	$iValue = $oClientLang->getProperty("newsletter", "dispatchcount");
	if (!is_numeric($iValue)) {
		$iValue = 50;
	}	
	$oNewsletter->set("dispatch_count", $iValue);
	
	$iValue = $oClientLang->getProperty("newsletter", "dispatchdelay");
	if (!is_numeric($iValue)) {
		$iValue = 5;
	}	
	$oNewsletter->set("dispatch_delay", $iValue);
	$oNewsletter->store();
} else if ($action == "news_duplicate" && $perm->have_perm_area_action($area, "news_create")) {
	// Copy newsletter
	$oNewsletter	= $oNewsletters->duplicate($idnewsletter);
	
	// Update subnav with new ID
	$oPage->setSubnav("idnewsletter=".$oNewsletter->get("idnews"), "news");
	$oPage->setReload();		
} else if ($action == "news_delete" && $perm->have_perm_area_action($area, "news_delete")) {
	// Delete newsletter
	// If it is an html newsletter, delete html message article, also
	$oNewsletter = new Newsletter($idnewsletter);
		
	if ($oNewsletter->get("type") == "html" && $oNewsletter->get("idart") > 0) {
		conDeleteArt($oNewsletter->get("idart"));
	}
	
	// Delete newsletter
	$oNewsletters->delete($idnewsletter);
	$oNewsletter = new Newsletter; // Generate empty newsletter object
	
	// Setting blank subnav - "blank" doesn't mean anything special, it just can't be empty
	// and must not contain "idnewsletter" as this is checked in the _subnav file.
	$oPage->setSubnav("blank", "news");
	$oPage->setReload();
} else if ($action == "news_add_job" && $perm->have_perm_area_action($area, "news_add_job")) {
	// Create job
	$oJobs = new cNewsletterJobCollection;
	$oJob = $oJobs->create($idnewsletter, $oClientLang->getProperty("newsletter", "idcatart"));	
	unset ($oJobs);
	
	if ($oJob) {
		$notis = $notification->returnNotification("info", i18n("Newsletter dispatch job has been added for this newsletter")) . "<br>";
	} else {
		$notis = $notification->returnNotification("error", i18n("Newsletter dispatch job has been not been added! Please check newsletter details")) . "<br>";
	} 
	
	$oNewsletter = new Newsletter($idnewsletter);
} else if ($action == "news_send_test" &&
		   ($perm->have_perm_area_action($area, "news_create") || 
		    $perm->have_perm_area_action($area, "news_save") ||
		    $perm->have_perm_area_action($area, "news_add_job"))) {
	// Send test newsletter		    	
	cInclude("classes", "contenido/class.lang.php");
	$oUser 	= new cApiUser($auth->auth["uid"]);
	
	// Subnav gets not updated otherwise (no multilink from newsletter_menu)
	$oPage->setSubnav("idnewsletter=" . $idnewsletter, "news");
	
	// Get test destination	
	if ($perm->have_perm_area_action($area, "news_send_test")) {
		$iTestIDNewsGroup = (int)$oUser->getProperty("newsletter", "test_idnewsgrp_lang" . $lang);
	} else {
	 	$iTestIDNewsGroup = 0; // If user doesn't have the news_send_test right, just send to himself
	}
	
	// Get encoding
	$oLang 		= new cApiLanguage($lang);
	$sEncoding	= $oLang->get("encoding");
	unset ($oLang);
	
	// Send test newsletter
	$oNewsletter = new Newsletter($idnewsletter);
	$aRecipients = array();
	if ($iTestIDNewsGroup == 0)
	{
		// Send test newsletter to current user email address
		$sName				= $oUser->get("realname");
		$sEMail				= $oUser->get("email");

		$bSend = $oNewsletter->sendEMail($oClientLang->getProperty("newsletter", "idcatart"), $sEMail, $sName, $sEncoding);
		if ($bSend) {
			$aRecipients[] = $sName . " (" . $sEMail . ")";
		} else {
			$aRecipients[] = i18n("None");
		} 
	} else {
		$bSend = $oNewsletter->sendDirect($oClientLang->getProperty("newsletter", "idcatart"), 0, $iTestIDNewsGroup, $aRecipients, $sEncoding);
	}
	unset($oUser);
	
	if ($bSend) {
		$notis = $notification->returnNotification("info", i18n("Test newsletter has been sent to:") . "<br />" . implode("<br />", $aRecipients) . "<br />");
	} else {
		$notis = $notification->returnNotification("warning", i18n("Test newsletter has not been sent (partly or completely):") . "<br />" .  
															  i18n("Successful:") . "<br />" . implode("<br />", $aRecipients) . "<br />" .
															  i18n("Error messages:") . "<br />" . $oNewsletter->_sError);
	}
} else {
	// No action, just get selected newsletter
	$oNewsletter = new Newsletter($idnewsletter);
}

if ($oNewsletter->virgin == false && $oNewsletter->get("idclient") == $client && $oNewsletter->get("idlang") == $lang)
{
	// Check and set values
	if ($_REQUEST["optSendTo"] == "") {
		$_REQUEST["optSendTo"] = $oNewsletter->get("send_to"); 
	}

	if (!is_numeric($_REQUEST["ckbWelcome"])) {
		$_REQUEST["ckbWelcome"] = 0;
	}

	if (!is_numeric($_REQUEST["txtDispatchCount"]) || $_REQUEST["txtDispatchCount"] <= 0) {
		$_REQUEST["txtDispatchCount"] = $oNewsletter->get("dispatch_count");		
	}
	
	// Note, that for DispatchDelay 0 is possible (= send chunks manually)
	if (!is_numeric($_REQUEST["txtDispatchDelay"]) || $_REQUEST["txtDispatchDelay"] < 0) {
		$_REQUEST["txtDispatchDelay"] = $oNewsletter->get("dispatch_delay");
	}
	
	// Only set template id to 0 if it has been specified (as something not useful).
	// This prevents deleting of the template id, if type setting is changed to "text"
	if (isset($_REQUEST["selTemplate"]) && !is_numeric($_REQUEST["selTemplate"])) {
		$_REQUEST["selTemplate"] = 0;
	}
	
	if ($action == "news_save" && $perm->have_perm_area_action($area, $action))
	{
		// Save changes
		$aMessages = array();

		// Changing e.g. \' back to ' (magic_quotes)
		$sName		= stripslashes($_REQUEST["txtName"]);
		$sFromEMail	= stripslashes($_REQUEST["txtFromEMail"]);
		$sFromName	= stripslashes($_REQUEST["txtFromName"]);
		$sSubject	= stripslashes($_REQUEST["txtSubject"]);
		
		if ($oNewsletter->get("name") != $sName || 
			$oNewsletter->get("welcome") != $_REQUEST["ckbWelcome"] || 
			!isValidMail($oNewsletter->get("newsfrom")) && isValidMail($sFromEMail))
		{
			// Only reload, if something visible has changed
			$oPage->setReload();
		}
			
		if ($oNewsletter->get("name") != $sName)
		{
			// Check, if item with same name exists
			$oNewsletters->setWhere("name", $sName);
			$oNewsletters->setWhere("idclient", $client);
			$oNewsletters->setWhere("idlang", $lang);
			$oNewsletters->setWhere($oNewsletter->primaryKey, $oNewsletter->get($oNewsletter->primaryKey), "!=");
			$oNewsletters->query();
				
	 		if ($oNewsletters->next())
	 		{
	 			$aMessages[] = i18n("Could not set new newsletter name: name already exists");	
	 		} else {
	 			$oNewsletter->set("name", $sName);
	 			if ($oNewsletter->get("idart") > 0)
	 			{
					// Update also HTML newsletter article title, if newsletter name has been changed
					$oArticles = new cApiArticleLanguageCollection;
					$oArticles->setWhere("idlang", $lang);
					$oArticles->setWhere("idart", $oNewsletter->get("idart"));
					$oArticles->query();
	 				
	 				if ($oArticle = $oArticles->next())
	 				{
	 					$oArticle->set("title", sprintf(i18n("Newsletter: %s"), $oNewsletter->get("name")));
	 					$oArticle->store();
	 				}
	 				unset ($oArticle);
	 				unset ($oArticles);
	 			}
		 	}
		}
		if ($oClientLang->getProperty("newsletter", "html_newsletter") == "true")
		{
			$oNewsletter->set("type", 	$selType);
		} else {
			$oNewsletter->set("type", 	"text");
		}
		$oNewsletter->set("newsfrom",	$sFromEMail);
		$oNewsletter->set("newsfromname", $sFromName);
		$oNewsletter->set("subject",	$sSubject);
	 	
	 	// Options
	 	$oNewsletter->set("welcome",  $_REQUEST["ckbWelcome"]);
		
		// Check out if there are any plugins
		if (is_array($cfg['plugins']['newsletters']))
		{
			foreach ($cfg['plugins']['newsletters'] as $plugin)
			{
				if (function_exists("newsletters_".$plugin."_wantedVariables") && function_exists("newsletters_".$plugin."_store"))
				{
					$wantVariables = call_user_func("newsletters_".$plugin."_wantedVariables");
		  			
					if (is_array($wantVariables))
					{
						$varArray = array();
		  					
						foreach ($wantVariables as $value) {
							$varArray[$value] = stripslashes($GLOBALS[$value]);	
						}	
					}
					$store = call_user_func("newsletters_".$plugin."_store", $varArray);
				}
			}
		}
		
		// If "selected groups" have been selected and no group specified, set
		// selection to "all" 
		if ($_REQUEST["optSendTo"] == "selection" && !is_array($_REQUEST["selGroup"])) {
			$aMessages[] = i18n("'Send to recipients in selected groups' has been selected, but no group has been specified. Selection has been set to 'Send to all recipients'");
			$_REQUEST["optSendTo"] = "all";
		}
		$oNewsletter->set("send_to", $_REQUEST["optSendTo"]);
		$oNewsletter->set("send_ids", serialize($_REQUEST["selGroup"]));

		if (getEffectiveSetting("newsletter", "option-cronjob-available", "false") == "true")
		{
			// Only store changes, if cronjob option is available
			if (isset($_REQUEST["ckbCronJob"])) {
				$oNewsletter->set("use_cronjob", 1);
			} else {
				$oNewsletter->set("use_cronjob", 0);
			}
		}
		
		if (isset($_REQUEST["ckbDispatch"])) {
			$oNewsletter->set("dispatch", 1);
		} else {
			$oNewsletter->set("dispatch", 0);
		}
		
		$oNewsletter->set("dispatch_count", $_REQUEST["txtDispatchCount"]);		
		$oNewsletter->set("dispatch_delay", $_REQUEST["txtDispatchDelay"]);
		
		$oNewsletter->store(); // Note, that the properties are stored, anyway
		
		// Storing from (e-mail), from (name) and options as default
		if ($_REQUEST["ckbSetDefault"])
		{
			$oClientLang->setProperty("newsletter", "newsfrom", $sFromEMail);
			$oClientLang->setProperty("newsletter", "newsfromname", $sFromName);
			$oClientLang->setProperty("newsletter", "sendto", $_REQUEST["optSendTo"]);
			$oClientLang->setProperty("newsletter", "sendgroups", serialize($_REQUEST["selGroup"]));
			if (isset($_REQUEST["ckbCronJob"])) {
				$oClientLang->setProperty("newsletter", "use_cronjob", "1");
			} else {
				$oClientLang->setProperty("newsletter", "use_cronjob", "0");
			}
			if (isset($_REQUEST["ckbDispatch"])) {
				$oClientLang->setProperty("newsletter", "dispatch", "1");
			} else {
				$oClientLang->setProperty("newsletter", "dispatch", "0");
			}
			$oClientLang->setProperty("newsletter", "dispatchcount", $_REQUEST["txtDispatchCount"]);
			$oClientLang->setProperty("newsletter", "dispatchdelay", $_REQUEST["txtDispatchDelay"]);
		}
	
		if (count($aMessages) > 0) {
			$notis .= $notification->returnNotification("warning", implode("<br>", $aMessages)) . "<br>";
		}
	} else {
		$_REQUEST["selGroup"] = unserialize ($oNewsletter->get("send_ids"));
		if (!is_array($_REQUEST["selGroup"]))
		{
			$_REQUEST["selGroup"] = unserialize($oClientLang->getProperty("newsletter", "sendgroups"));
			if (!is_array($_REQUEST["selGroup"])) {
				$_REQUEST["selGroup"] = array();
			}
		}

		$_REQUEST["ckbDispatch"] = false; 		
		if ($oNewsletter->get("dispatch") == 1) {
			$_REQUEST["ckbDispatch"] = true;
		} else if ($oNewsletter->get("dispatch") == "" && $oClientLang->getProperty("newsletter", "dispatch") == "true") {
			$_REQUEST["ckbDispatch"] = true; 
		}		
	}
	
	$oForm = new UI_Table_Form("properties");
	$oForm->setVar("frame", $frame);
	$oForm->setVar("area", $area);
	$oForm->setVar("action", "news_save");
	$oForm->setVar("idnewsletter", $oNewsletter->get("idnews"));

	$oForm->addHeader(i18n("Edit newsletter"));

	$oTxtName = new cHTMLTextbox("txtName", $oNewsletter->get("name"),40);
	$oForm->add(i18n("Name"), $oTxtName->render());
	
	$oSelType = new cHTMLSelectElement("selType");
	$aItems   = array();
	$aItems[] = array("text", i18n("Text only"));
	if ($oClientLang->getProperty("newsletter", "html_newsletter") == "true") {
		$aItems[] = array("html", i18n("HTML and text"));		
	} else {
		$oNewsletter->set("type", "text"); // just in case the global setting was switched off
		// TODO: Should this setting be stored?
	}
	$oSelType->autoFill($aItems);
	$oSelType->setDefault($oNewsletter->get("type"));
	
	$oForm->add(i18n("Type"), $oSelType->render());
		
	$oTxtFromEMail 	= new cHTMLTextbox("txtFromEMail",	$oNewsletter->get("newsfrom"), 40);
	$oTxtFromName 	= new cHTMLTextbox("txtFromName",	$oNewsletter->get("newsfromname"), 40);
	$oTxtSubject 	= new cHTMLTextarea("txtSubject",	$oNewsletter->get("subject"), 80, 2);

	$oForm->add(i18n("From (E-Mail)"),	$oTxtFromEMail->render());
	$oForm->add(i18n("From (Name)"),	$oTxtFromName->render()."&nbsp;".i18n("optional"));
	$oForm->add(i18n("Subject"),		$oTxtSubject->render());
		
	// Send options
	$oSendToAll		= new cHTMLRadiobutton("optSendTo", "all");
	$oSendToAll->setEvent("Click", "checkSelection(this.value)");
	$oSendToDefault	= new cHTMLRadiobutton("optSendTo", "default");
	$oSendToDefault->setEvent("Click", "checkSelection(this.value)");
	$oSendToGroups	= new cHTMLRadiobutton("optSendTo", "selection");
	$oSendToGroups->setEvent("Click", "checkSelection(this.value)");

	$oRcpGroups->setWhere("idclient", $client);
	$oRcpGroups->setWhere("idlang", $lang);
	$oRcpGroups->setOrder("defaultgroup DESC, groupname ASC");
	$oRcpGroups->query();

	$aItems = array();
	while ($oRcpGroup = $oRcpGroups->next())
	{
		$sGroupName = $oRcpGroup->get("groupname");
		if ($oRcpGroup->get("defaultgroup")) {
			$sGroupName = $sGroupName . "*";
		}
		$aItems[]	= array($oRcpGroup->get("idnewsgroup"), $sGroupName);
	}

	$oSelGroup = new cHTMLSelectElement("selGroup[]","","groupselect");
	$oSelGroup->setSize(10);
	$oSelGroup->setStyle("width: 350px; margin-top: 5px; margin-bottom: 5px; margin-left: 25px;");
	$oSelGroup->setMultiSelect();
	$oSelGroup->setAlt(i18n("Note: Hold <Ctrl> to select multiple items."));
	$oSelGroup->autoFill($aItems);

	// No groups in the list, sendToGroups and group listbox disabled
	if (count($aItems) == 0)
	{
		$oSendToGroups->setDisabled(true);
		if ($_REQUEST["optSendTo"] == "selection") {
			$_REQUEST["optSendTo"] == "all";
		}
	} else if (is_array($_REQUEST["selGroup"])) { 
		foreach ($_REQUEST["selGroup"] as $sValue)
		{ 
			if (array_key_exists($sValue, $oSelGroup->_options)) {
				// only select, if item still exists 
				$oSelGroup->_options[$sValue]->setSelected(true); 
			} 
		} 
	}

	switch ($_REQUEST["optSendTo"])
	{
		case "default":
			$oSendToDefault->setChecked(true);
			$oSelGroup->setDisabled(true);
			break;
		case "selection":
			$oSendToGroups->setChecked(true);
			break;
		default:
			$oSendToAll->setChecked(true);
			$oSelGroup->setDisabled(true);
	}

	// Recipients	
	$oForm->add(i18n("Recipients"), 
			$oSendToAll->toHTML(false)."&nbsp;".i18n("Send newsletter to all recipients")."<br />".chr(10).
			$oSendToDefault->toHTML(false)."&nbsp;".i18n("Send newsletter to the members of the default group")."<br />".chr(10).
			$oSendToGroups->toHTML(false)."&nbsp;".i18n("Send newsletter to the members of the selected group(s):")."<br />".chr(10).
			$oSelGroup->render());
	
	/* TODO: Work in progress
	// Files
	$oSelFiles = new cHTMLSelectElement("selFiles[]", "", "fileselect");
	$oSelFiles->setSize(5);
	//$oSelGroup->setStyle("width: 350px; margin-top: 5px; margin-bottom: 5px; margin-left: 25px;");
	$oSelFiles->setMultiSelect();
	$oSelFiles->setAlt(i18n("Note: Hold <Ctrl> to select multiple items."));
	//$oSelGroup->autoFill($aItems);
	$sFileSelScript = '<script type="text/javascript">
		var fb_handle;
		var fb_intervalhandle;

		function fncShowImageBrowser()
		{
			fb_handle = window.open(\''.$cfg["path"]["contenido_fullhtml"] .'frameset.php?area=upl&contenido='.$sess->id.'&appendparameters=imagebrowser\', \'imagebrowser\', \'dialog=yes,resizable=yes\');
			fb_intervalhandle = window.setInterval("fncUpdateImageFilebrowser()", 250);
		}

		function fncUpdateImageFilebrowser()
		{
			if (!fb_handle.left)
			{
				return;
			}
			
			if (!fb_handle.left.left_top)
			{
				return;
			}
			
			if (!fb_handle.left.left_top.document.getElementById("selectedfile"))
			{
				return;
			}	
			
			if (fb_handle.left.left_top.document.getElementById("selectedfile").value != "")
			{
				var oSelElement = document.forms[0].elements["selFiles[]"];
				var sValue = fb_handle.left.left_top.document.getElementById("selectedfile").value;

				oSelElement.options[oSelElement.length] = new Option(sValue, sValue, false, false);

				//fb_win.document.forms[0].elements[fb_fieldname].value = fb_handle.left.left_top.document.getElementById("selectedfile").value;
				
				fb_handle.close();
				window.clearInterval(fb_intervalhandle);
			}
		}
		</script>';
	$oPage->addScript('selFile', $sFileSelScript);
	$oForm->add(i18n("Attachments"), $oSelFiles->render().'<br /><span onclick="fncShowImageBrowser();">Test</span>');
	*/
	
	// Options
	$ckbWelcome		= new cHTMLCheckbox("ckbWelcome", "1");
	$ckbWelcome->setChecked($oNewsletter->get("welcome"));

	// Generate disabled cronjob element
	// Provide only "Use cronjob" option, if it has been explicitely enabled
	// (and the admin knows, what he is doing - like using a real cronjob, not a simulated one...)
	// Note, that the run_newsletter_job.php file has not been added to the cronjob 
	// list in the cronjobs folder - as it may be used, but not via cronjob simulation
	$ckbCronJob		= new cHTMLCheckbox("ckbCronJob", "1", "", $oNewsletter->get("use_cronjob"), true);
	
	if (getEffectiveSetting("newsletter", "option-cronjob-available", "false") == "true") {
		// Enable cronjob checkbox
		$ckbCronJob->setDisabled(""); 
	} else {
		// Give the user a hint
		$ckbCronJob->setAlt(i18n("Option has to be enabled as client setting - see techref for details"));
	}
	
	$oCkbDispatch 		= new cHTMLCheckbox("ckbDispatch", "enabled");
	$oCkbDispatch->setChecked($oNewsletter->get("dispatch"));
	$oTxtDispatchCount	= new cHTMLTextbox("txtDispatchCount", $oNewsletter->get("dispatch_count"), 4);
	$oTxtDispatchDelay	= new cHTMLTextbox("txtDispatchDelay", $oNewsletter->get("dispatch_delay"), 4);
	$oTxtDispatchDelay->setAlt(i18n("Note: Set to 0 to send chunks manually."));
	$oCkbSaveAsDefault	= new cHTMLCheckbox("ckbSetDefault", "1");
			
	$oForm->add(i18n("Options"),
			$ckbWelcome->toHTML(false)."&nbsp;".i18n("Welcome-Newsletter")."<br />".
			$ckbCronJob->toHTML(false)."&nbsp;".i18n("Use cronjob")."<br />".
			$oCkbDispatch->toHTML(false)."&nbsp;".i18n("Send in blocks:")."&nbsp;&nbsp;&nbsp;".
			i18n("Recipients per block:")."&nbsp;".$oTxtDispatchCount->render()."&nbsp;".
			i18n("Delay between blocks:")."&nbsp;".$oTxtDispatchDelay->render()."&nbsp;".i18n("sec.")."<br />".
			$oCkbSaveAsDefault->toHTML(false)."&nbsp;".i18n("Save option settings as default"));
		
	$oForm->add(i18n("Author"), $classuser->getUserName($oNewsletter->get("author")) . " (". $oNewsletter->get("created").")" ); 
	$oForm->add(i18n("Last modified by"), $classuser->getUserName($oNewsletter->get("modifiedby")). " (". $oNewsletter->get("modified").")" );	

	$sExecScript = '
    <script type="text/javascript">

	    // Enabled/Disable group box
        function checkSelection(strValue)
        {
            if (strValue == "selection") {
                document.getElementById("groupselect").disabled = false;
            } else {
                document.getElementById("groupselect").disabled = true;
            }
        }
		</script>';
	$oPage->addScript('exec', $sExecScript);
	
	$oPage->setContent($notis . $oForm->render(true));
} else {
	$oPage->setContent($notis . "");
}

$oPage->render();

?>