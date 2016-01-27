<?php

/**
 * This file contains the backend page for displaying log entries.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (!$perm->have_perm_area_action($area)) {
    $notification->displayNotification('error', i18n('Permission denied'));
    return;
}

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
$users = $userColl->getAccessibleUsers(explode(',', $auth->auth['perm']));
$userselect = '<option value="%">' . i18n("All users") . '</option>';
$actions = $actionColl->getAvailableActions();
$actionselect = '<option value="%">' . i18n("All actions") . '</option>';
$clientList = $clientColl->getAccessibleClients();
$idqaction = isset($idqaction) ? $idqaction : "%";

//select current client per default
if (!isset($idqclient)) {
    $idqclient = $client;
}

foreach ($clientList as $key => $value) {
    $selected = (strcmp($idqclient, $key) == 0) ? ' selected="selected"' : '';
    $clientselect .= '<option value="' . $key . '"' . $selected . '>' . $value['name'] . '</option>';
}

foreach ($users as $key => $value) {
    $selected = (strcmp($idquser, $key) == 0) ? ' selected="selected"' : '';
    $userselect .= '<option value="' . $key . '"' . $selected . '>' . $value['username'] . ' (' . $value['realname'] . ')</option>';
}

foreach ($actions as $key => $value) {
    $selected = (strcmp($idqaction, $key) == 0) ? ' selected="selected"' : '';

    // $areaname = $classarea->getAreaName($actionColl->getAreaForAction($value["name"]));
    $areaname = $value["areaname"];
    $actionDescription = $lngAct[$areaname][$value["name"]];

    if ($actionDescription == "") {
        $actionDescription = $value["name"];
    }

    $actionselect .= '<option value="' . $key . '"' . $selected . '>' . $value['name'] . ' (' . $actionDescription . ')</option>';
}

$days = array();
for ($i = 1; $i < 32; $i ++) {
    $days[$i] = $i;
}

$months = array();
for ($i = 1; $i < 13; $i++) {
    $months[$i] = $i;
}

$years = array();
for ($i = 2000; $i < (date('Y') + 1); $i++) {
    $years[$i] = $i;
}


//add language con-561

$sql = "SELECT
*
FROM
".$cfg["tab"]["lang"]." AS A,
".$cfg["tab"]["clients_lang"]." AS B
WHERE
A.idlang=B.idlang AND
B.idclient='".cSecurity::toInteger($client)."'
ORDER BY A.idlang";

$db->query($sql);


$iLangCount = 0;
$aDisplayLangauge = array();
$aDisplayLangauge['%'] = i18n('All languages');
$selectedLangauge = '%';

while ($db->nextRecord()) {
    $aDisplayLangauge[$db->f('idlang')] = $db->f('name');
}

if (array_key_exists($_REQUEST['display_langauge'], $aDisplayLangauge)) {
    $selectedLangauge = $_REQUEST['display_langauge'];
}


$fromday = new cHTMLSelectElement('fromday');
$fromday->autoFill($days);

if ($_REQUEST['fromday'] > 0) {
    $fromday->setDefault($_REQUEST['fromday']);
} else {
    $fromday->setDefault(date('j'));
}
$today = new cHTMLSelectElement('today');
$today->autoFill($days);

if ($_REQUEST['today'] > 0) {
    $today->setDefault($_REQUEST['today']);
} else {
    $today->setDefault(date('j'));
}

$frommonth = new cHTMLSelectElement('frommonth');
$frommonth->autoFill($months);

if ($_REQUEST['frommonth'] > 0) {
    $frommonth->setDefault($_REQUEST['frommonth']);
} else {
    $frommonth->setDefault(date('n'));
}

$tomonth = new cHTMLSelectElement('tomonth');
$tomonth->autoFill($months);

if ($_REQUEST['tomonth'] > 0) {
    $tomonth->setDefault($_REQUEST['tomonth']);
} else {
    $tomonth->setDefault(date('n'));
}

$fromyear = new cHTMLSelectElement('fromyear');
$fromyear->autoFill($years);

if ($_REQUEST['fromyear'] > 0) {
    $fromyear->setDefault($_REQUEST['fromyear']);
} else {
    $fromyear->setDefault(date('Y'));
}

$toyear = new cHTMLSelectElement('toyear');
$toyear->autoFill($years);

if ($_REQUEST['toyear'] > 0) {
    $toyear->setDefault($_REQUEST['toyear']);
} else {
    $toyear->setDefault(date('Y'));
}

$entries = array(
    1   => i18n('Unlimited'),
    10  => '10 '. i18n('Entries'),
    20  => '20 '. i18n('Entries'),
    30  => '30 '. i18n('Entries'),
    50  => '50 '. i18n('Entries'),
    100 => '100 '. i18n('Entries'),
);

$olimit = new cHTMLSelectElement('limit');
$olimit->autoFill($entries);

if (isset($_REQUEST['limit'])) {
    $olimit->setDefault($_REQUEST['limit']);
} else {
    $olimit->setDefault(10);
}

$olangauge = new cHTMLSelectElement('display_langauge');
$olangauge->autoFill($aDisplayLangauge);

if (isset($_REQUEST['display_langauge'])) {
    $olangauge->setDefault($_REQUEST['display_langauge']);
} else  {
    $olangauge->setDefault($belang);
}


$tpl->set('s', 'USERS', $userselect);
$tpl->set('s', 'CLIENTS', $clientselect);
$tpl->set('s', 'ACTION', $actionselect);
$tpl->set('s', 'FROMDAY', $fromday->render());
$tpl->set('s', 'FROMMONTH', $frommonth->render());
$tpl->set('s', 'FROMYEAR', $fromyear->render());
$tpl->set('s', 'TODAY', $today->render());
$tpl->set('s', 'TOMONTH', $tomonth->render());
$tpl->set('s', 'TOYEAR', $toyear->render());
$tpl->set('s', 'LIMIT', $olimit->render());
$tpl->set('s', 'LANGUAGE', $olangauge->render());

$fromdate = $fromyear->getDefault() . '-' . $frommonth->getDefault() . '-' . $fromday->getDefault() . ' 00:00:00';
$todate = $toyear->getDefault() . '-' . $tomonth->getDefault() . '-' . $today->getDefault() . ' 23:59:59';
$limitsql = "";
if ($limit == 1) {
    $limitsql = "";
} else if ($limit == 0) {
    $limitsql = "10";
} else {
    $limitsql = $db->escape($limit);
}

if ($idquser == '%' || $idquser == "") {
    $userarray = array();
    $users = $userColl->getAccessibleUsers(explode(',', $auth->auth['perm']));
    foreach ($users as $key => $value) {
        $userarray[] = $key;
    }
    $uservalues = implode('", "', $userarray);
    $userquery = 'IN ("' . $uservalues . '")';
} else {
    $userquery = "LIKE '" . $idquser . "'";
}

$where = 'user_id ' . $userquery . ' AND idlang LIKE "'.$db->escape($selectedLangauge) .'" AND idaction LIKE "' . $db->escape($idqaction) . '" AND '
    . 'logtimestamp > "' . $db->escape($fromdate) . '" AND logtimestamp < "' . $db->escape($todate) . '" AND '
    . 'idclient LIKE "' . $db->escape($idqclient) . '"';


$actionLogColl = new cApiActionlogCollection();
$result = $actionLogColl->select($where, '', 'logtimestamp DESC', $limitsql);

if (!$result) {
    $noresults = '<tr >'.
                 '<td valign="top" colspan="7">'.i18n("No results").'</td></tr>';
} else {
    $noresults = "";
}

$tpl->set('s', 'NORESULTS', $noresults);

$counter = 0;

$artNames = array();
$strNames = array();



$tpl->set('s', 'LABEL_CLIENT', i18n("Client"));
$tpl->set('s', 'LABEL_LANG', i18n("Language"));
$tpl->set('s', 'LABEL_DATE', i18n("Date"));
$tpl->set('s', 'LABEL_USER', i18n("User"));
$tpl->set('s', 'LABEL_ACTION', i18n("Action"));
$tpl->set('s', 'LABEL_CATEGORY', i18n("Category"));
$tpl->set('s', 'LABEL_ARTICLE', i18n("Article"));

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
    $tpl->set('d', 'RLANG', $aDisplayLangauge[$oItem->get('idlang')]);
    $areaname = $classarea->getAreaName($actionColl->getAreaForAction($oItem->get('idaction')));
    //the conversion of areaname may seem pointless, but it's apparently the only way to get the $langAct[''][*] array entries
    $actionDescription =  $lngAct[($areaname == "") ? "" : $areaname][$actionColl->getActionName($oItem->get('idaction'))];
    if ($actionDescription == '') {
        $actionDescription = $actionColl->getActionName($oItem->get('idaction'));
    }
    $tpl->set('d', 'RACTION', $actionDescription);
    $tpl->set('d', 'RSTR', $strNames[$key]);
    $tpl->set('d', 'RPAGE', $artNames[$key]);

    $tpl->next();
}

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['log_main']);
