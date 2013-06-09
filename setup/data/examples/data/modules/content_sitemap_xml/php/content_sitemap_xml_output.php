<?php

/**
 *
 * @package Module
 * @subpackage ContentSitemapXml
 * @version SVN Revision $Rev:$
 *
 * @version SVN Revision $Rev:$
 * @author simon.sprankel@4fb.de
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

$client = cRegistry::getClientId();
$cfgClient = cRegistry::getClientConfig();
$lang = cRegistry::getLanguageId();

$selected = "CMS_VALUE[1]";
$filename = "CMS_VALUE[2]";

$selected = cSecurity::toInteger($selected);

// filter the filename value
if (!empty($filename)) {
    $filename = basename($filename);
    // make sure that the filename ends with .xml
    if (substr($filename, -4) !== '.xml') {
        $filename .= '.xml';
    }
    $filename = $cfgClient[$client]['sitemap']['path'] . $filename;
}

// get all categories recursively
$categoryCollection = new cApiCategoryCollection();
$categoryIds = $categoryCollection->getAllCategoryIdsRecursive($selected, $client);

// filter the categories - category must be visible and public!
foreach ($categoryIds as $key => $categoryId) {
    $categoryLanguage = new cApiCategoryLanguage();
    $categoryLanguage->loadByCategoryIdAndLanguageId($categoryId, $lang);
    if ($categoryLanguage->get('visible') == false || $categoryLanguage->get('public') == false) {
        unset($categoryIds[$key]);
    }
}

$xmlString = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>
EOD;

$sitemap = new SimpleXMLElement($xmlString);

$itemCount = 0;
$itemCount += addArticlesToSitemap($sitemap, $categoryIds);

// if there are items
if (0 < $itemCount) {
    // provide the possibility to alter the sitemap content
    $sitemap = cApiCecHook::executeAndReturn('Contenido.Content.XmlSitemapCreate', $sitemap);
}

// echo sitemap or write it to file with the specified filename
saveSitemap($sitemap, $filename);

/**
 * Add all online and searchable articles of theses categories to the sitemap.
 *
 * @param SimpleXMLElement $sitemap
 */
function addArticlesToSitemap(SimpleXMLElement $sitemap, $categoryIds) {
    $cfg = cRegistry::getConfig();
    $db = cRegistry::getDb();
    $lang = cRegistry::getLanguageId();

    $itemCount = 0;

    // check if there are categories
    if (0 < count($categoryIds)) {

        // get articles from DB
        // needed fields: idart, lastmodified, sitemapprio, changefreq
        $sql = '
            SELECT
                `al`.`idart`
                , UNIX_TIMESTAMP(`al`.`lastmodified`) as lastmod
                , `al`.`changefreq`
                , `al`.`sitemapprio`
            FROM
                `' . $cfg['tab']['art_lang'] . '` AS `al`
                , `' . $cfg['tab']['cat_art'] . '` AS `ca`
            WHERE
                `al`.`idart` = `ca`.`idart`
                AND `al`.`idlang` = ' . $lang . '
                AND `ca`.`idcat` IN (' . implode(',', $categoryIds) . ')
                AND `al`.`online` = 1
                AND `al`.`searchable` = 1
            ;';

        $db->query($sql);

        // construct the XML node
        while ($db->next_record()) {
            addUrl($sitemap, array(
                // construct the link
                'loc' => cUri::getInstance()->build(array(
                    'idart' => $db->f('idart'),
                    'lang' => $lang
                ), true),
                // construct the last modified date in ISO 8601
                'lastmod' => (int) $db->f('lastmod'),
                // get the sitemap change frequency
                'changefreq' => $db->f('changefreq'),
                // get the sitemap priority
                'priority' => $db->f('sitemapprio')
            ));
            $itemCount++;
        }
    }

    return $itemCount;
}

/**
 *
 * @param SimpleXMLElement $sitemap
 * @param array $data
 */
function addUrl(SimpleXMLElement $sitemap, array $data) {
    $url = $sitemap->addChild('url');

    $url->addChild('loc', $data['loc']);

    if ($data['lastmod'] == '0000-00-00 00:00:00' || $data['lastmod'] == '') {
        $url->addChild('lastmod', htmlspecialchars(iso8601Date(mktime())));
    } else {
        $url->addChild('lastmod', htmlspecialchars(iso8601Date($data['lastmod'])));
    }

    if (!empty($data['changefreq'])) {
        $url->addChild('changefreq', $data['changefreq']);
    }

    if (!empty($data['priority']) || $data['priority'] == 0) {
        $url->addChild('priority', $data['priority']);
    }
}

/**
 * Formats a date/time according to ISO 8601.
 *
 * Example:
 * YYYY-MM-DDThh:mm:ss.sTZD (eg 1997-07-16T19:20:30.45+01:00)
 *
 * @param int $time a UNIX timestamp
 * @return string the formatted date string
 */
function iso8601Date($time) {
    $tzd = date('O', $time);
    $tzd = chunk_split($tzd, 3, ':');
    $tzd = substr($tzd, 0, 6);

    $date = date('Y-m-d\TH:i:s', $time);

    return $date . $tzd;
}

/**
 * Saves the sitemap to the file with the given filename.
 * If no filename is given, it outputs the sitemap.
 *
 * @param SimpleXMLElement $sitemap the XML structure of the sitemap
 * @param string $filename [optional] the filename to which the sitemap should
 *        be written
 */
function saveSitemap(SimpleXMLElement $sitemap, $filename = '') {
    $cfgClient = cRegistry::getClientConfig();
    $client = cRegistry::getClientId();
    if (0 === strlen($filename)) {
        header('Content-type: text/xml');
        echo $sitemap->asXML();
    } else {
        $success = $sitemap->asXML($filename);
        if ($success) {
            echo mi18n("XML sitemap successfully written to %s", $filename);
        } else {
            echo mi18n("XML sitemap could not be written to %s", $filename);
        }
    }
}

?>