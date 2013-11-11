<?php
/**
 * This file contains the left top frame backend page for style management.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
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

$tmp_mstr = '<div class="leftTopAction"><a class="addfunction" href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a></div>';
$area = 'style';
$mstr = sprintf(
    $tmp_mstr,
    'right_top', $sess->url("main.php?area=style&frame=3"),
    'right_bottom', $sess->url("main.php?area=style&frame=4&action=style_create"),
    i18n("Create style")
);
if ((int) $client > 0) {
    if ($perm->have_perm_area_action($area, "style_create")) {
        $tpl->set('s', 'NEWSTYLE', $mstr);
    } else {
        $tpl->set("s", "NEWSTYLE", '<div class="leftTopAction"><a class="addfunction_disabled" href="#">' . i18n("No permission to create new styles") . '</a></div>');
    }
} else {
    $tpl->set('s', 'NEWSTYLE', i18n('No Client selected'));
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['style_left_top']);
