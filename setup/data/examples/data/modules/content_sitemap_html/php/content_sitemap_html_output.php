<?php

/**
 *
 * @package    Module
 * @subpackage ContentSitemapHtml
 * @author     marcus.gnass@4fb.de
 * @author     alexander.scheider@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

if (!class_exists('ContentSitemapHtmlModule')) {
    cInclude('module', 'class.content_sitemap_html_module.php');
}

// get globals
$client = cRegistry::getClientId();
$lang = cRegistry::getLanguageId();
$idart = cRegistry::getArticleId();

// get content of current article
$artLang = new cApiArticleLanguage();
$artLang->loadByArticleAndLanguageId($idart, $lang);

$content = $artLang->getContent('CMS_TEXT', 1);
$level = $artLang->getContent('CMS_TEXT', 2);
$article = $artLang->getContent('CMS_TEXT', 3);

// get smarty template instance
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('isBackendEditMode', cRegistry::isBackendEditMode());

// assign module translations
$tpl->assign('trans', [
    'headline' => mi18n("HEADLINE"),
    'categoryLabel' => mi18n("CATEGORY_LABEL"),
    'levelLabel' => mi18n("LEVEL_LABEL"),
    'articleLabel' => mi18n("ARTICLE_LABEL"),
    'articleHintLabel' => mi18n("ARTICLE_HINT_LABEL"),
    'categoryHintLabel' => mi18n("GATEGORY_HINT_LABEL"),
    'levelHintLabel' => mi18n("LEVEL_HINT_LABEL")
]);

// assign CMS input fields
$tpl->assign('category', "CMS_TEXT[1]");
$tpl->assign('level', "CMS_TEXT[2]");
$tpl->assign('article', "CMS_TEXT[3]");
$tpl->assign('first', false);

// check if content is numeric
if (false === is_numeric($content) || false === is_numeric($level)) {
    $tpl->assign('error', mi18n("NOT_NUMERIC_VALUE"));
} else if ($article != 0 && $article != 1) {
    $tpl->assign('error', mi18n("NOT_ZERO_OR_ONE"));
} else {
    // get category tree
    $categoryHelper = cCategoryHelper::getInstance();
    $categoryHelper->setAuth(cRegistry::getAuth());
    $tree = $categoryHelper->getSubCategories($content, $level);
    if (1 == $article) {
        $contentSitemapHtmlModule = new ContentSitemapHtmlModule([
            'db' => cRegistry::getDb(),
            'idlang' => cRegistry::getLanguageId(),
        ]);
        $tree = $contentSitemapHtmlModule->addArticlesToTree($tree);
    }
    $tpl->assign('tree', $tree);
}

$tpl->display('get.tpl');

?>