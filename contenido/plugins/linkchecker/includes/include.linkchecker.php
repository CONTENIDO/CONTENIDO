<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Main file for the plugin linkchecker
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
 *   created 2007-08-08
 *   modified 2008-02-08, Andread Lindner, performance enhancements
 *   modified 2008-04-05, Holger Librenz, fixed wrong include-path for
 *                        PEAR cache module
 *   modified 2008-05-14, Frederic Schneider, new version
 *   modified 2008-06-21, Frederic Schneider, array initalization
 *   modified 2008-07-02, Frederic Schneider, add security fix
 *   modified 2008-07-07, Frederic Schneider, fixed wrong language var
 *
 *   $Id: include.linkchecker.php 1853 2012-02-10 14:27:49Z dominik.ziegler $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$plugin_name = "linkchecker";
global $cfg;

if(!$perm->have_perm_area_action($plugin_name, $plugin_name) && $cronjob != true) {
	exit;
}

if((int) $client == 0 && $cronjob != true) {
	$notification->displayNotification("error", i18n("No client selected"));
	exit;
}

// If no mode defined, use mode three
if(empty($_GET['mode'])) {
	$_GET['mode'] = 3;
}

// If no action definied
if(empty($_GET['action'])) {
	$_GET['action'] = 'linkchecker';
	$action = "linkchecker";
}

plugin_include('linkchecker', 'includes/config.plugin.php');
plugin_include('linkchecker', 'includes/include.checkperms.php');
plugin_include('linkchecker', 'includes/include.linkchecker_tests.php');

// Initialization of cache
cInclude('pear', 'PEAR.php');
cInclude('pear', 'Cache/Lite.php');

// Initialization
$actionID = 500;
$aCats = array();
$aSearchIDInfosArt = array();
$aSearchIDInfosCatArt = array();
$aSearchIDInfosNonID = array();
$iWhitelist_timeout = 2592000; // 30 days

// Var initialization
$aUrl = array('cms' => $cfgClient[$client]['path']['htmlpath'], 'contenido' => $cfg['path']['contenido_fullhtml']);

// Template- and languagevars
if($cronjob != true) {
	$tpl->set('s', 'FULLHTML', $aUrl['contenido']);
	$tpl->set('s', 'MODE', intval($_GET['mode']));
	$tpl->set('s', 'URL', $aUrl['contenido']);
	$tpl->set('s', 'SID', $sess->id);
}

// Fill Subnav I
$sLink = $sess->url("main.php?area=linkchecker&frame=4&action=linkchecker") . '&mode=';

// Fill Subnav II
$tpl->set('s', 'INTERNS_HREF', $sLink . '1');
$tpl->set('s', 'INTERNS_LABEL', i18n("Interns"));
$tpl->set('s', 'EXTERNS_HREF', $sLink . '2');
$tpl->set('s', 'EXTERNS_LABEL', i18n("Externs"));
$tpl->set('s', 'INTERNS_EXTERNS_HREF', $sLink . '3');
$tpl->set('s', 'INTERNS_EXTERNS_LABEL', i18n("Intern/extern Links"));

// Fill Subnav III
$tpl->set('s', 'UPDATE_HREF', $sLink . intval($_GET['mode']) . '&live=1');

// Cache options
$aCacheName = array('errors' => $sess->id, 'errorscount' => $aCacheName['errors'] . "ErrorsCountChecked");
$oCache = new Cache_Lite(array('cacheDir' => $cfgClient[$client]['path']['frontend'] . "cache/", 'caching' => true, 'lifeTime' => 1209600, 'automaticCleaningFactor' => 1));

/* *********
Program code
********* */

/* function linksort */
function linksort($sErrors) {

	if($_GET['sort'] == "nameart") {

		foreach($sErrors as $key => $aRow) {
			$aNameart[$key] = $aRow['nameart'];
		}

		array_multisort($sErrors, SORT_ASC, SORT_STRING, $aNameart);

	} elseif($_GET['sort'] == "namecat") {

		foreach($sErrors as $key => $aRow) {
			$aNamecat[$key] = $aRow['namecat'];
		}

		array_multisort($sErrors, SORT_ASC, SORT_STRING, $aNamecat);

	} elseif($_GET['sort'] == "wronglink") {

		foreach($sErrors as $key => $aRow) {
			$aWronglink[$key] = $aRow['url'];
		}

		array_multisort($sErrors, SORT_ASC, SORT_STRING, $aWronglink);

	} elseif($_GET['sort'] == "error_type") {

		foreach($sErrors as $key => $aRow) {
			$aError_type[$key] = $aRow['error_type'];
		}

		array_multisort($sErrors, SORT_ASC, SORT_STRING, $aError_type);

	}

	return $sErrors;

}

// function url_is_image
function url_is_image($sUrl) {

	if(substr($sUrl, -3, 3) == "gif"
	|| substr($sUrl, -3, 3) == "jpg"
	|| substr($sUrl, -4, 4) == "jpeg"
	|| substr($sUrl, -3, 3) == "png"
	|| substr($sUrl, -3, 3) == "tif"
	|| substr($sUrl, -3, 3) == "psd"
	|| substr($sUrl, -3, 3) == "bmp") {
		return true;
	} else {
		return false;
	}

}

// function url_is_uri
function url_is_uri($sUrl) {

	if(substr($sUrl, 0, 4) == "file"
	|| substr($sUrl, 0, 3) == "ftp"
	|| substr($sUrl, 0, 4) == "http"
	|| substr($sUrl, 0, 2) == "ww") {
		return true;
	} else {
		return false;
	}

}

/* Check: Changes after last check? */
$sql = "SELECT lastmodified FROM " . $cfg['tab']['content'] . " content
		LEFT JOIN " . $cfg['tab']['art_lang'] . " art ON (art.idartlang = content.idartlang)
		WHERE art.online = '1'";

/* Whitelist: Add */
if(!empty($_GET['whitelist'])) {
	$sql = "REPLACE INTO " . $cfg['tab']['whitelist'] . " VALUES ('" . Contenido_Security::escapeDB(base64_decode($_GET['whitelist']), $db) . "', '" . time() . "')";
	$db->query($sql);
}

/* Whitelist: Get */
$sql = "SELECT url FROM " . $cfg['tab']['whitelist'] . " WHERE lastview < " . (time() + $iWhitelist_timeout) . "
		AND lastview > " . (time() - $iWhitelist_timeout);
$db->query($sql);

$aWhitelist = array();
while($db->next_record()) {
	$aWhitelist[] = $db->f("url");
}

/* Get all links */
// Cache errors
$sCache_errors = $oCache->get($aCacheName['errors'], intval($_GET['mode']));

// Search if cache doesn't exist or we're in live mode
if($sCache_errors && $_GET['live'] != 1) {
	$aErrors = unserialize($sCache_errors);
} else { // If no cache exists

	// Select all categorys
	$sql = "SELECT idcat FROM " . $cfg['tab']['cat'] . " GROUP BY idcat";
	$db->query($sql);

	while($db->next_record()) {

		if($cronjob != true) { // Check userrights, if no cronjob

			$iCheck = cCatPerm($db->f("idcat"), $db2);

			if($iCheck == true) {
				$aCats[] = Contenido_Security::toInteger($db->f("idcat"));
			}

		} else {
			$aCats[] = Contenido_Security::toInteger($db->f("idcat"));
		}

	}

	// Use SQL-WHERE if lang is not zero
	if($langart != 0) {
		$sLang_where = "AND art.idlang = '" . Contenido_Security::toInteger($langart) . "' AND catName.idlang = '" . Contenido_Security::toInteger($langart) . "'";
	} elseif(!isset($langart)) {
		$sLang_where = "AND art.idlang = '" . Contenido_Security::toInteger($lang) . "' AND catName.idlang = '" . Contenido_Security::toInteger($lang) . "'";
	}

	// How many articles exists? [Text]
	$sql = "SELECT art.title, art.idlang, cat.idart, cat.idcat, catName.name AS namecat, con.value FROM " . $cfg['tab']['cat_art'] . " cat
			LEFT JOIN " . $cfg['tab']['art_lang'] . " art ON (art.idart = cat.idart)
			LEFT JOIN " . $cfg['tab']['cat_lang'] . " catName ON (catName.idcat = cat.idcat)
			LEFT JOIN " . $cfg['tab']['content'] . " con ON (con.idartlang = art.idartlang)
			WHERE (con.value LIKE '%action%' OR con.value LIKE '%data%' OR con.value LIKE '%href%' OR con.value LIKE '%src%')
			AND cat.idcat IN (0, " . join(", ", $aCats) . ") AND cat.idcat != '0' " . $sLang_where . "
			AND art.online = '1' AND art.redirect = '0'";
	$db->query($sql);

	while($db->next_record()) {

		// Text decode
		$value = urldecode($db->f("value"));

		// Search the text
		searchLinks($value, $db->f("idart"), $db->f("title"), $db->f("idcat"), $db->f("namecat"), $db->f("idlang"));

		// Search front_content.php-links
		if($_GET['mode'] != 2) {
			searchFrontContentLinks($value, $db->f("idart"), $db->f("title"), $db->f("idcat"), $db->f("namecat"));
		}

	}

	// How many articles exists? [Redirects]
	$sql = "SELECT art.title, art.redirect_url, art.idlang, cat.idart, cat.idcat, catName.name AS namecat FROM " . $cfg['tab']['cat_art'] . " cat
			LEFT JOIN " . $cfg['tab']['art_lang'] . " art ON (art.idart = cat.idart)
			LEFT JOIN " . $cfg['tab']['cat_lang'] . " catName ON (catName.idcat = cat.idcat)
			WHERE cat.idcat IN (0, " . join(", ", $aCats) . ") AND cat.idcat != '0' " . $sLang_where . "
			AND art.online = '1' AND art.redirect = '1'";
	$db->query($sql);

	while($db->next_record()) {

		// Search links
		searchLinks($db->f("redirect_url"), $db->f("idart"), $db->f("title"), $db->f("idcat"), $db->f("namecat"), $db->f("idlang"), "Redirect");

		// Search front_content.php-links
		if($_GET['mode'] != 2) {
			searchFrontContentLinks($db->f("redirect_url"), $db->f("idart"), $db->f("title"), $db->f("idcat"), $db->f("namecat"));
		}

	}

	// Check the links
	checkLinks();

}

/* Analysis of the errors */
// Templateset
if($cronjob != true) {
	$tpl->set('s', 'TITLE', i18n("Link analysis from ", $plugin_name) . strftime(i18n("%Y-%m-%d", $plugin_name), time()));
}

// If no errors found, say that
if(empty($aErrors) && $cronjob != true) {
	$tpl->set('s', 'NO_ERRORS', i18n("<strong>No errors</strong> were found.", $plugin_name));
	$tpl->generate($cfg['templates']['linkchecker_noerrors']);
} elseif(!empty($aErrors) && $cronjob != true) {

	$tpl->set('s', 'ERRORS_HEADLINE', i18n("Total checked links", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_ARTID', i18n("idart", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_ARTICLE', i18n("Article", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_CATID', i18n("idcat", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_CATNAME', i18n("Category", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_DESCRIPTION', i18n("Description", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_LINK', i18n("Linkerror", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_LINKS_ARTICLES', i18n("Links to articles", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_LINKS_CATEGORYS', i18n("Links to categories", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_LINKS_DOCIMAGES', i18n("Links to documents and images", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_OTHERS', i18n("Links to extern sites and not defined links", $plugin_name));
	$tpl->set('s', 'ERRORS_HEADLINE_WHITELIST', "Whitelist");
	$tpl->set('s', 'ERRORS_HELP_ERRORS', i18n("Wrong links", $plugin_name));

	// error_output initialization
	$aError_output = array('art' => '', 'cat' => '', 'docimages' => '', 'others' => '');

	foreach($aErrors as $sKey => $aRow) {

		$aRow = linksort($aRow);

		for($i = 0; $i < count($aRow); $i++) {

			$tpl2 = new Template;
			$tpl2->reset();

			$tpl2->set('s', 'ERRORS_ERROR_TYPE', $aRow[$i]['error_type']);
			$tpl2->set('s', 'ERRORS_ARTID', $aRow[$i]['idart']);
			$tpl2->set('s', 'ERRORS_ARTICLE', $aRow[$i]['nameart']);
			$tpl2->set('s', 'ERRORS_ARTICLE_SHORT', substr($aRow[$i]['nameart'], 0, 20) . ((strlen($aRow[$i]['nameart']) > 20) ? ' ...' : ''));
			$tpl2->set('s', 'ERRORS_CATID', $aRow[$i]['idcat']);
			$tpl2->set('s', 'ERRORS_LINK', $aRow[$i]['url']);
			$tpl2->set('s', 'ERRORS_LINK_ENCODE', base64_encode($aRow[$i]['url']));
			$tpl2->set('s', 'ERRORS_LINK_SHORT', substr($aRow[$i]['url'], 0, 55) . ((strlen($aRow[$i]['url']) > 55) ? ' ...' : ''));
			$tpl2->set('s', 'ERRORS_CATNAME', $aRow[$i]['namecat']);
			$tpl2->set('s', 'ERRORS_CATNAME_SHORT', substr($aRow[$i]['namecat'], 0, 20) . ((strlen($aRow[$i]['namecat']) > 20) ? ' ...' : ''));
			$tpl2->set('s', 'MODE', $_GET['mode']);
			$tpl2->set('s', 'URL', $aUrl['contenido']);
			$tpl2->set('s', 'SID', $sess->id);

			if($aRow[$i]['error_type'] == "unknown") {
				$tpl2->set('s', 'ERRORS_ERROR_TYPE_HELP', i18n("Unknown: articles, documents etc. do not exist.", $plugin_name));
			} elseif($aRow[$i]['error_type'] == "offline") {
				$tpl2->set('s', 'ERRORS_ERROR_TYPE_HELP', i18n("Offline: article or category is offline.", $plugin_name));
			} elseif($aRow[$i]['error_type'] == "startart") {
				$tpl2->set('s', 'ERRORS_ERROR_TYPE_HELP', i18n("Offline: article or category is offline.", $plugin_name));
			} elseif($aRow[$i]['error_type'] == "dbfs") {
				$tpl2->set('s', 'ERRORS_ERROR_TYPE_HELP', i18n("dbfs: no matches found in the dbfs database.", $plugin_name));
			}

			if($sKey != "cat") {
				$aError_output[$sKey] .= $tpl2->generate($cfg['templates']['linkchecker_test_errors'], 1);
			} else {
				$aError_output[$sKey] .= $tpl2->generate($cfg['templates']['linkchecker_test_errors_cat'], 1); // special template for idcats
			}

		}

	}

	/* Counter */
	if($iCounter = $oCache->get($aCacheName['errorscount'], intval($_GET['mode']))) { // Cache exists?
		$iErrors_count_checked = $iCounter;
	} else { // Count searched links: idarts + idcats + idcatarts + others
		$iErrors_count_checked = count($aSearchIDInfosArt) + count($aSearchIDInfosCat) + count($aSearchIDInfosCatArt) + count($aSearchIDInfosNonID);
	}

	// Count errors
	foreach($aErrors as $sKey => $aRow) {
		$iErrors_counted += count($aErrors[$sKey]);
	}

	$tpl->set('s', 'ERRORS_COUNT_CHECKED', $iErrors_count_checked);
	$tpl->set('s', 'ERRORS_COUNT_ERRORS', $iErrors_counted);
	$tpl->set('s', 'ERRORS_COUNT_ERRORS_PERCENT', round(($iErrors_counted * 100) / $iErrors_count_checked, 2));

	/* Template output */
	foreach($aError_output as $sKey => $sValue) {

		if(empty($aError_output[$sKey])) { // Errors for this type?
			$tpl2->set('s', 'ERRORS_NOTHING', i18n("No errors for this type.", $plugin_name));
			$aError_output[$sKey] = $tpl2->generate($cfg['templates']['linkchecker_test_nothing'], 1);
		}

		$tpl->set('s', 'ERRORS_SHOW_' . strtoupper($sKey), $aError_output[$sKey]);

		if(count($aErrors[$sKey]) > 0) {
			$tpl->set('s', 'ERRORS_COUNT_ERRORS_' . strtoupper($sKey), '<span style="color: #FF0000;">' . count($aErrors[$sKey]) . '</span>');
		} else {
			$tpl->set('s', 'ERRORS_COUNT_ERRORS_' . strtoupper($sKey), count($aErrors[$key]));
		}

	}

	$tpl->generate($cfg['templates']['linkchecker_test']);

	/* Cache */    
	// Remove older cache
	$oCache->remove($aCacheName['errors'], intval($_GET['mode']));
    
	// Build new cache
	$oCache->save(serialize($aErrors), $aCacheName['errors'], intval($_GET['mode']));
	$oCache->save($iErrors_count_checked, $aCacheName['errorscount'], intval($_GET['mode']));

}

// Log
if($cronjob != true) {
	$backend->log(0, 0, $client, $lang, $action);
}
?>