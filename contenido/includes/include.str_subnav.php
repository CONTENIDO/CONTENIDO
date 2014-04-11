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
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-05-01
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: include.str_subnav.php 1225 2010-10-13 08:15:43Z OliverL $:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if (!isset($path)) {
	$path = "";	
}

$area = $_GET['area'];

$areasNavSubs = getSubnavigationsByAreaName($area);

foreach ($areasNavSubs as $areasNavSub) {
    $areaName = $areasNavSub['name'];
    $caption = htmlentities($areasNavSub['caption']);

    if ($perm->have_perm_area_action($areaName)) {
    	if ($areaName != "upl_edit") {
            # Set template data
            $tpl->set("d", "ID", 'c_'.$tpl->dyn_cnt);
            $tpl->set("d", "CLASS", '');
            $tpl->set("d", "OPTIONS", '');
            $tpl->set("d", "CAPTION", '<a onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=$areaName&frame=4&path=$path").'">'.$caption.'</a>');
            $tpl->next();
    	}
    }
}

$tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

# Generate the third navigation layer
$tpl->generate($cfg["path"]["templates"] . "template.subnav_noleft.html");

?>