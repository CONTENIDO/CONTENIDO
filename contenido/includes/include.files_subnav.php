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
 * @version    1.0.2
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created  2003-01-25
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id: include.files_subnav.php 1225 2010-10-13 08:15:43Z OliverL $:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


if (isset($_GET['file'])) {

    $area = $_GET['area'];

    $areasNavSubs = getSubnavigationsByAreaName($area);

    foreach ($areasNavSubs as $areasNavSub) {
        $areaName = $areasNavSub['name'];
        $caption = $areasNavSub['caption'];
        
        if ($areaName == 'style') {
            $sAction = '&action=style_edit';
        } else if ($areaName == 'js') {
            $sAction = '&action=js_edit';
        } else if ($areaName == 'htmltpl') {
            $sAction = '&action=htmltpl_edit';
        } else {
            $sAction = '';
        }

        if ($perm->have_perm_area_action($areaName)) {
            # Set template data
            $tpl->set("d", "ID", 'c_'.$tpl->dyn_cnt);
            $tpl->set("d", "CLASS", '');
            $tpl->set("d", "OPTIONS", '');
            $tpl->set("d", "CAPTION", '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=$areaName&frame=4".$sAction."&file=$file&tmp_file=$file").'">'.$caption.'</a>');
            $tpl->next();
        }
    }

    $tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);
    
    if ($_GET['history'] == 'true') {
        $tpl->set('s', 'ACTIVATE_HISTORY', 'setHistory();');
    } else {
        $tpl->set('s', 'ACTIVATE_HISTORY', '');
    }

    # Generate the third navigation layer
    $tpl->generate($cfg["path"]["templates"] . $cfg['templates']['file_subnav']);

} else {
    include ($cfg["path"]["contenido"].$cfg["path"]["templates"] . $cfg["templates"]["right_top_blank"]);
}

?>