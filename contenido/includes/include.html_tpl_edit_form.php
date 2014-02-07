<?php
/**
 * This file contains the backend page for editing html template files.
 * @fixme: Rework logic for creation of cApiFileInformation entries
 * It may happpen, that we have already a file but not a entry or vice versa!
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

$readOnly = (getEffectiveSetting("client", "readonly", "false") == "true");
if($readOnly) {
	cRegistry::addWarningMessage(i18n("The administrator disbaled editing these files!"));
}

$sActionCreate = 'htmltpl_create';
$sActionEdit = 'htmltpl_edit';
$sActionDelete = 'htmltpl_delete';
$sFilename = '';

$page = new cGuiPage('html_tpl_edit_form', '', '0');

$tpl->reset();

if (!$perm->have_perm_area_action($area, $action)) {
    $notification->displayNotification('error', i18n('Permission denied'));
    return;
}

if (!(int) $client > 0) {
    // If there is no client selected, display empty page
    $page->render();
    return;
}

if ((!$readOnly) && $action == $sActionDelete) {

    $path = $cfgClient[$client]['tpl']['path'];
    // delete file
    // TODO also delete the versioning files
    if (!strrchr($_REQUEST['delfile'], '/')) {
        if (cFileHandler::exists($path . $_REQUEST['delfile'])) {
            $fileInfoCollection = new cApiFileInformationCollection();

            $fileIds = $fileInfoCollection->getIdsByWhereClause("`filename`='" . cSecurity::toString($_REQUEST["delfile"]) . "'");

            if (cSecurity::isInteger($fileIds[0]) && is_dir($cfgClient[$client]['version']['path'] . "templates/" . $fileIds[0])) {
                cDirHandler::recursiveRmdir($cfgClient[$client]['version']['path'] . "templates/" . $fileIds[0]);

                $fileInfoCollection->removeFileInformation(array(
                    'idclient' => cSecurity::toInteger($client),
                    'filename' => cSecurity::toString($_REQUEST['delfile']),
                    'type' => 'templates'
                ));
            }

            unlink($path . cSecurity::toString($_REQUEST['delfile']));

            $page->displayInfo(i18n('Deleted template file successfully!'));
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
	
    $path = $cfgClient[$client]['tpl']['path'];

    $sTempFilename = stripslashes($_REQUEST['tmp_file']);
    $sOrigFileName = $sTempFilename;

    // determine allowed extensions for template files in client template folder
    $allowedExtensions = cRegistry::getConfigValue('client_template', 'allowed_extensions', 'html');
    $allowedExtensions = explode(',', $allowedExtensions);
    $allowedExtensions = array_map('trim', $allowedExtensions);

    $sFilename = $_REQUEST['file'];
    if (!in_array(cFileHandler::getExtension($sFilename), $allowedExtensions) && strlen(stripslashes(trim($sFilename))) > 0) {
        // determine default extension for new template files
        $defaultExtension = cRegistry::getConfigValue('client_template', 'default_extension', 'html');
        $sFilename = stripslashes($sFilename) . '.' . $defaultExtension;
    } else {
        $sFilename = stripslashes($sFilename);
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

    // Content Type is template
    $sTypeContent = 'templates';

    if (!cFileHandler::writeable($path . $sFilename) && !cFileHandler::writeable($path . $sOrigFileName)) {
        $page->displayWarning(i18n("You have no write permissions for this file"));
    }

    // Create new file
    if ((!$readOnly) && $_REQUEST['action'] == $sActionCreate && $_REQUEST['status'] == 'send') {
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
        $reloadScriptUrl = $sess->url("main.php?area=$area&frame=3&file=$sTempFilename");
        $sReloadScript .= <<<JS
<script type="text/javascript">
(function(Con, $) {
     var frame = Con.getFrame('right_top');
     if (frame) {
         frame.location.href = '{$reloadScriptUrl}';
     }
})(Con, Con.$);
</script>
JS;
        $fileInfoCollection = new cApiFileInformationCollection();
        $fileInfoCollection->create('templates', $sFilename, $_REQUEST['description']);

        $page->displayInfo(i18n('Created new template file successfully!'));
    }

    // Edit selected file
    if ((!$readOnly) && $_REQUEST['action'] == $sActionEdit && $_REQUEST['status'] == 'send') {
        $sTempTempFilename = $sTempFilename;
        if ($sFilename != $sTempFilename) {
            cFileHandler::validateFilename($sFilename);
            if (cFileHandler::rename($path . $sTempFilename, $sFilename)) {
                $sTempFilename = $sFilename;
            } else {
                $notification->displayNotification('error', sprintf(i18n('Can not rename file %s'), $path . $sTempFilename));
                exit();
            }
            $reloadScriptUrl = $sess->url("main.php?area=$area&frame=3&file=$sTempFilename");
            $sReloadScript .= <<<JS
<script type="text/javascript">
(function(Con, $) {
     var frame = Con.getFrame('right_top');
     if (frame) {
         frame.location.href = '{$reloadScriptUrl}';
     }
})(Con, Con.$);
</script>
JS;
        } else {
            $sTempFilename = $sFilename;
        }

        if ($sFilename != $sTempTempFilename) {
            $page->displayInfo(i18n('Renamed template file successfully!'));
        } else {
            $page->displayInfo(i18n('Saved changes successfully!'));
        }

        $fileInfoCollection = new cApiFileInformationCollection();
        $aFileInfo = $fileInfoCollection->getFileInformation($sOrigFileName, $sTypeContent);

        // @fixme: Rework logic. Even if we have already a file, there may be no
        // db entry available!
        if (0 == count($aFileInfo)) {
            // No entry, create it
            $fileInfoCollection->create('templates', $sFilename, $_REQUEST['description']);
        }

        // @fixme: Check condition below, how is it possible to have an db entry
        // with primary key?
        if ((count($aFileInfo) > 0) && ($aFileInfo['idsfi'] != '')) {
            $oVersion = new cVersionFile($aFileInfo['idsfi'], $aFileInfo, $sFilename, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame, $sOrigFileName);
            // Create new Layout Version in cms/version/css/ folder
            $oVersion->createNewVersion();
        }

        // @fixme: no need to update if it was created before (see code above)
        $fileInfoCollection = new cApiFileInformationCollection();
        $fileInfoCollection->updateFile($sOrigFileName, 'templates', $_REQUEST['description'], $sFilename);

        // Track version
        $sTypeContent = 'templates';

        cFileHandler::validateFilename($sFilename);
        cFileHandler::write($path . $sFilename, $_REQUEST['code']);
        $bEdit = cFileHandler::read($path . $sFilename);
    }

    // Generate edit form
    if (isset($_REQUEST['action'])) {
        $sAction = ($bEdit) ? $sActionEdit : $_REQUEST['action'];

        // if the system is read only the code should always be read from the file system
        if ($readOnly || $_REQUEST['action'] == $sActionEdit) {
            $sCode = cFileHandler::read($path . $sFilename);
            if ($sCode === false) {
                exit();
            }
        } else {
            // stripslashes is required here in case of creating a new file
            $sCode = stripslashes($_REQUEST['code']);
        }

        // Try to validate html
        if (getEffectiveSetting('layout', 'htmlvalidator', 'true') == 'true' && $sCode !== '') {
            $v = new cHTMLValidator();
            $v->validate($sCode);
            $msg = '';

            foreach ($v->missingNodes as $value) {
                $idqualifier = '';

                $attr = array();

                if ($value['name'] != '') {
                    $attr['name'] = "name '" . $value['name'] . "'";
                }

                if ($value['id'] != '') {
                    $attr['id'] = "id '" . $value['id'] . "'";
                }

                $idqualifier = implode(', ', $attr);

                if ($idqualifier != '') {
                    $idqualifier = "($idqualifier)";
                }
                $msg .= sprintf(i18n("Tag '%s' %s has no end tag (start tag is on line %s char %s)"), $value['tag'], $idqualifier, $value['line'], $value['char']) . '<br>';
            }

            if ($msg != '') {
                $page->displayWarning($msg);
            }
        }

        $fileInfoCollection = new cApiFileInformationCollection();
        // if the readonly mode is on, tempFilename isn't set
        if($readOnly) {
       	 	$aFileInfo = $fileInfoCollection->getFileInformation($sFilename, $sTypeContent);
        } else {
        	$aFileInfo = $fileInfoCollection->getFileInformation($sTempFilename, $sTypeContent);
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
            'wrap' => getEffectiveSetting('html_editor', 'wrap', 'off')
        ));

        $form->add(i18n('Name'), $tb_name);
        $form->add(i18n('Description'), $descr->render());
        $form->add(i18n('Code'), $ta_code);

        $page->setContent($form);

        $oCodeMirror = new CodeMirror('code', 'html', substr(strtolower($belang), 0, 2), true, $cfg);
        if($readOnly) {
        	$oCodeMirror->setProperty("readOnly", "true");
        }
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
