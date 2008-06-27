<?php


/******************************************
* File      :   main.login.php
* Project   :   contendio
* Descr     :   contenido start screen
*
* Author    :   Jan Lengowski
* Created   :   21.01.2003
* Modified  :   21.01.2003
* Modified 2008-06-26 timo.trautmann update notifier class added
* 
* © four for business AG
******************************************/

$tpl->reset();

cInclude("classes", "class.todo.php");
cInclude("classes", "contenido/class.client.php");
cInclude("classes", "class.activeusers.php");
cInclude("classes", "class.update.notifier.php");

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

$userid= $auth->auth["uid"];

$sql= "SELECT realname FROM " . $cfg["tab"]["phplib_auth_user_md5"] . " WHERE user_id = '" . $userid . "'";

$db->query($sql);
$db->next_record();

$str= "<b>" . i18n("Welcome") . " </b>" . $db->f("realname") . ".";
$str_test= $db->f("realname");
$tpl->set('s', 'WELCOME', $str);
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
	$tpl->set('s', 'PULL_DOWN_MANDANTEN', $clientselect);

	if ($perm->have_perm() && count($warnings) > 0) {
		$tpl->set('s', 'WARNINGS', "<br>" . $notification->messageBox("warning", implode("<br>", $warnings), 0));
	} else {
		$tpl->set('s', 'WARNINGS', '');
	}
	$tpl->set('s', 'OKBUTTON', '<input type="image" src="images/but_ok.gif" alt="' . i18n("Change client") . '" title="' . i18n("Change client") . '" border="0">');
} else {
	$tpl->set('s', 'OKBUTTON', '');
	$tpl->set('s', 'CLIENTFORM', '');

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

$mycontenido_overview= '<a class="blue" href="' . $sess->url("main.php?area=mycontenido&frame=4") . '">' . i18n("Overview") . '</a>';
$mycontenido_lastarticles= '<a class="blue" href="' . $sess->url("main.php?area=mycontenido_recent&frame=4") . '">' . i18n("Recently edited articles") . '</a>';
$mycontenido_tasks= '<a class="blue" href="' . $sess->url("main.php?area=mycontenido_tasks&frame=4") . '">' . sprintf(i18n("Reminder list: %d Tasks open"), $todoitems->count()) . '</a>';
$mycontenido_settings= '<a class="blue" href="' . $sess->url("main.php?area=mycontenido_settings&frame=4") . '">' . i18n("Settings") . '</a>';

$tpl->set('s', 'MYCONTENIDO_OVERVIEW', $mycontenido_overview);
$tpl->set('s', 'MYCONTENIDO_LASTARTICLES', $mycontenido_lastarticles);
$tpl->set('s', 'MYCONTENIDO_TASKS', $mycontenido_tasks);
$tpl->set('s', 'MYCONTENIDO_SETTINGS', $mycontenido_settings);
$admins= $classuser->getSystemAdmins();

//echo '<pre>';
//print_r($admins);
//echo '</pre>';

$sAdminTemplate = '<li class="welcome">%s, %s</li>';

$sAdminName= "";
$sAdminEmail = "";
$sOutputAdmin = "";


foreach ($admins as $key => $value) {
	if ($value["email"] != "") {
		$sAdminEmail= '<a class="blue" href="mailto:' . $value["email"] . '">' . $value["email"] . '</a>';
		$sAdminName= $value['realname'];
		$sOutputAdmin .= sprintf($sAdminTemplate, $sAdminName, $sAdminEmail);
	}
}

//$adminstring= implode(", ", $aAdmins);

/*** We dont need this Client-Admins Information any more
$mclientadmins= array ();
$clientadmins= $classuser->getClientAdmins($client);

foreach ($clientadmins as $key => $value) {
	if ($value["email"] != "") {
		$mclientadmins[]= '<a href="mailto:' . $value["email"] . '">' . $value["email"] . '</a>';
	}
}

$clientadminstring= implode(", ", $mclientadmins);
*/

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
$sTemplate= '<li class="welcome">%s, %s</li>';

foreach ($aMemberList as $key) {
	$sRealName= $key['realname'];
	$aPerms['0']= $key['perms'];
	$sOutput .= sprintf($sTemplate,  $sRealName, $aPerms['0']);
}

// set template welcome
$tpl->set('s', 'USER_ONLINE', $sOutput);
$tpl->set('s', 'Anzahl', $iNumberOfUsers);


require_once 'Log.php';
$file = &Log::factory('file', 'd:/contenido.log', '');
$file->log(2);

// rss feed
if($perm->isSysadmin($vuser) && $cfg["backend"]["newsfeed"] == true){
	$newsfeed = 'some news';
	$tpl->set('s', 'CONTENIDO_NEWS', $newsfeed);
}
else{
	$tpl->set('s', 'CONTENIDO_NEWS', '');
}

// check for new updates
$oUpdateNotifier = new Contenido_UpdateNotifier($cfg['version'], $cfg['path'], $vuser, $perm, $sess);
$sUpdateNotifierOutput = $oUpdateNotifier->displayOutput();
$tpl->set('s', 'UPDATENOTIFICATION', $sUpdateNotifierOutput);

$tpl->generate($cfg["path"]["templates"] . $cfg["templates"]["welcome"]);

?>
