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
 * @deprecated Was replaced by include.default_subnav.php
 * 
 * {@internal 
 *   created  2003-01-25
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *   modified 2010-09-07, Oliver Lohkemper, deprecated
 *
 *   $Id: include.rights_subnav.php 1225 2010-10-13 08:15:43Z OliverL $:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


if (isset($_GET['userid'])) {

    $area = $_GET['area'];

    $areasNavSubs = getSubnavigationsByAreaName($area);

    foreach ($areasNavSubs as $areasNavSub) {
        $areaName = $areasNavSub['name'];
        $caption = $areasNavSub['caption'];

        if ($perm->have_perm_area_action($areaName)) {
            # Set template data
            $tpl->set("d", "ID", 'c_'.$tpl->dyn_cnt);
            $tpl->set("d", "CLASS", '');
            $tpl->set("d", "OPTIONS", '');
            $tpl->set("d", "CAPTION", '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=$areaName&frame=4&userid=$userid").'">'.$caption.'</a>');
            $tpl->next();
        }
    }

	$_cecIterator = $_cecRegistry->getIterator("Contenido.Permissions.User.Areas");

	if ($_cecIterator->count() > 0) {
		while ($chainEntry = $_cecIterator->next()) {
		    $aInfo = $chainEntry->execute();
		    
		    foreach ($aInfo as $key => $sAreaID) {
		    	$sAreaName = false;
				$_cecIterator2 = $_cecRegistry->getIterator("Contenido.Permissions.User.GetAreaName");
				while ($chainEntry2 = $_cecIterator2->next()) {
				    $aInfo2 = $chainEntry2->execute($sAreaID);
				    if ($aInfo2 !== false) {
				    	$sAreaName = $aInfo2;
				    	break;
				    }
			    }
			    
			    if ($sAreaName !== false) {
		            # Set template data
		            $tpl->set("d", "ID", 'c_'.$tpl->dyn_cnt);
		            $tpl->set("d", "CLASS", '');
		            $tpl->set("d", "OPTIONS", '');
		            $tpl->set("d", "CAPTION", '<a class="white" onclick="sub.clicked(this)" target="right_bottom" href="'.$sess->url("main.php?area=user_external&frame=4&external_area=$sAreaID&userid=$userid").'">'.$sAreaName.'</a>');
		            $tpl->next();
			    }
		    }
		}
	}
    $tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);

    # Generate the third navigation layer
    $tpl->generate($cfg["path"]["templates"] . $cfg["templates"]["subnav"]);

} else {
    include ($cfg["path"]["contenido"].$cfg["path"]["templates"] . $cfg["templates"]["right_top_blank"]);
}

?>