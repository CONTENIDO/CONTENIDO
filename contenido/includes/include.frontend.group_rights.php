<?php

/**
 * This file contains the backend page for frontend group rights.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var array $cfg
 * @var string $area
 * @var int $frame
 */

$requestUsePlugin = $_REQUEST['useplugin'] ?? '';
$requestIdFrontendGroup = cSecurity::toInteger($_REQUEST['idfrontendgroup'] ?? '0');

$page = new cGuiPage("frontend.group_rights");

if ($requestUsePlugin != 'category' || $requestIdFrontendGroup <= 0) {
    $page->displayCriticalError('Illegal call!');
    $page->render();
    return;
} elseif (!cHasPlugins('frontendlogic') || !in_array($requestUsePlugin, $cfg['plugins']['frontendlogic'])) {
    $page->displayCriticalError(i18n("Invalid plugin"));
    $page->render();
    return;
}

cInclude('plugins', 'frontendlogic/' . $requestUsePlugin . '/' . $requestUsePlugin . '.php');

$className = 'frontendlogic_' . $requestUsePlugin;
$class = new $className;
$perms = new cApiFrontendPermissionCollection();
$action = cRegistry::getAction();

$rights = new cGuiTableForm('rights');
$rights->setVar('area', $area);
$rights->setVar('frame', $frame);
$rights->setVar('useplugin', $requestUsePlugin);
$rights->setVar('idfrontendgroup', $requestIdFrontendGroup);
$rights->setVar('action', 'fegroups_save_perm');

$actions = $class->listActions();
$items = $class->listItems();

if ($action == 'fegroups_save_perm') {
    $myitems = $items;
    $myitems['__GLOBAL__'] = '__GLOBAL__';

    foreach ($actions as $action => $actionText) {
        foreach ($myitems as $item => $itemText) {
            if ($item === '__GLOBAL__') {
                $varname = 'action_' . $action;
            } else {
                $varname = 'item_' . $item . '_' . $action;
            }
            if (isset($_POST[$varname]) && $_POST[$varname] == 1) {
                $perms->setPerm($requestIdFrontendGroup, $requestUsePlugin, $action, $item);
            } else {
                $perms->removePerm($requestIdFrontendGroup, $requestUsePlugin, $action, $item);
            }
        }
    }
    cRegistry::addOkMessage(i18n("Saved changes successfully!"));
}

$rights->setHeader(sprintf(i18n("Permissions for plugin '%s'"), $class->getFriendlyName()));

foreach ($actions as $key => $action) {
    $check[$key] = new cHTMLCheckbox('action_' . $key, 1);
    $check[$key]->setLabelText($action." ".i18n("(All)"));

    if ($perms->checkPerm($requestIdFrontendGroup, $requestUsePlugin, $key, '__GLOBAL__')) {
        $check[$key]->setChecked(true);
    }
}

$rights->add(i18n("Global rights"), $check);

foreach ($actions as $key => $action) {
    $check = [];

    if (count($items) > 0) {
        foreach ($items as $item => $value) {
            $check[$item] = new cHTMLCheckbox('item_'.$item.'_'.$key, 1);
            $check[$item]->setLabelText($value);
            if ($perms->checkPerm($requestIdFrontendGroup, $requestUsePlugin, $key, $item)) {
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
