<?php

/**
 * @package    Plugin
 * @subpackage Linkchecker
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
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
     * @return mixed
     * @throws cDbException
     */
    public static function checkLinks()
    {
        global $auth, $cfg, $cronjob, $db, $aErrors, $lang;
        global $aSearchIDInfosArt, $aSearchIDInfosCat, $aSearchIDInfosCatArt, $aSearchIDInfosNonID;

        $sSearch = '';

        if (count($aSearchIDInfosArt) > 0) { // Checks idarts

            for ($i = 0; $i < count($aSearchIDInfosArt); $i++) {
                if ($i == 0) {
                    $sSearch = cSecurity::toInteger($aSearchIDInfosArt[$i]['id']);
                } else {
                    $sSearch .= ", " . cSecurity::toInteger($aSearchIDInfosArt[$i]['id']);
                }
            }

            // SQL query, please note: integer cast some lines before!
            $sql = "SELECT idart, online FROM " . $cfg['tab']['art_lang'] . " WHERE idart IN (" . $sSearch . ")";
            $db->query($sql);

            // Check articles
            $aFind = [];

            while ($db->nextRecord()) {
                $aFind[$db->f("idart")] = [
                    "online" => $db->f("online"),
                ];
            }

            for ($i = 0; $i < count($aSearchIDInfosArt); $i++) {
                if (isset($aFind[$aSearchIDInfosArt[$i]['id']])
                    && $aFind[$aSearchIDInfosArt[$i]['id']]['online'] == 0
                ) {
                    $aErrors['art'][] = array_merge(
                        $aSearchIDInfosArt[$i],
                        [
                            "error_type" => "offline",
                        ]
                    );
                } elseif (!isset($aFind[$aSearchIDInfosArt[$i]['id']])) {
                    $aErrors['art'][] = array_merge(
                        $aSearchIDInfosArt[$i],
                        [
                            "error_type" => "unknown",
                        ]
                    );
                }
            }
        }

        if (count($aSearchIDInfosCat) > 0) { // Checks idcats

            for ($i = 0; $i < count($aSearchIDInfosCat); $i++) {
                if ($i == 0) {
                    $sSearch = cSecurity::toInteger($aSearchIDInfosCat[$i]['id']);
                } else {
                    $sSearch .= ", " . cSecurity::toInteger($aSearchIDInfosCat[$i]['id']);
                }
            }

            // SQL query, please note: integer cast some lines before!
            $sql =
                "SELECT idcat, startidartlang, visible FROM " . $cfg['tab']['cat_lang'] . " WHERE idcat IN (" . $sSearch
                . ") AND idlang = '" . cSecurity::toInteger($lang) . "'";
            $db->query($sql);

            // Check categories
            $aFind = [];

            while ($db->nextRecord()) {
                $aFind[$db->f("idcat")] = [
                    "online"     => $db->f("visible"),
                    "startidart" => $db->f("startidartlang"),
                ];
            }

            for ($i = 0; $i < count($aSearchIDInfosCat); $i++) {
                if (is_array($aFind[$aSearchIDInfosCat[$i]['id']])
                    && $aFind[$aSearchIDInfosCat[$i]['id']]['startidart'] == 0
                ) {
                    $aErrors['cat'][] = array_merge(
                        $aSearchIDInfosCat[$i],
                        [
                            "error_type" => "startart",
                        ]
                    );
                } elseif (is_array($aFind[$aSearchIDInfosCat[$i]['id']])
                    && $aFind[$aSearchIDInfosCat[$i]['id']]['online'] == 0
                ) {
                    $aErrors['cat'][] = array_merge(
                        $aSearchIDInfosCat[$i],
                        [
                            "error_type" => "offline",
                        ]
                    );
                } elseif (!is_array($aFind[$aSearchIDInfosCat[$i]['id']])) {
                    $aErrors['cat'][] = array_merge(
                        $aSearchIDInfosCat[$i],
                        [
                            "error_type" => "unknown",
                        ]
                    );
                }

                if (is_array($aFind[$aSearchIDInfosCat[$i]['id']])
                    && $aFind[$aSearchIDInfosCat[$i]['id']]['startidart'] != 0
                ) {
                    $sql =
                        "SELECT idart FROM " . $cfg['tab']['art_lang'] . " WHERE idartlang = '" . cSecurity::toInteger(
                            $aFind[$aSearchIDInfosCat[$i]['id']]['startidart']
                        ) . "' AND online = '1'";
                    $db->query($sql);

                    if ($db->numRows() == 0) {
                        $aErrors['cat'][] = array_merge(
                            $aSearchIDInfosCat[$i],
                            [
                                "error_type" => "startart",
                            ]
                        );
                    }
                }
            }
        }

        if (count($aSearchIDInfosCatArt) > 0) { // Checks idcatarts

            for ($i = 0; $i < count($aSearchIDInfosCatArt); $i++) {
                if ($i == 0) {
                    $sSearch = cSecurity::toInteger($aSearchIDInfosCatArt[$i]['id']);
                } else {
                    $sSearch .= ", " . cSecurity::toInteger($aSearchIDInfosCatArt[$i]['id']);
                }
            }

            // SQL query, please note: integer cast some lines before!
            $sql = "SELECT idcatart FROM " . $cfg['tab']['cat_art'] . " WHERE idcatart IN (" . $sSearch . ")";
            $db->query($sql);

            // Check articles
            $aFind = [];

            while ($db->nextRecord()) {
                $aFind[] = $db->f("idcatart");
            }

            for ($i = 0; $i < count($aSearchIDInfosCatArt); $i++) {
                if (!in_array($aSearchIDInfosCatArt[$i]['id'], $aFind)) {
                    $aErrors['art'][] = array_merge(
                        $aSearchIDInfosCatArt[$i],
                        [
                            "error_type" => "unknown",
                        ]
                    );
                }
            }
        }

        if (count($aSearchIDInfosNonID) != 0) { // Checks other links (e. g. http,
            // www, dfbs)

            // Select userrights (is the user admin or sysadmin?)
            $sql = "SELECT username FROM " . $cfg['tab']['user'] . " WHERE user_id='" . cSecurity::toInteger(
                    $auth->auth['uid']
                ) . "' AND perms LIKE '%admin%'";
            $db->query($sql);

            if ($db->numRows() > 0 || $cronjob == true) { // User is admin when he
                // is or when he run the
                // cronjob
                $iAdmin = true;
            }

            $frontendPath = cRegistry::getFrontendPath();
            $frontendURL  = cRegistry::getFrontendUrl();

            for ($i = 0; $i < count($aSearchIDInfosNonID); $i++) {
                if (!filter_var($aSearchIDInfosNonID[$i]['url'], FILTER_VALIDATE_URL)
                    && !url_is_image(
                        $aSearchIDInfosNonID[$i]['url']
                    )
                ) {
                    $aErrors['others'][] = array_merge(
                        $aSearchIDInfosNonID[$i],
                        [
                            "error_type" => "invalidurl",
                        ]
                    );
                } elseif (url_is_uri($aSearchIDInfosNonID[$i]['url'])) {
                    if (cString::getPartOfString(
                            $aSearchIDInfosNonID[$i]['url'],
                            0,
                            cString::getStringLength($aSearchIDInfosNonID[$i]['url'])
                        ) == $frontendURL
                    ) {
                        $iPing = @cFileHandler::exists(
                            str_replace($frontendURL, $frontendPath, $aSearchIDInfosNonID[$i]['url'])
                        );
                    } else {
                        $iPing = @fopen($aSearchIDInfosNonID[$i]['url'], 'r');
                    }

                    if (!$iPing) {
                        if (url_is_image($aSearchIDInfosNonID[$i]['url'])) {
                            $aErrors['docimages'][] = array_merge(
                                $aSearchIDInfosNonID[$i],
                                [
                                    "error_type" => "unknown",
                                ]
                            );
                        } else {
                            $aErrors['others'][] = array_merge(
                                $aSearchIDInfosNonID[$i],
                                [
                                    "error_type" => "unknown",
                                ]
                            );
                        }
                    }
                } elseif (cString::getPartOfString(
                        $aSearchIDInfosNonID[$i]['url'],
                        cString::getStringLength($aSearchIDInfosNonID[$i]['url']) - 5,
                        5
                    ) == ".html"
                ) {
                    $iPing = @cFileHandler::exists($frontendURL . $aSearchIDInfosNonID[$i]['url']);

                    if (!$iPing) {
                        $aErrors['art'][] = array_merge(
                            $aSearchIDInfosNonID[$i],
                            [
                                "error_type" => "unknown",
                            ]
                        );
                    }
                } elseif (cString::getPartOfString($aSearchIDInfosNonID[$i]['url'], 0, 20) == "dbfs.php?file="
                    . cApiDbfs::PROTOCOL_DBFS . "/"
                ) {
                    $sDBurl = cString::getPartOfString(
                        $aSearchIDInfosNonID[$i]['url'],
                        20,
                        cString::getStringLength($aSearchIDInfosNonID[$i]['url'])
                    );

                    $iPos      = cString::findLastPos($sDBurl, '/');
                    $sDirname  = cString::getPartOfString($sDBurl, 0, $iPos);
                    $sFilename = cString::getPartOfString($sDBurl, $iPos + 1);

                    // Check dbfs
                    $sql = "SELECT iddbfs FROM " . $cfg['tab']['dbfs'] . " WHERE dirname IN('" . cSecurity::escapeDB(
                            $sDirname,
                            $db
                        ) . "', '" . conHtmlEntityDecode($sDirname) . "', '" . cSecurity::escapeDB($sDirname, $db)
                        . "') AND filename = '" . cSecurity::escapeDB($sFilename, $db) . "'";
                    $db->query($sql);

                    if ($db->numRows() == 0) {
                        $aErrors['docimages'][] = array_merge(
                            $aSearchIDInfosNonID[$i],
                            [
                                "error_type" => "dbfs",
                            ]
                        );
                    }
                } else {
                    if (!cFileHandler::exists($frontendPath . $aSearchIDInfosNonID[$i]['url'])) {
                        if (url_is_image($aSearchIDInfosNonID[$i]['url'])) {
                            $aErrors['docimages'][] = array_merge(
                                $aSearchIDInfosNonID[$i],
                                [
                                    "error_type" => "unknown",
                                ]
                            );
                        } else {
                            $aErrors['others'][] = array_merge(
                                $aSearchIDInfosNonID[$i],
                                [
                                    "error_type" => "unknown",
                                ]
                            );
                        }
                    }
                }
            }
        }

        return $aErrors;
    }

    /**
     * Searchs front_content.php-links
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
                        "id"      => $matches[1][$i],
                        "url"     => $matches[0][$i],
                        "idart"   => $iArt,
                        "nameart" => $sArt,
                        "idcat"   => $iCat,
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
                        "id"      => $matches[1][$i],
                        "url"     => $matches[0][$i],
                        "idart"   => $iArt,
                        "nameart" => $sArt,
                        "idcat"   => $iCat,
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
                        "id"      => $matches[1][$i],
                        "url"     => $matches[0][$i],
                        "idart"   => $iArt,
                        "nameart" => $sArt,
                        "idcat"   => $iCat,
                        "namecat" => $sCat,
                        "urltype" => "intern",
                    ];
                }
            }
        }
    }
}
