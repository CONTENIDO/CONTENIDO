<?php

/**
 * description: top navigation
 *
 * @package Module
 * @subpackage NavigationTop
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

// assert framework initialization
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// get client settings
$rootIdcat = getEffectiveSetting('navigation_top', 'idcat', 1);
$depth = getEffectiveSetting('navigation_top', 'depth', 3);

// get category tree
$categoryHelper = cCategoryHelper::getInstance();
$categoryHelper->setAuth(cRegistry::getAuth());
$tree = $categoryHelper->getSubCategories($rootIdcat, $depth);

// get path (breadcrumb) of current category
if (!function_exists("navigation_top_filter")) {
    /**
     *
     * @param cApiCategoryLanguage $categoryLanguage
     * @return int
     */
    function navigation_top_filter(cApiCategoryLanguage $categoryLanguage) {
        return $categoryLanguage->get('idcat');
    }
}
$path = array_map('navigation_top_filter', $categoryHelper->getCategoryPath(cRegistry::getCategoryId(), 1));

// use template to display navigation
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('tree', $tree);
$tpl->assign('path', $path);
$tpl->display('get.tpl');

?>