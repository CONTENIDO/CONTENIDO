<?php
$startIdcat = getEffectiveSetting('navigation', 'idcat-home', 1);
$selectedDepth = getEffectiveSetting('navigation', 'level-depth', 3);

$frontendHelper = cFrontendHelper::getInstance();
$navigation = $frontendHelper->renderNavigation($startIdcat, $selectedDepth, cRegistry::getCategoryId());

// use smarty template to output header text
$tpl = Contenido_SmartyWrapper::getInstance();
if (1 == $force) {
    $tpl->clearAllCache();
}

$tpl->assign('navigation_data', $navigation);
$tpl->display('navigation_main/template/navigation.tpl');
?>