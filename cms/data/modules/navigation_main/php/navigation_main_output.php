<?php

/**
 * description: main navigation
 *
 * @package Module
 * @subpackage navigation_main
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
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
$path = array_map(function (cApiCategoryLanguage $categoryLanguage) {
    return $categoryLanguage->get('idcat');
}, $categoryHelper->getCategoryPath(cRegistry::getCategoryId(), 1));

// use template to display navigation
$tpl = Contenido_SmartyWrapper::getInstance();
global $force;
if (1 == $force) {
    $tpl->clearAllCache();
}
$tpl->assign('ulId', 'navigation');
$tpl->assign('tree', $tree);
$tpl->assign('path', $path);
$tpl->display('navigation_main/template/get.tpl');

?>