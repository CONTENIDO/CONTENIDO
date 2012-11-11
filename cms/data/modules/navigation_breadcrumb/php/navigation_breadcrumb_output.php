<?php
/**
 * This module handles the breadcrumb output.
 *
 * @package Module
 * @subpackage Breadcrumb
 *
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

$breadcrumb = array();

// get category path
$helper = cCategoryHelper::getInstance();
foreach ($helper->getCategoryPath(cRegistry::getCategoryId(), 1) as $categoryLang) {
    $breadcrumb[] = $categoryLang;
}

// load current article information
$article = new cApiArticleLanguage();
$article->loadByArticleAndLanguageId(cRegistry::getArticleId(), cRegistry::getLanguageId());
$headline = strip_tags($article->getContent('CMS_HTMLHEAD', 1));

array_shift($breadcrumb);

// initialize smarty
$tpl = Contenido_SmartyWrapper::getInstance();
$tpl->assign('label_breadcrumb', mi18n("LABEL_BREADCRUMB"));
$tpl->assign('breadcrumb', $breadcrumb);
$tpl->assign('headline', $headline);
$tpl->display('navigation_breadcrumb/template/breadcrumb.tpl');
?>