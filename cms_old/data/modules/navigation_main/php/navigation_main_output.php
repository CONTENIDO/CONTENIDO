<?php
$iStartIdcat = getEffectiveSetting('navigation', 'idcat-home', 1);
$iSelectedDepth = getEffectiveSetting('navigation', 'level-depth', 3);

$tpl = new cTemplate();

$frontendHelper = cFrontendHelper::getInstance();
$frontendHelper->renderNavigation($iStartIdcat, $iSelectedDepth, $idcat, $tpl);

$tpl->generate('templates/navigation_standard.html');
?>