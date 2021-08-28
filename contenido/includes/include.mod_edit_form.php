<?php

/**
 * This file contains the backend page for editing modules.
 *
 * @package Core
 * @subpackage Backend
 * @author Olaf Niemann
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude("includes", "functions.upl.php");
cInclude("external", "codemirror/class.codemirror.php");

$idmod = isset($_REQUEST['idmod']) ? cSecurity::toInteger($_REQUEST['idmod']) : 0;
$mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : '';
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

$readOnly = (getEffectiveSetting("client", "readonly", "false") == "true");
$optionDebugRows = getEffectiveSetting("modules", "show-debug-rows", "never");

if ($readOnly && $action != "mod_edit" && $action != "mod_sync") {
    cRegistry::addWarningMessage(i18n("This area is read only! The administrator disabled edits!"));
}

$contenidoModuleHandler = new cModuleHandler($idmod);

if (!$readOnly && $action == 'mod_delete') {
    if (!$perm->have_perm_area_action_anyitem($area, $action)) {
        cRegistry::addErrorMessage(i18n("No permissions"));
        $page = new cGuiPage('generic_page');
        $page->abortRendering();
        $page->render();
        die();
    } else {
        $modules = new cApiModuleCollection();
        $modules->delete($idmod);

        // Delete version modules
        $moduleVersion = new cVersionModule($idmod, $cfg, $cfgClient, $db, $client, $area, $frame);
        if ($moduleVersion->getRevisionFiles() > 0) {
            $moduleVersion->deleteFile();
        }

        // show success message
        cRegistry::addOkMessage(i18n("Module was successfully deleted!"));
        $page = new cGuiPage('generic_page');

        $contenidoModuleHandler->eraseModule();

        // remove the navigation when module has been deleted
        $script = new cHTMLScript();
        $script->setContent('$(function() { $("#navlist", Con.getFrame("right_top").document).remove(); })');
        $page->setContent(array(
            $div
        ));
        // Reload, so that the modules overview on the left is refreshed
        $page->reloadLeftBottomFrame(['idmod' => null]);
        $page->render();
        exit();
    }
}

if ($action == "mod_sync") {
    if (!$perm->have_perm_area_action_anyitem($area, $action)) {
        cRegistry::addErrorMessage(i18n("No permissions"));
        $page = new cGuiPage('generic_page');
        $page->abortRendering();
        $page->render();
        die();
    } else {
        $cModuleSynchronizer = new cModuleSynchronizer();
        $idmod = $cModuleSynchronizer->synchronize();

        $idmodUpdate = $cModuleSynchronizer->compareFileAndModuleTimestamp();

        // if a module is deleted in filesystem but not in db make an update
        // idmodUpdate = $cModuleSynchronizer->updateDirFromModuls();
        // e need the idmod for refresh all frames
        if ($idmod == 0 && $idmodUpdate != 0) {
            $idmod = $idmodUpdate;
        }

        // the actual module is the last module from synchronize
        $contenidoModuleHandler = new cModuleHandler($idmod);
    }
}

if (!$readOnly && $action == "mod_new") {
    if (!$perm->have_perm_area_action_anyitem($area, $action)) {
        cRegistry::addErrorMessage(i18n("No permissions"));
        $page = new cGuiPage('generic_page');
        $page->abortRendering();
        $page->render();
        die();
    } else {
        $modules = new cApiModuleCollection();

        $alias = cString::cleanURLCharacters(i18n("- Unnamed module -"));
        $contenidoModuleHandler = new cModuleHandler();
        if ($contenidoModuleHandler->modulePathExistsInDirectory($alias)) {
            cRegistry::addErrorMessage(i18n("The given module name already exists. Please enter another module name."));
            $page = new cGuiPage('generic_page');
            $page->abortRendering();
            $page->render();
            die();
        }

        $module = $modules->create(i18n("- Unnamed module -"));
        $module->set("alias", cString::toLowerCase($alias));

        $module->store();
        // save into the file
        $contenidoModuleHandler = new cModuleHandler($module->get("idmod"));

        if ($contenidoModuleHandler->createModule() == false) {
            cRegistry::addErrorMessage(i18n("Unable to create a new module!"));
            $page = new cGuiPage('generic_page');
            $page->abortRendering();
            $page->render();
            die();
        } else {
            cRegistry::addOkMessage(i18n("New module created successfuly!"));
        }
    }
} else {
    $module = new cApiModule($idmod);
}

if (!$readOnly && $action == "mod_importexport_module") {
    if ($mode == "export") {
        $module->export();
    }
    if ($mode == "import") {
        if (cFileHandler::exists($_FILES["upload"]["tmp_name"])) {
            if (!$module->import($_FILES['upload']['name'], $_FILES["upload"]["tmp_name"])) {
                cRegistry::addErrorMessage(i18n("Could not import module!"));
            } else {
                // Load the item again (clearing slashes from import)
                $module->loadByPrimaryKey($module->get($module->getPrimaryKeyName()));
                $contenidoModuleHandler = new cModuleHandler($module->get('idmod'));
            }
        }
    }
}

$idmod = $module->get("idmod");

// Check correct module Id
if (!$idmod) {
    $page = new cGuiPage('generic_page');
    $page->reloadLeftBottomFrame(['idmod' => null]);
    $page->abortRendering();
    $page->render();
    die();
}

if (!$perm->have_perm_area_action_item("mod_edit", "mod_edit", $idmod)) {
    $link = new cHTMLLink();
    $link->setCLink("mod_translate", 4, "");
    $link->setCustom("idmod", $idmod);
    header("Location: " . $link->getHref());
} else {
    $oInUse = new cApiInUseCollection();
    list($bInUse, $message) = $oInUse->checkAndMark("idmod", $idmod, true, i18n("Module is in use by %s (%s)"), true, "main.php?area=$area&frame=$frame&idmod=$idmod");
    unset($oInUse);

    if ($bInUse == true) {
        $message .= "<br>";
        $disabled = true;
    } else {
        $disabled = false;
    }

    $page = new cGuiPage("mod_edit_form", "", "0");
    $form = new cGuiTableForm("frm_mod_edit");
    $form->setTableID('mod_edit');
    $form->setVar("area", "mod_edit");
    $form->setVar("frame", $frame);
    $form->setVar("idmod", $idmod);
    //$page->setSubnav('action=' . $action);
    if (!$bInUse) {
        $form->setVar("action", "mod_edit");
    }

    $form->addHeader(i18n("Edit module") . " &quot;". conHtmlSpecialChars($module->get('name')). "&quot;");

    $name = new cHTMLTextbox("name", conHtmlSpecialChars(stripslashes($module->get("name"))), 60);
    $descr = new cHTMLTextarea("descr", str_replace(array(
        '\r\n'
    ), "\r\n", conHtmlentities($module->get("description"))), 100, 5);

    // Get input and output code; if specified, prepare row fields
    $sInputData = "";
    $sOutputData = "";

    // Check write permissions
    if ($contenidoModuleHandler->moduleWriteable('php') == false) {
        cRegistry::addWarningMessage(i18n("You have no write permissions for this module"));
    }

    // Read the input and output for the editing in Backend from file
    if ($contenidoModuleHandler->modulePathExists() == true) {
        $sInputData = $contenidoModuleHandler->readInput(true);
        $sOutputData = $contenidoModuleHandler->readOutput(true);
    }

    if ($optionDebugRows !== "never") {
        // +2: Just sanity, to have at least two more lines than the code
        $iInputNewLines = cString::countSubstring($sInputData, "\n") + 2;
        $iOutputNewLines = cString::countSubstring($sOutputData, "\n") + 2;
        // +2: Just sanity, to have at least two more lines than the code have at
        // least 15 + 2 lines (15 = code textarea lines count)
        if ($iInputNewLines < 21) {
            $iInputNewLines = 21;
        }
        if ($iOutputNewLines < 21) {
            $iOutputNewLines = 21;
        }

        // Calculate how many characters are needed (e.g. 2 for lines ip to 99)
        $iInputNewLineChars = cString::getStringLength($iInputNewLines);
        $iOutputNewLineChars = cString::getStringLength($iOutputNewLines);
        if ($iInputNewLineChars > $iOutputNewLineChars) {
            $iChars = $iInputNewLineChars;
        } else {
            $iChars = $iOutputNewLineChars;
        }
        unset($iInputNewLineChars, $iOutputNewLineChars);

        $sRows = "";
        for ($i = 1; $i <= $iInputNewLines; $i++) {
            if ($sRows) {
                $sRows .= "\r\n"; // why windows line feed???
            }
            $sRows .= sprintf("%0" . $iChars . "d", $i);
        }
        $oInputRows = new cHTMLTextarea("txtInputRows", $sRows, $iChars, 20);

        $sRows = "";
        for ($i = 1; $i <= $iOutputNewLines; $i++) {
            if ($sRows) {
                $sRows .= "\r\n"; // why windows line feed???
            }
            $sRows .= sprintf("%0" . $iChars . "d", $i);
        }
        $oOutputRows = new cHTMLTextarea("txtOutputRows", $sRows, $iChars, 20);

        $oInputRows->updateAttributes(array(
            "wrap" => "off"
        ));
        $oOutputRows->updateAttributes(array(
            "wrap" => "off"
        ));

        $oInputRows->updateAttributes(array(
            "readonly" => "true"
        ));
        $oOutputRows->updateAttributes(array(
            "readonly" => "true"
        ));

        $oInputRows->setStyle("font-family: monospace;");
        $oOutputRows->setStyle("font-family: monospace;");
        $oOutputRows->setStyle("font-family: monospace;");
    }

    $input = new cHTMLTextarea("input", $sInputData, 100, 20, 'input');
    $output = new cHTMLTextarea("output", $sOutputData, 100, 20, 'output');

    // Style the fields
    $input->updateAttributes(array(
        "wrap" => "off"
    ));
    $output->updateAttributes(array(
        "wrap" => "off"
    ));

    $name->setDisabled($disabled);
    $descr->setDisabled($disabled);

    $descr->setStyle("width: 100%; font-family: monospace;");
    $input->setStyle("width: 100%; font-family: monospace;");
    $output->setStyle("width: 100%; font-family: monospace;");

    // Check, if tabs may be inserted in text areas (instead jumping to next
    // element)
    if (getEffectiveSetting("modules", "edit-with-tabs", "false") == "true") {
        $input->setEvent("onkeydown", "return insertTab(event, this);");
        $output->setEvent("onkeydown", "return insertTab(event, this);");
    }

    // Prepare type select box
    $typeSelect = new cHTMLSelectElement("type");

    $oModuleColl = new cApiModuleCollection();
    $aTypes = $oModuleColl->getAllTypesByIdclient($client);

    // Read existing layouts
    $oLayouts = new cApiLayoutCollection();
    $oLayouts->setWhere("idclient", $client);
    $oLayouts->query();

    while ($oLayout = $oLayouts->next()) {
        $aTypes = array_merge(explode(";", $oLayout->getProperty("layout", "used-types")), $aTypes);
    }
    $aTypes = array_unique($aTypes);

    $typeArray = [];
    foreach ($aTypes as $sType) {
        $typeArray[$sType] = $sType;
    }
    unset($aTypes);

    if (count($typeArray) > 0) {
        asort($typeArray);
        $typeSelect->autoFill(array_merge(array(
            "" => "-- " . i18n("Custom") . " --"
        ), $typeArray));
    } else {
        $typeSelect->autoFill(array(
            "" => "-- " . i18n("Custom") . " --"
        ));
    }

    $typeSelect->setEvent("change", "if (document.forms['frm_mod_edit'].elements['type'].value == 0) { document.forms['frm_mod_edit'].elements['customtype'].disabled=0;} else {document.forms['frm_mod_edit'].elements['customtype'].disabled=1;}");
    $typeSelect->setDisabled($disabled);

    $custom = new cHTMLTextbox("customtype", "");
    $custom->setDisabled($disabled);

    if ($module->get("type") == "" || $module->get("type") == "0") {
        $typeSelect->setDefault("0");
    } else {
        $typeSelect->setDefault($module->get("type"));
        $custom->setDisabled(true);
    }

    $modulecheck = getSystemProperty("system", "modulecheck");
    $isCodeError = $module->get('error');
    if ($modulecheck !== "false") {
        $outled = '<img style="float: right;" src="images/ajax-loader_16x16.gif" class="outputok" alt="" title="" data-state="' . htmlentities($isCodeError) . '">';
        $inled  = '<img style="float: right;" src="images/ajax-loader_16x16.gif" class="inputok" alt="" title="" data-state="' . htmlentities($isCodeError) . '">';
    }

    if ($readOnly) {
        $name->setDisabled(true);
        $descr->setDisabled(true);
        $typeSelect->setDisabled(true);
        $custom->setDisabled(true);
    }

    $form->add(i18n("Name"), $name->render());
    $form->add(i18n("Type"), $typeSelect->render() . $custom->render());
    $form->add(i18n("Description"), $descr->render());

    if ($optionDebugRows == "always" || ($optionDebugRows == "onerror" && $isCodeError !== 'none')) {
        $form->add(i18n("Input") . $inled . $oInputRows->render(), $input->render());
        $form->add(i18n("Output") . $outled . $oOutputRows->render(), $output->render());
    } else {
        $form->add('<div style="float: left;">' . i18n("Input") . '</div>' . $inled, $input->render());
        $form->add('<div style="float: left;">' . i18n("Output") . '</div>' . $outled, $output->render());
    }

    if ($module->isOldModule()) {
        cRegistry::addWarningMessage(i18n("This module uses variables and/or functions which are probably not available in this CONTENIDO version. Please make sure that you use up-to-date modules."));
    }

    if ($idmod != 0) {
        $oCodeMirrorInput = new CodeMirror('input', 'php', cString::getPartOfString(cString::toLowerCase($belang), 0, 2), true, $cfg, !$bInUse);
        $oCodeMirrorOutput = new CodeMirror('output', 'php', cString::getPartOfString(cString::toLowerCase($belang), 0, 2), false, $cfg, !$bInUse);

        if ($readOnly || $bInUse) {
            $oCodeMirrorInput->setProperty("readOnly", "true");
            $oCodeMirrorOutput->setProperty("readOnly", "true");

            $form->setActionButton('submit', cRegistry::getBackendUrl() . 'images/but_ok_off.gif', i18n('Overwriting files is disabled'), 's');
        }

        $codeMirrorScripts = trim($oCodeMirrorInput->renderScript() . $oCodeMirrorOutput->renderScript());
        if (!empty($codeMirrorScripts)) {
            $page->addScript($codeMirrorScripts);
        }

        // dont print menu
        if ($action == "mod_sync") {
            $page->set("s", "FORM", "");
            //$page->setSubnav("idmod=" . $idmod . "&dont_print_subnav=1");
        } else {
            //$page->setSubnav("idmod=" . $idmod, "mod");
        }
        // Dont show form if we delete or synchronize a module
        if ($action == "mod_sync" || $action == "mod_delete") {
            $page->abortRendering();
        } else {
            $page->set("s", "FORM", $message . $form->render() . "<br>");
        }

        if ($action == "mod_sync") {
            $page->reloadLeftBottomFrame(['idmod' => null]);
        } else {
            $page->reloadLeftBottomFrame(['idmod' => $idmod]);
        }

        $page->render();
    }
}
