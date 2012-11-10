<?php
$iSelectedCat = intval("CMS_VALUE[1]");
$iSelectedDepth = intval("CMS_VALUE[2]");

$tpl = new cTemplate();

$frontendHelper = cFrontendHelper::getInstance();
$frontendHelper->renderSitemap($iSelectedCat, $iSelectedDepth, $tpl);

$tpl->generate('templates/sitemap_standard.html');
?>