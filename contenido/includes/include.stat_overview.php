<?php
/**
 * This file contains the backend page for displaying statistics overview.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Olaf Niemann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$tpl->reset();
$contenidoNotification = new cGuiNotification();
$trackingNotification = "";
$googleNotification = "";
$piwikNotification = "";
//Show message if statistics off
$cApiClient = new cApiClient($client);
if ($cApiClient->getProperty("stats", "tracking") == "off") {
    $trackingNotification = $contenidoNotification->returnNotification('warning', i18n("Tracking was disabled for this client!"));
}

//Display google account message
if (($googleAccount = getEffectiveSetting('stats', 'ga_account', '')) != "") {
    $linkToGoogle = sprintf('<a target="_blank" href="http://www.google.com/intl/' . $belang . '/analytics/">%s</a>', i18n("here"));
    $googleNotification = $contenidoNotification->returnNotification('warning', sprintf(i18n("This client has been configured with Google Analytics account %s. Click %s to visit Google Analytics"), $googleAccount, $linkToGoogle));
}

//display piwik account message
if (($piwikUrl = getEffectiveSetting('stats', 'piwik_url', '')) != "") {
    if (($piwikSite = getEffectiveSetting('stats', 'piwik_site', '')) != "") {
        $linkToPiwik = sprintf('<a target="_blank" href="' . $piwikUrl . '">%s</a>', i18n('here'));
        $piwikNotification = $contenidoNotification->returnNotification('warning', sprintf(i18n("This client has bee configured with Piwik Site %s. Click %s to visit the Piwik installation."), $piwikSite, $linkToPiwik));
    }
}


if ($action == "stat_show") {
    if (strlen($yearmonth) < 4) {
        $yearmonth = "current";
    }

    switch ($displaytype) {
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
            $displaytype = "all";
            $stattype = i18n("Full statistics");
            break;
    }

    $tpl->set('s', 'SELF_URL', $sess->url("main.php?area=stat&frame=4&idcat=$idcat"));
    if ($showYear == 1) {
        $tpl->set('s', 'DROPDOWN', statDisplayYearlyTopChooser($displaytype));
        $tpl->set('s', 'YEARMONTH', '<form name="hiddenValues"><input type="hidden" name="yearmonth" value="' . $year . '"></form>');
    } else {
        $tpl->set('s', 'DROPDOWN', statDisplayTopChooser($displaytype));
        $tpl->set('s', 'YEARMONTH', '<form name="hiddenValues"><input type="hidden" name="yearmonth" value="' . $yearmonth . '"></form>');
    }

    if ($showYear == 1) {
        $tpl->set('s', 'STATTITLE', i18n("Yearly") . ' ' . $stattype . " " . $year);
    } else {
        if (strcmp($yearmonth, "current") == 0) {
            $tpl->set('s', 'STATTITLE', i18n("Current") . ' ' . $stattype);
        } else {
            $tpl->set('s', 'STATTITLE', $stattype . " " . getCanonicalMonth(substr($yearmonth, 4, 2)) . ' ' . substr($yearmonth, 0, 4));
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
    $tpl->set('s', 'TRACKING_NOTIFICATION', $trackingNotification . ($trackingNotification != '') ? '<br>' : '');

    switch ($displaytype) {
        case "all":
        default:
            if ($showYear == 1) {
                statsOverviewYear($year);
            } else {
                statsOverviewAll($yearmonth);
            }
            $tpl->generate($cfg['path']['templates'] . $cfg['templates']['stat_overview']);
            break;
        case "top10":
            if ($showYear == 1) {
                statsOverviewTopYear($year, 10);
            } else {
                statsOverviewTop($yearmonth, 10);
            }
            $tpl->generate($cfg['path']['templates'] . $cfg['templates']['stat_top']);
            break;
        case "top20":
            if ($showYear == 1) {
                statsOverviewTopYear($year, 20);
            } else {
                statsOverviewTop($yearmonth, 20);
            }
            $tpl->generate($cfg['path']['templates'] . $cfg['templates']['stat_top']);
            break;
        case "top30":
            if ($showYear == 1) {
                statsOverviewTopYear($year, 30);
            } else {
                statsOverviewTop($yearmonth, 30);
            }
            $tpl->generate($cfg['path']['templates'] . $cfg['templates']['stat_top']);
            break;
    }
} else {
    $tpl->reset();
    $tpl->set('s', 'CONTENTS', $trackingNotification . '<br>' . $googleNotification . '<br>' . $piwikNotification);
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['blank']);
}

?>