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
 * @version    1.5.1
 * @author     Willi Mann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2004-07-14
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2008-08-14, Timo Trautmann, Bilal Arslan - Functions for versionning and storing file meta data added
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude("external", "codemirror/class.codemirror.php");
cInclude("includes", "functions.file.php");

$sFileType = "html";

$sActionCreate = 'htmltpl_create';
$sActionEdit = 'htmltpl_edit';
$sActionDelete = 'htmltpl_delete';
$sFilename = '';
$page = new cGuiPage("html_tpl_edit_form");

$tpl->reset();

if (!$perm->have_perm_area_action($area, $action)) {
    $notification->displayNotification("error", i18n("Permission denied"));
    return;
}

if (!(int) $client > 0) {
    // If there is no client selected, display empty page
    $page->render();
    return;
}

if ($action == $sActionDelete) {

    $path = $cfgClient[$client]["tpl"]["path"];
    # delete file
    if (!strrchr($_REQUEST['delfile'], "/")) {
        if (cFileHandler::exists($path.$_REQUEST['delfile'])) {
            unlink($path.$_REQUEST['delfile']);
            removeFileInformation($client, $_REQUEST['delfile'], 'templates', $db);
            $notification->displayNotification(Contenido_Notification::LEVEL_INFO,i18n("Deleted template file successfully!"));
        }
    }
    $sReloadScript = "<script type=\"text/javascript\">
    var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
    if (left_bottom) {
    var href = left_bottom.location.href;
    href = href.replace(/&file[^&]*/, '');
    left_bottom.location.href = href+'&file='+'".$sFilename."';
    }
    </script>";

    $page->addScript($sReloadScript);
    $page->render();

} else {
    $path = $cfgClient[$client]["tpl"]["path"];
    $sTempFilename = stripslashes($_REQUEST['tmp_file']);
    $sOrigFileName = $sTempFilename;

    if (getFileType($_REQUEST['file']) != $sFileType && strlen(stripslashes(trim($_REQUEST['file']))) > 0) {
        $sFilename .= stripslashes($_REQUEST['file']) . '.' . $sFileType;
    } else {
        $sFilename .= stripslashes($_REQUEST['file']);
    }

    if (stripslashes($_REQUEST['file'])) {
        $sReloadScript = "<script type=\"text/javascript\">
                             var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
                             if (left_bottom) {
                                 var href = left_bottom.location.href;
                                 href = href.replace(/&file[^&]*/, '');
                                 left_bottom.location.href = href+'&file='+'".$sFilename."';
                             }
                         </script>";
    } else {
        $sReloadScript = "";
    }

    // Content Type is template
    $sTypeContent = "templates";
    $aFileInfo = getFileInformation ($client, $sTempFilename, $sTypeContent, $db);

    // Create new file
    if ($_REQUEST['action'] == $sActionCreate && $_REQUEST['status'] == 'send') {
        $sTempFilename = $sFilename;
        $ret = createFile($sFilename, $path);
        $bEdit = fileEdit($sFilename, $_REQUEST['code'], $path);
        $sReloadScript .= "<script type=\"text/javascript\">
                 var right_top = top.content.right.right_top;
                 if (right_top) {
                     var href = '".$sess->url("main.php?area=$area&frame=3&file=$sTempFilename")."';
                     right_top.location.href = href;
                 }
                 </script>";
        updateFileInformation($client, $sFilename, 'templates', $auth->auth['uid'], $_REQUEST['description'], $db);
        $notification->displayNotification(Contenido_Notification::LEVEL_INFO,i18n("Created new template file successfully!"));
    }

    // Edit selected file
    if ($_REQUEST['action'] == $sActionEdit && $_REQUEST['status'] == 'send') {
        $sTempTempFilename = $sTempFilename;
        if ($sFilename != $sTempFilename) {
            $sTempFilename = renameFile($sTempFilename, $sFilename, $path);
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

        if ($sFilename != $sTempTempFilename) {
            $notification->displayNotification(Contenido_Notification::LEVEL_INFO,i18n("Renamed template file successfully!"));
        } else {
            $notification->displayNotification(Contenido_Notification::LEVEL_INFO,i18n("Saved changes successfully!"));
        }
        updateFileInformation($client, $sOrigFileName, 'templates', $auth->auth['uid'], $_REQUEST['description'], $db, $sFilename);

        // Track version
        $sTypeContent = "templates";

        if ((count($aFileInfo) == 0) || ((int)$aFileInfo["idsfi"] == 0)) {
            $aFileInfo = getFileInformation ($client, $sTempFilename, $sTypeContent, $db);
            $aFileInfo['description'] = '';
        }

        if ((count($aFileInfo) > 0) && ($aFileInfo["idsfi"] !="")) {
            $oVersion = new cVersionFile($aFileInfo["idsfi"], $aFileInfo,  $sFilename, $sTypeContent, $cfg, $cfgClient, $db, $client, $area, $frame, $sOrigFileName);
            // Create new Layout Version in cms/version/css/ folder
            $oVersion->createNewVersion();
        }

        $bEdit = fileEdit($sFilename, $_REQUEST['code'], $path);
    }

    // Generate edit form
    if (isset($_REQUEST['action'])) {
        $sAction = ($bEdit) ? $sActionEdit : $_REQUEST['action'];

        if ($_REQUEST['action'] == $sActionEdit) {
            $sCode = getFileContent($sFilename, $path);
        } else {
            $sCode = stripslashes($_REQUEST['code']); # stripslashes is required here in case of creating a new file
        }

        // Try to validate html
        if (getEffectiveSetting("layout", "htmlvalidator", "true") == "true" && $sCode !== "") {
            $v = new cHTMLValidator;
            $v->validate($sCode);
            $msg = "";

            foreach ($v->missingNodes as $value) {
                $idqualifier = "";

                $attr = array();

                if ($value["name"] != "") {
                    $attr["name"] = "name '".$value["name"]."'";
                }

                if ($value["id"] != "") {
                    $attr["id"] = "id '".$value["id"]."'";
                }

                $idqualifier = implode(", ",$attr);

                if ($idqualifier != "") {
                    $idqualifier = "($idqualifier)";
                }
                $msg .= sprintf(i18n("Tag '%s' %s has no end tag (start tag is on line %s char %s)"), $value["tag"], $idqualifier, $value["line"],$value["char"]) . "<br />";
            }

            if ($msg != "") {
                $page->displayWarning($msg);
            }
        }

        $aFileInfo = getFileInformation($client, $sTempFilename, $sTypeContent, $db);

        $form = new UI_Table_Form("file_editor");
        $form->addHeader(i18n("Edit file"));
        $form->setWidth("100%");
        $form->setVar("area", $area);
        $form->setVar("action", $sAction);
        $form->setVar("frame", $frame);
        $form->setVar("status", 'send');
        $form->setVar("tmp_file", $sTempFilename);

        $tb_name = new cHTMLTextbox("file", $sFilename, 60);
        $ta_code = new cHTMLTextarea("code", htmlspecialchars($sCode), 100, 35, "code");
        $descr  = new cHTMLTextarea("description", htmlspecialchars($aFileInfo["description"]), 100, 5);

        $ta_code->setStyle("font-family: monospace;width: 100%;");
        $descr->setStyle("font-family: monospace;width: 100%;");
        $ta_code->updateAttributes(array("wrap" => getEffectiveSetting('html_editor', 'wrap', 'off')));

        $form->add(i18n("Name"), $tb_name);
        $form->add(i18n("Description"), $descr->render());
        $form->add(i18n("Code"), $ta_code);

        $page->setContent($form);

        $oCodeMirror = new CodeMirror('code', 'html', substr(strtolower($belang), 0, 2), true, $cfg);
        $page->addScript($oCodeMirror->renderScript());

        $page->addScript($sReloadScript);
        $page->render();
    }
}

?>