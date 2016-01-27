<?php

/**
 * This file contains the left top frame backend page for client management.
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

$tpl->set('s', 'ID', 'oTplSel');
$tpl->set('s', 'CLASS', 'text_medium');
$tpl->set('s', 'OPTIONS', '');
$tpl->set('s', 'CAPTION', '');
$tpl->set('s', 'ACTION', $select);

$tmp_mstr = '<a class="addfunction" href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
$area = 'client';
$mstr = sprintf(
    $tmp_mstr,
    'right_top', $sess->url("main.php?area=client&frame=3"),
    'right_bottom', $sess->url("main.php?area=client_edit&action=client_new&frame=4"),
    i18n("Create client")
);
if (strpos($auth->auth["perm"],"sysadmin") !== false) {
    $tpl->set('s', 'NEWCLIENT', $mstr);
} else {
    $tpl->set('s', 'NEWCLIENT', '<a class="addfunction_disabled" href="#">' . i18n("Only sysadmins can create clients") . '</a>');
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['client_left_top']);
