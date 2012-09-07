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
 *   created 2008-08-12
 *   $Id: include.mod_history.php 741 2008-08-27 10:51:59Z timo.trautmann $:
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
cInclude("classes","class.versionModule.php");


// For Editor syntax highlighting
cInclude("external", "edit_area/class.edit_area.php");

// 
cInclude("includes", "functions.mod.php");


if($idmod =="") {
	$idmod = $_REQUEST['idmod'];	
}

$bDeleteFile = false;
$oPage = new cPage;
$oPage->addScript('messageBox', '<script type="text/javascript" src="'.$sess->url('scripts/messageBox.js.php').'"></script>');
$oPage->addScript('messageBoxInit', '<script type="text/javascript">box = new messageBox("", "", "", 0, 0);</script>');

if (!$perm->have_perm_area_action($area, 'mod_history_manage'))
{
  $notification->displayNotification("error", i18n("Permission denied"));
  $oPage->render();
} else if (!(int) $client > 0) {
  $oPage->render();
} else if (getEffectiveSetting('versioning', 'activated', 'false') == 'false') {
  $notification->displayNotification("warning", i18n("Versioning is not activated"));
  $oPage->render();
} else {

    if ($_POST["mod_send"] == true && ($_POST["CodeOut"] !="" || $_POST["CodeIn"] !="") ) { // save button 
    	$oVersion = new VersionModule($idmod, $cfg, $cfgClient, $db, $client, $area, $frame);
    	$sName = $_POST["modname"];
    	$sCodeInput = $_POST["CodeIn"];
    	$sCodeOutput = $_POST["CodeOut"];
    	$sDescription = $_POST["moddesc"];

    //	save and mak new revision
        $oPage->addScript('refresh', $oVersion->renderReloadScript('mod', $idmod, $sess));
    	modEditModule($idmod, $sName, $sDescription, $sCodeInput, $sCodeOutput, $oVersion->sTemplate, $oVersion->sModType);
    	unset($oVersion);
    }
	
	// [action] => history_truncate delete all current history
  	if($_POST["action"] == "history_truncate") {
        $oVersion = new VersionModule($idmod, $cfg, $cfgClient, $db, $client, $area, $frame);
  		$bDeleteFile = $oVersion->deleteFile();
        unset($oVersion);
  	}

    $oVersion = new VersionModule($idmod, $cfg, $cfgClient, $db, $client, $area, $frame);

    // Init Form variables of SelectBox
    $sSelectBox = "";
    $oVersion->setVarForm("area",  $area);
    $oVersion->setVarForm("frame", $frame);
    $oVersion->setVarForm("idmod", $idmod);

    // create and output the select box, for params please look class.version.php
    $sSelectBox = $oVersion->buildSelectBox("mod_history", "Mod History", "Show History Entry", "idmodhistory");

    // Generate Form
    $oForm = new UI_Table_Form("mod_display");
    $oForm->addHeader(i18n("Edit Module"));
    $oForm ->setWidth("100%");
    $oForm->setVar("area", "mod_history");
    $oForm->setVar("frame", $frame);
    $oForm->setVar("idmod", $idmod);
    $oForm->setVar("mod_send", 1);



    // if send form refresh
    if ($_POST["idmodhistory"] != "") {
        $sRevision = $_POST["idmodhistory"];
    } else {
        $sRevision = $oVersion->getLastRevision();
    }
        
    if ($sRevision != '' && $_POST["action"] != "history_truncate") {
    	// File Path	
        $sPath = $oVersion->getFilePath() . $sRevision;
    	
    	// Read XML Nodes  and get an array 
    	$aNodes = array();
    	$aNodes = $oVersion->initXmlReader($sPath);

    	if (count($aNodes) > 1) {
    				
    			//	if choose xml file read value an set it						
    			$sName = $oVersion->getTextBox("modname", $aNodes["name"], 60);
    			$sDescription = $oVersion->getTextarea("moddesc", $aNodes["desc"], 100, 10);
    			$sCodeInput = $oVersion->getTextarea("CodeIn", $aNodes["code_input"], 100, 30, "IdCodeIn");
    			$sCodeOutput = $oVersion->getTextarea("CodeOut", $aNodes["code_output"], 100, 30, "IdCodeOut");
    			
    		
    	}
    } 

    // Add new Elements of Form
    $oForm->add(i18n("Name"), $sName);
    $oForm->add(i18n("Description"), $sDescription);
    $oForm->add(i18n("Code Input"), $sCodeInput);
    $oForm->add(i18n("Code Output"), $sCodeOutput);
    $oForm->setActionButton("apply", "images/but_ok.gif", i18n("Copy to current"), "c"/*, "mod_history_takeover"*/); //modified it 
    $oForm->unsetActionButton("submit");

    // Render and handle History Area

    $oEditAreaIn = new EditArea('IdCodeIn', 'php', substr(strtolower($belang), 0, 2), true, $cfg, !$bInUse);
    $oEditAreaOutput = new EditArea('IdCodeOut', 'php', substr(strtolower($belang), 0, 2), true, $cfg, !$bInUse);
    $oPage->addScript('IdCodeIn', $oEditAreaIn->renderScript());
    $oPage->addScript('IdCodeOut', $oEditAreaOutput->renderScript());

    if($sSelectBox !="") {
    	$oPage->setContent($sSelectBox . $oForm->render());

    } else {
    	if($bDeleteFile){
    		$notification->displayNotification("warning", i18n("Version history was cleared"));
    	} else {
    		$notification->displayNotification("warning", i18n("No module history available"));	
    	}
    	
    
    }	
    $oPage->render();
}
?>