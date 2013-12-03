<?php
/**
 * This file contains the backend page for editing style files.
 * @fixme: Rework logic for creation of cApiFileInformation entries
 * It may happpen, that we have already a file but not a entry or vice versa!
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Willi Man, Olaf Niemann
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('external', 'codemirror/class.codemirror.php');
$sFileType = 'css';
$sFilename = '';
$page = new cGuiPage('style_edit_form');
$page->setEncoding('utf-8');

$tpl->reset();

// Some checks
if (!$perm->have_perm_area_action($area, $action)) {
    $page->displayCriticalError(i18n('Permission denied'));
    $page->render();
    exit();
} elseif (!(int) $client > 0) {
    // If there is no client selected, display empty page
    $page->render();
    exit();
}

if ($action == 'style_delete') {
    $path = $cfgClient[$client]['css']['path'];
    // Delete file
    $file = new cApiFileInformation();

    $file->loadByMany(array(
        'idsfi' => $_REQUEST['delfile']
    ));
    $filename = $file->get('filename');
    if (!strrchr($_REQUEST['delfile'], '/')) {
        $fileId = $file->get("idsfi");

        if (cFileHandler::exists($path . $filename) && cSecurity::isInteger($fileId)) {
            if (is_dir($cfgClient[$client]['version']['path'] . "css/" . $fileId)) {
                cDirHandler::recursiveRmdir($cfgClient[$client]['version']['path'] . "css/" . $fileId);
            }

            unlink($path . cSecurity::toString($filename));

            $fileInfoCollection = new cApiFileInformationCollection();
            $fileInfoCollection->removeFileInformation(array(
                'idclient' => cSecurity::toInteger($client),
                'filename' => cSecurity::toString($filename),
                'type' => 'css'
            ));
            $page->displayInfo(i18n('Deleted CSS file successfully!'));
        } elseif (cFileHandler::exists($path . $_REQUEST['delfile'])) {
            unlink($path . cSecurity::toString($_REQUEST['delfile']));
            $page->displayInfo(i18n('Deleted CSS file successfully!'));
        }
    }

    $page->setReload();
    $page->render();
} else {
    $path = $cfgClient[$client]['css']['path'];
    if (stripslashes($_REQUEST['file'])) {
        $reloadFile = stripslashes($_REQUEST['file']);
        $sReloadScript = <<<JS
<script type="text/javascript">
(function(Con, $) {
    var frame = Con.getFrame('left_bottom');
    if (frame) {
        frame.location.href = Con.UtilUrl.replaceParams(frame.location.href, {file: '{$reloadFile}'});
    }
})(Con, Con.$);
</script>
JS;
    } else {
        $sReloadScript = '';
    }

    $sTempFilename = stripslashes($_REQUEST['tmp_file']);
    $sOrigFileName = $sTempFilename;

    if (getFileType($_REQUEST['file']) != $sFileType && strlen(stripslashes(trim($_REQUEST['file']))) > 0) {
        $sFilename .= stripslashes($_REQUEST['file']) . '.' . $sFileType;
    } else {
        $sFilename .= stripslashes($_REQUEST['file']);
    }

    // Content Type is css
    $sTypeContent = 'css';
    $fileInfoCollection = new cApiFileInformationCollection();
    $aFileInfo = $fileInfoCollection->getFileInformation($sTempFilename, $sTypeContent);

    if (!cFileHandler::writeable($path . $sFilename) && !cFileHandler::writeable($path . $sOrigFileName)) {
        $page->displayWarning(i18n("You have no write permissions for this file"));
    }

    // Create new file
    if ($_REQUEST['action'] == 'style_create' && $_REQUEST['status'] == 'send') {
        $sTempFilename = $sFilename;
        // check filename and create new file
        cFileHandler::validateFilename($sFilename);

        // CON-1284 check if file already exists in FS
        if (cFileHandler::exists($path . $sFilename)) {
            $notification->displayNotification('error', sprintf(i18n('Can not create file %s'), $sFilename));
            $page->render();
            exit();
        }

        // CON-1284 check if file already exists in DB
        $fileInfoCollection = new cApiFileInformationCollection();
        $aFileInfo = $fileInfoCollection->getFileInformation($sFilename, $sTypeContent);
        if (0 < count($aFileInfo)) {
            $notification->displayNotification('error', sprintf(i18n('Can not create file %s'), $sFilename));
            $page->render();
            exit();
        }

        cFileHandler::create($path . $sFilename, $_REQUEST['code']);
        $bEdit = cFileHandler::read($path . $sFilename);
        $fileInfoCollection = new cApiFileInformationCollection();
        $fileInfoCollection->create('css', $sFilename, $_REQUEST['description']);
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
        // if ($bEdit) {
        $page->displayInfo(i18n('Created new CSS file successfully!'));
        // }
    }

    // Edit selected file
    if ($_REQUEST['action'] == 'style_edit' && $_REQUEST['status'] == 'send') {
        $tempTemplate = $sTempFilename;
        if ($sFilename != $sTempFilename) {
            cFileHandler::validateFilename($sFilename);

            // CON-1284 check if file already exists in FS
            if (cFileHandler::exists($path . $sFilename)) {
                $notification->displayNotification('error', sprintf(i18n('Can not rename file %s'), $sTempFilename));
                $page->render();
                exit();
            }

            // CON-1284 check if file already exists in DB
            $fileInfoCollection = new cApiFileInformationCollection();
            $aFileInfo = $fileInfoCollection->getFileInformation($sFilename, $sTypeContent);
            if (0 < count($aFileInfo)) {
                $notification->displayNotification('error', sprintf(i18n('Can not rename file %s'), $sTempFilename));
                $page->render();
                exit();
            }

            if (cFileHandler::rename($path . $sTempFilename, $sFilename)) {
                $sTempFilename = $sFilename;
            } else {
                $notification->displayNotification('error', sprintf(i18n('Can not rename file %s'), $path . $sTempFilename));
                $page->render();
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
        $aFileInfo = $fileInfoCollection->getFileInformation($sOrigFileName, $sTypeContent);

        // @fixme: Rework logic. Even if we have already a file, there may be no
        // db entry available!
        if (0 == count($aFileInfo)) {
            // No entry, create it
            $fileInfoCollection->create('css', $sFilename, $_REQUEST['description']);
        }

        // @fixme: Check condition below, how is it possible to have an db entry
        // with primary key?
        if ((count($aFileInfo) == 0) || ($aFileInfo['idsfi'] != '')) {
            $oVersion = new cVersionFile($aFileInfo['idsfi'], $aFileInfo, $sFilename, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame, $sFilename);
            // Create new version
            $oVersion->createNewVersion();
        }
        cFileHandler::validateFilename($sFilename);
        cFileHandler::write($path . $sFilename, $_REQUEST['code']);
        $bEdit = cFileHandler::read($path . $sFilename);

        // Show message
        if ($sFilename != $sTempFilename) {
            $page->displayInfo(i18n('Renamed template file successfully!'));
        } else {
            $page->displayInfo(i18n('Saved changes successfully!'));
        }

        // @fixme: no need to update if it was created before (see code above)
        $fileInfoCollection = new cApiFileInformationCollection();
        $fileInfoCollection->updateFile($sOrigFileName, 'css', $_REQUEST['description'], $sFilename);
        // Track version
        // For read Fileinformation an get the id of current File
        cInclude('includes', 'functions.file.php');
    }
    // Generate edit form
    if (isset($_REQUEST['action'])) {
        $sAction = ($_REQUEST['file']) ? 'style_edit' : $_REQUEST['action'];

        if ($_REQUEST['action'] == 'style_edit') {
            $sCode = cFileHandler::read($path . $sFilename);
        } else {
            // stripslashes is required here in case of creating a new file
            $sCode = stripslashes($_REQUEST['code']);
        }

        $fileInfoCollection = new cApiFileInformationCollection();
        $aFileInfo = $fileInfoCollection->getFileInformation($sTempFilename, 'css');

        $form = new cGuiTableForm('file_editor');
        $form->addHeader(i18n('Edit file'));
        $form->setVar('area', $area);
        $form->setVar('action', $sAction);
        $form->setVar('frame', $frame);
        $form->setVar('status', 'send');
        $form->setVar('tmp_file', $sTempFilename);

        $tb_name = new cHTMLTextbox('file', $sFilename, 60);
        $ta_code = new cHTMLTextarea('code', conHtmlSpecialChars($sCode), 100, 35, 'code');
        $descr = new cHTMLTextarea('description', conHtmlSpecialChars($aFileInfo['description']), 100, 5);

        $ta_code->setStyle('font-family:monospace;width:100%;');
        $descr->setStyle('font-family:monospace;width:100%;');
        $ta_code->updateAttributes(array(
            'wrap' => getEffectiveSetting('style_editor', 'wrap', 'off')
        ));

        $form->add(i18n('Name'), $tb_name);
        $form->add(i18n('Description'), $descr->render());
        $form->add(i18n('Code'), $ta_code);

        $page->setContent(array(
            $form
        ));

        $oCodeMirror = new CodeMirror('code', 'css', substr(strtolower($belang), 0, 2), true, $cfg);
        $page->addScript($oCodeMirror->renderScript());

        if (!empty($sReloadScript)) {
            $page->addScript($sReloadScript);
        }
    }

    $page->render();
}
