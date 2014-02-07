<?php
/**
 * This file contains the backend page for editing javascript files.
 * @fixme: Rework logic for creation of cApiFileInformation entries
 * It may happpen, that we have already a file but not a entry or vice versa!
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('external', 'codemirror/class.codemirror.php');

$sFileType = 'js';

$readOnly = (getEffectiveSetting("client", "readonly", "false") == "true");
if($readOnly) {
	cRegistry::addWarningMessage(i18n("The administrator disbaled editing these files!"));
}

$sFilename = '';
$page = new cGuiPage('js_edit_form', '', '0');

$tpl->reset();

if (!$perm->have_perm_area_action($area, $action)) {
    $page->displayCriticalError(i18n('Permission denied'));
    $page->render();
    return;
}

if (!(int) $client > 0) {
    // If there is no client selected, display empty page
    $page->render();
    return;
}

if ((!$readOnly) && $action == 'js_delete') {
    $path = $cfgClient[$client]['js']['path'];

    if (!strrchr($_REQUEST['delfile'], '/')) {
        if (cFileHandler::exists($path . $_REQUEST['delfile'])) {
            $fileInfoCollection = new cApiFileInformationCollection();
            $fileIds = $fileInfoCollection->getIdsByWhereClause("`filename`='" . cSecurity::toString($_REQUEST["delfile"]) . "'");

            if (cSecurity::isInteger($fileIds[0]) && is_dir($cfgClient[$client]['version']['path'] . "js/" . $fileIds[0])) {
                cDirHandler::recursiveRmdir($cfgClient[$client]['version']['path'] . "js/" . $fileIds[0]);

                $fileInfoCollection->removeFileInformation(array(
                    'idclient' => cSecurity::toInteger($client),
                    'filename' => cSecurity::toString($_REQUEST['delfile']),
                    'type' => 'js'
                ));
            }

            unlink($path . cSecurity::toString($_REQUEST['delfile']));

            $page->displayInfo(i18n('Deleted JS-File successfully!'));
        }
    }

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
    $page->addScript($sReloadScript);
    $page->render();
} else {
	// clicking on 'Save Changes' or 'Delete file' doesn't set the right request variables in read only mode
    if($readOnly && !isset($_REQUEST['file']) && isset($_REQUEST['delfile'])) {
        $_REQUEST['file'] = $_REQUEST['delfile'];
    } else if($readOnly && !isset($_REQUEST['file']) && isset($_REQUEST['tmp_file'])) {
    	$_REQUEST['file'] = $_REQUEST['tmp_file'];
    }
    
    $path = $cfgClient[$client]['js']['path'];
    $sTempFilename = stripslashes($_REQUEST['tmp_file']);
    $sOrigFileName = $sTempFilename;

    if (getFileType($_REQUEST['file']) != $sFileType && strlen(stripslashes(trim($_REQUEST['file']))) > 0) {
        $sFilename .= stripslashes($_REQUEST['file']) . ".$sFileType";
    } else {
        $sFilename .= stripslashes($_REQUEST['file']);
    }

    if (stripslashes($_REQUEST['file'])) {
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

    if (!cFileHandler::writeable($path . $sFilename) && !cFileHandler::writeable($path . $sOrigFileName)) {
        $page->displayWarning(i18n("You have no write permissions for this file"));
    }

    // Content Type is template
    $sTypeContent = 'js';

    // Create new file
    if ((!$readOnly) && $_REQUEST['action'] == 'js_create' && $_REQUEST['status'] == 'send') {

        $sTempFilename = $sFilename;
        // check filename and create new file
        cFileHandler::validateFilename($sFilename);


        // check if file already exists in FS
        if (cFileHandler::exists($path . $sFilename)) {
            $notification->displayNotification('error', sprintf(i18n('Can not create file %s'), $sFilename));
            $page->render();
            exit();
        }

        // check if file already exists in DB
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
        $fileInfoCollection->create('js', $sFilename, $_REQUEST['description']);
        $urlReload = $sess->url("main.php?area=$area&frame=3&file=$sTempFilename");
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
        // if ($bEdit) {
        $page->displayInfo(i18n('Created new JS-File successfully!'));
        // }
    }

    // Edit selected file
    if ((!$readOnly) && $_REQUEST['action'] == 'js_edit' && $_REQUEST['status'] == 'send') {
        $sTempTempFilename = $sTempFilename;

        if ($sFilename != $sTempFilename) {
            cFileHandler::validateFilename($sFilename);
            if (cFileHandler::rename($path . $sTempFilename, $sFilename)) {
                $sTempFilename = $sFilename;
            } else {
                $notification->displayNotification('error', sprintf(i18n('Can not rename file %s'), $path . $sTempFilename));
                exit();
            }

            $urlReload = $sess->url("main.php?area=$area&frame=3&file=$sTempFilename");
            $sReloadScript = '';
        } else {
            $sTempFilename = $sFilename;
        }

        $fileInfoCollection = new cApiFileInformationCollection();
        $aFileInfo = $fileInfoCollection->getFileInformation($sOrigFileName, $sTypeContent);

        // @fixme: Rework logic. Even if we have already a file, there may be no
        // db entry available!
        if (0 == count($aFileInfo)) {
            // No entry, create it
            $fileInfoCollection->create('js', $sFilename, $_REQUEST['description']);
        }

        // @fixme: Check condition below, how is it possible to have an db entry
        // with primary key?
        if (count($aFileInfo) > 0 && $aFileInfo['idsfi'] != '') {
            $oVersion = new cVersionFile($aFileInfo['idsfi'], $aFileInfo, $sFilename, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame, $sOrigFileName);
            // Create new Jscript Version in cms/version/js/ folder
            $oVersion->createNewVersion();
        }

        // @fixme: no need to update if it was created before (see code above)
        $fileInfoCollection = new cApiFileInformationCollection();
        $fileInfoCollection->updateFile($sOrigFileName, 'js', $_REQUEST['description'], $sFilename);

        cFileHandler::validateFilename($sFilename);
        cFileHandler::write($path . $sFilename, $_REQUEST['code']);
        $bEdit = cFileHandler::read($path . $sFilename);

        if ($sFilename != $sTempTempFilename) {
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
            $page->displayInfo(i18n('Renamed the JS-File successfully!'));
        } else {
            $page->displayInfo(i18n('Saved changes successfully!'));
        }
    }

    // Generate edit form
    if (isset($_REQUEST['action'])) {
        $fileInfoCollection = new cApiFileInformationCollection();
        $aFileInfo = $fileInfoCollection->getFileInformation($sTempFilename, $sTypeContent);

        $sAction = ($bEdit) ? 'js_edit' : $_REQUEST['action'];

        if ($_REQUEST['action'] == 'js_edit' || $readOnly) {
            $sCode = cFileHandler::read($path . $sFilename);
            if ($sCode === false) {
                exit();
            }
        } else {
            // stripslashes is required here in case of creating a new file
            $sCode = stripslashes($_REQUEST['code']);
        }

        $form = new cGuiTableForm('file_editor');
        $form->addHeader(i18n('Edit file'));
        $form->setVar('area', $area);
        $form->setVar('action', $sAction);
        $form->setVar('frame', $frame);
        $form->setVar('status', 'send');
        $form->setVar('tmp_file', $sTempFilename);

        $tb_name = new cHTMLTextbox('file', $sFilename, 60, '', '', $readOnly);
        $ta_code = new cHTMLTextarea('code', conHtmlSpecialChars($sCode), 100, 35, 'code');
        $descr = new cHTMLTextarea('description', conHtmlSpecialChars($aFileInfo['description']), 100, 5, '', $readOnly);

        $ta_code->setStyle('font-family: monospace;width: 100%;');
        $descr->setStyle('font-family: monospace;width: 100%;');
        $ta_code->updateAttributes(array(
            'wrap' => getEffectiveSetting('script_editor', 'wrap', 'off')
        ));

        $form->add(i18n('Name'), $tb_name);
        $form->add(i18n('Description'), $descr->render());
        $form->add(i18n('Code'), $ta_code);

        $oCodeMirror = new CodeMirror('code', 'js', substr(strtolower($belang), 0, 2), true, $cfg);
        if($readOnly) {
        	$oCodeMirror->setProperty("readOnly", "true");
        	
        	$form->setActionButton('submit', cRegistry::getBackendUrl() . 'images/but_ok_off.gif', i18n('Overwriting files is disabled'), 's');
        }
        $page->setContent($form);
        $page->addScript($oCodeMirror->renderScript());

        if (!empty($sReloadScript)) {
            $page->addScript($sReloadScript);
        }
        $page->render();
    } else {
        $page = new cGuiPage('generic_page');
        $page->setContent('');
        $page->render();
    }
}
