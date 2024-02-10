<?php

/**
 * @package    Plugin
 * @subpackage Linkchecker
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class cLinkcheckerTester
 */
class cLinkcheckerTester
{
    /**
     * Checks all links without front_content.php
     *
     * @return array
     * @throws cDbException
     */
    public static function checkLinks()
    {
        global $cronjob, $aErrors;
        global $aSearchIDInfosArt, $aSearchIDInfosCat, $aSearchIDInfosCatArt, $aSearchIDInfosNonID;

        $auth = cRegistry::getAuth();
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();
        $lang = cSecurity::toInteger(cRegistry::getLanguageId());

        if (!is_array($aErrors)) {
            $aErrors = [];
        }

        // Checks idarts
        if (count($aSearchIDInfosArt) > 0) {
            self::_checkArticles($aSearchIDInfosArt, $aErrors, $db, $cfg);
        }

        // Checks idcats
        if (count($aSearchIDInfosCat) > 0) {
            self::_checkCategories($aSearchIDInfosCat, $aErrors, $db, $cfg, $lang);
        }

        // Checks idcatarts
        if (count($aSearchIDInfosCatArt) > 0) {
            self::_checkCategoryArticles($aSearchIDInfosCatArt, $aErrors, $db, $cfg);
        }

        // Checks other links (e.g. http, www, dfbs)
        if (count($aSearchIDInfosNonID) != 0) {
            self::_checkOtherLinks($aSearchIDInfosNonID, $aErrors, $db, $cfg, $auth, $cronjob);
        }

        return $aErrors;
    }

    /**
     * Searches front_content.php-links
     *
     * @param $sValue
     * @param $iArt
     * @param $sArt
     * @param $iCat
     * @param $sCat
     */
    public static function searchFrontContentLinks($sValue, $iArt, $sArt, $iCat, $sCat)
    {
        global $aSearchIDInfosArt, $aSearchIDInfosCat, $aSearchIDInfosCatArt, $aWhitelist;

        // detect urls with parameter idart
        $matches = [];
        if (preg_match_all('/(?!file|ftp|http|ww)front_content.php\?idart=([0-9]*)/i', $sValue, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                if (!in_array($matches[0][$i], $aWhitelist)) {
                    $aSearchIDInfosArt[] = [
                        "id" => $matches[1][$i],
                        "url" => $matches[0][$i],
                        "idart" => $iArt,
                        "nameart" => $sArt,
                        "idcat" => $iCat,
                        "namecat" => $sCat,
                        "urltype" => "intern",
                    ];
                }
            }
        }

        // detect urls with parameter idcat
        $matches = [];
        if (preg_match_all('/(?!file|ftp|http|ww)front_content.php\?idcat=([0-9]*)/i', $sValue, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                if (!in_array($matches[0][$i], $aWhitelist)) {
                    $aSearchIDInfosCat[] = [
                        "id" => $matches[1][$i],
                        "url" => $matches[0][$i],
                        "idart" => $iArt,
                        "nameart" => $sArt,
                        "idcat" => $iCat,
                        "namecat" => $sCat,
                        "urltype" => "intern",
                    ];
                }
            }
        }

        // detect urls with parameter idcatart
        $matches = [];
        if (preg_match_all(
            '/(?!file|ftp|http|ww)front_content.php\?idcatart=([0-9]*)/i',
            $sValue,
            $matches
        )
        ) { // idcatart
            for ($i = 0; $i < count($matches[0]); $i++) {
                if (!in_array($matches[0][$i], $aWhitelist)) {
                    $aSearchIDInfosCatArt[] = [
                        "id" => $matches[1][$i],
                        "url" => $matches[0][$i],
                        "idart" => $iArt,
                        "nameart" => $sArt,
                        "idcat" => $iCat,
                        "namecat" => $sCat,
                        "urltype" => "intern",
                    ];
                }
            }
        }
    }


    /**
     * Checks for articles found in links.
     *
     * @param array $aSearchIDInfosArt
     * @param array $aErrors
     * @param cDb $db
     * @param array $cfg
     * @return void
     * @throws cDbException
     */
    private static function _checkArticles(array &$aSearchIDInfosArt, array &$aErrors, $db, $cfg)
    {
        $aIds = [];
        foreach ($aSearchIDInfosArt as $entry) {
            $aIds[] = cSecurity::toInteger($entry['id']);
        }
        $idArts = implode(', ', $aIds);

        // SQL query, please note: integer cast some lines before!
        $sql = "SELECT `idart`, `online` FROM `%s` WHERE `idart` IN (" . $idArts . ")";
        $db->query($sql, cRegistry::getDbTableName('art_lang'));

        // Check articles
        $aFind = [];
        while ($db->nextRecord()) {
            $aFind[$db->f('idart')] = [
                'online' => $db->f('online'),
            ];
        }

        for ($i = 0; $i < count($aSearchIDInfosArt); $i++) {
            if (isset($aFind[$aSearchIDInfosArt[$i]['id']])
                && $aFind[$aSearchIDInfosArt[$i]['id']]['online'] == 0
            ) {
                $aErrors['art'][] = array_merge($aSearchIDInfosArt[$i], [
                    'error_type' => 'offline',
                ]);
            } elseif (!isset($aFind[$aSearchIDInfosArt[$i]['id']])) {
                $aErrors['art'][] = array_merge($aSearchIDInfosArt[$i], [
                    'error_type' => 'unknown',
                ]);
            }
        }
    }

    /**
     * Checks for categories found in links.
     *
     * @param array $aSearchIDInfosCat
     * @param array $aErrors
     * @param cDb $db
     * @param array $cfg
     * @param int $lang
     * @return void
     * @throws cDbException
     */
    private static function _checkCategories(array &$aSearchIDInfosCat, array &$aErrors, $db, $cfg, $lang)
    {
        $aIds = [];
        foreach ($aSearchIDInfosCat as $entry) {
            $aIds[] = cSecurity::toInteger($entry['id']);
        }
        $sSearch = implode(', ', $aIds);

        // SQL query, please note: integer cast some lines before!
        $sql = "SELECT `idcat`, `startidartlang`, `visible` FROM `%s` WHERE `idcat` IN (" . $sSearch . ") AND `idlang` = %d";
        $db->query($sql, cRegistry::getDbTableName('cat_lang'), $lang);

        // Check categories
        $aFind = [];
        while ($db->nextRecord()) {
            $aFind[$db->f('idcat')] = [
                'online' => $db->f('visible'),
                'startidart' => $db->f('startidartlang'),
            ];
        }

        for ($i = 0; $i < count($aSearchIDInfosCat); $i++) {
            if (is_array($aFind[$aSearchIDInfosCat[$i]['id']])
                && $aFind[$aSearchIDInfosCat[$i]['id']]['startidart'] == 0
            ) {
                $aErrors['cat'][] = array_merge($aSearchIDInfosCat[$i], [
                    'error_type' => 'startart',
                ]);
            } elseif (is_array($aFind[$aSearchIDInfosCat[$i]['id']])
                && $aFind[$aSearchIDInfosCat[$i]['id']]['online'] == 0
            ) {
                $aErrors['cat'][] = array_merge($aSearchIDInfosCat[$i], [
                    'error_type' => 'offline',
                ]);
            } elseif (!is_array($aFind[$aSearchIDInfosCat[$i]['id']])) {
                $aErrors['cat'][] = array_merge($aSearchIDInfosCat[$i], [
                    'error_type' => 'unknown',
                ]);
            }

            if (is_array($aFind[$aSearchIDInfosCat[$i]['id']])
                && $aFind[$aSearchIDInfosCat[$i]['id']]['startidart'] != 0
            ) {
                $sql = "SELECT `idart` FROM `%s` WHERE `idartlang` = %d AND online = 1";
                $db->query($sql, cRegistry::getDbTableName('art_lang'), $aFind[$aSearchIDInfosCat[$i]['id']]['startidart']);

                if ($db->numRows() == 0) {
                    $aErrors['cat'][] = array_merge($aSearchIDInfosCat[$i], [
                        'error_type' => 'startart',
                    ]);
                }
            }
        }
    }

    /**
     * Checks for category-articles found in links.
     *
     * @param array $aSearchIDInfosCatArt
     * @param array $aErrors
     * @param cDb $db
     * @param array $cfg
     * @return void
     * @throws cDbException
     */
    private static function _checkCategoryArticles(array &$aSearchIDInfosCatArt, array &$aErrors, $db, $cfg)
    {
        $aIds = [];
        foreach ($aSearchIDInfosCatArt as $entry) {
            $aIds[] = cSecurity::toInteger($entry['id']);
        }
        $sSearch = implode(', ', $aIds);

        // SQL query, please note: integer cast some lines before!
        $sql = "SELECT `idcatart` FROM `%s` WHERE `idcatart` IN (" . $sSearch . ")";
        $db->query($sql, cRegistry::getDbTableName('cat_art'));

        // Check articles
        $aFind = [];
        while ($db->nextRecord()) {
            $aFind[] = $db->f('idcatart');
        }

        for ($i = 0; $i < count($aSearchIDInfosCatArt); $i++) {
            if (!in_array($aSearchIDInfosCatArt[$i]['id'], $aFind)) {
                $aErrors['art'][] = array_merge($aSearchIDInfosCatArt[$i], [
                    'error_type' => 'unknown',
                ]);
            }
        }
    }

    /**
     * Checks for other links (e.g. http, https, www, dfbs) found in articles.
     *
     * @param array $aSearchIDInfosNonID
     * @param array $aErrors
     * @param cDb $db
     * @param array $cfg
     * @param cAuth $auth
     * @param bool $cronjob
     * @return void
     * @throws cDbException
     */
    private static function _checkOtherLinks(array &$aSearchIDInfosNonID, array &$aErrors, $db, array $cfg, $auth, $cronjob)
    {
        // Select user-rights (is the user admin or sysadmin?)
        $sql = "SELECT `username` FROM `:tab_user` WHERE `user_id` = ':user_id' AND `perms` LIKE '%admin%'";
        $db->query($sql, [
            'tab_user' => cRegistry::getDbTableName('user'),
            'user_id' => $auth->auth['uid'],
        ]);

        // User is admin when he is or when he run the cronjob
        if ($db->numRows() > 0 || $cronjob) {
            // TODO Variable $iAdmin is unused
            $iAdmin = true;
        }

        $frontendPath = cRegistry::getFrontendPath();
        $frontendURL = cRegistry::getFrontendUrl();

        for ($i = 0; $i < count($aSearchIDInfosNonID); $i++) {
            $url = $aSearchIDInfosNonID[$i]['url'];
            $urlLength = cString::getStringLength($url);
            if (!filter_var($url, FILTER_VALIDATE_URL) && !cLinkcheckerHelper::urlIsImage($url)) {
                $aErrors['others'][] = array_merge($aSearchIDInfosNonID[$i], [
                    'error_type' => 'invalidurl',
                ]);
            } elseif (cLinkcheckerHelper::urlIsUri($url)) {
                if (cString::getPartOfString($url, 0, $urlLength) == $frontendURL) {
                    $iPing = @cFileHandler::exists(str_replace($frontendURL, $frontendPath, $url));
                } else {
                    $iPing = @fopen($url, 'r');
                }

                if (!$iPing) {
                    if (cLinkcheckerHelper::urlIsImage($url)) {
                        $aErrors['docimages'][] = array_merge($aSearchIDInfosNonID[$i], [
                            'error_type' => 'unknown',
                        ]);
                    } else {
                        $aErrors['others'][] = array_merge($aSearchIDInfosNonID[$i], [
                            'error_type' => 'unknown',
                        ]);
                    }
                }
            } elseif (cString::getPartOfString($url, $urlLength - 5, 5) == '.html') {
                $iPing = @cFileHandler::exists($frontendURL . $url);

                if (!$iPing) {
                    $aErrors['art'][] = array_merge($aSearchIDInfosNonID[$i], [
                        'error_type' => 'unknown',
                    ]);
                }
            } elseif (cString::getPartOfString($url, 0, 20) == 'dbfs.php?file=' . cApiDbfs::PROTOCOL_DBFS . '/') {
                $sDBurl = cString::getPartOfString($url, 20, $urlLength);
                $iPos = cString::findLastPos($sDBurl, '/');
                $sDirname = cString::getPartOfString($sDBurl, 0, $iPos);
                $sFilename = cString::getPartOfString($sDBurl, $iPos + 1);

                // Check dbfs
                $sql = "SELECT `iddbfs` FROM `:tab_dbfs`"
                    . " WHERE `dirname` IN (':dirname', '" . conHtmlEntityDecode($sDirname) . "')"
                    . " AND `filename` = ':filename'";
                $db->query($sql, [
                    'tab_dbfs' => cRegistry::getDbTableName('dbfs'),
                    'dirname' => $sDirname,
                    'filename' => $sFilename
                ]);

                if ($db->numRows() == 0) {
                    $aErrors['docimages'][] = array_merge($aSearchIDInfosNonID[$i], [
                        'error_type' => 'dbfs',
                    ]);
                }
            } else {
                if (!cFileHandler::exists($frontendPath . $url)) {
                    if (cLinkcheckerHelper::urlIsImage($url)) {
                        $aErrors['docimages'][] = array_merge($aSearchIDInfosNonID[$i], [
                            'error_type' => 'unknown',
                        ]);
                    } else {
                        $aErrors['others'][] = array_merge($aSearchIDInfosNonID[$i], [
                            'error_type' => 'unknown',
                        ]);
                    }
                }
            }
        }
    }

}
