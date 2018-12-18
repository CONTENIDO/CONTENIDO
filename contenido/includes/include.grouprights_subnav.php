<?php

/**
 * This file contains the sub navigation frame backend page for group management.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Jan Lengowski
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (!isset($_GET['groupid'])) {
    $tpl->reset();
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['right_top_blank']);
    return;
}

$area = $_GET['area'];

$anchorTpl = '<a class="white" target="right_bottom" href="%s">%s</a>';

// Get all sub navigation items
$navSubColl = new cApiNavSubCollection();
$areasNavSubs = $navSubColl->getSubnavigationsByAreaName($area);

foreach ($areasNavSubs as $areasNavSub) {
    $areaName = $areasNavSub['name'];

    if ($perm->have_perm_area_action($areaName)) {
        // Set template data
        $tpl->set('d', 'ID',      'c_' . $tpl->dyn_cnt);
        $tpl->set('d', 'DATA_NAME', $areaName);
        $tpl->set('d', 'CLASS',   '');
        $tpl->set('d', 'OPTIONS', '');
        $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=$areaName&frame=4&groupid=$groupid"), $areasNavSub['caption']));
        $tpl->next();
    }
}

$_cecIterator = $_cecRegistry->getIterator('Contenido.Permissions.Group.Areas');

if ($_cecIterator->count() > 0) {
    $areaName = 'group_external';
    $caption = 'group_external';

    while (($chainEntry = $_cecIterator->next()) !== false) {
        $aInfo = $chainEntry->execute();

        foreach ($aInfo as $key => $sAreaID) {
            $sAreaName = false;
            $_cecIterator2 = $_cecRegistry->getIterator('Contenido.Permissions.Group.GetAreaName');
            while (($chainEntry2 = $_cecIterator2->next()) !== false) {
                $aInfo2 = $chainEntry2->execute($sAreaID);
                if ($aInfo2 !== false) {
                    $sAreaName = $aInfo2;
                    break;
                }
            }

            if ($sAreaName !== false) {
                // Set template data
                $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
                $tpl->set('d', 'DATA_NAME', $areaName);
                $tpl->set('d', 'CLASS', '');
                $tpl->set('d', 'OPTIONS', '');
                $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=$areaName&frame=4&external_area=$sAreaID&groupid=$groupid"), $caption));
                $tpl->next();
            }
        }
    }
}
$tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

$tpl->set('s', 'CLASS', ''); // With menu (left frame)

// Generate the third navigation layer
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['subnav']);
