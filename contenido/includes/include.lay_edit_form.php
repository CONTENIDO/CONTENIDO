<?php
/**
 * This file contains the backend page for editing layouts.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Olaf Niemann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude("external", "codemirror/class.codemirror.php");
cInclude('classes', 'class.layout.synchronizer.php');

if (!isset($idlay)) {
    $idlay = 0;
}

$page = new cGuiPage("lay_edit_form");
$layout = new cApiLayout();
$bReloadSyncSrcipt = false;
if ($idlay != 0) {
    $layout->loadByPrimaryKey($idlay);
}

if ($action == "lay_new") {
    if (!$perm->have_perm_area_action_anyitem($area, $action)) {
        $page->displayError(i18n("Permission denied"));
    } else {
        $layoutAlias = strtolower(cApiStrCleanURLCharacters(i18n("-- New layout --")));

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
                $page->displayInfo(i18n("Created layout succsessfully!"));
            }
        }
    }
    $bReloadSyncSrcipt = true;
} elseif ($action == "lay_delete") {
    if (!$perm->have_perm_area_action_anyitem($area, $action)) {
        $page->displayError(i18n("Permission denied"));
    } else {
        $layout->virgin = true;
        $page->displayInfo(i18n("Layout deleted"));
    }
} elseif ($action == "lay_sync") {
    // Synchronize layout from db and filesystem
    if (!$perm->have_perm_area_action_anyitem($area, $action)) {
        $page->displayError(i18n("Permission denied"));
    } else {
        $layoutSynchronization = new cLayoutSynchronizer($cfg, $cfgClient, $lang, $client);
        $layoutSynchronization->synchronize();
        // Reload the overview of Layouts
        $bReloadSyncSrcipt = true;
    }
}

if ($refreshtemplates != "") {
    // Update all templates for containers with mode fixed and mandatory
    $sql = "SELECT idtpl FROM " . $cfg["tab"]["tpl"] . " WHERE idlay = '" . cSecurity::toInteger($idlay) . "'";
    $db->query($sql);

    $fillTemplates = array();

    while ($db->nextRecord()) {
        $fillTemplates[] = $db->f("idtpl");
    }

    foreach ($fillTemplates as $fillTemplate) {
        tplAutoFillModules($fillTemplate);
    }
}

if (!$layout->virgin) {
    $msg = '';

    $idlay = $layout->get("idlay");
    $layoutInFile = new cLayoutHandler($idlay, "", $cfg, $lang);
    $code = $layoutInFile->getLayoutCode();
    // code = $layout->get("code");
    $name = cString::stripSlashes(conHtmlSpecialChars($layout->get("name")));
    $description = $layout->get("description");

    if (!$layoutInFile->isWritable($name, $layoutInFile->_getLayoutPath())) {
        $page->displayWarning(i18n("You have no write permissions for this file"));
    }

    // Search for duplicate containers
    $containerNumbers = tplGetContainerNumbersInLayout($idlay);
    if (count($containerNumbers) > 0) {
        $types = array();
        $containerCounter = array();

        foreach ($containerNumbers as $containerNr) {
            if (empty($containerNr)) {
                continue;
            }

            $containerCounter[$containerNr] = 0;

            // Search for old-style CMS_CONTAINER[x]
            $containerCounter[$containerNr] += substr_count($code, "CMS_CONTAINER[$containerNr]");

            // Search for the new-style containers
            $count = preg_match_all("/<container( +)id=\\\\\"$containerNr\\\\\"(.*)>(.*)<\/container>/i", addslashes($code), $matches);

            $containerCounter[$containerNr] += $count;

            if (is_array(tplGetContainerTypes($idlay, $containerNr))) {
                $types = array_merge($types, tplGetContainerTypes($idlay, $containerNr));
            }
        }

        $types = array_unique($types);
        $layout->setProperty("layout", "used-types", implode($types, ";"));

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
            $idqualifier = "";

            $attr = array();

            if ($value["name"] != "") {
                $attr["name"] = "name '" . $value["name"] . "'";
            }

            if ($value["id"] != "") {
                $attr["id"] = "id '" . $value["id"] . "'";
            }

            $idqualifier = implode(", ", $attr);

            if ($idqualifier != "") {
                $idqualifier = "($idqualifier)";
            }
            $msg .= sprintf(i18n("Tag '%s' %s has no end tag (start tag is on line %s char %s)"), $value["tag"], $idqualifier, $value["line"], $value["char"]);
            $msg .= "<br>";
        }
    }

    if ($msg != "") {
        $page->displayWarning($msg);
    }

    $form = new cGuiTableForm("module");
    $form->addHeader(i18n("Edit Layout"));
    $form->setVar("area", $area);
    $form->setVar("action", "lay_edit");
    $form->setVar("frame", $frame);
    $form->setVar("idlay", $idlay);

    $tb_name = new cHTMLTextbox("layname", $name, 60);
    $ta_description = new cHTMLTextarea("description", $description, 100, 10);
    $ta_description->setStyle("font-family: monospace;width: 100%;");
    $ta_description->updateAttributes(array(
        "wrap" => "off"
    ));

    $ta_code = new cHTMLTextarea("code", conHtmlSpecialChars($code), 100, 20, 'code');
    $ta_code->setStyle("font-family: monospace;width: 100%;");
    $ta_code->updateAttributes(array(
        "wrap" => "off"
    ));

    $cb_refresh = new cHTMLCheckbox("refreshtemplates", i18n("On save, apply default modules to new containers"));

    $form->add(i18n("Name"), $tb_name);
    $form->add(i18n("Description"), $ta_description);
    $form->add(i18n("Code"), $ta_code);
    $form->add(i18n("Options"), $cb_refresh);

    $oCodeMirror = new CodeMirror('code', 'html', substr(strtolower($belang), 0, 2), true, $cfg);
    $page->addScript($oCodeMirror->renderScript());

    $page->set('s', 'FORM', $form->render());
} else {
    $page->set('s', 'FORM', '');
}

if (stripslashes($_REQUEST['idlay'] || $bReloadSyncSrcipt)) {
    $page->setReload();
}

if ($action == "lay_sync") {
    $page->setSubnav("idlay=" . $idlay . "&dont_print_subnav=1", "lay");
} else {
    $page->setSubnav("idlay=$idlay", "lay");
}
$page->render();

?>