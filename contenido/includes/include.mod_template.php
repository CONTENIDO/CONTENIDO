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
    cRegistry::addWarningMessage(i18n('The administrator disabled editing of these files!'));
}

$sActionCreate = 'htmltpl_create';
$sActionEdit = 'htmltpl_edit';

$fileRequest = $_REQUEST['file'];
$TmpFileRequest = $_REQUEST['tmp_file'];

$page = new cGuiPage("mod_template");
$tpl->reset();

if (!is_object($notification)) {
    $notification = new cGuiNotification();
}

// $_REQUEST['action'] = $sActionEdit;

if (!$perm->have_perm_area_action($area, $action)) {
    $page->displayCriticalError(i18n("Permission denied"));
} else if (!(int) $client > 0) {
    // If there is no client selected, display empty page
} else {
    $contenidoModulTemplateHandler = new cModuleTemplateHandler($idmod, $page);
    $contenidoModulTemplateHandler->checkWritePermissions();
    $contenidoModulTemplateHandler->setAction($action);
    $contenidoModulTemplateHandler->setCode($_REQUEST['code']);
    $contenidoModulTemplateHandler->setFiles($_REQUEST['file'], $_REQUEST['tmp_file']);
    $contenidoModulTemplateHandler->setFrameIdmodArea($frame, $idmod, $area);
    $contenidoModulTemplateHandler->setNewDelete($_REQUEST['new'], $_REQUEST['delete']);
    $contenidoModulTemplateHandler->setSelectedFile($_REQUEST['selectedFile']);
    $contenidoModulTemplateHandler->setStatus($_REQUEST['status']);
    $contenidoModulTemplateHandler->display($perm, $notification, $belang, $readOnly);
}

$page->render();

?>