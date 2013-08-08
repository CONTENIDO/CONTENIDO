<?php
/**
 * This file contains the backend page for module history.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Bilal Arslan, Timo Trautmann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('external', 'codemirror/class.codemirror.php');
cInclude('includes', 'functions.mod.php');


if ($idmod == '') {
    $idmod = $_REQUEST['idmod'];
}

$bDeleteFile = false;
$oPage = new cGuiPage('mod_history');

if (!$perm->have_perm_area_action($area, 'mod_history_manage')) {
    $oPage->displayError(i18n("Permission denied"));
    $oPage->abortRendering();
    $oPage->render();
    return;
} elseif (!(int) $client > 0) {
	$oPage->abortRendering();
    $oPage->render();
    return;
} elseif (getEffectiveSetting('versioning', 'activated', 'false') == 'false') {
    $oPage->displayWarning(i18n("Versioning is not activated"));
    $oPage->abortRendering();
    $oPage->render();
    return;
}

if ($_POST["mod_send"] == true && ($_POST["CodeOut"] != "" || $_POST["CodeIn"] != "")) { // save button
    $oVersion = new cVersionModule($idmod, $cfg, $cfgClient, $db, $client, $area, $frame);
    $sName = $_POST["modname"];
    $sCodeInput = $_POST["CodeIn"];
    $sCodeOutput = $_POST["CodeOut"];
    $description = $_POST["moddesc"];

    //    save and mak new revision
    $oPage->addScript($oVersion->renderReloadScript('mod', $idmod, $sess));
    modEditModule($idmod, $sName, $description, $sCodeInput, $sCodeOutput, $oVersion->sTemplate, $oVersion->sModType);
    unset($oVersion);
}

// [action] => history_truncate delete all current history
if ($_POST["action"] == "history_truncate") {
    $oVersion = new cVersionModule($idmod, $cfg, $cfgClient, $db, $client, $area, $frame);
    $bDeleteFile = $oVersion->deleteFile();
    unset($oVersion);
}

$oVersion = new cVersionModule($idmod, $cfg, $cfgClient, $db, $client, $area, $frame);

// Init Form variables of SelectBox
$sSelectBox = "";
$oVersion->setVarForm("area", $area);
$oVersion->setVarForm("frame", $frame);
$oVersion->setVarForm("idmod", $idmod);
// needed - otherwise history can not be deleted!
$oVersion->setVarForm("action", '');

// create and output the select box, for params please look class.version.php
$sSelectBox = $oVersion->buildSelectBox("mod_history", i18n("Module History"), i18n("Show history entry"), "idmodhistory");

// Generate Form
$oForm = new cGuiTableForm("mod_display");
$oForm->setTableid('mod_history');
$oForm->addHeader(i18n("Edit module"));
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

        //    if choose xml file read value an set it
        $sName = $oVersion->getTextBox("modname", cString::stripSlashes(conHtmlentities(conHtmlSpecialChars($aNodes["name"]))), 60);
        $description = $oVersion->getTextarea("moddesc", cString::stripSlashes(conHtmlSpecialChars($aNodes["desc"])), 100, 10);
        $sCodeInput = $oVersion->getTextarea("CodeIn", $aNodes["code_input"], 100, 30, "IdCodeIn");
        $sCodeOutput = $oVersion->getTextarea("CodeOut", $aNodes["code_output"], 100, 30, "IdCodeOut");
    }
}

if ($sSelectBox != "") {
    // Add new Elements of Form
    $oForm->add(i18n("Name"), $sName);
    $oForm->add(i18n("Description"), $description);
    $oForm->add(i18n("Code input"), $sCodeInput);
    $oForm->add(i18n("Code output"), $sCodeOutput);
    $oForm->setActionButton("apply", "images/but_ok.gif", i18n("Copy to current"), "c"/* , "mod_history_takeover" */); //modified it
    $oForm->unsetActionButton("submit");

    // Render and handle History Area
    $oCodeMirrorIn = new CodeMirror('IdCodeIn', 'php', substr(strtolower($belang), 0, 2), true, $cfg, !$bInUse);
    $oCodeMirrorOutput = new CodeMirror('IdCodeOut', 'php', substr(strtolower($belang), 0, 2), false, $cfg, !$bInUse);
    $oPage->addScript($oCodeMirrorIn->renderScript());
    $oPage->addScript($oCodeMirrorOutput->renderScript());

    $oPage->set("s", "FORM", $sSelectBox . $oForm->render());
} else {
    if ($bDeleteFile) {
        $oPage->displayWarning(i18n("Version history was cleared"));
    } else {
        $oPage->displayWarning(i18n("No module history available"));
    }
    
    $oPage->abortRendering();
}

$oPage->render();
