<?php

/**
 * This file contains the left top frame backend page for language management.
 *
 * @package Core
 * @subpackage Backend
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$tpl->set('s', 'CLASS', 'text_medium');
$tpl->set('s', 'OPTIONS', '');
$tpl->set('s', 'CAPTION', '');
$tpl->set('s', 'ACTION', '');

$clients = $classclient->getAccessibleClients();

$tpl2 = new cTemplate();
$tpl2->set('s', 'ID', 'editclient');
$tpl2->set('s', 'NAME', 'editclient');
$tpl2->set('s', 'CLASS', 'text_medium');
$tpl2->set('s', 'OPTIONS', '');

$iClientcount = count($clients);

$selectedClient = isset($_GET['targetclient']) ? $_GET['targetclient'] : cRegistry::getClientId();
foreach ($clients as $key => $value) {
    $selected = ($selectedClient == $key) ? 'selected' : '';

    if (cString::getStringLength($value['name']) > 15) {
        $value['name'] = cString::getPartOfString($value['name'], 0, 12) . '...';
    }

    $tpl2->set('d', 'VALUE', $key);
    $tpl2->set('d', 'CAPTION', $value['name']);
    $tpl2->set('d', 'SELECTED', $selected);
    $tpl2->next();
}

$select = $tpl2->generate($cfg["path"]["templates"] . $cfg['templates']['generic_select'], true);

$tpl->set('s', 'CLIENTSELECT', $select);

if ($perm->have_perm_area_action("lang_edit", "lang_newlanguage") && $iClientcount > 0) {
    $tpl->set('s', 'NEWLANG', '<a class="addfunction" href="javascript:void(0)">' . i18n("Create language for") . '</a>');
} else if ($iClientcount == 0) {
    $tpl->set('s', 'NEWLANG', i18n('No Client selected'));
} else {
    $tpl->set('s', 'NEWLANG', '');
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['lang_left_top']);
