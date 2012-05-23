<?php
/**
 * Project:
 * contenido.org
 *
 * Description:
 * Simple sitemap.
 * Can output all categories in one list or each main category as separate list
 *
 * To modify the behaviour of the module (e.g. style of URL, CSS classes, ...), you need to edit Contenido_Sitemap_Util::loopCats
 * @see {frontend}/includes/Util/Modules/Contenido_Sitemap_Util.class.php
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @version    0.1.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 *
 * {@internal
 *   created 2009-01-05
 *   $Id: Sitemap_Standard.php 3438 2009-01-15 10:54:05Z rudi.bieller $
 * }}
 *
 */

if (!isset($db)) {
    $db = new DB_Contenido();
}
if (!isset($tpl)) {
    $tpl = new Template();
}
$tpl->reset();

cInclude('classes', 'Contenido_FrontendNavigation/Contenido_FrontendNavigation.class.php');
cInclude('classes', 'Util/Modules/Contenido_Sitemap_Util.class.php');

$iSelectedCat = intval("CMS_VALUE[1]");
$iSelectedDepth = intval("CMS_VALUE[2]");
$iHtmlOutputType = intval("CMS_VALUE[3]");
$sUrlStyle = $cfg['url_builder']['name'];

if ($iSelectedCat > 0 && $iSelectedDepth >= 0) {
    $oFeNav = new Contenido_FrontendNavigation($db, $cfg, $client, $lang, $cfgClient);
    $oFeNav->setAuth($auth);
    $oSubCategories = $oFeNav->getSubCategories($iSelectedCat, true, true, 1);
    // see if there are any subcategories to display
    if ($oSubCategories->count() > 0) {
        $aDepthInfo = array();
        $aDepthInfo[0] = 0;
        $aDepthInfo[1] = $iSelectedDepth;
        if ($iHtmlOutputType == 1) {
            $sMainCats = '';
        }
        foreach ($oSubCategories as $oSubCategory) {
            switch($iHtmlOutputType) {
                case 0:
                    Contenido_Sitemap_Util::loopCats($oSubCategory, $oFeNav, $tpl, $sUrlStyle, $cfg, $lang, $aDepthInfo);
                    break;
                case 1:
                    $tpl->reset();
                    Contenido_Sitemap_Util::loopCats($oSubCategory, $oFeNav, $tpl, $sUrlStyle, $cfg, $lang, $aDepthInfo);
                    $sSubCats = $tpl->generate('templates/sitemap_standard_li.html', true);
                    $tpl->reset();
                    $tpl->set('s', 'list_items', $sSubCats);
                    $sMainCats .= $tpl->generate('templates/sitemap_standard_ul.html', true);
                    break;
                default:
                    break;
            }
        }
        if ($iHtmlOutputType == 0) {
            $tpl->generate('templates/sitemap_standard.html');
        } else {
            echo $sMainCats;
        }
    }
} else {
    echo '<p>Sitemap not configured correctly.</p>';
}
?>