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
 * @package CONTENIDO Backend Includes
 * @version 1.5.1
 * @author Willi Mann
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release <= 4.6
 *
 *        {@internal
 *        created 2004-07-14
 *        $Id$:
 *        }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude("external", "codemirror/class.codemirror.php");
cInclude("includes", "functions.file.php");
$sFileType = "html";

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
    $contenidoModulTemplateHandler = new cModuleTemplateHandler($idmod);
    $contenidoModulTemplateHandler->setAction($action);
    $contenidoModulTemplateHandler->setCode($_REQUEST['code']);
    $contenidoModulTemplateHandler->setFiles($_REQUEST['file'], $_REQUEST['tmp_file']);
    $contenidoModulTemplateHandler->setFrameIdmodArea($frame, $idmod, $area);
    $contenidoModulTemplateHandler->setNewDelete($_REQUEST['new'], $_REQUEST['delete']);
    $contenidoModulTemplateHandler->setSelectedFile($_REQUEST['selectedFile']);
    $contenidoModulTemplateHandler->setStatus($_REQUEST['status']);
    $contenidoModulTemplateHandler->display($perm, $notification, $belang);
}

$page->render();

?>