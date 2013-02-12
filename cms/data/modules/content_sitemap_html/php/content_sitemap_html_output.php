<?php

/**
 *
 * @package Module
 * @subpackage content_sitemap_html
 * @version SVN Revision $Rev:$
 * @author marcus.gnass@4fb.de
 * @author alexander.scheider@4fb.de
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// get smarty template instance
$tpl = Contenido_SmartyWrapper::getInstance();

// get needed id's
$client = cRegistry::getClientId();
$cfgClient = cRegistry::getClientConfig();
$lang = cRegistry::getLanguageId();
$idart = cRegistry::getArticleId();

// assign module translation tags
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

// create article and get content of it
$art = new Article($idart, $client, $lang);
$content = $art->getContent("CMS_TEXT", 1);
$level = $art->getContent("CMS_TEXT", 2);
$article = $art->getContent("CMS_TEXT", 3);
// check if content is numeric
if (TRUE === is_numeric($content) && TRUE === is_numeric($level)) {

    if($article == 0 || $article == 1) {
    // get category tree
    $categoryHelper = cCategoryHelper::getInstance();
    $categoryHelper->setAuth(cRegistry::getAuth());

    $tree = $categoryHelper->getSubCategories($content, $level);
    if($article == 1) {
    $tree = addArticlesToTree($tree);
    }
    $tpl->assign('tree', $tree);
    } else {
        $tpl->assign('errorArticle', mi18n("NOT_ZERO_OR_ONE"));
    }
} else {
    // assign error message
    $tpl->assign('error', mi18n("NOT_NUMERIC_VALUE"));
}

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
    $lang = cRegistry::getLanguageId();

    // get articles from DB
    // needed fields: idart, lastmodified, sitemapprio, changefreq
    $sql = '
            SELECT
                `al`.`idart`
                , UNIX_TIMESTAMP(`al`.`lastmodified`) as lastmod
                , `al`.`changefreq`
                , `al`.`sitemapprio`
                , `al`.`title`
            FROM
                `' . $cfg['tab']['art_lang'] . '` AS `al`
                , `' . $cfg['tab']['cat_art'] . '` AS `ca`
            WHERE
                `al`.`idart` = `ca`.`idart`
                AND `al`.`idlang` = ' . $lang . '
                AND `ca`.`idcat` IN (' . $categoryId . ')
                AND `al`.`online` = 1
                AND `al`.`searchable` = 1
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

/**
 * Formats a date/time according to ISO 8601.
 * Example:
 * YYYY-MM-DDThh:mm:ss.sTZD (eg 1997-07-16T19:20:30.45+01:00)
 *
 * @param int $time a UNIX timestamp
 * @return string the formatted date string
 */
function iso8601Date($time) {
    $tzd = date('O', $time);
    $tzd = substr(chunk_split($tzd, 3, ':'), 0, 6);
    $date = date('Y-m-d\TH:i:s', $time) . $tzd;

    return $date;
}

$tpl->assign('isBackendEditMode', cRegistry::isBackendEditMode());
echo $tpl->fetch('content_sitemap_html/template/get.tpl');

?>