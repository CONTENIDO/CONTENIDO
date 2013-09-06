<?php
/**
 * This file contains the backend page for importing and exporting modules.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Olaf Niemann, Jan Lengowski
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$page = new cGuiPage("mod_import_export");

$module = new cApiModule();
$module->loadByPrimaryKey($idmod);
$notification = new cGuiNotification();
$sScript = '';

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
            if (cFileHandler::exists($_FILES["upload"]["tmp_name"])) {
                if (!$module->import($_FILES['upload']['name'], $_FILES["upload"]["tmp_name"])) {
                    $notification->displayNotification('error', i18n("Could not import module!"));
                } else {
                    $notification->displayNotification('info', i18n("Module import successfully!"));
                    $idmod = $module->get('idmod');
                    $sScript = "<script type=\"text/javascript\">
                                         var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
                                         if (left_bottom) {
                                             var href = left_bottom.location.href;
                                             href = href.replace(/&idmod[^&]*/, '');
                                             left_bottom.location.href = href+'&idmod='+'" . $idmod . "';
                                         }
                                </script>";
                }
            } else {
                $notification->displayNotification('error', i18n("No file uploaded!"));
            }
            break;
        case 'import_xml':
            // Make new module
            $modules = new cApiModuleCollection();

            if (cFileHandler::exists($_FILES["upload"]["tmp_name"])) {
                $modulName = substr($_FILES['upload']['name'], 0, -4);

                $module = $modules->create($modulName);
                if (!$module->importModuleFromXML($_FILES["upload"]["tmp_name"])) {
                    $notification->displayNotification('error', i18n("Could not import module!"));
                    $module->delete();
                } else {
                    $notification->displayNotification('info', i18n("Module import successfully!"));
                    $idmod = $module->get('idmod');
                    $sScript = "<script type=\"text/javascript\">
                                         var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
                                         if (left_bottom) {
                                             var href = left_bottom.location.href;
                                             href = href.replace(/&idmod[^&]*/, '');
                                             left_bottom.location.href = href+'&idmod='+'" . $idmod . "';
                                         }
                                </script>";
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
$form2 = new cGuiTableForm("export");
$form2->setVar("action", "mod_importexport_module");
$form2->setVar("use_encoding", "false");
$form2->addHeader("Import/Export");
$form2->add(i18n("Mode"), array($export, "<br>", $import, '<br>', $importXML));


if ($inputChecked != "" && $outputChecked != "") {
    $form2->add(i18n("File"), $upload, "vupload", "display: none;");
} else {
    $form2->add(i18n("File"), $upload, "vupload");
}


$form2->setVar("area", $area);
$form2->setVar("frame", $frame);
$form2->setVar("idmod", $idmod);
$form2->custom["submit"]["accesskey"] = '';

if (!empty($sScript)) {
    $page->addScript($sScript);
}
$page->setContent(array($form2));

$page->render();

?>