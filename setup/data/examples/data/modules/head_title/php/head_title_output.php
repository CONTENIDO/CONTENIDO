<?php
/**
 * This module handles the content of the title element.
 *
 * @package Module
 * @subpackage HeadTitle
 * @author dominik.ziegler@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

$breadcrumb = [];

// get category path
$helper = cCategoryHelper::getInstance();
foreach ($helper->getCategoryPath(cRegistry::getCategoryId(), 1) as $categoryLang) {
    $breadcrumb[] = $categoryLang->get('name');
}

// load current article information
$article = new cApiArticleLanguage();
$article->loadByArticleAndLanguageId(cRegistry::getArticleId(), cRegistry::getLanguageId());
$headline = strip_tags($article->getContent('CMS_HTMLHEAD', 1));

// append headline of article if existing
if ($headline != '') {
    $breadcrumb[] = $headline;
}

if ($headline === '') {
    $breadcrumb[] = conHtmlSpecialChars(mi18n("STARTPAGE"));
}

array_shift($breadcrumb);

if (count($breadcrumb) > 0) {
    echo implode(' - ', $breadcrumb);
}

?>