<?php
$oClient = new cApiClient($client);

$aBread = array();
$aBread[] = cRegistry::getClient()->getField('name');

$helper = cCategoryHelper::getInstance();
foreach ($helper->getCategoryPath($idcat, 1) as $categoryLang) {
	$aBread[] = $categoryLang->get('name');
}

$oArticle = new cApiArticleLanguage();
$oArticle->loadByArticleAndLanguageId($idart, $lang);
$sHeadline = strip_tags($oArticle->getContent('CMS_HTMLHEAD', 1));

if ($sHeadline != '') {
	$aBread[] = $sHeadline;
}

echo implode(' - ', $aBread);
?>