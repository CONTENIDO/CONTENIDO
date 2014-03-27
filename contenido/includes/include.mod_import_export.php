<?php
/**
 * This file contains the backend page for importing and exporting modules.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Olaf Niemann, Jan Lengowski
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$page = new cGuiPage("mod_import_export");

$module = new cApiModule();
$module->loadByPrimaryKey($idmod);
$notification = new cGuiNotification();
$reloadLeftBottom = false;

$readOnly = (getEffectiveSetting("client", "readonly", "false") == "true");

if($readOnly) {
    cRegistry::addWarningMessage(i18n('This area is read only! The administrator disabled edits!'));
}

if ($action == "mod_importexport_module") {

    switch ($mode) {
        case 'export':
            if ($idmod != 0) {
                $module->export();
            } else {
                $notification->displayNotification('error', i18n("Could not export module!"));
            }
            break;
        case 'import':
            if($readOnly) {
                cRegistry::addWarningMessage(i18n("This area is read only! The administrator disabled edits!"));
                break;
            }
            if (cFileHandler::exists($_FILES["upload"]["tmp_name"])) {
                if (!$module->import($_FILES['upload']['name'], $_FILES["upload"]["tmp_name"])) {
                    $notification->displayNotification('error', i18n("Could not import module!"));
                } else {
                    $notification->displayNotification('info', i18n("Module import successfully!"));
                    $idmod = $module->get('idmod');
                    $reloadLeftBottom = true;
                }
            } else {
                $notification->displayNotification('error', i18n("No file uploaded!"));
            }
            break;
        case 'import_xml':
            if($readOnly) {
                cRegistry::addWarningMessage(i18n("This area is read only! The administrator disabled edits!"));
                break;
            }
            // Make new module
            $modules = new cApiModuleCollection();

            if (cFileHandler::exists($_FILES["upload"]["tmp_name"])) {
                $modulName = substr($_FILES['upload']['name'], 0, -4);

                $module = $modules->create($modulName);
                if (!$module->importModuleFromXML($_FILES["upload"]["tmp_name"])) {
                    $notification->displayNotification('error', i18n("Could not import module!"));
                    $modules->delete($module->get('idmod'));
                } else {
                    $notification->displayNotification('info', i18n("Module import successfully!"));
                    $idmod = $module->get('idmod');
                    $reloadLeftBottom = true;
                }
            } else {
                $notification->displayNotification('error', i18n("No file uploaded!"));
            }
            break;
    }
}

$import = new cHTMLRadiobutton("mode", "import");
$importXML = new cHTMLRadiobutton('mode', 'import_xml');
$export = new cHTMLRadiobutton("mode", "export");

$import->setLabelText(i18n("Import from ZIP file"));
$importXML->setLabelText(i18n("Import from XML file"));
$export->setLabelText(i18n("Export to ZIP file"));

$export->setEvent("onclick", "$('#vupload').hide()");
$importXML->setEvent("onclick", "$('#vupload').show()");
$import->setEvent("onclick", "$('#vupload').show()");

$upload = new cHTMLUpload("upload");

$inputChecked = "";
$outputChecked = "";

if ($inputChecked != "" && $outputChecked != "") {
    $export->setChecked("checked");
} else {
    $import->setChecked("checked");
}

if($readOnly) {
    $import->setDisabled('disabled');
    $importXML->setDisabled('disabled');
    $export->setChecked('checked');
    $import->setChecked('');
    $importXML->setChecked('');
}

$form2 = new cGuiTableForm("export");
$form2->setVar("action", "mod_importexport_module");
$form2->setVar("use_encoding", "false");
$form2->addHeader("Import/Export");
$form2->add(i18n("Mode"), array(
    $export,
    "<br>",
    $import,
    '<br>',
    $importXML
));

if ($inputChecked != "" && $outputChecked != "") {
    $form2->add(i18n("File"), $upload, "vupload", "display: none;");
} else {
    $form2->add(i18n("File"), $upload, "vupload");
}

$form2->setVar("area", $area);
$form2->setVar("frame", $frame);
$form2->setVar("idmod", $idmod);
$form2->custom["submit"]["accesskey"] = '';

if ($reloadLeftBottom) {
    $page->reloadFrame('left_bottom', array(
        "idmod" => $idmod
    ));
}
$page->setContent(array(
    $form2
));

$page->render();

?>