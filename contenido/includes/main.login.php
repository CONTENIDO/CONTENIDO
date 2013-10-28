<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Contenido Start Screen
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend includes
 * @version    1.0.5
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created 2003-01-21
 *   modified 2008-06-26, Dominik Ziegler, update notifier class added
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2009-12-14, Dominik Ziegler, use User::getRealname() for user name output and provide username fallback
 *   modified 2010-05-20, Oliver Lohkemper, add param true for get active admins
 *   modified 2011-01-28, Dominik Ziegler, added missing notice in backend home when no clients are available [#CON-379]
 *   modified 2011-08-18, Dominik Ziegler, added notification if maintenance mode is active [#CON-403]
 *
 *   $Id: main.login.php 1268 2011-01-28 12:18:44Z dominik.ziegler $:
 * }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$tpl->reset();

cInclude("pear", "XML/Parser.php");
cInclude("pear", "XML/RSS.php");

if ($saveLoginTime == true) {
	$sess->register("saveLoginTime");
	$saveLoginTime= 0;

	$vuser= new User();

	$vuser->loadUserByUserID($auth->auth["uid"]);

	$lastTime= $vuser->getUserProperty("system", "currentlogintime");
	$timestamp= date("Y-m-d H:i:s");
	$vuser->setUserProperty("system", "currentlogintime", $timestamp);
	$vuser->setUserProperty("system", "lastlogintime", $lastTime);

}

$vuser= new User();
$vuser->loadUserByUserID($auth->auth["uid"]);
$lastlogin= $vuser->getUserProperty("system", "lastlogintime");

if ($lastlogin == "") {
	$lastlogin= i18n("No Login Information available.");
}

// notification for requested password
if($vuser->getField('using_pw_request') == 1) {
    $sPwNoti = $notification->returnNotification("warning", i18n("You're logged in with a temporary password. Please change your password."));
} else {
    $sPwNoti = '';
}
$tpl->set('s', 'NOTIFICATION', $sPwNoti);

// notification for requested password
$sNotificationText = '';
if($vuser->getField('using_pw_request') == 1) {
    $sNotificationText = $notification->returnNotification("warning", i18n("You're logged in with a temporary password. Please change your password."));
	$sNotificationText .= '<br />';
}

// check for active maintenance mode
if (getSystemProperty('maintenance', 'mode') == 'enabled') {
	$sNotificationText .= $notification->returnNotification("warning", i18n("Contenido is in maintenance mode. Only sysadmins are allowed to login."));
	$sNotificationText .= '<br />';
}

$tpl->set('s', 'NOTIFICATION', $sNotificationText);

$userid = $auth->auth["uid"];

$tpl->set('s', 'WELCOME', "<b>" . i18n("Welcome") . " </b>" . $vuser->getRealname($userid, true) . ".");
$tpl->set('s', 'LASTLOGIN', i18n("Last login") . ": " . $lastlogin);

$clients= $classclient->getAccessibleClients();

$cApiClient= new cApiClient;

if (count($clients) > 1) {

	$clientform= '<form style="margin: 0px" name="clientselect" method="post" target="_top" action="' . $sess->url("index.php") . '">';
	$select= new cHTMLSelectElement("changeclient");
	$choices= array ();
	$warnings= array ();

	foreach ($clients as $key => $v_client) {
		if ($perm->hasClientPermission($key)) {

			$cApiClient->loadByPrimaryKey($key);
			if ($cApiClient->hasLanguages()) {
				$choices[$key]= $v_client['name'] . " (" . $key . ')';
			} else {
				$warnings[]= sprintf(i18n("Client %s (%s) has no languages"), $v_client['name'], $key);
			}

		}
	}

	$select->autoFill($choices);
	$select->setDefault($client);

	$clientselect= $select->render();

	$tpl->set('s', 'CLIENTFORM', $clientform);
	$tpl->set('s', 'CLIENTFORMCLOSE', "</form>");
	$tpl->set('s', 'PULL_DOWN_MANDANTEN', $clientselect);

	if ($perm->have_perm() && count($warnings) > 0) {
		$tpl->set('s', 'WARNINGS', "<br>" . $notification->messageBox("warning", implode("<br>", $warnings), 0));
	} else {
		$tpl->set('s', 'WARNINGS', '');
	}
	$tpl->set('s', 'OKBUTTON', '<input type="image" src="images/but_ok.gif" alt="' . i18n("Change client") . '" title="' . i18n("Change client") . '" border="0">');
} else {
	$tpl->set('s', 'OKBUTTON', '');
	$sClientForm = '';
	if ( count($clients) == 0 ) {
		$sClientForm = i18n("No clients available!");
	}
	$tpl->set('s', 'CLIENTFORM', $sClientForm);
	$tpl->set('s', 'CLIENTFORMCLOSE', '');

	foreach ($clients as $key => $v_client) {
        if ($perm->hasClientPermission($key)) {
            $cApiClient->loadByPrimaryKey($key);
			if ($cApiClient->hasLanguages()) {
                $name= $v_client['name'] . " (" . $key . ')';
            } else {
				$warnings[]= sprintf(i18n("Client %s (%s) has no languages"), $v_client['name'], $key);
			}
        }
	}

    if ($perm->have_perm() && count($warnings) > 0) {
		$tpl->set('s', 'WARNINGS', "<br>" . $notification->messageBox("warning", implode("<br>", $warnings), 0));
	} else {
		$tpl->set('s', 'WARNINGS', '');
	}

	$tpl->set('s', 'PULL_DOWN_MANDANTEN', $name);
}

$props= new PropertyCollection;
$props->select("itemtype = 'idcommunication' AND idclient='$client' AND type = 'todo' AND name = 'status' AND value != 'done'");

$todoitems= array ();

while ($prop= $props->next()) {
	$todoitems[]= $prop->get("itemid");
}

if (count($todoitems) > 0) {
	$in= "idcommunication IN (" . implode(",", $todoitems) . ")";
} else {
	$in= 1;
}
$todoitems= new TODOCollection;
$recipient= $auth->auth["uid"];
$todoitems->select("recipient = '$recipient' AND idclient='$client' AND $in");

while ($todo= $todoitems->next()) {
	if ($todo->getProperty("todo", "status") != "done") {
		$todoitems++;
	}
}

$sTaskTranslation = '';
if ($todoitems->count() == 1) {
  $sTaskTranslation = i18n("Reminder list: %d Task open");
} else {
  $sTaskTranslation = i18n("Reminder list: %d Tasks open");
}

$mycontenido_overview= '<a class="blue" href="' . $sess->url("main.php?area=mycontenido&frame=4") . '">' . i18n("Overview") . '</a>';
$mycontenido_lastarticles= '<a class="blue" href="' . $sess->url("main.php?area=mycontenido_recent&frame=4") . '">' . i18n("Recently edited articles") . '</a>';
$mycontenido_tasks= '<a class="blue" onclick="sub.highlightById(\'c_1\', top.content.right_top)" href="' . $sess->url("main.php?area=mycontenido_tasks&frame=4") . '">' . sprintf($sTaskTranslation, $todoitems->count()) . '</a>';
$mycontenido_settings= '<a class="blue" onclick="sub.highlightById(\'c_2\', top.content.right_top)" href="' . $sess->url("main.php?area=mycontenido_settings&frame=4") . '">' . i18n("Settings") . '</a>';

$tpl->set('s', 'MYCONTENIDO_OVERVIEW', $mycontenido_overview);
$tpl->set('s', 'MYCONTENIDO_LASTARTICLES', $mycontenido_lastarticles);
$tpl->set('s', 'MYCONTENIDO_TASKS', $mycontenido_tasks);
$tpl->set('s', 'MYCONTENIDO_SETTINGS', $mycontenido_settings);
$admins= $classuser->getSystemAdmins(true);

$sAdminTemplate = '<li class="welcome">%s, %s</li>';

$sAdminName= "";
$sAdminEmail = "";
$sOutputAdmin = "";


foreach ($admins as $key => $value) {
	if ($value["email"] != "") {
		$sAdminEmail= '<a class="blue" href="mailto:' . $value["email"] . '">' . $value["email"] . '</a>';
		$sAdminName = $value['realname'] ? $value["realname"] : $value["username"];
		$sOutputAdmin .= sprintf($sAdminTemplate, $sAdminName, $sAdminEmail);
	}
}

$tpl->set('s', 'ADMIN_EMAIL', $sOutputAdmin);

$tpl->set('s', 'SYMBOLHELP', '<a href="' . $sess->url("frameset.php?area=symbolhelp&frame=4") . '">' . i18n("Symbol help") . '</a>');

if (file_exists($cfg["contenido"]["handbook_path"])) {
	$tpl->set('s', 'CONTENIDOMANUAL', '<a href="' . $cfg["contenido"]["handbook_url"] . '" target="_blank">' . i18n("Contenido Manual") . '</a>');
} else {
	$tpl->set('s', 'CONTENIDOMANUAL', '');
}

// For display current online user in Contenido-Backend
$aMemberList= array ();
$oActiveUsers= new ActiveUsers($db, $cfg, $auth);
$iNumberOfUsers = 0;

// Start()
$oActiveUsers->startUsersTracking();

//Currently User Online
$iNumberOfUsers = $oActiveUsers->getNumberOfUsers();

// Find all User who is online
$aMemberList= $oActiveUsers->findAllUser();

// Template for display current user
$sTemplate = "";
$sOutput = "";
$sTemplate= '<li class="welcome">%s%s</li>';

foreach ($aMemberList as $key) {
	$sRealName = $key['realname'] ? $key["realname"] : $key["username"];
	$aPerms['0'] = $key['perms'];
	if(strstr($aPerms['0'], "admin") === false) {
	    $sOutput .= sprintf($sTemplate,  $sRealName, " ".$aPerms['0']);
	} else {
	    $sOutput .= sprintf($sTemplate,  $sRealName, ", ".$aPerms['0']);
	}
}

// set template welcome
$tpl->set('s', 'USER_ONLINE', $sOutput);
$tpl->set('s', 'Anzahl', $iNumberOfUsers);


// check for new updates
$oUpdateNotifier = new Contenido_UpdateNotifier($cfg, $vuser, $perm, $sess, $belang);
$sUpdateNotifierOutput = $oUpdateNotifier->displayOutput();
$tpl->set('s', 'UPDATENOTIFICATION', $sUpdateNotifierOutput);

$tpl->generate($cfg["path"]["templates"] . $cfg["templates"]["welcome"]);

?>