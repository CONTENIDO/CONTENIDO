<?php

/**
 * This file contains the sub navigation frame backend page in structure management.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (!isset($path)) {
    $path = '';
}

$area = $_GET['area'];
$anchorTpl = '<a class="white%s" target="right_bottom" href="%s">%s</a>';

// Get all sub navigation items
$navSubColl = new cApiNavSubCollection();
$areasNavSubs = $navSubColl->getSubnavigationsByAreaName($area);

foreach ($areasNavSubs as $areasNavSub) {
    $areaName = $areasNavSub['name'];

    // CSS Class
    $sClass = ($areaName == $area) ? ' current' : '';

    // Link
    $sLink = $sess->url("main.php?area=$areaName&frame=4&path=$path");

    if ($perm->have_perm_area_action($areaName)) {
        if ($areaName != 'upl_edit') {
            // Set template data
            $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
            $tpl->set('d', 'DATA_NAME', $areaName);
            $tpl->set('d', 'CLASS', '');
            $tpl->set('d', 'OPTIONS', '');
            $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sClass, $sLink, $areasNavSub['caption']));
            $tpl->next();
        }
    }
}

$tpl->set('s', 'CLASS', 'menuless'); // With menu (left frame)

// Generate the third navigation layer
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['subnav']);
