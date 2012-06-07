<?php
/**
* $RCSfile$
*
* Description:
* Main Navigation, displays Navigation from a starting idcat down to the max. level set.
* For now (as of 2009-01-12) all subcategories are put inside one <ul> and are assigned css classes
* to distinguish level depth, first/last and active item property.
*
* Module requires two client settings:
* navigation_main_standard | start_idcat
* navigation_main_standard | level_depth
*
* To modify the behaviour of the module (e.g. style of URL, CSS classes, ...), you need to edit Contenido_NavMain_Util::loopCats
* @see {contenido}/Util/Modules/Contenido_NavMain_Util.class.php
*
* @version 1.0.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2009-01-12
* }}
*
* $Id$
*/

if (!isset($db)) {
    $db = new DB_Contenido();
}
if (!isset($tpl)) {
    $tpl = new Template();
}
$tpl->reset();

cInclude('classes', 'Contenido_FrontendNavigation/Contenido_FrontendNavigation.class.php');
cInclude('classes', 'Util/Modules/Contenido_NavMain_Util.class.php');

$iStartIdcat = getEffectiveSetting('navigation', 'idcat-home', 1);
$iSelectedDepth = getEffectiveSetting('navigation', 'level-depth', 3);

if ($iStartIdcat > 0) {
    $oFeNav = new Contenido_FrontendNavigation($db, $cfg, $client, $lang, $cfgClient);
    $oFeNav->setAuth($auth);
    $oFeNav->setRootCat($iStartIdcat);
    $oSubCategories = $oFeNav->getSubCategories($iStartIdcat, true, true, 1);
    // see if there are any subcategories to display
    if ($oSubCategories->count() > 0) {
        $aLevelInfo = array();
        $aDepthInfo = array();
        $aDepthInfo[0] = 0;
        $aDepthInfo[1] = $iSelectedDepth;
        foreach ($oSubCategories as $oSubCategory) {
            Contenido_NavMain_Util::loopCats($oSubCategory, $oFeNav, $tpl, $cfg, $lang, $aLevelInfo, intval($idcat), $aDepthInfo);
        }
        $tpl->generate('templates/navigation_standard.html');
    }
} else {
    echo '<p>Navigation not configured correctly.</p>';
}
?>