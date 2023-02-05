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

/**
 * @var cPermission $perm
 * @var cSession $sess
 * @var cTemplate $tpl
 * @var array $cfg
 */

$client = cSecurity::toInteger(cRegistry::getClientId());

// Display critical error if no valid client is selected
if ($client < 1) {
    $oPage = new cGuiPage('tpl_new');
    $oPage->displayCriticalError(i18n("No Client selected"));
    $oPage->render();
    return;
}

$tpl->reset();

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

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['left_top']);
