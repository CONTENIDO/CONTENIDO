<?php

/**
 * This file contains the backend page for managing module template files.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Willi Man
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $idmod, $tpl, $notification;

$client = cSecurity::toInteger(cRegistry::getClientId());
$perm = cRegistry::getPerm();
$area = cRegistry::getArea();
$belang = cRegistry::getBackendLanguage();
$frame = cRegistry::getFrame();

cInclude("external", "codemirror/class.codemirror.php");
cInclude("includes", "functions.file.php");

$sFileType = "html";
$module = new cApiModule($idmod);

$readOnly = (getEffectiveSetting('client', 'readonly', 'false') === 'true');
if ($readOnly) {
    cRegistry::addWarningMessage(i18n('This area is read only! The administrator disabled edits!'));
}

$sActionCreate = 'htmltpl_create';
$sActionEdit = 'htmltpl_edit';

$requestFile = $_REQUEST['file'] ?? '';
$requestTmpFile = $_REQUEST['tmp_file'] ?? '';
$requestNew = $_REQUEST['new'] ?? '';
$requestDelete = $_REQUEST['delete'] ?? '';
$requestSelectedFile = $_REQUEST['selectedFile'] ?? '';
$requestStatus = $_REQUEST['status'] ?? '';

$page = new cGuiPage("mod_template");
$tpl->reset();

if (!$perm->have_perm_area_action($area, $sActionEdit)) {
    $page->displayCriticalError(i18n("Permission denied"));
    $page->render();
    return;
}

// display critical error if no valid client is selected
if ($client < 1) {
    $page->displayCriticalError(i18n("No Client selected"));
    $page->render();
    return;
}

$page->displayInfo(i18n('Edit file') . " &quot;". conHtmlSpecialChars($module->get('name')) . "&quot;");

$moduleTemplateHandler = new cModuleTemplateHandler($idmod, $page);
$moduleTemplateHandler->checkWritePermissions();
$moduleTemplateHandler->setAction($sActionEdit);
if (isset($_REQUEST['code'])) {
    if (true === cRegistry::getConfigValue('simulate_magic_quotes')) {
        $moduleTemplateHandler->setCode($_REQUEST['code']);
    } else {
        $moduleTemplateHandler->setCode(stripslashes($_REQUEST['code']));
    }
}
if (true === cRegistry::getConfigValue('simulate_magic_quotes')) {
    $moduleTemplateHandler->setFiles($requestFile, $requestTmpFile);
    $moduleTemplateHandler->setFrameIdmodArea($frame, $idmod, $area);
    $moduleTemplateHandler->setNewDelete($requestNew, $requestDelete);
    $moduleTemplateHandler->setSelectedFile($requestSelectedFile);
    $moduleTemplateHandler->setStatus($requestStatus);
} else {
    $moduleTemplateHandler->setFiles(stripslashes($requestFile), stripslashes($requestTmpFile));
    $moduleTemplateHandler->setFrameIdmodArea($frame, $idmod, $area);
    $moduleTemplateHandler->setNewDelete(stripslashes($requestNew), stripslashes($requestDelete));
    $moduleTemplateHandler->setSelectedFile(stripslashes($requestSelectedFile));
    $moduleTemplateHandler->setStatus(stripslashes($requestStatus));
}
$moduleTemplateHandler->display($perm, $notification, $belang, $readOnly);

$page->render();
