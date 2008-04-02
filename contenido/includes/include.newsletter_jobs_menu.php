<?php
/*****************************************
* File      :   $RCSfile$
* Project   :   Contenido
* Descr     :   Frontend user list
* Modified  :   $Date$
*
* © four for business AG, www.4fb.de, provided by HerrB
*
* $Id$
******************************************/
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "widgets/class.widgets.foldingrow.php");
cInclude("classes", "widgets/class.widgets.pager.php");
cInclude("classes", "class.newsletter.jobs.php");
cInclude("classes", "contenido/class.user.php");
cInclude("classes", "class.ui.php");

$oPage	= new cPage;
$oMenu	= new UI_Menu;
$oJobs	= new cNewsletterJobCollection;

##################################
# Getting values for sorting, etc.
##################################
// Items per page
$oUser = new cApiUser($auth->auth["uid"]);
if (!isset($_REQUEST["elemperpage"]) || !is_numeric($_REQUEST["elemperpage"]) || $_REQUEST["elemperpage"] < 0) {
	$_REQUEST["elemperpage"] = $oUser->getProperty("itemsperpage", $area);
}
if (!is_numeric($_REQUEST["elemperpage"])) {
	$_REQUEST["elemperpage"] = 25;
}
if ($_REQUEST["elemperpage"] > 0) { 
	// -- All -- will not be stored, as it may be impossible to change this back to something more useful
	$oUser->setProperty("itemsperpage", $area, $_REQUEST["elemperpage"]);
}
unset ($oUser);

// Current page
if (!isset($_REQUEST["page"]) || !is_numeric($_REQUEST["page"]) || $_REQUEST["page"] <= 0 || $_REQUEST["elemperpage"] == 0) {
	$_REQUEST["page"] = 1;
}

// Sort order
if ($_REQUEST["sortorder"] != "ASC") {
	$_REQUEST["sortorder"]  = "DESC";
}

// Initialization
$aFields = array();
$aFields["name"]	= array("field" => "name", "caption" => i18n("Name"), "type" => "base,sort,search");
$aFields["created"]	= array("field" => "created", "caption" => i18n("Created"), "type" => "base,sort");
$aFields["status"]	= array("field" => "status", "caption" => i18n("Status"), "type" => "base,sort");
$aFields["cronjob"]	= array("field" => "use_cronjob", "caption" => i18n("Use cronjob"), "type" => "base");

$sDelTitle		= i18n("Delete dispatch job");
$sDelDescr		= i18n("Do you really want to delete the following newsletter dispatch job:<br>");

$sSendTitle		= i18n("Run job");
$sSendDescr		= i18n("Do you really want to run the following job:<br>");

$sView          = i18n("View");

// Fill authors in dropdown
$sSQL = "SELECT DISTINCT author, authorname FROM ".$cfg["tab"]["news_jobs"]." ORDER BY authorname";
$db->query($sSQL);

$aItems = array();
$bUserInTheList = false;
while ($db->next_record()) {
	if ($db->f("author") == $auth->auth["uid"]) {
		$bUserInTheList = true;
	}
	$aItems[] = array($db->f("author"), urldecode($db->f("authorname")));
}
$oSelAuthor = new cHTMLSelectElement("selAuthor");
$oSelAuthor->autoFill($aItems);
if (!$bUserInTheList) {
	$oOption = new cHTMLOptionElement($auth->auth["uname"], $auth->auth["uid"]);
	$oSelAuthor->addOptionElement($auth->auth["uid"], $oOption);
}
$oSelAuthor->setDefault($_REQUEST["selAuthor"]);

// Items per page
$oSelectItemsPerPage = new cHTMLSelectElement("elemperpage");
$oSelectItemsPerPage->autoFill(array(0 => i18n("-- All --"), 25 => 25, 50 => 50, 75 => 75, 100 => 100));
$oSelectItemsPerPage->setDefault($_REQUEST["elemperpage"]);

// sortby
$oSelectSortBy = new cHTMLSelectElement("sortby");
foreach ($aFields as $sKey => $aData) {
	if (strpos($aData["type"], "sort") !== false) {
		if ($_REQUEST["sortby"] == "") {
			$_REQUEST["sortby"] = "created"; // Usually $sKey, but I'd like to get it sort by created as default...
		}
		$oOption = new cHTMLOptionElement($aData["caption"], $sKey);
		$oSelectSortBy->addOptionElement($sKey, $oOption);
	}
}	
$oSelectSortBy->setDefault($_REQUEST["sortby"]);

// Sortorder
$oSelectSortOrder = new cHTMLSelectElement("sortorder");
$oSelectSortOrder->autoFill(array("ASC" => i18n("Ascending"), "DESC" => i18n("Descending")));
$oSelectSortOrder->setDefault($_REQUEST["sortorder"]);

// Search for
$oTextboxFilter = new cHTMLTextbox("filter", $_REQUEST["filter"], 16);

// Search in
$oSelectSearchIn = new cHTMLSelectElement("searchin");
$oOption = new cHTMLOptionElement(i18n("-- All fields --"), "--all--");
$oSelectSearchIn->addOptionElement("all", $oOption);

foreach ($aFields as $sKey => $aData) {
	if (strpos($aData["type"], "search") !== false) {
		$oOption = new cHTMLOptionElement($aData["caption"], $sKey);
		$oSelectSearchIn->addOptionElement($sKey, $oOption);
	}
}
$oSelectSearchIn->setDefault($_REQUEST["searchin"]);

// Submit button
$oSubmit = new cHTMLButton("submit", i18n("Apply"));


// Request data
$oJobs->setWhere("idclient", $client);
$oJobs->setWhere("idlang", $lang);
if ($_REQUEST["selAuthor"] == "") {
    $oJobs->setWhere("author", $_REQUEST["selAuthor"]);
}

if ($_REQUEST["filter"] != "") {
	if ($_REQUEST["searchin"] == "--all--" || $_REQUEST["searchin"] == "") {
		foreach ($aFields as $sKey => $aData) {
			if (strpos($aData["type"], "search") !== false) {
				$oJobs->setWhereGroup("filter", $aData["field"], $_REQUEST["filter"], "LIKE");
			}
		}
		$oJobs->setInnerGroupCondition("filter", "OR");
	} else {
		$oJobs->setWhere($_REQUEST["searchin"], $_REQUEST["filter"], "LIKE");
	}
}

if ($_REQUEST["elemperpage"] > 0) {
	$oJobs->query();

	$iItemCount = $oJobs->count(); // Getting item count without limit (for page function) - better idea anyone (performance)?
    
    if ($_REQUEST["elemperpage"]*($_REQUEST["page"]) >= $iItemCount+$_REQUEST["elemperpage"] && $_REQUEST["page"]  != 1) {
        $_REQUEST["page"]--;
    }
	$oJobs->setLimit($_REQUEST["elemperpage"] * ($_REQUEST["page"] - 1), $_REQUEST["elemperpage"]);
} else {
	$iItemCount = 0;
}

$oJobs->setOrder($_REQUEST["sortby"]." ".$_REQUEST["sortorder"]);
$oJobs->query();

// Output data
$oMenu			= new UI_Menu;
$iMenu			= 0;
$sDateFormat	= getEffectiveSetting("backend", "timeformat", "d.m.Y H:i");

// Prepare "send link" template
$sTplSend = '<a title="'.$sSendTitle.'" href="javascript://" onclick="showSendMsg(\'{ID}\',\'{NAME}\')"><img src="'.$cfg['path']['images'].'newsletter_16.gif" border="0" title="'.$sSendTitle.'" alt="'.$sSendTitle.'"></a>';

while ($oJob = $oJobs->next())
{
	$iMenu++;
	$iID	= $oJob->get("idnewsjob");
	$sName	= $oJob->get("name") . " (" . date($sDateFormat, strtotime($oJob->get("created"))) .")";

	$oLnk = new cHTMLLink;
	$oLnk->setMultiLink($area, "", $area, "");
	$oLnk->setCustom("idnewsjob", $iID);

// Is at present redundant 
	//$oMenu->setImage($iMenu, "images/newsletter_16.gif");
	$oMenu->setTitle($iMenu, $sName);
	
	switch ($oJob->get("status"))
	{
		case 1:
			// Pending
			if ($oJob->get("cronjob") == 0)
			{
				// Standard job can be run if user has the right to do so
				if ($perm->have_perm_area_action($area, "news_job_run"))
				{
					$sLnkSend = str_replace('{ID}',   $iID, $sTplSend);
					$sLnkSend = str_replace('{NAME}', addslashes($sName), $sLnkSend);
				 
					$oMenu->setActions($iMenu, 'send', $sLnkSend);
				}
			} else if ($oJob->get("cronjob") == 1) {
				// It's a cronjob job - no manual sending, show it blue
				$oLnk->updateAttributes(array("style" => "color:#0000FF"));
			}
			
			if ($perm->have_perm_area_action($area, "news_job_delete")) {
				// Job may be deleted, if user has the right to do so
				$oMenu->setActions($iMenu, 'delete', '<a title="'.$sDelTitle.'" href="javascript://" onclick="showDelMsg('.$iID.',\''.addslashes($sName).'\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$sDelTitle.'" alt="'.$sDelTitle.'"></a>');
			}
			break;
		case 2:
			// Sending job
			if ($perm->have_perm_area_action($area, "news_job_run"))
			{
				// User may try to start sending, again - if he has the right to do so
				$sLnkSend = str_replace('{ID}',   $iID, $sTplSend);
				$sLnkSend = str_replace('{NAME}', addslashes($sName), $sLnkSend);
				 
				$oMenu->setActions($iMenu, 'send', $sLnkSend);
			}
			
			$oLnk->updateAttributes(array("style" => "color:#da8a00"));
			
			$sDelete = '<img src="'.$cfg['path']['images'].'delete_inact.gif" border="0" title="'.$sDelTitle.'" alt="'.$sDelTitle.'">';
			break;
		case 9:
			// Job finished, don't do anything
			$oLnk->updateAttributes(array("style" => "color:#808080"));
			
			if ($perm->have_perm_area_action($area, "news_job_delete")) {
				// You have the right, but you can't delete the job after sending
				$oMenu->setActions($iMenu, 'delete', '<img src="'.$cfg['path']['images'].'delete_inact.gif" border="0" title="'.$sDelTitle.'" alt="'.$sDelTitle.'">');
			}
			break;
	}
	
	$oMenu->setLink($iMenu, $oLnk);
}

$execScript = '
    <script type="text/javascript">
        /* Session-ID */
        var sid = "'.$sess->id.'";

        /* Create messageBox instance */
        box = new messageBox("", "", "", 0, 0);

        function showSendMsg(lngId, strElement) {
            box.confirm("'.$sSendTitle.'", "'.$sSendDescr.'<b>" + strElement + "</b>", "runJob(\'" + lngId + "\')");
        }

        function showDelMsg(lngId, strElement) {
            box.confirm("'.$sDelTitle.'", "'.$sDelDescr.'<b>" + strElement + "</b>", "deleteJob(\'" + lngId + "\')");
        }

        /* Function for running job */
        function runJob(idnewsjob)
        {
            oForm = parent.parent.left.left_top.document.getElementById("dispatch_listoptionsform");
         
            url  = "main.php?area=news_jobs";
            url += "&action=news_job_run";
            url += "&frame=4";
            url += "&idnewsjob=" + idnewsjob;
            url += "&contenido=" + sid;
            url += get_registered_parameters();
            url += "&selAuthor=" + oForm.selAuthor.value;
            url += "&sortby=" + oForm.sortby.value;
            url += "&sortorder=" + oForm.sortorder.value;
            url += "&filter=" + oForm.filter.value;
            url += "&elemperpage=" + oForm.elemperpage.value;

            parent.parent.right.right_bottom.location.href = url;
        }

        /* Function for deleting job */
        function deleteJob(idnewsjob)
        {
            oForm = parent.parent.left.left_top.document.getElementById("dispatch_listoptionsform");
          
            url  = "main.php?area=news_jobs";
            url += "&action=news_job_delete";
            url += "&frame=4";
            url += "&idnewsjob=" + idnewsjob;
            url += "&contenido=" + sid;
            url += get_registered_parameters();
            url += "&selAuthor=" + oForm.selAuthor.value;
            url += "&sortby=" + oForm.sortby.value;
            url += "&sortorder=" + oForm.sortorder.value;
            url += "&filter=" + oForm.filter.value;
            url += "&elemperpage=" + oForm.elemperpage.value;

            parent.parent.right.right_bottom.location.href = url;
        }
		</script>';

$oPage->setMargin(0);
$oPage->addScript('messagebox', '<script type="text/javascript" src="scripts/messageBox.js.php?contenido='.$sess->id.'"></script>');
$oPage->addScript('exec', $execScript);
$oPage->addScript('cfoldingrow.js', '<script language="JavaScript" src="scripts/cfoldingrow.js"></script>');
$oPage->addScript('parameterCollector.js', '<script language="JavaScript" src="scripts/parameterCollector.js"></script>');

//generate current content for Object Pager
$oPagerLink = new cHTMLLink;
$oPagerLink->setLink("main.php");
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setCustom("selAuthor", $_REQUEST["selAuthor"]);
$oPagerLink->setCustom("elemperpage", $_REQUEST["elemperpage"]);
$oPagerLink->setCustom("filter", $_REQUEST["filter"]);
$oPagerLink->setCustom("restrictgroup", $_REQUEST["restrictgroup"]);
$oPagerLink->setCustom("sortby", $_REQUEST["sortby"]);
$oPagerLink->setCustom("sortorder", $_REQUEST["sortorder"]);
$oPagerLink->setCustom("searchin", $_REQUEST["searchin"]);
$oPagerLink->setCustom("frame", $frame);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);

$oPager = new cObjectPager("0ed6d632-6adf-4f09-a0c6-1e38ab60e303", $iItemCount, $_REQUEST["elemperpage"], $_REQUEST["page"], $oPagerLink, "page", $pagerlDisp);

//add slashes, to insert in javascript
$sPagerContent = $oPager->render(1);
$sPagerContent = str_replace('\\', '\\\\', $sPagerContent);
$sPagerContent = str_replace('\'', '\\\'', $sPagerContent);

//send new object pager to left_top
$sRefreshPager = '
    <script type="text/javascript">
        var sNavigation = \''.$sPagerContent.'\';
        var left_top = parent.left_top;
        if (left_top.document) {
            var oPager = left_top.document.getElementById(\'0ed6d632-6adf-4f09-a0c6-1e38ab60e303\');
            var sDisplay = oPager.style.display;
            if (oPager) {
                oInsert = oPager.firstChild;
                oInsert.innerHTML = sNavigation;
                left_top.dispatch_listoptionsform_curPage = '.$_REQUEST["page"].';
                left_top.toggle_pager(\'0ed6d632-6adf-4f09-a0c6-1e38ab60e303\');
                if (sDisplay == \'none\') {
                    oPager.style.display = sDisplay;
                }
            }
        }
    </script>';
    
$oPage->addScript('refreshpager', $sRefreshPager);  

$oPage->setContent(array('<table border="0" cellspacing="0" cellpadding="0" width="100%">', $oListOptionRow, '</table>', $oMenu->render(false)));
$oPage->render();

?>