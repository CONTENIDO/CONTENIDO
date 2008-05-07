<?php
/******************************************************************************
Description 	: Linkchecker 2.0.1
Author      	: Frederic Schneider (4fb)
Urls        	: http://www.4fb.de
Create date 	: 2008-02-28
Modified		: Andreas Lindner (4fb), 08.02.2008, Performance enhancements
Modified		: Frederic Schneider (4fb), 06.05.2008, Fix for big pages
*******************************************************************************/

// Checks all links without front_content.php
function checkLinks() {
	global $auth, $cfgClient, $client, $cfg, $cronjob, $db, $errors, $lang, $langart, $whitelist;
	global $searchIDInfosArt, $searchIDInfosCat, $searchIDInfosCatArt, $searchIDInfosNonID;

	if(count($searchIDInfosArt) > 0) { // Checks idarts

		for($i = 0; $i < count($searchIDInfosArt); $i++) {

			if($i == 0) {
				$search = $searchIDInfosArt[$i]['id'];
			} else {
				$search .= ", " . $searchIDInfosArt[$i]['id'];
			}

		}

		// Check articles
		$sql = "SELECT idart, online FROM " . $cfg['tab']['art_lang'] . " WHERE idart IN (" . $search . ")";
		$db->query($sql);

		while($db->next_record()) {
			$find[$db->f("idart")] = array("online" => $db->f("online"));
		}

		for($i = 0; $i < count($searchIDInfosArt); $i++) {

			if(isset($find[$searchIDInfosArt[$i]['id']]) && $find[$searchIDInfosArt[$i]['id']]['online'] == 0) {
				$errors['art'][] = array_merge($searchIDInfosArt[$i], array("error_type" => "offline"));
			} elseif(!isset($find[$searchIDInfosArt[$i]['id']])) {
				$errors['art'][] = array_merge($searchIDInfosArt[$i], array("error_type" => "unknown"));
			}

		}

	}

	if(count($searchIDInfosCat) > 0) { // Checks idcats
    
		for($i = 0; $i < count($searchIDInfosCat); $i++) {

			if($i == 0) {
				$search = $searchIDInfosCat[$i]['id'];
			} else {
				$search .= ", " . $searchIDInfosCat[$i]['id'];
			}

		}

		unset($find);

		// Check categorys
		$sql = "SELECT idcat, startidartlang, visible FROM " . $cfg['tab']['cat_lang'] . " WHERE idcat IN (" . $search . ") AND idlang = '" . $lang . "'";
		$db->query($sql);

		while($db->next_record()) {
			$find[$db->f("idcat")] = array("online" => $db->f("visible"), "startidart" => $db->f("startidartlang"));
		}

		for($i = 0; $i < count($searchIDInfosCat); $i++) {

			if(is_array($find[$searchIDInfosCat[$i]['id']]) && $find[$searchIDInfosCat[$i]['id']]['startidart'] == 0) {
				$errors['cat'][] = array_merge($searchIDInfosCat[$i], array("error_type" => "startart"));
			} elseif(is_array($find[$searchIDInfosCat[$i]['id']]) && $find[$searchIDInfosCat[$i]['id']]['online'] == 0) {
				$errors['cat'][] = array_merge($searchIDInfosCat[$i], array("error_type" => "offline"));
			} elseif(!is_array($find[$searchIDInfosCat[$i]['id']])) {
				$errors['cat'][] = array_merge($searchIDInfosCat[$i], array("error_type" => "unknown"));
			}

			if(is_array($find[$searchIDInfosCat[$i]['id']]) && $find[$searchIDInfosCat[$i]['id']]['startidart'] != 0) {

				$sql = "SELECT idart FROM " . $cfg['tab']['art_lang'] . " WHERE idartlang = '" . $find[$searchIDInfosCat[$i]['id']]['startidart'] . "' AND online = '1'";
				$db->query($sql);

				if($db->num_rows() == 0) {
					$errors['cat'][] = array_merge($searchIDInfosCat[$i], array("error_type" => "startart"));
				}

			}

		}

	}

	if(count($searchIDInfosCatArt) > 0) { // Checks idcatarts

		for($i = 0; $i < count($searchIDInfosCatArt); $i++) {

			if($i == 0) {
				$search = $searchIDInfosCatArt[$i]['id'];
			} else {
				$search .= ", " . $searchIDInfosCatArt[$i]['id'];
			}

		}

		unset($find);

		// Check articles
		$sql = "SELECT idcatart FROM " . $cfg['tab']['cat_art'] . " WHERE idcatart IN (" . $search . ")";
		$db->query($sql);

		while($db->next_record()) {
			$find[] = $db->f("idcatart");
		}

		for($i = 0; $i < count($searchIDInfosCatArt); $i++) {

			if(!in_array($searchIDInfosCatArt[$i]['id'], $find)) {
				$errors['art'][] = array_merge($searchIDInfosCatArt[$i], array("error_type" => "unknown"));
			}

		}

	}

	if(count($searchIDInfosNonID) != 0) { // Checks other links (e. g. http, www, dfbs)

		// Lang-Fix
		if($langart != 0) { // If langart is 0 than get langart (cronjob-special var)
			$lang_insert = ", '" . $langart . "'";
			$lang_where = " WHERE lang = '" . $langart . "'";
		} elseif(!isset($langart)) { // If langart isn't defined than get lang-var
			$lang_insert = ", '" . $lang . "'";
			$lang_where = " WHERE lang = '" . $lang . "'";
		}

		// Select userrights (is the user admin or sysadmin?)
		$sql = "SELECT username FROM " . $cfg['tab']['phplib_auth_user_md5'] . " WHERE user_id='" . $auth->auth["uid"] . "' AND perms LIKE '%admin%'";
		$db->query($sql);

		if($db->num_rows() > 0 || $cronjob == true) { // User is admin when he is or when he run the cronjob
			$admin = true;
		}

		// Clean extern-links database-cache, when mode 2 is active and user has admin-rights
		if($_REQUEST['mode'] == 2 && $admin == true) {

				$sql = "DELETE FROM " . $cfg['tab']['externlinks'] . $lang_where;
				$db->query($sql);

		} elseif($_REQUEST['mode'] == 3) { // When mode is three: Select cached extern links from database

			$sql = "SELECT * FROM " . $cfg['tab']['externlinks'] . $lang_where;
			$db->query($sql);
			
			$db2 = new DB_Contenido();

			while($db->next_record()) {

				$temp_array = array("url" => $db->f("url"), "idart" => $db->f("idart"), "nameart" => $db->f("nameart"),
									"idcat" => $db->f("idcat"),	"namecat" => $db->f("namecat"), "urltype" => $db->f("urltype"),
									"error_type" => "unknown");

				$check = cCatPerm($db->f("idcat"), $db2);
				if($check == true && !in_array($db->f("url"), $whitelist)) {

					if(url_is_image($db->f("url"))) {
						$errors['docimages'][] = $temp_array;
					} else {
						$errors['others'][] = $temp_array;
					}

				}

			}

		}

		for($i = 0; $i < count($searchIDInfosNonID); $i++) {

			if(url_is_uri($searchIDInfosNonID[$i]['url'])) {

				if($_REQUEST['mode'] == 2) { // Live only for mode 2

					if(substr($searchIDInfosNonID[$i]['url'], 0, strlen($searchIDInfosNonID[$i]['url'])) == $cfgClient[$client]['path']['htmlpath']) {
						$ping = @file_exists(str_replace($cfgClient[$client]['path']['htmlpath'], $cfgClient[$client]['path']['frontend'], $searchIDInfosNonID[$i]['url']));
					} else {
						$ping = @fopen($searchIDInfosNonID[$i]['url'], 'r');
					}

					if(!$ping) {

						if(url_is_image($searchIDInfosNonID[$i]['url'])) {
							$errors['docimages'][] = array_merge($searchIDInfosNonID[$i], array("error_type" => "unknown"));
						} else {
							$errors['others'][] = array_merge($searchIDInfosNonID[$i], array("error_type" => "unknown"));
						}

						if($admin == true) {

							if($langart == 0) { // If more than one language is active, get lang-var from searchIDInfosNonID-array
								$lang_insert = ", '" . $searchIDInfosNonID[$i]['lang'] . "'";
							}

							// Write all extern links in the database for caching
							$sql = "INSERT INTO " . $cfg['tab']['externlinks'] . " VALUES ('" . $searchIDInfosNonID[$i]['url'] . "',
									'" . $searchIDInfosNonID[$i]['idart'] . "',	'" . $searchIDInfosNonID[$i]['nameart'] . "',
									'" . $searchIDInfosNonID[$i]['idcat'] . "', '" . $searchIDInfosNonID[$i]['namecat'] . "',
									'" . $searchIDInfosNonID[$i]['urltype'] . "'" . $lang_insert . ")";
							$db->query($sql);

						}

					}

				}

			} elseif(substr($searchIDInfosNonID[$i]['url'], strlen($searchIDInfosNonID[$i]['url'])-5, 5) == ".html") {

				$ping = @file_exists($cfgClient[$client]['path']['htmlpath'] . $searchIDInfosNonID[$i]['url']);

				if(!$ping) {
					$errors['art'][] = array_merge($searchIDInfosNonID[$i], array("error_type" => "unknown"));
				}

			} elseif(substr($searchIDInfosNonID[$i]['url'], 0, 20) == "dbfs.php?file=dbfs:/") {

				$dburl = substr($searchIDInfosNonID[$i]['url'], 20, strlen($searchIDInfosNonID[$i]['url']));

				$pos = strrpos($dburl, '/');
				$dirname = substr($dburl, 0, $pos);
				$filename = substr($dburl, $pos + 1);

				// Check categorys
				$sql = "SELECT iddbfs FROM " . $cfg['tab']['dbfs'] . " WHERE dirname IN('" . $dirname . "', '" . html_entity_decode($dirname) . "', '" . urldecode($dirname) . "') AND filename = '" . $filename . "'";
				$db->query($sql);

				if($db->num_rows() == 0) {
					$errors['docimages'][] = array_merge($searchIDInfosNonID[$i], array("error_type" => "dbfs"));
				}

			} else {

				if(!file_exists($cfgClient[$client]['path']['frontend'] . $searchIDInfosNonID[$i]['url'])) {

					if(url_is_image($searchIDInfosNonID[$i]['url'])) {
						$errors['docimages'][] = array_merge($searchIDInfosNonID[$i], array("error_type" => "unknown"));
					} else {
						$errors['others'][] = array_merge($searchIDInfosNonID[$i], array("error_type" => "unknown"));
					}

				}

			}

		}

	}

	return $errors;

}

// Searchs front_content.php-links
function searchFrontContentLinks($value, $idart, $nameart, $idcat, $namecat) {
	global $searchIDInfosArt, $searchIDInfosCat, $searchIDInfosCatArt, $whitelist;

	if(preg_match_all('/(?!file|ftp|http|ww)front_content.php\?idart=([0-9]*)/i', $value, $matches)) { // idart

		if(count($matches[0]) > 1) {

			for($i = 0; $i < count($matches[0]); $i++) {

				if(!in_array($matches[0][$i], $whitelist)) {
					$searchIDInfosArt[] = array("id" => $matches[1][$i], "url" => $matches[0][$i], "idart" => $idart, "nameart" => $nameart, "idcat" => $idcat, "namecat" => $namecat, "urltype" => "intern");
				}

			}

		} elseif(!in_array($matches[0][0], $whitelist)) {
			$searchIDInfosArt[] = array("id" => $matches[1][0], "url" => $matches[0][0], "idart" => $idart, "nameart" => $nameart, "idcat" => $idcat, "namecat" => $namecat, "urltype" => "intern");
		}

	}

	if(preg_match_all('/(?!file|ftp|http|ww)front_content.php\?idcat=([0-9]*)/i', $value, $matches)) { // idcat

		if(count($matches[0]) > 1) {

			for($i = 0; $i < count($matches[0]); $i++) {

				if(!in_array($matches[0][$i], $whitelist)) {
					$searchIDInfosCat[] = array("id" => $matches[1][$i], "url" => $matches[0][$i], "idart" => $idart, "nameart" => $nameart, "idcat" => $idcat, "namecat" => $namecat, "urltype" => "intern");
				}

			}

		} elseif(!in_array($matches[0][0], $whitelist)) {
			$searchIDInfosCat[] = array("id" => $matches[1][0], "url" => $matches[0][0], "idart" => $idart, "nameart" => $nameart, "idcat" => $idcat, "namecat" => $namecat, "urltype" => "intern");
		}

	}

	if(preg_match_all('/(?!file|ftp|http|ww)front_content.php\?idcatart=([0-9]*)/i', $value, $matches)) { // idcatart

		if(count($matches[0]) > 1) {

			for($i = 0; $i < count($matches[0]); $i++) {

				if(!in_array($matches[0][$i], $whitelist)) {
					$searchIDInfosCatArt[] = array("id" => $matches[1][$i], "url" => $matches[0][$i], "idart" => $idart, "nameart" => $nameart, "idcat" => $idcat, "namecat" => $namecat, "urltype" => "intern");
				}

			}

		} elseif(!in_array($matches[0][0], $whitelist)) {
			$searchIDInfosCatArt[] = array("id" => $matches[1][0], "url" => $matches[0][0], "idart" => $idart, "nameart" => $nameart, "idcat" => $idcat, "namecat" => $namecat, "urltype" => "intern");
		}

	}

}

// Searchs extern and intern links
function searchLinks($value, $idart, $nameart, $idcat, $namecat, $lang, $fromtype = "") {
	global $url, $searchIDInfosNonID, $whitelist;

	// Extern URL
	if(preg_match_all('~(?:(?:action|data|href|src)=["\']((?:file|ftp|http|ww)[^\s]*)["\'])~i', $value, $matches) && $_REQUEST['mode'] != 1) {

		for($i = 0; $i < count($matches[1]); $i++) {

			if(!in_array($matches[1][$i], $whitelist)) {
				$searchIDInfosNonID[] = array("url" => $matches[1][$i], "idart" => $idart, "nameart" => $nameart, "idcat" => $idcat, "namecat" => $namecat, "lang" => $lang, "urltype" => "extern");
			}

		}

	}

	// Redirect
	if($fromtype == "Redirect" && (preg_match('!(' . preg_quote($url['cms']) . '[^\s]*)!i', $value, $matches)
	|| (preg_match('~(?:file|ftp|http|ww)[^\s]*~i', $value, $matches) && $_REQUEST['mode'] != 1))
	&& !eregi("front_content.php", $value)
	&& !in_array($matches[0], $whitelist)) {
		$searchIDInfosNonID[] = array("url" => $matches[0], "idart" => $idart, "nameart" => $nameart, "idcat" => $idcat, "namecat" => $namecat, "lang" => $lang, "urltype" => "unknown");
	}

	// Intern URL
	if(preg_match_all('~(?:(?:action|data|href|src)=["\'])(?!file://)(?!ftp://)(?!http://)(?!https://)(?!ww)(?!mailto)(?!\#)(?!/\#)([^"\']+)(?:["\'])~i', $value, $matches) && $_REQUEST['mode'] != 2) {

		for($i = 0; $i < count($matches[1]); $i++) {

			if(strpos($matches[1][$i], "front_content.php") === false && !in_array($matches[1][$i], $whitelist)) {
				$searchIDInfosNonID[] = array("url" => $matches[1][$i], "idart" => $idart, "nameart" => $nameart, "idcat" => $idcat, "namecat" => $namecat, "lang" => $lang, "urltype" => "intern");
            }

		}

	}

}
?>