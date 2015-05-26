<?php

/**
 * This file contains the sub navigation frame backend page in upload section.
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

// Use remembered path from upl_last_path (from session)
if (!isset($_GET['path']) && $sess->isRegistered('upl_last_path')) {
    $_GET['path'] = $upl_last_path;
}

if (!isset($_GET['path'])) {
    $tpl->reset();
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['right_top_blank']);
    return;
}

$path = $_GET['path'];
$area = $_GET['area'];
$anchorTpl = '<a class="white" target="right_bottom" href="%s">%s</a>';

// Get all sub navigation items
$navSubColl = new cApiNavSubCollection();
$areasNavSubs = $navSubColl->getSubnavigationsByAreaName($area);

foreach ($areasNavSubs as $areasNavSub) {
    $areaName = $areasNavSub['name'];

    if ($perm->have_perm_area_action($areaName)) {
        if ($areaName != 'upl_edit') {
            // Set template data
            $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
            $tpl->set('d', 'DATA_NAME', $areaName);
            $tpl->set('d', 'CLASS', '');
            $tpl->set('d', 'OPTIONS', '');
            $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=$areaName&frame=4&path=$path&appendparameters=$appendparameters"), $areasNavSub['caption']));
            $tpl->next();
        }
    }
}

$tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

$tpl->set('s', 'CLASS', ''); // With menu (left frame)

// Generate the third navigation layer
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['subnav']);
