<?php
/**
 * This file contains the sub navigation frame backend page in files overview.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Jan Lengowski
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (isset($_GET['file'])) {

    $area = $_GET['area'];

    $nav = new cGuiNavigation();

    $sql = "SELECT idarea
            FROM ".$cfg['tab']['area']." AS a
            WHERE a.name = '".$db->escape($area)."' OR a.parent_id = '".$db->escape($area)."'
            ORDER BY idarea";

    $db->query($sql);

    $in_str = '';
    while ($db->nextRecord()) {
        $in_str .= $db->f('idarea') . ',';
    }
    $in_str = substr($in_str, 0, -1);

    $sql = "SELECT
                b.location AS location,
                a.name AS name
            FROM
                ".$cfg['tab']['area']." AS a,
                ".$cfg['tab']['nav_sub']." AS b
            WHERE
                b.idarea IN (".$in_str.") AND
                b.idarea = a.idarea AND
                b.level = 1 AND
                b.online = 1
            ORDER BY
                b.idnavs";

    $db->query($sql);

    while ($db->nextRecord()) {
        // Extract names from the XML document.
        $caption = $nav->getName($db->f('location'));

        $tmp_area = $db->f('name');
        if ($tmp_area == 'style') {
            $sAction = '&action=style_edit';
        } elseif ($tmp_area == 'js') {
            $sAction = '&action=js_edit';
        } elseif ($tmp_area == 'htmltpl') {
            $sAction = '&action=htmltpl_edit';
        } else {
            $sAction = '';
        }
        if ($perm->have_perm_area_action($tmp_area)) {
            // Set template data
            $tpl->set('d', 'ID',      'c_' . $tpl->dyn_cnt);
            $tpl->set('d', 'CLASS',   '');
            $tpl->set('d', 'OPTIONS', '');
            $tpl->set('d', 'CAPTION', '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=$tmp_area&frame=4".$sAction."&file=$file&tmp_file=$file").'">'.$caption.'</a>');
            $tpl->next();
        }
    }

    $tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

    if (isset($_GET['history']) && $_GET['history'] == 'true') {
        $tpl->set('s', 'ACTIVATE_HISTORY', 'true');
    } else {
        $tpl->set('s', 'ACTIVATE_HISTORY', 'false');
    }

    // Generate the third navigation layer
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['file_subnav']);
} else {
    include(cRegistry::getBackendPath() . $cfg['path']['templates'] . $cfg['templates']['right_top_blank']);
}

?>