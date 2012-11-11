<?php

// get client settings
$rootIdcat = getEffectiveSetting('navigation_main', 'idcat', 1);
$depth = getEffectiveSetting('navigation_main', 'depth', 3);

$idcat = cRegistry::getCategoryId();

// get category tree
$auth = cRegistry::getAuth();
$categoryHelper = cCategoryHelper::getInstance();
$categoryHelper->setAuth($auth);
$tree = $categoryHelper->getSubCategories($rootIdcat, $depth);

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