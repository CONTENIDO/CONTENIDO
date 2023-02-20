<?php

/**
 * This file contains the menu frame backend page for statistics area.
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

/**
 * @var cTemplate $tpl
 * @var cSession $sess
 * @var array $cfg
 */

$tpl->reset();

$client = cSecurity::toInteger(cRegistry::getClientId());
$lang = cSecurity::toInteger(cRegistry::getLanguageId());

// CON-2718
// Do not display anything if the statistic is disabled
$statisticmode = getSystemProperty('stats', 'tracking');
if ($statisticmode == 'disabled') {
    return false;
}

$currentLink = '<a target="right_bottom" href="' . $sess->url("main.php?area=stat&frame=4&displaytype=top10&action=stat_show&yearmonth=current") . '">' . i18n("Current Report") . '</a>';

$availableYears = statGetAvailableYears($client, $lang);

// Title
$tpl->set('s', 'PADDING_LEFT', '7');
$tpl->set('s', 'OVERVIEWTEXT', "<b>" . i18n("Statistics Overview") . "</b>");

// Current Statistic
$tpl->set('s', 'CURRENTTEXT', $currentLink);
$tpl->set('s', 'PADDING_LEFT', '7');

// Empty Row
$text = '&nbsp;';
if (count($availableYears) != 0) {
    $text = '<b>' . i18n("Archived Statistics") . '</b>';
}

$tpl->set('s', 'ARCHIVETEXT', $text);
$tpl->set('s', 'PADDING_LEFT', '7');

foreach ($availableYears as $yearIterator) {
    //$yearLink = function statsOverviewYear($year)
    $yearLink = '<a target="right_bottom" href="' . $sess->url("main.php?area=stat&frame=4&action=stat_show&displaytype=top10&showYear=1&year=" . $yearIterator) . '">' . "$yearIterator" . '</a>';
    $tpl->set('d', 'TEXT', $yearLink);
    $tpl->set('d', 'PADDING_LEFT', '7');
    $tpl->next();

    $availableMonths = statGetAvailableMonths($yearIterator, $client, $lang);

    foreach ($availableMonths as $monthIterator) {
        $monthCanonical = cDate::getCanonicalMonth($monthIterator);
        $monthLink = '<a target="right_bottom" href="' . $sess->url("main.php?area=stat&frame=4&action=stat_show&displaytype=top10&yearmonth=" . $yearIterator . $monthIterator) . '">' . "$monthCanonical" . '</a>';

        $tpl->set('d', 'TEXT', $monthLink);
        $tpl->set('d', 'PADDING_LEFT', '17');
        $tpl->next();
    }
}

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['stat_menu']);
