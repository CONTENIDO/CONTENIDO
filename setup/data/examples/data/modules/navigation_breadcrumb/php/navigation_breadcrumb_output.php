<?php

/**
 * This module handles the breadcrumb output.
 *
 * @package Module
 * @subpackage NavigationBreadcrumb
 * @author dominik.ziegler@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

// get category path
$categoryHelper = cCategoryHelper::getInstance();
$categoryHelper->setAuth(cRegistry::getAuth());
$categories = $categoryHelper->getCategoryPath(cRegistry::getCategoryId(), 1);

// get breadcrumb (w/o first level)
$breadcrumb = [];
foreach ($categories as $categoryLang) {
    $breadcrumb[] = $categoryLang;
}
array_shift($breadcrumb);

$headline = '';

// // optionally load current article headline
// $article = new cApiArticleLanguage();
// $article->loadByArticleAndLanguageId(cRegistry::getArticleId(), cRegistry::getLanguageId());
// $headline = strip_tags($article->getContent('CMS_HTMLHEAD', 1));

// build template
$smarty = cSmartyFrontend::getInstance();
$smarty->assign('label_breadcrumb', mi18n("LABEL_BREADCRUMB"));
$smarty->assign('breadcrumb', $breadcrumb);
$smarty->assign('headline', $headline);
$smarty->display('get.tpl');

?>