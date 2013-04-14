<?php
/**
 * This file contains the left top frame backend page for html template management.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Willi Man
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
$tpl->set('s', 'SESSID', $sess->id);


$tpl->set('s', 'ACTION', $select);

$tmp_mstr = '<a class="addfunction" href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
$area = "htmltpl";
$mstr = sprintf($tmp_mstr, 'right_top',
                                   $sess->url("main.php?area=htmltpl&frame=3"),
                                   'right_bottom',
                                   $sess->url("main.php?area=htmltpl&frame=4&action=htmltpl_create"),
                                   i18n("Create module template"));
if ((int) $client > 0) {
    $tpl->set('s', 'NEWSTYLE', $mstr);
} else {
    $tpl->set('s', 'NEWSTYLE', i18n('No Client selected'));
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['html_tpl_left_top']);
?>