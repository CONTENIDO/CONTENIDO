<?php
/**
 * This file contains the building of the third navigation layer.
 *
 * @package Plugin
 * @subpackage Workflow
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (!isset($idworkflow) || (int) $idworkflow <= 0) {
    $tpl->reset();
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['right_top_blank']);
    return;
}

$anchorTpl = '<a class="white" target="right_bottom" href="%s">%s</a>';

// Get all sub navigation items
$navSubColl = new cApiNavSubCollection();
$areasNavSubs = $navSubColl->getSubnavigationsByAreaName($area);

foreach ($areasNavSubs as $areasNavSub) {
    $areaName = $areasNavSub['name'];

    // Set template data
    $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
    $tpl->set('d', 'DATA_NAME', $areaName);
    $tpl->set('d', 'CLASS', '');
    $tpl->set('d', 'OPTIONS', '');
    $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sess->url("main.php?area=$areaName&frame=4&idworkflow=$idworkflow"), $areasNavSub['caption']));
    if ($area == $areaName) {
        $tpl->set('s', 'DEFAULT', markSubMenuItem($tpl->dyn_cnt, true));
    }
    $tpl->next();
}

// @TODO  Do we realy need this in subnav template?
$tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);
$tpl->set('s', 'IDCAT', $idcat);
$tpl->set('s', 'CLIENT', $client);
$tpl->set('s', 'LANG', $lang);

$tpl->set('s', 'CLASS', ''); // With menu (left frame)

// Generate the third navigation layer
if ($idworkflow <= 0) {
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['subnav_blank']);
} else {
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['subnav']);
}
