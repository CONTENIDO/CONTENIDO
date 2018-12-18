<?php

/**
 * This file contains the backend page for the form of template pre configuration.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Olaf Niemann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$tpl->reset();

//Form
$formaction = $sess->url("main.php");
// <input type="hidden" name="action" value="tplcfg_edit">
$hidden     = '<input type="hidden" name="area" value="tpl_cfg">
               <input type="hidden" name="frame" value="'.$frame.'">
               <input type="hidden" name="idcat" value="'.$idcat.'">
               <input type="hidden" name="idart" value="'.$idart.'">
               <input type="hidden" name="idtpl" value="'.$idtpl.'">
               <input type="hidden" name="lang" value="'.$lang.'">
               <input type="hidden" name="idtplcfg" value="'.$idtplcfg.'">
               <input type="hidden" name="changetemplate" value="0">';

$tpl->set('s', 'FORMACTION', $formaction);
$tpl->set('s', 'HIDDEN', $hidden);

$templateItem = new cApiTemplate((int) $idtpl);

$tpl->set('s', 'TEMPLATECAPTION', i18n("Template"). ": ");
$tpl->set('s', 'TEMPLATESELECTBOX', $templateItem->get('name'));

$tpl->set('s', 'LABLE_DESCRIPTION', i18n('Description'));
$tpl->set('s', 'DESCRIPTION', nl2br($templateItem->get('description')));

// List of configured container
$containerConfigurations = conGetContainerConfiguration($idtplcfg);

// List of used modules in container
$containerModules = conGetUsedModules($idtpl);

foreach ($containerModules as $containerNumber => $containerModuleId) {
    // Show only the container which contains a module
    if (0 == $containerModuleId) {
        continue;
    }

    $moduleItem = new cApiModule($containerModuleId);
    if (!$moduleItem->isLoaded()) {
        continue;
    }

    global $cCurrentModule, $cCurrentContainer;
    $cCurrentModule = $containerModuleId;
    $cCurrentContainer = $containerNumber;

    $input = "\n";

    // Read the input for the editing in Backend from file
    $contenidoModuleHandler = new cModuleHandler($containerModuleId);
    if ($contenidoModuleHandler->modulePathExists() == true) {
        $input = $contenidoModuleHandler->readInput() . "\n";
    }

    $modulecode = cApiModule::processContainerInputCode($containerNumber, $containerConfigurations[$containerNumber], $input);

    ob_start();
    eval($modulecode);
    $modulecode = ob_get_contents();
    ob_end_clean();

    $modulecaption = sprintf(i18n("Module in Container %s"), $containerNumber);

    $tpl->set('d', 'MODULECAPTION', $modulecaption);
    $tpl->set('d', 'MODULENAME', $moduleItem->get('name'));
    $tpl->set('d', 'MODULECODE', $modulecode);
    $tpl->next();
}

$tpl->set('s', 'SCRIPT', '');
$tpl->set('s', 'MARKSUBMENU', '');
$tpl->set('s', 'CATEGORY', '');

$tpl->set('s', 'HEADER', i18n('Template preconfiguration'));
$tpl->set('s', 'DISPLAY_HEADER', 'block');

$buttons = '<a href="javascript:history.back()"><img src="images/but_cancel.gif" alt="" border="0"></a>&nbsp;&nbsp;&nbsp;&nbsp;
            <input type="image" alt="" src="images/but_ok.gif">';

$tpl->set('s', 'BUTTONS', $buttons);

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['tplcfg_edit_form']);

?>