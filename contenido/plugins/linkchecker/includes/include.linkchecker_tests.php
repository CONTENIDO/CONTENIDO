<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Some linktests for the Linkchecker
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend plugins
 * @version    2.0.1
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.7
 * 
 * {@internal 
 *   created 2008-02-28
 *   modified 2008-06-05, Frederic Schneider
 *   modified 2008-06-26, Frederic Schneider, add security fix
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
				$sSearch = $aSearchIDInfosArt[$i]['id'];
			} else {
				$sSearch .= ", " . $aSearchIDInfosArt[$i]['id'];
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

		unset($aFind);

		// Check categorys
		$sql = "SELECT idcat, startidartlang, visible FROM " . $cfg['tab']['cat_lang'] . " WHERE idcat IN (" . $sSearch . ") AND idlang = '" . $lang . "'";
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
				$sSearch = $aSearchIDInfosCatArt[$i]['id'];
			} else {
				$sSearch .= ", " . $aSearchIDInfosCatArt[$i]['id'];
			}

		}

		unset($aFind);

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

		// Lang-Fix
		if($langart != 0) { // If langart is 0 than get langart (cronjob-special var)
			$sLang_insert = ", '" . $langart . "'";
			$sLang_where = " WHERE lang = '" . $langart . "'";
		} elseif(!isset($langart)) { // If langart isn't defined than get lang-var
			$sLang_insert = ", '" . $lang . "'";
			$sLang_where = " WHERE lang = '" . $lang . "'";
		}

		// Select userrights (is the user admin or sysadmin?)
		$sql = "SELECT username FROM " . $cfg['tab']['phplib_auth_user_md5'] . " WHERE user_id='" . $auth->auth['uid'] . "' AND perms LIKE '%admin%'";
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

	if(preg_match_all('/(?!file|ftp|http|ww)front_content.php\?idart=([0-9]*)/i', $sValue, $matches)) { // idart

		if(count($matches[0]) > 1) {

			for($i = 0; $i < count($matches[0]); $i++) {

				if(!in_array($aMatches[0][$i], $aWhitelist)) {
					$aSearchIDInfosArt[] = array("id" => $matches[1][$i], "url" => $matches[0][$i], "idart" => $iArt, "nameart" => $sArt, "idcat" => $iCat, "namecat" => $sCat, "urltype" => "intern");
				}

			}

		} elseif(!in_array($matches[0][0], $aWhitelist)) {
			$aSearchIDInfosArt[] = array("id" => $matches[1][0], "url" => $matches[0][0], "idart" => $iArt, "nameart" => $sArt, "idcat" => $iCat, "namecat" => $sCat, "urltype" => "intern");
		}

	}

	if(preg_match_all('/(?!file|ftp|http|ww)front_content.php\?idcat=([0-9]*)/i', $sValue, $aMatches)) { // idcat

		if(count($aMatches[0]) > 1) {

			for($i = 0; $i < count($aMatches[0]); $i++) {

				if(!in_array($aMatches[0][$i], $aWhitelist)) {
					$aSearchIDInfosCat[] = array("id" => $aMatches[1][$i], "url" => $matches[0][$i], "idart" => $iArt, "nameart" => $sArt, "idcat" => $iCat, "namecat" => $sCat, "urltype" => "intern");
				}

			}

		} elseif(!in_array($aMatches[0][0], $aWhitelist)) {
			$aSearchIDInfosCat[] = array("id" => $aMatches[1][0], "url" => $aMatches[0][0], "idart" => $iArt, "nameart" => $sArt, "idcat" => $iCat, "namecat" => $sCat, "urltype" => "intern");
		}

	}

	if(preg_match_all('/(?!file|ftp|http|ww)front_content.php\?idcatart=([0-9]*)/i', $sValue, $aMatches)) { // idcatart

		if(count($aMatches[0]) > 1) {

			for($i = 0; $i < count($aMatches[0]); $i++) {

				if(!in_array($aMatches[0][$i], $aWhitelist)) {
					$aSearchIDInfosCatArt[] = array("id" => $aMatches[1][$i], "url" => $aMatches[0][$i], "idart" => $iArt, "nameart" => $sArt, "idcat" => $iCat, "namecat" => $sCat, "urltype" => "intern");
				}

			}

		} elseif(!in_array($aMatches[0][0], $aWhitelist)) {
			$aSearchIDInfosCatArt[] = array("id" => $aMatches[1][0], "url" => $aMatches[0][0], "idart" => $iArt, "nameart" => $sArt, "idcat" => $iCat, "namecat" => $sCat, "urltype" => "intern");
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
	&& !eregi("front_content.php", $sValue)
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