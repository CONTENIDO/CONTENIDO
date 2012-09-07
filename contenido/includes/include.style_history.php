<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Style History.
 * We use super class Version to create a Version. To read the xml File, we use SimpleXml. 
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
 *   $Id: include.style_history.php 741 2008-08-27 10:51:59Z timo.trautmann $:
 * }}
 * 
 */
if(!defined('CON_FRAMEWORK')) {
 die('Illegal call');
}
 // For build select box
cInclude("classes", "class.ui.php");
cInclude("classes", "class.htmlelements.php");

// For get Version Informatian    
cInclude("classes", "class.version.php");
cInclude("classes", "class.versionFile.php");

// For update current layout with Revision
cInclude("includes", "functions.lay.php");

// For read Fileinformation an get the id of current File
cInclude("includes", "functions.file.php");

// For Editor syntax highlighting
cInclude("external", "edit_area/class.edit_area.php");

$sFileName = "";    
$sFileName = $_REQUEST['file'];

if($sFileName == ""){
	$sFileName = $_REQUEST['idstyle'];	// Content Type is css
}

$sType = "css";

$oPage = new cPage;
$oPage->addScript('messageBox', '<script type="text/javascript" src="'.$sess->url('scripts/messageBox.js.php').'"></script>');
$oPage->addScript('messageBoxInit', '<script type="text/javascript">box = new messageBox("", "", "", 0, 0);</script>');

if (!$perm->have_perm_area_action($area, 'style_history_manage'))
{
  $notification->displayNotification("error", i18n("Permission denied"));
  $oPage->render();
} else if (!(int) $client > 0) {
  $oPage->render();
} else if (getEffectiveSetting('versioning', 'activated', 'false') == 'false') {
  $notification->displayNotification("warning", i18n("Versioning is not activated"));
  $oPage->render();
} else {

    $sTypeContent = "css";

    $aFileInfo = getFileInformation ($client, $sFileName , $sTypeContent, $db);
	
	// [action] => history_truncate delete all current history
  	if($_POST["action"] == "history_truncate") {
    	$oVersionStyle = new VersionFile($aFileInfo["idsfi"], $aFileInfo, $sFileName ,$sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);
  		 $bDeleteFile = $oVersionStyle->deleteFile();
        unset($oVersionStyle);
  	}
	
    if ($_POST["style_send"] == true && $_POST["stylecode"] !="" && $sFileName != "" && $aFileInfo["idsfi"]!="") { // save button 
    	//		Get Post variables
    	$oVersionStyle = new VersionFile($aFileInfo["idsfi"], $aFileInfo, $sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);		
        
        $sStyleCode = $_POST["stylecode"];
        $sStyleName = $_POST["stylename"];
        $sStyleDesc = $_POST["styledesc"];
        
        $sPath = $oVersionStyle->getPathFile();
        
        //	Edit File
        
        //		There is a need for renaming file
        if($sFileName != $sStyleName){
            if (getFileType($sStyleName) != 'css' AND strlen(stripslashes(trim($sStyleName))) > 0) {
                $sStyleName = stripslashes($sStyleName).".css";
            }
        
            renameFile($sFileName, $sStyleName, $oVersionStyle->getPathFile());
            $oPage->addScript("reload", $oVersionStyle->renderReloadScript('style', $sStyleName, $sess));         
        }
        
        if(fileEdit($sStyleName, $sStyleCode, $sPath)) {
            //		make new revision File	
            $oVersionStyle->createNewVersion();	 
            
            // 		Update File Information 
			updateFileInformation($client, $sFileName, $sType, $aFileInfo["author"], $sStyleDesc, $db, $sStyleName); 
            $sFileName = $sStyleName;   
        }
        
        unset($oVersionStyle);
    }

    if($sFileName != "" && $aFileInfo["idsfi"]!="" && $_POST["action"] != "history_truncate") {
    	$oVersionStyle = new VersionFile($aFileInfo["idsfi"], $aFileInfo, $sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);
    	
    	// Init Form variables of SelectBox
    	$sSelectBox = "";
    	$oVersionStyle->setVarForm("area",  $area);
    	$oVersionStyle->setVarForm("frame", $frame);
    	$oVersionStyle->setVarForm("idstyle", $sFileName);
        $oVersionStyle->setVarForm("file", $sFileName);

    	// create and output the select box, for params please look class.version.php
    	$sSelectBox = $oVersionStyle->buildSelectBox("style_history", "Style History", "Show History Entry", "idstylehistory");
    	
    	// Generate Form
    	$oForm = new UI_Table_Form("style_display");
    	$oForm->addHeader(i18n("Edit Style"));
    	$oForm ->setWidth("100%");
    	$oForm->setVar("area", "style_history");
    	$oForm->setVar("frame", $frame);
    	$oForm->setVar("idstyle", $sFileName);
    	$oForm->setVar("style_send", 1);
    	
    	
    	// if send form refresh button
    	if ($_POST["idstylehistory"] != "") {
            $sRevision = $_POST["idstylehistory"];
    	} else {
            $sRevision = $oVersionStyle->getLastRevision();
        }
        
        if ($sRevision != '') {
            $sPath = $oVersionStyle->getFilePath() . $sRevision;
            
    		// Read XML Nodes  and get an array 
    		$aNodes = array();
    		$aNodes = $oVersionStyle->initXmlReader($sPath);

    		// Create Textarea and fill it with xml nodes
            if (count($aNodes) > 1) { 
    			//	if choose xml file read value an set it						
    			$sName = $oVersionStyle->getTextBox("stylename", $aNodes["name"], 60);
    			$sDescription = $oVersionStyle->getTextarea("styledesc", $aNodes["desc"], 100, 10);
    			$sCode = $oVersionStyle->getTextarea("stylecode", $aNodes["code"], 100, 30, "IdLaycode");
    		
    		}

        }

    	// Add new Elements of Form
    	$oForm->add(i18n("Name"), $sName);
    	$oForm->add(i18n("Description"), $sDescription);
    	$oForm->add(i18n("Code"), $sCode);
    	$oForm->setActionButton("apply", "images/but_ok.gif", i18n("Copy to current"), "c" /*, "mod_history_takeover"*/); //modified it 
    	$oForm->unsetActionButton("submit");
    	
    	// Render and handle History Area
    	$oPage->setEncoding("utf-8");
    	
    	$oEditAreaOutput = new EditArea('IdLaycode', 'css', substr(strtolower($belang), 0, 2), true, $cfg, !$bInUse);
    	$oPage->addScript('IdLaycode', $oEditAreaOutput->renderScript());

    	if($sSelectBox !="") {
    		$oPage->setContent($sSelectBox . $oForm->render());
    	
    	} else {
    		$notification->displayNotification("warning", i18n("No style history available"));
    	}	
    	$oPage->render();
    	
    }else{
    	if($bDeleteFile) {
    		$notification->displayNotification("warning", i18n("Version history was cleared"));
    	} else {
    		$notification->displayNotification("warning", i18n("No style history available"));	
    	}
    }
}
?>