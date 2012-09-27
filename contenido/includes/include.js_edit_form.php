<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Edit file
 *
 * @package CONTENIDO Backend Includes
 * @version 1.1.1
 * @author Willi Mann
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release <= 4.6
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude("external", "codemirror/class.codemirror.php");

$sFileType = "js";

$sFilename = '';
$page = new cGuiPage("js_edit_form");

$tpl->reset();

if (!$perm->have_perm_area_action($area, $action)) {
    $page->displayCriticalError(i18n("Permission denied"));
    $page->render();
    return;
}

if (!(int) $client > 0) {
    // If there is no client selected, display empty page
    $page->render();
    return;
}

if ($action == 'js_delete') {
    $path = $cfgClient[$client]["js"]["path"];
    // TODO also delete the versioning files

    if (!strrchr($_REQUEST['delfile'], "/")) {
        if (cFileHandler::exists($path . $_REQUEST['delfile'])) {
            unlink($path . $_REQUEST['delfile']);
            $fileInfoCollection = new cApiFileInformationCollection();
            $fileInfoCollection->removeFileInformation(array(
                'idclient' => $client,
                'filename' => $_REQUEST['delfile'],
                'type' => 'js'
            ));

            $page->displayInfo(i18n("Deleted JS-File successfully!"));
        }
    }

    $sReloadScript = "<script type=\"text/javascript\">
                        var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
                        if (left_bottom) {
                            var href = left_bottom.location.href;
                            href = href.replace(/&file[^&]*/, '');
                            left_bottom.location.href = href+'&file='+'" . $sFilename . "';
                        }
                      </script>";
    $page->addScript($sReloadScript);
    $page->render();
} else {
    $path = $cfgClient[$client]["js"]["path"];
    $sTempFilename = stripslashes($_REQUEST['tmp_file']);
    $sOrigFileName = $sTempFilename;

    if (getFileType($_REQUEST['file']) != $sFileType && strlen(stripslashes(trim($_REQUEST['file']))) > 0) {
        $sFilename .= stripslashes($_REQUEST['file']) . ".$sFileType";
    } else {
        $sFilename .= stripslashes($_REQUEST['file']);
    }

    if (stripslashes($_REQUEST['file'])) {
        $sReloadScript = "<script type=\"text/javascript\">
                             var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
                             if (left_bottom) {
                                 var href = left_bottom.location.href;
                                 href = href.replace(/&file[^&]*/, '');
                                 left_bottom.location.href = href+'&file='+'" . $sFilename . "';
                             }
                         </script>";
    } else {
        $sReloadScript = "";
    }

    // Content Type is template
    $sTypeContent = "js";

    // Create new file
    if ($_REQUEST['action'] == 'js_create' && $_REQUEST['status'] == 'send') {
        $sTempFilename = $sFilename;
        // check filename and create new file
        cFileHandler::validateFilename($sFilename);
        cFileHandler::create($path . $sFilename, $_REQUEST['code']);
        $bEdit = cFileHandler::read($path . $sFilename);
        $fileInfoCollection = new cApiFileInformationCollection();
        $fileInfoCollection->create('js', $sFilename, $_REQUEST['description']);
        $sReloadScript .= "<script type=\"text/javascript\">
                 var right_top = top.content.right.right_top;
                 if (right_top) {
                     var href = '" . $sess->url("main.php?area=$area&frame=3&file=$sTempFilename") . "';
                     right_top.location.href = href;
                 }
                 </script>";
        // if ($bEdit) {
        $page->displayInfo(i18n("Crated new JS-File successfully!"));
        // }
    }

    // Edit selected file
    if ($_REQUEST['action'] == 'js_edit' && $_REQUEST['status'] == 'send') {
        $sTempTempFilename = $sTempFilename;

        if ($sFilename != $sTempFilename) {
            cFileHandler::validateFilename($sFilename);
            if (cFileHandler::rename($path . $sTempFilename, $sFilename)) {
                $sTempFilename = $sFilename;
            } else {
                $notification->displayNotification("error", sprintf(i18n("Can not rename file %s"), $path . $sTempFilename));
                exit();
            }
            $sReloadScript .= "<script type=\"text/javascript\">
                 var right_top = top.content.right.right_top;
                 if (right_top) {
                     var href = '" . $sess->url("main.php?area=$area&frame=3&file=$sTempFilename") . "';
                     right_top.location.href = href;
                 }
                 </script>";
        } else {
            $sTempFilename = $sFilename;
        }

        $fileInfoCollection = new cApiFileInformationCollection();
        $aFileInfo = $fileInfoCollection->getFileInformation($sTempFilename, $sTypeContent);

        if (count($aFileInfo) > 0 && $aFileInfo["idsfi"] != "") {
            $oVersion = new cVersionFile($aFileInfo["idsfi"], $aFileInfo, $sFilename, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame, $sOrigFileName);
            // Create new Jscript Version in cms/version/js/ folder
            $oVersion->createNewVersion();
        }

        $fileInfoCollection = new cApiFileInformationCollection();
        $fileInfoCollection->updateFile($sOrigFileName, 'js', $_REQUEST['description'], $sFilename);

        cFileHandler::validateFilename($sFilename);
        cFileHandler::write($path . $sFilename, $_REQUEST['code']);
        $bEdit = cFileHandler::read($path . $sFilename);

        if ($sFilename != $sTempTempFilename) {
            $page->displayInfo(i18n("Renamed the JS-File successfully!"));
        } else {
            $page->displayInfo(i18n("Saved changes successfully!"));
        }
    }

    // Generate edit form
    if (isset($_REQUEST['action'])) {
        $fileInfoCollection = new cApiFileInformationCollection();
        $aFileInfo = $fileInfoCollection->getFileInformation($sTempFilename, $sTypeContent);

        $sAction = ($bEdit)? 'js_edit' : $_REQUEST['action'];

        if ($_REQUEST['action'] == 'js_edit') {
            $sCode = cFileHandler::read($path . $sFilename);
            if ($sCode === false) {
                exit();
            }
        } else {
            $sCode = stripslashes($_REQUEST['code']); // stripslashes is
                                                      // required here in case
                                                      // of creating a new file
        }

        $form = new cGuiTableForm("file_editor");
        $form->addHeader(i18n("Edit file"));
        $form->setVar("area", $area);
        $form->setVar("action", $sAction);
        $form->setVar("frame", $frame);
        $form->setVar("status", 'send');
        $form->setVar("tmp_file", $sTempFilename);

        $tb_name = new cHTMLTextbox("file", $sFilename, 60);
        $ta_code = new cHTMLTextarea("code", htmlspecialchars($sCode), 100, 35, "code");
        $descr = new cHTMLTextarea("description", htmlspecialchars($aFileInfo["description"]), 100, 5);

        $ta_code->setStyle("font-family: monospace;width: 100%;");
        $descr->setStyle("font-family: monospace;width: 100%;");
        $ta_code->updateAttributes(array(
            "wrap" => getEffectiveSetting('script_editor', 'wrap', 'off')
        ));

        $form->add(i18n("Name"), $tb_name);
        $form->add(i18n("Description"), $descr->render());
        $form->add(i18n("Code"), $ta_code);

        $page->setContent($form);

        $oCodeMirror = new CodeMirror('code', 'js', substr(strtolower($belang), 0, 2), true, $cfg);
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
