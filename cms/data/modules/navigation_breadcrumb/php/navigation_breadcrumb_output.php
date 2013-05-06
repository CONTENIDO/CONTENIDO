<?php

/**
 * This module handles the breadcrumb output.
 *
 * @package Module
 * @subpackage NavigationBreadcrumb
 * @version SVN Revision $Rev:$
 *
 * @author dominik.ziegler@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

// get category path
$helper = cCategoryHelper::getInstance();
$categories = $helper->getCategoryPath(cRegistry::getCategoryId(), 1);

// get breadcrumb (w/o first level)
$breadcrumb = array();
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
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('label_breadcrumb', mi18n("LABEL_BREADCRUMB"));
$tpl->assign('breadcrumb', $breadcrumb);
$tpl->assign('headline', $headline);
$tpl->display('get.tpl');

?>