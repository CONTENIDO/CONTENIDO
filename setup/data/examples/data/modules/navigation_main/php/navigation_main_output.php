<?php

/**
 * description: main navigation
 *
 * @package Module
 * @subpackage NavigationMain
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

// assert framework initialization
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// get client settings
$rootIdcat = getEffectiveSetting('navigation_main', 'idcat', 1);
$depth = getEffectiveSetting('navigation_main', 'depth', 3);

// get category tree
$categoryHelper = cCategoryHelper::getInstance();
$categoryHelper->setAuth(cRegistry::getAuth());
$tree = $categoryHelper->getSubCategories($rootIdcat, $depth);

// get path (breadcrumb) of current category
$filter = function(cApiCategoryLanguage $item) {
    return $item->get('idcat');
};
$path = array_map($filter, $categoryHelper->getCategoryPath(cRegistry::getCategoryId(), 1));

// use template to display navigation
$smarty = cSmartyFrontend::getInstance();
$smarty->assign('ulId', 'navigation');
$smarty->assign('tree', $tree);
$smarty->assign('path', $path);
$smarty->display('get.tpl');

?>