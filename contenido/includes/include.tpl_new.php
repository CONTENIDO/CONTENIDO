<?php

/**
 * This file contains the backend page for creating new templates.
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

if ($client > 0) {
    if ($perm->have_perm_area_action("tpl_edit", "tpl_new")) {
        $str = sprintf(
            '<a class="addfunction" href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>',
            'right_top', $sess->url("main.php?area=tpl_edit&frame=3"),
            'right_bottom', $sess->url("main.php?area=tpl_edit&action=tpl_new&frame=4"),
            i18n("New template")
        );
        $tpl->set('s', 'ACTION', $str);
    } else {
        $tpl->set('s', 'ACTION', '<a class="addfunction_disabled" href="#">' . i18n("No permission to create templates") . '</a>');
    }
} else {
    $tpl->set('s', 'ACTION', i18n('No Client selected'));
}
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['left_top']);

?>