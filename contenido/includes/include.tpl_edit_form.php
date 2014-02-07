<?php
/**
 * This file contains the backend page for editing templates.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$tpl2 = new cTemplate();

$page = new cGuiPage("tpl_edit_form", '', '0');

if ($action == "tpl_delete" && $perm->have_perm_area_action_anyitem($area, $action)) {
    $page->displayInfo(i18n("Deleted Template succcessfully!"));
    $page->abortRendering();
    $page->render();
    exit();
}

if (($action == "tpl_new") && (!$perm->have_perm_area_action_anyitem($area, $action))) {
    $page->displayCriticalError(i18n("Permission denied"));
    $page->render();
    return;
}

if ($action == "tpl_new") {
    $tplname = i18n("-- New template --");
}

$sql = "SELECT
        a.idtpl, a.name as name, a.description, a.idlay, b.description as laydescription, a.defaulttemplate
        FROM
        " . $cfg['tab']['tpl'] . " AS a
        LEFT JOIN
        " . $cfg['tab']['lay'] . " AS b
        ON a.idlay=b.idlay
        WHERE a.idtpl='" . cSecurity::toInteger($idtpl) . "'
        ORDER BY name";

$db->query($sql);

if ($db->nextRecord()) {
    $idtpl = $db->f("idtpl");
    $tplname = $db->f("name");
    $description = $db->f("description");
    $idlay = $db->f("idlay");
    $laydescription = nl2br($db->f("laydescription"));
    $vdefault = $db->f("defaulttemplate");
}

// *************** List layouts ****************
$tpl2->set('s', 'NAME', 'idlay');
$tpl2->set('s', 'CLASS', 'text_medium');
$tpl2->set('s', 'OPTIONS', 'onchange="tplform.changelayout.value=1;tplform.submit();"');

if ($idlay != 0) {
    $tpl2->set('d', 'VALUE', 0);
    $tpl2->set('d', 'CAPTION', '--- ' . i18n("none") . ' ---');
    $tpl2->set('d', 'SELECTED', '');
    $tpl2->next();
} else {
    $tpl2->set('d', 'VALUE', 0);
    $tpl2->set('d', 'CAPTION', '--- ' . i18n("none") . ' ---');
    $tpl2->set('d', 'SELECTED', 'selected');
    $tpl2->next();
}

$sql = "SELECT idlay, name FROM " . $cfg['tab']['lay'] . "
        WHERE idclient='" . cSecurity::toInteger($client) . "'
        ORDER BY name";
$db->query($sql);
while ($db->nextRecord()) {
    if ($db->f("idlay") != $idlay) {
        $tpl2->set('d', 'VALUE', $db->f("idlay"));
        $tpl2->set('d', 'CAPTION', $db->f("name"));
        $tpl2->set('d', 'SELECTED', '');
        $tpl2->next();
    } else {
        $tpl2->set('d', 'VALUE', $db->f("idlay"));
        $tpl2->set('d', 'CAPTION', $db->f("name"));
        $tpl2->set('d', 'SELECTED', 'selected');
        $tpl2->next();
    }
}

$select = $tpl2->generate($cfg['path']['templates'] . $cfg['templates']['generic_select'], true);


// Get all modules by clients
$moduleColl = new cApiModuleCollection();
$modules = $moduleColl->getAllByIdclient($client);

$form = new cGuiTableForm("tplform");
$form->setVar("area", $area);
$form->setVar("changelayout", 0);
$form->setVar("frame", $frame);
$form->setVar("action", "tpl_edit");
$form->setVar("idtpl", $idtpl != -1 ? $idtpl : "");

if (!$idlay) {
    $form->setVar("createmode", 1);
}
$form->addHeader(i18n("Edit template"));

$name = new cHTMLTextbox("tplname", $tplname, 35);
$form->add(i18n("Name"), $name->render());

$descr = new cHTMLTextarea("description", $description);
$form->add(i18n("Description"), $descr->render());

$standardcb = new cHTMLCheckbox("vdefault", 1, "", $vdefault);
$form->add(i18n("Default"), $standardcb->toHTML(false));

$form->add(i18n("Layout"), $select);
$form->add(i18n("Layout description"), $laydescription);

if ($idlay) {

    // List of configured container
    $containerNumbers = tplGetContainerNumbersInLayout($idlay);

    // List of used modules in container
    $containerModules = conGetUsedModules($idtpl);

    foreach ($containerNumbers as $containerNr) {
        if (empty($containerNr)) {
            continue;
        }

        // Loop through containers ****************
        $name = tplGetContainerName($idlay, $containerNr);

        $modselect = new cHTMLSelectElement("c[{$containerNr}]");

        $caption = ($name != '') ? "{$name} (Container {$containerNr})" : "Container {$containerNr}";

        $mode = tplGetContainerMode($idlay, $containerNr);
        $defaultModuleNotice = '';

        if ($mode == 'fixed') {
            $default = tplGetContainerDefault($idlay, $containerNr);

            foreach ($modules as $key => $val) {
                if ($val['name'] == $default) {
                    $option = new cHTMLOptionElement($val['name'], $key);
                    if ($containerModules[$containerNr] == $key) {
                        $option->setSelected(true);
                    }

                    $modselect->addOptionElement($key, $option);
                }
            }
        } else {
            $default = tplGetContainerDefault($idlay, $containerNr);

            if ($mode == 'optional' || $mode == '') {
                $option = new cHTMLOptionElement('-- ' . i18n("none") . ' --', 0);

                if (isset($containerModules[$containerNr]) && $containerModules[$containerNr] != 0) {
                    $option->setSelected(false);
                } else {
                    $option->setSelected(true);
                }

                $modselect->addOptionElement(0, $option);
            }

            $allowedtypes = tplGetContainerTypes($idlay, $containerNr);

            foreach ($modules as $key => $val) {
                $option = new cHTMLOptionElement($val['name'], $key);

                if ($containerModules[$containerNr] == $key || (($containerModules[$containerNr] == 0 && $val['name'] == $default) && $createmode == 1)) {
                    $option->setSelected(true);
                }

                if (count($allowedtypes) > 0) {
                    if (in_array($val['type'], $allowedtypes) || $val['type'] == '') {
                        $modselect->addOptionElement($key, $option);
                    }
                } else {
                    $modselect->addOptionElement($key, $option);
                }
            }

            if ($default != '' && $modules[$containerModules[$containerNr]]['name'] != $default && $createmode != 1) {
                $defaultModuleNotice = '&nbsp;(' . i18n('Default') . ': ' . $default . ')';
            }
        }

        $form->add($caption, $modselect->render() . $defaultModuleNotice);
    }
}

$href = $sess->url("main.php?area=tpl&frame=2&idtpl=" . $idtpl);

$page->setReload();
//$page->setSubnav("idtpl=$idtpl", "tpl");

$page->setContent(array($form));

if ($_POST["idtpl"] === "" && $idtpl > 0) {
    $page->displayInfo(i18n("Created new Template successfully!"));
} elseif ($idtpl > 0 && (isset($_POST["submit_x"]) || ($_POST["idtpl"] == $idtpl && $action != 'tpl_new'))) {
    $page->displayInfo(i18n("Saved changes successfully!"));
}

$page->render();

?>