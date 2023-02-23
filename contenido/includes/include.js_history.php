<?php

/**
 * This file contains the backend page for javascript history.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Bilal Arslan
 * @author           Timo Trautmann
 * @copyright        four for business AG <www.4fb.de>
 * @license          https://www.contenido.org/license/LIZENZ.txt
 * @link             https://www.4fb.de
 * @link             https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// For read Fileinformation an get the id of current File
cInclude('includes', 'functions.file.php');

// For Editor syntax highlighting
cInclude('external', 'codemirror/class.codemirror.php');

$sFileName = '';
$sFileName = $_REQUEST['file'];

$readOnly = (getEffectiveSetting("client", "readonly", "false") == "true");
if($readOnly) {
    cRegistry::addWarningMessage(i18n("This area is read only! The administrator disabled edits!"));
}

$sType = 'js';

if ($sFileName == '') {
    $sFileName = $_REQUEST['idjscript'];
}

$oPage = new cGuiPage('js_history');

if (!$perm->have_perm_area_action($area, 'js_history_manage')) {
    $oPage->displayCriticalError(i18n('Permission denied'));
    $oPage->abortRendering();
    $oPage->render();
} elseif (!(int) $client > 0) {
    $oPage->abortRendering();
    $oPage->render();
} elseif (getEffectiveSetting('versioning', 'activated', 'false') == 'false') {
    $oPage->displayWarning(i18n('Versioning is not activated'));
    $oPage->abortRendering();
    $oPage->render();
} else {

    // Content Type is css
    $sTypeContent = 'js';

    $fileInfoCollection = new cApiFileInformationCollection();
    $aFileInfo = $fileInfoCollection->getFileInformation($sFileName, $sTypeContent);

    // [action] => history_truncate delete all current history
    if ((!$readOnly) && $_POST['action'] == 'history_truncate') {
        $oVersionJScript = new cVersionFile($aFileInfo['idsfi'], $aFileInfo, $sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);
        $bDeleteFile = $oVersionJScript->deleteFile();
        unset($oVersionJScript);
    }

    if ((!$readOnly) && $_POST['jscript_send'] == true && $_POST['jscriptcode'] != '' && $sFileName != '' && $aFileInfo['idsfi'] != '') { // save
                                                                                                                       // button
        $oVersionJScript = new cVersionFile($aFileInfo['idsfi'], $aFileInfo, $sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);

        // Get Post variables
        $sJScriptCode = $_POST['jscriptcode'];
        $sJScriptName = $_POST['jscriptname'];
        $sJScriptDesc = $_POST['jscriptdesc'];

        // Edit File
        $sPath = $oVersionJScript->getPathFile();

        // There is a need for renaming file
        if ($sFileName != $sJScriptName) {
            if (cFileHandler::getExtension($sJScriptName) != 'js' && cString::getStringLength(stripslashes(trim($sJScriptName))) > 0) {
                $sJScriptName = stripslashes($sJScriptName) . '.js';
            }

            cFileHandler::validateFilename($sJScriptName);
            if (!cFileHandler::rename($oVersionJScript->getPathFile() . $sFileName, $sJScriptName)) {
                $notification->displayNotification('error', sprintf(i18n('Can not rename file %s'), $oVersionJScript->getPathFile() . $sFileName));
                exit();
            }
            $oPage->addScript($oVersionJScript->renderReloadScript('js', $sJScriptName, $sess));
        }

        cFileHandler::validateFilename($sJScriptName);
        cFileHandler::write($sPath . $sJScriptName, $sJScriptCode);
        if (cFileHandler::read($sPath . $sJScriptName)) {
            // make new revision File
            $oVersionJScript->createNewVersion();

            // Update File Information
            $fileInfoCollection = new cApiFileInformationCollection();
            $fileInfoCollection->updateFile($sFileName, $sType, $sJScriptDesc, $sJScriptName, $aFileInfo['author']);

            $sFileName = $sJScriptName;
        }

        unset($oVersionJScript);
    }

    if ($sFileName != '' && $aFileInfo['idsfi'] != '' && ($_POST['action'] != 'history_truncate' || $readOnly)) {
        $oVersionJScript = new cVersionFile($aFileInfo['idsfi'], $aFileInfo, $sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);

        // Init Form variables of SelectBox
        $sSelectBox = '';
        $oVersionJScript->setVarForm('area', $area);
        $oVersionJScript->setVarForm('frame', $frame);
        $oVersionJScript->setVarForm('idjscript', $sFileName);
        $oVersionJScript->setVarForm('file', $sFileName);
        // needed - otherwise history can not be deleted!
        $oVersionJScript->setVarForm('action', '');

        // create and output the select box, for params please look
        // class.version.php
        $sSelectBox = $oVersionJScript->buildSelectBox('jscript_history', 'JScript History', i18n('Show history entry'), 'idjscripthistory', $readOnly);

        // Generate Form
        $oForm = new cGuiTableForm('jscript_display');
        $oForm->addHeader(i18n('Edit JScript'));
        $oForm->setVar('area', $area);
        $oForm->setVar('frame', $frame);
        $oForm->setVar('idjscript', $sFileName);
        $oForm->setVar('jscript_send', 1);

        // if send form refresh button
        if ($_POST['idjscripthistory'] != '') {
            $sRevision = $_POST['idjscripthistory'];
        } else {
            $sRevision = $oVersionJScript->getLastRevision();
        }

        if ($sRevision != '') {
            $sPath = $oVersionJScript->getFilePath() . $sRevision;

            // Read XML Nodes and get an array
            $aNodes = $oVersionJScript->initXmlReader($sPath);

            if (count($aNodes) > 1) {
                $sName = $oVersionJScript->getTextBox('jscriptname', $aNodes['name'], 60, $readOnly);
                $description = $oVersionJScript->getTextarea('jscriptdesc', (string) $aNodes['desc'], 100, 10, '', $readOnly);
                $sCode = $oVersionJScript->getTextarea('jscriptcode', (string) $aNodes['code'], 100, 30, 'IdLaycode');
            }
        }

        // Add new Elements of Form
        $oForm->add(i18n('Name'), $sName);
        $oForm->add(i18n('Description'), $description);
        $oForm->add(i18n('Code'), $sCode);
        $oForm->setActionButton('apply', 'images/but_ok' . (($readOnly) ? '_off' : '') . '.gif', i18n('Copy to current'), 'c'/*, 'mod_history_takeover'*/); // modified
                                                                                                                         // it
        $oForm->unsetActionButton('submit');

        // Render and handle History Area
        $oPage->setEncoding('utf-8');

        $oCodeMirrorOutput = new CodeMirror('IdLaycode', 'js', cString::getPartOfString(cString::toLowerCase($belang), 0, 2), true, $cfg, !$bInUse);
        if($readOnly) {
            $oCodeMirrorOutput->setProperty("readOnly", "true");
        }
        $oPage->addScript($oCodeMirrorOutput->renderScript());

        if ($sSelectBox != '') {
            $oPage->set('s', 'FORM', $sSelectBox . $oForm->render());
        } else {
            $oPage->displayWarning(i18n('No jscript history available'));
            $oPage->abortRendering();
        }
        $oPage->render();
    } else {
        if ($bDeleteFile) {
            $oPage->displayOk(i18n('Version history was cleared'));
        } else {
            $oPage->displayWarning(i18n('No jscript history available'));
        }
        $oPage->setEncoding('utf-8');
        $oPage->abortRendering();
        $oPage->render();
    }
}
