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

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call: Missing framework initialization - request aborted.');
}

// get client settings
$rootIdcat = getEffectiveSetting('navigation_main', 'idcat', 1);
$depth = getEffectiveSetting('navigation_main', 'depth', 3);

// get category tree
$auth = cRegistry::getAuth();
$categoryHelper = cCategoryHelper::getInstance();
$categoryHelper->setAuth($auth);
$tree = $categoryHelper->getSubCategories($rootIdcat, $depth);

// get current idcat
$idcat = cRegistry::getCategoryId();

// get breadcrumb of current category
$helper = cCategoryHelper::getInstance();
$path = array_map(function (cApiCategoryLanguage $categoryLanguage) {
    return $categoryLanguage->get('idcat');
}, $helper->getCategoryPath($idcat, 1));

// use smarty template to output header text
$tpl = Contenido_SmartyWrapper::getInstance();
global $force;
if (1 == $force) {
    $tpl->clearAllCache();
}
$tpl->assign('ulId', 'navigation');
$tpl->assign('tree', $tree);
$tpl->assign('actualIdcat', $idcat);
$tpl->assign('path', $path);
$tpl->display('navigation_main/template/navigation.tpl');

?>