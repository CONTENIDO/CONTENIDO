<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Edit modules
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.2
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2003-01-21
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2011-02-07, Dominik Ziegler, removed integration of not supported java module editor
 *   modified 2011-01-11, Rusmir Jusufovic
 *       - save and load input/output of moduls from files
 *       - mod_sync synchronize moduls from file and moduls from db
 *
 *   modified 2011-06-22, Rusmir Jusufovic , the name of the moduls come from field alias
 *                   differnet updates (error display ...)
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude("includes", "functions.upl.php");
cInclude("external", "codemirror/class.codemirror.php");

$noti             = "";
$sOptionDebugRows = getEffectiveSetting("modules", "show-debug-rows", "never");

if (!isset($idmod)) $idmod = 0;

$contenidoModuleHandler = new cModuleHandler($idmod );
if (($action == "mod_delete") && (!$perm->have_perm_area_action_anyitem($area, $action))) {
    $notification->displayNotification("error", i18n("No permission"));
    return;
}

if ($action == "mod_delete") {

    // if erase had been successfully
    if ($contenidoModuleHandler->eraseModule() == true) {
        $modules = new cApiModuleCollection;
        $modules->delete($idmod);
        $notification->displayNotification(cGuiNotification::LEVEL_INFO, i18n("Deleted module successfully!"));

    }
}

if (($action == "mod_synch") && (!$perm->have_perm_area_action_anyitem($area, $action))) {
    $notification->displayNotification("error", i18n("No permission"));
    return;
}

if ($action == "mod_sync") {
    $contenidoModuleSynchronizer = new cModuleSynchronizer();
    $idmod = $contenidoModuleSynchronizer->synchronize();

    $idmodUpdate = $contenidoModuleSynchronizer->compareFileAndModulTimestamp();

    // if a module is deleted in filesystem but not in db make an update
    #$idmodUpdate = $contenidoModuleSynchronizer->updateDirFromModuls();
    #we need the idmod for refresh all frames
    if ($idmod == 0 &&$idmodUpdate != 0) {
        $idmod = $idmodUpdate;
    }

    // the actuly Modul is the last Modul from synchronize
    $contenidoModuleHandler = new cModuleHandler($idmod);

}

if (($action == "mod_new") && (!$perm->have_perm_area_action_anyitem($area, $action))) {
    $notification->displayNotification("error", i18n("No permission"));
    return;
}

if ($action == "mod_new") {
    $modules = new cApiModuleCollection();

    $alias = cApiStrCleanURLCharacters(i18n("- Unnamed module -"));
    $contenidoModuleHandler = new cModuleHandler();
    if ($contenidoModuleHandler->modulePathExistsInDirectory($alias)) {
        $notification->displayNotification("error", i18n("Modul name exist in module directory, rename the module."));
        die();
    }

    $module = $modules->create(i18n("- Unnamed module -"));
    $module->set("description", implode("\n", array(i18n("<your module description>"), "", i18n("Author: "), i18n("Version:"))));

    $module->set("alias",strtolower($alias));

    $module->store();
    // save into the file
    $contenidoModuleHandler = new cModuleHandler($module->get("idmod"));

    if ($contenidoModuleHandler->createModule() == false) {
         // logg error
         $notification->displayNotification("error", i18n("Cant make a new modul!"));
         die();
    } else {
         $notification->displayNotification(cGuiNotification::LEVEL_INFO, i18n("Created new module successfuly!"));
    }
} else {
    $module = new cApiModule();
    $module->loadByPrimaryKey($idmod);
}

if ($action == "mod_importexport_module") {
    if ($mode == "export") {
        $module->export();
    }
    if ($mode == "import") {
        if (cFileHandler::exists($_FILES["upload"]["tmp_name"])) {
            if (!$module->import($_FILES['upload']['name'], $_FILES["upload"]["tmp_name"])) {
                $page->displayError(i18n("Culd not import modul:"));
            } else {
                // Load the item again (clearing slashes from import)
                $module->loadByPrimaryKey($module->get($module->primaryKey));
                $contenidoModuleHandler  = new cModuleHandler($module->get('idmod'));
            }
        }
    }
}

$idmod = $module->get("idmod");

if (!$perm->have_perm_area_action_item("mod_edit", "mod_edit", $idmod)) {
    $link = new cHTMLLink();
    $link->setCLink("mod_translate", 4, "");
    $link->setCustom("idmod", $idmod);
    header("Location: ".$link->getHREF());
} else {
    $oInUse = new cApiInUseCollection();
    list($bInUse, $message) = $oInUse->checkAndMark("idmod", $idmod, true, i18n("Module is in use by %s (%s)"), true, "main.php?area=$area&frame=$frame&idmod=$idmod");
    unset($oInUse);

    if ($bInUse == true) {
        $message .= "<br>";
        $disabled = 'disabled="disabled"';
    } else {
        $disabled = "";
    }

    $page = new cGuiPage("mod_edit_form");
    $form = new cGuiTableForm("mod_edit");
    $form->setVar("area","mod_edit");
    $form->setVar("frame", $frame);
    $form->setVar("idmod", $idmod);
    $page->setSubnav('action='+$action);
    if (!$bInUse) {
        $form->setVar("action", "mod_edit");
    }

    $form->addHeader(i18n("Edit module"));

    $name  = new cHTMLTextbox("name", $module->get("name"),60);
    $descr = new cHTMLTextarea("descr", htmlspecialchars($module->get("description")), 100, 5);

    // Get input and output code; if specified, prepare row fields
    $sInputData  = "";
    $sOutputData = "";

    // Read the input and output for the editing in Backend from file
    if ($contenidoModuleHandler->modulePathExists() == true) {
        $sInputData = $contenidoModuleHandler->readInput();
        $sOutputData = $contenidoModuleHandler->readOutput();
    }

    if ($sOptionDebugRows !== "never") {
        $iInputNewLines  = substr_count($sInputData,  "\n") + 2; // +2: Just sanity, to have at least two more lines than the code
        $iOutputNewLines = substr_count($sOutputData, "\n") + 2; // +2: Just sanity, to have at least two more lines than the code

        // Have at least 15 + 2 lines (15 = code textarea lines count)
        if ($iInputNewLines < 21) {
            $iInputNewLines = 21;
        }
        if ($iOutputNewLines < 21) {
            $iOutputNewLines = 21;
        }

        // Calculate how many characters are needed (e.g. 2 for lines ip to 99)
        $iInputNewLineChars  = strlen($iInputNewLines);
        $iOutputNewLineChars = strlen($iOutputNewLines);
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
            $sRows .= sprintf("%0".$iChars."d", $i);
        }
        $oInputRows = new cHTMLTextarea("txtInputRows", $sRows, $iChars, 20);

        $sRows = "";
        for ($i = 1; $i <= $iOutputNewLines; $i++) {
            if ($sRows) {
                $sRows .= "\r\n"; // why windows line feed???
            }
            $sRows .= sprintf("%0".$iChars."d", $i);
        }
        $oOutputRows = new cHTMLTextarea("txtOutputRows", $sRows, $iChars, 20);

        $oInputRows->updateAttributes(array("wrap" => "off"));
        $oOutputRows->updateAttributes(array("wrap" => "off"));

        $oInputRows->updateAttributes(array("readonly" => "true"));
        $oOutputRows->updateAttributes(array("readonly" => "true"));

        $oInputRows->setStyle("font-family: monospace;");
        $oOutputRows->setStyle("font-family: monospace;");
        $oOutputRows->setStyle("font-family: monospace;");
    }

    $input  = new cHTMLTextarea("input",  $sInputData, 100, 20, 'input');
    $output = new cHTMLTextarea("output", $sOutputData, 100, 20, 'output');

    // Style the fields
    $input->updateAttributes(array("wrap" => "off"));
    $output->updateAttributes(array("wrap" => "off"));

    $name->setDisabled($disabled);
    $descr->setDisabled($disabled);
    $input->setDisabled($disabled);
    $output->setDisabled($disabled);

    $descr->setStyle("width: 100%; font-family: monospace;");
    $input->setStyle("width: 100%; font-family: monospace;");
    $output->setStyle("width: 100%; font-family: monospace;");

    // Check, if tabs may be inserted in text areas (instead jumping to next element)
    if (getEffectiveSetting("modules", "edit-with-tabs", "false") == "true") {
        $input->setEvent("onkeydown", "return insertTab(event,this);");
        $output->setEvent("onkeydown", "return insertTab(event,this);");
    }

    // Prepare type select box
    $typeselect = new cHTMLSelectElement("type");

    $db2 = cRegistry::getDb();
    $sql = "SELECT type FROM ".$cfg["tab"]["mod"]." WHERE idclient=" . (int) $client . " GROUP BY type"; // This query can't be designed using GenericDB...
    $db2->query($sql);

    $aTypes = array();
    while ($db2->next_record()) {
        if ($db2->f("type") != "") {
            $aTypes[] = $db2->f("type");
        }
    }

    // Read existing layouts
    $oLayouts = new cApiLayoutCollection();
    $oLayouts->setWhere("idclient", $client);
    $oLayouts->query();

    while ($oLayout = $oLayouts->next()) {
        $aTypes = array_merge(explode(";", $oLayout->getProperty("layout", "used-types")), $aTypes);
    }
    $aTypes = array_unique($aTypes);

    foreach ($aTypes as $sType) {
        $typearray[$sType] = $sType;
    }
    unset($aTypes);

    if (is_array($typearray)) {
        asort($typearray);
        $typeselect->autoFill(array_merge(array("" => "-- ".i18n("Custom")." --"), $typearray));
    } else {
        $typeselect->autoFill(array("" => "-- ".i18n("Custom")." --"));
    }

    $typeselect->setEvent("change", 'if (document.forms["mod_edit"].elements["type"].value == 0) { document.forms["mod_edit"].elements["customtype"].disabled=0;} else {document.forms["mod_edit"].elements["customtype"].disabled=1;}');
    $typeselect->setDisabled($disabled);

    $custom = new cHTMLTextbox("customtype", "");
    $custom->setDisabled($disabled);

    if ($module->get("type") == "" || $module->get("type") == "0") {
        $typeselect->setDefault("0");
    } else {
        $typeselect->setDefault($module->get("type"));
        $custom->setDisabled("disabled");
    }

    $modulecheck = getSystemProperty("system", "modulecheck");

    $inputok  = true;
    $outputok = true;

    $inputModTest = "";
    $outputModTest = "";

    // get input/output from file
    if ($contenidoModuleHandler->modulePathExists() == true) {
        $inputModTest = $contenidoModuleHandler->readInput();
        $outputModTest = $contenidoModuleHandler->readOutput();
    } else {
        // donut
    }

    if ($modulecheck !== "false") {
        $outputok = modTestModule($outputModTest, $module->get("idmod") . "o", true);
        if (!$outputok) {
            $errorMessage = sprintf(i18n("Error in module. Error location: %s"),$modErrorMessage);
            $outled = '<img align="right" src="images/but_online_no.gif" alt="'.$errorMessage.'" title="'.$errorMessage.'">';
        } else {
            $okMessage = i18n("Module successfully compiled");
            $outled = '<img align="right" src="images/but_online.gif" alt="'.$okMessage.'" title="'.$okMessage.'">';
        }

        $inputok = modTestModule($inputModTest, $module->get("idmod"). "i");
        if (!$inputok) {
            $errorMessage = sprintf(i18n("Error in module. Error location: %s"),$modErrorMessage);
            $inled = '<img align="right" src="images/but_online_no.gif" alt="'.$errorMessage.'" title="'.$errorMessage.'">';
        } else {
            $okMessage = i18n("Module successfully compiled");
            $inled = '<img align="right" src="images/but_online.gif" alt="'.$okMessage.'" title="'.$okMessage.'">';
        }

        // Store error information in the database (to avoid re-eval for module overview/menu)
        if ($inputok && $outputok) {
            $sStatus = "none";
        } else if ($inputok) {
            $sStatus = "input";
        } else if ($outputok) {
            $sStatus = "output";
        } else {
            $sStatus = "both";
        }

        // If status has been changed, store and show in overview
        $sPrevStatus = $module->get("error");
        if ($sPrevStatus !== $sStatus) {
            $module->set("error", $sStatus);
            $module->store();
            $page->setReload();
        }
    }

    $form->add(i18n("Name"), $name->render());
    $form->add(i18n("Type"), $typeselect->render().$custom->render());
    $form->add(i18n("Description"), $descr->render());

    if ($sOptionDebugRows == "always" || ($sOptionDebugRows == "onerror" && (!$inputok || !$outputok)))
    {
        $form->add('<table class="borderless" width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td style="vertical-align: top;">'.i18n("Input").'</td><td style="vertical-align: top;">'.$inled.'</td><td style="padding-left: 5px; vertical-align: top;">'.$oInputRows->render().'</td></tr></table>', $input->render());
        $form->add('<table class="borderless" width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td style="vertical-align: top;">'.i18n("Output").'</td><td style="vertical-align: top;">'.$outled.'</td><td style="padding-left: 5px; vertical-align: top;">'.$oOutputRows->render().'</td></tr></table>', $output->render());
    } else {
        $form->add('<table class="borderless" width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td style="vertical-align: top;">'.i18n("Input").'</td><td style="vertical-align: top;">'.$inled.'</td></tr></table>', $input->render());
        $form->add('<table class="borderless" width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td style="vertical-align: top;">'.i18n("Output").'</td><td style="vertical-align: top;">'.$outled.'</td></tr></table>', $output->render());
    }

    if ($module->isOldModule()) {
        $page->displayWarning(i18n("This module uses variables and/or functions which are probably not available in this CONTENIDO version. Please make sure that you use up-to-date modules."));
    }

    if ($idmod != 0) {
        $import = new cHTMLRadiobutton("mode", "import");
        $export = new cHTMLRadiobutton("mode", "export");

        $import->setLabelText(i18n("Import from file"));
        $export->setLabelText(i18n("Export to file"));

        $import->setEvent("click", "document.getElementById('vupload').style.display = '';");
        $export->setEvent("click", "document.getElementById('vupload').style.display = 'none';");

        $upload = new cHTMLUpload("upload");

        $inputChecked = "";
        $outputChecked = "";

        if ($contenidoModuleHandler->modulePathExists() == true) {
             $inputChecked = $contenidoModuleHandler->readInput();
             $outputChecked = $contenidoModuleHandler->readOutput();
        }

        if ($inputChecked != "" && $outputChecked != "") {
            $export->setChecked("checked");
        } else {
            $import->setChecked("checked");
        }
        $form2 = new cGuiTableForm("export");

        $form2->setVar("action", "mod_importexport_module");
        $form2->setVar("use_encoding", "false");
        $form2->addHeader("Import/Export");
        $form2->add(i18n("Mode"), array($export, "<br>", $import));

        if ($inputChecked != "" && $outputChecked != "") {
            $form2->add(i18n("File"), $upload, "vupload", "display: none;");
        } else {
            $form2->add(i18n("File"), $upload, "vupload");
        }

        $form2->setVar("area", $area);
        $form2->setVar("frame", $frame);
        $form2->setVar("idmod", $idmod);
        $form2->custom["submit"]["accesskey"] = '';

        $sScript = '<script type="text/javascript">
                        if (document.getElementById(\'scroll\')) {
                            document.getElementById(\'scroll\').onmousedown = triggerClickOn;
                            document.getElementById(\'scroll\').onmouseup = triggerClickOff;
                            document.getElementById(\'scroll\').style.paddingTop=\'4px\';
                            document.getElementById(\'scroll\').style.paddingBottom=\'5px\';
                        }
                    </script>';
        //Dont show form if we delete or synchronize a module
        if ($action == "mod_sync" || $action == "mod_delete") {
            $page->abortRendering();
        } else {
            $page->set("s", "FORM", $message.$form->render()."<br>");
        }
    }


    if ($action) {
        if (stripslashes($idmod > 0) || $action == "mod_sync") {
            $page->setReload();
        }
    }
    if (!($action == "mod_importexport_module" && $mode == "export")) {
        $oCodeMirrorInput = new CodeMirror('input', 'php', substr(strtolower($belang), 0, 2), true, $cfg, !$bInUse);
        $oCodeMirrorOutput = new CodeMirror('output', 'php', substr(strtolower($belang), 0, 2), false, $cfg, !$bInUse);

        $page->addScript($oCodeMirrorInput->renderScript().$oCodeMirrorOutput->renderScript());

        //dont print meneu
        if($action == "mod_sync") {
            $page->setSubnav("idmod=".$idmod."&dont_print_subnav=1");
        }
        else {
            $page->setSubnav("idmod=".$idmod, "mod");
        }
        $page->render();
    }
}

?>