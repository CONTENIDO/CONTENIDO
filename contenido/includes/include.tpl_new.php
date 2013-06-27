<?php
/**
 * This file contains the backend page for creating new templates.
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
    $tpl->set('s', 'ACTION', '<a class="addfunction"target="right_bottom"
        href="' . $sess->url("main.php?area=tpl_edit&frame=4&action=tpl_new") . '">' . i18n("New template") . '</a></div>'
    );
} else {
    $tpl->set('s', 'ACTION', i18n('No Client selected'));
}
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['left_top']);

?>
