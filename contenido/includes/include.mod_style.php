<?php
/**
 * This file contains the backend page for managing module style files.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Olaf Niemann, Willi Man
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('external', 'codemirror/class.codemirror.php');
cInclude('includes', 'functions.file.php');

$readOnly = (getEffectiveSetting("client", "readonly", "false") == "true");

if($readOnly) {
    cRegistry::addWarningMessage(i18n('This area is read only! The administrator disabled edits!'));
}

$contenidoModulHandler = new cModuleHandler($idmod);
$sFileType = 'css';

$sActionCreate = 'style_create';
$sActionEdit = 'style_edit';
$sFilename = '';

$tmp_file = $contenidoModulHandler->getCssFileName();
$file = $contenidoModulHandler->getCssFileName();

if (empty($action)) {
    $actionRequest = $sActionEdit;
} else {
    $actionRequest = $action;
}
$page = new cGuiPage('mod_style');

$tpl->reset();
$premCreate = false;

if (!$contenidoModulHandler->existFile('css', $contenidoModulHandler->getCssFileName())) {
    if (!$perm->have_perm_area_action('style', $sActionCreate)) {
        $premCreate = true;
    }
}

if (!$perm->have_perm_area_action('style', $actionRequest) || $premCreate) {
    $page->displayCriticalError(i18n('Permission denied'));
    $page->render();
    return;
}

if (!(int) $client > 0) {
    // If there is no client selected, display empty page
    $page->render();
    return;
}

$path = $contenidoModulHandler->getCssPath(); // $cfgClient[$client]['css']['path'];

// ERROR MESSAGE
if (!$contenidoModulHandler->moduleWriteable('css')) {
    $page->displayCriticalError(i18n('No write permissions in folder css for this module!'));
    $page->render();
    exit();
}

// Make automatic a new css file
$contenidoModulHandler->createModuleFile('css');

if (stripslashes($file)) {
    $sReloadScript = <<<JS
<script type="text/javascript">
(function(Con, $) {
    var frame = Con.getFrame('left_bottom');
    if (frame) {
        frame.location.href = Con.UtilUrl.replaceParams(frame.location.href, {file: '{$file}'});
    }
})(Con, Con.$);
</script>
JS;
} else {
    $sReloadScript = '';
}

$sTempFilename = stripslashes($tmp_file);
$sOrigFileName = $sTempFilename;

if (getFileType($file) != $sFileType && strlen(stripslashes(trim($file))) > 0) {
    $sFilename .= stripslashes($file) . '.' . $sFileType;
} else {
    $sFilename .= stripslashes($file);
}

if (stripslashes($file)) {
    $sReloadScript = <<<JS
<script type="text/javascript">
(function(Con, $) {
    var frame = Con.getFrame('left_bottom');
    if (frame) {
        frame.location.href = Con.UtilUrl.replaceParams(frame.location.href, {file: '{$sFilename}'});
    }
})(Con, Con.$);
</script>
JS;
} else {
    $sReloadScript = '';
}

// Content Type is css
$sTypeContent = 'css';
$fileInfoCollection = new cApiFileInformationCollection();
$aFileInfo = $fileInfoCollection->getFileInformation($sTempFilename, $sTypeContent);

if (!cFileHandler::writeable($path . $sFilename)) {
    $page->displayWarning(i18n("You have no write permissions for this file"));
}

// Create new file
if ((!$readOnly) && $actionRequest == $sActionCreate && $_REQUEST['status'] == 'send') {
    $sTempFilename = $sFilename;
    $ret = cFileHandler::create($path . $sFilename);

    $fileEncoding = getEffectiveSetting('encoding', 'file_encoding', 'UTF-8');

    $tempCode = iconv(cModuleHandler::getEncoding(), $fileEncoding, $_REQUEST['code']);
    cFileHandler::validateFilename($sFilename);
    cFileHandler::write($path . $sFilename, $tempCode);
    $bEdit = cFileHandler::read($path . $sFilename);

    $fileInfoCollection = new cApiFileInformationCollection();
    $fileInfoCollection->updateFile($sFilename, 'css', $_REQUEST['description'], $auth->auth['uid']);

    $urlReload = $sess->url("main.php?area=$area&frame=3&file=$sTempFilename");
    $sReloadScript = <<<JS
<script type="text/javascript">
(function(Con, $) {
    var frame = Con.getFrame('right_top');
    if (frame) {
        frame.location.href = '{$urlReload}';
    }
})(Con, Con.$);
</script>
JS;

    if ($ret && $bEdit) {
        $page->displayInfo(i18n('Created new css file successfully'));
    } else {
        $page->displayInfo(i18n('Could not create a new css file!'));
    }
}

// Edit selected file
if ((!$readOnly) && $actionRequest == $sActionEdit && $_REQUEST['status'] == 'send') {
    if ($sFilename != $sTempFilename) {
        cFileHandler::validateFilename($sFilename);
        if (cFileHandler::rename($path . $sTempFilename, $sFilename)) {
            $sTempFilename = $sFilename;
        } else {
            $notification->displayNotification('error', sprintf(i18n('Can not rename file %s'), $path . $sTempFilename));
            exit();
        }

        $urlReload = $sess->url("main.php?area=$area&frame=3&file=$sTempFilename");
        $sReloadScript = <<<JS
<script type="text/javascript">
(function(Con, $) {
    var frame = Con.getFrame('right_top');
    if (frame) {
        frame.location.href = '{$urlReload}';
    }
})(Con, Con.$);
</script>
JS;
    } else {
        $sTempFilename = $sFilename;
    }

    $fileInfoCollection = new cApiFileInformationCollection();
    $fileInfoCollection->updateFile($sOrigFileName, 'css', $_REQUEST['description'], $sFilename, $auth->auth['uid']);

    $fileEncoding = getEffectiveSetting('encoding', 'file_encoding', 'UTF-8');
    $tempCode = iconv(cModuleHandler::getEncoding(), $fileEncoding, $_REQUEST['code']);
    cFileHandler::validateFilename($sFilename);
    cFileHandler::write($path . $sFilename, $tempCode);
    $bEdit = cFileHandler::read($path . $sFilename);

    if ($sFilename != $sTempFilename && $bEdit) {
        $page->displayInfo(i18n('Renamed and saved changes successfully!'));
    } elseif (!$bEdit) {
        $page->displayError(i18n("Can't save file!"));
    } else {
        $page->displayInfo(i18n('Saved changes successfully!'));
    }
}

// Generate edit form
if (isset($actionRequest)) {

    $sAction = ($bEdit) ? $sActionEdit : $actionRequest;

    $fileEncoding = getEffectiveSetting('encoding', 'file_encoding', 'UTF-8');

    if ($actionRequest == $sActionEdit) {
        $sCode = cFileHandler::read($path . $sFilename);
        if ($sCode === false) {
            exit();
        }
        $sCode = iconv($fileEncoding, cModuleHandler::getEncoding(), $sCode);
    } else {
        // stripslashes is required here in case of creating a new file
        $sCode = stripslashes($_REQUEST['code']);
    }
    $fileInfoCollection = new cApiFileInformationCollection();
    $aFileInfo = $fileInfoCollection->getFileInformation($sTempFilename, 'css');

    $form = new cGuiTableForm('file_editor');
    $form->setTableid('mod_style');
    $form->addHeader(i18n('Edit file'));
    $form->setVar('area', $area);
    $form->setVar('action', $sAction);
    $form->setVar('frame', $frame);
    $form->setVar('status', 'send');
    $form->setVar('tmp_file', $sTempFilename);
    $form->setVar('idmod', $idmod);
    $label = new cHTMLLabel($sFilename, '');

    $code = new cHTMLTextarea('code', conHtmlSpecialChars($sCode), 100, 35, 'code');
    $code->setStyle('font-family: monospace;width: 100%;');
    $code->updateAttributes(array(
        'wrap' => getEffectiveSetting('style_editor', 'wrap', 'off')
    ));

    $form->add(i18n('Name'), $label);
    $form->add(i18n('Code'), $code);


    $oCodeMirror = new CodeMirror('code', 'css', substr(strtolower($belang), 0, 2), true, $cfg);
    if($readOnly) {
        $oCodeMirror->setProperty("readOnly", "true");

        $form->setActionButton('submit', cRegistry::getBackendUrl() . 'images/but_ok_off.gif', i18n('Overwriting files is disabled'), 's');
    }
    $page->setContent($form);
    $page->addScript($oCodeMirror->renderScript());

    // $page->addScript('reload', $sReloadScript);
    $page->render();
}
