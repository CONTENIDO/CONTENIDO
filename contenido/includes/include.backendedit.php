<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Backend edit include
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-07-02, Frederic Schneider, add security fix and include security_class
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined("CON_FRAMEWORK")) {
    define("CON_FRAMEWORK", true);
}
 
// include security class and check request variables
include_once ('../classes/class.security.php');
Contenido_Security::checkRequests();

include_once ('../includes/startup.php');
cInclude ("includes", 'functions.general.php');
$fullstart = getmicrotime();

cInclude ("includes", 'functions.i18n.php');
cInclude ("includes", 'functions.api.php');
cInclude ("includes", 'functions.general.php');
cInclude ("includes", 'functions.forms.php');
cInclude ("includes", 'functions.con.php');

cInclude ("includes", 'cfg_sql.inc.php');


cInclude ("classes", 'class.xml.php');
cInclude ("classes", 'class.navigation.php');
cInclude ("classes", 'class.template.php');
cInclude ("classes", 'class.backend.php');
cInclude ("classes", 'class.notification.php');
cInclude ("classes", 'class.area.php');
cInclude ("classes", 'class.action.php');
cInclude ("classes", 'contenido/class.module.php');
cInclude ("classes", 'class.layout.php');
cInclude ("classes", 'class.treeitem.php');
cInclude ("classes", 'class.user.php');
cInclude ("classes", 'class.group.php');
cInclude ("classes", 'class.cat.php');
cInclude ("classes", 'class.client.php');
cInclude ("classes", 'class.inuse.php');
cInclude ("classes", 'class.table.php');


page_open(array('sess' => 'Contenido_Session',
                'auth' => 'Contenido_Challenge_Crypt_Auth',
                'perm' => 'Contenido_Perm'));

i18nInit($cfg["path"]["contenido"].$cfg["path"]["locale"], $belang);
cInclude ("includes", 'cfg_language_de.inc.php');



# Create Contenido classes
$db = new DB_Contenido;
$notification = new Contenido_Notification;
$classarea = new Area();
$classlayout = new Layout();
$classclient = new Client();
$classuser = new User();

# change Client
if ( is_numeric($changeclient) ) {
    $client = $changeclient;
    unset($lang);
}

# Sprache wechseln
if ( is_numeric($changelang) ) {
	unset($area_rights);
	unset($item_rights);

    $lang = $changelang;
}

if (!is_numeric($client) || $client == "") {
    $sess->register("client");
    $sql = "SELECT idclient FROM ".$cfg["tab"]["clients"]." ORDER BY idclient ASC";
    $db->query($sql);
    $db->next_record();
    $client = $db->f("idclient");
} else {
    $sess->register("client");
}

if (!is_numeric($lang) || $lang == "") {
    $sess->register("lang");
    # search for the first language of this client
    $sql = "SELECT * FROM ".$cfg["tab"]["lang"]." AS A, ".$cfg["tab"]["clients_lang"]." AS B WHERE A.idlang=B.idlang AND idclient='$client' ORDER BY A.idlang ASC";
    $db->query($sql);
    $db->next_record();
    $lang = $db->f("idlang");
} else {
	$sess->register("lang");
}

$perm->load_permissions();

# Create Contenido classes
$xml        = new XML_doc;
$tpl        = new Template;
$backend    = new Contenido_Backend;

# Register session variables
$sess->register("sess_area");

if (isset($area)) {
    $sess_area = $area;
} else {
    $area = ( isset($sess_area) && $sess_area != "" ) ? $sess_area : 'login';
}

$sess->register("cfgClient");
$sess->register("errsite_idcat");
$sess->register("errsite_idart");

if ($cfgClient["set"] != "set")
{
	rereadClients();
}

$start = getmicrotime();

include ($cfg["path"]["contenido"].$cfg["path"]["includes"].'include.'.$type.'.php');

$end = getmicrotime();

if ($cfg["debug"]["rendering"] == true)
{
	echo "Rendering this page took: " . ($end - $start)." seconds<br>";
	echo "Building the complete page took: " . ($end - $fullstart)." seconds<br>";
}
page_close();

?>
