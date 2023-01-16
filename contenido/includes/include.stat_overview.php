<?php

/**
 * This file contains the backend page for displaying statistics overview.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Olaf Niemann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$tpl->reset();
$contenidoNotification = new cGuiNotification();

// CON-2718
$statisticmode = getSystemProperty('stats', 'tracking');
if ($statisticmode == 'disabled') {
    $trackingNotification = $contenidoNotification->returnNotification('error', i18n('The statistic is disabled. You can activate it at the system configuration.'));
    $tpl->set('s', 'CONTENTS', $trackingNotification);
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['blank']);
    return false;
}

$googleNotification = "";
$piwikNotification = "";

// Display google account message
if (($googleAccount = getEffectiveSetting('stats', 'ga_account', '')) != "") {
    $linkToGoogle = sprintf('<a target="_blank" href="http://www.google.com/intl/' . $belang . '/analytics/">%s</a>', i18n("here"));
    $googleNotification = $contenidoNotification->returnNotification('warning', sprintf(i18n("This client has been configured with Google Analytics account %s. Click %s to visit Google Analytics"), $googleAccount, $linkToGoogle));
}

// display piwik account message
if (($piwikUrl = getEffectiveSetting('stats', 'piwik_url', '')) != "") {
    if (($piwikSite = getEffectiveSetting('stats', 'piwik_site', '')) != "") {
        $linkToPiwik = sprintf('<a target="_blank" href="' . $piwikUrl . '">%s</a>', i18n('here'));
        $piwikNotification = $contenidoNotification->returnNotification('warning', sprintf(i18n("This client has been configured with Piwik Site %s. Click %s to visit the Piwik installation."), $piwikSite, $linkToPiwik));
    }
}

$requestShowYear = cSecurity::toInteger($_REQUEST['showYear'] ?? '0');
$requestYear = cSecurity::toInteger($_REQUEST['year'] ?? '0');
$requestYearMonth = $_REQUEST['yearmonth'] ?? '';
$requestDisplayType = $_REQUEST['displaytype'] ?? '';

if ($action == "stat_show") {
    if (cString::getStringLength($requestYearMonth) < 4) {
        $requestYearMonth = "current";
    }

    switch ($requestDisplayType) {
        case "all":
            $stattype = i18n("Full statistics");
            break;
        case "top10":
            $stattype = i18n("Top 10");
            break;
        case "top20":
            $stattype = i18n("Top 20");
            break;
        case "top30":
            $stattype = i18n("Top 30");
            break;
        default:
            $requestDisplayType = "all";
            $stattype = i18n("Full statistics");
            break;
    }

    $tpl->set('s', 'SELF_URL', $sess->url("main.php?area=stat&frame=4&idcat=$idcat"));
    if ($requestShowYear == 1) {
        $tpl->set('s', 'DROPDOWN', statDisplayYearlyTopChooser($requestDisplayType));
        $tpl->set('s', 'YEARMONTH', '<form name="hiddenValues"><input type="hidden" name="yearmonth" value="' . $requestYear . '"></form>');
    } else {
        $tpl->set('s', 'DROPDOWN', statDisplayTopChooser($requestDisplayType));
        $tpl->set('s', 'YEARMONTH', '<form name="hiddenValues"><input type="hidden" name="yearmonth" value="' . $requestYearMonth . '"></form>');
    }

    if ($requestShowYear == 1) {
        $tpl->set('s', 'STATTITLE', i18n("Yearly") . ' ' . $stattype . " " . $requestYear);
    } else {
        if (strcmp($requestYearMonth, "current") == 0) {
            $tpl->set('s', 'STATTITLE', i18n("Current") . ' ' . $stattype);
        } else {
            $tpl->set('s', 'STATTITLE', $stattype . " " . getCanonicalMonth(cString::getPartOfString($requestYearMonth, 4, 2)) . ' ' . cString::getPartOfString($requestYearMonth, 0, 4));
        }
    }

    $tpl->set('s', 'TITLETEXT', i18n("Title"));
    $tpl->set('s', 'TITLESTATUS', i18n("Status"));
    $tpl->set('s', 'TITLENUMBEROFARTICLES', i18n("Number of articles"));
    $tpl->set('s', 'TITLETOTAL', i18n("Hits"));
    $tpl->set('s', 'TITLEPADDING_LEFT', "5");
    $tpl->set('s', 'TITLEINTHISLANGUAGE', i18n("Hits in this language"));

    $tpl->set('s', 'GOOGLE_NOTIFICATION', $googleNotification . ($googleNotification != '') ? '<br>' : '');
    $tpl->set('s', 'PIWIK_NOTIFICATION', $piwikNotification . ($piwikNotification != '') ? '<br>' : '');

    switch ($requestDisplayType) {
        case "all":
        default:
            if ($requestShowYear == 1) {
                statsOverviewYear($requestYear);
            } else {
                statsOverviewAll($requestYearMonth);
            }
            $tpl->generate($cfg['path']['templates'] . $cfg['templates']['stat_overview']);
            break;
        case "top10":
            if ($requestShowYear == 1) {
                statsOverviewTopYear($requestYear, 10);
            } else {
                statsOverviewTop($requestYearMonth, 10);
            }
            $tpl->generate($cfg['path']['templates'] . $cfg['templates']['stat_top']);
            break;
        case "top20":
            if ($requestShowYear == 1) {
                statsOverviewTopYear($requestYear, 20);
            } else {
                statsOverviewTop($requestYearMonth, 20);
            }
            $tpl->generate($cfg['path']['templates'] . $cfg['templates']['stat_top']);
            break;
        case "top30":
            if ($requestShowYear == 1) {
                statsOverviewTopYear($requestYear, 30);
            } else {
                statsOverviewTop($requestYearMonth, 30);
            }
            $tpl->generate($cfg['path']['templates'] . $cfg['templates']['stat_top']);
            break;
    }
} else {
    $tpl->reset();
    $tpl->set('s', 'CONTENTS', $googleNotification . '<br>' . $piwikNotification);
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['blank']);
}

?>