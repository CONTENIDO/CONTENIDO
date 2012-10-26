<?php

$cfg = cRegistry::getConfig();
$client = cRegistry::getClientId();
$cfgClient = cRegistry::getClientConfig();
$db = cRegistry::getDb();
$lang = cRegistry::getLanguageId();

$selected = "CMS_VALUE[1]";
$filename = "CMS_VALUE[2]";
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
$categoryIdsString = implode(',', $categoryIds);

$xmlString = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>
EOD;
$sitemap = new SimpleXMLElement($xmlString);

// if there are no categories, output empty sitemap
if (empty($categoryIdsString)) {
    saveSitemap($sitemap, $filename);
    exit;
}

// get articles from DB, needed fields: idart, lastmodified, sitemapprio, changefreq
$query = 'SELECT `al`.`idart`, `al`.`lastmodified`, `al`.`sitemapprio`, `al`.`changefreq`, UNIX_TIMESTAMP(`al`.`lastmodified`) as lastmod FROM `' . $cfg['tab']['art_lang'] . '` AS `al`, `' . $cfg['tab']['cat_art'] . '` AS `ca` WHERE `al`.`idart`=`ca`.`idart` AND `al`.`idlang`=' . $lang . ' AND `ca`.`idcat` IN (' . $categoryIdsString . ') AND `al`.`online`=1 AND `al`.`searchable`=1';
$db->query($query);

while ($db->next_record()) {
    // construct the link
    $params = array(
        'idart' => $db->f('idart'),
        'lang' => $lang
    );
    $link = cUri::getInstance()->build($params, true);

    // construct the last modified date in ISO 8601
    if ($db->f('lastmodified') == '0000-00-00 00:00:00' || $db->f('lastmodified') == '') {
        $lastmod = iso8601Date(mktime());
    } else {
        $lastmod = iso8601Date($db->f('lastmod'));
    }

    // get the sitemap change frequency
    $frequency = $db->f('changefreq');

    // get the sitemap priority
    $sitemapprio = $db->f('sitemapprio');

    // construct the XML node
    $child = $sitemap->addChild('url');
    $child->addChild('loc', conHtmlSpecialChars($link));
    $child->addChild('lastmod', conHtmlSpecialChars($lastmod));
    if (!empty($frequency)) {
        $child->addChild('changefreq', $frequency);
    }
    if (!empty($sitemapprio) || $sitemapprio == 0) {
        $child->addChild('priority', $sitemapprio);
    }
}

// provide the possibility to alter the sitemap content
$sitemap = cApiCecHook::executeAndReturn('Contenido.Content.XmlSitemapCreate', $sitemap);

// echo sitemap or write it to file with the specified filename
saveSitemap($sitemap, $filename);
exit;

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

/**
 * Saves the sitemap to the file with the given filename.
 * If no filename is given, it outputs the sitemap.
 *
 * @param SimpleXMLElement $sitemap the XML structure of the sitemap
 * @param string $filename [optional] the filename to which the sitemap should be written
 */
function saveSitemap(SimpleXMLElement $sitemap, $filename = '') {
    if (empty($filename)) {
        header('Content-type: text/xml');
        echo $sitemap->asXML();
    } else {
        $cfgClient = cRegistry::getClientConfig();
        $client = cRegistry::getClientId();
        $shortFilename = $cfgClient[$client]['sitemap']['frontendpath'] . $filename;
        $success = $sitemap->asXML($filename);
        if ($success) {
            $transString = mi18n("XML_SITEMAP_SUCCES_WRITTEN", $filename);
        } else {
			$transString = mi18n("XML_SITEMAP_NOT_WRITTEN", $shortFilename);
        }
    }
}

?>