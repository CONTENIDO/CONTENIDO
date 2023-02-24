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

$auth = cRegistry::getAuth();
$area = cRegistry::getArea();
$cfg = cRegistry::getConfig();
$db = cRegistry::getDb();
$frame = cRegistry::getFrame();
$sess = cRegistry::getSession();
$perm = cRegistry::getPerm();
$client = cRegistry::getClientId();
$belang = cRegistry::getBackendLanguage();

if (!$perm->have_perm_area_action($area)) {
    $notification->displayNotification('error', i18n('Permission denied'));
    return;
}

$display_language = isset($_REQUEST['display_language']) ? cSecurity::toInteger($_REQUEST['display_language']) : 0;
$fromday = isset($_REQUEST['fromday']) ? cSecurity::toInteger($_REQUEST['fromday']) : 0;
$today = isset($_REQUEST['today']) ? cSecurity::toInteger($_REQUEST['today']) : 0;
$frommonth = isset($_REQUEST['frommonth']) ? cSecurity::toInteger($_REQUEST['frommonth']) : 0;
$tomonth = isset($_REQUEST['tomonth']) ? cSecurity::toInteger($_REQUEST['tomonth']) : 0;
$fromyear = isset($_REQUEST['fromyear']) ? cSecurity::toInteger($_REQUEST['fromyear']) : 0;
$toyear = isset($_REQUEST['toyear']) ? cSecurity::toInteger($_REQUEST['toyear']) : 0;
$limit = isset($_REQUEST['limit']) ? cSecurity::toInteger($_REQUEST['limit']) : 0;
$idquser = isset($_REQUEST['idquser']) ? cSecurity::toString($_REQUEST['idquser']) : '';
$idqclient = isset($_REQUEST['idqclient']) ? cSecurity::toInteger($_REQUEST['idqclient']) : $client;
$idqaction = isset($_REQUEST['idqaction']) ? cSecurity::toInteger($_REQUEST['idqaction']) : '%';

$clientColl = new cApiClientCollection();

$tpl->reset();

$form = '<form name="log_select" method="post" action="'.$sess->url("main.php?").'">
             <input type="hidden" name="area" value="'.$area.'">
             <input type="hidden" name="action" value="log_show">
             <input type="hidden" name="frame" value="'.$frame.'">';

$tpl->set('s', 'FORM', $form);
$tpl->set('s', 'SUBMITTEXT', i18n('Submit query'));
$tpl->set('s', 'CANCELLINK', $sess->url("main.php?area=$area&frame=4"));


$userColl = new cApiUserCollection();
$actionColl = new cApiActionCollection();

$clients = $clientColl->getAccessibleClients();
$clientSelect = '';
$users = $userColl->getAccessibleUsers(explode(',', $auth->auth['perm']));
$userSelect = '<option value="%">' . i18n("All users") . '</option>';
$actions = $actionColl->getAvailableActions();
$actionSelect = '<option value="%">' . i18n("All actions") . '</option>';
$clientList = $clientColl->getAccessibleClients();

foreach ($clientList as $key => $value) {
    $selected = (strcmp($idqclient, $key) == 0) ? ' selected="selected"' : '';
    $clientSelect .= '<option value="' . $key . '"' . $selected . '>' . conHtmlSpecialChars($value['name']) . '</option>';
}

foreach ($users as $key => $value) {
    $selected = (strcmp($idquser, $key) == 0) ? ' selected="selected"' : '';
    $userSelect .= '<option value="' . $key . '"' . $selected . '>' . $value['username'] . ' (' . $value['realname'] . ')</option>';
}

foreach ($actions as $key => $value) {
    $selected = (strcmp($idqaction, $key) == 0) ? ' selected="selected"' : '';

    // $areaname = $classarea->getAreaName($actionColl->getAreaForAction($value["name"]));
    $areaname = $value["areaname"];
    $actionDescription = $lngAct[$areaname][$value["name"]] ?? '';

    if ($actionDescription == "") {
        $actionDescription = $value["name"];
    }

    $actionSelect .= '<option value="' . $key . '"' . $selected . '>' . $value['name'] . ' (' . $actionDescription . ')</option>';
}

$days = [];
for ($i = 1; $i < 32; $i ++) {
    $days[$i] = $i;
}

$months = [];
for ($i = 1; $i < 13; $i++) {
    $months[$i] = $i;
}

$years = [];
$endYear = cSecurity::toInteger(date('Y')) + 1;
for ($i = 2000; $i < $endYear; $i++) {
    $years[$i] = $i;
}


//add language con-561

$sql = "SELECT * FROM " . $cfg["tab"]["lang"] . " AS A, " . $cfg["tab"]["clients_lang"] . " AS B
    WHERE A.idlang = B.idlang AND B.idclient = " . cSecurity::toInteger($client) . " ORDER BY A.idlang";
$db->query($sql);

$iLangCount = 0;
$aDisplayLanguage = [
    '%' => i18n('All languages')
];
$selectedLanguage = '%';

while ($db->nextRecord()) {
    $aDisplayLanguage[$db->f('idlang')] = $db->f('name');
}

if (array_key_exists($display_language, $aDisplayLanguage)) {
    $selectedLanguage = $display_language;
}


$oFromDay = new cHTMLSelectElement('fromday');
$oFromDay->autoFill($days);
$oFromDay->setDefault($fromday > 0 ? $fromday : date('j'));

$oToDay = new cHTMLSelectElement('today');
$oToDay->autoFill($days);
$oToDay->setDefault($today > 0 ? $today : date('j'));

$oFromMonth = new cHTMLSelectElement('frommonth');
$oFromMonth->autoFill($months);
$oToDay->setDefault($frommonth > 0 ? $frommonth : date('n'));

$oToMonth = new cHTMLSelectElement('tomonth');
$oToMonth->autoFill($months);
$oToMonth->setDefault($tomonth > 0 ? $tomonth : date('n'));

$oFromYear = new cHTMLSelectElement('fromyear');
$oFromYear->autoFill($years);
$oFromYear->setDefault($fromyear > 0 ? $fromyear : date('Y'));

$oToYear = new cHTMLSelectElement('toyear');
$oToYear->autoFill($years);
$oToYear->setDefault($toyear > 0 ? $toyear : date('Y'));

$entries = [
    1   => i18n('Unlimited'),
    10  => '10 '. i18n('Entries'),
    20  => '20 '. i18n('Entries'),
    30  => '30 '. i18n('Entries'),
    50  => '50 '. i18n('Entries'),
    100 => '100 '. i18n('Entries'),
];

$oLimitSelect = new cHTMLSelectElement('limit');
$oLimitSelect->autoFill($entries);

if (isset($_REQUEST['limit'])) {
    $oLimitSelect->setDefault($_REQUEST['limit']);
} else {
    $oLimitSelect->setDefault(10);
}

$aDisplayLanguageEscaped = $aDisplayLanguage;
foreach ($aDisplayLanguageEscaped as $id => $displayLanguage) {
    $aDisplayLanguageEscaped[$id] = conHtmlSpecialChars( $displayLanguage);
}
$oLanguage = new cHTMLSelectElement('display_language');
$oLanguage->autoFill($aDisplayLanguageEscaped);

if ($display_language > 0) {
    $oLanguage->setDefault($display_language);
} else  {
    $oLanguage->setDefault($belang);
}


$tpl->set('s', 'USERS', $userSelect);
$tpl->set('s', 'CLIENTS', $clientSelect);
$tpl->set('s', 'ACTION', $actionSelect);
$tpl->set('s', 'FROMDAY', $oFromDay->render());
$tpl->set('s', 'FROMMONTH', $oFromMonth->render());
$tpl->set('s', 'FROMYEAR', $oFromYear->render());
$tpl->set('s', 'TODAY', $oToDay->render());
$tpl->set('s', 'TOMONTH', $oToMonth->render());
$tpl->set('s', 'TOYEAR', $oToYear->render());
$tpl->set('s', 'LIMIT', $oLimitSelect->render());
$tpl->set('s', 'LANGUAGE', $oLanguage->render());

$fromdate = $oFromYear->getDefault() . '-' . $oFromMonth->getDefault() . '-' . $oFromDay->getDefault() . ' 00:00:00';
$todate = $oToYear->getDefault() . '-' . $oToMonth->getDefault() . '-' . $oToDay->getDefault() . ' 23:59:59';
$limitsql = "";
if ($limit == 1) {
    $limitsql = "";
} elseif ($limit == 0) {
    $limitsql = "10";
} else {
    $limitsql = $db->escape($limit);
}

if ($idquser == '%' || $idquser == "") {
    $userarray = [];
    $users = $userColl->getAccessibleUsers(explode(',', $auth->auth['perm']));
    foreach ($users as $key => $value) {
        $userarray[] = $key;
    }
    $uservalues = implode('\', \'', $userarray);
    $userquery = "IN ('" . $uservalues . "')";
} else {
    $userquery = "LIKE '" . $idquser . "'";
}

$where = "user_id " . $userquery . " AND idlang LIKE '".$db->escape($selectedLanguage) ."' AND idaction LIKE '" . $db->escape($idqaction) . "' AND "
    . "logtimestamp > '" . $db->escape($fromdate) . "' AND logtimestamp < '" . $db->escape($todate) . "' AND "
    . "idclient LIKE '" . $db->escape($idqclient) . "'";


$actionLogColl = new cApiActionlogCollection();
$result = $actionLogColl->select($where, '', 'logtimestamp DESC', $limitsql);

if (!$result) {
    $noresults = '<tr >'.
                 '<td class="align_top" colspan="7">'.i18n("No results").'</td></tr>';
} else {
    $noresults = "";
}

$tpl->set('s', 'NORESULTS', $noresults);

$counter = 0;

$artNames = [];
$strNames = [];

$tpl->set('s', 'LABEL_CLIENT', i18n("Client"));
$tpl->set('s', 'LABEL_LANG', i18n("Language"));
$tpl->set('s', 'LABEL_DATE', i18n("Date"));
$tpl->set('s', 'LABEL_USER', i18n("User"));
$tpl->set('s', 'LABEL_ACTION', i18n("Action"));
$tpl->set('s', 'LABEL_CATEGORY', i18n("Category"));
$tpl->set('s', 'LABEL_ARTICLE', i18n("Article"));

$oCategoryArticle = null;
while ($oItem = $actionLogColl->next()) {
    $counter++;

    $idcatart = $oItem->get('idcatart');
    $idlang = $oItem->get('idlang');
    $key = $idcatart . '_' . $idlang;

    if (!isset($strNames[$key]) || !isset($artNames[$key])) {
        // instantiate category article object, we need it later
        $oCategoryArticle = new cApiCategoryArticle($idcatart);
    }

    // get structure id and name
    if (!isset($strNames[$key])) {
        $strNames[$key] = '';
        if ($oCategoryArticle->get('idcat')) {
            $oCategoryLanguage = new cApiCategoryLanguage();
            $oCategoryLanguage->loadByCategoryIdAndLanguageId($oCategoryArticle->get('idcat'), $idlang);
            if ($oCategoryLanguage->isLoaded()) {
                $strNames[$key] = $oCategoryLanguage->get('name')  . " (" . $oCategoryLanguage->get('idcat') . ")";
            }
        }
        if ($strNames[$key] == '') {
            $strNames[$key] = '-';
        }
    }

    // get article id and name
    if (!isset($artNames[$key])) {
        $artNames[$key] = '';
        if ($oCategoryArticle->get('idart')) {
            $oArticleLanguage = new cApiArticleLanguage();
            $oArticleLanguage->loadByArticleAndLanguageId($oCategoryArticle->get('idart'), $idlang);
            if ($oArticleLanguage->isLoaded()) {
                $artNames[$key] = $oArticleLanguage->get('title') . " (" . $oArticleLanguage->get('idart') . ")";
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
    $tpl->set('d', 'RLANG', $aDisplayLanguage[$oItem->get('idlang')]);
    $areaName = $classarea->getAreaName($actionColl->getAreaForAction($oItem->get('idaction')));
    $actionName = $actionColl->getActionName($oItem->get('idaction'));
    //the conversion of areaname may seem pointless, but it's apparently the only way to get the $langAct[''][*] array entries
    $actionDescription = isset($lngAct[$areaName][$actionName]) ? $lngAct[$areaName][$actionName] : '';
    if ($actionDescription == '') {
        $actionDescription = $actionName;
    }
    $tpl->set('d', 'RACTION', $actionDescription);
    $tpl->set('d', 'RSTR', $strNames[$key]);
    $tpl->set('d', 'RPAGE', $artNames[$key]);

    $tpl->next();
}

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['log_main']);
