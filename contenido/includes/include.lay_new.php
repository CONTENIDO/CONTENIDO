<?php

/**
 * This file contains the backend page for creating layouts.
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

$client = cSecurity::toInteger(cRegistry::getClientId());

if (!$perm->have_perm_area_action($area, $action)) {
    $tpl->set('s', 'ACTION', "");
    $tpl->set('s', 'ACTION2', '');
} else if ($client > 0) {
    // New layout link
   if ($perm->have_perm_area_action("lay_edit", "lay_new")) {
       $str = sprintf(
           '<a class="addfunction" href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>',
           'right_top', $sess->url("main.php?area=lay_edit&frame=3"),
           'right_bottom', $sess->url("main.php?area=lay_edit&action=lay_new&frame=4"),
           i18n("New Layout")
       );
       $tpl->set('s', 'ACTION', $str);
    } else {
        $tpl->set('s', 'ACTION', '<a class="addfunction_disabled" href="#">'. i18n("No permission to create layouts") . '</a>');
    }
    // Sync layouts link
    if ($perm->have_perm_area_action("lay_edit", "lay_new")) {
        $str = sprintf(
            '<a class="syncronizefunction" href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>',
            'right_top', $sess->url("main.php?area=lay_edit&frame=3"),
            'right_bottom', $sess->url("main.php?area=lay_edit&action=lay_sync&frame=4"),
            i18n("Synchronize layouts")
        );
        $tpl->set('s', 'ACTION2', $str);
    } else {
        $tpl->set('s', 'ACTION2', '<a class="syncronizefunction_disabled" href="#">' . i18n("No permission to synchronize layouts") . '</a>');
    }
} else {
    $tpl->set('s', 'ACTION', i18n('No Client selected'));
    $tpl->set('s', 'ACTION2', '');
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['lay_left_top']);

?>