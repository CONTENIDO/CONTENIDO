<?php
/**
 * This is the main backend page for the linkchecker plugin.
 *
 * @package Plugin
 * @subpackage Linkchecker
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$plugin_name = "linkchecker";
$cfg = cRegistry::getConfig();

if (!$cronjob) {

    // Check permissions for linkchecker action
    if (!$perm->have_perm_area_action($plugin_name, "linkchecker") && $cronjob != true) {
        cRegistry::addErrorMessage(i18n("No permissions"));
        $page = new cGuiPage('generic_page');
        $page->abortRendering();
        $page->render();
        exit();
    }

    if (cRegistry::getClientId() == 0 && $cronjob != true) {
        $notification->displayNotification("error", i18n("No Client selected"));
        exit();
    }
}

// If no mode defined, use mode three
if (empty($_GET['mode'])) {
    $_GET['mode'] = 3;
}

// If no action definied
if (empty($_GET['action'])) {
    $_GET['action'] = 'linkchecker';
    $action = "linkchecker";
}

plugin_include('linkchecker', 'includes/config.plugin.php');
plugin_include('linkchecker', 'includes/include.checkperms.php');
plugin_include('linkchecker', 'includes/include.linkchecker_tests.php');

// Initialization
$aCats = array();
$aSearchIDInfosArt = array();
$aSearchIDInfosCatArt = array();
$aSearchIDInfosNonID = array();

// Var initialization
$aUrl = array(
    'cms' => cRegistry::getFrontendUrl(),
    'contenido' => cRegistry::getBackendUrl()
);

// Template- and languagevars
if ($cronjob != true) {
    $tpl->set('s', 'MODE', cSecurity::toInteger($_GET['mode']));
}

// Fill Subnav I
if (!$cronjob) {
    $sLink = $sess->url("main.php?area=linkchecker&frame=4&action=linkchecker") . '&mode=';

    // Fill Subnav II
    $tpl->set('s', 'INTERNS_HREF', $sLink . '1');
    $tpl->set('s', 'INTERNS_LABEL', i18n("Interns", $plugin_name));
    $tpl->set('s', 'EXTERNS_HREF', $sLink . '2');
    $tpl->set('s', 'EXTERNS_LABEL', i18n("Externs", $plugin_name));
    $tpl->set('s', 'INTERNS_EXTERNS_HREF', $sLink . '3');
    $tpl->set('s', 'INTERNS_EXTERNS_LABEL', i18n("Intern/extern Links", $plugin_name));

    // Fill Subnav III
    $tpl->set('s', 'UPDATE_HREF', $sLink . cSecurity::toInteger($_GET['mode']) . '&live=1');
}

// Cache options
$aCacheName = array(
    'errors' => $sess->id,
    'errorscount' => $aCacheName['errors'] . "ErrorsCountChecked"
);
$oCache = new cFileCache(array(
    'cacheDir' => $cfgClient[$client]['cache']['path'],
    'lifeTime' => $iCacheLifeTime
));

/*
 * ******** Program code ********
 */

// function linksort
function linksort($sErrors) {
    if ($_GET['sort'] == "nameart") {

        foreach ($sErrors as $key => $aRow) {
            $aNameart[$key] = $aRow['nameart'];
        }

        array_multisort($sErrors, SORT_ASC, SORT_STRING, $aNameart);
    } elseif ($_GET['sort'] == "namecat") {

        foreach ($sErrors as $key => $aRow) {
            $aNamecat[$key] = $aRow['namecat'];
        }

        array_multisort($sErrors, SORT_ASC, SORT_STRING, $aNamecat);
    } elseif ($_GET['sort'] == "wronglink") {

        foreach ($sErrors as $key => $aRow) {
            $aWronglink[$key] = $aRow['url'];
        }

        array_multisort($sErrors, SORT_ASC, SORT_STRING, $aWronglink);
    } elseif ($_GET['sort'] == "error_type") {

        foreach ($sErrors as $key => $aRow) {
            $aError_type[$key] = $aRow['error_type'];
        }

        array_multisort($sErrors, SORT_ASC, SORT_STRING, $aError_type);
    }

    return $sErrors;
}

// function url_is_image
function url_is_image($sUrl) {
    if (cString::getPartOfString($sUrl, -3, 3) == "gif" || cString::getPartOfString($sUrl, -3, 3) == "jpg" || cString::getPartOfString($sUrl, -4, 4) == "jpeg" || cString::getPartOfString($sUrl, -3, 3) == "png" || cString::getPartOfString($sUrl, -3, 3) == "tif" || cString::getPartOfString($sUrl, -3, 3) == "psd" || cString::getPartOfString($sUrl, -3, 3) == "bmp") {
        return true;
    } else {
        return false;
    }
}

// function url_is_uri
function url_is_uri($sUrl) {
    if (cString::getPartOfString($sUrl, 0, 4) == "file" || cString::getPartOfString($sUrl, 0, 3) == "ftp" || cString::getPartOfString($sUrl, 0, 4) == "http" || cString::getPartOfString($sUrl, 0, 2) == "ww") {
        return true;
    } else {
        return false;
    }
}

/* Repaire some selected link */
if (!empty($_GET['idcontent']) && !empty($_GET['idartlang']) && !empty($_GET['oldlink']) && !empty($_GET['repairedlink'])) {

    if ($_GET['redirect'] == true) { // Update redirect
        $sql = "UPDATE " . $cfg['tab']['art_lang'] . " SET redirect_url = '" . $db->escape(base64_decode($_GET['repairedlink'])) . "' WHERE idartlang = '" . cSecurity::toInteger($_GET['idartlang']) . "'";
        $db->query($sql);
    } else { // Update content

        // Get old value
        $sql = "SELECT value FROM " . $cfg['tab']['content'] . " WHERE idcontent = '" . cSecurity::toInteger($_GET['idcontent']) . "' AND idartlang = '" . cSecurity::toInteger($_GET['idartlang']) . "'";
        $db->query($sql);
        $db->next_record();

        // Generate new value
        $newvalue = str_replace($db->escape(base64_decode($_GET['oldlink'])), $db->escape(base64_decode($_GET['repairedlink'])), $db->f("value"));

        // Update database table with new value
        $sql = "UPDATE " . $cfg['tab']['content'] . " SET value = '" . $newvalue . "' WHERE idcontent = '" . cSecurity::toInteger($_GET['idcontent']) . "' AND idartlang = '" . cSecurity::toInteger($_GET['idartlang']) . "'";
        $db->query($sql);
    }

    // Reset cache
    $oCache->remove($aCacheName['errors'], cSecurity::toInteger($_GET['mode']));
}

/* Whitelist: Add */
if (!empty($_GET['whitelist'])) {
    $sql = "REPLACE INTO " . $cfg['tab']['whitelist'] . " VALUES ('" . $db->escape(base64_decode($_GET['whitelist'])) . "', '" . time() . "')";
    $db->query($sql);

    $oCache->remove($aCacheName['errors'], cSecurity::toInteger($_GET['mode']));
}

/* Whitelist: Get */
$sql = "SELECT url FROM " . $cfg['tab']['whitelist'] . " WHERE lastview < " . (time() + $iWhitelistTimeout) . "
        AND lastview > " . (time() - $iWhitelistTimeout);
$db->query($sql);

$aWhitelist = array();
while ($db->nextRecord()) {
    $aWhitelist[] = $db->f("url");
}

/* Get all links */
// Cache errors
$sCache_errors = $oCache->get($aCacheName['errors'], cSecurity::toInteger($_GET['mode']));

// Search if cache doesn't exist or we're in live mode
if ($sCache_errors && $_GET['live'] != 1) {
    $aErrors = unserialize($sCache_errors);
} else { // If no cache exists

    // Initializing searchLinks class
    $searchLinks = new searchLinks();

    // Select all categorys
    $sql = "SELECT idcat FROM " . $cfg['tab']['cat'] . " GROUP BY idcat";
    $db->query($sql);

    while ($db->nextRecord()) {
        if ($cronjob != true) { // Check userrights, if no cronjob
            $iCheck = cCatPerm($db->f("idcat"), $db2);

            if ($iCheck == true) {
                $aCats[] = cSecurity::toInteger($db->f("idcat"));
            }
        } else {
            $aCats[] = cSecurity::toInteger($db->f("idcat"));
        }
    }

    // Build $aCats-Statement
    if (count($aCats) == 0) {
        $aCats_Sql = "";
    } else {
        $aCats_Sql = "AND cat.idcat IN (0, " . join(", ", $aCats) . ")";
    }

    // Get languageId
    $languageId = cRegistry::getLanguageId();

    // How many articles exist? [Text]
    $sql = "SELECT art.title, art.idartlang, art.idlang, cat.idart, cat.idcat, catName.name AS namecat, con.idcontent, con.value FROM " . $cfg['tab']['cat_art'] . " cat
            LEFT JOIN " . $cfg['tab']['art_lang'] . " art ON (art.idart = cat.idart)
            LEFT JOIN " . $cfg['tab']['cat_lang'] . " catName ON (catName.idcat = cat.idcat)
            LEFT JOIN " . $cfg['tab']['content'] . " con ON (con.idartlang = art.idartlang)
            WHERE (con.value LIKE '%action%' OR con.value LIKE '%data%' OR con.value LIKE '%href%' OR con.value LIKE '%src%')
            " . $aCats_Sql . " AND cat.idcat != '0'
            AND art.idlang = '" . cSecurity::toInteger($languageId) . "' AND catName.idlang = '" . cSecurity::toInteger($languageId) . "'
            AND art.online = '1' AND art.redirect = '0'";
    $db->query($sql);

    while ($db->nextRecord()) {
        // Text decode
        $value = $db->f("value");

        // Set contentId
        $searchLinks->setContentId($db->f("idcontent"));

        // Set articleLangId
        $searchLinks->setArticleLangId($db->f("idartlang"));

        // Search the text
        $aSearchIDInfosNonID = $searchLinks->search($value, $db->f("idart"), $db->f("title"), $db->f("idcat"), $db->f("namecat"), $db->f("idlang"));

        // Search front_content.php-links
        if ($_GET['mode'] != 2) {
            searchFrontContentLinks($value, $db->f("idart"), $db->f("title"), $db->f("idcat"), $db->f("namecat"));
        }
    }

    // How many articles exist? [Redirects]
    $sql = "SELECT art.title, art.redirect_url, art.idartlang, art.idlang, cat.idart, cat.idcat, catName.name AS namecat FROM " . $cfg['tab']['cat_art'] . " cat
            LEFT JOIN " . $cfg['tab']['art_lang'] . " art ON (art.idart = cat.idart)
            LEFT JOIN " . $cfg['tab']['cat_lang'] . " catName ON (catName.idcat = cat.idcat)
            WHERE art.online = '1' AND art.redirect = '1' " . $aCats_Sql . "
            AND art.idlang = '" . cSecurity::toInteger($languageId) . "' AND catName.idlang = '" . cSecurity::toInteger($languageId) . "'
            AND cat.idcat != '0'";
    $db->query($sql);

    // Set mode to "redirect"
    $searchLinks->setMode("redirect");

    while ($db->nextRecord()) {

        // Set articleLangId
        $searchLinks->setArticleLangId($db->f("idartlang"));

        // Search the text
        $aSearchIDInfosNonID = $searchLinks->search($db->f("redirect_url"), $db->f("idart"), $db->f("title"), $db->f("idcat"), $db->f("namecat"), $db->f("idlang"));

        // Search front_content.php-links
        if ($_GET['mode'] != 2) {
            searchFrontContentLinks($db->f("redirect_url"), $db->f("idart"), $db->f("title"), $db->f("idcat"), $db->f("namecat"));
        }
    }

    // Check the links
    checkLinks();
}

/* Analysis of the errors */
// Templateset
if ($cronjob != true) {
    $tpl->set('s', 'TITLE', i18n('Link analysis from ', $plugin_name) . strftime(i18n('%Y-%m-%d', $plugin_name), time()));
}

// If no errors found, say that
if (empty($aErrors) && $cronjob != true) {
    // Reset cache
    $oCache->remove($aCacheName['errors'], cSecurity::toInteger($_GET['mode']));

    $tpl->set('s', 'NO_ERRORS', i18n("<strong>No errors</strong> were found.", $plugin_name));
    $tpl->generate($cfg['templates']['linkchecker_noerrors']);
} elseif (!empty($aErrors) && $cronjob != true) {

    $tpl->set('s', 'ERRORS_HEADLINE', i18n("Total checked links", $plugin_name));
    $tpl->set('s', 'ERRORS_HEADLINE_ARTID', i18n("idart", $plugin_name));
    $tpl->set('s', 'ERRORS_HEADLINE_ARTICLE', i18n("Article", $plugin_name));
    $tpl->set('s', 'ERRORS_HEADLINE_CATID', i18n("idcat", $plugin_name));
    $tpl->set('s', 'ERRORS_HEADLINE_CATNAME', i18n("Category", $plugin_name));
    $tpl->set('s', 'ERRORS_HEADLINE_DESCRIPTION', i18n("Description", $plugin_name));
    $tpl->set('s', 'ERRORS_HEADLINE_REPAIRED', i18n("Repair", $plugin_name));
    $tpl->set('s', 'ERRORS_HEADLINE_LINK', i18n("Linkerror", $plugin_name));
    $tpl->set('s', 'ERRORS_HEADLINE_LINKS_ARTICLES', i18n("Links to articles", $plugin_name));
    $tpl->set('s', 'ERRORS_HEADLINE_LINKS_CATEGORYS', i18n("Links to categories", $plugin_name));
    $tpl->set('s', 'ERRORS_HEADLINE_LINKS_DOCIMAGES', i18n("Links to documents and images", $plugin_name));
    $tpl->set('s', 'ERRORS_HEADLINE_OTHERS', i18n("Links to extern sites and not defined links", $plugin_name));
    $tpl->set('s', 'ERRORS_HEADLINE_WHITELIST', "Whitelist");
    $tpl->set('s', 'ERRORS_HELP_ERRORS', i18n("Wrong links", $plugin_name));

    // error_output initialization
    $aError_output = array(
        'art' => '',
        'cat' => '',
        'docimages' => '',
        'others' => ''
    );

    // Initializing repair class
    $repair = new LinkcheckerRepair();

    foreach ($aErrors as $sKey => $aRow) {

        $aRow = linksort($aRow);

        for ($i = 0; $i < count($aRow); $i++) {

            $tpl2 = new cTemplate();
            $tpl2->reset();

            // html entities for artname and catname
            $aRow[$i]['nameart'] = conHtmlentities($aRow[$i]['nameart']);
            $aRow[$i]['namecat'] = conHtmlentities($aRow[$i]['namecat']);

            // set template variables
            $tpl2->set('s', 'ERRORS_ARTID', $aRow[$i]['idart']);
            $tpl2->set('s', 'ERRORS_ARTICLE', $aRow[$i]['nameart']);
            $tpl2->set('s', 'ERRORS_ARTICLE_SHORT', cString::getPartOfString($aRow[$i]['nameart'], 0, 20) . ((cString::getStringLength($aRow[$i]['nameart']) > 20) ? ' ...' : ''));
            $tpl2->set('s', 'ERRORS_CATID', $aRow[$i]['idcat']);
            $tpl2->set('s', 'ERRORS_LANGARTID', $aRow[$i]['idartlang']);
            $tpl2->set('s', 'ERRORS_LINK', $aRow[$i]['url']);
            $tpl2->set('s', 'ERRORS_LINK_ENCODE', base64_encode($aRow[$i]['url']));
            $tpl2->set('s', 'ERRORS_LINK_SHORT', cString::getPartOfString($aRow[$i]['url'], 0, 45) . ((cString::getStringLength($aRow[$i]['url']) > 45) ? ' ...' : ''));
            $tpl2->set('s', 'ERRORS_CATNAME', $aRow[$i]['namecat']);
            $tpl2->set('s', 'ERRORS_CATNAME_SHORT', cString::getPartOfString($aRow[$i]['namecat'], 0, 20) . ((cString::getStringLength($aRow[$i]['namecat']) > 20) ? ' ...' : ''));
            $tpl2->set('s', 'MODE', $_GET['mode']);
            $tpl2->set('s', 'URL_FRONTEND', $aUrl['cms']);

            if ($aRow[$i]['error_type'] == "unknown") {
                $tpl2->set('s', 'ERRORS_ERROR_TYPE', i18n("Unknown", $plugin_name));
                $tpl2->set('s', 'ERRORS_ERROR_TYPE_HELP', i18n("Unknown: articles, documents etc. do not exist.", $plugin_name));
            } elseif ($aRow[$i]['error_type'] == "offline") {
                $tpl2->set('s', 'ERRORS_ERROR_TYPE', i18n("Offline", $plugin_name));
                $tpl2->set('s', 'ERRORS_ERROR_TYPE_HELP', i18n("Offline: article or category is offline.", $plugin_name));
            } elseif ($aRow[$i]['error_type'] == "startart") {
                $tpl2->set('s', 'ERRORS_ERROR_TYPE', i18n("Offline startarticle", $plugin_name));
                $tpl2->set('s', 'ERRORS_ERROR_TYPE_HELP', i18n("Offline: article or category is offline.", $plugin_name));
            } elseif ($aRow[$i]['error_type'] == "dbfs") {
                $tpl2->set('s', 'ERRORS_ERROR_TYPE', i18n("Filemanager", $plugin_name));
                $tpl2->set('s', 'ERRORS_ERROR_TYPE_HELP', i18n("dbfs: no matches found in the dbfs database.", $plugin_name));
            } elseif ($aRow[$i]['error_type'] == "invalidurl") {
                $tpl2->set('s', 'ERRORS_ERROR_TYPE', i18n("Invalid url", $plugin_name));
                $tpl2->set('s', 'ERRORS_ERROR_TYPE_HELP', i18n("Invalid url, i. e. syntax error.", $plugin_name));

                // Try to repair this link misstage
                $repaired_link = $repair->checkLink($aRow[$i]['url']);
            }

            // Generate repaired link variables
            if ($aRow[$i]['error_type'] != "invalidurl") { // No invalid url
                                                           // case
                $tpl2->set('s', 'ERRORS_REPAIRED_LINK', '-');
            } elseif ($repaired_link == false) { // Linkchecker can not repaire
                                                 // this link
                $tpl2->set('s', 'ERRORS_REPAIRED_LINK', i18n("No repaired link", $plugin_name));
            } else { // Yeah, we have an repaired link!

                // Repaired question
                $repaired_question = i18n("Linkchecker has found a way to repair your wrong link. Do you want to automatically repair the link to the URL below?", $plugin_name);

                $tpl2->set('s', 'ERRORS_REPAIRED_LINK', '<a href="javascript:void(0)" onclick="javascript:Con.showConfirmation(\'' . $repaired_question . '<br /><br /><strong>' . $repaired_link . '</strong>\', function() { window.location.href=\'' . $aUrl['contenido'] . 'main.php?area=linkchecker&frame=4&contenido=' . $sess->id . '&action=linkchecker&mode=' . $_GET['mode'] . '&idcontent=' . $aRow[$i]['idcontent'] . '&idartlang=' . $aRow[$i]['idartlang'] . '&oldlink=' . base64_encode($aRow[$i]['url']) . '&repairedlink=' . base64_encode($repaired_link) . '&redirect=' . $aRow[$i]['redirect'] . '\';})"><img src="' . $aUrl['contenido'] . 'images/but_editlink.gif" alt="" border="0"></a>');
            }

            if ($sKey != "cat") {
                $aError_output[$sKey] .= $tpl2->generate($cfg['templates']['linkchecker_test_errors'], 1);
            } else {
                // special template for idcats
                $aError_output[$sKey] .= $tpl2->generate($cfg['templates']['linkchecker_test_errors_cat'], 1);
            }
        }
    }

    // Counter
    if ($iCounter = $oCache->get($aCacheName['errorscount'], cSecurity::toInteger($_GET['mode']))) {
        // Cache exists?
        $iErrorsCountChecked = $iCounter;
    } else {
        // Count searched links: idarts + idcats + idcatarts + others
        $iErrorsCountChecked = count($aSearchIDInfosArt) + count($aSearchIDInfosCat) + count($aSearchIDInfosCatArt) + count($aSearchIDInfosNonID);
    }

    // Count errors
    foreach ($aErrors as $sKey => $aRow) {
        $iErrorsCounted += count($aErrors[$sKey]);
    }

    $tpl->set('s', 'ERRORS_COUNT_CHECKED', $iErrorsCountChecked);
    $tpl->set('s', 'ERRORS_COUNT_ERRORS', $iErrorsCounted);
    $tpl->set('s', 'ERRORS_COUNT_ERRORS_PERCENT', round(($iErrorsCounted * 100) / $iErrorsCountChecked, 2));

    /* Template output */
    foreach ($aError_output as $sKey => $sValue) {

        if (empty($aError_output[$sKey])) { // Errors for this type?
            $tpl2->set('s', 'ERRORS_NOTHING', i18n("No errors for this type.", $plugin_name));
            $aError_output[$sKey] = $tpl2->generate($cfg['templates']['linkchecker_test_nothing'], 1);
        }

        $tpl->set('s', 'ERRORS_SHOW_' . cString::toUpperCase($sKey), $aError_output[$sKey]);

        if (count($aErrors[$sKey]) > 0) {
            $tpl->set('s', 'ERRORS_COUNT_ERRORS_' . cString::toUpperCase($sKey), '<span class="settingWrong">' . count($aErrors[$sKey]) . '</span>');
        } else {
            $tpl->set('s', 'ERRORS_COUNT_ERRORS_' . cString::toUpperCase($sKey), count($aErrors[$key]));
        }
    }

    $tpl->generate($cfg['templates']['linkchecker_test']);

    /* Cache */
    // Reset cache
    $oCache->remove($aCacheName['errors'], cSecurity::toInteger($_GET['mode']));

    // Build new cache
    $oCache->save(serialize($aErrors), $aCacheName['errors'], cSecurity::toInteger($_GET['mode']));
    $oCache->save($iErrorsCountChecked, $aCacheName['errorscount'], cSecurity::toInteger($_GET['mode']));
}

// Log
if ($cronjob != true) {
    $backend->log(0, 0, cRegistry::getClientId(), cRegistry::getLanguageId(), $action);
}

?>