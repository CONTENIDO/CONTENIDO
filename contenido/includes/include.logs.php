<?php

/**
 * This file contains the backend page for displaying log entries.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $notification, $tpl, $lngAct, $classarea;

// Display critical error if client or language does not exist
$client = cSecurity::toInteger(cRegistry::getClientId());
$lang = cSecurity::toInteger(cRegistry::getLanguageId());
if (($client < 1 || !cRegistry::getClient()->isLoaded()) || ($lang < 1 || !cRegistry::getLanguage()->isLoaded())) {
    $message = $client && !cRegistry::getClient()->isLoaded() ? i18n('No Client selected') : i18n('No language selected');
    $oPage = new cGuiPage("mod_overview");
    $oPage->displayCriticalError($message);
    $oPage->render();
    return;
}

$auth = cRegistry::getAuth();
$area = cRegistry::getArea();
$cfg = cRegistry::getConfig();
$db = cRegistry::getDb();
$frame = cRegistry::getFrame();
$sess = cRegistry::getSession();
$perm = cRegistry::getPerm();
$belang = cRegistry::getBackendLanguage();

if (!$perm->have_perm_area_action($area)) {
    $notification->displayNotification('error', i18n('Permission denied'));
    return;
}

$languageId = $_REQUEST['idqlanguage'] ?? '%';
$fromDay = cSecurity::toInteger($_REQUEST['fromday'] ?? '0');
$toDay = cSecurity::toInteger($_REQUEST['today'] ?? '0');
$fromMonth = cSecurity::toInteger($_REQUEST['frommonth'] ?? '0');
$toMonth = cSecurity::toInteger($_REQUEST['tomonth'] ?? '0');
$fromYear = cSecurity::toInteger($_REQUEST['fromyear'] ?? '0');
$toYear = cSecurity::toInteger($_REQUEST['toyear'] ?? '');
$limit = $_REQUEST['limit'] ?? 10;
$userId = cSecurity::toString($_REQUEST['idquser'] ?? '%');
$clientId = cSecurity::toInteger($_REQUEST['idqclient'] ?? $client);
$actionId = $_REQUEST['idqaction'] ?? '%';

if ($languageId !== '%') {
    $languageId = cSecurity::toInteger($languageId);
}
if ($limit !== '%') {
    $limit = cSecurity::toInteger($limit);
}
if ($actionId !== '%') {
    $actionId = cSecurity::toInteger($actionId);
}

$clientColl = new cApiClientCollection();

$userColl = new cApiUserCollection();
$accessibleUsers = $userColl->getAccessibleUsers(explode(',', $auth->auth['perm']));

$actionColl = new cApiActionCollection();

// Clients select
$clientList = $clientColl->getAccessibleClients();
$clientSelect = new cHTMLSelectElement('idqclient');
foreach ($clientList as $key => $value) {
    $_idClient = cSecurity::toInteger($key);
    $option = new cHTMLOptionElement(conHtmlSpecialChars($value['name']), $_idClient);
    if ($clientId === $_idClient) {
        $option->setSelected(true);
    }
    $clientSelect->addOptionElement($_idClient, $option);
}

// Users select
$users = ['%' => i18n("All users")] + $accessibleUsers;
$userSelect = new cHTMLSelectElement('idquser');
foreach ($users as $key => $value) {
    $_userId = $key;
    $_name = $key === '%' ? $value : $value['username'] . ' (' . $value['realname'] . ')';
    $option = new cHTMLOptionElement(conHtmlSpecialChars($_name), $_userId);
    if ($userId === $_userId) {
        $option->setSelected(true);
    }
    $userSelect->addOptionElement($_userId, $option);
}

// Actions select
$actions = ['%' => i18n("All actions")] + $actionColl->getAvailableActions();
$actionSelect = new cHTMLSelectElement('idqaction');
foreach ($actions as $key => $value) {
    if ($key === '%') {
        $_idAction = $key;
        $_name = $value;
    } else {
        $_idAction = cSecurity::toInteger($key);
        $_name = $value['areaname'] ?? '';
        $_name = $_name . ' (' . ($lngAct[$_name][$value['name']] ?? $value['name']) . ')';
    }
    $option = new cHTMLOptionElement($_name, $_idAction);
    if ($actionId === $_idAction) {
        $option->setSelected(true);
    }
    $actionSelect->addOptionElement($_idAction, $option);
}

// Selects for days, months and years
$days = [];
foreach (range(1, cDate::MAX_DAY_VALUE) as $value) {
    $days[$value] = cDate::padDay(cSecurity::toString($value));
}

$months = [];
foreach (range(1, cDate::MAX_MONTH_VALUE) as $value) {
    $months[$value] = cDate::padMonth(cSecurity::toString($value));
}

$years = [];
$endYear = cSecurity::toInteger(date('Y')) + 1;
for ($i = 2000; $i < $endYear; $i++) {
    $years[$i] = $i;
}

$fromDaySelect = new cHTMLSelectElement('fromday');
$fromDaySelect->autoFill($days);
$fromDaySelect->setDefault($fromDay > 0 ? $fromDay : date('j'));

$toDaySelect = new cHTMLSelectElement('today');
$toDaySelect->autoFill($days);
$toDaySelect->setDefault($toDay > 0 ? $toDay : date('j'));

$fromMonthSelect = new cHTMLSelectElement('frommonth');
$fromMonthSelect->autoFill($months);
$fromMonthSelect->setDefault($fromMonth > 0 ? $fromMonth : date('n'));

$toMonthSelect = new cHTMLSelectElement('tomonth');
$toMonthSelect->autoFill($months);
$toMonthSelect->setDefault($toMonth > 0 ? $toMonth : date('n'));

$fromYearSelect = new cHTMLSelectElement('fromyear');
$fromYearSelect->autoFill($years);
$fromYearSelect->setDefault($fromYear > 0 ? $fromYear : date('Y'));

$toYearSelect = new cHTMLSelectElement('toyear');
$toYearSelect->autoFill($years);
$toYearSelect->setDefault($toYear > 0 ? $toYear : date('Y'));

// Number of actions select
$entries = [
    '%' => i18n('Unlimited'),
    10  => '10 '. i18n('Entries'),
    20  => '20 '. i18n('Entries'),
    30  => '30 '. i18n('Entries'),
    50  => '50 '. i18n('Entries'),
    100 => '100 '. i18n('Entries'),
];
if (!isset($entries[$limit])) {
    $limit = 10;
}
$limitSelect = new cHTMLSelectElement('limit');
$limitSelect->autoFill($entries);
$limitSelect->setDefault($limit);

// Language select
$clientLanguageColl = new cApiClientLanguageCollection();
$aDisplayLanguage = ['%' => i18n('All languages')]
    + $clientLanguageColl->getLanguageNamesByClient($client);
if (!isset($aDisplayLanguage[$languageId])) {
    $languageId = '%';
}

$aDisplayLanguageEscaped = $aDisplayLanguage;
foreach ($aDisplayLanguageEscaped as $id => $name) {
    $aDisplayLanguageEscaped[$id] = conHtmlSpecialChars($name);
}
$languageSelect = new cHTMLSelectElement('idqlanguage');
$languageSelect->autoFill($aDisplayLanguageEscaped);
$languageSelect->setDefault($languageId);

// Build query
$where = [];

$where[] = '`idclient` = ' . $clientId;
if ($languageId !== '%') {
    $where[] = '`idlang` = ' . $languageId;
}
if ($actionId !== '%') {
    $where[] =  '`idaction` = ' . $actionId;
}
if ($userId == '%' || $userId == "") {
    $userValues = implode("', '", array_keys($accessibleUsers));
    $where[] = "`user_id` IN ('" . $userValues . "')";
} else {
    $where[] = "`user_id` = '" . $userId . "'";
}

$fromDate = sprintf('%s-%s-%s 00:00:00', $fromYearSelect->getDefault(), $fromMonthSelect->getDefault(), $fromDaySelect->getDefault());
$where[] = "`logtimestamp` > '" . $fromDate . "'";

$toDate = sprintf('%s-%s-%s 23:59:59', $toYearSelect->getDefault(), $toMonthSelect->getDefault(), $toDaySelect->getDefault());
$where[] = "`logtimestamp` < '" . $toDate . "'";

$where = implode(' AND ', $where);
$limitSql = ($limit === '%') ? '' : $limit;
$actionLogColl = new cApiActionlogCollection();
$result = $actionLogColl->select($where, '', '`logtimestamp` DESC', $limitSql);

// Set 'no results' message when nothing was found
if (!$result) {
    $noResultsRow = '<tr>'
        . '<td class="align_top" colspan="7">'. i18n("No results") . '</td>'
        . '</tr>';
} else {
    $noResultsRow = '';
}

$tpl->reset();

$form = '<form name="log_select" method="post" action="' . $sess->url('main.php?') . '">
             <input type="hidden" name="area" value="' . $area . '">
             <input type="hidden" name="action" value="log_show">
             <input type="hidden" name="frame" value="' . $frame . '">';

$tpl->set('s', 'FORMSTART', $form);
$tpl->set('s', 'SUBMITTEXT', i18n('Submit query'));
$tpl->set('s', 'CANCELLINK', $sess->url("main.php?area=$area&frame=4"));

$tpl->set('s', 'LABEL_CLIENT', i18n("Client"));
$tpl->set('s', 'LABEL_LANG', i18n("Language"));
$tpl->set('s', 'LABEL_DATE', i18n("Date"));
$tpl->set('s', 'LABEL_USER', i18n("User"));
$tpl->set('s', 'LABEL_ACTION', i18n("Action"));
$tpl->set('s', 'LABEL_CATEGORY', i18n("Category"));
$tpl->set('s', 'LABEL_ARTICLE', i18n("Article"));

$tpl->set('s', 'USERS', $userSelect->render());
$tpl->set('s', 'CLIENTS', $clientSelect->render());
$tpl->set('s', 'ACTION', $actionSelect->render());
$tpl->set('s', 'FROMDAY', $fromDaySelect->render());
$tpl->set('s', 'FROMMONTH', $fromMonthSelect->render());
$tpl->set('s', 'FROMYEAR', $fromYearSelect->render());
$tpl->set('s', 'TODAY', $toDaySelect->render());
$tpl->set('s', 'TOMONTH', $toMonthSelect->render());
$tpl->set('s', 'TOYEAR', $toYearSelect->render());
$tpl->set('s', 'LIMIT', $limitSelect->render());
$tpl->set('s', 'LANGUAGE', $languageSelect->render());

$tpl->set('s', 'NORESULTS', $noResultsRow);

$counter = 0;
$artNames = [];
$strNames = [];
$oCategoryArticle = null;
while ($oItem = $actionLogColl->next()) {
    $counter++;

    $idcatart = $oItem->get('idcatart');
    $idlang = cSecurity::toInteger($oItem->get('idlang'));
    $key = $idcatart . '_' . $idlang;

    if (!isset($strNames[$key]) || !isset($artNames[$key])) {
        // Instantiate category article object, we need it later
        $oCategoryArticle = new cApiCategoryArticle($idcatart);
    }

    // Get structure id and name
    if (!isset($strNames[$key])) {
        $strNames[$key] = '';
        if ($oCategoryArticle->get('idcat')) {
            $oCategoryLanguage = new cApiCategoryLanguage();
            $oCategoryLanguage->loadByCategoryIdAndLanguageId($oCategoryArticle->get('idcat'), $idlang);
            if ($oCategoryLanguage->isLoaded()) {
                $strNames[$key] = $oCategoryLanguage->get('name')
                    . ' (' . $oCategoryLanguage->get('idcat') . ')';
            }
        }
        if ($strNames[$key] == '') {
            $strNames[$key] = '-';
        }
    }

    // Get article id and name
    if (!isset($artNames[$key])) {
        $artNames[$key] = '';
        if ($oCategoryArticle->get('idart')) {
            $oArticleLanguage = new cApiArticleLanguage();
            $oArticleLanguage->loadByArticleAndLanguageId($oCategoryArticle->get('idart'), $idlang);
            if ($oArticleLanguage->isLoaded()) {
                $artNames[$key] = $oArticleLanguage->get('title')
                    . ' (' . $oArticleLanguage->get('idart') . ')';
            }
        }
        if ($artNames[$key] == '') {
            $artNames[$key] = '-';
        }
    }

    $tpl->set('d', 'ROWNAME', 'row_' . $counter);
    $tpl->set('d', 'RCLIENT', $clientList[$oItem->get('idclient')]['name']);
    $tpl->set('d', 'RDATETIME', $oItem->get('logtimestamp'));
    $tpl->set('d', 'RUSER' , $users[$oItem->get('user_id')]['username']);
    $tpl->set('d', 'RLANG', $aDisplayLanguage[$idlang] ?? $idlang);
    $areaName = $classarea->getAreaName($actionColl->getAreaForAction($oItem->get('idaction')));
    $actionName = $actionColl->getActionName($oItem->get('idaction'));
    // The conversion of areaname may seem pointless, but it's apparently the
    // only way to get the $langAct[''][*] array entries
    $actionDescription = $lngAct[$areaName][$actionName] ?? '';
    if ($actionDescription == '') {
        $actionDescription = $actionName;
    }
    $tpl->set('d', 'RACTION', $actionDescription);
    $tpl->set('d', 'RSTR', $strNames[$key]);
    $tpl->set('d', 'RPAGE', $artNames[$key]);

    $tpl->next();
}

$tpl->set('s', 'FORMEND', '</form>');

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['log_main']);
