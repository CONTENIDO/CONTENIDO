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
 * @version    1.0.0
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * @deprecated Was replaced by include.default_subnav.php
 * 
 * {@internal 
 *   created 2003-01-25
 *   modified 2008-06-26, Dominik Ziegler, add security fix
 *   modified 2010-09-07, Oliver Lohkemper, deprecated
 *
 *   $Id: include.client_subnav.php 1225 2010-10-13 08:15:43Z OliverL $:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if (isset($_GET['idclient']) && ($_GET['idclient'] != 0)) {

    $areasNavSubs = getSubnavigationsByAreaName($area);

    foreach ($areasNavSubs as $areasNavSub) {
        $areaName = $areasNavSub['name'];

        # Set template data
        $tpl->set("d", "ID", 'c_' . $tpl->dyn_cnt);
        $tpl->set("d", "CLASS", '');
        $tpl->set("d", "OPTIONS", '');
        $tpl->set("d", "CAPTION", '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=$areaName&frame=4&idclient=$idclient").'">'.$areasNavSub['caption'].'</a>');
        $tpl->next();
    }

    $tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);
    $tpl->set('s', 'IDCAT', $idcat);
    $tpl->set('s', 'SESSID', $sess->id);
    $tpl->set('s', 'CLIENT', $client);
    $tpl->set('s', 'LANG', $lang);

    # Generate the third navigation layer
    $tpl->generate($cfg["path"]["templates"] . $cfg['templates']['client_subnav']);

} else {
    $tpl->reset();
    $tpl->set('s', 'ACTION', '');
    $tpl->generate($cfg["path"]["templates"] . $cfg['templates']['right_top_blank']);
}

?>