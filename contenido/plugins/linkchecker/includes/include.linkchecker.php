<?php

/**
 * This is the main backend page for the linkchecker plugin.
 *
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
 * @var cPermission $perm
 * @var cGuiNotification $notification
 * @var cBackend $backend
 * @var cTemplate $tpl
 * @var cDb $db
 * @var cSession $sess
 * @var array $cfgClient
 * @var int $client
 *
 * @var bool $cronjob
 */

$cfg = cRegistry::getConfig();
$pluginName = $cfg['pi_linkchecker']['pluginName'];

$cronjob = $cronjob ?? false;

if (!$cronjob) {
    // Check permissions for linkchecker action
    if (!$perm->have_perm_area_action($pluginName, "linkchecker")) {
        cRegistry::addErrorMessage(i18n("No permissions"));
        $page = new cGuiPage('generic_page');
        $page->abortRendering();
        $page->render();
        exit();
    }

    if (cRegistry::getClientId() == 0) {
        $notification->displayNotification("error", i18n("No Client selected"));
        exit();
    }
}

// If no mode defined, use mode 3 (1 = intern, 2 = extern, 3 = intern/extern)
$requestMode = cSecurity::toInteger($_GET['mode'] ?? '3');

// If no action defined
$requestAction = $_GET['action'] ?? 'linkchecker';

// Initialization
$aCats                = [];
$aSearchIDInfosArt    = [];
$aSearchIDInfosCat    = [];
$aSearchIDInfosCatArt = [];
$aSearchIDInfosNonID  = [];

// Var initialization
$aUrl = [
    'cms' => cRegistry::getFrontendUrl(),
    'contenido' => cRegistry::getBackendUrl()
];

// Cache options
// TODO find out where the variable $aCacheName is used and where it is set.
if (!isset($aCacheName['errors'])) {
    $aCacheName['errors'] = '';
}
$aCacheName = [
    'errors' => $sess->id,
    'errorscount' => $aCacheName['errors'] . "ErrorsCountChecked"
];
$oCache = new cFileCache([
    'cacheDir' => $cfgClient[$client]['cache']['path'],
    'lifeTime' => $cfg['pi_linkchecker']['cacheLifeTime'],
]);

/*
 * ******** Program code ********
 */

/**
 * @deprecated [2023-01-25] Since 4.10.2, use cLinkcheckerHelper::sortErrors() instead
 */
function linksort($sErrors, $requestSort) {
    cDeprecated("The function linksort() is deprecated since CONTENIDO 4.10.2, use cLinkcheckerHelper::sortErrors() instead.");
    return cLinkcheckerHelper::sortErrors($sErrors, $requestSort);
}

/**
 * @deprecated [2023-01-25] Since 4.10.2, use cLinkcheckerHelper::urlIsImage() instead
 */
function url_is_image($sUrl) {
    cDeprecated("The function url_is_image() is deprecated since CONTENIDO 4.10.2, use cLinkcheckerHelper::urlIsImage() instead.");
    return cLinkcheckerHelper::urlIsImage($sUrl);
}

/**
 * @deprecated [2023-01-25] Since 4.10.2, use cLinkcheckerHelper::urlIsUri() instead
 */
function url_is_uri($sUrl) {
    cDeprecated("The function url_is_uri() is deprecated since CONTENIDO 4.10.2, use cLinkcheckerHelper::urlIsUri() instead.");
    return cLinkcheckerHelper::urlIsUri($sUrl);
}

/// Repair some selected link
if (!empty($_GET['idcontent']) && !empty($_GET['idartlang']) && !empty($_GET['oldlink']) && !empty($_GET['repairedlink'])) {

    $requestIdArtLang = cSecurity::toInteger($_GET['idartlang']);

    if ($_GET['redirect'] == true) {
        // Update redirect
        $sql = $db->buildUpdate(cRegistry::getDbTableName('art_lang'), ['redirect_url' => base64_decode($_GET['repairedlink'])], ['idartlang' => $requestIdArtLang]);
        $db->query($sql);
    } else {
        // Update content

        $requestIdContent = cSecurity::toInteger($_GET['idcontent']);

        // Get old value
        $sql = "SELECT `value` FROM `%s` WHERE `idcontent` = %d AND `idartlang` = %d";
        $db->query($sql, cRegistry::getDbTableName('content'), $requestIdContent, $requestIdArtLang);
        $db->nextRecord();

        // Generate new value
        $newValue = str_replace(base64_decode($_GET['oldlink']), base64_decode($_GET['repairedlink']), $db->f("value"));

        // Update database table with new value
        $sql = $db->buildUpdate(cRegistry::getDbTableName('content'), ['value' => $newValue], ['idcontent' => $requestIdContent, 'idartlang' => $requestIdArtLang]);

        $db->query($sql);
    }

    // Reset cache
    $oCache->remove($aCacheName['errors'], $requestMode);
}

/* Whitelist: Add */
if (!empty($_GET['whitelist'])) {
    $sql = "REPLACE INTO `:tab_whitelist` VALUES (':url', ':lastview')";
    $db->query($sql, [
        'tab_whitelist' => cRegistry::getDbTableName('whitelist'),
        'url' => base64_decode($_GET['whitelist']),
        'lastview' => time()
    ]);

    $oCache->remove($aCacheName['errors'], $requestMode);
}

/* Whitelist: Get */
$whitelistTimeout = $cfg['pi_linkchecker']['whitelistTimeout'];
$sql = "SELECT `url` FROM `%s` WHERE `lastview` < %d AND `lastview` > %d";
$db->query($sql, cRegistry::getDbTableName('whitelist'), time() + $whitelistTimeout, time() - $whitelistTimeout);

$aWhitelist = [];
while ($db->nextRecord()) {
    $aWhitelist[] = $db->f("url");
}

/* Get all links */
// Cache errors
$sCache_errors = $oCache->get($aCacheName['errors'], $requestMode);

// Search if cache doesn't exist, or we're in live mode
$requestLive = $_GET['live'] ?? '';
if ($sCache_errors && $requestLive != 1) {
    $aErrors = unserialize($sCache_errors);
} else { // If no cache exists

    // Initializing cLinkCheckerSearchLinks class
    $searchLinks = new cLinkcheckerSearchLinks('text', $requestMode);

    $db2 = cRegistry::getDb();

    // Select all categories
    // Check user-rights, if no cronjob
    $db->query("SELECT `idcat` FROM `%s` GROUP BY `idcat`",  cRegistry::getDbTableName('cat'));
    while ($db->nextRecord()) {
        if ($cronjob || cLinkcheckerCategoryHelper::checkPermission($db->f("idcat"), $db2)) {
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
    $sql = "SELECT art.title, art.idartlang, art.idlang, cat.idart, cat.idcat, catName.name AS namecat, con.idcontent, con.value FROM " . cRegistry::getDbTableName('cat_art') . " cat
            LEFT JOIN " . cRegistry::getDbTableName('art_lang') . " art ON (art.idart = cat.idart)
            LEFT JOIN " . cRegistry::getDbTableName('cat_lang') . " catName ON (catName.idcat = cat.idcat)
            LEFT JOIN " . cRegistry::getDbTableName('content') . " con ON (con.idartlang = art.idartlang)
            WHERE (
                con.value LIKE '%action%'
                OR con.value LIKE '%data%'
                OR con.value LIKE '%href%'
                OR con.value LIKE '%src%'
            )
                " . $aCats_Sql . "
                AND cat.idcat != '0'
                AND art.idlang = '" . cSecurity::toInteger($languageId) . "'
                AND catName.idlang = '" . cSecurity::toInteger($languageId) . "'
                AND art.online = '1'
                AND art.redirect = '0'";

    $db->query($sql);

    while ($db->nextRecord()) {
        // Text decode
        $value = $db->f("value");

        // Search the text
        $aSearchIDInfosNonID = $searchLinks->search($value, $db->f("idart"), $db->f("title"), $db->f("idcat"), $db->f("namecat"), $db->f("idlang"), $db->f("idartlang"), $db->f("idcontent"));

        // Search front_content.php-links
        if ($requestMode != 2) {
            cLinkcheckerTester::searchFrontContentLinks($value, $db->f("idart"), $db->f("title"), $db->f("idcat"), $db->f("namecat"));
        }
    }

    // How many articles exist? [Redirects]
    $sql = "SELECT art.title, art.redirect_url, art.idartlang, art.idlang, cat.idart, cat.idcat, catName.name AS namecat FROM " . cRegistry::getDbTableName('cat_art') . " cat
            LEFT JOIN " . cRegistry::getDbTableName('art_lang') . " art ON (art.idart = cat.idart)
            LEFT JOIN " . cRegistry::getDbTableName('cat_lang') . " catName ON (catName.idcat = cat.idcat)
            WHERE art.online = '1'
                AND art.redirect = '1'
                " . $aCats_Sql . "
                AND art.idlang = '" . cSecurity::toInteger($languageId) . "'
                AND catName.idlang = '" . cSecurity::toInteger($languageId) . "'
                AND cat.idcat != '0'";
    $db->query($sql);

    // Set mode to "redirect"
    $searchLinks->setMode("redirect");

    while ($db->nextRecord()) {
        // Search the text
        $aSearchIDInfosNonID = $searchLinks->search($db->f("redirect_url"), $db->f("idart"), $db->f("title"), $db->f("idcat"), $db->f("namecat"), $db->f("idlang"), $db->f("idartlang"));

        // Search front_content.php-links
        if ($requestMode != 2) {
            cLinkcheckerTester::searchFrontContentLinks($db->f("redirect_url"), $db->f("idart"), $db->f("title"), $db->f("idcat"), $db->f("namecat"));
        }
    }

    // Check the links
    cLinkcheckerTester::checkLinks();
}

/* Analysis of the errors */

if (!$cronjob) {
    // Fill and render the template

    $tpl->set('s', 'MODE', $requestMode);

    // Fill Subnav I
    $sLink = $sess->url("main.php?area=linkchecker&frame=4&action=linkchecker") . '&mode=';

    // Fill Subnav II
    $tpl->set('s', 'INTERNS_HREF', $sLink . '1');
    $tpl->set('s', 'INTERNS_LABEL', i18n("Interns", $pluginName));
    $tpl->set('s', 'EXTERNS_HREF', $sLink . '2');
    $tpl->set('s', 'EXTERNS_LABEL', i18n("Externs", $pluginName));
    $tpl->set('s', 'INTERNS_EXTERNS_HREF', $sLink . '3');
    $tpl->set('s', 'INTERNS_EXTERNS_LABEL', i18n("Intern/extern Links", $pluginName));

    // Fill Subnav III
    $tpl->set('s', 'UPDATE_HREF', $sLink . $requestMode . '&live=1');

    $tpl->set('s', 'TITLE', i18n('Link analysis from ', $pluginName) . cDate::formatToDate(i18n('%Y-%m-%d', $pluginName), time()));

    // If no errors found, say that
    if (empty($aErrors)) {
        // Reset cache
        $oCache->remove($aCacheName['errors'], $requestMode);

        $tpl->set('s', 'NO_ERRORS', i18n("<strong>No errors</strong> were found.", $pluginName));
        $tpl->generate($cfg['templates']['linkchecker_noerrors']);
    } elseif (!empty($aErrors)) {

        $tpl->set('s', 'ERRORS_HEADLINE', i18n("Total checked links", $pluginName));
        $tpl->set('s', 'ERRORS_HEADLINE_ARTID', i18n("idart", $pluginName));
        $tpl->set('s', 'ERRORS_HEADLINE_ARTICLE', i18n("Article", $pluginName));
        $tpl->set('s', 'ERRORS_HEADLINE_CATID', i18n("idcat", $pluginName));
        $tpl->set('s', 'ERRORS_HEADLINE_CATNAME', i18n("Category", $pluginName));
        $tpl->set('s', 'ERRORS_HEADLINE_DESCRIPTION', i18n("Description", $pluginName));
        $tpl->set('s', 'ERRORS_HEADLINE_REPAIRED', i18n("Repair", $pluginName));
        $tpl->set('s', 'ERRORS_HEADLINE_LINK', i18n("Linkerror", $pluginName));
        $tpl->set('s', 'ERRORS_HEADLINE_LINKS_ARTICLES', i18n("Links to articles", $pluginName));
        $tpl->set('s', 'ERRORS_HEADLINE_LINKS_CATEGORYS', i18n("Links to categories", $pluginName));
        $tpl->set('s', 'ERRORS_HEADLINE_LINKS_DOCIMAGES', i18n("Links to documents and images", $pluginName));
        $tpl->set('s', 'ERRORS_HEADLINE_OTHERS', i18n("Links to extern sites and not defined links", $pluginName));
        $tpl->set('s', 'ERRORS_HEADLINE_WHITELIST', "Whitelist");
        $tpl->set('s', 'ERRORS_HELP_ERRORS', i18n("Wrong links", $pluginName));

        // error_output initialization
        $aError_output = [
            'art' => '',
            'cat' => '',
            'docimages' => '',
            'others' => ''
        ];

        // Initializing repair class
        $repair = new cLinkcheckerRepair();

        $sortBy = $_GET['sort'] ?? '';
        $tpl2 = new cTemplate();

        foreach ($aErrors as $sKey => $aRow) {
            $aRow = cLinkcheckerHelper::sortErrors($aRow, $sortBy);

            for ($i = 0; $i < count($aRow); $i++) {
                $tpl2->reset();

                // html entities for artname and catname
                $aRow[$i]['nameart'] = conHtmlentities($aRow[$i]['nameart']);
                $aRow[$i]['namecat'] = conHtmlentities($aRow[$i]['namecat']);

                $artNameShort = cString::getPartOfString($aRow[$i]['nameart'], 0, 20) . ((cString::getStringLength($aRow[$i]['nameart']) > 20) ? ' ...' : '');
                $caNameShort = cString::getPartOfString($aRow[$i]['namecat'], 0, 20) . ((cString::getStringLength($aRow[$i]['namecat']) > 20) ? ' ...' : '');
                $linkShort = cString::getPartOfString($aRow[$i]['url'], 0, 45) . ((cString::getStringLength($aRow[$i]['url']) > 45) ? ' ...' : '');

                // set template variables
                $tpl2->set('s', 'ERRORS_ARTID', cSecurity::toInteger($aRow[$i]['idart']));
                $tpl2->set('s', 'ERRORS_ARTICLE', cSecurity::escapeString($aRow[$i]['nameart']));
                $tpl2->set('s', 'ERRORS_ARTICLE_SHORT', $artNameShort);
                $tpl2->set('s', 'ERRORS_CATID', cSecurity::toInteger($aRow[$i]['idcat']));
                $tpl2->set('s', 'ERRORS_LANGARTID', cSecurity::toInteger($aRow[$i]['idartlang']));
                $tpl2->set('s', 'ERRORS_LINK', cSecurity::escapeString($aRow[$i]['url']));
                $tpl2->set('s', 'ERRORS_LINK_ENCODE', base64_encode($aRow[$i]['url']));
                $tpl2->set('s', 'ERRORS_LINK_SHORT', $linkShort);
                $tpl2->set('s', 'ERRORS_CATNAME', cSecurity::escapeString($aRow[$i]['namecat']));
                $tpl2->set('s', 'ERRORS_CATNAME_SHORT', $caNameShort);
                $tpl2->set('s', 'MODE', $requestMode);
                $tpl2->set('s', 'URL_FRONTEND', $aUrl['cms']);

                $repaired_link = false;
                if ($aRow[$i]['error_type'] == "unknown") {
                    $tpl2->set('s', 'ERRORS_ERROR_TYPE', i18n("Unknown", $pluginName));
                    $tpl2->set('s', 'ERRORS_ERROR_TYPE_HELP', i18n("Unknown: articles, documents etc. do not exist.", $pluginName));
                } elseif ($aRow[$i]['error_type'] == "offline") {
                    $tpl2->set('s', 'ERRORS_ERROR_TYPE', i18n("Offline", $pluginName));
                    $tpl2->set('s', 'ERRORS_ERROR_TYPE_HELP', i18n("Offline: article or category is offline.", $pluginName));
                } elseif ($aRow[$i]['error_type'] == "startart") {
                    $tpl2->set('s', 'ERRORS_ERROR_TYPE', i18n("Offline startarticle", $pluginName));
                    $tpl2->set('s', 'ERRORS_ERROR_TYPE_HELP', i18n("Offline: article or category is offline.", $pluginName));
                } elseif ($aRow[$i]['error_type'] == "dbfs") {
                    $tpl2->set('s', 'ERRORS_ERROR_TYPE', i18n("Filemanager", $pluginName));
                    $tpl2->set('s', 'ERRORS_ERROR_TYPE_HELP', i18n("dbfs: no matches found in the dbfs database.", $pluginName));
                } elseif ($aRow[$i]['error_type'] == "invalidurl") {
                    $tpl2->set('s', 'ERRORS_ERROR_TYPE', i18n("Invalid url", $pluginName));
                    $tpl2->set('s', 'ERRORS_ERROR_TYPE_HELP', i18n("Invalid url, i. e. syntax error.", $pluginName));

                    // Try to repair this link misstate
                    $repaired_link = $repair->checkLink($aRow[$i]['url']);
                }

                // Generate repaired link variables
                if ($aRow[$i]['error_type'] != "invalidurl") {
                    // No invalid url case
                    $tpl2->set('s', 'ERRORS_REPAIRED_LINK', '-');
                } elseif ($repaired_link === false) {
                    // Linkchecker can not repair this link
                    $tpl2->set('s', 'ERRORS_REPAIRED_LINK', i18n("No repaired link", $pluginName));
                } else {
                    // Repair link
                    $repaired_question = i18n("Linkchecker has found a way to repair your wrong link. Do you want to automatically repair the link to the URL below?", $pluginName);
                    $url = $aUrl['contenido'] . 'main.php?area=linkchecker&frame=4&contenido=' . $sess->id
                        . '&action=linkchecker&mode=' . $requestMode . '&idcontent=' . $aRow[$i]['idcontent']
                        . '&idartlang=' . $aRow[$i]['idartlang'] . '&oldlink=' . base64_encode($aRow[$i]['url'])
                        . '&repairedlink=' . base64_encode($repaired_link) . '&redirect=' . $aRow[$i]['redirect'];
                    $link = '<a href="javascript:void(0)" onclick="Con.showConfirmation(\'' . $repaired_question . '<br /><br /><strong>'
                        . $repaired_link . '</strong>\', function() { window.location.href=\'' . $url . '\';})"><img src="' . $aUrl['contenido'] . 'images/but_editlink.gif" alt=""></a>';
                    $tpl2->set('s', 'ERRORS_REPAIRED_LINK', $link);
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
        if ($iCounter = $oCache->get($aCacheName['errorscount'], $requestMode)) {
            // Cache exists?
            $iErrorsCountChecked = $iCounter;
        } else {
            // Count searched links: idarts + idcats + idcatarts + others
            $iErrorsCountChecked = count($aSearchIDInfosArt) + count($aSearchIDInfosCat) + count($aSearchIDInfosCatArt) + count($aSearchIDInfosNonID);
        }

        // Count errors
        $iErrorsCounted = 0;
        foreach ($aErrors as $sKey => $aRow) {
            $iErrorsCounted += count($aRow);
        }

        $tpl->set('s', 'ERRORS_COUNT_CHECKED', $iErrorsCountChecked);
        $tpl->set('s', 'ERRORS_COUNT_ERRORS', $iErrorsCounted);
        $tpl->set('s', 'ERRORS_COUNT_ERRORS_PERCENT', round(($iErrorsCounted * 100) / $iErrorsCountChecked, 2));

        /* Template output */
        foreach ($aError_output as $sKey => $sValue) {
            if (empty($sValue)) { // Errors for this type?
                $tpl2->reset();
                $tpl2->set('s', 'ERRORS_NOTHING', i18n("No errors for this type.", $pluginName));
                $aError_output[$sKey] = $tpl2->generate($cfg['templates']['linkchecker_test_nothing'], 1);
            }

            $tpl->set('s', 'ERRORS_SHOW_' . cString::toUpperCase($sKey), $aError_output[$sKey]);

            if (isset($aErrors[$sKey]) && is_array($aErrors[$sKey]) && count($aErrors[$sKey]) > 0) {
                $tpl->set('s', 'ERRORS_COUNT_ERRORS_' . cString::toUpperCase($sKey), '<span class="settingWrong">' . count($aErrors[$sKey]) . '</span>');
            } else {
                $tpl->set('s', 'ERRORS_COUNT_ERRORS_' . cString::toUpperCase($sKey), 0);
            }
        }

        $tpl->generate($cfg['templates']['linkchecker_test']);

        /* Cache */
        // Reset cache
        $oCache->remove($aCacheName['errors'], $requestMode);

        // Build new cache
        $oCache->save(serialize($aErrors), $aCacheName['errors'], $requestMode);
        $oCache->save($iErrorsCountChecked, $aCacheName['errorscount'], $requestMode);
    }

    // Log
    $backend->log(0, 0, cRegistry::getClientId(), cRegistry::getLanguageId(), $requestAction);
}
