<?php

/**
 * Advanced Mod Rewrite plugin helper class.
 *
 * @package    Plugin
 * @subpackage ModRewrite
 * @author     Stefan Seifarth / stese
 * @author     Murat Purc <murat@purc.de>
 * @copyright   www.polycoder.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class to create web-safe names; it also provides several helper functions.
 *
 * @author     Stefan Seifarth / stese
 * @author     Murat Purc <murat@purc.de>
 * @package    Plugin
 * @subpackage ModRewrite
 */
class PiModRewrite extends PiModRewriteBase
{

    /**
     * Database instance
     *
     * @var cDb
     */
    private static $db;

    /**
     * The initialization method, it is to call at least once, it is also possible to call multiple times
     * if different client configuration is to load.
     * Loads configuration of the passed client and sets some properties.
     *
     * @param int $clientId Client id
     * @throws cInvalidArgumentException
     */
    public static function initialize(int $clientId)
    {
        PiModRewriteConfigurationService::getInstance()->loadConfiguration($clientId, true);
        self::$db = cRegistry::getDb();
    }

    /**
     * Check categories on web-safe name.
     * Check all categories in the main parent category on existing same web-safe name.
     *
     * @param string $urlName Websafe name to check
     * @param int $categoryId Current category id
     * @param int $languageId Current language id
     * @throws cDbException|cInvalidArgumentException
     */
    public static function isInCategories(string $urlName = '', int $categoryId = 0, int $languageId = 0): bool
    {
        // get parentid
        $parentId = 0;
        $sql = self::$db->prepare(
            "SELECT `parentid` FROM `%s` WHERE `idcat` = %d",
            cDb::getTableName('cat'),
            $categoryId
        );
        if ($record = PiModRewriteDatabaseUtil::queryAndNextRecord($sql)) {
            $parentId = $record['parentid'] > 0 ? cSecurity::toInteger($record['parentid']) : 0;
        }

        // check if a web-safe name is in this category
        $sql = self::$db->prepare(
            "SELECT COUNT(cl.idcat) AS `numcats` "
            . "FROM `%s` cl "
            . "LEFT JOIN `%s` c ON cl.idcat = c.idcat "
            . "WHERE c.parentid = %d AND cl.idlang = %d AND LOWER(cl.urlname) = LOWER('%s') AND cl.idcat <> %d",
            cDb::getTableName('cat_lang'),
            cDb::getTableName('cat'),
            $parentId,
            $languageId,
            $urlName,
            $categoryId
        );
        PiModRewriteDebugger::log($sql, __METHOD__ . ' $sql');

        if ($record = PiModRewriteDatabaseUtil::queryAndNextRecord($sql)) {
            return $record['numcats'] > 0;
        }

        return false;
    }

    /**
     * Check articles on web-safe name.
     * Check all articles in the current category on existing same web-safe name.
     *
     * @param string $urlName Websafe name to check
     * @param int $articleId Current article id
     * @param int $languageId Current language id
     * @param int $categoryId Category id
     * @throws cDbException
     * @internal This method only considers the case that articles are related to a single category.
     *           The function [@see conIsArticleUrlnameUnique()} also considers multiple categories.
     */
    public static function isInCatArticles(
        string $urlName = '',
        int $articleId = 0,
        int $languageId = 0,
        int $categoryId = 0
    ): bool {
        // handle multi-pages
        if ($categoryId == 0) {
            // get category id if not set
            $sql = self::$db->prepare(
                "SELECT `idcat` FROM `%s` WHERE `idart` = %d",
                cDb::getTableName('cat_art'),
                $articleId
            );
            if ($record = PiModRewriteDatabaseUtil::queryAndNextRecord($sql)) {
                $categoryId = ($record['idcat'] > 0) ? cSecurity::toInteger($record['idcat']) : 0;
            }
        }

        // check if the web-safe name is in this category
        $sql = self::$db->prepare(
            "SELECT COUNT(al.idart) AS `numcats` "
            . "FROM `%s` al "
            . "LEFT JOIN `%s` ca ON al.idart = ca.idart "
            . "WHERE ca.idcat = %d AND al.idlang = %d AND  LOWER(al.urlname) = LOWER('%s') AND al.idart <> %d",
            cDb::getTableName('art_lang'),
            cDb::getTableName('cat_art'),
            $categoryId,
            $languageId,
            $urlName,
            $articleId
        );
        if ($record = PiModRewriteDatabaseUtil::queryAndNextRecord($sql)) {
            return $record['numcats'] > 0;
        }

        return false;
    }

    /**
     * Set the web-safe name in the article list.
     * Insert the new web-safe name in the article list
     *
     * @param string $urlName Original name (will be converted)
     * @param int $articleId Current article id
     * @param int $languageId Current language id
     * @param int $categoryId Category id
     * @return bool True if insert was successful
     * @throws cDbException
     */
    public static function setArtWebsafeName(
        string $urlName = '',
        int $articleId = 0,
        int $languageId = 0,
        int $categoryId = 0
    ): bool {
        // get the web-safe name
        $newName = cString::cleanURLCharacters(conHtmlEntityDecode($urlName));

        // remove double or more separators
        $newName = PiModRewriteUtil::removeMultipleChars('-', $newName);

        // check if the web-safe name already exists
        if (self::isInCatArticles($newName, $articleId, $languageId, $categoryId)) {
            // create new web-safe name if exists
            $newName = $newName . $articleId;
        }

        // check again - and set the name
        if (!self::isInCatArticles($newName, $articleId, $languageId, $categoryId)) {
            // insert the web-safe name in the article list
            $sql = self::$db->prepare(
                "UPDATE `%s` SET `urlname` = '%s' WHERE `idart` = %d AND `idlang` = %d",
                cDb::getTableName('art_lang'),
                $newName,
                $articleId,
                $languageId
            );
            return cSecurity::toBoolean(self::$db->query($sql));
        } else {
            return false;
        }
    }

    /**
     * Set the web-safe name in the category list.
     * Insert the new web-safe name in the category list.
     *
     * @param string $urlName Original name (will be converted) or alias
     * @param int $categoryId Category id
     * @param int $languageId Language id
     * @return bool True if insert was successful
     * @throws cInvalidArgumentException|cDbException
     */
    public static function setCatWebsafeName(string $urlName = '', int $categoryId = 0, int $languageId = 0): bool
    {
        // create the web-safe name
        $newName = cString::cleanURLCharacters(conHtmlEntityDecode($urlName));

        // remove double or more separators
        $newName = PiModRewriteUtil::removeMultipleChars('-', $newName);

        // check if the web-safe name already exists
        if (self::isInCategories($newName, $categoryId, $languageId)) {
            // create new web-safe name if exists
            $newName = $newName . $categoryId;
        }

        // check again - and set the name
        if (!self::isInCategories($newName, $categoryId, $languageId)) {
            // update urlname
            $sql = self::$db->prepare(
                "UPDATE `%s` SET `urlname` = '%s' WHERE `idcat` = %d AND `idlang` = %d",
                cDb::getTableName('cat_lang'),
                $newName,
                $categoryId,
                $languageId
            );

            PiModRewriteDebugger::log([
                'sName' => $urlName,
                'iCatId' => $categoryId,
                'iLangId' => $languageId,
                'sNewName' => $newName
            ], __METHOD__ . ' $data');

            return cSecurity::toBoolean(self::$db->query($sql));
        } else {
            return false;
        }
    }

    /**
     * Set urlpath of category
     *
     * @param int $categoryId Category id
     * @param int $languageId Language id
     * @return bool True if insert was successful
     * @throws cDbException|cInvalidArgumentException
     */
    public static function setCatUrlPath(int $categoryId = 0, int $languageId = 0): bool
    {
        $path = self::buildRecursivePath($categoryId, $languageId);

        // update urlpath
        $sql = self::$db->prepare(
            "UPDATE `%s` SET `urlpath` = '%s' WHERE `idcat` = %d AND `idlang` = %d",
            cDb::getTableName('cat_lang'),
            $path,
            $categoryId,
            $languageId
        );

        PiModRewriteDebugger::log([
            'categoryId' => $categoryId,
            'languageId' => $languageId,
            'path' => $path
        ], __METHOD__ . ' $data');

        return cSecurity::toBoolean(self::$db->query($sql));
    }

    /**
     * Get article id and language id from article language id
     *
     * @param int $articleLanguageId Current article id
     * @return array Array with idart and idlang of current article
     * @throws cDbException
     */
    public static function getArtIdByArtlangId(int $articleLanguageId = 0): array
    {
        $sql = self::$db->prepare(
            "SELECT `idart`, `idlang` FROM `%s` WHERE `idartlang` = %d",
            cDb::getTableName('art_lang'),
            $articleLanguageId
        );
        if ($record = PiModRewriteDatabaseUtil::queryAndNextRecord($sql)) {
            return $record;
        }
        return [];
    }

    /**
     * Get article id by article web-safe name
     *
     * @param string $articleName Websafe name
     * @param ?int $categoryId Category id
     * @param ?int $languageId Language id
     * @return ?int Recent article id or NULL
     * @throws cDbException
     */
    public static function getArtIdByWebsafeName(
        string $articleName = '',
        ?int $categoryId = null,
        ?int $languageId = null
    ): ?int
    {
        if (!$languageId) {
            $languageId = cRegistry::getLanguageId();
        }
        $where = '';
        if (($languageId ?? 0) > 0) {
            $where = ' AND al.idlang = ' . $languageId;
        }
        // only article name was given
        if (!$categoryId) {
            // get all basic category ids with parentid=0
            $categoryIds = [];
            self::$db->query(
                "SELECT `idcat` FROM `%s` WHERE `parentid` = 0",
                cDb::getTableName('cat')
            );
            while (self::$db->nextRecord()) {
                $categoryIds[] = cSecurity::toInteger(self::$db->f('idcat'));
            }
            $where .= " AND ca.idcat IN (" . join(',', $categoryIds) . ")";
        } else {
            $where .= " AND ca.idcat = " . $categoryId;
        }

        $sql = self::$db->prepare(
            "SELECT al.idart
            FROM `%s` al
            LEFT JOIN `%s` ca ON al.idart = ca.idart
            WHERE LOWER(al.urlname) = LOWER('%s') $where",
            cDb::getTableName('art_lang'),
            cDb::getTableName('cat_art'),
            $articleName
        );
        if ($record = PiModRewriteDatabaseUtil::queryAndNextRecord($sql)) {
            return cSecurity::toInteger($record['idart']);
        } else {
            return NULL;
        }
    }

    /**
     * Get category name from category id and language id.
     *
     * @param int $categoryId Category id
     * @param int $languageId Language id
     * @return string Category name
     * @throws cDbException
     */
    public static function getCatName(int $categoryId = 0, int $languageId = 0): string
    {
        $cacheKey = sprintf('pluginModRewrite_getCatName_%d_%d', $categoryId, $languageId);

        $categoryName = cRegistry::getAppVar($cacheKey);
        if (isset($categoryName)) {
            return $categoryName;
        }

        $sql = self::$db->prepare(
            "SELECT `name` FROM `%s` WHERE `idcat` = %d AND `idlang` = %d",
            cDb::getTableName('cat_lang'),
            $categoryId,
            $languageId
        );
        if ($record = PiModRewriteDatabaseUtil::queryAndNextRecord($sql)) {
            $catName = $record['name'];
        } else {
            $catName = '';
        }

        cRegistry::setAppVar($cacheKey, $catName);

        return $catName;
    }

    /**
     * Function to return cat id by path.
     *
     * Caches the paths at the first call to provide faster processing at further calls.
     *
     * @param string $path Category path
     * @return int Category id
     * @throws cDbException|cInvalidArgumentException
     */
    public static function getCatIdByUrlPath(string $path): int
    {
        $clientId = cRegistry::getClientId();
        $languageId = cRegistry::getLanguageId();

        if (cString::findFirstPos($path, '/') === 0) {
            $path = cString::getPartOfString($path, 1);
        }
        if (cString::findLastPos($path, '/') === cString::getStringLength($path) - 1) {
            $path = cString::getPartOfString($path, 0, -1);
        }

        $catSeparator = '/';
        $startFromRoot = parent::getConfig('startfromroot');
        $urls2lowercase = parent::getConfig('use_lowercase_uri');

        $path = str_replace('/', parent::getConfig('category_seperator'), $path);

        $cacheKey = sprintf('pluginModRewrite_getCatIdByUrlPath_%d_%d', $clientId, $languageId);

        $pathsCache = cRegistry::getAppVar($cacheKey, []);

        if (count($pathsCache) == 0) {
            self::$db->query(
                "SELECT cl.idcat, cl.urlpath "
                . "FROM `%s` AS cl, `%s` AS c "
                . "WHERE c.idclient = %d AND c.idcat = cl.idcat AND cl.idlang = %d",
                cDb::getTableName('cat_lang'),
                cDb::getTableName('cat'),
                $clientId,
                $languageId
            );
            while (self::$db->nextRecord()) {
                $urlPath = self::$db->f('urlpath');
                if ($startFromRoot == 0 && cString::findFirstPos($urlPath, $catSeparator) > 0) {
                    // paths are stored with the prefixed main category, but created
                    // urls doesn't contain the main cat, remove it...
                    $urlPath = cString::getPartOfString(
                        $urlPath,
                        cString::findFirstPos($urlPath, $catSeparator) + 1
                    );
                }
                if ($urls2lowercase) {
                    $urlPath = cString::toLowerCase($urlPath);
                }

                // store path
                $pathsCache[cSecurity::toInteger(self::$db->f('idcat'))] = $urlPath;
            }
        }
        cRegistry::setAppVar($cacheKey, $pathsCache);

        // compare paths using the similar_text algorithm
        $fPercent = 0;
        $aResults = [];
        foreach ($pathsCache as $id => $pathItem) {
            similar_text($path, $pathItem, $fPercent);
            $aResults[$id] = $fPercent;
        }

        arsort($aResults, SORT_NUMERIC);

        PiModRewriteDebugger::add($path, __METHOD__ . ' $path');
        PiModRewriteDebugger::add($pathsCache, __METHOD__ . ' $pathsCache');
        PiModRewriteDebugger::add($aResults, __METHOD__ . ' $aResults');

        $iMinPercentage = cSecurity::toInteger(parent::getConfig('category_resolve_min_percentage', 0));
        $catId = key($aResults);
        if ($iMinPercentage > 0 && $aResults[$catId] < $iMinPercentage) {
            return 0;
        } else {
            return cSecurity::toInteger($catId);
        }
    }

    /**
     * Get article name from article id and language id
     *
     * @NOTE: seems to be not used???
     *
     * @param int $articleId Article id
     * @param int $languageId Language id
     * @return string Article name
     * @throws cDbException
     */
    public static function getArtTitle(int $articleId = 0, int $languageId = 0): string
    {
        $record = PiModRewriteDatabaseUtil::queryAndNextRecord(self::$db->prepare(
            "SELECT `title` FROM `%s` WHERE `idart` = %s AND `idlang` = %d",
            cDb::getTableName('art_lang'),
            $articleId,
            $languageId
        ));

        return $record ? $record['title'] : '';
    }

    /**
     * Get language ids from category id
     *
     * @param int $categoryId Category id
     * @return array Used language ids
     * @throws cDbException
     */
    public static function getCatLanguages(int $categoryId = 0): array
    {
        $cacheKey = sprintf('pluginModRewrite_getCatLanguages_%d', $categoryId);

        $languageIds = cRegistry::getAppVar($cacheKey);
        if (isset($languageIds)) {
            return $languageIds;
        }

        $languageIds = [];

        self::$db->query(
            "SELECT `idlang` FROM `%s` WHERE `idcat` = %s",
            cDb::getTableName('cat_lang'),
            $categoryId
        );
        while (self::$db->nextRecord()) {
            $languageIds[] = cSecurity::toInteger(self::$db->f('idlang'));
        }

        cRegistry::setAppVar($cacheKey, $languageIds);

        return $languageIds;
    }

    /**
     * Get article urlname and language id
     *
     * @param int $articleLanguageId idartlang
     * @return array{urlname: string, idlang: int} Urlname, idlang or empty array
     * @throws cDbException
     */
    public static function getArtIds(int $articleLanguageId = 0): array
    {
        $record = PiModRewriteDatabaseUtil::queryAndNextRecord(self::$db->prepare(
            "SELECT `urlname`, `idlang` FROM `%s` WHERE `idartlang` = %d",
            cDb::getTableName('art_lang'),
            $articleLanguageId
        ));

        if ($record) {
            $record['idlang'] = cSecurity::toInteger($record['idlang']);
        }

        return $record ?: [];
    }

    /**
     * Build a recursive path for mod_rewrite rule like server directories (dir1/dir2/dir3)
     *
     * @param int $categoryId Latest category id
     * @param int $languageId Language id
     * @param int $lastCategoryId Last category id
     * @return string Link path with correct uri
     * @throws cDbException
     */
    public static function buildRecursivePath(int $categoryId = 0, int $languageId = 0, int $lastCategoryId = 0): string
    {
        $aDirectories = [];
        $isFinished = false;
        $actCategoryId = $categoryId;

        while ($isFinished == false) {
            $sql = self::$db->prepare(
                "SELECT cl.urlname, c.parentid "
                . "FROM `%s` cl "
                . "LEFT JOIN `%s` c ON cl.idcat = c.idcat "
                . "WHERE cl.idcat = %d AND cl.idlang = %d",
                cDb::getTableName('cat_lang'),
                cDb::getTableName('cat'),
                $actCategoryId,
                $languageId
            );
            if ($record = PiModRewriteDatabaseUtil::queryAndNextRecord($sql)) {
                $aDirectories[] = $record['urlname'];
                $actCategoryId = cSecurity::toInteger($record['parentid']);

                if ($record['parentid'] == 0 || $record['parentid'] == $lastCategoryId) {
                    $isFinished = true;
                }
            } else {
                $isFinished = true;
            }
        }

        // reverse array entries and create the directory string
        return join('/', array_reverse($aDirectories));
    }

    /**
     * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewrite::buildRecursivePath()} instead.
     */
    public static function buildRecursivPath(int $categoryId = 0, int $languageId = 0, int $lastCategoryId = 0): string
    {
        cDeprecated(__FUNCTION__ . ' is deprecated since Advanced Mod Rewrite 2.1.0, use PiModRewrite::buildRecursivePath() instead.');
        return self::buildRecursivePath($categoryId, $languageId, $lastCategoryId);
    }

    /**
     * Return full CONTENIDO url from single anchor
     *
     * @param array $matches [0] = complete anchor, [1] = pre arguments, [2] = anchor name, [3] = post arguments
     * @return string New anchor
     */
    public static function rewriteHtmlAnchor(array $matches = []): string
    {
        global $artname;

        // Set base parameter
        $params = [];
        if (isset($artname) && cString::getStringLength($artname) > 0) {
            $params['idart'] = cRegistry::getArticleId();
        }
        $params['idcat'] = cRegistry::getCategoryId();
        $params['client'] = cRegistry::getClientId();
        $params['changelang'] = cRegistry::getLanguageId();

        // Add additional parameter in url
        $paramsToIgnore = [
            'idcat',
            'idart',
            'lang',
            'client',
            'idcatart',
            'changelang',
            'changeclient',
            'idartlang',
            'parts',
            'artname'
        ];
        if (!empty($_GET)) {
            foreach ($_GET as $key => $value) {
                if (!in_array($key, $paramsToIgnore) && cString::getStringLength(trim($value)) > 0) {
                    $params[urldecode($key)] = urldecode($value);
                }
            }
        }

        $url = cRegistry::getSession()->url(sprintf(
            'front_content.php?%s#%s', http_build_query($params), $matches[2]
        ));

        return '<a' . $matches[1] . 'href="' . $url . '"' . $matches[3] . '>';
    }

    /**
     * Return full CONTENIDO url from single anchor
     *
     * @param array $matches [0] = complete anchor, [1] = pre arguments, [2] = anchor name, [3] = post arguments
     * @param bool $isXHTML Flag to return XHTML valid url
     * @return string New anchor
     */
    public static function contenidoHtmlAnchor(array $matches = [], bool $isXHTML = true): string
    {
        $params = [];
        $argSeparator = $isXHTML ? '&amp;' : '&';

        foreach ($_GET as $key => $value) {
            $aNoAnchor = explode('#', $value);
            $params[urldecode($key)] = urldecode($aNoAnchor[0]);
        }

        $url = cRegistry::getSession()->url(sprintf(
            'front_content.php?%s#%s', http_build_query($params, $argSeparator), $matches[2]
        ));

        return '<a' . $matches[1] . 'href="' . $url . '"' . $matches[3] . '>';
    }

    /**
     * Get the article web-safe name from article id and language id.
     *
     * @param int $articleId Article id
     * @param int $languageId Language id
     * @return ?string Article web-safe name
     * @throws cDbException
     */
    public static function getArtWebsafeName(int $articleId = 0, int $languageId = 0): ?string
    {
        $record = PiModRewriteDatabaseUtil::queryAndNextRecord(self::$db->prepare(
            "SELECT `urlname` FROM `%s` WHERE `idart` = %s AND `idlang` = %d",
            cDb::getTableName('art_lang'),
            $articleId,
            $languageId
        ));

        return $record ? $record['urlname'] : null;
    }

    /**
     * Get the article web-safe name from idartlang.
     *
     * @param int $articleLanguageId idartlang
     * @return ?string Article web-safe name
     * @throws cDbException
     */
    public static function getArtLangWebsafeName(int $articleLanguageId = 0): ?string
    {
        $record = PiModRewriteDatabaseUtil::queryAndNextRecord(self::$db->prepare(
            "SELECT `urlname` FROM `%s` WHERE `idartlang` = %d",
            cDb::getTableName('art_lang'),
            $articleLanguageId
        ));

        return $record ? $record['urlname'] : null;
    }

    /**
     * Get the name of the client by id.
     *
     * @param int $clientId Client id
     * @return string Client name
     * @throws cDbException
     */
    public static function getClientName(int $clientId = 0): string
    {
        $cacheKey = sprintf('pluginModRewrite_getClientName_%d', $clientId);

        $clientName = cRegistry::getAppVar($cacheKey);
        if (isset($clientName)) {
            return $clientName;
        }

        $record = PiModRewriteDatabaseUtil::queryAndNextRecord(self::$db->prepare(
            "SELECT `name` FROM `%s` WHERE `idclient` = %d",
            cDb::getTableName('clients'),
            $clientId
        ));

        $clientName = $record ? $record['name'] : '';
        cRegistry::setAppVar($cacheKey, $clientName);

        return $clientName;
    }

    /**
     * Get the client id by the client name
     *
     * @param string $clientName Client name
     * @return int Client id
     * @throws cDbException
     */
    public static function getClientId(string $clientName = ''): int
    {
        $clientName = cString::toLowerCase($clientName);

        $cacheKey = sprintf('pluginModRewrite_getClientId_%s', $clientName);

        $clientId = cRegistry::getAppVar($cacheKey);
        if (isset($clientId)) {
            return $clientId;
        }

        $record = PiModRewriteDatabaseUtil::queryAndNextRecord(self::$db->prepare(
            "SELECT `idclient` FROM `%s` WHERE LOWER(`name`) = '%s' OR LOWER(`name`) = '%s'",
            cDb::getTableName('clients'),
            $clientName,
            urldecode($clientName)
        ));

        $clientId = $record ? cSecurity::toInteger($record['idclient']) : 0;
        cRegistry::setAppVar($cacheKey, $clientId);

        return $clientId;
    }

    /**
     * Checks if the client id exists
     *
     * @throws cDbException
     */
    public static function clientIdExists(int $clientId): bool
    {
        $cacheKey = sprintf('pluginModRewrite_clientIdExists_%d', $clientId);

        $clientExists = cRegistry::getAppVar($cacheKey);
        if (isset($clientExists)) {
            return $clientExists;
        }

        $clientExists = cSecurity::toBoolean(PiModRewriteDatabaseUtil::queryAndNextRecord(self::$db->prepare(
            "SELECT `idclient` FROM `%s` WHERE `idclient` = %d",
            cDb::getTableName('clients'),
            $clientId
        )));

        cRegistry::setAppVar($cacheKey, $clientExists);

        return $clientExists;
    }

    /**
     * Returns name of language by id.
     *
     * @param int $languageId Language id
     * @return string Language name
     * @throws cDbException
     */
    public static function getLanguageName(int $languageId = 0): string
    {
        $cacheKey = sprintf('pluginModRewrite_getLanguageName_%d', $languageId);

        $languageName = cRegistry::getAppVar($cacheKey);
        if (isset($languageName)) {
            return $languageName;
        }

        $record = PiModRewriteDatabaseUtil::queryAndNextRecord(self::$db->prepare(
            "SELECT `name` FROM `%s` WHERE `idlang` = %d",
            cDb::getTableName('lang'),
            $languageId
        ));

        $languageName = $record ? $record['name'] : '';
        cRegistry::setAppVar($cacheKey, $languageName);

        return $languageName;
    }

    /**
     * Checks if language id exists
     *
     * @param int $languageId Language id
     * @throws cDbException
     */
    public static function languageIdExists(int $languageId): bool
    {
        $cacheKey = sprintf('pluginModRewrite_languageIdExists_%d', $languageId);

        $languageIdExists = cRegistry::getAppVar($cacheKey);
        if (isset($languageIdExists)) {
            return $languageIdExists;
        }

        $languageIdExists = cSecurity::toBoolean(PiModRewriteDatabaseUtil::queryAndNextRecord(self::$db->prepare(
            "SELECT `idlang` FROM `%s` WHERE `idlang` = %d",
            cDb::getTableName('lang'),
            $languageId
        )));

        cRegistry::setAppVar($cacheKey, $languageIdExists);

        return $languageIdExists;
    }

    /**
     * Get language id from language name thanks to Nicolas Dickinson for multi Client/Language BugFix
     *
     * @param string $sLanguageName Language name
     * @param int $clientId Client id
     * @throws cDbException
     */
    public static function getLanguageId(string $sLanguageName = '', int $clientId = 1): int
    {
        $sLanguageName = cString::toLowerCase($sLanguageName);

        $cacheKey = sprintf('pluginModRewrite_getLanguageId_%s_%d', $sLanguageName, $clientId);

        $languageId = cRegistry::getAppVar($cacheKey);
        if (isset($languageId)) {
            return $languageId;
        }

        $sql = self::$db->prepare(
            "SELECT l.idlang FROM `%s` AS l "
            . "LEFT JOIN `%s` AS cl ON l.idlang = cl.idlang "
            . "WHERE cl.idclient = %d AND (LOWER(l.name) = '%s' OR LOWER(l.name) = '%s')",
            cDb::getTableName('lang'),
            cDb::getTableName('clients_lang'),
            $clientId,
            $sLanguageName,
            urldecode($sLanguageName)
        );
        if ($record = PiModRewriteDatabaseUtil::queryAndNextRecord($sql)) {
            $languageId = cSecurity::toInteger($record['idlang']);
        } else {
            $languageId = 0;
        }

        cRegistry::setAppVar($cacheKey, $languageId);

        return $languageId;
    }

    /**
     * Splits passed argument into scheme://host and path/query.
     *
     * Example:
     * input = https://host/front_content.php?idcat=123
     * return = ['htmlpath' => 'https://host', 'url' => 'front_content.php?idcat=123']
     *
     * @param string $url URL to split
     * @return array{htmlpath: string, url: string} Associative array including the two parts:
     *      - ['htmlpath' => $path, 'url' => $url]
     */
    public static function getClientFullUrlParts(string $url): array
    {
        $clientPath = cRegistry::getFrontendUrl();

        if (cString::findFirstOccurrenceCI($url, $clientPath) !== false) {
            // url includes the full HTML path (scheme host path, etc.)
            $url = str_replace($clientPath, '', $url);
            $htmlPath = $clientPath;
            $aComp = parse_url($htmlPath);

            // check if the path matches to defined rootdir from mod_rewrite conf
            if (isset($aComp['path']) && $aComp['path'] !== parent::getConfig('rootdir')) {
                // replace the not matching path against the configured one
                // this will replace e.g. "http://host/cms/" against "http://host/"
                $htmlPath = str_replace($aComp['path'], parent::getConfig('rootdir'), $htmlPath);
                if (cString::getPartOfString($htmlPath, cString::getStringLength($htmlPath) - 1) == '/') {
                    // remove the last slash
                    $htmlPath = cString::getPartOfString($htmlPath, 0, cString::getStringLength($htmlPath) - 1);
                }
            }
        } else {
            $htmlPath = '';
        }
        return ['htmlpath' => $htmlPath, 'url' => $url];
    }

    /**
     * Function to preclean the url.
     *
     * Removes absolute path declaration '/front_content.php' or relative path definition to actual
     * dir './front_content.php', ampersand entities '&amp;'
     * and returns a url like 'front_content.php?idart=12&idlang=1'
     *
     * @param string $url Url to clean
     * @return string Cleaned Url
     */
    public static function urlPreClean(string $url): string
    {
        // some preparation of different front_content.php occurrence
        if (cString::findFirstPos($url, './front_content.php') === 0) {
            $url = str_replace('./front_content.php', 'front_content.php', $url);
        } elseif (cString::findFirstPos($url, '/front_content.php') === 0) {
            $url = str_replace('/front_content.php', 'front_content.php', $url);
        }

        return str_replace('&amp;', '&', $url);
    }

    /**
     * Recreates all or only empty aliases in the category table.
     *
     * @param bool $onlyEmpty Flag to reset only empty items
     * @throws cDbException|cInvalidArgumentException
     */
    public static function recreateCategoriesAliases(bool $onlyEmpty = false)
    {
        $db = cRegistry::getDb();
        $aCats = [];

        // get all or only empty categories
        $sql = "SELECT name, idcat, idlang FROM " . cDb::getTableName('cat_lang');
        if ($onlyEmpty) {
            $sql .= " WHERE urlname IS NULL OR urlname = '' OR urlpath IS NULL OR urlpath = ''";
        }

        $db->query($sql);
        while ($db->nextRecord()) {
            //set new alias
            self::setCatWebsafeName(
                cSecurity::toString($db->f('name')),
                cSecurity::toInteger($db->f('idcat')),
                cSecurity::toInteger($db->f('idlang'))
            );
            $aCats[] = [
                'idcat' => cSecurity::toInteger($db->f('idcat')),
                'idlang' => cSecurity::toInteger($db->f('idlang'))
            ];
        }

        foreach ($aCats as $item) {
            self::setCatUrlPath($item['idcat'], $item['idlang']);
        }
    }

    /**
     * Returns the list of all empty category aliases
     *
     * @param bool $bOnlyNumber
     * @return array|int
     * @throws cDbException
     */
    public static function getEmptyCategoriesAliases(bool $bOnlyNumber = true)
    {
        $db = cRegistry::getDb();
        $return = ($bOnlyNumber) ? 0 : [];

        // get all empty categories
        $sql = "SELECT name, idcat, idlang FROM " . cDb::getTableName('cat_lang')
            . " WHERE urlname IS NULL OR urlname = '' OR urlpath IS NULL OR urlpath = ''";

        $db->query($sql);

        if ($bOnlyNumber) {
            $return = cSecurity::toInteger($db->numRows());
        } else {
            while ($db->nextRecord()) {
                $return[] = [$db->f('name'), $db->f('idcat'), $db->f('idlang')];
            }
        }

        return $return;
    }

    /**
     * Recreates all or only empty urlname entries in the article language table.
     *
     * @param bool $onlyEmpty Flag to reset only empty items
     * @throws cDbException
     */
    public static function recreateArticlesAliases(bool $onlyEmpty = false)
    {
        $db = cRegistry::getDb();

        // get all or only empty articles
        $sql = "SELECT `title`, `idart`, `idlang` FROM " . cDb::getTableName('art_lang');
        if ($onlyEmpty) {
            $sql .= " WHERE `urlname` IS NULL OR `urlname` = ''";
        }
        $db->query($sql);

        while ($db->nextRecord()) {
            //set new alias
            self::setArtWebsafeName(
                cSecurity::toString($db->f('title')),
                cSecurity::toInteger($db->f('idart')),
                cSecurity::toInteger($db->f('idlang'))
            );
        }
    }

    /**
     * Returns a list of all empty article aliases, either the number of found
     *
     * @param bool $onlyNumber Flag to return number of rows instead the array
     * @return array<int, array{0: string, 1: int, 2: int}>|int List of articles (array of [title, idart, idlang]),
     *      or number of rows.
     * @throws cDbException
     */
    public static function getEmptyArticlesAliases(bool $onlyNumber = true)
    {
        $db = cRegistry::getDb();

        // get all empty articles
        $db->query(
            "SELECT `title`, `idart`, `idlang` FROM `%s` WHERE `urlname` IS NULL OR `urlname` = ''",
            cDb::getTableName('art_lang')
        );
        if ($onlyNumber) {
            return $db->numRows();
        }

        $return = [];
        while ($db->nextRecord()) {
            $return[] = [
                $db->f('title'),
                cSecurity::toInteger($db->f('idart')),
                cSecurity::toInteger($db->f('idlang'))
            ];
        }

        return $return;
    }

    /**
     * Method to reset all aliases (categories and articles).
     * Shortcut to recreateCategoriesAliases() and recreateArticlesAliases()
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public static function resetAliases()
    {
        self::recreateCategoriesAliases();
        self::recreateArticlesAliases();
    }

    /**
     * Recreate all or only empty aliases (categories and articles).
     *
     * Shortcut to recreateCategoriesAliases() and recreateArticlesAliases()
     *
     * @param bool $onlyEmpty Flag to reset only empty items
     * @throws cDbException|cInvalidArgumentException
     */
    public static function recreateAliases(bool $onlyEmpty = false)
    {
        self::recreateCategoriesAliases($onlyEmpty);
        self::recreateArticlesAliases($onlyEmpty);
    }

    /**
     * Returns .htaccess related associative info array
     */
    public static function getHtaccessInfo(): array
    {
        $arr = [
            'contenido_full_path' => str_replace(
                '\\',
                '/',realpath(cRegistry::getBackendPath() . '../') . '/'
            ),
            'client_full_path' => cRegistry::getFrontendPath(),
        ];
        $arr['in_contenido_path'] = is_file($arr['contenido_full_path'] . '.htaccess');
        $arr['in_client_path'] = is_file($arr['client_full_path'] . '.htaccess');
        $arr['has_htaccess'] = ($arr['in_contenido_path'] || $arr['in_client_path']);

        return $arr;
    }

}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewrite} instead
 */
class ModRewrite extends PiModRewrite
{}
