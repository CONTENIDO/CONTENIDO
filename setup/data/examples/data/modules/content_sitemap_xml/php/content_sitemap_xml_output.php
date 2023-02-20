<?php

/**
 * Generate an XML sitemap.
 *
 * The module configuration allows for the selection of a category which is used
 * as root to determine articles that will be listed in the sitemap.
 *
 * An optional filename can be defined too. If no filename is given, the sitemap
 * is displayed immediately. With a filename the sitemap is written to the given
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
 * Executes the chain 'Contenido.Content.XmlSitemapCreate' and passes the generated
 * sitemap xml to the chain function where you are able to modify the xml.
 *
 * @package Module
 * @subpackage ContentSitemapXml
 * @author simon.sprankel@4fb.de
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG
 * @link http://www.4fb.de
 * @see http://www.sitemaps.org/
 */
if (cRegistry::getBackendSessionId() === NULL) {
    if (!class_exists('ModuleContentSitemapXml')) {
        cInclude('module', 'class.module_content_sitemap_xml.php');
    }

    $cfg = cRegistry::getConfig();

    $moduleContentSitemapXml = new ModuleContentSitemapXml([
        'cfg' => $cfg,
        'cronLogPath' => $cfg['path']['contenido_cronlog'],
        'db' => cRegistry::getDb(),
        'uriBuilder' => cUri::getInstance(),
        'catUrlForStartArt' => getEffectiveSetting('content-sitemap-xml', 'cat-url-for-startart', 'true'),
        'msgXmlWriteSuccess' => mi18n("XML sitemap successfully written to %s"),
        'msgXmlWriteFail' => mi18n("XML sitemap could not be written to %s"),
    ]);

    $client = cRegistry::getClientId();

    // get idcat of category to generate sitemap from
    $idcatStart = "CMS_VALUE[1]";
    $idcatStart = cSecurity::toInteger($idcatStart);

    // get filename to save sitemap to (optional)
    $filename = "CMS_VALUE[2]";
    if (!empty($filename)) {
        $filename = basename($filename);
        // assert .xml extension
        if (cString::getPartOfString($filename, -4) !== '.xml') {
            $filename .= '.xml';
        }
    }

    try {
        // check if this is a rerun (a cException will then be thrown)
        // check is skipped when 'rerun' is forced
        if (!empty($filename) && !array_key_exists('rerun', $_REQUEST)) {
            $moduleContentSitemapXml->checkJobRerun('xml_sitemap_' . cRegistry::getClient()->get('name') . '_' . cRegistry::getLanguage()->get('name') . '_' . cRegistry::getArticleLanguageId());
        }

        // get all categories recursively
        $categoryCollection = new cApiCategoryCollection();
        $categoryIds = $categoryCollection->getAllCategoryIdsRecursive($idcatStart, $client);

        $xmlString = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>
EOD;

        $sitemap = new SimpleXMLElement($xmlString);

        $itemCount = [];

        // loop all languages of current client
        $clientLanguageCollection = new cApiClientLanguageCollection();
        foreach ($clientLanguageCollection->getLanguagesByClient($client) as $currentIdlang) {

            // skip nonexistent or inactive languages
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

            $itemCount[] = $moduleContentSitemapXml->addArticlesToSitemap(
                $sitemap, $currentCategoryIds, cSecurity::toInteger($currentIdlang)
            );
        }

        // if there are items
        if (0 < array_sum($itemCount)) {
            // provide the possibility to alter the sitemap content
            $sitemap = cApiCecHook::executeAndReturn('Contenido.Content.XmlSitemapCreate', $sitemap);
        }

        // echo sitemap or write it to file with the specified filename
        $moduleContentSitemapXml->saveSitemap($sitemap, $filename);
    } catch (cException $e) {
        echo "\n\n[" . date('Y-m-d') . "] " . $e->getMessage() . "\n";
    }
} else {
    echo mi18n("Please open this article in frontend in order to output the XML sitemap or to store it on the file system.");
}

?>