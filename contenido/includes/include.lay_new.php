<?php
/**
 * This file contains the backend page for creating layouts.
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

$tpl->reset();

if ((int) $client > 0) {
    $tpl->set('s', 'ACTION', '<a class="addfunction" target="right_bottom" href="main.php?area=lay_edit&frame=4&action=lay_new&contenido=1">'. i18n("New Layout") . '</a>');
    //synch button for layout
    $tpl->set('s', 'ACTION2', '<a class="syncronizefunction" target="right_bottom" href="main.php?area=lay_edit&frame=4&action=lay_sync&contenido=1">' . i18n("Synchronize layouts") . '</a>');


} else {
    $tpl->set('s', 'ACTION', i18n('No Client selected'));
    $tpl->set('s', 'ACTION2', '');
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['lay_left_top']);
?>