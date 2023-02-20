<?php

/**
 * This file contains the backend page for editing layouts.
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

cInclude('external', 'codemirror/class.codemirror.php');
cInclude('classes', 'class.layout.synchronizer.php');

global $action, $perm, $area, $cfgClient, $client, $cfg, $lang, $db, $frame;

$idlay = isset($_REQUEST['idlay']) ? cSecurity::toInteger($_REQUEST['idlay']) : 0;
$refreshTemplates = isset($_REQUEST['refreshtemplates']) ? $_REQUEST['refreshtemplates'] : '';

$belang = cRegistry::getBackendLanguage();

// check the read only setting and display a warning if it's active
$readOnly = (getEffectiveSetting("client", "readonly", "false") == "true");
if ($readOnly) {
    cRegistry::addWarningMessage(i18n('This area is read only! The administrator disabled edits!'));
}

$page = new cGuiPage('lay_edit_form', '', '0');
$layout = new cApiLayout();
$bReloadSyncScript = false;
if ($idlay != 0) {
    $layout->loadByPrimaryKey($idlay);
}

// check the readOnly boolean to see if changes should be made
if (!$readOnly && $action == "lay_new") {
    if (!$perm->have_perm_area_action_anyitem($area, $action)) {
        $page->displayError(i18n("Permission denied"));
    } else {
        $layoutAlias = cString::toLowerCase(cString::cleanURLCharacters(i18n("-- New layout --")));

        if (cLayoutHandler::existLayout($layoutAlias, $cfgClient, $client)) {
            $page->displayError(i18n("Layout name exist, rename the layout!"));
        } else {
            $layouts = new cApiLayoutCollection();

            $layout = $layouts->create(i18n("-- New layout --"));

            // save alias
            $layout->set("alias", $layoutAlias);
            $layout->store();

            // make new layout in filesystem
            $layoutInFile = new cLayoutHandler($layout->get("idlay"), "", $cfg, $lang);
            if ($layoutInFile->saveLayout('') == false) {
                $page->displayError(i18n("Cant save layout in filesystem!"));
            } else {
                $page->displayOk(i18n("Created layout succsessfully!"));
                $page->reloadRightTopFrame(['area' => 'lay', 'action' => null, 'idlay' => $layout->get('idlay')]);
            }
        }
    }
    $bReloadSyncScript = true;
} elseif (!$readOnly && $action == 'lay_delete') {
    if (!$perm->have_perm_area_action_anyitem("lay", $action)) {
        $page->displayError(i18n("Permission denied"));
    } else {
        $layout = new cApiLayout();
        $page->displayOk(i18n("Layout deleted"));
        $bReloadSyncScript = true;
    }
} elseif (!$readOnly && $action == 'lay_edit') {
    // Saving layout is done in action file include.lay_edit.action.php, we check here for changed name.
    if (isset($_POST['layname']) && isset($_POST['oldname']) && $_POST['layname'] !== $_POST['oldname']) {
        $bReloadSyncScript = true;
    }
} elseif ($action == 'lay_sync') {
    // Synchronize layout from db and filesystem
    if (!$perm->have_perm_area_action_anyitem($area, $action)) {
        $page->displayError(i18n("Permission denied"));
    } else {
        $layoutSynchronization = new cLayoutSynchronizer($cfg, $cfgClient, $lang, $client);
        $layoutSynchronization->synchronize();
        // Reload the overview of Layouts
        $bReloadSyncScript = true;
    }
}

if ($refreshTemplates != "") {
    // Update all templates for containers with mode fixed and mandatory
    $sql = "SELECT idtpl FROM " . $cfg["tab"]["tpl"] . " WHERE idlay = '" . cSecurity::toInteger($idlay) . "'";
    $db->query($sql);

    $fillTemplates = [];

    while ($db->nextRecord()) {
        $fillTemplates[] = $db->f("idtpl");
    }

    foreach ($fillTemplates as $fillTemplate) {
        tplAutoFillModules($fillTemplate);
    }
}

if (true === $layout->isLoaded()) {
    $msg = '';

    $idlay = $layout->get("idlay");
    $layoutInFile = new cLayoutHandler($idlay, "", $cfg, $lang);
    $code = $layoutInFile->getLayoutCode();
    // code = $layout->get("code");
    $name = cString::stripSlashes(conHtmlSpecialChars($layout->get("name")));
    $description = $layout->get('description') ?? '';

    if (!$layoutInFile->isWritable($name, $layoutInFile->_getLayoutPath())) {
        $page->displayWarning(i18n("You have no write permissions for this file"));
    }

    // Search for duplicate containers
    $containerNumbers = tplGetContainerNumbersInLayout($idlay);
    if (count($containerNumbers) > 0) {
        $types = [];
        $containerCounter = [];

        foreach ($containerNumbers as $containerNr) {
            if (empty($containerNr)) {
                continue;
            }

            $containerCounter[$containerNr] = 0;

            // Search for old-style CMS_CONTAINER[x]
            $containerCounter[$containerNr] += cString::countSubstring($code, "CMS_CONTAINER[$containerNr]");

            // Search for the new-style containers
            $count = preg_match_all("/<container( +)id=\\\\\"$containerNr\\\\\"(.*)>(.*)<\/container>/i", addslashes($code), $matches);

            $containerCounter[$containerNr] += $count;

            $types = array_merge($types, tplGetContainerTypes($idlay, $containerNr));
        }

        $types = array_unique($types);
        $layout->setProperty("layout", "used-types", implode(';', $types));

        foreach ($containerCounter as $key => $value) {
            if ($value > 1) {
                $msg .= sprintf(i18n("Container %s was defined %s times"), $key, $value) . "<br>";
            }
        }
    }

    // Try to validate html
    if (getEffectiveSetting("layout", "htmlvalidator", "true") == "true" && $code !== "") {
        $v = new cHTMLValidator();
        $v->validate($code);

        if (!$v->tagExists("body") && !$v->tagExists("BODY")) {
            $msg .= sprintf(i18n("The body tag does not exist in the layout. This is a requirement for the insite editing."));
            $msg .= "<br>";
        }

        if (!$v->tagExists("head") && !$v->tagExists("HEAD")) {
            $msg .= sprintf(i18n("The head tag does not exist in the layout. This is a requirement for the insite editing."));
            $msg .= "<br>";
        }

        foreach ($v->missingNodes as $value) {
            $idQualifier = "";

            $attr = [];

            if ($value["name"] != "") {
                $attr["name"] = "name '" . $value["name"] . "'";
            }

            if ($value["id"] != "") {
                $attr["id"] = "id '" . $value["id"] . "'";
            }

            $idQualifier = implode(", ", $attr);

            if ($idQualifier != "") {
                $idQualifier = "($idQualifier)";
            }
            $msg .= sprintf(i18n("Tag '%s' %s has no end tag (start tag is on line %s char %s)"), $value["tag"], $idQualifier, $value["line"], $value["char"]);
            $msg .= "<br>";
        }
    }

    if ($msg != "") {
        $page->displayWarning($msg);
    }

    $form = new cGuiTableForm("module");
    $form->addHeader(i18n("Edit Layout"));
    $form->setVar("area", $area);
    $form->setVar("action", 'lay_edit');
    $form->setVar("frame", $frame);
    $form->setVar("idlay", $idlay);
    $form->setVar("oldname", $name);

    $tb_name = new cHTMLTextbox("layname", $name, 60);
    $ta_description = new cHTMLTextarea("description", $description, 100, 10);
    $ta_description->setStyle("font-family: monospace;width: 100%;");
    $ta_description->updateAttributes([
        "wrap" => "off"
    ]);

    $ta_code = new cHTMLTextarea("code", conHtmlSpecialChars($code), 100, 20, 'code');
    $ta_code->setStyle("font-family: monospace;width: 100%;");
    $ta_code->updateAttributes([
        "wrap" => "off"
    ]);

    $cb_refresh = new cHTMLCheckbox("refreshtemplates", i18n("On save, apply default modules to new containers"));

    // disable the name textbox and the description textbox if readonly is on
    if ($readOnly) {
        $tb_name->setDisabled(true);
        $ta_description->setDisabled(true);
    }

    $form->add(i18n("Name"), $tb_name);
    $form->add(i18n("Description"), $ta_description);
    $form->add(i18n("Code"), $ta_code);
    $form->add(i18n("Options"), $cb_refresh);

    $oCodeMirror = new CodeMirror('code', 'html', cString::getPartOfString(cString::toLowerCase($belang), 0, 2), true, $cfg);
    // disable codemirror editing if readonly is on
    if ($readOnly) {
        $oCodeMirror->setProperty("readOnly", "true");

        $form->setActionButton('submit', cRegistry::getBackendUrl() . 'images/but_ok_off.gif', i18n('Overwriting files is disabled'), 's');
    }
    $page->addScript($oCodeMirror->renderScript());

    $page->set('s', 'FORM', $form->render());
} else {
    $page->set('s', 'FORM', '');
}

if ($bReloadSyncScript) {
    if ($action == 'lay_sync' || $action == 'lay_delete') {
        $page->reloadLeftBottomFrame(['idlay' => null]);
    } else {
        $page->reloadLeftBottomFrame(['idlay' => $idlay]);
    }
}

// if ($action == 'lay_sync') {
//     $page->setSubnav("idlay={$idlay}&dont_print_subnav=1", "lay");
// } else {
//     $page->setSubnav("idlay={$idlay}", "lay");
// }

$page->render();

?>
