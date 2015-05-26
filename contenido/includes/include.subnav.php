<?php

/**
 * This file contains the default sub navigation frame backend page.
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

if (!isset($_GET['idcat'])) {
    $tpl->reset();
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['right_top_blank']);
    return;
}

$anchorTpl = '<a class="white" target="right_bottom" href="%s">%s</a>';

$nav = new cGuiNavigation();

$sql = "SELECT
            b.location AS location,
            a.name AS name,
            a.relevant AS relevant
        FROM
            ".$cfg['tab']['area']." AS a,
            ".$cfg['tab']['nav_sub']." AS b
        WHERE
            b.level = 1 AND
            b.idarea = a.idarea AND
            b.online = 1
        ORDER BY
            b.idnavs";

$db->query($sql);

while ($db->nextRecord()) {
    // Extract names from the XML document.
    $caption = $nav->getName($db->f('location'));
    $areaName = $db->f('name');

    if ($perm->have_perm_area_action($areaName) || ($db->f('relevant') == 0)) {
        // Set template data
        $tpl->set('d', 'ID',  'c_' . $tpl->dyn_cnt);
        $tpl->set('d', 'DATA_NAME', $areaName);
        $tpl->set('d', 'CLASS', '');
        $tpl->set('d', 'OPTIONS', '');
        $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=$areaName&frame=4&idcat=$idcat"), $caption));
        $tpl->next();
    }
}

$tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

$tpl->set('s', 'CLASS', ''); // With menu (left frame)

// Generate the third navigation layer
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['subnav']);
