<?php

/**
 * This file contains the backend page for frontend group rights.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// @TODO: check the code beneath is necessary
if ($_REQUEST['useplugin'] != 'category') {
    die('Illegal call!');
}


$page = new cGuiPage("frontend.group_rights");

if (!in_array($useplugin, $cfg['plugins']['frontendlogic'])) {
    $page->displayCriticalError(i18n("Invalid plugin"));
    $page->render();
    return;
}

cInclude('plugins', 'frontendlogic/' . $useplugin . '/' . $useplugin . '.php');

$className = 'frontendlogic_' . $useplugin;
$class = new $className;
$perms = new cApiFrontendPermissionCollection();

$rights = new cGuiTableForm('rights');
$rights->setVar('area', $area);
$rights->setVar('frame', $frame);
$rights->setVar('useplugin', $useplugin);
$rights->setVar('idfrontendgroup', $idfrontendgroup);
$rights->setVar('action', 'fegroups_save_perm');

$actions = $class->listActions();
$items = $class->listItems();

if ($action == 'fegroups_save_perm') {
    $myitems = $items;
    $myitems['__GLOBAL__'] = '__GLOBAL__';

       foreach ($actions as $action => $text) {
           foreach ($myitems as $item => $text) {
            if ($item === '__GLOBAL__') {
                $varname = 'action_' . $action;
            } else {
                $varname = 'item_' . $item . '_' . $action;
            }

            if ($_POST[$varname] == 1) {
                $perms->setPerm($idfrontendgroup, $useplugin, $action, $item);
            } else {
                $perms->removePerm($idfrontendgroup, $useplugin, $action, $item);
            }
        }
    }
    cRegistry::addOkMessage(i18n("Saved changes successfully!"));
}

$rights->addHeader(sprintf(i18n("Permissions for plugin '%s'"), $class->getFriendlyName()));

foreach ($actions as $key => $action) {
    $check[$key] = new cHTMLCheckbox('action_' . $key, 1);
    $check[$key]->setLabelText($action." ".i18n("(All)"));

    if ($perms->checkPerm($idfrontendgroup, $useplugin, $key, '__GLOBAL__')) {
        $check[$key]->setChecked(true);
    }
}

$rights->add(i18n("Global rights"), $check);

foreach ($actions as $key => $action) {
    unset($check);

    if (count($items) > 0) {
        foreach ($items as $item => $value) {
            $check[$item] = new cHTMLCheckbox('item_'.$item.'_'.$key, 1);
            $check[$item]->setLabelText($value);
            if ($perms->checkPerm($idfrontendgroup, $useplugin, $key, $item)) {
                $check[$item]->setChecked(true);
            }
        }
        $rights->add($action, $check);
    } else {
        $rights->add($action, i18n("No items found"));
    }
}

$page->setContent($rights);

$page->render();

?>