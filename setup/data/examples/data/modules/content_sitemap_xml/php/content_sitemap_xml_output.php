<?php

/**
 * Generate a XML sitemap.
 *
 * The module configuration allows for the selection of a category which is used
 * as root to determine articles that will be listed in the sitemap.
 *
 * An optional filename can be defined too. If no filename is given, the sitemap
 * is displayed immediatly. With a filename the sitemap is written to the given
 * file. The filename has to be a basename (no path). The clients frontend path
 * is used instead. In this case this module makes sure that the sitemap is
 * generated only once each 23h.
 *
 * SETTING: content-sitemap-xml/cat-url-for-startart (default: true)
 * If set to true for all startarticles the URL is generated for their category
 * instead for the article itself.
 * This should be done if the navigation produces category links which is
 * usually the case..
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
 * @see http://www.sitemaps.org/
 */

$client = cRegistry::getClientId();
$cfgClient = cRegistry::getClientConfig();

// get idcat of category to generate sitemap from
$idcatStart = "CMS_VALUE[1]";
$idcatStart = cSecurity::toInteger($idcatStart);

// get filename to save sitemap to (optional)
$filename = "CMS_VALUE[2]";
if (!empty($filename)) {
    $filename = basename($filename);
    // assert .xml extension
    if (substr($filename, -4) !== '.xml') {
        $filename .= '.xml';
    }
}

try {

    // check if this is a rerun (a cException will then be thrown)
    // check is skipped when 'rerun' is forced
    if (!empty($filename) && !array_key_exists('rerun', $_REQUEST)) {
        checkJobRerun('xml_sitemap_' . cRegistry::getClient()->get('name') . '_' . cRegistry::getLanguage()->get('name'));
    }

    // get all categories recursively
    $categoryCollection = new cApiCategoryCollection();
    $categoryIds = $categoryCollection->getAllCategoryIdsRecursive($idcatStart, $client);

    $xmlString = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>
EOD;

    $sitemap = new SimpleXMLElement($xmlString);

    $itemCount = array();

    // loop all languages of current client
    $clientLanguageCollection = new cApiClientLanguageCollection();
    foreach ($clientLanguageCollection->getLanguagesByClient($client) as $currentIdlang) {

        // skip nonexistant or incative languages
        $language = new cApiLanguage($currentIdlang);
        if (!$language->isLoaded() || '1' != $language->get('active')) {
            continue;
        }

        // create copy of category ids
        $arrayObject = new ArrayObject($categoryIds);
        $currentCategoryIds = $arrayObject->getArrayCopy();

        // filter the categories - category must be visible and public!
        foreach ($currentCategoryIds as $key => $categoryId) {
            $categoryLanguage = new cApiCategoryLanguage();
            $categoryLanguage->loadByCategoryIdAndLanguageId($categoryId, $currentIdlang);
            if ($categoryLanguage->get('visible') == false || $categoryLanguage->get('public') == false) {
                unset($currentCategoryIds[$key]);
            }
        }

        $itemCount[] = addArticlesToSitemap($sitemap, $currentCategoryIds, $currentIdlang);
    }

    // if there are items
    if (0 < array_sum($itemCount)) {
        // provide the possibility to alter the sitemap content
        $sitemap = cApiCecHook::executeAndReturn('Contenido.Content.XmlSitemapCreate', $sitemap);
    }

    // echo sitemap or write it to file with the specified filename
    saveSitemap($sitemap, $filename);
} catch (cException $e) {
    echo "\n\n[" . date('Y-m-d') . "] " . $e->getMessage() . "\n";
}

/**
 * Reads timestamp from last job run and compares it to current timestamp.
 * If last run is less than 23h ago this script will be aborted. Elsethe
 * current timestamp is stored into job file.
 *
 * @param unknown_type $jobname
 * @throws cException if job was already executed within last 23h
 */
function checkJobRerun($jobname) {
    // get filename of cron job file
    $cfg = cRegistry::getConfig();
    $filename = $cfg['path']['contenido_cronlog'] . $jobname . '.job';
    if (cFileHandler::exists($filename)) {
        // get timestamp of last runf from cron job file
        $cronlogContent = file_get_contents($filename);
        $lastRun = cSecurity::toInteger($cronlogContent);
        // check timestamp of last run
        if ($lastRun > strtotime('-23 hour')) {
            // abort if last run is less than 23h ago
            throw new cException('job was already executed within last 23h');
        }
    }
    // store current timestamp in cronjob file
    file_put_contents($filename, time());
}

/**
 * Add all online and searchable articles of theses categories to the sitemap.
 *
 * @param SimpleXMLElement $sitemap
 */
function addArticlesToSitemap(SimpleXMLElement $sitemap, $categoryIds, $lang) {
    $itemCount = 0;

    // check if there are categories
    if (0 < count($categoryIds)) {

        $cfg = cRegistry::getConfig();
        $tab = $cfg['tab'];
        $db = cRegistry::getDb();

        $useCategoryUrlsForStartArticles = 'true' == getEffectiveSetting('content-sitemap-xml', 'cat-url-for-startart', 'true');

        $categoryIds = implode(',', $categoryIds);

        // get articles from DB
        $db->query("
            SELECT
                art_lang.idart
                , art_lang.idartlang
                , UNIX_TIMESTAMP(art_lang.lastmodified) as lastmod
                , art_lang.changefreq
                , art_lang.sitemapprio
                , cat_art.idcat
                , IF(art_lang.idartlang = cat_lang.startidartlang, 1, 0) AS is_start
            FROM
                `$tab[art_lang]` AS art_lang
                , `$tab[cat_art]` AS cat_art
                , `$tab[cat_lang]` AS cat_lang
            WHERE
                art_lang.idart = cat_art.idart
                AND art_lang.idlang = $lang
                AND art_lang.online = 1
                AND cat_art.idcat = cat_lang.idcat
                AND cat_art.idcat IN ($categoryIds)
                AND cat_lang.idlang = $lang
            ;");

        // construct the XML node
        while ($db->nextRecord()) {
            $indexState = conGetMetaValue($db->f('idartlang'), 7);

            if (preg_match('/noindex/', $indexState)) {
                continue;
            }

            $params = array();
            $params['lang'] = $lang;
            $params['changelang'] = $lang;

            // if it is a startarticle the generated URL should be that of
            // the category (assuming the navigation contains category URLs)
            if (1 == $db->f('is_start') && $useCategoryUrlsForStartArticles) {
                $params['idcat'] = $db->f('idcat');
            } else {
                $params['idart'] = $db->f('idart');
            }

            $loc = cUri::getInstance()->build($params, true);
            $loc = htmlentities($loc);

            addUrl($sitemap, array(
                // construct the link
                'loc' => $loc,
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
 * @todo How can I save this properly formatted?
 * @see http://stackoverflow.com/questions/1191167/format-output-of-simplexml-asxml
 * @param SimpleXMLElement $sitemap the XML structure of the sitemap
 * @param string $filename [optional] the filename to which the sitemap should
 *        be written
 */
function saveSitemap(SimpleXMLElement $sitemap, $filename = '') {
    if (empty($filename)) {
        header('Content-type: text/xml');
        echo $sitemap->asXML();
    } else if ($sitemap->asXML($cfgClient[$client]['path']['frontend'] . $filename)) {
        echo mi18n("XML sitemap successfully written to %s", $filename);
    } else {
        echo mi18n("XML sitemap could not be written to %s", $filename);
    }
}

?>