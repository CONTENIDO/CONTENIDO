<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Import and export of modules
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.2
 * @author     Olaf Niemann, Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release >=4.9
 *
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

$page = new cPage();

$module = new cApiModule();
$module->loadByPrimaryKey($idmod);
$notification = new Contenido_Notification();

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
                if (!$module->import($_FILES['upload']['name'] , $_FILES["upload"]["tmp_name"])) {
                    $notification-> displayNotification('error',i18n("Could not import module!"));
                } else {
                    $notification->displayNotification('info', i18n("Module import successfully!"));
                    $idmod = $module->get('idmod');
                    $sScript = "<script type=\"text/javascript\">
                                         var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
                                         if (left_bottom) {
                                             var href = left_bottom.location.href;
                                             href = href.replace(/&idmod[^&]*/, '');
                                             left_bottom.location.href = href+'&idmod='+'".$idmod."';
                                         }
                                </script>";
                }
            } else {
                $notification-> displayNotification('error',i18n("No file uploaded!"));
            }
            break;
        case 'import_xml':
            // Make new module
            $modules = new cApiModuleCollection();

            if (cFileHandler::exists($_FILES["upload"]["tmp_name"])) {
                $modulName = substr($_FILES['upload']['name'], 0, -4);

                $module = $modules->create($modulName);
                if (!$module->importModuleFromXML($_FILES["upload"]["tmp_name"])) {
                    $notification-> displayNotification('error',i18n("Could not import module!"));
                } else {
                    $notification->displayNotification('info', i18n("Module import successfully!"));
                    $idmod = $module->get('idmod');
                    $sScript = "<script type=\"text/javascript\">
                                         var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
                                         if (left_bottom) {
                                             var href = left_bottom.location.href;
                                             href = href.replace(/&idmod[^&]*/, '');
                                             left_bottom.location.href = href+'&idmod='+'".$idmod."';
                                         }
                                </script>";
                }
            } else {
                $notification-> displayNotification('error',i18n("No file uploaded!"));
            }
            break;
    }
}


$import = new cHTMLRadiobutton("mode", "import");
$importXML = new cHTMLRadiobutton('mode', 'import_xml');
$export = new cHTMLRadiobutton("mode", "export");

$import->setLabelText(i18n("Import from zip-file"));
$importXML->setLabelText(i18n("Import from xml-file"));
$export->setLabelText(i18n("Export to file"));


$upload = new cHTMLUpload("upload");

$inputChecked = "";
$outputChecked = "";

if ($inputChecked != "" && $outputChecked != "") {
    $export->setChecked("checked");
} else {
    $import->setChecked("checked");
}
$form2 = new UI_Table_Form("export");
$form2->setVar("action", "mod_importexport_module");
$form2->setVar("use_encoding", "false");
$form2->addHeader("Import/Export");
$form2->add(i18n("Mode"), array($export, "<br>", $import,'<br>',$importXML));


if ($inputChecked != "" && $outputChecked != "") {
    $form2->add(i18n("File"), $upload, "vupload", "display: none;");
} else {
    $form2->add(i18n("File"), $upload, "vupload");
}


$form2->setVar("area", $area);
$form2->setVar("frame", $frame);
$form2->setVar("idmod", $idmod);
$form2->custom["submit"]["accesskey"] = '';


$page->setContent($form2->render().$sScript);


$page->render();

?>