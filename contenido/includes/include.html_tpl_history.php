<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Style History.
 * We use super class Version to create a new Version. To read the xml File, we use SimpleXml. 
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @version    1.0.0
 * @author     Bilal Arslan, Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release >= 5.0
 * 
 * {@internal 
 *   created 2008-08-05
 *
 * }}
 * 
 */
// For build select box
cInclude("classes", "class.ui.php");
cInclude("classes", "class.htmlelements.php");

// For get Version Informatian    
cInclude("classes", "class.version.php");
cInclude("classes", "class.versionFile.php");

// For read Fileinformation an get the id of current File
cInclude("includes", "functions.file.php");

// For Editor syntax highlighting
cInclude("external", "edit_area/class.edit_area.php");

$sFileName = "";    
$sFileName = $_REQUEST['file'];
$bDeleteFile = false;


if($sFileName == ""){
	$sFileName = $_REQUEST['idhtml_tpl'];	
}

$sType = "templates";
$sTypeContent = "templates";

$oPage = new cPage;
$oPage->addScript('messageBox', '<script type="text/javascript" src="'.$sess->url('scripts/messageBox.js.php').'"></script>');
$oPage->addScript('messageBoxInit', '<script type="text/javascript">box = new messageBox("", "", "", 0, 0);</script>');


if (!$perm->have_perm_area_action($area, 'htmltpl_history_manage'))
{
  $notification->displayNotification("error", i18n("Permission denied"));
  $oPage->render();
} else if (!(int) $client > 0) {
  $oPage->render();
} else if (getEffectiveSetting('versioning', 'activated', 'false') == 'false') {
  $notification->displayNotification("warning", i18n("Versioning is not activated"));
  $oPage->render();
} else {


    
    $sTypeContent = "templates";
	
    // Get File Informataion from DB
    $aFileInfo = getFileInformation ($client, $sFileName , $sTypeContent, $db);
	
	// [action] => history_truncate delete all current history
  	if($_POST["action"] == "history_truncate") {
    	$oVersionHtmlTemp = new VersionFile($aFileInfo["idsfi"], $aFileInfo, $sFileName ,$sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);
  		 $bDeleteFile = $oVersionHtmlTemp->deleteFile();
        unset($oVersionHtmlTemp);
  	}
    if ($_POST["html_tpl_send"] == true && $_POST["html_tpl_code"] !="" && $sFileName != "" && $aFileInfo["idsfi"]!="" ) { // save button 
            $oVersionHtmlTemp = new VersionFile($aFileInfo["idsfi"], $aFileInfo,$sFileName ,$sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);
            
    //		Get Post variables
            $sHTMLCode = $_POST["html_tpl_code"];
            $sHTMLName = $_POST["html_tpl_name"];
            $sHTMLDesc = $_POST["html_tpl_desc"];

          $sPath = $oVersionHtmlTemp->getPathFile();   
		//	Edit File
		
        //		There is a need for renaming file
        if($sFileName != $sHTMLName) {
            if (getFileType($sHTMLName) != 'html' AND strlen(stripslashes(trim($sHTMLName))) > 0) {
                $sHTMLName = stripslashes($sHTMLName).".html";
            }
        
            renameFile($sFileName, $sHTMLName, $oVersionHtmlTemp->getPathFile());
            $oPage->addScript("reload", $oVersionHtmlTemp->renderReloadScript('htmltpl', $sHTMLName, $sess));               
        }
        
		if(fileEdit($sHTMLName, $sHTMLCode, $sPath)) {
		    //		make new revision File	
			$oVersionHtmlTemp->createNewVersion();	 
			
			// 		Update File Information 
			updateFileInformation($client, $sFileName, $sType, $aFileInfo, $sHTMLDesc, $db, $sHTMLName); 
            $sFileName = $sHTMLName;
		 }

         unset($oVersionHtmlTemp);
    }

    if($sFileName != "" && $aFileInfo["idsfi"]!="" && $_POST["action"] != "history_truncate" ) {
    	$oVersionHtmlTemp= new VersionFile($aFileInfo["idsfi"],$aFileInfo["description"] ,$sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);
    	
    	// Init Form variables of SelectBox
    	$sSelectBox = "";
    	$oVersionHtmlTemp->setVarForm("area",  $area);
    	$oVersionHtmlTemp->setVarForm("frame", $frame);
    	$oVersionHtmlTemp->setVarForm("idhtml_tpl", $sFileName);
        $oVersionHtmlTemp->setVarForm("file", $sFileName);

    	// create and output the select box, for params please look class.version.php
    	$sSelectBox = $oVersionHtmlTemp->buildSelectBox("html_tpl_history", "HTML Template History", "Show History Entry", "idhtml_tpl_history");
    	
    	// Generate Form
    	$oForm = new UI_Table_Form("jscript_display");
    	$oForm->addHeader(i18n("Edit JScript"));
    	$oForm ->setWidth("100%");
    	$oForm->setVar("area", $area);
    	$oForm->setVar("frame", $frame);
    	$oForm->setVar("idhtml_tpl", $sFileName);
    	$oForm->setVar("html_tpl_send", 1);
    	
    	
    	// if send form refresh button
        if ($_POST["idhtml_tpl_history"] != "") {
            $sRevision = $_POST["idhtml_tpl_history"];
    	} else {
            $sRevision = $oVersionHtmlTemp->getLastRevision();
        }
        
        if ($sRevision != '') {
            $sPath = $oVersionHtmlTemp->getFilePath() . $sRevision;
    		
    		// Read XML Nodes  and get an array 
    		$aNodes = array();
    		$aNodes = $oVersionHtmlTemp->initXmlReader($sPath);

    		// Create Textarea and fill it with xml nodes
            if (count($aNodes) > 1) { 
    			//	if choose xml file read value an set it						
    			$sName = $oVersionHtmlTemp->getTextBox("html_tpl_name", $aNodes["name"], 60);
    			$sDescription = $oVersionHtmlTemp->getTextarea("html_tpl_desc", $aNodes["desc"], 100, 10);
    			$sCode = $oVersionHtmlTemp->getTextarea("html_tpl_code", $aNodes["code"], 100, 30, "IdLaycode");
    		
    		}

    	}

    	// Add new Elements of Form
    	$oForm->add(i18n("Name"), $sName);
    	$oForm->add(i18n("Description"), $sDescription);
    	$oForm->add(i18n("Code"), $sCode);
    	$oForm->setActionButton("apply", "images/but_ok.gif", i18n("Copy to current"), "c"/*, "mod_history_takeover"*/); //modified it 
    	$oForm->unsetActionButton("submit");
    	
    	// Render and handle History Area
    	$oPage->setEncoding("utf-8");
    	
    	$oEditAreaOutput = new EditArea('IdLaycode', 'php', substr(strtolower($belang), 0, 2), true, $cfg, !$bInUse);
    	$oPage->addScript('IdLaycode', $oEditAreaOutput->renderScript());

    	if($sSelectBox !="") {
    		$oPage->setContent($sSelectBox . $oForm->render());
    	
    	} else {
    		$notification->displayNotification("warning", i18n("No template history available"));
    	}	
    	$oPage->render();
    	
    } else {
    	if($bDeleteFile){
    		$notification->displayNotification("warning", i18n("Version history was cleared"));
    	} else {
    		$notification->displayNotification("error", i18n("Internal History Error"));	
    	}
    	
    }
}
?>