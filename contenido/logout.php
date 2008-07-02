<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Logout function
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.1.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-05-20
 *   modified 2008-07-02, Frederic Schneider, new code-header
 *
 *   $Id$:
 * }}
 * 
 */

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