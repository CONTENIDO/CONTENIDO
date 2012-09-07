<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Layout history.
 * We use SimpleXml to read the xml nodes
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @version    1.0.0
 * @author     Bilal Arslan
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release >= 5.0
 * 
 * {@internal 
 *   created 2008-08-1
 *   $Id: include.lay_history.php 741 2008-08-27 10:51:59Z timo.trautmann $:
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
cInclude("classes", "class.versionLayout.php");

// For update current layout with Revision
cInclude("includes", "functions.lay.php");

// For Editor syntax highlighting
cInclude("external", "edit_area/class.edit_area.php");

$oPage = new cPage;
$oPage->addScript('messageBox', '<script type="text/javascript" src="'.$sess->url('scripts/messageBox.js.php').'"></script>');
$oPage->addScript('messageBoxInit', '<script type="text/javascript">box = new messageBox("", "", "", 0, 0);</script>');

$bDeleteFile = false;

if (!$perm->have_perm_area_action($area, 'lay_history_manage')) {
  $notification->displayNotification("error", i18n("Permission denied"));
  $oPage->render();
} else if (!(int) $client > 0) {
  $oPage->render();
} else if (getEffectiveSetting('versioning', 'activated', 'false') == 'false') {
  $notification->displayNotification("warning", i18n("Versioning is not activated"));
  $oPage->render();
} else {	
    if ($_POST["lay_send"] == true && $_POST["layname"]!="" && $_POST["laycode"] !="" && (int) $idlay > 0) { // save button 
    	$oVersion = new VersionLayout($idlay, $cfg, $cfgClient, $db, $client, $area, $frame);
    	$sLayoutName = $_POST["layname"];
    	$sLayoutCode = $_POST["laycode"];
    	$sLayoutDescription = $_POST["laydesc"];

    //	save and mak new revision
        $oPage->addScript('refresh', $oVersion->renderReloadScript('lay', $idlay, $sess));
    	layEditLayout($idlay, $sLayoutName, $sLayoutDescription, $sLayoutCode);
    	unset($oVersion);
    }
    
    // [action] => history_truncate delete all current modul history
  	if($_POST["action"] == "history_truncate") {
        $oVersion = new VersionLayout($idlay, $cfg, $cfgClient, $db, $client, $area, $frame);
  		$bDeleteFile = $oVersion->deleteFile();
        unset($oVersion);
  	}

    // Init construct with contenido variables, in class.VersionLayout
    $oVersion = new VersionLayout($idlay, $cfg, $cfgClient, $db, $client, $area, $frame);

    // Init Form variables of SelectBox
    $sSelectBox = "";
    $oVersion->setVarForm("area",  $area);
    $oVersion->setVarForm("frame", $frame);
    $oVersion->setVarForm("idlay", $idlay);

    // create and output the select box, for params please look class.version.php
    $sSelectBox = $oVersion->buildSelectBox("mod_history", "Layout History", "Show History Entry", "idlayhistory");

    // Generate Form
    $oForm = new UI_Table_Form("lay_display");
    $oForm->addHeader(i18n("Edit Layout"));
    $oForm ->setWidth("100%");
    $oForm->setVar("area", "lay_history");
    $oForm->setVar("frame", $frame);
    $oForm->setVar("idlay", $idlay);
    $oForm->setVar("lay_send", 1);

    // if send form refresh
    if ($_POST["idlayhistory"] != "") {
        $sRevision = $_POST["idlayhistory"];
    } else {
        $sRevision = $oVersion->getLastRevision();
    }
        
    if ($sRevision != '' && $_POST["action"] != "history_truncate") {
    	// File Path	
        $sPath = $oVersion->getFilePath() . $sRevision;
    	
    	// Read XML Nodes  and get an array 
    	$aNodes = array();
    	$aNodes = $oVersion->initXmlReader($sPath);

    	// Create Textarea and fill it with xml nodes
    	if (count($aNodes) > 1) { 
    		//	if choose xml file read value an set it						
    		$sName = $oVersion->getTextBox("layname", $aNodes["name"], 60);
    		$sDescription = $oVersion->getTextarea("laydesc", $aNodes["desc"], 100, 10);
    		$sCode = $oVersion->getTextarea("laycode", $aNodes["code"], 100, 30, "IdLaycode");
    	
    	}
    }

    // Add new Elements of Form
    $oForm->add(i18n("Name"), $sName);
    $oForm->add(i18n("Description"), $sDescription);
    $oForm->add(i18n("Code"), $sCode);
    $oForm->setActionButton("apply", "images/but_ok.gif", i18n("Copy to current"), "c"/*, "mod_history_takeover"*/); //modified it 
    $oForm->unsetActionButton("submit");

    // Render and handle History Area
    $oEditAreaOutput = new EditArea('IdLaycode', 'php', substr(strtolower($belang), 0, 2), true, $cfg, !$bInUse);
    $oPage->addScript('IdLaycode', $oEditAreaOutput->renderScript());
    
    if($sSelectBox !="") {
    	$oPage->setContent($sSelectBox . $oForm->render());

    } else {
    	if($bDeleteFile){
    		$notification->displayNotification("warning", i18n("Version history was cleared"));
    	} else {
    		$notification->displayNotification("warning", i18n("No layout history available"));	
    	}
    	
    }	
    $oPage->render();
	
	
}

?>