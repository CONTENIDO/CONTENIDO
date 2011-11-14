<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Some linktests for the Linkchecker
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend Plugins
 * @version    2.0.2
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.8.7
 * 
 * {@internal 
 *   created 2008-02-28
 *   modified 2008-06-05, Frederic Schneider
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *   modified 2009-11-06, Murat Purc, replaced deprecated functions (PHP 5.3 ready)
 *   modified 2010-01-07, Murat Purc, fixed usage of wrong variable, see [#CON-292]
 *   modified 2010-11-26, Dominik Ziegler, resetten array with redefinition of empty array instead of unsetting the variable [#CON-369]
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

// Checks all links without front_content.php
function checkLinks() {
	global $auth, $cfgClient, $client, $cfg, $cronjob, $db, $aErrors, $lang, $langart, $whitelist;
	global $aSearchIDInfosArt, $aSearchIDInfosCat, $aSearchIDInfosCatArt, $aSearchIDInfosNonID;

	if(count($aSearchIDInfosArt) > 0) { // Checks idarts

		for($i = 0; $i < count($aSearchIDInfosArt); $i++) {

			if($i == 0) {
				$sSearch = Contenido_Security::toInteger($aSearchIDInfosArt[$i]['id']);
			} else {
				$sSearch .= ", " . Contenido_Security::toInteger($aSearchIDInfosArt[$i]['id']);
			}

		}

		// Check articles
		$sql = "SELECT idart, online FROM " . $cfg['tab']['art_lang'] . " WHERE idart IN (" . $sSearch . ")";
		$db->query($sql);

		while($db->next_record()) {
			$aFind[$db->f("idart")] = array("online" => $db->f("online"));
		}

		for($i = 0; $i < count($aSearchIDInfosArt); $i++) {

			if(isset($aFind[$aSearchIDInfosArt[$i]['id']]) && $aFind[$aSearchIDInfosArt[$i]['id']]['online'] == 0) {
				$aErrors['art'][] = array_merge($aSearchIDInfosArt[$i], array("error_type" => "offline"));
			} elseif(!isset($aFind[$aSearchIDInfosArt[$i]['id']])) {
				$aErrors['art'][] = array_merge($aSearchIDInfosArt[$i], array("error_type" => "unknown"));
			}

		}

	}

	if(count($aSearchIDInfosCat) > 0) { // Checks idcats
    
		for($i = 0; $i < count($aSearchIDInfosCat); $i++) {

			if($i == 0) {
				$sSearch = $aSearchIDInfosCat[$i]['id'];
			} else {
				$sSearch .= ", " . $aSearchIDInfosCat[$i]['id'];
			}

		}

		$aFind = array();

		// Check categorys
		$sql = "SELECT idcat, startidartlang, visible FROM " . $cfg['tab']['cat_lang'] . " WHERE idcat IN (" . $sSearch . ") AND idlang = '" . Contenido_Security::toInteger($lang) . "'";
		$db->query($sql);

		while($db->next_record()) {
			$aFind[$db->f("idcat")] = array("online" => $db->f("visible"), "startidart" => $db->f("startidartlang"));
		}

		for($i = 0; $i < count($aSearchIDInfosCat); $i++) {

			if(is_array($aFind[$aSearchIDInfosCat[$i]['id']]) && $aFind[$aSearchIDInfosCat[$i]['id']]['startidart'] == 0) {
				$aErrors['cat'][] = array_merge($aSearchIDInfosCat[$i], array("error_type" => "startart"));
			} elseif(is_array($aFind[$aSearchIDInfosCat[$i]['id']]) && $aFind[$aSearchIDInfosCat[$i]['id']]['online'] == 0) {
				$aErrors['cat'][] = array_merge($aSearchIDInfosCat[$i], array("error_type" => "offline"));
			} elseif(!is_array($aFind[$aSearchIDInfosCat[$i]['id']])) {
				$aErrors['cat'][] = array_merge($aSearchIDInfosCat[$i], array("error_type" => "unknown"));
			}

			if(is_array($aFind[$aSearchIDInfosCat[$i]['id']]) && $aFind[$aSearchIDInfosCat[$i]['id']]['startidart'] != 0) {

				$sql = "SELECT idart FROM " . $cfg['tab']['art_lang'] . " WHERE idartlang = '" . $aFind[$aSearchIDInfosCat[$i]['id']]['startidart'] . "' AND online = '1'";
				$db->query($sql);

				if($db->num_rows() == 0) {
					$aErrors['cat'][] = array_merge($aSearchIDInfosCat[$i], array("error_type" => "startart"));
				}

			}

		}

	}

	if(count($aSearchIDInfosCatArt) > 0) { // Checks idcatarts

		for($i = 0; $i < count($aSearchIDInfosCatArt); $i++) {

			if($i == 0) {
				$sSearch = Contenido_Security::toInteger($aSearchIDInfosCatArt[$i]['id']);
			} else {
				$sSearch .= ", " . Contenido_Security::toInteger($aSearchIDInfosCatArt[$i]['id']);
			}

		}

		$aFind = array();

		// Check articles
		$sql = "SELECT idcatart FROM " . $cfg['tab']['cat_art'] . " WHERE idcatart IN (" . $sSearch . ")";
		$db->query($sql);

		while($db->next_record()) {
			$aFind[] = $db->f("idcatart");
		}

		for($i = 0; $i < count($aSearchIDInfosCatArt); $i++) {

			if(!in_array($aSearchIDInfosCatArt[$i]['id'], $aFind)) {
				$aErrors['art'][] = array_merge($aSearchIDInfosCatArt[$i], array("error_type" => "unknown"));
			}

		}

	}

	if(count($aSearchIDInfosNonID) != 0) { // Checks other links (e. g. http, www, dfbs)

		// Select userrights (is the user admin or sysadmin?)
		$sql = "SELECT username FROM " . $cfg['tab']['phplib_auth_user_md5'] . " WHERE user_id='" . Contenido_Security::escapeDB($auth->auth['uid'], $db) . "' AND perms LIKE '%admin%'";
		$db->query($sql);

		if($db->num_rows() > 0 || $cronjob == true) { // User is admin when he is or when he run the cronjob
			$iAdmin = true;
		}

		for($i = 0; $i < count($aSearchIDInfosNonID); $i++) {

			if(url_is_uri($aSearchIDInfosNonID[$i]['url'])) {

				if(substr($aSearchIDInfosNonID[$i]['url'], 0, strlen($aSearchIDInfosNonID[$i]['url'])) == $cfgClient[$client]['path']['htmlpath']) {
					$iPing = @file_exists(str_replace($cfgClient[$client]['path']['htmlpath'], $cfgClient[$client]['path']['frontend'], $aSearchIDInfosNonID[$i]['url']));
				} else {
					$iPing = @fopen($aSearchIDInfosNonID[$i]['url'], 'r');
				}

				if(!$iPing) {

					if(url_is_image($aSearchIDInfosNonID[$i]['url'])) {
						$aErrors['docimages'][] = array_merge($aSearchIDInfosNonID[$i], array("error_type" => "unknown"));
					} else {
						$aErrors['others'][] = array_merge($aSearchIDInfosNonID[$i], array("error_type" => "unknown"));
					}

				}

			} elseif(substr($aSearchIDInfosNonID[$i]['url'], strlen($aSearchIDInfosNonID[$i]['url'])-5, 5) == ".html") {

				$iPing = @file_exists($cfgClient[$client]['path']['htmlpath'] . $aSearchIDInfosNonID[$i]['url']);

				if(!$iPing) {
					$aErrors['art'][] = array_merge($aSearchIDInfosNonID[$i], array("error_type" => "unknown"));
				}

			} elseif(substr($aSearchIDInfosNonID[$i]['url'], 0, 20) == "dbfs.php?file=dbfs:/") {

				$sDBurl = substr($aSearchIDInfosNonID[$i]['url'], 20, strlen($aSearchIDInfosNonID[$i]['url']));

				$iPos = strrpos($sDBurl, '/');
				$sDirname = substr($sDBurl, 0, $iPos);
				$sFilename = substr($sDBurl, $iPos + 1);

				// Check categorys
				$sql = "SELECT iddbfs FROM " . $cfg['tab']['dbfs'] . " WHERE dirname IN('" . $sDirname . "', '" . html_entity_decode($sDirname) . "', '" . urldecode($sDirname) . "') AND filename = '" . $sFilename . "'";
				$db->query($sql);

				if($db->num_rows() == 0) {
					$aErrors['docimages'][] = array_merge($aSearchIDInfosNonID[$i], array("error_type" => "dbfs"));
				}

			} else {

				if(!file_exists($cfgClient[$client]['path']['frontend'] . $aSearchIDInfosNonID[$i]['url'])) {

					if(url_is_image($aSearchIDInfosNonID[$i]['url'])) {
						$aErrors['docimages'][] = array_merge($aSearchIDInfosNonID[$i], array("error_type" => "unknown"));
					} else {
						$aErrors['others'][] = array_merge($aSearchIDInfosNonID[$i], array("error_type" => "unknown"));
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
                    "id" => $matches[1][$i], "url" => $matches[0][$i], "idart" => $iArt, "nameart" => $sArt, "idcat" => $iCat, "namecat" => $sCat, "urltype" => "intern"
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
                    "id" => $matches[1][$i], "url" => $matches[0][$i], "idart" => $iArt, "nameart" => $sArt, "idcat" => $iCat, "namecat" => $sCat, "urltype" => "intern"
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
                    "id" => $matches[1][$i], "url" => $matches[0][$i], "idart" => $iArt, "nameart" => $sArt, "idcat" => $iCat, "namecat" => $sCat, "urltype" => "intern"
                );
            }
        }
	}
}

// Searchs extern and intern links
function searchLinks($sValue, $iArt, $sArt, $iCat, $sCat, $iLang, $sFromtype = "") {
	global $aUrl, $aSearchIDInfosNonID, $aWhitelist;

	// Extern URL
	if(preg_match_all('~(?:(?:action|data|href|src)=["\']((?:file|ftp|http|ww)[^\s]*)["\'])~i', $sValue, $aMatches) && $_GET['mode'] != 1) {

		for($i = 0; $i < count($aMatches[1]); $i++) {

			if(!in_array($aMatches[1][$i], $aWhitelist)) {
				$aSearchIDInfosNonID[] = array("url" => $aMatches[1][$i], "idart" => $iArt, "nameart" => $sArt, "idcat" => $iCat, "namecat" => $sCat, "lang" => $iLang, "urltype" => "extern");
			}

		}

	}

	// Redirect
	if($sFromtype == "Redirect" && (preg_match('!(' . preg_quote($aUrl['cms']) . '[^\s]*)!i', $sValue, $aMatches)
	|| (preg_match('~(?:file|ftp|http|ww)[^\s]*~i', $sValue, $aMatches) && $_GET['mode'] != 1))
	&& (stripos($sValue, 'front_content.php') === false)
	&& !in_array($aMatches[0], $aWhitelist)) {
		$aSearchIDInfosNonID[] = array("url" => $aMatches[0], "idart" => $iArt, "nameart" => $sArt, "idcat" => $iCat, "namecat" => $sCat, "lang" => $iLang, "urltype" => "unknown");
	}

	// Intern URL
	if(preg_match_all('~(?:(?:action|data|href|src)=["\'])(?!file://)(?!ftp://)(?!http://)(?!https://)(?!ww)(?!mailto)(?!\#)(?!/\#)([^"\']+)(?:["\'])~i', $sValue, $aMatches) && $_GET['mode'] != 2) {

		for($i = 0; $i < count($aMatches[1]); $i++) {

			if(strpos($aMatches[1][$i], "front_content.php") === false && !in_array($aMatches[1][$i], $aWhitelist)) {
				$aSearchIDInfosNonID[] = array("url" => $aMatches[1][$i], "idart" => $iArt, "nameart" => $sArt, "idcat" => $iCat, "namecat" => $sCat, "lang" => $iLang, "urltype" => "intern");
			}

		}

	}

}
?>