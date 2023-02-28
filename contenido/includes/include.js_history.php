<?php

/**
 * This file contains the backend page for javascript history.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Bilal Arslan
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cPermission $perm
 * @var cSession $sess
 * @var cDb $db
 * @var cGuiNotification $notification
 * @var array $cfg
 * @var array $cfgClient
 * @var string $area
 * @var string $belang
 * @var int $client
 * @var int $frame
 * @var bool $bInUse
 */

cInclude('includes', 'functions.file.php');
cInclude('external', 'codemirror/class.codemirror.php');

$readOnly = (getEffectiveSetting("client", "readonly", "false") == "true");
if ($readOnly) {
    cRegistry::addWarningMessage(i18n("This area is read only! The administrator disabled edits!"));
}

$sFileName = $_REQUEST['file'] ?? '';
if ($sFileName == '') {
    $sFileName = $_REQUEST['idjscript'] ?? '';
}

$sType = 'js';

$oPage = new cGuiPage('js_history');

if (!$perm->have_perm_area_action($area, 'js_history_manage')) {
    $oPage->displayError(i18n('Permission denied'));
    $oPage->abortRendering();
    $oPage->render();
    return;
} elseif (!(int) $client > 0) {
    $oPage->abortRendering();
    $oPage->render();
    return;
} elseif (getEffectiveSetting('versioning', 'activated', 'false') == 'false') {
    $oPage->displayWarning(i18n('Versioning is not activated'));
    $oPage->abortRendering();
    $oPage->render();
    return;
}

$sTypeContent = 'js';
$bDeleteFile = false;

$fileInfoCollection = new cApiFileInformationCollection();
$aFileInfo = $fileInfoCollection->getFileInformation($sFileName, $sTypeContent);

$requestAction = $_POST['action'] ?? '';
$requestJsScriptSend = cSecurity::toInteger($_POST['jscript_send'] ?? '0');
$requestJsScriptCode = $_POST['jscriptcode'] ?? '';

// [action] => history_truncate delete all current history
if ((!$readOnly) && $requestAction == 'history_truncate') {
    $oVersionJScript = new cVersionFile($aFileInfo['idsfi'], $aFileInfo, $sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);
    $bDeleteFile = $oVersionJScript->deleteFile();
    unset($oVersionJScript);
}

// Save button
if ((!$readOnly) && $requestJsScriptSend && $requestJsScriptCode != '' && $sFileName != '' && !empty($aFileInfo['idsfi'])) {
    $oVersionJScript = new cVersionFile($aFileInfo['idsfi'], $aFileInfo, $sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);

    $sJScriptCode = stripslashes($requestJsScriptCode);
    $sJScriptName = stripslashes($_POST['jscriptname']);
    $sJScriptDesc = stripslashes($_POST['jscriptdesc']);

    $sPath = $oVersionJScript->getPathFile();

    // Do we need to rename the file?
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
        // Make new revision file
        $oVersionJScript->createNewVersion();

        // Update file information
        $fileInfoCollection = new cApiFileInformationCollection();
        $fileInfoCollection->updateFile($sFileName, $sType, $sJScriptDesc, $sJScriptName, $aFileInfo['author']);

        $sFileName = $sJScriptName;
    }

    unset($oVersionJScript);
}

if ($sFileName != '' && !empty($aFileInfo['idsfi']) && ($requestAction != 'history_truncate' || $readOnly)) {
    $oVersionJScript = new cVersionFile($aFileInfo['idsfi'], $aFileInfo, $sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);

    // Init form variables of select box
    $sSelectBox = '';
    $oVersionJScript->setVarForm('area', $area);
    $oVersionJScript->setVarForm('action', '');
    $oVersionJScript->setVarForm('frame', $frame);
    $oVersionJScript->setVarForm('idjscript', $sFileName);
    $oVersionJScript->setVarForm('file', $sFileName);

    // Create and output the select box, for params please look
    // class.version.php
    $sSelectBox = $oVersionJScript->buildSelectBox('jscript_history', 'JScript History', i18n('Show history entry'), 'idjscripthistory', $readOnly);

    // Generate form
    $oForm = new cGuiTableForm('jscript_display');
    $oForm->addTableClass('col_50');
    $oForm->addHeader(i18n('Edit JScript'));
    $oForm->setVar('area', $area);
    $oForm->setVar('frame', $frame);
    $oForm->setVar('idjscript', $sFileName);
    $oForm->setVar('jscript_send', 1);

    // Is form refresh button send
    if (!empty($_POST['idjscripthistory'])) {
        $sRevision = $_POST['idjscripthistory'];
    } else {
        $sRevision = $oVersionJScript->getLastRevision();
    }

    $sName = '';
    $description = '';
    $sCode = '';

    if ($sRevision != '') {
        $sPath = $oVersionJScript->getFilePath() . $sRevision;

        // Read XML nodes and get an array
        $aNodes = $oVersionJScript->initXmlReader($sPath);

        // Create textarea and fill it with xml nodes
        if (count($aNodes) > 1) {
            $sName = $oVersionJScript->getTextBox('jscriptname', $aNodes['name'], 60, $readOnly);
            $description = $oVersionJScript->getTextarea('jscriptdesc', cSecurity::toString($aNodes['desc']), 100, 10, '', $readOnly);
            $sCode = $oVersionJScript->getTextarea('jscriptcode', cSecurity::toString($aNodes['code']), 100, 30, 'IdLaycode');
        }
    }

    // Add new elements of form
    $oForm->add(i18n('Name'), $sName);
    $oForm->add(i18n('Description'), $description);
    $oForm->add(i18n('Code'), $sCode);
    $oForm->setActionButton('apply', 'images/but_ok' . (($readOnly) ? '_off' : '') . '.gif', i18n('Copy to current'), 'c' /*, 'mod_history_takeover'*/);
    $oForm->unsetActionButton('submit');

    // Render and handle history area
    $bInUse = $bInUse ?? false;
    $oCodeMirrorOutput = new CodeMirror('IdLaycode', 'js', cString::getPartOfString(cString::toLowerCase($belang), 0, 2), true, $cfg, !$bInUse);
    if ($readOnly) {
        $oCodeMirrorOutput->setProperty("readOnly", "true");
    }
    $oPage->addScript($oCodeMirrorOutput->renderScript());

    if ($sSelectBox != '') {
        $oPage->set('s', 'FORM', $sSelectBox . $oForm->render());
    } else {
        $oPage->displayWarning(i18n('No jscript history available'));
        $oPage->abortRendering();
    }
} else {
    if ($bDeleteFile) {
        $oPage->displayOk(i18n('Version history was cleared'));
    } else {
        $oPage->displayWarning(i18n('No jscript history available'));
    }
    $oPage->abortRendering();
}

$oPage->setEncoding('utf-8');
$oPage->render();
