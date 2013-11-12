<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Builds the third navigation layer
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.1.1
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created  2003-05-20
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *   
 *   $Id: include.workflow_subnav.php,v 1.1 2003/07/31 13:44:03 timo.hummel Exp $
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$areasNavSubs = getSubnavigationsByAreaName($area);

foreach ($areasNavSubs as $areasNavSub) {
    $areaName = $areasNavSub['name'];
    $caption = $areasNavSub['caption'];

    # Set template data
    $tpl->set("d", "ID", 'c_'.$tpl->dyn_cnt);
    $tpl->set("d", "CLASS", '');
    $tpl->set("d", "OPTIONS", '');
    $tpl->set("d", "CAPTION", '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=$areaName&frame=4&idworkflow=$idworkflow").'">'.$caption.'</a>');
    if ($area == $areaName) {
        $tpl->set('s', 'DEFAULT', markSubMenuItem($tpl->dyn_cnt,true));
    }
    $tpl->next();
}

$tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);
$tpl->set('s', 'IDCAT', $idcat);
$tpl->set('s', 'SESSID', $sess->id);
$tpl->set('s', 'CLIENT', $client);
$tpl->set('s', 'LANG', $lang);

# Generate the third navigation layer
if ($idworkflow <= 0) {
	$tpl->generate($cfg["path"]["templates"].$cfg["templates"]["subnav_blank"]);
} else {
	$tpl->generate($cfg["path"]["templates"] . $cfg["templates"]["subnav"]);
}

?>