<?php
/**
 * This file contains the backend page for the backend start page known as "My CONTENIDO".
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Jan Lengowski
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$page = new cGuiPage("mycontenido", "", "0");

$vuser = new cApiUser($auth->auth['uid']);

if ($saveLoginTime == true) {
    $sess->register('saveLoginTime');
    $saveLoginTime = 0;

    $lastTime = $vuser->getUserProperty('system', 'currentlogintime');
    $timestamp = date('Y-m-d H:i:s');
    $vuser->setUserProperty('system', 'currentlogintime', $timestamp);
    $vuser->setUserProperty('system', 'lastlogintime', $lastTime);
}

$lastlogin = displayDatetime($vuser->getUserProperty('system', 'lastlogintime'));
if ($lastlogin == '') {
    $lastlogin = i18n('No Login Information available.');
}

// notification for requested password
$aNotificationText = array();
if ($vuser->getField('using_pw_request') == 1) {
    $page->displayWarning(i18n("You're logged in with a temporary password. Please change your password."));
}

// check for active maintenance mode
if (getSystemProperty('maintenance', 'mode') == 'enabled') {
    $page->displayWarning(i18n('CONTENIDO is in maintenance mode. Only sysadmins are allowed to login.'));
}

// Check, if setup folder is still available
if (cFileHandler::exists(dirname(dirname(dirname(__FILE__))) . '/setup')) {
    $page->displayWarning(i18n("The setup directory still exists. Please remove the setup directory before you continue."));
}

// check for size of log directory
$max_log_size = getSystemProperty('backend', 'max_log_size');
if ($max_log_size === false) {
    $max_log_size = 10;
}
if (in_array('sysadmin', explode(',', $vuser->getEffectiveUserPerms())) && $max_log_size > 0) {
    $log_size = getDirectorySize($cfg['path']['contenido_logs']);
    if ($log_size > $max_log_size * 1024 * 1024) {
        $page->displayWarning(i18n('The log directory is bigger than') . ' ' . humanReadableSize($max_log_size * 1024 * 1024) . '. ' . i18n('Current size') . ': ' . humanReadableSize($log_size));
    }
}

//check for data in the old data folders
$foldersToCheck = array($cfg["path"]["frontend"]."/contenido/logs", $cfg["path"]["frontend"]."/contenido/temp");
if(is_array($cfgClient)) {
    foreach ($cfgClient as $iclient => $aclient) {
        if (!is_numeric($iclient)) {
            continue;
        }
        $foldersToCheck[] = $cfgClient[$iclient]['path']['frontend']."layouts";
        $foldersToCheck[] = $cfgClient[$iclient]['path']['frontend']."logs";
    }
}
$faultyFolders = array();
foreach ($foldersToCheck as $folder) {
    $handle = @opendir($folder);
    if ($handle != false) {
        $faultyFolders[] = $folder;
    }
}
foreach ($faultyFolders as $folder) {
    if (in_array("sysadmin", explode(",", $vuser->getEffectiveUserPerms()))) {
        $page->displayWarning(sprintf(i18n("The folder located at %s contains data but it's no longer needed. You can delete it."), $folder));
    }
}

$userid = $auth->auth['uid'];

$page->set('s', 'WELCOME', '<b>' . i18n('Welcome') . ' </b>' . ($vuser->getRealname($userid, true) ? $vuser->getRealname($userid, true) : $vuser->getUserName($userid, true)) . '.');
$page->set('s', 'LASTLOGIN', i18n('Last login') . ': ' . $lastlogin);

$clientCollection = new cApiClientCollection();
$clients = $clientCollection->getAccessibleClients();

$cApiClient = new cApiClient();

if (count($clients) > 1) {
    $select = new cHTMLSelectElement('changeclient');
    $select->setClass("vAlignMiddle");
    $choices = array();
    $warnings = array();

    foreach ($clients as $key => $v_client) {
        if ($perm->hasClientPermission($key)) {
            $cApiClient->loadByPrimaryKey($key);
                $choices[$key] = $v_client['name'] . ' (' . $key . ')';
        }
    }

    $select->autoFill($choices);
    $select->setDefault($client);

    $clientselect = $select->render();

    $page->set('s', 'CLIENTSDROPDOWN', $clientselect);

    if ($perm->have_perm() && count($warnings) > 0) {
        $page->displayWarning(implode('<br>', $warnings));
    }
    $page->set('s', 'OKBUTTON', '<input class="vAlignMiddle" type="image" src="images/but_ok.gif" alt="' . i18n('Change client') . '" title="' . i18n('Change client') . '" border="0">');
} else {
    $page->set('s', 'OKBUTTON', '');
    $name = '';
    if (count($clients) == 0) {
        $name = i18n('No clients available!');
    }

    foreach ($clients as $key => $v_client) {
        if ($perm->hasClientPermission($key)) {
            $cApiClient->loadByPrimaryKey($key);
            if ($cApiClient->hasLanguages()) {
                $name = $v_client['name'] . ' (' . $key . ')';
            } else {
                $warnings[] = sprintf(i18n('Client %s (%s) has no languages'), $v_client['name'], $key);
            }
        }
    }

    if ($perm->have_perm() && count($warnings) > 0) {
        $page->displayWarning(implode('<br>', $warnings));
    }

    $page->set('s', 'CLIENTSDROPDOWN', $name);
}

$props = new cApiPropertyCollection();
$props->select("itemtype = 'idcommunication' AND idclient = " . (int) $client . " AND type = 'todo' AND name = 'status' AND value != 'done'");

$todoitems = array();

while ($prop = $props->next()) {
    $todoitems[] = $prop->get('itemid');
}

if (count($todoitems) > 0) {
    $in = 'idcommunication IN (' . implode(',', $todoitems) . ')';
} else {
    $in = 1;
}
$todoitems = new TODOCollection();
$recipient = $auth->auth['uid'];
$todoitems->select("recipient = '$recipient' AND idclient = " . (int) $client . " AND $in");

while ($todo = $todoitems->next()) {
    if ($todo->getProperty('todo', 'status') != 'done') {
        $todoitems++;
    }
}

$sTaskTranslation = '';
if ($todoitems->count() == 1) {
    $sTaskTranslation = i18n('Reminder list: %d Task open');
} else {
    $sTaskTranslation = i18n('Reminder list: %d Tasks open');
}

$mycontenido_overview = '<a class="blue" href="' . $sess->url("main.php?area=mycontenido&frame=4") . '">' . i18n('Overview') . '</a>';
$mycontenido_lastarticles = '<a class="blue" href="' . $sess->url("main.php?area=mycontenido_recent&frame=4") . '">' . i18n('Recently edited articles') . '</a>';
$mycontenido_tasks = '<a class="blue" href="' . $sess->url("main.php?area=mycontenido_tasks&frame=4") . '">' . sprintf($sTaskTranslation, $todoitems->count()) . '</a>';
$mycontenido_settings = '<a class="blue" href="' . $sess->url("main.php?area=mycontenido_settings&frame=4") . '">' . i18n('Settings') . '</a>';

$page->set('s', 'MYCONTENIDO_OVERVIEW', $mycontenido_overview);
$page->set('s', 'MYCONTENIDO_LASTARTICLES', $mycontenido_lastarticles);
$page->set('s', 'MYCONTENIDO_TASKS', $mycontenido_tasks);
$page->set('s', 'MYCONTENIDO_SETTINGS', $mycontenido_settings);

// Systemadmins list
$sOutputAdmin = '';
$userColl = new cApiUserCollection();
$admins = $userColl->fetchSystemAdmins(true);

foreach ($admins as $pos => $item) {
    if ($item->get('email') != '') {
        $sAdminName = $item->get('realname') ? $item->get('realname') : $item->get('username');
        $sAdminEmail = '<a class="blue" href="mailto:' . $item->get('email') . '">' . $item->get('email') . '</a>';
        $li = '<li class="welcome">';
        if ($sAdminName !== '' && $sAdminEmail !== '') {
            $li .= $sAdminName . ', ' . $sAdminEmail . '</li>';
        } else if ($sAdminName === '' && $sAdminEmail !== '') {
            $li .= $sAdminEmail . '</li>';
        } else if ($sAdminName !== '' && $sAdminEmail === '') {
            $li .= $sAdminName . '</li>';
        } else {
            $li = '';
        }
        $sOutputAdmin .= $li;
    }
}

$page->set('s', 'ADMIN_EMAIL', $sOutputAdmin);

// For display current online user in CONTENIDO-Backend
$aMemberList = array();
$oActiveUsers = new cApiOnlineUserCollection();
$iNumberOfUsers = 0;

// Start()
$oActiveUsers->startUsersTracking();

// Currently User Online
$iNumberOfUsers = $oActiveUsers->getNumberOfUsers();

// Find all User who is online
$aMemberList = $oActiveUsers->findAllUser();

// Template to display current user
$sOutput = '';
foreach ($aMemberList as $key) {
    $sRealName = $key['realname'] ? $key['realname'] : $key['username'];
    $aPerms['0'] = $key['perms'];
    $li = '';
    if ('' !== $sRealName) {
        $li .= $sRealName;
    }
    if ('' !== $aPerms['0']) {
        $li .= strlen($li) ? ', ' : '';
        $li .= $aPerms['0'];
    }
    if (0 == strlen($li)) {
        continue;
    }
    $sOutput .= '<li class="welcome">' . $li . '</li>';
}

// set template welcome
$page->set('s', 'USER_ONLINE', $sOutput);
$page->set('s', 'NUMBER', $iNumberOfUsers);

// check for new updates
$oUpdateNotifier = new cUpdateNotifier($cfg, $vuser, $perm, $sess, $belang);
$sUpdateNotifierOutput = $oUpdateNotifier->displayOutput();
try {
	$page->set('s', 'UPDATENOTIFICATION', mb_convert_encoding($sUpdateNotifierOutput, cRegistry::getLanguage()->get('encoding')));
} catch (cInvalidArgumentException $e) {
	$page->set('s', 'UPDATENOTIFICATION', $sUpdateNotifierOutput);
}

$page->render();
