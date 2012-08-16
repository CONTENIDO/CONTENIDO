<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Edit file
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.2
 * @author     Olaf Niemann, Willi Mann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2003-04-20
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('external', 'codemirror/class.codemirror.php');
cInclude('includes', 'functions.file.php');

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

$page = new cGuiPage("mod_style");

$tpl->reset();
$premCreate = false;

if (!$contenidoModulHandler->existFile('css', $contenidoModulHandler->getCssFileName())) {
    if (!$perm->have_perm_area_action('style', $sActionCreate)) {
        $premCreate = true;
    }
}


if (!$perm->have_perm_area_action('style', $actionRequest)|| $premCreate) {
    $page->displayCriticalError(i18n('Permission denied'));
    $page->render();
    return;
}

if (!(int) $client > 0) {
    // If there is no client selected, display empty page
    $page->render();
    return;
}

$path = $contenidoModulHandler->getCssPath();// $cfgClient[$client]['css']['path'];
// Make automatic a new css file
if (!$contenidoModulHandler->createModuleFile('css')) {
    $page->displayCriticalError(i18n('Could not create a new css file!'));
    $page->render();
    return;
}

if (stripslashes($file)) {
    $sReloadScript = "<script type=\"text/javascript\">
                         var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
                         if (left_bottom) {
                             var href = left_bottom.location.href;
                             href = href.replace(/&file.*/, '');
                             left_bottom.location.href = href+'&file='+'".$file."';
                         }
                     </script>";
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
    $sReloadScript = "<script type=\"text/javascript\">
                         var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
                         if (left_bottom) {
                             var href = left_bottom.location.href;
                             href = href.replace(/&file[^&]*/, '');
                             left_bottom.location.href = href+'&file='+'".$sFilename."';
                         }
                     </script>";
} else {
    $sReloadScript = '';
}

// Content Type is css
$sTypeContent = 'css';
$aFileInfo = getFileInformation($client, $sTempFilename, $sTypeContent, $db);

// Create new file
if ($actionRequest == $sActionCreate && $_REQUEST['status'] == 'send') {
    $sTempFilename = $sFilename;
    $ret = cFileHandler::create($path . $sFilename);

    $fileEncoding = getEffectiveSetting('encoding', 'file_encoding', 'UTF-8');

    $tempCode = iconv(cModuleHandler::getEncoding(), $fileEncoding, $_REQUEST['code']);
    cFileHandler::validateFilename($sFilename);
    cFileHandler::write($path . $sFilename, $tempCode);
    $bEdit = cFileHandler::read($path . $sFilename);

    updateFileInformation($client, $sFilename, 'css', $auth->auth['uid'], $_REQUEST['description'], $db);
    $sReloadScript .= "<script type=\"text/javascript\">
                 var right_top = top.content.right.right_top;
                 if (right_top) {
                     var href = '".$sess->url("main.php?area=$area&frame=3&file=$sTempFilename")."';
                     right_top.location.href = href;
                 }
                 </script>";

    if ($ret && $bEdit) {
        $page->displayInfo(i18n('Created new css file successfully'));
    } else {
        $page->displayInfo(i18n('Could not create a new css file!'));
    }
}

// Edit selected file
if ($actionRequest == $sActionEdit && $_REQUEST['status'] == 'send') {
    if ($sFilename != $sTempFilename) {
        cFileHandler::validateFilename($sFilename);
        if (cFileHandler::rename($path . $sTempFilename, $sFilename)) {
            $sTempFilename = $sFilename;
        } else {
            $notification->displayNotification("error", sprintf(i18n("Can not rename file %s"), $path . $sTempFilename));
            exit;
        }
        $sReloadScript .= "<script type=\"text/javascript\">
                             var right_top = top.content.right.right_top;
                             if (right_top) {
                                 var href = '".$sess->url("main.php?area=$area&frame=3&file=$sTempFilename")."';
                                 right_top.location.href = href;
                             }
                             </script>";
    } else {
        $sTempFilename = $sFilename;
    }

    updateFileInformation($client, $sOrigFileName, 'css', $auth->auth['uid'], $_REQUEST['description'], $db, $sFilename);

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
            exit;
        }
        $sCode = iconv($fileEncoding, cModuleHandler::getEncoding(), $sCode);
    } else {
        $sCode = stripslashes($_REQUEST['code']); // stripslashes is required here in case of creating a new file
    }

    $aFileInfo = getFileInformation($client, $sTempFilename, 'css', $db);

    $form = new cGuiTableForm('file_editor');
    $form->addHeader(i18n('Edit file'));
    $form->setVar('area', $area);
    $form->setVar('action', $sAction);
    $form->setVar('frame', $frame);
    $form->setVar('status', 'send');
    $form->setVar('tmp_file', $sTempFilename);
    $form->setVar('idmod', $idmod);
    $tb_name = new  cHTMLLabel($sFilename, '');//new cHTMLTextbox('file', $sFilename, 60);
    $ta_code = new cHTMLTextarea('code', htmlspecialchars($sCode), 100, 35, 'code');
    //$descr     = new cHTMLTextarea('description', htmlspecialchars($aFileInfo['description']), 100, 5);


    $ta_code->setStyle('font-family: monospace;width: 100%;');
    // $descr->setStyle('font-family: monospace;width: 100%;');
    $ta_code->updateAttributes(array('wrap' => getEffectiveSetting('style_editor', 'wrap', 'off')));

    $form->add(i18n('Name'), $tb_name);
    //$form->add(i18n('Description'), $descr->render());
    $form->add(i18n('Code'), $ta_code);

    $page->setContent(array($form));

    $oCodeMirror = new CodeMirror('code', 'css', substr(strtolower($belang), 0, 2), true, $cfg);
    $page->addScript($oCodeMirror->renderScript());

    //$page->addScript('reload', $sReloadScript);
    $page->render();
}

?>