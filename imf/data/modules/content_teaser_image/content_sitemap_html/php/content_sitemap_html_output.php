<?php

/**
 *
 * @package Module
 * @subpackage ContentSitemapHtml
 * @version SVN Revision $Rev:$
 *
 * @version SVN Revision $Rev:$
 * @author marcus.gnass@4fb.de
 * @author alexander.scheider@4fb.de
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// get globals
$client = cRegistry::getClientId();
$lang = cRegistry::getLanguageId();
$idart = cRegistry::getArticleId();

// get content of current article
$art = new cApiArticleLanguage();
$art->loadByArticleAndLanguageId($idart, $lang);
$content = $art->getContent('CMS_TEXT', 1);
$level = $art->getContent('CMS_TEXT', 2);
$article = $art->getContent('CMS_TEXT', 3);

// get smarty template instance
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('isBackendEditMode', cRegistry::isBackendEditMode());

// assign module translations
$tpl->assign('trans', array(
    'headline' => mi18n("HEADLINE"),
    'categoryLabel' => mi18n("CATEGORY_LABEL"),
    'levelLabel' => mi18n("LEVEL_LABEL"),
    'articleLabel' => mi18n("ARTICLE_LABEL"),
    'articleHintLabel' => mi18n("ARTICLE_HINT_LABEL"),
    'categoryHintLabel' => mi18n("GATEGORY_HINT_LABEL"),
    'levelHintLabel' => mi18n("LEVEL_HINT_LABEL")
));

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
        $tree = addArticlesToTree($tree);
    }
    $tpl->assign('tree', $tree);
}

$tpl->display('content_sitemap_html/template/get.tpl');

/**
 * Adds articles to categories in given array $tree as returned by
 * cCategoryHelper->getSubCategories().
 *
 * @param array $tree
 * @return array
 */
function addArticlesToTree(array $tree) {

    foreach ($tree as $key => $wrapper) {
        $tree[$key]['articles'] = getArticlesFromCategory($wrapper['idcat']);
        $tree[$key]['subcats'] = addArticlesToTree($tree[$key]['subcats']);
    }

    return $tree;

}

/**
 * Add all online and searchable articles of theses categories to the sitemap.
 *
 * @param int $categoryId
 */
function getArticlesFromCategory($categoryId) {

    $cfg = cRegistry::getConfig();
    $db = cRegistry::getDb();

    // get articles from DB
    // needed fields: idart, lastmodified, sitemapprio, changefreq
    $sql = '-- getArticlesFromCategory()
        SELECT
            al.idart
            , UNIX_TIMESTAMP(al.lastmodified) AS lastmod
            , al.changefreq
            , al.sitemapprio
            , al.title
        FROM
            `' . $cfg['tab']['art_lang'] . '` AS al
            , `' . $cfg['tab']['cat_art'] . '` AS ca
        WHERE
            al.idart = ca.idart
            AND al.idlang = ' . cSecurity::toInteger(cRegistry::getLanguageId()) . '
            AND ca.idcat IN (' . $categoryId . ')
            AND al.online = 1
            AND al.searchable = 1
        ;';

    $ret = $db->query($sql);

    $array = array();
    if (false !== $ret) {
        while ($db->next_record()) {
            $article = new cApiArticleLanguage();
            $article->loadByPrimaryKey($db->f('idart'));
            $array[] = $article;
        }
    }

    return $array;

}

?>