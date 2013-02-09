<?php

/**
 *
 * @package Module
 * @subpackage
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 *
 * @version SVN Revision $Rev:$
 * @author alexander.scheider@4fb.de
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

$tpl = Contenido_SmartyWrapper::getInstance();

$client = cRegistry::getClientId();
$cfgClient = cRegistry::getClientConfig();
$lang = cRegistry::getLanguageId();
$idart = cRegistry::getArticleId();
$catArray = array();
$artArray = array();

if (cRegistry::isBackendEditMode()) {
    echo "CMS_TEXT[1]";
}

$art = new Article($idart, $client, $lang);
$content = $art->getContent("CMS_TEXT", 1);

if (TRUE === is_numeric($content)) {

    // // get all categories recursively
    // $categoryCollection = new cApiCategoryCollection();
    // $categoryIds = $categoryCollection->getAllCategoryIdsRecursive($content,
    // $client);

    // get category tree
    $categoryHelper = cCategoryHelper::getInstance();
    $categoryHelper->setAuth(cRegistry::getAuth());

    $tree = $categoryHelper->getSubCategories($content, 10);
    $tree = addArticlesToTree($tree);

    $tpl->assign('tree', $tree);
} else {
    $tpl->assign('error', 'NOT_NUMERIC_VALUE');
}

$itemCount = 0;
// $itemCount += addArticlesToSitemap($sitemap, $categoryIds);

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
 * @param int
 */
function getArticlesFromCategory($categoryId) {

    $cfg = cRegistry::getConfig();
    $db = cRegistry::getDb();
    $lang = cRegistry::getLanguageId();

    $itemCount = 0;

    // check if there are categories

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
            $array[] = $db->toArray();
        }
    }

    return $array;

}

// /**
// *
// * @param SimpleXMLElement $sitemap
// * @param array $data
// */
// function addUrl(SimpleXMLElement $sitemap, array $data) {

// $url = $sitemap->addChild('url');

// $url->addChild('loc', $data['loc']);

// if ($data['lastmod'] == '0000-00-00 00:00:00' || $data['lastmod'] == '') {
// $url->addChild('lastmod', htmlspecialchars(iso8601Date(mktime())));
// } else {
// $url->addChild('lastmod', htmlspecialchars(iso8601Date($data['lastmod'])));
// }

// if (!empty($data['changefreq'])) {
// $url->addChild('changefreq', $data['changefreq']);
// }

// if (!empty($data['priority']) || $data['priority'] == 0) {
// $url->addChild('priority', $data['priority']);
// }

// }

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

echo $tpl->fetch('content_sitemap_html/template/get.tpl');

?>