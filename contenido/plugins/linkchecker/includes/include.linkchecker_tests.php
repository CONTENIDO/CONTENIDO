<?php
/**
 * This is the tests backend page for the linkchecker plugin.
 *
 * @package Plugin
 * @subpackage Linkchecker
 * @version SVN Revision $Rev:$
 *
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// Checks all links without front_content.php
function checkLinks() {
    global $auth, $cfgClient, $client, $cfg, $cronjob, $db, $aErrors, $lang, $langart, $whitelist;
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

        // Check articles
        $aFind = array();
        $sql = "SELECT idart, online FROM " . $cfg['tab']['art_lang'] . " WHERE idart IN (" . $sSearch . ")";
        $db->query($sql);

        while ($db->nextRecord()) {
            $aFind[$db->f("idart")] = array(
                "online" => $db->f("online")
            );
        }

        for ($i = 0; $i < count($aSearchIDInfosArt); $i++) {

            if (isset($aFind[$aSearchIDInfosArt[$i]['id']]) && $aFind[$aSearchIDInfosArt[$i]['id']]['online'] == 0) {
                $aErrors['art'][] = array_merge($aSearchIDInfosArt[$i], array(
                    "error_type" => "offline"
                ));
            } elseif (!isset($aFind[$aSearchIDInfosArt[$i]['id']])) {
                $aErrors['art'][] = array_merge($aSearchIDInfosArt[$i], array(
                    "error_type" => "unknown"
                ));
            }
        }
    }

    if (count($aSearchIDInfosCat) > 0) { // Checks idcats

        for ($i = 0; $i < count($aSearchIDInfosCat); $i++) {

            if ($i == 0) {
                $sSearch = $aSearchIDInfosCat[$i]['id'];
            } else {
                $sSearch .= ", " . $aSearchIDInfosCat[$i]['id'];
            }
        }

        // Check categories
        $aFind = array();
        $sql = "SELECT idcat, startidartlang, visible FROM " . $cfg['tab']['cat_lang'] . " WHERE idcat IN (" . $sSearch . ") AND idlang = '" . cSecurity::toInteger($lang) . "'";
        $db->query($sql);

        while ($db->nextRecord()) {
            $aFind[$db->f("idcat")] = array(
                "online" => $db->f("visible"),
                "startidart" => $db->f("startidartlang")
            );
        }

        for ($i = 0; $i < count($aSearchIDInfosCat); $i++) {

            if (is_array($aFind[$aSearchIDInfosCat[$i]['id']]) && $aFind[$aSearchIDInfosCat[$i]['id']]['startidart'] == 0) {
                $aErrors['cat'][] = array_merge($aSearchIDInfosCat[$i], array(
                    "error_type" => "startart"
                ));
            } elseif (is_array($aFind[$aSearchIDInfosCat[$i]['id']]) && $aFind[$aSearchIDInfosCat[$i]['id']]['online'] == 0) {
                $aErrors['cat'][] = array_merge($aSearchIDInfosCat[$i], array(
                    "error_type" => "offline"
                ));
            } elseif (!is_array($aFind[$aSearchIDInfosCat[$i]['id']])) {
                $aErrors['cat'][] = array_merge($aSearchIDInfosCat[$i], array(
                    "error_type" => "unknown"
                ));
            }

            if (is_array($aFind[$aSearchIDInfosCat[$i]['id']]) && $aFind[$aSearchIDInfosCat[$i]['id']]['startidart'] != 0) {

                $sql = "SELECT idart FROM " . $cfg['tab']['art_lang'] . " WHERE idartlang = '" . $aFind[$aSearchIDInfosCat[$i]['id']]['startidart'] . "' AND online = '1'";
                $db->query($sql);

                if ($db->numRows() == 0) {
                    $aErrors['cat'][] = array_merge($aSearchIDInfosCat[$i], array(
                        "error_type" => "startart"
                    ));
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

        // Check articles
        $aFind = array();
        $sql = "SELECT idcatart FROM " . $cfg['tab']['cat_art'] . " WHERE idcatart IN (" . $sSearch . ")";
        $db->query($sql);

        while ($db->nextRecord()) {
            $aFind[] = $db->f("idcatart");
        }

        for ($i = 0; $i < count($aSearchIDInfosCatArt); $i++) {

            if (!in_array($aSearchIDInfosCatArt[$i]['id'], $aFind)) {
                $aErrors['art'][] = array_merge($aSearchIDInfosCatArt[$i], array(
                    "error_type" => "unknown"
                ));
            }
        }
    }

    if (count($aSearchIDInfosNonID) != 0) { // Checks other links (e. g. http,
                                            // www, dfbs)

        // Select userrights (is the user admin or sysadmin?)
        $sql = "SELECT username FROM " . $cfg['tab']['user'] . " WHERE user_id='" . $db->escape($auth->auth['uid']) . "' AND perms LIKE '%admin%'";
        $db->query($sql);

        if ($db->numRows() > 0 || $cronjob == true) { // User is admin when he
                                                      // is or when he run the
                                                      // cronjob
            $iAdmin = true;
        }

        $frontendPath = cRegistry::getFrontendPath();
        $frontendURL = cRegistry::getFrontendUrl();

        for ($i = 0; $i < count($aSearchIDInfosNonID); $i++) {
            if (!filter_var($aSearchIDInfosNonID[$i]['url'], FILTER_VALIDATE_URL) && !url_is_image($aSearchIDInfosNonID[$i]['url'])) {
                $aErrors['others'][] = array_merge($aSearchIDInfosNonID[$i], array(
                    "error_type" => "invalidurl"
                ));
            } elseif (url_is_uri($aSearchIDInfosNonID[$i]['url'])) {
                if (substr($aSearchIDInfosNonID[$i]['url'], 0, strlen($aSearchIDInfosNonID[$i]['url'])) == $frontendURL) {
                    $iPing = @cFileHandler::exists(str_replace($frontendURL, $frontendPath, $aSearchIDInfosNonID[$i]['url']));
                } else {
                    $iPing = @fopen($aSearchIDInfosNonID[$i]['url'], 'r');
                }

                if (!$iPing) {

                    if (url_is_image($aSearchIDInfosNonID[$i]['url'])) {
                        $aErrors['docimages'][] = array_merge($aSearchIDInfosNonID[$i], array(
                            "error_type" => "unknown"
                        ));
                    } else {
                        $aErrors['others'][] = array_merge($aSearchIDInfosNonID[$i], array(
                            "error_type" => "unknown"
                        ));
                    }
                }
            } elseif (substr($aSearchIDInfosNonID[$i]['url'], strlen($aSearchIDInfosNonID[$i]['url']) - 5, 5) == ".html") {

                $iPing = @cFileHandler::exists($frontendURL . $aSearchIDInfosNonID[$i]['url']);

                if (!$iPing) {
                    $aErrors['art'][] = array_merge($aSearchIDInfosNonID[$i], array(
                        "error_type" => "unknown"
                    ));
                }
            } elseif (substr($aSearchIDInfosNonID[$i]['url'], 0, 20) == "dbfs.php?file=" . cApiDbfs::PROTOCOL_DBFS . "/") {

                $sDBurl = substr($aSearchIDInfosNonID[$i]['url'], 20, strlen($aSearchIDInfosNonID[$i]['url']));

                $iPos = strrpos($sDBurl, '/');
                $sDirname = substr($sDBurl, 0, $iPos);
                $sFilename = substr($sDBurl, $iPos + 1);

                // Check dbfs
                $sql = "SELECT iddbfs FROM " . $cfg['tab']['dbfs'] . " WHERE dirname IN('" . $sDirname . "', '" . conHtmlEntityDecode($sDirname) . "', '" . $sDirname . "') AND filename = '" . $sFilename . "'";
                $db->query($sql);

                if ($db->numRows() == 0) {
                    $aErrors['docimages'][] = array_merge($aSearchIDInfosNonID[$i], array(
                        "error_type" => "dbfs"
                    ));
                }
            } else {

                if (!cFileHandler::exists($frontendPath . $aSearchIDInfosNonID[$i]['url'])) {

                    if (url_is_image($aSearchIDInfosNonID[$i]['url'])) {
                        $aErrors['docimages'][] = array_merge($aSearchIDInfosNonID[$i], array(
                            "error_type" => "unknown"
                        ));
                    } else {
                        $aErrors['others'][] = array_merge($aSearchIDInfosNonID[$i], array(
                            "error_type" => "unknown"
                        ));
                    }
                }
            }
        }
    }

    return $aErrors;
}

// Searchs front_content.php-links
function searchFrontContentLinks($sValue, $iArt, $sArt, $iCat, $sCat) {
    global $aSearchIDInfosArt, $aSearchIDInfosCat, $aSearchIDInfosCatArt, $aWhitelist;

    // detect urls with parameter idart
    $matches = array();
    if (preg_match_all('/(?!file|ftp|http|ww)front_content.php\?idart=([0-9]*)/i', $sValue, $matches)) {
        for ($i = 0; $i < count($matches[0]); $i++) {
            if (!in_array($matches[0][$i], $aWhitelist)) {
                $aSearchIDInfosArt[] = array(
                    "id" => $matches[1][$i],
                    "url" => $matches[0][$i],
                    "idart" => $iArt,
                    "nameart" => $sArt,
                    "idcat" => $iCat,
                    "namecat" => $sCat,
                    "urltype" => "intern"
                );
            }
        }
    }

    // detect urls with parameter idcat
    $matches = array();
    if (preg_match_all('/(?!file|ftp|http|ww)front_content.php\?idcat=([0-9]*)/i', $sValue, $matches)) {
        for ($i = 0; $i < count($matches[0]); $i++) {
            if (!in_array($matches[0][$i], $aWhitelist)) {
                $aSearchIDInfosCat[] = array(
                    "id" => $matches[1][$i],
                    "url" => $matches[0][$i],
                    "idart" => $iArt,
                    "nameart" => $sArt,
                    "idcat" => $iCat,
                    "namecat" => $sCat,
                    "urltype" => "intern"
                );
            }
        }
    }

    // detect urls with parameter idcatart
    $matches = array();
    if (preg_match_all('/(?!file|ftp|http|ww)front_content.php\?idcatart=([0-9]*)/i', $sValue, $matches)) { // idcatart
        for ($i = 0; $i < count($matches[0]); $i++) {
            if (!in_array($matches[0][$i], $aWhitelist)) {
                $aSearchIDInfosCatArt[] = array(
                    "id" => $matches[1][$i],
                    "url" => $matches[0][$i],
                    "idart" => $iArt,
                    "nameart" => $sArt,
                    "idcat" => $iCat,
                    "namecat" => $sCat,
                    "urltype" => "intern"
                );
            }
        }
    }
}

// Searchs extern and intern links
function searchLinks($sValue, $iArt, $sArt, $iCat, $sCat, $iArtLang, $iLang, $sFromtype = "") {
    global $aUrl, $aSearchIDInfosNonID, $aWhitelist;

    // Extern URL
    if (preg_match_all('~(?:(?:action|data|href|src)=["\']((?:file|ftp|http|ww)[^\s]*)["\'])~i', $sValue, $aMatches) && $_GET['mode'] != 1) {

        for ($i = 0; $i < count($aMatches[1]); $i++) {

            if (!in_array($aMatches[1][$i], $aWhitelist)) {
                $aSearchIDInfosNonID[] = array(
                    "url" => $aMatches[1][$i],
                    "idart" => $iArt,
                    "nameart" => $sArt,
                    "idcat" => $iCat,
                    "namecat" => $sCat,
                    "idartlang" => $iArtLang,
                    "lang" => $iLang,
                    "urltype" => "extern"
                );
            }
        }
    }

    // Redirect
    if ($sFromtype == "Redirect" && (preg_match('!(' . preg_quote($aUrl['cms']) . '[^\s]*)!i', $sValue, $aMatches) || (preg_match('~(?:file|ftp|http|ww)[^\s]*~i', $sValue, $aMatches) && $_GET['mode'] != 1)) && (stripos($sValue, 'front_content.php') === false) && !in_array($aMatches[0], $aWhitelist)) {
        $aSearchIDInfosNonID[] = array(
            "url" => $aMatches[0],
            "idart" => $iArt,
            "nameart" => $sArt,
            "idcat" => $iCat,
            "namecat" => $sCat,
            "idartlang" => $iArtLang,
            "lang" => $iLang,
            "urltype" => "unknown",
            "redirect" => true
        );
    }

    // Intern URL
    if (preg_match_all('~(?:(?:action|data|href|src)=["\'])(?!file://)(?!ftp://)(?!http://)(?!https://)(?!ww)(?!mailto)(?!\#)(?!/\#)([^"\']+)(?:["\'])~i', $sValue, $aMatches) && $_GET['mode'] != 2) {

        for ($i = 0; $i < count($aMatches[1]); $i++) {

            if (strpos($aMatches[1][$i], "front_content.php") === false && !in_array($aMatches[1][$i], $aWhitelist)) {
                $aSearchIDInfosNonID[] = array(
                    "url" => $aMatches[1][$i],
                    "idart" => $iArt,
                    "nameart" => $sArt,
                    "idcat" => $iCat,
                    "namecat" => $sCat,
                    "idartlang" => $iArtLang,
                    "lang" => $iLang,
                    "urltype" => "intern"
                );
            }
        }
    }
}
?>