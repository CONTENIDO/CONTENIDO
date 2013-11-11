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

if (!isset($_GET['file'])) {
    $tpl->reset();
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['right_top_blank']);
    return;
}

$area = $_GET['area'];

$anchorTpl = '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="%s">%s</a>';

// Get all sub navigation items
$navSubColl = new cApiNavSubCollection();
$areasNavSubs = $navSubColl->getSubnavigationsByAreaName($area);

foreach ($areasNavSubs as $areasNavSub) {
    $areaName = $areasNavSub['name'];
    if ($areaName == 'style') {
        $sAction = '&action=style_edit';
    } elseif ($areaName == 'js') {
        $sAction = '&action=js_edit';
    } elseif ($areaName == 'htmltpl') {
        $sAction = '&action=htmltpl_edit';
    } else {
        $sAction = '';
    }
    if ($perm->have_perm_area_action($areaName)) {
        // Set template data
        $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
        $tpl->set('d', 'DATA_NAME', $areaName);
        $tpl->set('d', 'CLASS', '');
        $tpl->set('d', 'OPTIONS', '');
        $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area={$areaName}&frame=4{$sAction}&file={$file}&tmp_file={$file}"), $areasNavSub['caption']));
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
