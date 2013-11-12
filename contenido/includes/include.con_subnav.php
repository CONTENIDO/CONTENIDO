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
 *   created 2003-01-25
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id: include.con_subnav.php 1225 2010-10-13 08:15:43Z OliverL $:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

//Get sync options
if (isset($syncoptions)) {
	$syncfrom = $syncoptions;
	$remakeCatTable = true;
}

if (!isset($syncfrom)) {
	$syncfrom = 0;	
}

if (isset($_GET['idcat']) && $_GET['idcat'] != 0) {

    $areasNavSubs = getSubnavigationsByAreaName($area);

    foreach ($areasNavSubs as $areasNavSub) {
        $areaName = $areasNavSub['name'];
        $caption = $areasNavSub['caption'];

        # Set template data
        $tpl->set("d", "ID", 'c_'.$tpl->dyn_cnt);
        $tpl->set("d", "CLASS", '');
        $tpl->set("d", "OPTIONS", '');
		if ($cfg['help'] == true) {
			$tpl->set("d", "CAPTION", '<a onclick="'.setHelpContext(i18n("Article")."/$caption").'sub.clicked(this);artObj.doAction(\''.$areaName.'\')">'.$caption.'</a>');
		} else {
			$tpl->set("d", "CAPTION", '<a onclick="sub.clicked(this);artObj.doAction(\''.$areaName.'\')">'.$caption.'</a>');	
		}

        $tpl->next();
    }

    $tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);
    $tpl->set('s', 'IDCAT', $idcat);
    $tpl->set('s', 'SESSID', $sess->id);
    $tpl->set('s', 'CLIENT', $client);
    $tpl->set('s', 'LANG', $lang);

    # Generate the third navigation layer
    $tpl->generate($cfg["path"]["templates"] . $cfg["templates"]["con_subnav"]);

} else {
    include ($cfg["path"]["contenido"].$cfg["path"]["templates"] . $cfg["templates"]["right_top_blank"]);
}

?>