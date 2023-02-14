<?php

/**
 * @package Module
 * @subpackage ContentSitemapXml
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

class ModuleContentSitemapXml {
    /**
     * @var array
     */
    private $cfg;

    /**
     * @var string
     */
    private $cronLogPath;

    /**
     * @var cDb
     */
    private $db;

    /**
     * @var string 'true' or 'false'
     */
    private $catUrlForStartArt;

    /**
     * @var cUri
     */
    private $uriBuilder;

    /**
     * @var string
     */
    private $msgXmlWriteSuccess;

    /**
     * @var string
     */
    private $msgXmlWriteFail;

    /**
     * ModuleContentSitemapXml constructor.
     * @param array $options
     */
    public function __construct(array $options) {
        $this->cfg = $options['cfg'];
        $this->cronLogPath = $options['cronLogPath'];
        $this->db = $options['db'];
        $this->catUrlForStartArt = $options['catUrlForStartArt'];
        $this->uriBuilder = $options['uriBuilder'];
        $this->msgXmlWriteSuccess = $options['msgXmlWriteSuccess'];
        $this->msgXmlWriteFail = $options['msgXmlWriteFail'];
    }

    /**
     * Reads timestamp from last job run and compares it to current timestamp.
     * If last run is less than 23h ago this script will be aborted. Else the
     * current timestamp is stored into job file.
     *
     * @param string $jobName
     * @throws cException if job was already executed within last 23h
     */
    public function checkJobRerun($jobName) {
        // get filename of cron job file
        $filename = $this->cronLogPath . $jobName . '.job';
        if (cFileHandler::exists($filename)) {
            // get timestamp of last run from cron job file
            $cronLogContent = file_get_contents($filename);
            $lastRun = cSecurity::toInteger($cronLogContent);
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
     * Add all online and searchable articles of these categories to the sitemap.
     *
     * @param SimpleXMLElement $sitemap
     * @param array $categoryIds
     * @param int $lang
     * @return int
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function addArticlesToSitemap(SimpleXMLElement $sitemap, array $categoryIds, $lang) {
        $itemCount = 0;

        // check if there are categories
        if (0 < count($categoryIds)) {
            $tab = $this->cfg['tab'];

            $useCategoryUrlsForStartArticles = 'true' == $this->catUrlForStartArt;

            $lang = cSecurity::toInteger($lang);
            $categoryIds = implode(',', array_map(function($categoryId) {
                return cSecurity::toInteger($categoryId);
            }, $categoryIds));

            // get articles from DB
            $this->db->query("
            SELECT
                art_lang.idart
                , art_lang.idartlang
                , UNIX_TIMESTAMP(art_lang.lastmodified) AS lastmod
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
            while ($this->db->nextRecord()) {
                $indexState = conGetMetaValue($this->db->f('idartlang'), 7);

                if (preg_match('/noindex/', $indexState)) {
                    continue;
                }

                $params = [
                    'lang' => $lang,
                    'changelang' => $lang,
                ];

                // if it is a startarticle the generated URL should be that of
                // the category (assuming the navigation contains category URLs)
                if (1 == $this->db->f('is_start') && $useCategoryUrlsForStartArticles) {
                    $params['idcat'] = $this->db->f('idcat');
                } else {
                    $params['idart'] = $this->db->f('idart');
                }

                $loc = $this->uriBuilder->build($params, true);
                $loc = htmlentities($loc);

                $this->addUrl($sitemap, [
                    // construct the link
                    'loc' => $loc,
                    // construct the last modified date in ISO 8601
                    'lastmod' => (int) $this->db->f('lastmod'),
                    // get the sitemap change frequency
                    'changefreq' => $this->db->f('changefreq'),
                    // get the sitemap priority
                    'priority' => $this->db->f('sitemapprio')
                ]);
                $itemCount++;
            }
        }

        return $itemCount;
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
    public function saveSitemap(SimpleXMLElement $sitemap, $filename = '') {
        if (empty($filename)) {
            header('Content-type: text/xml');
            echo $sitemap->asXML();
        } else if ($sitemap->asXML(cRegistry::getFrontendPath() . $filename)) {
            echo conHtmlSpecialChars(sprintf($this->msgXmlWriteSuccess, $filename));
        } else {
            echo conHtmlSpecialChars(sprintf($this->msgXmlWriteFail, $filename));
        }
    }

    /**
     *
     * @param SimpleXMLElement $sitemap
     * @param array $data
     */
    protected function addUrl(SimpleXMLElement $sitemap, array $data) {
        $url = $sitemap->addChild('url');

        $url->addChild('loc', $data['loc']);

        if ($data['lastmod'] == '0000-00-00 00:00:00' || $data['lastmod'] == '') {
            $url->addChild('lastmod', conHtmlSpecialChars($this->iso8601Date(time())));
        } else {
            $url->addChild('lastmod', conHtmlSpecialChars($this->iso8601Date($data['lastmod'])));
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
    protected function iso8601Date($time) {
        $tzd = date('O', $time);
        $tzd = chunk_split($tzd, 3, ':');
        $tzd = cString::getPartOfString($tzd, 0, 6);
        $date = date('Y-m-d\TH:i:s', $time);
        return $date . $tzd;
    }

}
