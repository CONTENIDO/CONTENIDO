<?php
/**
 * This file contains the backend page for layout history.
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

// For update current layout with Revision
cInclude("includes", "functions.lay.php");

// For Editor syntax highlighting
cInclude("external", "codemirror/class.codemirror.php");

$oPage = new cGuiPage("lay_history");

$bDeleteFile = false;

if (!$perm->have_perm_area_action($area, 'lay_history_manage')) {
    $oPage->displayError(i18n("Permission denied"));
    $oPage->abortRendering();
    $oPage->render();
} else if (!(int) $client > 0) {
	$oPage->abortRendering();
    $oPage->render();
} else if (getEffectiveSetting('versioning', 'activated', 'false') == 'false') {
    $oPage->displayWarning(i18n("Versioning is not activated"));
    $oPage->abortRendering();
    $oPage->render();
} else {
    if ($_POST["lay_send"] == true && $_POST["layname"] != "" && $_POST["laycode"] != "" && (int) $idlay > 0) { // save
                                                                                                             // button
        $oVersion = new cVersionLayout($idlay, $cfg, $cfgClient, $db, $client, $area, $frame);
        $sLayoutName = $_POST["layname"];
        $sLayoutCode = $_POST["laycode"];
        $sLayoutDescription = $_POST["laydesc"];

        // save and mak new revision
        $oPage->addScript($oVersion->renderReloadScript('lay', $idlay, $sess));
        layEditLayout($idlay, $sLayoutName, $sLayoutDescription, $sLayoutCode);
        unset($oVersion);
    }

    // [action] => history_truncate delete all current modul history
    if ($_POST["action"] == "history_truncate") {
        $oVersion = new cVersionLayout($idlay, $cfg, $cfgClient, $db, $client, $area, $frame);
        $bDeleteFile = $oVersion->deleteFile();
        unset($oVersion);
    }

    // Init construct with CONTENIDO variables, in class.VersionLayout
    $oVersion = new cVersionLayout($idlay, $cfg, $cfgClient, $db, $client, $area, $frame);

    // Init Form variables of SelectBox
    $sSelectBox = "";
    $oVersion->setVarForm("area", $area);
    $oVersion->setVarForm("frame", $frame);
    $oVersion->setVarForm("idlay", $idlay);
    // needed - otherwise history can not be deleted!
    $oVersion->setVarForm("action", '');

    // create and output the select box, for params please look
    // class.version.php
    $sSelectBox = $oVersion->buildSelectBox("mod_history", "Layout History", i18n("Show history entry"), "idlayhistory");

    // Generate Form
    $oForm = new cGuiTableForm("lay_display");
    $oForm->addHeader(i18n("Edit Layout"));
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

        // Read XML Nodes and get an array
        $aNodes = array();
        $aNodes = $oVersion->initXmlReader($sPath);

        // Create Textarea and fill it with xml nodes
        if (count($aNodes) > 1) {
            // if choose xml file read value an set it
            $sName = $oVersion->getTextBox("layname", cString::stripSlashes(conHtmlentities(conHtmlSpecialChars($aNodes["name"]))), 60);
            $description = $oVersion->getTextarea("laydesc", cString::stripSlashes(conHtmlSpecialChars($aNodes["desc"])), 100, 10);
            $sCode = $oVersion->getTextarea("laycode", conHtmlSpecialChars($aNodes["code"]), 100, 30, "IdLaycode");
        }
    }

    // Add new Elements of Form
    $oForm->add(i18n("Name"), $sName);
    $oForm->add(i18n("Description"), $description);
    $oForm->add(i18n("Code"), $sCode);
    $oForm->setActionButton("apply", "images/but_ok.gif", i18n("Copy to current"), "c"/*, "mod_history_takeover"*/); // modified
                                                                                                                     // it
    $oForm->unsetActionButton("submit");

    // Render and handle History Area
    $oCodeMirrorOutput = new CodeMirror('IdLaycode', 'php', substr(strtolower($belang), 0, 2), true, $cfg, !$bInUse);
    $oPage->addScript($oCodeMirrorOutput->renderScript());

    if ($sSelectBox != "") {
        $div = new cHTMLDiv();
        $div->setContent($sSelectBox . "<br>");
        $oPage->setContent(array(
                $div,
                $oForm
        ));
    } else {
        if ($bDeleteFile) {
            $oPage->displayInfo(i18n("Version history was cleared"));
        } else {
            $oPage->displayWarning(i18n("No layout history available"));
        }
        
        $oPage->abortRendering();
    }
    $oPage->render();
}

?>