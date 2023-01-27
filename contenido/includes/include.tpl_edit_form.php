<?php

/**
 * This file contains the backend page for editing templates.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cPermission $perm
 * @var cSession $sess
 * @var cDb $db
 * @var array $cfg
 * @var string $area
 * @var string $action
 * @var int $client
 * @var int $frame
 */


$tpl2 = new cTemplate();

$page = new cGuiPage("tpl_edit_form", '', '0');

if ($action == "tpl_delete" && $perm->have_perm_area_action_anyitem($area, $action)) {
    $page->displayOk(i18n("Deleted Template succcessfully!"));
    $page->abortRendering();
    $page->reloadLeftBottomFrame(['idtpl' => null]);
    $page->render();
    exit();
}

if ($action == "tpl_new" && !$perm->have_perm_area_action_anyitem($area, $action)) {
    $page->displayCriticalError(i18n("Permission denied"));
    $page->render();
    return;
}

$idtpl = cSecurity::toInteger($_REQUEST['idtpl'] ?? '0');
$description = $description ?? '';
$idlay = cSecurity::toInteger($idlay ?? '0');
$vdefault = cSecurity::toInteger(!empty($vdefault) ? $vdefault : '0');
$laydescription = '';

if ($action == "tpl_new") {
    $tplname = i18n("-- New template --");
}

$sql = "SELECT
        a.idtpl, a.name AS name, a.description, a.idlay, b.description AS laydescription, a.defaulttemplate
        FROM
            " . $cfg['tab']['tpl'] . " AS a
        LEFT JOIN
            " . $cfg['tab']['lay'] . " AS b ON a.idlay = b.idlay
        WHERE a.idtpl = " . $idtpl . "
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
$tpl2->set('s', 'ID', 'cLayoutSelect');
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

$sql = 'SELECT `idlay`, `name` FROM `%s` WHERE `idclient` = %d ORDER BY `name`';
$db->query($sql, $cfg['tab']['lay'], $client);
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
$form->setVar("oldname", $tplname);

if (!$idlay) {
    $form->setVar("createmode", 1);
}
$form->addHeader(i18n("Edit template"));

$name = new cHTMLTextbox("tplname", conHtmlSpecialChars(stripslashes($tplname)), 35);
$form->add(i18n("Name"), $name->render());

$descr = new cHTMLTextarea("description", $description);
$form->add(i18n("Description"), $descr->render());

$standardcb = new cHTMLCheckbox("vdefault", 1, "", $vdefault);
$form->add(i18n("Default"), $standardcb->toHtml(false));

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

        $modSelect = new cHTMLSelectElement("c[{$containerNr}]");

        $caption = ($name != '') ? "{$name} (Container {$containerNr})" : "Container {$containerNr}";

        $mode = tplGetContainerMode($idlay, $containerNr);
        $defaultModuleNotice = '';

        if ($mode == 'fixed') {
            $default = tplGetContainerDefault($idlay, $containerNr);

            $option = new cHTMLOptionElement('-- ' . i18n("none") . ' --', 0);
            $modSelect->addOptionElement(0, $option);

            foreach ($modules as $key => $val) {
                if ($val['name'] == $default) {
                    $option = new cHTMLOptionElement($val['name'], $key);
                    if ($containerModules[$containerNr] == $key) {
                        $option->setSelected(true);
                    }
                    $modSelect->addOptionElement($key, $option);
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

                $modSelect->addOptionElement(0, $option);
            }

            $allowedTypes = tplGetContainerTypes($idlay, $containerNr);
            $createmode = cSecurity::toInteger($_REQUEST['createmode'] ?? '0');

            foreach ($modules as $key => $val) {
                $option = new cHTMLOptionElement($val['name'], $key);

                $containerModulePos = $containerModules[$containerNr] ?? 0;
                if ($containerModulePos === $key || (($containerModulePos === 0 && $val['name'] == $default) && $createmode === 1)) {
                    $option->setSelected(true);
                }

                if (count($allowedTypes) > 0) {
                    if (in_array($val['type'], $allowedTypes) || $val['type'] == '') {
                        $modSelect->addOptionElement($key, $option);
                    }
                } else {
                    $modSelect->addOptionElement($key, $option);
                }
            }

            $containerModuleName = isset($containerModules[$containerNr]) && isset($modules[$containerModules[$containerNr]]['name'])
                ? $modules[$containerModules[$containerNr]]['name'] : '';
            if ($default != '' && $containerModuleName != $default && $createmode != 1) {
                $defaultModuleNotice = '&nbsp;(' . i18n('Default') . ': ' . $default . ')';
            }
        }

        $form->add($caption, $modSelect->render() . $defaultModuleNotice);
    }
}

$href = $sess->url("main.php?area=tpl&frame=2&idtpl=" . $idtpl);

if ($action == 'tpl_delete' || $action == 'tpl_new') {
    $page->reloadLeftBottomFrame(['idtpl' => null]);
} else {
    $page->reloadLeftBottomFrame(['idtpl' => $idtpl]);
}

$page->setContent([$form]);

$postIdTpl = cSecurity::toInteger($_POST['idtpl'] ?? '0');
if ($postIdTpl <= 0 && $idtpl > 0) {
    $page->displayOk(i18n("Created new Template successfully!"));
} elseif ($idtpl > 0 && (isset($_POST['submit_x']) || ($postIdTpl == $idtpl && $action != 'tpl_new'))) {
    $page->displayOk(i18n("Saved changes successfully!"));
}

$page->render();
