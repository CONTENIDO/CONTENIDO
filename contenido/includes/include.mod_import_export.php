<?php

/**
 * This file contains the backend page for importing and exporting modules.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Olaf Niemann
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var string $area
 * @var int $frame
 */

$page = new cGuiPage("mod_import_export");

$requestIdMod = cSecurity::toInteger($_REQUEST['idmod'] ?? '0');
$requestMode = $_REQUEST['mode'] ?? '';
$action = $action ?? '';

$module = new cApiModule();
if ($requestIdMod > 0) {
    $module->loadByPrimaryKey($requestIdMod);
}
$notification = new cGuiNotification();
$reloadLeftBottom = false;

$readOnly = (getEffectiveSetting('client', 'readonly', 'false') === 'true');
if ($readOnly) {
    cRegistry::addWarningMessage(i18n('This area is read only! The administrator disabled edits!'));
}

if ($action == "mod_importexport_module") {

    switch ($requestMode) {
        case 'export':
            if ($requestIdMod > 0) {
                $module->export();
            } else {
                $notification->displayNotification('error', i18n("Could not export module!"));
            }
            break;
        case 'import':
            if ($readOnly) {
                cRegistry::addWarningMessage(i18n("This area is read only! The administrator disabled edits!"));
                break;
            }
            if (cFileHandler::exists($_FILES["upload"]["tmp_name"])) {
                if (!$module->import($_FILES['upload']['name'], $_FILES["upload"]["tmp_name"])) {
                    $notification->displayNotification('error', i18n("Could not import module!"));
                } else {
                    $notification->displayNotification('info', i18n("Module import successfully!"));
                    $requestIdMod = $module->get('idmod');
                    $reloadLeftBottom = true;
                }
            } else {
                $notification->displayNotification('error', i18n("No file uploaded!"));
            }
            break;
        case 'import_xml':
            if ($readOnly) {
                cRegistry::addWarningMessage(i18n("This area is read only! The administrator disabled edits!"));
                break;
            }
            // Make new module
            $modules = new cApiModuleCollection();

            if (cFileHandler::exists($_FILES["upload"]["tmp_name"])) {
                $modulName = cString::getPartOfString($_FILES['upload']['name'], 0, -4);

                $module = $modules->create($modulName);
                if (!$module->importModuleFromXML($_FILES["upload"]["tmp_name"])) {
                    $notification->displayNotification('error', i18n("Could not import module!"));
                    $modules->delete($module->get('idmod'));
                } else {
                    $notification->displayNotification('info', i18n("Module import successfully!"));
                    $requestIdMod = $module->get('idmod');
                    $reloadLeftBottom = true;
                }
            } else {
                $notification->displayNotification('error', i18n("No file uploaded!"));
            }
            break;
    }
}

$import = new cHTMLRadiobutton("mode", "import");
$import->setLabelText(i18n("Import from ZIP file"));
$import->setEvent("onclick", "$('#vupload').css('visibility','visible')");

$importXML = new cHTMLRadiobutton('mode', 'import_xml');
$importXML->setLabelText(i18n("Import from XML file"));
$importXML->setEvent("onclick", "$('#vupload').css('visibility','visible')");

$export = new cHTMLRadiobutton("mode", "export");
$export->setLabelText(i18n("Export to ZIP file"));
$export->setEvent("onclick", "$('#vupload').css('visibility','hidden')");

$upload = new cHTMLUpload("upload");
$upload->setID('vupload');

$inputChecked = "";
$outputChecked = "";

if ($inputChecked != "" && $outputChecked != "") {
    $export->setChecked(true);
} else {
    $import->setChecked(true);
}

if ($readOnly) {
    $import->setDisabled(true);
    $importXML->setDisabled(true);
    $export->setChecked(true);
    $import->setChecked(false);
    $importXML->setChecked(false);
}

$form2 = new cGuiTableForm("export");
$form2->addTableClass('col_xs');
$form2->setVar("action", "mod_importexport_module");
$form2->setVar("use_encoding", "false");
$form2->setHeader("Import/Export" . " &quot;" . conHtmlSpecialChars($module->get('name')) . "&quot;");
$form2->add(i18n("Mode"), [
    new cHTMLDiv($export, 'mgb5'),
    new cHTMLDiv($import, 'mgb5'),
    new cHTMLDiv($importXML)
]);

if ($inputChecked != "" && $outputChecked != "") {
    $form2->add(i18n("File"), $upload, "vupload", "visibility: hidden;");
} else {
    $form2->add(i18n("File"), $upload, "vupload");
}

$form2->setVar("area", $area);
$form2->setVar("frame", $frame);
$form2->setVar("idmod", $requestIdMod);
$form2->custom["submit"]["accesskey"] = '';

if ($reloadLeftBottom) {
    $page->reloadLeftBottomFrame(['idmod' => $requestIdMod]);
}
$page->setContent([
    $form2
]);

$page->render();
