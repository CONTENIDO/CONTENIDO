<?php

/*****************************************
* File      :   $RCSfile: logout.php,v $
* Project   :   Contenido
* Descr     :   Contenido Logout function
*
* Author    :   Timo A. Hummel
*               
* Created   :   20.05.2003
* Modified  :   $Date: 2006/04/28 09:20:55 $
*
* © four for business AG, www.4fb.de
*
* $Id: logout.php,v 1.11 2006/04/28 09:20:55 timo.hummel Exp $
******************************************/

define("CON_FRAMEWORK", true);

include_once ('./includes/startup.php');

cInclude ("includes", 'functions.i18n.php');

cInclude("classes", 'class.user.php');
cInclude("classes", 'class.xml.php');
cInclude("classes", 'class.navigation.php');
cInclude("classes", 'class.template.php');
cInclude("classes", 'class.backend.php');
cInclude("classes", 'class.table.php');
cInclude("classes", 'class.notification.php');
cInclude("classes", 'class.area.php');
cInclude("classes", 'class.client.php');
cInclude("classes", 'class.cat.php');


page_open(array('sess' => 'Contenido_Session',
                'auth' => 'Contenido_Challenge_Crypt_Auth',
                'perm' => 'Contenido_Perm'));

i18nInit($cfg["path"]["contenido"].$cfg["path"]["locale"], $belang);

cInclude("includes",  'cfg_sql.inc.php');
cInclude("includes",   'cfg_language_de.inc.php');
cInclude("includes",   'functions.general.php');
cInclude("includes",   'functions.i18n.php');
cInclude("includes",   'functions.forms.php');
cInclude("classes", "class.activeusers.php");

$oActiveUser = new ActiveUsers($db, $cfg, $auth);
$iUserId= $auth->auth["uid"];
$oActiveUser->deleteUser($iUserId);

$auth->logout();
page_close();
$sess->delete();
header("location:index.php");

?>
