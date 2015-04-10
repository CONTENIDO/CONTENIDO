<?php
/**
 * This file contains the backend page for managing module template files.
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

cInclude("external", "codemirror/class.codemirror.php");
cInclude("includes", "functions.file.php");
$sFileType = "html";

$readOnly = (getEffectiveSetting("client", "readonly", "false") == "true");

if($readOnly) {
    cRegistry::addWarningMessage(i18n('This area is read only! The administrator disabled edits!'));
}

$sActionCreate = 'htmltpl_create';
$sActionEdit = 'htmltpl_edit';

if (true === cRegistry::getConfigValue('simulate_magic_quotes')) {
    $fileRequest = stripslashes($_REQUEST['file']);
    $TmpFileRequest = stripslashes($_REQUEST['tmp_file']);
} else {
    $fileRequest = $_REQUEST['file'];
    $TmpFileRequest = $_REQUEST['tmp_file'];
}

$page = new cGuiPage("mod_template");
$tpl->reset();

if (!is_object($notification)) {
    $notification = new cGuiNotification();
}

// $_REQUEST['action'] = $sActionEdit;
if (!$perm->have_perm_area_action($area, $sActionEdit)) {
    $page->displayCriticalError(i18n("Permission denied"));
} else if (!(int) $client > 0) {
    // If there is no client selected, display empty page
} else {
    $contenidoModulTemplateHandler = new cModuleTemplateHandler($idmod, $page);
    $contenidoModulTemplateHandler->checkWritePermissions();
    $contenidoModulTemplateHandler->setAction($sActionEdit);
    if (isset($_REQUEST['code'])) {
        if (true === cRegistry::getConfigValue('simulate_magic_quotes')) {
            $contenidoModulTemplateHandler->setCode($_REQUEST['code']);
        } else {
            $contenidoModulTemplateHandler->setCode(stripslashes($_REQUEST['code']));
        }
    }
    if (true === cRegistry::getConfigValue('simulate_magic_quotes')) {
        $contenidoModulTemplateHandler->setFiles($_REQUEST['file'], $_REQUEST['tmp_file']);
        $contenidoModulTemplateHandler->setFrameIdmodArea($frame, $idmod, $area);
        $contenidoModulTemplateHandler->setNewDelete($_REQUEST['new'], $_REQUEST['delete']);
        $contenidoModulTemplateHandler->setSelectedFile($_REQUEST['selectedFile']);
        $contenidoModulTemplateHandler->setStatus($_REQUEST['status']);
    } else {
        $contenidoModulTemplateHandler->setFiles(stripslashes($_REQUEST['file']), stripslashes($_REQUEST['tmp_file']));
        $contenidoModulTemplateHandler->setFrameIdmodArea($frame, $idmod, $area);
        $contenidoModulTemplateHandler->setNewDelete(stripslashes($_REQUEST['new']), stripslashes($_REQUEST['delete']));
        $contenidoModulTemplateHandler->setSelectedFile(stripslashes($_REQUEST['selectedFile']));
        $contenidoModulTemplateHandler->setStatus(stripslashes($_REQUEST['status']));
    }
    $contenidoModulTemplateHandler->display($perm, $notification, $belang, $readOnly);
}

$page->render();

?>