<?php

/**
 * AMR Mod Rewrite helper class
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
 * Class to create websafe names, it also provides several helper functions
 *
 * @author     Stefan Seifarth / stese
 * @author     Murat Purc <murat@purc.de>
 * @package    Plugin
 * @subpackage ModRewrite
 */
class ModRewrite extends ModRewriteBase
{

    /**
     * Database instance
     *
     * @var cDb
     */
    private static $_db;

    /**
     * Lookup table to cache some internal data such as db query results
     *
     * @var array
     */
    protected static $_lookupTable;

    /**
     * Initialization, is to call at least once, also possible to call multiple
     * times, if different client configuration is to load.
     *
     * Loads configuration of passed client and sets some properties.
     *
     * @param int $clientId Client id
     * @throws cInvalidArgumentException
     */
    public static function initialize($clientId)
    {
        mr_loadConfiguration($clientId, true);
        self::$_db = cRegistry::getDb();
        self::$_lookupTable = [];
    }

    /**
     * Check categories on websafe name
     *
     * Check all categories in the main parent category on existing same websafe name
     *
     * @param string $urlName Websafe name to check
     * @param int $categoryId Current category id
     * @param int $languageId Current language id
     * @throws cDbException|cInvalidArgumentException
     */
    public static function isInCategories(string $urlName = '', int $categoryId = 0, int $languageId = 0): bool
    {
        // get parentid
        $iParentId = 0;
        $sql = "SELECT parentid FROM " . cDb::getTableName('cat') . " WHERE idcat = " . $categoryId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            $iParentId = $aData['parentid'] > 0 ? cSecurity::toInteger($aData['parentid']) : 0;
        }

        // check if websafe name is in this category
        $sql = "SELECT count(cl.idcat) as numcats FROM " . cDb::getTableName('cat_lang') . " cl "
            . "LEFT JOIN " . cDb::getTableName('cat') . " c ON cl.idcat = c.idcat WHERE "
            . "c.parentid = '$iParentId' AND cl.idlang = " . $languageId . " AND "
            . "LOWER(cl.urlname) = LOWER('" . self::$_db->escape($urlName) . "') AND cl.idcat <> " . $categoryId;
        ModRewriteDebugger::log($sql, 'ModRewrite::isInCategories $sql');

        if ($aData = mr_queryAndNextRecord($sql)) {
            return $aData['numcats'] > 0;
        }

        return false;
    }

    /**
     * Check articles on websafe name.
     *
     * Check all articles in the current category on existing same websafe name.
     *
     * @param string $urlName Websafe name to check
     * @param int $articleId Current article id
     * @param int $languageId Current language id
     * @param int $categoryId Category id
     * @throws cDbException
     * @internal This method only considers the case that articles are related to a single category.
     *           The function conIsArticleUrlnameUnique also considers multiple categories.
     */
    public static function isInCatArticles(
        string $urlName = '',
        int $articleId = 0,
        int $languageId = 0,
        int $categoryId = 0
    ): bool {
        // handle multipages
        if ($categoryId == 0) {
            // get category id if not set
            $sql = "SELECT idcat FROM " . cDb::getTableName('cat_art') . " WHERE idart = " . $articleId;
            if ($aData = mr_queryAndNextRecord($sql)) {
                $categoryId = ($aData['idcat'] > 0) ? cSecurity::toInteger($aData['idcat']) : 0;
            }
        }

        // check if websafe name is in this category
        $sql = "SELECT count(al.idart) as numcats FROM " . cDb::getTableName('art_lang') . " al "
            . "LEFT JOIN " . cDb::getTableName('cat_art') . " ca ON al.idart = ca.idart WHERE "
            . " ca.idcat='$categoryId' AND al.idlang=" . $languageId . " AND "
            . "LOWER(al.urlname) = LOWER('" . self::$_db->escape($urlName) . "') AND al.idart <> " . $articleId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            return $aData['numcats'] > 0;
        }

        return false;
    }

    /**
     * Set websafe name in article list.
     *
     * Insert new websafe name in article list
     *
     * @param string $urlName Original name (will be converted)
     * @param int $articleId Current article id
     * @param int $languageId Current language id
     * @param int $categoryId Category id
     * @return bool True if insert was successfully
     * @throws cDbException
     */
    public static function setArtWebsafeName(
        string $urlName = '',
        int $articleId = 0,
        int $languageId = 0,
        int $categoryId = 0
    ): bool {
        // get websafe name
        $sNewName = cString::cleanURLCharacters(conHtmlEntityDecode($urlName));

        // remove double or more separators
        $sNewName = mr_removeMultipleChars('-', $sNewName);

        // check if websafe name already exists
        if (self::isInCatArticles($sNewName, $articleId, $languageId, $categoryId)) {
            // create new websafe name if exists
            $sNewName = $sNewName . $articleId;
        }

        // check again - and set name
        if (!self::isInCatArticles($sNewName, $articleId, $languageId, $categoryId)) {
            // insert websafe name in article list
            $sql = "UPDATE " . cDb::getTableName('art_lang') . " SET urlname = '" . self::$_db->escape($sNewName) . "' "
                . "WHERE idart = " . $articleId . " AND idlang = " . $languageId;
            return (bool) self::$_db->query($sql);
        } else {
            return false;
        }
    }

    /**
     * Set websafe name in category list.
     *
     * Insert new websafe name in category list.
     *
     * @param string $urlName Original name (will be converted) or alias
     * @param int $categoryId Category id
     * @param int $languageId Language id
     * @return bool True if insert was successfully
     * @throws cInvalidArgumentException|cDbException
     */
    public static function setCatWebsafeName(string $urlName = '', int $categoryId = 0, int $languageId = 0): bool
    {
        // create websafe name
        $sNewName = cString::cleanURLCharacters(conHtmlEntityDecode($urlName));

        // remove double or more separators
        $sNewName = mr_removeMultipleChars('-', $sNewName);

        // check if websafe name already exists
        if (self::isInCategories($sNewName, $categoryId, $languageId)) {
            // create new websafe name if exists
            $sNewName = $sNewName . $categoryId;
        }

        // check again - and set name
        if (!self::isInCategories($sNewName, $categoryId, $languageId)) {
            // update urlname
            $sql = "UPDATE " . cDb::getTableName('cat_lang') . " SET urlname = '" . self::$_db->escape($sNewName) . "' "
                . "WHERE idcat = " . $categoryId . " AND idlang = " . $languageId;

            ModRewriteDebugger::log([
                'sName' => $urlName,
                'iCatId' => $categoryId,
                'iLangId' => $languageId,
                'sNewName' => $sNewName
            ], 'ModRewrite::setCatWebsafeName $data');

            return (bool) self::$_db->query($sql);
        } else {
            return false;
        }
    }

    /**
     * Set urlpath of category
     *
     * @param int $categoryId Category id
     * @param int $languageId Language id
     * @return bool True if insert was successfully
     * @throws cDbException|cInvalidArgumentException
     */
    public static function setCatUrlPath(int $categoryId = 0, int $languageId = 0): bool
    {
        $sPath = self::buildRecursivPath($categoryId, $languageId);

        // update urlpath
        $sql = "UPDATE " . cDb::getTableName('cat_lang') . " SET urlpath = '" . self::$_db->escape($sPath) . "' "
            . "WHERE idcat = " . $categoryId . " AND idlang = " . $languageId;

        ModRewriteDebugger::log([
            'iCatId' => $categoryId,
            'iLangId' => $languageId,
            'sPath' => $sPath
        ], 'ModRewrite::setCatUrlPath $data');

        return (bool) self::$_db->query($sql);
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
        $sql = "SELECT idart, idlang FROM " . cDb::getTableName('art_lang') . " WHERE idartlang = " . $articleLanguageId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            return $aData;
        }
        return [];
    }

    /**
     * Get article id by article websafe name
     *
     * @param string $articleName Websafe name
     * @param int $categoryId Category id
     * @param int $languageId Language id
     * @return ?int Recent article id or NULL
     * @throws cDbException
     */
    public static function getArtIdByWebsafeName(string $articleName = '', int $categoryId = 0, int $languageId = 0): ?int
    {
        $where = '';
        if ($languageId > 0) {
            $where = ' AND al.idlang = ' . $languageId;
        }
        // only article name were given
        if ($categoryId == 0) {
            // get all basic category ids with parentid=0
            $aCatIds = [];
            $sql = "SELECT idcat FROM " . cDb::getTableName('cat') . " WHERE parentid = 0";
            self::$_db->query($sql);
            while (self::$_db->nextRecord()) {
                $aCatIds[] = "idcat = " . cSecurity::toInteger(self::$_db->f('idcat'));
            }
            $where .= " AND (" . join(" OR ", $aCatIds) . ")";
        } else {
            $where .= " AND ca.idcat = " . $categoryId;
        }

        $sql = "
            SELECT al.idart
            FROM " . cDb::getTableName('art_lang') . " al
            LEFT JOIN " . cDb::getTableName('cat_art') . " ca ON al.idart = ca.idart
            WHERE LOWER(al.urlname) = LOWER('" . self::$_db->escape($articleName) . "') $where";

        if ($aData = mr_queryAndNextRecord($sql)) {
            return cSecurity::toInteger($aData['idart']);
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
        $key = 'catname_by_catid_idlang_' . $categoryId . '_' . $languageId;

        if (isset(self::$_lookupTable[$key])) {
            return self::$_lookupTable[$key];
        }

        $sql = "SELECT name FROM " . cDb::getTableName('cat_lang')
            . " WHERE idcat = " . $categoryId . " AND idlang = " . $languageId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            $catName = $aData['name'];
        } else {
            $catName = '';
        }

        self::$_lookupTable[$key] = $catName;

        return $catName;
    }

    /**
     * Function to return cat id by path.
     *
     * Caches the paths at first call to provide faster processing at further calls.
     *
     * @param string $path Category path
     * @return int Category id
     * @throws cDbException|cInvalidArgumentException
     */
    public static function getCatIdByUrlPath(string $path): int
    {
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();

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

        $key = 'cat_ids_and_urlpath_' . $client . '_' . $lang;

        $aPathsCache = self::$_lookupTable[$key] ?? [];

        if (count($aPathsCache) == 0) {
            $sql = "SELECT cl.idcat, cl.urlpath FROM " . cDb::getTableName('cat_lang')
                . " AS cl, " . cDb::getTableName('cat') . " AS c WHERE c.idclient = " . cSecurity::toInteger($client)
                . " AND c.idcat = cl.idcat AND cl.idlang = " . cSecurity::toInteger($lang);

            self::$_db->query($sql);
            while (self::$_db->nextRecord()) {
                $urlPath = self::$_db->f('urlpath');
                if ($startFromRoot == 0 && cString::findFirstPos($urlPath, $catSeparator) > 0) {
                    // paths are stored with prefixed main category, but created
                    // urls doesn't contain the main cat, remove it...
                    $urlPath = cString::getPartOfString($urlPath, cString::findFirstPos($urlPath, $catSeparator) + 1);
                }
                if ($urls2lowercase) {
                    $urlPath = cString::toLowerCase($urlPath);
                }

                // store path
                $aPathsCache[cSecurity::toInteger(self::$_db->f('idcat'))] = $urlPath;
            }
        }
        self::$_lookupTable[$key] = $aPathsCache;

        // compare paths using the similar_text algorithm
        $fPercent = 0;
        $aResults = [];
        foreach ($aPathsCache as $id => $pathItem) {
            similar_text($path, $pathItem, $fPercent);
            $aResults[$id] = $fPercent;
        }

        arsort($aResults, SORT_NUMERIC);

        ModRewriteDebugger::add($path, 'ModRewrite::getCatIdByUrlPath() $path');
        ModRewriteDebugger::add($aPathsCache, 'ModRewrite::getCatIdByUrlPath() $aPathsCache');
        ModRewriteDebugger::add($aResults, 'ModRewrite::getCatIdByUrlPath() $aResults');

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
        $articleId = cSecurity::toInteger($articleId);
        $languageId = cSecurity::toInteger($languageId);

        $sql = "SELECT title FROM " . cDb::getTableName('art_lang')
            . " WHERE idart = " . $articleId . " AND idlang = " . $languageId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            return $aData['title'];
        }
        return '';
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
        $categoryId = cSecurity::toInteger($categoryId);
        $key = 'cat_idlang_by_catid_' . $categoryId;

        if (isset(self::$_lookupTable[$key])) {
            return self::$_lookupTable[$key];
        }

        $languageIds = [];

        $sql = "SELECT idlang FROM " . cDb::getTableName('cat_lang') . " WHERE idcat = " . $categoryId;
        self::$_db->query($sql);
        while (self::$_db->nextRecord()) {
            $languageIds[] = cSecurity::toInteger(self::$_db->f('idlang'));
        }

        self::$_lookupTable[$key] = $languageIds;
        return $languageIds;
    }

    /**
     * Get article urlname and language id
     *
     * @param int $articleLanguageId idartlang
     * @return array Urlname, idlang of empty array
     * @throws cDbException
     */
    public static function getArtIds(int $articleLanguageId = 0): array
    {
        $articleLanguageId = cSecurity::toInteger($articleLanguageId);
        $sql = "SELECT urlname, idlang FROM " . cDb::getTableName('art_lang')
            . " WHERE idartlang = " . $articleLanguageId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            return $aData;
        }
        return [];
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
    public static function buildRecursivPath(int $categoryId = 0, int $languageId = 0, int $lastCategoryId = 0): string
    {
        $aDirectories = [];
        $isFinished = false;
        $actCategoryId = $categoryId;

        while ($isFinished == false) {
            $sql = "SELECT cl.urlname, c.parentid FROM " . cDb::getTableName('cat_lang') . " cl "
                . "LEFT JOIN " . cDb::getTableName('cat') . " c ON cl.idcat = c.idcat "
                . "WHERE cl.idcat = " . $actCategoryId . " AND cl.idlang = " . $languageId;
            if ($aData = mr_queryAndNextRecord($sql)) {
                $aDirectories[] = $aData['urlname'];
                $actCategoryId = cSecurity::toInteger($aData['parentid']);

                if ($aData['parentid'] == 0 || $aData['parentid'] == $lastCategoryId) {
                    $isFinished = true;
                }
            } else {
                $isFinished = true;
            }
        }

        // reverse array entries and create directory string
        return join('/', array_reverse($aDirectories));
    }

    /**
     * Return full CONTENIDO url from single anchor
     *
     * @param array $aMatches [0] = complete anchor, [1] = pre arguments, [2] = anchor name, [3] = post arguments
     * @return string New anchor
     */
    public static function rewriteHtmlAnchor(array $aMatches = []): string
    {
        global $artname;

        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();
        $idcat = cRegistry::getCategoryId();
        $idart = cRegistry::getArticleId();
        $sess = cRegistry::getSession();

        // set article name
        $sArtParam = '';
        if (isset($artname) && cString::getStringLength($artname) > 0) {
            $sArtParam = '&idart=' . cSecurity::toInteger($idart);
        }

        // check for additional parameter in url
        $aParamsToIgnore = [
            'idcat', 'idart', 'lang', 'client', 'idcatart', 'changelang', 'changeclient', 'idartlang', 'parts', 'artname'
        ];
        $sOtherParams = '';

        if (!empty($_GET)) {
            foreach ($_GET as $key => $value) {
                if (!in_array($key, $aParamsToIgnore) && cString::getStringLength(trim($value)) > 0) {
                    #$aNoAnchor = explode('#', $value);
                    $sOtherParams .= '&' . urlencode(urldecode($key)) . '=' . urlencode(urldecode($value));
                }
            }
        }

        $url = $sess->url(
            'front_content.php?' . 'idcat=' . cSecurity::toInteger($idcat) . '&client=' . cSecurity::toInteger($client)
            . '&changelang=' . cSecurity::toInteger($lang) . $sArtParam . $sOtherParams . '#' . $aMatches[2]
        );

        return '<a' . $aMatches[1] . 'href="' . $url . '"' . $aMatches[3] . '>';
    }

    /**
     * Return full CONTENIDO url from single anchor
     *
     * @param array $aMatches [0] = complete anchor, [1] = pre arguments, [2] = anchor name, [3] = post arguments
     * @param bool $bXHTML Flag to return XHTML valid url
     * @return string New anchor
     */
    public static function contenidoHtmlAnchor(array $aMatches = [], bool $bXHTML = true): string
    {
        $sess = cRegistry::getSession();
        $aParams = [];
        $sAmpersand = $bXHTML ? '&amp;' : '&';

        foreach ($_GET as $key => $value) {
            $aNoAnchor = explode('#', $value);
            $aParams[] = urlencode(urldecode($key)) . '=' . urlencode(urldecode($aNoAnchor[0]));
        }

        $url = $sess->url('front_content.php?' . implode($sAmpersand, $aParams) . '#' . $aMatches[2]);
        return '<a' . $aMatches[1] . 'href="' . $url . '"' . $aMatches[3] . '>';
    }

    /**
     * Get article websafe name from article id and language id.
     *
     * @param int $articleId Article id
     * @param int $languageId Language id
     * @return ?string Article websafe name
     * @throws cDbException
     */
    public static function getArtWebsafeName(int $articleId = 0, int $languageId = 0): ?string
    {
        $articleId = cSecurity::toInteger($articleId);
        $languageId = cSecurity::toInteger($languageId);
        $sql = "SELECT urlname FROM " . cDb::getTableName('art_lang')
            . " WHERE idart = " . $articleId . " AND idlang = " . $languageId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            return $aData['urlname'];
        }
        return null;
    }

    /**
     * Get article websafe name from idartlang.
     *
     * @param int $articleLanguageId idartlang
     * @return ?string Article websafe name
     * @throws cDbException
     */
    public static function getArtLangWebsafeName(int $articleLanguageId = 0): ?string
    {
        $articleLanguageId = cSecurity::toInteger($articleLanguageId);
        $sql = "SELECT urlname FROM " . cDb::getTableName('art_lang') . " WHERE idartlang = " . $articleLanguageId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            return $aData['urlname'];
        }
        return null;
    }

    /**
     * Get name of client by id.
     *
     * @param int $clientId Client id
     * @return string Client name
     * @throws cDbException
     */
    public static function getClientName(int $clientId = 0): string
    {
        $clientId = cSecurity::toInteger($clientId);
        $key = 'clientname_by_clientid_' . $clientId;

        if (isset(self::$_lookupTable[$key])) {
            return self::$_lookupTable[$key];
        }

        $sql = "SELECT name FROM " . cDb::getTableName('clients') . " WHERE idclient = " . $clientId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            $clientName = $aData['name'];
        } else {
            $clientName = '';
        }

        self::$_lookupTable[$key] = $clientName;
        return $clientName;
    }

    /**
     * Get client id from client name
     *
     * @param string $clientName Client name
     * @return int Client id
     * @throws cDbException
     */
    public static function getClientId(string $clientName = ''): int
    {
        $clientName = cString::toLowerCase($clientName);
        $key = 'clientid_by_name_' . $clientName;

        if (isset(self::$_lookupTable[$key])) {
            return self::$_lookupTable[$key];
        }

        $sql = "SELECT idclient FROM " . cDb::getTableName('clients')
            . " WHERE LOWER(name) = '" . self::$_db->escape($clientName) . "'"
            . " OR LOWER(name) = '" . self::$_db->escape(urldecode($clientName)) . "'";
        if ($aData = mr_queryAndNextRecord($sql)) {
            $clientId = $aData['idclient'];
        } else {
            $clientId = 0;
        }

        self::$_lookupTable[$key] = $clientId;

        return $clientId;
    }

    /**
     * Checks if client id exists
     *
     * @throws cDbException
     */
    public static function clientIdExists(int $clientId): bool
    {
        $key = 'clientid_exists_' . $clientId;

        if (isset(self::$_lookupTable[$key])) {
            return self::$_lookupTable[$key];
        }

        $sql = "SELECT idclient FROM " . cDb::getTableName('clients') . " WHERE idclient = " . $clientId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            $exists = true;
        } else {
            $exists = false;
        }

        self::$_lookupTable[$key] = $exists;
        return $exists;
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
        $key = 'languagename_by_id_' . $languageId;

        if (isset(self::$_lookupTable[$key])) {
            return self::$_lookupTable[$key];
        }

        $sql = "SELECT name FROM " . cDb::getTableName('lang') . " WHERE idlang = " . $languageId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            $languageName = $aData['name'];
        } else {
            $languageName = '';
        }

        self::$_lookupTable[$key] = $languageName;
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
        $key = 'languageid_exists_' . $languageId;

        if (isset(self::$_lookupTable[$key])) {
            return self::$_lookupTable[$key];
        }

        $sql = "SELECT idlang FROM " . cDb::getTableName('lang') . " WHERE idlang = " . $languageId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            $exists = true;
        } else {
            $exists = false;
        }

        self::$_lookupTable[$key] = $exists;
        return $exists;
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
        $clientId = cSecurity::toInteger($clientId);
        $key = 'langid_by_langname_clientid_' . $sLanguageName . '_' . $clientId;

        if (isset(self::$_lookupTable[$key])) {
            return self::$_lookupTable[$key];
        }

        $sql = "SELECT l.idlang FROM " . cDb::getTableName('lang') . " as l "
            . "LEFT JOIN " . cDb::getTableName('clients_lang') . " AS cl ON l.idlang = cl.idlang "
            . "WHERE cl.idclient = " . $clientId . " AND (LOWER(l.name) = '" . self::$_db->escape($sLanguageName) . "' "
            . "OR LOWER(l.name) = '" . self::$_db->escape(urldecode($sLanguageName)) . "')";
        if ($aData = mr_queryAndNextRecord($sql)) {
            $languageId = cSecurity::toInteger($aData['idlang']);
        } else {
            $languageId = 0;
        }

        self::$_lookupTable[$key] = $languageId;

        return $languageId;
    }

    /**
     * Splits passed argument into scheme://host and path/query.
     *
     * Example:
     * input  = https://host/front_content.php?idcat=123
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
            // url includes full html path (scheme host path, etc.)
            $url = str_replace($clientPath, '', $url);
            $htmlPath = $clientPath;
            $aComp = parse_url($htmlPath);

            // check if path matches to defined rootdir from mod_rewrite conf
            if (isset($aComp['path']) && $aComp['path'] !== parent::getConfig('rootdir')) {
                // replace not matching path against configured one
                // this will replace e.g. "http://host/cms/" against "http://host/"
                $htmlPath = str_replace($aComp['path'], parent::getConfig('rootdir'), $htmlPath);
                if (cString::getPartOfString($htmlPath, cString::getStringLength($htmlPath) - 1) == '/') {
                    // remove last slash
                    $htmlPath = cString::getPartOfString($htmlPath, 0, cString::getStringLength($htmlPath) - 1);
                }
            }
        } else {
            $htmlPath = '';
        }
        return ['htmlpath' => $htmlPath, 'url' => $url];
    }

    /**
     * Function to preclean a url.
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
     * Recreates all or only empty aliases in categories table.
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

        foreach ($aCats as $p => $item) {
            self::setCatUrlPath($item['idcat'], $item['idlang']);
        }
    }

    /**
     * Returns list of all empty category aliases
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
     * Recreates all or only empty urlname entries in art_lang table.
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
     * Returns list of all empty article aliases
     *
     * @param bool $onlyNumber
     * @return array|int
     * @throws cDbException
     */
    public static function getEmptyArticlesAliases($onlyNumber = true)
    {
        $db = cRegistry::getDb();
        $return = ($onlyNumber) ? 0 : [];

        // get all empty articles
        $sql = "SELECT title, idart, idlang FROM " . cDb::getTableName('art_lang')
            . " WHERE urlname IS NULL OR urlname = ''";

        $db->query($sql);
        if ($onlyNumber) {
            $return = cSecurity::toInteger($db->numRows());
        } else {
            while ($db->nextRecord()) {
                $return[] = [$db->f('title'), $db->f('idart'), $db->f('idlang')];
            }
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
            'contenido_full_path' => str_replace('\\', '/', realpath(cRegistry::getBackendPath() . '../') . '/'),
            'client_full_path' => cRegistry::getFrontendPath(),
        ];
        $arr['in_contenido_path'] = is_file($arr['contenido_full_path'] . '.htaccess');
        $arr['in_client_path'] = is_file($arr['client_full_path'] . '.htaccess');
        $arr['has_htaccess'] = ($arr['in_contenido_path'] || $arr['in_client_path']);

        return $arr;
    }

}
