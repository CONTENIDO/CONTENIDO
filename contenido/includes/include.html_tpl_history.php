<?php

/**
 * This file contains the backend page for html template history.
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
    $sFileName = $_REQUEST['idhtml_tpl'] ?? '';
}

$sType = 'templates';

$oPage = new cGuiPage('html_tpl_history');

if (!$perm->have_perm_area_action($area, 'htmltpl_history_manage')) {
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

$sTypeContent = 'templates';
$bDeleteFile = false;

$fileInfoCollection = new cApiFileInformationCollection();
$aFileInfo = $fileInfoCollection->getFileInformation($sFileName, $sTypeContent);

$requestAction = $_POST['action'] ?? '';
$requestHtmlTplSend = cSecurity::toInteger($_POST['html_tpl_send'] ?? '0');
$requestHtmlTplCode = $_POST['html_tpl_code'] ?? '';

// [action] => history_truncate delete all current history
if ((!$readOnly) && $requestAction == 'history_truncate') {
    $oVersionHtmlTemp = new cVersionFile($aFileInfo['idsfi'], $aFileInfo, $sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);
    $bDeleteFile = $oVersionHtmlTemp->deleteFile();
    unset($oVersionHtmlTemp);
}

// Save button
if ((!$readOnly) && $requestHtmlTplSend && $requestHtmlTplCode != '' && $sFileName != '' && !empty($aFileInfo['idsfi'])) {
    $oVersionHtmlTemp = new cVersionFile($aFileInfo['idsfi'], $aFileInfo, $sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);

    $sHTMLCode = stripslashes($requestHtmlTplCode);
    $sHTMLName = stripslashes($_POST['html_tpl_name']);
    $sHTMLDesc = stripslashes($_POST['html_tpl_desc']);

    $sPath = $oVersionHtmlTemp->getPathFile();

    // Do we need to rename the file?
    if ($sFileName != $sHTMLName) {
        if (cFileHandler::getExtension($sHTMLName) != 'html' && cString::getStringLength(stripslashes(trim($sHTMLName))) > 0) {
            $sHTMLName = stripslashes($sHTMLName) . '.html';
        }

        cFileHandler::validateFilename($sHTMLName);
        if (!cFileHandler::rename($oVersionHtmlTemp->getPathFile() . $sFileName, $sHTMLName)) {
            $notification->displayNotification('error', sprintf(i18n('Can not rename file %s'), $oVersionHtmlTemp->getPathFile() . $sFileName));
            exit();
        }
        $oPage->addScript($oVersionHtmlTemp->renderReloadScript('htmltpl', $sHTMLName, $sess));
    }

    cFileHandler::validateFilename($sHTMLName);
    cFileHandler::write($sPath . $sHTMLName, $sHTMLCode);
    if (cFileHandler::read($sPath . $sHTMLName)) {
        // Make new revision file
        $oVersionHtmlTemp->createNewVersion();

        // Update file information
        $fileInfoCollection = new cApiFileInformationCollection();
        $fileInfoCollection->updateFile($sFileName, $sType, $sHTMLDesc, $sHTMLName, $aFileInfo['author']);

        $sFileName = $sHTMLName;
    }

    unset($oVersionHtmlTemp);
}

if ($sFileName != '' && !empty($aFileInfo['idsfi']) && ($requestAction != 'history_truncate' || $readOnly)) {
    $oVersionHtmlTemp = new cVersionFile($aFileInfo['idsfi'], $aFileInfo, $sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);

    // Init form variables of select box
    $sSelectBox = '';
    $oVersionHtmlTemp->setVarForm('area', $area);
    $oVersionHtmlTemp->setVarForm('action', '');
    $oVersionHtmlTemp->setVarForm('frame', $frame);
    $oVersionHtmlTemp->setVarForm('idhtml_tpl', $sFileName);
    $oVersionHtmlTemp->setVarForm('file', $sFileName);

    // Create and output the select box, for params please look
    // class.version.php
    $sSelectBox = $oVersionHtmlTemp->buildSelectBox('html_tpl_history', 'HTML Template History', i18n('Show history entry'), 'idhtml_tpl_history', $readOnly);

    // Generate Form
    $oForm = new cGuiTableForm('jscript_display');
    $oForm->addTableClass('col_50');
    $oForm->addHeader(i18n('Edit template'));
    $oForm->setVar('area', $area);
    $oForm->setVar('frame', $frame);
    $oForm->setVar('idhtml_tpl', $sFileName);
    $oForm->setVar('html_tpl_send', 1);

    // Is form refresh button send
    if (!empty($_POST['idhtml_tpl_history'])) {
        $sRevision = $_POST['idhtml_tpl_history'];
    } else {
        $sRevision = $oVersionHtmlTemp->getLastRevision();
    }

    $sName = '';
    $description = '';
    $sCode = '';

    if ($sRevision != '') {
        $sPath = $oVersionHtmlTemp->getFilePath() . $sRevision;

        // Read XML nodes and get an array
        $aNodes = $oVersionHtmlTemp->initXmlReader($sPath);

        // Create textarea and fill it with xml nodes
        if (count($aNodes) > 1) {
            $sName = $oVersionHtmlTemp->getTextBox('html_tpl_name', $aNodes['name'], 60, $readOnly);
            $description = $oVersionHtmlTemp->getTextarea('html_tpl_desc', cSecurity::toString($aNodes['desc']), 100, 10, '', $readOnly);
            $sCode = $oVersionHtmlTemp->getTextarea('html_tpl_code', cSecurity::toString($aNodes['code']), 100, 30, 'IdLaycode');
        }
    }

    // Add new elements of form
    $oForm->add(i18n('Name'), $sName);
    $oForm->add(i18n('Description'), $description);
    $oForm->add(i18n('Code'), $sCode);
    $oForm->setActionButton('apply', 'images/but_ok' . (($readOnly) ? '_off' : '' ) . '.gif', i18n('Copy to current'), 'c' /*, 'mod_history_takeover'*/);
    $oForm->unsetActionButton('submit');

    // Render and handle history area
    $bInUse = $bInUse ?? false;
    $oCodeMirrorOutput = new CodeMirror('IdLaycode', 'php', cString::getPartOfString(cString::toLowerCase($belang), 0, 2), true, $cfg, !$bInUse);
    if ($readOnly) {
        $oCodeMirrorOutput->setProperty("readOnly", "true");
    }
    $oPage->addScript($oCodeMirrorOutput->renderScript());

    if ($sSelectBox != '') {
        $oPage->set('s', 'FORM', $sSelectBox . $oForm->render());
    } else {
        $oPage->displayWarning(i18n('No template history available'));
        $oPage->abortRendering();
    }
} else {
    if ($bDeleteFile) {
        $oPage->displayOk(i18n('Version history was cleared'));
    } else {
        $oPage->displayWarning(i18n('No template history available'));
    }
    $oPage->abortRendering();
}

$oPage->setEncoding('utf-8');
$oPage->render();
