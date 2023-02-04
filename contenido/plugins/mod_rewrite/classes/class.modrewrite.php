<?php
/**
 * AMR Mod Rewrite helper class
 *
 * @package     Plugin
 * @subpackage  ModRewrite
 * @id          $Id$:
 * @author      Stefan Seifarth / stese
 * @author      Murat Purc <murat@purc.de>
 * @copyright   www.polycoder.de
 * @copyright   four for business AG <www.4fb.de>
 * @license     http://www.contenido.org/license/LIZENZ.txt
 * @link        http://www.4fb.de
 * @link        http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class to create websafe names, it also provides several helper functions
 *
 * @author      Stefan Seifarth / stese
 * @author      Murat Purc <murat@purc.de>
 * @package     Plugin
 * @subpackage  ModRewrite
 */
class ModRewrite extends ModRewriteBase {

    /**
     * Database instance
     *
     * @var  cDb
     */
    private static $_db;

    /**
     * Lookup table to cache some internal data such as db query results
     *
     * @var  array
     */
    protected static $_lookupTable;

    /**
     * Initialization, is to call at least once, also possible to call multible
     * times, if different client configuration is to load.
     *
     * Loads configuration of passed client and sets some properties.
     *
     * @param  int $clientId Client id
     *
     * @throws cInvalidArgumentException
     */
    public static function initialize($clientId) {
        mr_loadConfiguration($clientId, true);
        self::$_db = cRegistry::getDb();
        self::$_lookupTable = [];
    }

    /**
     * Check categories on websafe name
     *
     * Check all categories in the main parent category on existing same websafe name
     *
     * @param   string $sName   Websafe name to check
     * @param   int    $iCatId  Current category id
     * @param   int    $iLangId Current language id
     *
     * @return  bool    True if websafename already exists, false if not
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public static function isInCategories($sName = '', $iCatId = 0, $iLangId = 0) {
        $cfg = cRegistry::getConfig();
        $iCatId = cSecurity::toInteger($iCatId);
        $iLangId = cSecurity::toInteger($iLangId);

        // get parentid
        $iParentId = 0;
        $sql = "SELECT parentid FROM " . cRegistry::getDbTableName('cat') . " WHERE idcat = " . $iCatId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            $iParentId = ($aData['parentid'] > 0) ? cSecurity::toInteger($aData['parentid']) : 0;
        }

        // check if websafe name is in this category
        $sql = "SELECT count(cl.idcat) as numcats FROM " . cRegistry::getDbTableName('cat_lang') . " cl "
                . "LEFT JOIN " . cRegistry::getDbTableName('cat') . " c ON cl.idcat = c.idcat WHERE "
                . "c.parentid = '$iParentId' AND cl.idlang = " . $iLangId . " AND "
                . "LOWER(cl.urlname) = LOWER('" . self::$_db->escape($sName) . "') AND cl.idcat <> " . $iCatId;
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
     * @internal This method only considers the case that articles are related to a single category.
     *           The function conIsArticleUrlnameUnique also considers multiple categories.
     *
     * @param    string  $sName    Websafe name to check
     * @param    int     $iArtId   Current article id
     * @param    int     $iLangId  Current language id
     * @param    int     $iCatId   Category id
     *
     * @return   bool    True if websafename already exists, false if not
     * @throws cDbException
     */
    public static function isInCatArticles($sName = '', $iArtId = 0, $iLangId = 0, $iCatId = 0) {
        $cfg = cRegistry::getConfig();
        $iArtId = cSecurity::toInteger($iArtId);
        $iLangId = cSecurity::toInteger($iLangId);
        $iCatId = cSecurity::toInteger($iCatId);

        // handle multipages
        if ($iCatId == 0) {
            // get category id if not set
            $sql = "SELECT idcat FROM " . cRegistry::getDbTableName('cat_art') . " WHERE idart = " . $iArtId;
            if ($aData = mr_queryAndNextRecord($sql)) {
                $iCatId = ($aData['idcat'] > 0) ? cSecurity::toInteger($aData['idcat']) : 0;
            }
        }

        // check if websafe name is in this category
        $sql = "SELECT count(al.idart) as numcats FROM " . cRegistry::getDbTableName('art_lang') . " al "
                . "LEFT JOIN " . cRegistry::getDbTableName('cat_art') . " ca ON al.idart = ca.idart WHERE "
                . " ca.idcat='$iCatId' AND al.idlang=" . $iLangId . " AND "
                . "LOWER(al.urlname) = LOWER('" . self::$_db->escape($sName) . "') AND al.idart <> " . $iArtId;
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
     * @param   string  $sName    Original name (will be converted)
     * @param   int     $iArtId   Current article id
     * @param   int     $iLangId  Current language id
     * @param   int     $iCatId   Category id
     * @return  bool    True if insert was successfully
     * @throws  cInvalidArgumentException
     * @throws  cDbException
     */
    public static function setArtWebsafeName($sName = '', $iArtId = 0, $iLangId = 0, $iCatId = 0) {
        $cfg = cRegistry::getConfig();
        $iArtId = cSecurity::toInteger($iArtId);
        $iLangId = cSecurity::toInteger($iLangId);
        $iCatId = cSecurity::toInteger($iCatId);

        // get websafe name
        $sNewName = cString::cleanURLCharacters(conHtmlEntityDecode($sName));

        // remove double or more separators
        $sNewName = mr_removeMultipleChars('-', $sNewName);

        // check if websafe name already exists
        if (self::isInCatArticles($sNewName, $iArtId, $iLangId, $iCatId)) {
            // create new websafe name if exists
            $sNewName = $sNewName . $iArtId;
        }

        // check again - and set name
        if (!self::isInCatArticles($sNewName, $iArtId, $iLangId, $iCatId)) {
            // insert websafe name in article list
            $sql = "UPDATE " . cRegistry::getDbTableName('art_lang') . " SET urlname = '" . self::$_db->escape($sNewName) . "' "
                . "WHERE idart = " . $iArtId . " AND idlang = " . $iLangId;
            return self::$_db->query($sql);
        } else {
            return false;
        }
    }

    /**
     * Set websafe name in category list.
     *
     * Insert new websafe name in category list.
     *
     * @param   string  $sName    Original name (will be converted) or alias
     * @param   int     $iCatId   Category id
     * @param   int     $iLangId  Language id
     *
     * @return  bool    True if insert was successfully
     * @throws  cInvalidArgumentException
     * @throws  cDbException
     */
    public static function setCatWebsafeName($sName = '', $iCatId = 0, $iLangId = 0) {
        $iCatId = cSecurity::toInteger($iCatId);
        $iLangId = cSecurity::toInteger($iLangId);

        // create websafe name
        $sNewName = cString::cleanURLCharacters(conHtmlEntityDecode($sName));

        // remove double or more separators
        $sNewName = mr_removeMultipleChars('-', $sNewName);

        // check if websafe name already exists
        if (self::isInCategories($sNewName, $iCatId, $iLangId)) {
            // create new websafe name if exists
            $sNewName = $sNewName . $iCatId;
        }

        // check again - and set name
        if (!self::isInCategories($sNewName, $iCatId, $iLangId)) {
            // update urlname
            $sql = "UPDATE " . cRegistry::getDbTableName('cat_lang') . " SET urlname = '" . self::$_db->escape($sNewName) . "' "
                . "WHERE idcat = " . $iCatId . " AND idlang = " . $iLangId;

            ModRewriteDebugger::log([
                'sName' => $sName,
                'iCatId' => $iCatId,
                'iLangId' => $iLangId,
                'sNewName' => $sNewName
            ], 'ModRewrite::setCatWebsafeName $data');

            return self::$_db->query($sql);
        } else {
            return false;
        }
    }

    /**
     * Set urlpath of category
     *
     * @param   int     $iCatId   Category id
     * @param   int     $iLangId  Language id
     *
     * @return  bool    True if insert was successfully
     * @throws  cDbException
     * @throws cInvalidArgumentException
     */
    public static function setCatUrlPath($iCatId = 0, $iLangId = 0) {
        $sPath = self::buildRecursivPath($iCatId, $iLangId);
        $iCatId = cSecurity::toInteger($iCatId);
        $iLangId = cSecurity::toInteger($iLangId);

        // update urlpath
        $sql = "UPDATE " . cRegistry::getDbTableName('cat_lang') . " SET urlpath = '" . self::$_db->escape($sPath) . "' "
            . "WHERE idcat = " . $iCatId . " AND idlang = " . $iLangId;

        ModRewriteDebugger::log([
            'iCatId' => $iCatId,
            'iLangId' => $iLangId,
            'sPath' => $sPath
        ], 'ModRewrite::setCatUrlPath $data');

        return self::$_db->query($sql);
    }

    /**
     * Get article id and language id from article language id
     *
     * @param int $iArtlangId Current article id
     *
     * @return array  Array with idart and idlang of current article
     * @throws cDbException
     */
    public static function getArtIdByArtlangId($iArtlangId = 0) {
        $iArtlangId = cSecurity::toInteger($iArtlangId);
        $sql = "SELECT idart, idlang FROM " . cRegistry::getDbTableName('art_lang') . " WHERE idartlang = " . $iArtlangId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            return $aData;
        }
        return [];
    }

    /**
     * Get article id by article websafe name
     *
     * @param   string    $sArtName  Websafe name
     * @param   int       $iCatId    Category id
     * @param   int       $iLangId   Language id
     *
     * @return  int|NULL  Recent article id or NULL
     * @throws  cDbException
     */
    public static function getArtIdByWebsafeName($sArtName = '', $iCatId = 0, $iLangId = 0) {
        $lang = cRegistry::getLanguageId();
        $iCatId = cSecurity::toInteger($iCatId);
        $iLangId = cSecurity::toInteger($iLangId);
        if (0 === $iLangId && is_int($lang)) {
            $iLangId = $lang;
        }

        $sWhere = '';
        if ($iLangId !== 0) {
            $sWhere = ' AND al.idlang = ' . $iLangId;
        }
        // only article name were given
        if ($iCatId == 0) {
            // get all basic category ids with parentid=0
            $aCatIds = [];
            $sql = "SELECT idcat FROM " . cRegistry::getDbTableName('cat') . " WHERE parentid = 0";
            self::$_db->query($sql);
            while (self::$_db->nextRecord()) {
                $aCatIds[] = "idcat = " . cSecurity::toInteger(self::$_db->f('idcat'));
            }
            $sWhere .= " AND (" . join(" OR ", $aCatIds) . ")";
        } else {
            $sWhere .= " AND ca.idcat = " . $iCatId;
        }

        $sql = "
            SELECT al.idart
            FROM " . cRegistry::getDbTableName('art_lang') . " al
            LEFT JOIN " . cRegistry::getDbTableName('cat_art') . " ca ON al.idart = ca.idart
            WHERE LOWER(al.urlname) = LOWER('" . self::$_db->escape($sArtName) . "') $sWhere";

        if ($aData = mr_queryAndNextRecord($sql)) {
            return $aData['idart'];
        } else {
            return NULL;
        }
    }

    /**
     * Get category name from category id and language id.
     *
     * @param   int $iCatId  Category id
     * @param   int $iLangId Language id
     *
     * @return  string  Category name
     * @throws cDbException
     */
    public static function getCatName($iCatId = 0, $iLangId = 0) {
        $iCatId = cSecurity::toInteger($iCatId);
        $iLangId = cSecurity::toInteger($iLangId);
        $key = 'catname_by_catid_idlang_' . $iCatId . '_' . $iLangId;

        if (isset(self::$_lookupTable[$key])) {
            return self::$_lookupTable[$key];
        }

        $sql = "SELECT name FROM " . cRegistry::getDbTableName('cat_lang')
            . " WHERE idcat = " . $iCatId . " AND idlang = " . $iLangId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            $catName = $aData['name'];
        } else {
            $catName = '';
        }

        self::$_lookupTable[$key] = $catName;
        return $catName;
    }

    /**
     * Funcion to return cat id by path.
     *
     * Caches the paths at first call to provode faster processing at further calls.
     *
     * @param   string $path Category path
     *
     * @return  int  Category id
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public static function getCatIdByUrlPath($path) {
        $client = cSecurity::toInteger(cRegistry::getClientId());
        $lang = cSecurity::toInteger(cRegistry::getLanguageId());

        if (cString::findFirstPos($path, '/') === 0) {
            $path = cString::getPartOfString($path, 1);
        }
        if (cString::findLastPos($path, '/') === cString::getStringLength($path) - 1) {
            $path = cString::getPartOfString($path, 0, -1);
        }

        $catSeperator = '/';
        $startFromRoot = parent::getConfig('startfromroot');
        $urls2lowercase = parent::getConfig('use_lowercase_uri');

        $path = str_replace('/', parent::getConfig('category_seperator'), $path);

        $key = 'cat_ids_and_urlpath_' . $client . '_' . $lang;

        if (isset(self::$_lookupTable[$key])) {
            $aPathsCache = self::$_lookupTable[$key];
        } else {
            $aPathsCache = [];
        }

        if (count($aPathsCache) == 0) {
            $sql = "SELECT cl.idcat, cl.urlpath FROM " . cRegistry::getDbTableName('cat_lang')
                . " AS cl, " . cRegistry::getDbTableName('cat') . " AS c WHERE c.idclient = " . cSecurity::toInteger($client)
                . " AND c.idcat = cl.idcat AND cl.idlang = " . cSecurity::toInteger($lang);

            self::$_db->query($sql);
            while (self::$_db->nextRecord()) {
                $urlPath = self::$_db->f('urlpath');
                if ($startFromRoot == 0 && cString::findFirstPos($urlPath, $catSeperator) > 0) {
                    // paths are stored with prefixed main category, but created
                    // urls doesn't contain the main cat, remove it...
                    $urlPath = cString::getPartOfString($urlPath, cString::findFirstPos($urlPath, $catSeperator) + 1);
                }
                if ($urls2lowercase) {
                    $urlPath = cString::toLowerCase($urlPath);
                }

                // store path
                $aPathsCache[self::$_db->f('idcat')] = $urlPath;
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
            return $catId;
        }
    }

    /**
     * Get article name from article id and language id
     *
     * @NOTE: seems to be not used???
     *
     * @param   int $iArtId  Article id
     * @param   int $iLangId Language id
     *
     * @return  string  Article name
     * @throws cDbException
     */
    public static function getArtTitle($iArtId = 0, $iLangId = 0) {
        $iArtId = cSecurity::toInteger($iArtId);
        $iLangId = cSecurity::toInteger($iLangId);

        $sql = "SELECT title FROM " . cRegistry::getDbTableName('art_lang')
            . " WHERE idart = " . $iArtId . " AND idlang = " . $iLangId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            return $aData['title'];
        }
        return '';
    }

    /**
     * Get language ids from category id
     *
     * @param   int $iCatId Category id
     *
     * @return  array  Used language ids
     * @throws  cDbException
     */
    public static function getCatLanguages($iCatId = 0) {
        $iCatId = cSecurity::toInteger($iCatId);
        $key = 'cat_idlang_by_catid_' . $iCatId;

        if (isset(self::$_lookupTable[$key])) {
            return self::$_lookupTable[$key];
        }

        $aLanguages = [];

        $sql = "SELECT idlang FROM " . cRegistry::getDbTableName('cat_lang') . " WHERE idcat = " . $iCatId;
        self::$_db->query($sql);
        while (self::$_db->nextRecord()) {
            $aLanguages[] = self::$_db->f('idlang');
        }

        self::$_lookupTable[$key] = $aLanguages;
        return $aLanguages;
    }

    /**
     * Get article urlname and language id
     *
     * @param   int $iArtlangId idartlang
     *
     * @return  array  Urlname, idlang of empty array
     * @throws cDbException
     */
    public static function getArtIds($iArtlangId = 0) {
        $iArtlangId = cSecurity::toInteger($iArtlangId);
        $sql = "SELECT urlname, idlang FROM " . cRegistry::getDbTableName('art_lang')
            . " WHERE idartlang = " . $iArtlangId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            return $aData;
        }
        return [];
    }

    /**
     * Build a recursiv path for mod_rewrite rule like server directories
     * (dir1/dir2/dir3)
     *
     * @param   int     $iCatId   Latest category id
     * @param   int     $iLangId  Language id
     * @param   int     $iLastId  Last category id
     *
     * @return  string  linkpath with correct uri
     * @throws cDbException
     */
    public static function buildRecursivPath($iCatId = 0, $iLangId = 0, $iLastId = 0) {
        $aDirectories = [];
        $bFinish = false;
        $iTmpCatId = cSecurity::toInteger($iCatId);
        $iLangId = cSecurity::toInteger($iLangId);
        $iLastId = cSecurity::toInteger($iLastId);

        while ($bFinish == false) {
            $sql = "SELECT cl.urlname, c.parentid FROM " . cRegistry::getDbTableName('cat_lang') . " cl "
                . "LEFT JOIN " . cRegistry::getDbTableName('cat') . " c ON cl.idcat = c.idcat "
                . "WHERE cl.idcat = " . $iTmpCatId . " AND cl.idlang = " . $iLangId;
            if ($aData = mr_queryAndNextRecord($sql)) {
                $aDirectories[] = $aData['urlname'];
                $iTmpCatId = cSecurity::toInteger($aData['parentid']);

                if ($aData['parentid'] == 0 || $aData['parentid'] == $iLastId) {
                    $bFinish = true;
                }
            } else {
                $bFinish = true;
            }
        }

        // reverse array entries and create directory string
        return join('/', array_reverse($aDirectories));
    }

    /**
     * Return full CONTENIDO url from single anchor
     *
     * @param   array   $aMatches [0] = complete anchor, [1] = pre arguments, [2] = anchor name, [3] = post arguments
     * @return  string  New anchor
     */
    public static function rewriteHtmlAnchor(array $aMatches = []) {
        global $artname;

        $client = cSecurity::toInteger(cRegistry::getClientId());
        $lang = cSecurity::toInteger(cRegistry::getLanguageId());
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
     * @param   array   $aMatches [0] = complete anchor, [1] = pre arguments, [2] = anchor name, [3] = post arguments
     * @param   bool    $bXHTML  Flag to return XHTML valid url
     * @return  string  New anchor
     */
    public static function contenidoHtmlAnchor(array $aMatches = [], $bXHTML = true) {
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
     * @param    int     $iArtId   Article id
     * @param    int     $iLangId  Language id
     *
     * @return   string    Article websafe name
     * @throws cDbException
     */
    public static function getArtWebsafeName($iArtId = 0, $iLangId = 0) {
        $iArtId = cSecurity::toInteger($iArtId);
        $iLangId = cSecurity::toInteger($iLangId);
        $sql = "SELECT urlname FROM " . cRegistry::getDbTableName('art_lang')
            . " WHERE idart = " . $iArtId . " AND idlang = " . $iLangId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            return $aData['urlname'];
        }
        return NULL;
    }

    /**
     * Get article websafe name from idartlang.
     *
     * @param    int $iArtLangId idartlang
     *
     * @return     string    Article websafe name
     * @throws cDbException
     */
    public static function getArtLangWebsafeName($iArtLangId = 0) {
        $iArtLangId = cSecurity::toInteger($iArtLangId);
        $sql = "SELECT urlname FROM " . cRegistry::getDbTableName('art_lang') . " WHERE idartlang = " . $iArtLangId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            return $aData['urlname'];
        }
        return NULL;
    }

    /**
     * Get name of client by id.
     *
     * @param   int $clientId Client id
     *
     * @return  string  Client name
     * @throws cDbException
     */
    public static function getClientName($clientId = 0) {
        $clientId = cSecurity::toInteger($clientId);
        $key = 'clientname_by_clientid_' . $clientId;

        if (isset(self::$_lookupTable[$key])) {
            return self::$_lookupTable[$key];
        }

        $sql = "SELECT name FROM " . cRegistry::getDbTableName('clients') . " WHERE idclient = " . $clientId;
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
     * @param   string $sClientName Client name
     *
     * @return  int  Client id
     * @throws cDbException
     */
    public static function getClientId($sClientName = '') {
        $sClientName = cString::toLowerCase($sClientName);
        $key = 'clientid_by_name_' . $sClientName;

        if (isset(self::$_lookupTable[$key])) {
            return self::$_lookupTable[$key];
        }

        $sql = "SELECT idclient FROM " . cRegistry::getDbTableName('clients')
            . " WHERE LOWER(name) = '" . self::$_db->escape($sClientName) . "'"
            . " OR LOWER(name) = '" . self::$_db->escape(urldecode($sClientName)) . "'";
        if ($aData = mr_queryAndNextRecord($sql)) {
            $clientId = $aData['idclient'];
        } else {
            $clientId = false;
        }

        self::$_lookupTable[$key] = $clientId;
        return $clientId;
    }

    /**
     * Checks if client id exists
     *
     * @param   int $clientId
     *
     * @return  bool
     * @throws cDbException
     */
    public static function clientIdExists($clientId) {
        $clientId = cSecurity::toInteger($clientId);
        $key = 'clientid_exists_' . $clientId;

        if (isset(self::$_lookupTable[$key])) {
            return self::$_lookupTable[$key];
        }

        $sql = "SELECT idclient FROM " . cRegistry::getDbTableName('clients') . " WHERE idclient = " . $clientId;
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
     * @param   int $languageId Language id
     *
     * @return  string  Lanuage name
     * @throws cDbException
     */
    public static function getLanguageName($languageId = 0) {
        $languageId = cSecurity::toInteger($languageId);
        $key = 'languagename_by_id_' . $languageId;

        if (isset(self::$_lookupTable[$key])) {
            return self::$_lookupTable[$key];
        }

        $sql = "SELECT name FROM " . cRegistry::getDbTableName('lang') . " WHERE idlang = " . $languageId;
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
     * @param   int $languageId Language id
     *
     * @return  bool
     * @throws cDbException
     */
    public static function languageIdExists($languageId) {
        $languageId = cSecurity::toInteger($languageId);
        $key = 'languageid_exists_' . $languageId;

        if (isset(self::$_lookupTable[$key])) {
            return self::$_lookupTable[$key];
        }

        $sql = "SELECT idlang FROM " . cRegistry::getDbTableName('lang') . " WHERE idlang = " . $languageId;
        if ($aData = mr_queryAndNextRecord($sql)) {
            $exists = true;
        } else {
            $exists = false;
        }

        self::$_lookupTable[$key] = $exists;
        return $exists;
    }

    /**
     * Get language id from language name thanks to Nicolas Dickinson for multi
     * Client/Language BugFix
     *
     * @param  string $sLanguageName Language name
     * @param  int    $iClientId     Client id
     *
     * @return int  Language id
     * @throws cDbException
     */
    public static function getLanguageId($sLanguageName = '', $iClientId = 1) {
        $sLanguageName = cString::toLowerCase($sLanguageName);
        $iClientId = cSecurity::toInteger($iClientId);
        $key = 'langid_by_langname_clientid_' . $sLanguageName . '_' . $iClientId;

        if (isset(self::$_lookupTable[$key])) {
            return self::$_lookupTable[$key];
        }

        $sql = "SELECT l.idlang FROM " . cRegistry::getDbTableName('lang') . " as l "
            . "LEFT JOIN " . cRegistry::getDbTableName('clients_lang') . " AS cl ON l.idlang = cl.idlang "
            . "WHERE cl.idclient = " . $iClientId . " AND (LOWER(l.name) = '" . self::$_db->escape($sLanguageName) . "' "
            . "OR LOWER(l.name) = '" . self::$_db->escape(urldecode($sLanguageName)) . "')";
        if ($aData = mr_queryAndNextRecord($sql)) {
            $languageId = $aData['idlang'];
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
     * input  = http://host/front_content.php?idcat=123
     * return = ['htmlpath' => 'http://host', 'url' => 'front_content.php?idcat=123']
     *
     * @param  string  $url  URL to split
     * @return array   Assoziative array including the two parts:
     *                 - ['htmlpath' => $path, 'url' => $url]
     */
    public static function getClientFullUrlParts($url) {
        $clientPath = cRegistry::getFrontendUrl();

        if (cString::findFirstOccurrenceCI($url, $clientPath) !== false) {
            // url includes full html path (scheme host path, etc.)
            $url = str_replace($clientPath, '', $url);
            $htmlPath = $clientPath;
            $aComp = parse_url($htmlPath);

            // check if path matches to defined rootdir from mod_rewrite conf
            if (isset($aComp['path']) && $aComp['path'] !== parent::getConfig('rootdir')) {
                // replace not matching path agaings configured one
                // this will replace e. g. "http://host/cms/" against "http://host/"
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
     * Removes absolute path declaration '/front_content.php' or relative path
     * definition to actual dir './front_content.php', ampersand entities '&amp;'
     * and returns a url like 'front_content.php?idart=12&idlang=1'
     *
     * @param   string  $url  Url to clean
     * @return  string  Cleaned Url
     */
    public static function urlPreClean($url) {
        // some preparation of different front_content.php occurence
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
     * @param  bool $bOnlyEmpty Flag to reset only empty items
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public static function recreateCategoriesAliases($bOnlyEmpty = false) {
        $db = cRegistry::getDb();
        $aCats = [];

        // get all or only empty categories
        $sql = "SELECT name, idcat, idlang FROM " . cRegistry::getDbTableName('cat_lang');
        if ($bOnlyEmpty === true) {
            $sql .= " WHERE urlname IS NULL OR urlname = '' OR urlpath IS NULL OR urlpath = ''";
        }

        $db->query($sql);
        while ($db->nextRecord()) {
            //set new alias
            self::setCatWebsafeName($db->f('name'), $db->f('idcat'), $db->f('idlang'));
            $aCats[] = ['idcat' => $db->f('idcat'), 'idlang' => $db->f('idlang')];
        }

        foreach ($aCats as $p => $item) {
            self::setCatUrlPath($item['idcat'], $item['idlang']);
        }
    }

    /**
     * Returns list of all empty category aliases
     *
     * @param bool $bOnlyNumber
     *
     * @return array|int
     * @throws cDbException
     */
    public static function getEmptyCategoriesAliases($bOnlyNumber = true) {
        $db = cRegistry::getDb();
        $return = ($bOnlyNumber) ? 0 : [];

        // get all empty categories
        $sql = "SELECT name, idcat, idlang FROM " . cRegistry::getDbTableName('cat_lang')
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
     * @param  bool $bOnlyEmpty Flag to reset only empty items
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public static function recreateArticlesAliases($bOnlyEmpty = false) {
        $db = cRegistry::getDb();

        // get all or only empty articles
        $sql = "SELECT title, idart, idlang FROM " . cRegistry::getDbTableName('art_lang');
        if ($bOnlyEmpty === true) {
            $sql .= " WHERE urlname IS NULL OR urlname = ''";
        }
        $db->query($sql);

        while ($db->nextRecord()) {
            //set new alias
            self::setArtWebsafeName($db->f('title'), $db->f('idart'), $db->f('idlang'));
        }
    }

    /**
     * Returns list of all empty article aliases
     *
     * @param   bool  $bOnlyNumber
     * @return  array|int
     * @throws  cDbException
     */
    public static function getEmptyArticlesAliases($bOnlyNumber = true) {
        $db = cRegistry::getDb();
        $return = ($bOnlyNumber) ? 0 : [];

        // get all empty articles
        $sql  = "SELECT title, idart, idlang FROM " . cRegistry::getDbTableName('art_lang')
            . " WHERE urlname IS NULL OR urlname = ''";

        $db->query($sql);
        if ($bOnlyNumber) {
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
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public static function resetAliases() {
        self::recreateCategoriesAliases();
        self::recreateArticlesAliases();
    }

    /**
     * Recreate all or only empty aliases (categories and articles).
     *
     * Shortcut to recreateCategoriesAliases() and recreateArticlesAliases()
     *
     * @param  bool $bOnlyEmpty Flag to reset only empty items
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public static function recreateAliases($bOnlyEmpty = false) {
        self::recreateCategoriesAliases($bOnlyEmpty);
        self::recreateArticlesAliases($bOnlyEmpty);
    }

    /**
     * Returns .htaccess related assoziative info array
     *
     * @return  array
     */
    public static function getHtaccessInfo() {
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
