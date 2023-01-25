<?php

/**
 *
 * @package Plugin
 * @subpackage FormAssistant
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $cfg, $lngAct;

$pluginName = basename(dirname(__DIR__, 1));
plugin_include($pluginName, 'classes/class.pifa.php');

$pluginName = Pifa::getName();

// define plugin path
$cfg['plugins'][$pluginName] = Pifa::getPath();

// define template names
$pluginTemplatesPath = cRegistry::getBackendPath() . $cfg['path']['plugins'] . "$pluginName/templates";
// $cfg['templates']['form_left_bottom'] = $cfg['plugins']['form'] . 'templates/template.left_bottom.html';
$cfg['templates']['pifa_right_bottom_form'] = $pluginTemplatesPath . '/template.right_bottom_form.tpl';
$cfg['templates']['pifa_right_bottom_fields'] = $pluginTemplatesPath . '/template.right_bottom_fields.tpl';
$cfg['templates']['pifa_right_bottom_data'] = $pluginTemplatesPath . '/template.right_bottom_data.tpl';
$cfg['templates']['pifa_right_bottom_export'] = $pluginTemplatesPath . '/template.right_bottom_export.tpl';
$cfg['templates']['pifa_right_bottom_import'] = $pluginTemplatesPath . '/template.right_bottom_import.tpl';
$cfg['templates']['pifa_ajax_field_form'] = $pluginTemplatesPath . '/template.ajax_field_form.tpl';
$cfg['templates']['pifa_ajax_field_row'] = $pluginTemplatesPath . '/template.ajax_field_row.tpl';
$cfg['templates']['pifa_ajax_option_row'] = $pluginTemplatesPath . '/template.ajax_option_row.tpl';

// define table names
$cfg['tab']['pifa_form'] = $cfg['sql']['sqlprefix'] . '_pifa_form';
$cfg['tab']['pifa_field'] = $cfg['sql']['sqlprefix'] . '_pifa_field';

// define action translations
$lngAct['form']['pifa_show_form'] = Pifa::i18n('pifa_show_form');
$lngAct['form']['pifa_store_form'] = Pifa::i18n('pifa_store_form');
$lngAct['form']['pifa_delete_form'] = Pifa::i18n('pifa_delete_form');
$lngAct['form_fields']['pifa_show_fields'] = Pifa::i18n('pifa_show_fields');
$lngAct['form_data']['pifa_show_data'] = Pifa::i18n('pifa_show_data');
$lngAct['form_import']['pifa_import_form'] = Pifa::i18n('pifa_import_form');
$lngAct['form_ajax']['pifa_export_form'] = Pifa::i18n('pifa_export_form');
$lngAct['form_ajax']['pifa_get_field_form'] = Pifa::i18n('pifa_get_field_form');
$lngAct['form_ajax']['pifa_post_field_form'] = Pifa::i18n('pifa_post_field_form');
$lngAct['form_ajax']['pifa_reorder_fields'] = Pifa::i18n('pifa_reorder_fields');
$lngAct['form_ajax']['pifa_export_data'] = Pifa::i18n('pifa_export_data');
$lngAct['form_ajax']['pifa_get_file'] = Pifa::i18n('pifa_get_file');
$lngAct['form_ajax']['pifa_delete_field'] = Pifa::i18n('pifa_delete_field');
$lngAct['form_ajax']['pifa_delete_data'] = Pifa::i18n('pifa_delete_data');
$lngAct['form_ajax']['pifa_get_option_row'] = Pifa::i18n('pifa_get_option_row');

// Setup autoloader for plugin
$pluginClassesPath = cRegistry::getBackendPath(true) . $cfg['path']['plugins'] . "$pluginName/classes";
cAutoload::addClassmapConfig([
    'Pifa' => $pluginClassesPath . '/class.pifa.php',
    'cContentTypePifaForm' => $pluginClassesPath . '/class.content.type.pifa_form.php',
    'PifaExternalOptionsDatasourceInterface' => $pluginClassesPath . '/class.pifa.external_options_datasource_interface.php',
    'PifaExporter' => $pluginClassesPath . '/class.pifa.exporter.php',
    'PifaImporter' => $pluginClassesPath . '/class.pifa.importer.php',
    'PifaLeftBottomPage' => $pluginClassesPath . '/class.pifa.gui.php',
    'PifaRightBottomFormPage' => $pluginClassesPath . '/class.pifa.gui.php',
    'PifaRightBottomFormFieldsPage' => $pluginClassesPath . '/class.pifa.gui.php',
    'PifaRightBottomFormDataPage' => $pluginClassesPath . '/class.pifa.gui.php',
    'PifaRightBottomFormExportPage' => $pluginClassesPath . '/class.pifa.gui.php',
    'PifaRightBottomFormImportPage' => $pluginClassesPath . '/class.pifa.gui.php',
    'PifaFormCollection' => $pluginClassesPath . '/class.pifa.form.php',
    'PifaForm' => $pluginClassesPath . '/class.pifa.form.php',
    'PifaFieldCollection' => $pluginClassesPath . '/class.pifa.field.php',
    'PifaField' => $pluginClassesPath . '/class.pifa.field.php',
    'PifaAbstractFormModule' => $pluginClassesPath . '/class.pifa.abstract_form_module.php',
    'PifaAbstractFormProcessor' => $pluginClassesPath . '/class.pifa.abstract_form_processor.php',
    'PifaAjaxHandler' => $pluginClassesPath . '/class.pifa.ajax_handler.php',
    'PifaException' => $pluginClassesPath . '/class.pifa.exceptions.php',
    'PifaDatabaseException' => $pluginClassesPath . '/class.pifa.exceptions.php',
    'PifaNotImplementedException' => $pluginClassesPath . '/class.pifa.exceptions.php',
    'PifaIllegalStateException' => $pluginClassesPath . '/class.pifa.exceptions.php',
    'PifaNotYetStoredException' => $pluginClassesPath . '/class.pifa.exceptions.php',
    'PifaValidationException' => $pluginClassesPath . '/class.pifa.exceptions.php',
    'PifaMailException' => $pluginClassesPath . '/class.pifa.exceptions.php'
]);

unset($pluginName, $pluginTemplatesPath, $pluginClassesPath);