<?php
/******************************************************************************
Description 	: Linkchecker 2.0.0
Author      	: Frederic Schneider (4fb)
Urls        	: http://www.4fb.de
Create date 	: 2007-08-08
Modified		: Andreas Lindner (4fb), 08.02.2008, Performance enhancements  
*******************************************************************************/

$plugin_name = "linkchecker";
global $cfg;

if(!$perm->have_perm_area_action($plugin_name, $plugin_name)) {
	exit;
}

// If no mode defined
if(empty($_REQUEST['mode'])) {
    $_REQUEST['mode'] = 3;
}
include_once($cfg['path']['contenido'].$cfg['path']['plugins'].'linkchecker/includes/config.plugin.php');
require_once($cfg['plugins']['linkchecker'] . "includes/include.checkperms.php");
require_once($cfg['plugins']['linkchecker'] . "includes/include.linkchecker_tests.php");

// Var initialization
$url = array('cms' => $cfgClient[$client]['path']['htmlpath'], 'contenido' => $cfg['path']['contenido_fullhtml']);

// Initialization of cache
require_once($cfg['path']['pear'] . "PEAR.php");
require_once($cfg['path']['pear'] . "CACHE/Lite.php");

$cacheName = array('errors' => $sess->id, 'errorscount' => $cacheName['errors'] . "ErrorsCountChecked");
$cache = new Cache_Lite(array('cacheDir' => $cfgClient[$client]['path']['frontend'] . "cache/", 'caching' => true, 'lifeTime' => 60, 'automaticCleaningFactor' => 1));

// Initialization
$actionID = 500;
$area = "linkchecker";
$whitelist_timeout = 2592000; // 30 days

// Template- and languagevars
if($cronjob != true) {
	$tpl->set('s', 'AREA', 'lc_whitelist');
	$tpl->set('s', 'FULLHTML', $url['contenido']);
	$tpl->set('s', 'MODE', $_REQUEST['mode']);
	$tpl->set('s', 'URL', $url['contenido']);
	$tpl->set('s', 'SID', $sess->id);
}

//Fill Subnav

$sLink = $sess->url("main.php?area=linkchecker&frame=4&action=linkchecker").'&mode=';
//Fill Subnav
$tpl->set('s', 'INTERNS_HREF', $sLink.'1');
$tpl->set('s', 'INTERNS_LABEL', i18n("Interns"));
$tpl->set('s', 'EXTERNS_HREF', $sLink.'2');
$tpl->set('s', 'EXTERNS_LABEL', i18n("Externs"));
$tpl->set('s', 'INTERNS_EXTERNS_HREF', $sLink.'3');
$tpl->set('s', 'INTERNS_EXTERNS_LABEL', i18n("Intern/extern Links"));

/* *********
Program code
********* */

/* function linksort */
function linksort($errors) {

	if($_GET['sort'] == "nameart") {

		foreach($errors as $key => $row) {
			$nameart[$key] = $row['nameart'];
		}

		array_multisort($errors, SORT_ASC, SORT_STRING, $nameart);

	}  elseif($_GET['sort'] == "namecat") {

		foreach($errors as $key => $row) {
			$namecat[$key] = $row['namecat'];
		}

		array_multisort($errors, SORT_ASC, SORT_STRING, $namecat);

	}  elseif($_GET['sort'] == "wronglink") {

		foreach($errors as $key => $row) {
			$wronglink[$key] = $row['url'];
		}

		array_multisort($errors, SORT_ASC, SORT_STRING, $wronglink);

	} elseif($_GET['sort'] == "error_type") {

		foreach($errors as $key => $row) {
			$error_type[$key] = $row['error_type'];
		}

		array_multisort($errors, SORT_ASC, SORT_STRING, $error_type);

	}

	return $errors;

}

// function url_is_image
function url_is_image($url) {

	if(substr($url, -3, 3) == "gif"
	|| substr($url, -3, 3) == "jpg"
	|| substr($url, -4, 4) == "jpeg"
	|| substr($url, -3, 3) == "png"
	|| substr($url, -3, 3) == "tif"
	|| substr($url, -3, 3) == "psd"
	|| substr($url, -3, 3) == "bmp") {
		return true;
	} else {
		return false;
	}

}

// function url_is_uri
function url_is_uri($url) {

	if(substr($url, 0, 4) == "file"
	|| substr($url, 0, 3) == "ftp"
	|| substr($url, 0, 4) == "http"
	|| substr($url, 0, 2) == "ww") {
		return true;
	} else {
		return false;
	}

}

/* Whitelist: Add */
if(!empty($_GET['whitelist'])) {
	$sql = "INSERT INTO " . $cfg['tab']['whitelist'] . " VALUES ('" . base64_decode($_GET['whitelist']) . "', '" . time() . "')";
	$db->query($sql);
}

/* Whitelist: Get */
$sql = "SELECT url FROM " . $cfg['tab']['whitelist'] . " WHERE lastview < " . (time() + $whitelist_timeout) . "
		AND lastview > " . (time() - $whitelist_timeout);
$db->query($sql);

$whitelist = array();
while($db->next_record()) {
	$whitelist[] = $db->f("url");
}

/* Get all links */
if($errors = $cache->get($cacheName['errors'], intval($_REQUEST['mode']))) {  // If cache exists
	$errors = unserialize($errors);
} else { // If no cache exists

	// Select all categorys
	$sql = "SELECT idcat FROM " . $cfg['tab']['cat'] . " GROUP BY idcat";
	$db->query($sql);
    
    $db2 = new DB_Contenido();
    
	while($db->next_record()) {

		if($cronjob != true) { // Check userrights, if no cronjob

			$check = cCatPerm($db->f("idcat"), $db2);
			
			if($check == true) {
				$cats[] = $db->f("idcat");
			}

		} else {
			$cats[] = $db->f("idcat");
		}

	}

	// Where, if lang not 0
	if($langart != 0) {
		$lang_where = "AND art.idlang = '" . $langart . "' AND catName.idlang = '" . $langart . "'";
	} elseif(!isset($langart)) {
		$lang_where = "AND art.idlang = '" . $lang . "' AND catName.idlang = '" . $lang . "'";
	}

	// How many articles exists? [Text]
	$sql = "SELECT art.title, art.idlang, cat.idart, cat.idcat, catName.name AS namecat, con.value FROM " . $cfg['tab']['cat_art'] . " cat
			LEFT JOIN " . $cfg['tab']['art_lang'] . " art ON (art.idart = cat.idart)
			LEFT JOIN " . $cfg['tab']['cat_lang'] . " catName ON (catName.idcat = cat.idcat)
			LEFT JOIN " . $cfg['tab']['content'] . " con ON (con.idartlang = art.idartlang)
			WHERE (con.value LIKE '%action%' OR con.value LIKE '%data%' OR con.value LIKE '%href%' OR con.value LIKE '%src%')
			AND cat.idcat IN (0, " . join(", ", $cats) . ") AND cat.idcat != '0' " . $lang_where . " 
			AND art.online = '1' AND art.redirect = '0'";
	$db->query($sql);

	while($db->next_record()) {

		// Text decode
		$value = urldecode($db->f("value"));

		// Search the text
		searchLinks($value, $db->f("idart"), $db->f("title"), $db->f("idcat"), $db->f("namecat"), $db->f("idlang"));

		// Search front_content.php-links
		if($_REQUEST['mode'] != 2) {
			searchFrontContentLinks($value, $db->f("idart"), $db->f("title"), $db->f("idcat"), $db->f("namecat"));
		}

	}

	// How many articles exists? [Redirects]
	$sql = "SELECT art.title, art.redirect_url, art.idlang, cat.idart, cat.idcat, catName.name AS namecat FROM " . $cfg['tab']['cat_art'] . " cat
			LEFT JOIN " . $cfg['tab']['art_lang'] . " art ON (art.idart = cat.idart)
			LEFT JOIN " . $cfg['tab']['cat_lang'] . " catName ON (catName.idcat = cat.idcat)
			WHERE cat.idcat IN (0, " . join(", ", $cats) . ") AND cat.idcat != '0' " . $lang_where . " 
			AND art.online = '1' AND art.redirect = '1'";
	$db->query($sql);

	while($db->next_record()) {

		// Search links
		searchLinks($db->f("redirect_url"), $db->f("idart"), $db->f("title"), $db->f("idcat"), $db->f("namecat"), $db->f("idlang"), "Redirect");

		// Search front_content.php-links
		if($_REQUEST['mode'] != 2) {
			searchFrontContentLinks($db->f("redirect_url"), $db->f("idart"), $db->f("title"), $db->f("idcat"), $db->f("namecat"));
		}

	}

	// Check the links
	checkLinks();

}

/* Analysis of the errors */
// Templateset
if($cronjob != true) {
	$tpl->set('s', 'TITLE', i18n('Link analysis from ', $plugin_name) . strftime(i18n('%Y-%m-%d', $plugin_name), time()));
}

// If no errors found, say that
if(empty($errors)) {

	if($cronjob != true) {
		$tpl->set('s', 'NO_ERRORS', i18n("<strong>No errors</strong> were found.", $plugin_name));
		$tpl->generate($cfg['templates']['linkchecker_noerrors']);
	}

} elseif(!empty($errors) && $cronjob != true) {

	$tpl->set('s', 'ERRORS_HEADLINE', i18n("Total checked links", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_ARTID', i18n("idart", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_ARTICLE', i18n("Article", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_CATID', i18n("idcat", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_CATNAME', i18n("Category", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_DESCRIPTION', i18n("Description", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_LINK', i18n("Linkerror", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_LINKS_ARTICLES', i18n("Links to articles", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_LINKS_CATEGORYS', i18n("Links to categorys", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_LINKS_DOCIMAGES', i18n("Links to documents and images", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_OTHERS', i18n("Links to extern sites and not defined links", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_WHITELIST', "Whitelist");
	$tpl->set('s', 'ERRORS_HELP_ERRORS', i18n("Wrong links", $plugin_name));

	// error_output initialization
	$error_output = array("art" => "", "cat" => "", "docimages" => "", "others" => "");

	foreach($errors as $key => $row) {

		$row = linksort($row);

		for($i = 0; $i < count($row); $i++) {

			$tpl2 = new Template;
			$tpl2->reset();

			$tpl2->set('s', 'ERRORS_ERROR_TYPE', $row[$i]['error_type']);
			$tpl2->set('s', 'ERRORS_ARTID', $row[$i]['idart']);
			$tpl2->set('s', 'ERRORS_ARTICLE', $row[$i]['nameart']);
			$tpl2->set('s', 'ERRORS_ARTICLE_SHORT', substr($row[$i]['nameart'], 0, 20) . ((strlen($row[$i]['nameart']) > 20) ? ' ...' : ''));
			$tpl2->set('s', 'ERRORS_CATID', $row[$i]['idcat']);
			$tpl2->set('s', 'ERRORS_LINK', $row[$i]['url']);
			$tpl2->set('s', 'ERRORS_LINK_ENCODE', base64_encode($row[$i]['url']));
			$tpl2->set('s', 'ERRORS_LINK_SHORT', substr($row[$i]['url'], 0, 55) . ((strlen($row[$i]['url']) > 55) ? ' ...' : ''));
			$tpl2->set('s', 'ERRORS_CATNAME', $row[$i]['namecat']);
			$tpl2->set('s', 'ERRORS_CATNAME_SHORT', substr($row[$i]['namecat'], 0, 20) . ((strlen($row[$i]['namecat']) > 20) ? ' ...' : ''));
			$tpl2->set('s', 'MODE', $_REQUEST['mode']);
			$tpl2->set('s', 'URL', $url['contenido']);
			$tpl2->set('s', 'SID', $sess->id);

			if($row[$i]['error_type'] == "unknown") {
				$tpl2->set('s', 'ERRORS_ERROR_TYPE_HELP', i18n("Unknown: articles, documents etc. do not exist.", $plugin_name));
			} elseif($row[$i]['error_type'] == "offline") {
				$tpl2->set('s', 'ERRORS_ERROR_TYPE_HELP', i18n("Offline: article or category is offline.", $plugin_name));
			} elseif($row[$i]['error_type'] == "startart") {
				$tpl2->set('s', 'ERRORS_ERROR_TYPE_HELP', i18n("Offline: article or category is offline.", $plugin_name));
			} elseif($row[$i]['error_type'] == "dbfs") {
				$tpl2->set('s', 'ERRORS_ERROR_TYPE_HELP', i18n("dbfs: no matches found in the dbfs database.", $plugin_name));
			}

			if($key != "cat") {
				$error_output[$key] .= $tpl2->generate($cfg['templates']['linkchecker_test_errors'], 1);
			} else {
				$error_output[$key] .= $tpl2->generate($cfg['templates']['linkchecker_test_errors_cat'], 1); // special template for idcats
			}

		}

	}

	/* Counter */
	if($counter = $cache->get($cacheName['errorscount'], intval($_REQUEST['mode']))) { // Cache exists?
		$errors_count_checked = $counter;
	} else { // Count searched links: idarts + idcats + idcatarts + others
		$errors_count_checked = count($searchIDInfosArt) + count($searchIDInfosCat) + count($searchIDInfosCatArt) + count($searchIDInfosNonID);
	}

	// Count errors
	foreach($errors as $key => $row) {
		$errors_counted += count($errors[$key]);
	}

	$tpl->set('s', 'ERRORS_COUNT_CHECKED', $errors_count_checked);
	$tpl->set('s', 'ERRORS_COUNT_ERRORS', $errors_counted);
	$tpl->set('s', 'ERRORS_COUNT_ERRORS_PERCENT', round(($errors_counted * 100) / $errors_count_checked, 2));

	/* Template output */
	foreach($error_output as $key => $value) {

		if(empty($error_output[$key])) { // Errors for this type?
			$tpl2->set('s', 'ERRORS_NOTHING', i18n("No errors for this type.", $plugin_name));
			$error_output[$key] = $tpl2->generate($cfg['templates']['linkchecker_test_nothing'], 1);
		}

		$tpl->set('s', 'ERRORS_SHOW_' . strtoupper($key), $error_output[$key]);

		if(count($errors[$key]) > 0) {
			$tpl->set('s', 'ERRORS_COUNT_ERRORS_' . strtoupper($key), '<span style="color: #FF0000;">' . count($errors[$key]) . '</span>');
		} else {
			$tpl->set('s', 'ERRORS_COUNT_ERRORS_' . strtoupper($key), count($errors[$key]));
		}

	}

	$tpl->generate($cfg['templates']['linkchecker_test']);

	/* Cache */
	$cache->save(serialize($errors), $cacheName['errors'], intval($_REQUEST['mode']));
	$cache->save($errors_count_checked, $cacheName['errorscount'], intval($_REQUEST['mode']));

}

// Log
if($cronjob != true) {
	$backend->log(0, 0, $client, $lang, $action);
}
?>
