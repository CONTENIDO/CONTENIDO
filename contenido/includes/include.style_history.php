<?php

/**
 * This file contains the backend page for style history.
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

cInclude('includes', 'functions.lay.php');
cInclude('includes', 'functions.file.php');
cInclude('external', 'codemirror/class.codemirror.php');

$readOnly = (getEffectiveSetting("client", "readonly", "false") == "true");
if ($readOnly) {
    cRegistry::addWarningMessage(i18n("This area is read only! The administrator disabled edits!"));
}

$sFileName = $_REQUEST['file'] ?? '';
if ($sFileName == '') {
    $sFileName = $_REQUEST['idstyle'] ?? '';
}

$sType = 'css';

$oPage = new cGuiPage('style_history');

if (!$perm->have_perm_area_action($area, 'style_history_manage')) {
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

// Content Type is css
$sTypeContent = 'css';
$bDeleteFile = false;

$fileInfoCollection = new cApiFileInformationCollection();
$aFileInfo = $fileInfoCollection->getFileInformation($sFileName, $sTypeContent);

$requestAction = $_POST['action'] ?? '';
$requestStyleSend = cSecurity::toInteger($_POST['style_send'] ?? '0');
$requestStyleCode = $_POST['stylecode'] ?? '';

// [action] => history_truncate delete all current history
if ((!$readOnly) && $requestAction == 'history_truncate') {
    $oVersionStyle = new cVersionFile($aFileInfo['idsfi'], $aFileInfo, $sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);
    $bDeleteFile = $oVersionStyle->deleteFile();
    unset($oVersionStyle);
}

// Save button
if ((!$readOnly) && $requestStyleSend && $requestStyleCode != '' && $sFileName != '' && !empty($aFileInfo['idsfi'])) {
    $oVersionStyle = new cVersionFile($aFileInfo['idsfi'], $aFileInfo, $sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);

    $sStyleCode = stripslashes($requestStyleCode);
    $sStyleName = stripslashes($_POST['stylename']);
    $sStyleDesc = stripslashes($_POST['styledesc']);

    $sPath = $oVersionStyle->getPathFile();

    // Do we need to rename the file?
    if ($sFileName != $sStyleName) {
        if (cFileHandler::getExtension($sStyleName) != 'css' && cString::getStringLength(stripslashes(trim($sStyleName))) > 0) {
            $sStyleName = stripslashes($sStyleName) . '.css';
        }

        cFileHandler::validateFilename($sStyleName);
        if (!cFileHandler::rename($oVersionStyle->getPathFile() . $sFileName, $sStyleName)) {
            $notification->displayNotification('error', sprintf(i18n('Can not rename file %s'), $oVersionStyle->getPathFile() . $sFileName));
            exit();
        }
        $oPage->addScript($oVersionStyle->renderReloadScript('style', $sStyleName, $sess));
    }

    cFileHandler::validateFilename($sStyleName);
    cFileHandler::write($sPath . $sStyleName, $sStyleCode);
    if (cFileHandler::read($sPath . $sStyleName)) {
        // Make new revision file
        $oVersionStyle->createNewVersion();

        // Update file information
        $fileInfoCollection = new cApiFileInformationCollection();
        $fileInfoCollection->updateFile($sFileName, $sType, $sStyleDesc, $sStyleName, $aFileInfo['author']);

        $sFileName = $sStyleName;
    }

    unset($oVersionStyle);
}

if ($sFileName != '' && !empty($aFileInfo['idsfi']) && ($requestAction != 'history_truncate' || $readOnly)) {
    $oVersionStyle = new cVersionFile($aFileInfo['idsfi'], $aFileInfo, $sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);

    // Init form variables of select box
    $sSelectBox = '';
    $oVersionStyle->setVarForm('area', $area);
    $oVersionStyle->setVarForm('action', '');
    $oVersionStyle->setVarForm('frame', $frame);
    $oVersionStyle->setVarForm('idstyle', $sFileName);
    $oVersionStyle->setVarForm('file', $sFileName);

    // Create and output the select box, for params please look
    // class.version.php
    $sSelectBox = $oVersionStyle->buildSelectBox('style_history', 'Style History', i18n('Show history entry'), 'idstylehistory', $readOnly);

    // Generate form
    $oForm = new cGuiTableForm('style_display');
    $oForm->addTableClass('col_50');
    $oForm->addHeader(i18n('Edit style'));
    $oForm->setVar('area', 'style_history');
    $oForm->setVar('frame', $frame);
    $oForm->setVar('idstyle', $sFileName);
    $oForm->setVar('style_send', 1);

    // Is form refresh button send
    if (!empty($_POST['idstylehistory'])) {
        $sRevision = $_POST['idstylehistory'];
    } else {
        $sRevision = $oVersionStyle->getLastRevision();
    }

    $sName = '';
    $description = '';
    $sCode = '';

    if ($sRevision != '') {
        $sPath = $oVersionStyle->getFilePath() . $sRevision;

        // Read XML nodes and get an array
        $aNodes = $oVersionStyle->initXmlReader($sPath);

        // Create textarea and fill it with xml nodes
        if (count($aNodes) > 1) {
            $sName = $oVersionStyle->getTextBox('stylename', $aNodes['name'], 60, $readOnly);
            $description = $oVersionStyle->getTextarea('styledesc', cSecurity::toString($aNodes['desc']), 100, 10, '', $readOnly);
            $sCode = $oVersionStyle->getTextarea('stylecode', cSecurity::toString($aNodes['code']), 100, 30, 'IdLaycode');
        }
    }

    // Add new elements of form
    $oForm->add(i18n('Name'), $sName);
    $oForm->add(i18n('Description'), $description);
    $oForm->add(i18n('Code'), $sCode);
    $oForm->setActionButton('apply', 'images/but_ok' . (($readOnly) ? '_off' : '') . '.gif', i18n('Copy to current'), 'c' /* , 'mod_history_takeover' */);
    $oForm->unsetActionButton('submit');

    // Render and handle history area
    $bInUse = $bInUse ?? false;
    $oCodeMirrorOutput = new CodeMirror('IdLaycode', 'css', cString::getPartOfString(cString::toLowerCase($belang), 0, 2), true, $cfg, !$bInUse);
    if ($readOnly) {
        $oCodeMirrorOutput->setProperty("readOnly", "true");
    }
    $oPage->addScript($oCodeMirrorOutput->renderScript());

    if ($sSelectBox != '') {
        $oPage->set('s', 'FORM', $sSelectBox . $oForm->render());
    } else {
        $oPage->displayWarning(i18n('No style history available'));
        $oPage->abortRendering();
    }
} else {
    if ($bDeleteFile) {
        $oPage->displayOk(i18n('Version history was cleared'));
    } else {
        $oPage->displayWarning(i18n('No style history available'));
    }

    $oPage->abortRendering();
}

$oPage->setEncoding('utf-8');
$oPage->render();
