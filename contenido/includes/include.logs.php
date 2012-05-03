<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Display log entries
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.7
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  2003-05-09
 *   modified 2008-06-16, Holger Librenz, Hotfix: added check for invalid calls
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2009-10-15, Dominik Ziegler, fetching areaname from actions array to save a lot of database queries
 *   modified 2009-11-06, Murat Purc, replaced deprecated functions (PHP 5.3 ready)
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


if (!$perm->have_perm_area_action($area)) {
    $notification->displayNotification('error', i18n('Permission denied'));
    return;
}

$clientColl = new cApiClientCollection();



$tpl->reset();

$form = '<form name="log_select" method="post" action="'.$sess->url("main.php?").'">
             '.$sess->hidden_session().'
             <input type="hidden" name="area" value="'.$area.'">
             <input type="hidden" name="action" value="log_show">
             <input type="hidden" name="frame" value="'.$frame.'">';

$tpl->set('s', 'FORM', $form);
$tpl->set('s', 'BORDERCOLOR', $cfg['color']['table_border']);
$tpl->set('s', 'SELECTBGCOLOR', $cfg['color']['table_dark']);
$tpl->set('s', 'SELECTBBGCOLOR', $cfg['color']['table_light']);
$tpl->set('s', 'HEADERBGCOLOR', $cfg['color']['table_header']);
$tpl->set('s', 'RHEADERBGCOLOR', $cfg['color']['table_header']);
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

foreach ($clientList as $key => $value) {
    $selected = (strcmp($idqclient, $key) == 0) ? ' selected="selected"' : '';
    $clientselect .= '<option value="' . $key . '"' . $selected . '>' . $value['name'] . '</option>';
}

foreach ($users as $key => $value) {
    $selected = (strcmp($idquser, $key) == 0) ? ' selected="selected"' : '';
    $userselect .= '<option value="' . $key . '"' . $selected . '>' . $value['username'] . '(' . $value['realname'] . ')</option>';
}

foreach ($actions as $key => $value) {
    $selected = (strcmp($idqaction, $key) == 0) ? ' selected="selected"' : '';

    // $areaname = $classarea->getAreaName($actionColl->getAreaForAction($value["name"]));
    $areaname = $value["areaname"];
    $actionDescription = $lngAct[$areaname][$value["name"]];

    if ($actionDescription == "") {
        $actionDescription = $value["name"];
    }

    $actionselect .= '<option value="' . $key . '"' . $selected . '>' . $value['name'] . '(' . $actionDescription . ')</option>';
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

$aAvailableLangs = i18nGetAvailableLanguages();
$aDisplayLangauge = array();
foreach ($aAvailableLangs as $sCode => $aEntry) {
	if (isset($cfg['login_languages'])) {
		if (in_array($sCode, $cfg['login_languages'])) {
			list($sLanguage, $sCountry, $sCodeSet, $sAcceptTag) = $aEntry;
			$aDisplayLangauge[$sCode] = $sLanguage.' ('.$sCountry.')';
			
		}
	} 
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
    0   => i18n('Unlimited'),
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

if(isset($_REQUEST['display_langauge'])) {
	$olangauge->setDefault($_REQUEST['display_langauge']);	
}else  {
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
$limitsql = ($limit == 0) ? '' : $db->escape($limit);

if ($idquser == '%') {
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

$where = 'user_id ' . $userquery . ' AND idaction LIKE "' . $db->escape($idqaction) . '" AND '
    . 'logtimestamp > "' . $db->escape($fromdate) . '" AND logtimestamp < "' . $db->escape($todate) . '" AND '
    . 'idclient LIKE "' . $db->escape($idqclient) . '"';

$actionLogColl = new cApiActionlogCollection();
$result = $actionLogColl->select($where, '', 'logtimestamp DESC', $limitsql);

if (!$result) {
    $noresults = '<tr class="text_medium" style="background-color:'.$bgcolor.';" >'.
                 '<td valign="top" colspan="6" style="border:0;border-top:1px;border-right:1px;border-color:'.$cfg["color"]["table_border"].';border-style:solid;">'.i18n("No results").'</td></tr>';
} else {
    $noresults = "";
}

$tpl->set('s', 'NORESULTS', $noresults);

$counter = 0;

$artNames = array();
$strNames = array();



//Set manual the language
$saveBelang = $belang;
if(isset($_REQUEST['display_langauge'])) {
	$belang = $_REQUEST['display_langauge'];
}
$GLOBALS['belang'] = $_conI18n['language'] = $belang;
unset($_conI18n['cache']);
unset($_conI18n['files']);
//load action strings
cInclude("includes", "cfg_actions.inc.php", true);




$tpl->set('s', 'LABEL_CLIENT', i18n("Client"));
$tpl->set('s', 'LABEL_DATE', i18n("Date"));
$tpl->set('s', 'LABEL_USER', i18n("User"));
$tpl->set('s', 'LABEL_ACTION', i18n("Action"));
$tpl->set('s', 'LABEL_CATEGORY', i18n("Category"));
$tpl->set('s', 'LABEL_ARTICLE', i18n("Article"));

while ($oItem = $actionLogColl->next()) {
    $counter++;
    $darkrow = !$darkrow;
    $bgcolor = ($darkrow) ? $cfg['color']['table_dark'] : $cfg['color']['table_light'];

    $idcatart = $oItem->get('idcatart');
    $idlang = $oItem->get('idlang');
    $key = $idcatart . '_' . $idlang;

    if (!isset($strNames[$key]) || !isset($artNames[$key])) {
        // instantiate category article object, we need it later
        $oCategoryArticle = new cApiCategoryArticle($idcatart);
    }

    // get structure name
    if (!isset($strNames[$key])) {
        $strNames[$key] = '';
        if ($oCategoryArticle->get('idcat')) {
            $oCategoryLanguage = new cApiCategoryLanguage();
            $oCategoryLanguage->loadByCategoryIdAndLanguageId($oCategoryArticle->get('idcat'), $idlang);
            if ($oCategoryLanguage->isLoaded()) {
                $strNames[$key] = $oCategoryLanguage->get('name');
            }
        }
        if ($strNames[$key] == '') {
            $strNames[$key] = '-';
        }
    }

    // get article name
    if (!isset($artNames[$key])) {
        $artNames[$key] = '';
        if ($oCategoryArticle->get('idart')) {
            $oArticleLanguage = new cApiArticleLanguage();
            $oArticleLanguage->loadByArticleAndLanguageId($oCategoryArticle->get('idart'), $idlang);
            if ($oArticleLanguage->isLoaded()) {
                $artNames[$key] = $oArticleLanguage->get('title');
            }
        }
        if ($artNames[$key] == '') {
            $artNames[$key] = '-';
        }
    }

    $tpl->set('d', 'ROWNAME', 'row_' . $counter);
    $tpl->set('d', 'BORDERCOLOR', $cfg['color']['table_border']);
    $tpl->set('d', 'RBGCOLOR', $bgcolor);
    $tpl->set('d', 'RCLIENT', $clientList[$oItem->get('idclient')]['name']);
    $tpl->set('d', 'RDATETIME', $oItem->get('logtimestamp'));
    $tpl->set('d', 'RUSER' , $users[$oItem->get('user_id')]['username']);
    $areaname = $classarea->getAreaName($actionColl->getAreaForAction($oItem->get('idaction')));
    $actionDescription =  $lngAct[$areaname][$actionColl->getActionName($oItem->get('idaction'))];
    if ($actionDescription == '') {
        $actionDescription = $actionColl->getActionName($oItem->get('idaction'));
    }
    $tpl->set('d', 'RACTION', $actionDescription);
    $tpl->set('d', 'RSTR', $strNames[$key]);
    $tpl->set('d', 'RPAGE', $artNames[$key]);

    $tpl->next();
}


//set/reset to selected language
$GLOBALS['belang'] = $_conI18n['language'] = $saveBelang;
unset($_conI18n['cache']);
unset($_conI18n['files']);
//load action strings 
cInclude("includes", "cfg_actions.inc.php", true);

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['log_main']);

 
?>