<?php
/*****************************************
* File      :   $RCSfile$
* Project   :   Contenido
* Descr     :   Shows job details
* Modified  :   $Date$
*
* © four for business AG, www.4fb.de
*
* $Id$
******************************************/
cInclude("classes", "class.newsletter.jobs.php");
cInclude("classes", "class.newsletter.logs.php");
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "class.ui.php");
cInclude("classes", "class.htmlelements.php");

// Initialization
$oPage	= new cPage;

if ($action == "news_job_run" && $perm->have_perm_area_action($area, $action) && is_numeric($_REQUEST["idnewsjob"])) {
	// Run job
	$oJob		= new cNewsletterJob($_REQUEST["idnewsjob"]);
	$iSendCount	= $oJob->runJob();
	
	if ($oJob->get("dispatch") == 1 && $oJob->get("sendcount") < $oJob->get("rcpcount")) {
		// Send in chunks
		$sPathNext = $sess->url("main.php?area=$area&action=news_job_run&frame=4&idnewsjob=".$_REQUEST["idnewsjob"]);
		
		// Calculating some statistics 
		$iChunk		= ceil($oJob->get("sendcount") / $oJob->get("dispatch_count"));
		$iChunks	= ceil($oJob->get("rcpcount")  / $oJob->get("dispatch_count"));
			
		// Dispatch count > send/recipient count, set values to 1, at least
		if ($iChunk == 0) {
			$iChunk = 1; 
		}
		if ($iChunks == 0) {
			$iChunks = 1;
		}
		
		if ($oJob->get("dispatch_delay") == 0) {
			// Send manually
			$oForm = new UI_Table_Form("properties", $sPathNext);
			$oForm->addHeader(i18n("Report:"));
			$oForm->add("", "");
						 
			$oForm->add("", sprintf(i18n("Sending newsletter ... (chunk %s of %s, recipients: %s, sent: %s)"),
									$iChunk, $iChunks, $oJob->get("rcpcount"), $oJob->get("sendcount")));

			$oForm->setActionButton("cancel", $cfg['path']['contenido_fullhtml']."images/but_cancel.gif", i18n("Stop sending"), "c");
			$oForm->setActionButton("submit", $cfg['path']['contenido_fullhtml']."images/but_ok.gif", i18n("Send next chunk"), "s", "news_job_run");
		} else {
			// Send automatically
			$oForm = new UI_Table_Form("properties");
			$oForm->addHeader(i18n("Report:"));
			$oForm->add("", "");
			
			$oForm->add("", sprintf(i18n("Sending newsletter ... (chunk %s of %s, recipients: %s, sent: %s)"),
									$iChunk, $iChunks, $oJob->get("rcpcount"), $oJob->get("sendcount")));

			$oPage->addScript("Refresh", '<meta http-equiv="refresh" content="'.$oJob->get("dispatch_delay").'; URL='.$sPathNext.'">');
			$oForm->unsetActionButton("submit");
			$oForm->setActionButton("cancel", $cfg['path']['contenido_fullhtml']."images/but_cancel.gif", i18n("Stop sending"), "c");
		}
	} else {
		// All newsletters should have been sent
		$oForm = new UI_Table_Form("properties");
		$oForm->addHeader(i18n("Report:"));
		$oForm->add("", "");
		
		$oForm->add("", sprintf(i18n("The newsletter has been sent to %s recipients"), $oJob->get("sendcount")));
		$oPage->setReload();
	}
   
	$oPage->setContent($notis . $oForm->render(true));
} else if ($action == "news_job_delete" && $perm->have_perm_area_action($area, $action) && is_numeric($_REQUEST["idnewsjob"])) {
	$oJobs = new cNewsletterJobCollection;
	$oJobs->delete($_REQUEST["idnewsjob"]);
	
	$oPage->setSubnav("blank", "news_jobs");
	$oPage->setReload();
	$oPage->setContent($notis);
} else if ($action == "news_job_details" || $action == "news_job_detail_delete") {
	// Show job details (recipients)
	
	// Remove recipient from a job
	if ($action == "news_job_detail_delete" && is_numeric($_REQUEST["idnewslog"]) && $perm->have_perm_area_action($area, "news_job_detail_delete"))
	{
		$oLogs = new cNewsletterLogCollection;
		$oLogs->delete($_REQUEST["idnewslog"]);
	}
	
	// Initialize
	if ($_REQUEST["sortmode"] !== "DESC") {
		$_REQUEST["sortmode"] = "ASC";
	}
	
	$sDateFormat = getEffectiveSetting("backend", "timeformat", "d.m.Y H:i");
	
	$oList = new cScrollList (true, "news_job_details");
	$oList->setCustom("idnewsjob", $_REQUEST["idnewsjob"]);
	
	$oList->setHeader(i18n("Recipient"), i18n("E-Mail"), i18n("Type"), i18n("Status"), i18n("Sent"), i18n("Actions"));
	$oList->setSortable(0, true);
	$oList->setSortable(1, true);
	$oList->setSortable(2, true);
	$oList->setSortable(3, true);
	$oList->setSortable(4, true);
		
	if (!is_object($oLogs)) {
		$oLogs = new cNewsletterLogCollection;
	} else {
		$oLogs->resetQuery();
	}
	$oLogs->setWhere("idnewsjob", $_REQUEST["idnewsjob"]);
			
	$aCols = array("rcpname", "rcpemail", "status", "sent");
	if (!array_key_exists($_REQUEST["sortby"], $aCols)) { 
		$oLogs->setOrder("rcpname " . $_REQUEST["sortmode"]);
	} else {
		$oLogs->setOrder($aCols[$_REQUEST["sortby"]] . " " . $_REQUEST["sortmode"]);
	}
	
	// TODO: Limitierung der Datensätze und LIMIT einfügen
	$oLogs->query();
	
	$oImgDelete = new cHTMLImage("images/delete.gif");
	$oImgDelete->setAlt(i18n("Delete item"));
	$sImgDelete = $oImgDelete->render();
	unset ($oImgDelete);
	
	$iCount = 0;
	$aNewsType[]  = array(); // Performance
	$aNewsType[0] = i18n("Text only");
	$aNewsType[1] = i18n("HTML/Text");
	while ($oLog = $oLogs->next())
	{
		$sName	= $oLog->get("rcpname");
		$sEMail	= $oLog->get("rcpemail");
		
		switch ($oLog->get("status"))
		{
			case "pending":
				$sStatus = i18n("Waiting for sending");
				break;
			case "sending":
				$sStatus = i18n("Sending");
				break;
			case "successful":
				$sStatus = i18n("Successful");
				break;
			default:
				$sStatus = sprintf(i18n("Error: %s"), $oLog->get("status"));
		}
		
		if ($oLog->get("sent") == "0000-00-00 00:00:00") {
			$sSent	= "-";
		} else {
			$sSent	= date($sDateFormat, strtotime($oLog->get("sent")));
		}
		
		$sLnkRemove = "";
		if ($oLog->get("status") == "pending" && $perm->have_perm_area_action($area, "news_job_detail_delete"))
		{
			$oLnkRemove = new cHTMLLink;
    		$oLnkRemove->setCLink("news_jobs", 4, "news_job_detail_delete");
    		$oLnkRemove->setCustom("idnewsjob", $_REQUEST["idnewsjob"]);
    		$oLnkRemove->setCustom("idnewslog", $oLog->get($oLog->primaryKey));
    		$oLnkRemove->setCustom("sortby",	$_REQUEST["sortby"]);
    		$oLnkRemove->setCustom("sortmode",	$_REQUEST["sortmode"]);
	    	$oLnkRemove->setContent($sImgDelete);
	    	
	    	$sLnkRemove = $oLnkRemove->render();
		} 
					
		$oList->setData($iCount, $sName, $sEMail, $aNewsType[$oLog->get("rcpnewstype")], $sStatus, $sSent, $sLnkRemove);

		$iCount++;
	}
	
	// A little bit senseless, as the data is already sorted, but
	// we need the sortmode in the header link  
	$oList->sort($_REQUEST["sortby"], $_REQUEST["sortmode"]); 
	$oPage->setContent($oList->render());
} else {
	// Just show the job data
	$oJob  = new cNewsletterJob($_REQUEST["idnewsjob"]);
	
	$oForm = new UI_Table_Form("properties");
	$oForm->setVar("frame",		$frame);
	$oForm->setVar("area",		$area);
	$oForm->setVar("action",	"");
	$oForm->setVar("idnewsjob",	$idnewsjob);

	$oForm->addHeader(i18n("Newsletter Dispatch Job"));
	
	$oForm->add(i18n("Name"), $oJob->get("name"));
	
	$sDateFormat = getEffectiveSetting("backend", "timeformat", "d.m.Y H:i");
	switch ($oJob->get("status"))
	{
		case 1:
			$oForm->add(i18n("Status"),	i18n("Pending"));
			break;
		case 2:
			$oForm->add(i18n("Status"),	sprintf(i18n("Sending (started: %s)"), 
										date($sDateFormat, strtotime($oJob->get("started")))));
			break;
		case 9:
			$oForm->add(i18n("Status"),	sprintf(i18n("Finished (started: %s, finished: %s)"),
										date($sDateFormat, strtotime($oJob->get("started"))),
										date($sDateFormat, strtotime($oJob->get("finished")))));
			break;
	}
	
	$oForm->add(i18n("Statistics"),	sprintf(i18n("Planned: %s, Send: %s"), $oJob->get("rcpcount"), $oJob->get("sendcount"))); 
	$oForm->add(i18n("From"),		$oJob->get("newsfrom") . " (" . $oJob->get("newsfromname") . ")");
	$oForm->add(i18n("Subject"),	$oJob->get("subject"));

	if ($oJob->get("type") == "html")
	{
		$oForm->add(i18n("Type"),	i18n("HTML and text"));
		
		$txtMessageHTML	= new cHTMLTextarea("txtMessageHTML", $oJob->get("message_html"), 80, 20);
		$txtMessageHTML->setDisabled("disabled");
		
		$oForm->add(i18n("HTML Message"), $txtMessageHTML->render());	
	} else {
		$oForm->add(i18n("Type"),	i18n("Text only"));
	}
	$txtMessageText	= new cHTMLTextarea("txtMessageText", $oJob->get("message_text"), 80, 20);
	$txtMessageText->setDisabled("disabled");
	
	$oForm->add(i18n("Text Message"), $txtMessageText->render());
		
	$aSendTo = unserialize($oJob->get("send_to"));
	switch ($aSendTo[0])
	{
		case "all":
			$sSendToInfo = i18n("Send newsletter to all recipients");
			break;
		case "default":
			$sSendToInfo = i18n("Send newsletter to the members of the default group");
			break;
		case "selection":
			$sSendToInfo = i18n("Send newsletter to the members of the selected group(s):");
			
			unset ($aSendTo[0]); 
			foreach ($aSendTo as $sGroup)
			{
				$sSendToInfo .= "<br />" . $sGroup;
			}
			break;
		case "single":
			$sSendToInfo = i18n("Send newsletter to single recipient:");
			$sSendToInfo .= "<br />" . $aSendTo[1] . " (" . $aSendTo[2] . ")";			
			break;
		default:
	}
	unset ($aSendTo);
	
	$oForm->add(i18n("Recipients"), $sSendToInfo);
	
	if ($oJob->get("use_cronjob") == 1) {
		$sOptionsInfo = i18n("Use cronjob: Enabled");
	} else {
		$sOptionsInfo = i18n("Use cronjob: Not enabled");
	}
	
	if ($oJob->get("dispatch")) {
		$sOptionsInfo .= "<br />" . sprintf(i18n("Dispatch: Enabled (block size: %s, delay: %s sec.)"), $oJob->get("dispatch_count"), $oJob->get("dispatch_delay"));
	} else {
		$sOptionsInfo .= "<br />" . i18n("Dispatch: Disabled");
	}
	
	$oForm->add(i18n("Options"), $sOptionsInfo);
		
	$oForm->add(i18n("Author"),		$oJob->get("authorname")); 
	$oForm->add(i18n("Created"),	$oJob->get("created"));	
	
	$oPage->setContent($oForm->render(true));
}

$oPage->render();

?>
