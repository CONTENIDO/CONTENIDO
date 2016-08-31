<?php

/**
 *
 * @package Module
 * @subpackage ContentSitemapHtml
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
$artLang = new cApiArticleLanguage();
$artLang->loadByArticleAndLanguageId($idart, $lang);

$content = $artLang->getContent('CMS_TEXT', 1);
$level = $artLang->getContent('CMS_TEXT', 2);
$article = $artLang->getContent('CMS_TEXT', 3);

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

$tpl->display('get.tpl');

/**
 * Adds articles to categories in given array $tree as returned by
 * cCategoryHelper->getSubCategories().
 *
 * @param array $tree
 * @return array
 */
function addArticlesToTree(array $tree) {

    $startidartlang = getStartIdArtLang();

    foreach ($tree as $key => $wrapper) {
        $tree[$key]['articles'] = getArticlesFromCategory($wrapper['idcat'], $startidartlang);
        $tree[$key]['subcats'] = addArticlesToTree($tree[$key]['subcats']);
    }

    return $tree;

}

/**
 * Read the IDs of all article languages that are used as start article
 * of their respective category.
 *
 * @return array
 *         of article language IDs
 */
function getStartIdArtLang() {

    $cfg = cRegistry::getConfig();
    $db = cRegistry::getDb();

    // get all startidartlangs

    $ret = $db->query('-- getStartIdArtLang()
        SELECT
            startidartlang
        FROM
            `' . $cfg['tab']['cat_lang'] . '`
        WHERE
            visible = 1
            AND public = 1
        ;');

    $result = array();
    while ($db->nextRecord()) {
        $result[] = $db->f('startidartlang');
    }

    return $result;

}

/**
 * Read article languages of given category and the current language.
 * Only online articles that are searchable are considered.
 * Optionally an array of article language IDs to exclude can be given.
 * If no article languages were found an empty array will be returned.
 *
 * @param int $idcat
 *         ID of category to search in
 * @param array $excludedIdartlangs [optional]
 *         ID of article languages to exclude
 * @return array
 *         of article languages
 */
function getArticlesFromCategory($idcat, array $excludedIdartlangs = array()) {

    $cfg = cRegistry::getConfig();
    $db = cRegistry::getDb();
    $idlang = cRegistry::getLanguageId();

    $ret = $db->query('-- getArticlesFromCategory()
        SELECT
            art_lang.idartlang
        FROM
            `' . $cfg['tab']['art_lang'] . '` AS art_lang
            , `' . $cfg['tab']['cat_art'] . '` AS ca
        WHERE
            art_lang.idart = cat_art.idart
            AND art_lang.idlang = ' . cSecurity::toInteger($idlang) . '
            AND art_lang.online = 1
            AND art_lang.searchable = 1
            AND cat_art.idcat = ' . cSecurity::toInteger($idcat) . '
        ;');

    if (false === $ret) {
        return array();
    }

    $result = array();
    while ($db->nextRecord()) {

        // skip article languages to exclude
        if (in_array($db->f('idartlang'), $excludedIdartlangs)) {
            continue;
        }

        // add article languages to result
        $result[] = new cApiArticleLanguage($db->f('idartlang'));

    }

    return $result;

}

?>