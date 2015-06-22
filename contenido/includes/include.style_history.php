<?php

/**
 * This file contains the backend page for style history.
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

cInclude('includes', 'functions.lay.php');
cInclude('includes', 'functions.file.php');
cInclude('external', 'codemirror/class.codemirror.php');

$readOnly = (getEffectiveSetting("client", "readonly", "false") == "true");
if($readOnly) {
    cRegistry::addWarningMessage(i18n("This area is read only! The administrator disabled edits!"));
}

$sFileName = '';
$sFileName = $_REQUEST['file'];

if ($sFileName == '') {
    $sFileName = $_REQUEST['idstyle']; // Content Type is css
}

$sType = 'css';

$oPage = new cGuiPage('style_history');

if (!$perm->have_perm_area_action($area, 'style_history_manage')) {
    $oPage->displayCriticalError(i18n('Permission denied'));
    $oPage->abortRendering();
    $oPage->render();
} else if (!(int) $client > 0) {
    $oPage->abortRendering();
    $oPage->render();
} else if (getEffectiveSetting('versioning', 'activated', 'false') == 'false') {
    $oPage->displayWarning(i18n('Versioning is not activated'));
    $oPage->abortRendering();
    $oPage->render();
} else {

    $sTypeContent = 'css';

    $fileInfoCollection = new cApiFileInformationCollection();
    $aFileInfo = $fileInfoCollection->getFileInformation($sFileName, $sTypeContent);

    // [action] => history_truncate delete all current history
    if ((!$readOnly) && $_POST['action'] == 'history_truncate') {
        $oVersionStyle = new cVersionFile($aFileInfo['idsfi'], $aFileInfo, $sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);
        $bDeleteFile = $oVersionStyle->deleteFile();
        unset($oVersionStyle);
    }

    if ((!$readOnly) && $_POST['style_send'] == true && $_POST['stylecode'] != '' && $sFileName != '' && $aFileInfo['idsfi'] != '') { // save
                                                                                                                      // button
                                                                                                                      // Get
                                                                                                                      // Post
                                                                                                                      // variables
        $oVersionStyle = new cVersionFile($aFileInfo['idsfi'], $aFileInfo, $sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);

        $sStyleCode = $_POST['stylecode'];
        $sStyleName = $_POST['stylename'];
        $sStyleDesc = $_POST['styledesc'];

        $sPath = $oVersionStyle->getPathFile();

        // Edit File, there is a need for renaming file
        if ($sFileName != $sStyleName) {
            if (cFileHandler::getExtension($sStyleName) != 'css' and strlen(stripslashes(trim($sStyleName))) > 0) {
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
            // make new revision File
            $oVersionStyle->createNewVersion();

            // Update File Information
            $fileInfoCollection = new cApiFileInformationCollection();
            $fileInfoCollection->updateFile($sFileName, $sType, $sStyleDesc, $sStyleName, $aFileInfo['author']);

            $sFileName = $sStyleName;
        }

        unset($oVersionStyle);
    }

    if ($sFileName != '' && $aFileInfo['idsfi'] != '' && ($_POST['action'] != 'history_truncate' || $readOnly)) {
        $oVersionStyle = new cVersionFile($aFileInfo['idsfi'], $aFileInfo, $sFileName, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame);

        // Init Form variables of SelectBox
        $sSelectBox = '';
        $oVersionStyle->setVarForm('area', $area);
        $oVersionStyle->setVarForm('frame', $frame);
        $oVersionStyle->setVarForm('idstyle', $sFileName);
        $oVersionStyle->setVarForm('file', $sFileName);
        // needed - otherwise history can not be deleted!
        $oVersionStyle->setVarForm('action', '');

        // create and output the select box, for params please look
        // class.version.php
        $sSelectBox = $oVersionStyle->buildSelectBox('style_history', 'Style History', i18n('Show history entry'), 'idstylehistory', $readOnly);

        // Generate Form
        $oForm = new cGuiTableForm('style_display');
        $oForm->addHeader(i18n('Edit style'));
        $oForm->setVar('area', 'style_history');
        $oForm->setVar('frame', $frame);
        $oForm->setVar('idstyle', $sFileName);
        $oForm->setVar('style_send', 1);

        // if send form refresh button
        if ($_POST['idstylehistory'] != '') {
            $sRevision = $_POST['idstylehistory'];
        } else {
            $sRevision = $oVersionStyle->getLastRevision();
        }

        if ($sRevision != '') {
            $sPath = $oVersionStyle->getFilePath() . $sRevision;

            // Read XML Nodes and get an array
            $aNodes = array();
            $aNodes = $oVersionStyle->initXmlReader($sPath);

            // Create Textarea and fill it with xml nodes
            if (count($aNodes) > 1) {
                // If choose xml file read value an set it
                $sName = $oVersionStyle->getTextBox('stylename', $aNodes['name'], 60, $readOnly);
                $description = $oVersionStyle->getTextarea('styledesc', (string) $aNodes['desc'], 100, 10, '', $readOnly);
                $sCode = $oVersionStyle->getTextarea('stylecode', (string) $aNodes['code'], 100, 30, 'IdLaycode');
            }
        }

        // Add new Elements of Form
        $oForm->add(i18n('Name'), $sName);
        $oForm->add(i18n('Description'), $description);
        $oForm->add(i18n('Code'), $sCode);
        $oForm->setActionButton('apply', 'images/but_ok' . (($readOnly) ? '_off' : '') . '.gif', i18n('Copy to current'), 'c' /* , 'mod_history_takeover' */); // modified
                                                                                                                            // it
        $oForm->unsetActionButton('submit');

        // Render and handle History Area
        $oPage->setEncoding('utf-8');

        $oCodeMirrorOutput = new CodeMirror('IdLaycode', 'css', substr(strtolower($belang), 0, 2), true, $cfg, !$bInUse);
        if($readOnly) {
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
    $oPage->render();
}
