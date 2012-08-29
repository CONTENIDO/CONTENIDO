<?php
/**
 * Project:
 * CONTENIDO Content Management System
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
 * @since      file available since CONTENIDO release >= 5.0
 *
 * {@internal
 *   created 2008-08-05
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


// For read Fileinformation an get the id of current File
cInclude("includes", "functions.file.php");

// For Editor syntax highlighting
cInclude("external", "codemirror/class.codemirror.php");

$sFileName = "";
$sFileName = $_REQUEST['file'];

$sType = "js";

if($sFileName == ""){
    $sFileName = $_REQUEST['idjscript'];
}

$oPage = new cGuiPage("js_history");

if (!$perm->have_perm_area_action($area, 'js_history_manage'))
{
  $oPage->displayCriticalError(i18n("Permission denied"));
  $oPage->render();
} else if (!(int) $client > 0) {
  $oPage->render();
} else if (getEffectiveSetting('versioning', 'activated', 'false') == 'false') {
  $oPage->displayWarning(i18n("Versioning is not activated"));
  $oPage->render();
} else {

    // Content Type is css
    $sTypeContent = "js";

    $aFileInfo = getFileInformation ($client, $sFileName , $sTypeContent, $db);

       // [action] => history_truncate delete all current history
      if($_POST["action"] == "history_truncate") {
        $oVersionJScript = new cVersionFile($aFileInfo["idsfi"], $aFileInfo, $sFileName ,$sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);
           $bDeleteFile = $oVersionJScript->deleteFile();
        unset($oVersionJScript);
      }

    if ($_POST["jscript_send"] == true && $_POST["jscriptcode"] !="" && $sFileName != "" && $aFileInfo["idsfi"]!="") { // save button
            $oVersionJScript = new cVersionFile($aFileInfo["idsfi"], $aFileInfo, $sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);

        //    Get Post variables
            $sJScriptCode = $_POST["jscriptcode"];
            $sJScriptName = $_POST["jscriptname"];
            $sJScriptDesc = $_POST["jscriptdesc"];

        //    Edit File
           $sPath = $oVersionJScript->getPathFile();

            //        There is a need for renaming file
            if($sFileName != $sJScriptName){
                if (getFileType($sJScriptName) != 'js' AND strlen(stripslashes(trim($sJScriptName))) > 0) {
                    $sJScriptName = stripslashes($sJScriptName).".js";
                }

                cFileHandler::validateFilename($sJScriptName);
                if (!cFileHandler::rename($oVersionJScript->getPathFile() . $sFileName, $sJScriptName)) {
                    $notification->displayNotification("error", sprintf(i18n("Can not rename file %s"), $oVersionJScript->getPathFile() . $sFileName));
                    exit;
                }
                $oPage->addScript("reload", $oVersionJScript->renderReloadScript('js', $sJScriptName, $sess));
            }

            cFileHandler::validateFilename($sJScriptName);
            cFileHandler::write($sPath . $sJScriptName, $sJScriptCode);
            if(cFileHandler::read($sPath . $sJScriptName)) {
        //        make new revision File
                $oVersionJScript->createNewVersion();

        //         Update File Information
                updateFileInformation($client, $sFileName, $sType, $aFileInfo["author"], $sJScriptDesc, $db, $sJScriptName);
                $sFileName = $sJScriptName;
            }

            unset($oVersionJScript);
    }

    if($sFileName != "" && $aFileInfo["idsfi"]!=""  && $_POST["action"] != "history_truncate") {
        $oVersionJScript = new cVersionFile($aFileInfo["idsfi"], $aFileInfo,$sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);


        // Init Form variables of SelectBox
        $sSelectBox = "";
        $oVersionJScript->setVarForm("area",  $area);
        $oVersionJScript->setVarForm("frame", $frame);
        $oVersionJScript->setVarForm("idjscript", $sFileName);
        $oVersionJScript->setVarForm("file", $sFileName);
        // needed - otherwise history can not be deleted!
        $oVersionJScript->setVarForm("action", '');

        // create and output the select box, for params please look class.version.php
        $sSelectBox = $oVersionJScript->buildSelectBox("jscript_history", "JScript History", i18n("Show history entry"), "idjscripthistory");

        // Generate Form
        $oForm = new cGuiTableForm("jscript_display");
        $oForm->addHeader(i18n("Edit JScript"));
        $oForm->setVar("area", $area);
        $oForm->setVar("frame", $frame);
        $oForm->setVar("idjscript", $sFileName);
        $oForm->setVar("jscript_send", 1);


        // if send form refresh button
        if ($_POST["idjscripthistory"] != "") {
            $sRevision = $_POST["idjscripthistory"];
        } else {
            $sRevision = $oVersionJScript->getLastRevision();
        }

        if ($sRevision != '') {
            $sPath = $oVersionJScript->getFilePath() . $sRevision;

            // Read XML Nodes  and get an array
            $aNodes = array();
            $aNodes = $oVersionJScript->initXmlReader($sPath);

            if (count($aNodes) > 1) {
                $sName = $oVersionJScript->getTextBox("jscriptname", $aNodes["name"], 60);
                $sDescription = $oVersionJScript->getTextarea("jscriptdesc",  $aNodes["desc"], 100, 10);
                $sCode = $oVersionJScript->getTextarea("jscriptcode", $aNodes["code"], 100, 30, "IdLaycode");
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

        $oCodeMirrorOutput = new CodeMirror('IdLaycode', 'js', substr(strtolower($belang), 0, 2), true, $cfg, !$bInUse);
        $oPage->addScript($oCodeMirrorOutput->renderScript());

        if($sSelectBox !="") {
            $oPage->set("s", "FORM", $sSelectBox . $oForm->render());

        } else {
            $oPage->displayWarning(i18n("No jscript history available"));
            $oPage->abortRendering();
        }
        $oPage->render();

    } else {
        if($bDeleteFile) {
            $notification->displayNotification("warning", i18n("Version history was cleared"));
        } else {
            $notification->displayNotification("warning", i18n("No style history available"));
        }
    }
}
?>