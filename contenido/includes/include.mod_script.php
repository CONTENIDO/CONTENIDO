<?php
/**
 * This file contains the backend page for managing module script files.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Willi Man
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('external', 'codemirror/class.codemirror.php');
cInclude('includes', 'functions.file.php');
$contenidoModulHandler = new cModuleHandler($idmod);

$readOnly = (getEffectiveSetting("client", "readonly", "false") == "true");

if($readOnly) {
    cRegistry::addWarningMessage(i18n('This area is read only! The administrator disabled edits!'));
}

$sFileType = 'js';

$sActionCreate = 'js_create';
$sActionEdit = 'js_edit';

$file = $contenidoModulHandler->getJsFileName();
$tmpFile = $contenidoModulHandler->getJsFileName();
$sFilename = '';

if (empty($action)) {
    $actionRequest = $sActionEdit;
} else {
    $actionRequest = $action;
}

$premCreate = false;

if (!$contenidoModulHandler->existFile('js', $contenidoModulHandler->getJsFileName())) {
    if (!$perm->have_perm_area_action('js', $sActionCreate)) {
        $premCreate = true;
    }
}

$page = new cGuiPage("mod_script");

$tpl->reset();

if (!$perm->have_perm_area_action('js', $actionRequest) || $premCreate) {
    $notification->displayNotification('error', i18n('Permission denied'));
    return;
}

if (!(int) $client > 0) {
    // If there is no client selected, display empty page
    $page->render();
    return;
}


$path = $contenidoModulHandler->getJsPath(); // $cfgClient[$client]['js']['path'];
// Make automatic a new js file
$contenidoModulHandler->createModuleFile('js');

// ERROR MESSAGE
if (!$contenidoModulHandler->moduleWriteable('js')) {
	$page->displayCriticalError(i18n('No write permissions in folder js for this module!'));
	$page->render();
	exit();
}

$sTempFilename = stripslashes($tmpFile);
$sOrigFileName = $sTempFilename;

if (getFileType($file) != $sFileType && strlen(stripslashes(trim($file))) > 0) {
    $sFilename .= stripslashes($file) . '.' . $sFileType;
} else {
    $sFilename .= stripslashes($file);
}

if (stripslashes($file)) {
    $page->reloadFrame('left_bottom', array(
    	"file" => $sFilename
    ));
}

if (!cFileHandler::writeable($path . $sFilename)) {
    $page->displayWarning(i18n("You have no write permissions for this file"));
}

$fileEncoding = getEffectiveSetting('encoding', 'file_encoding', 'UTF-8');

// Create new file
if ((!$readOnly) && $actionRequest == $sActionCreate && $_REQUEST['status'] == 'send') {
    $sTempFilename = $sFilename;
    $ret = cFileHandler::create($path . $sFilename);
    $tempCode = iconv(cModuleHandler::getEncoding(), $fileEncoding, $_REQUEST['code']);
    cFileHandler::validateFilename($sFilename);
    cFileHandler::write($path . $sFilename, $tempCode);
    $bEdit = cFileHandler::read($path . $sFilename);

    $urlReload = $sess->url("main.php?area=$area&frame=3&file=$sTempFilename");
    $page->reloadFrame('right_top', $urlReload);

    // Show message for user
    if ($ret == true) {
        $page->displayInfo(i18n('Created new javascript file successfully'));
    } else {
        $page->displayError(i18n('Could not create a new javascript file!'));
    }
}

// Edit selected file
if ((!$readOnly) && $actionRequest == $sActionEdit && $_REQUEST['status'] == 'send') {

    if ($sFilename != $sTempFilename) {
        cFileHandler::validateFilename($sFilename);
        if (cFileHandler::rename($path . $sTempFilename, $sFilename)) {
            $sTempFilename = $sFilename;
        } else {
            $notification->displayNotification("error", sprintf(i18n("Can not rename file %s"), $path . $sTempFilename));
            exit;
        }

        $urlReload = $sess->url("main.php?area=$area&frame=3&file=$sTempFilename");
        $page->reloadFrame('right_top', $urlReload);
    } else {
        $sTempFilename = $sFilename;
    }

    $fileEncoding = getEffectiveSetting('encoding', 'file_encoding', 'UTF-8');
    $tempCode = iconv(cModuleHandler::getEncoding(), $fileEncoding, $_REQUEST['code']);
    cFileHandler::validateFilename($sFilename);
    cFileHandler::write($path . $sFilename, $tempCode);
    $bEdit = cFileHandler::read($path . $sFilename);

    // Show message for user
    if ($sFilename != $sTempFilename) {
        $page->displayInfo(i18n('Renamed and saved changes successfully!'));
    } else {
        $page->displayInfo(i18n('Saved changes successfully!'));
    }
}

// Generate edit form
if (isset($actionRequest)) {
    $fileEncoding = getEffectiveSetting('encoding', 'file_encoding', 'UTF-8');
    $sAction = ($bEdit) ? $sActionEdit : $actionRequest;

    if ($actionRequest == $sActionEdit) {
        $sCode = cFileHandler::read($path . $sFilename);
        if ($sCode === false) {
            exit;
        }
        $sCode = iconv($fileEncoding, cModuleHandler::getEncoding(), $sCode);
    } else {
        $sCode = stripslashes($_REQUEST['code']); # stripslashes is required here in case of creating a new file
    }

    $form = new cGuiTableForm('file_editor');
    $form->setTableid('mod_javascript');
    $form->addHeader(i18n('Edit file'));
    $form->setVar('area', $area);
    $form->setVar('action', $sAction);
    $form->setVar('frame', $frame);
    $form->setVar('status', 'send');
    $form->setVar('tmp_file', $sTempFilename);
    $form->setVar('idmod', $idmod);
    $tb_name = new cHTMLLabel($sFilename, ''); // new cHTMLTextbox('file', $sFilename, 60);
    $ta_code = new cHTMLTextarea('code', conHtmlSpecialChars($sCode), 100, 35, 'code');
    //$descr     = new cHTMLTextarea('description', conHtmlSpecialChars($aFileInfo['description']), 100, 5);

    $ta_code->setStyle('font-family:monospace;width:100%;');
    //$descr->setStyle('font-family:monospace;width:100%;');
    $ta_code->updateAttributes(array('wrap' => getEffectiveSetting('script_editor', 'wrap', 'off')));

    $form->add(i18n('Name'), $tb_name);
    $form->add(i18n('Code'), $ta_code);

    $oCodeMirror = new CodeMirror('code', 'js', substr(strtolower($belang), 0, 2), true, $cfg);
    if($readOnly) {
        $oCodeMirror->setProperty("readOnly", "true");

        $form->setActionButton('submit', cRegistry::getBackendUrl() . 'images/but_ok_off.gif', i18n('Overwriting files is disabled'), 's');
    }

    $page->setContent(array($form));
    $page->addScript($oCodeMirror->renderScript());

    //$page->addScript('reload', $sReloadScript);
    $page->render();
}

?>