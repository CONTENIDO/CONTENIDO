<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido main file
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend
 * @version    1.2.1
 * @author     Olaf Niemann, Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-01-20
 *   modified 2008-06-16, Holger Librenz, Hotifx: added check for invalid calls
 *   modified 2008-06-16, Rudi Bieller, Hotifx: added check for XSS at "contenido" and "belang"
 *   modified 2008-06-25, Timo Trautmann, Contenido Framework Constand added
 *   modified 2008-07-02, Frederic Schneider, add security fix and include security class 
 *
 *   $Id$:
 * }}
 * 
 */

define("CON_FRAMEWORK", true);

// include security class and check request variables
include_once ('./classes/class.security.php');
Contenido_Security::checkRequests();

if (isset($_REQUEST['contenido'])) {
	$sPattern = '/^[a-zA-Z0-9]+$/i';
	if (!preg_match($sPattern, $_REQUEST['contenido'])) {
		die ('Invalid call');
	}
}

include_once ('./includes/startup.php');

cInclude ("includes", 'functions.i18n.php');

cInclude ("classes", 'class.xml.php');
cInclude ("classes", 'class.navigation.php');
cInclude ("classes", 'class.template.php');
cInclude ("classes", 'class.backend.php');

cInclude ("includes", 'cfg_sql.inc.php');


page_open(array('sess' => 'Contenido_Session',
				'auth' => 'Contenido_Challenge_Crypt_Auth',
				'perm' => 'Contenido_Perm'));

i18nInit($cfg["path"]["contenido"].$cfg["path"]["locale"], $belang);

/**
 * Bugfix
 * @see http://contenido.org/forum/viewtopic.php?t=18291
 *
 * added by H. Librenz (2007-12-07)
 */
//includePluginConf();
require_once $cfg['path']['contenido'] . $cfg['path']['includes'] . 'functions.includePluginConf.php';
cInclude ("includes", 'cfg_language_de.inc.php');
cInclude ("includes", 'functions.general.php');
cInclude ("includes", 'functions.forms.php');

$sess->register("belang");

// Create Contenido classes
$db  = new DB_Contenido;
$tpl = new Template;

// Sprache wechseln
if (isset($changelang) && is_numeric($changelang))
{
    $lang = $changelang;
}

// Change Client
if (isset($changeclient) && is_numeric($changeclient))
{
     $client = $changeclient;
     unset($lang);
}

// Preselect client, if definied
if (!$sess->is_registered("client")) { // only check at first login into backend
    $iTmpClient = getEffectiveSetting ("backend", "preferred_idclient", false);

    if ($iTmpClient && ($perm->have_perm_client("admin[".$iTmpClient."]") || $perm->have_perm_client("client[".$iTmpClient."]"))) {
      $client = $iTmpClient;
      unset($lang);
    }
    unset($iTmpClient);
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
    // search for the first language of this client
    $sql = "SELECT * FROM ".$cfg["tab"]["lang"]." AS A, ".$cfg["tab"]["clients_lang"]." AS B WHERE A.idlang=B.idlang AND idclient='".Contenido_Security::toInteger($client)."' ORDER BY A.idlang ASC";
    $db->query($sql);
    $db->next_record();
    $lang = $db->f("idlang");

	if (!$perm->have_perm_client_lang($client, $lang)) {
		$lang = '';

		while ($db->next_record() && ($lang == '')) {
			if ($perm->have_perm_client_lang($client, $db->f('idlang'))) {
				$lang = $db->f("idlang");
			}
		}
	}
} else {
	$sess->register("lang");
}

$perm->load_permissions();

if (isset($area))
{
    $sess_area = $area;
} else {
    $area = (isset($sess_area)) ? $sess_area : 'login';
}

$tpl->reset();

$tpl->set('s', 'HEADER',    $sess->url('header.php?changelang='.$lang.'&changeclient='.$client));
$tpl->set('s', 'CONTENT',   $sess->url('frameset.php?area=mycontenido&frame=1&menuless=1&changelang='.$changelang.'&lang='.$lang.'&client='.$client));
$tpl->set('s', 'VERSION',	$cfg["version"]);
$tpl->set('s', 'LOCATION',	$cfg['path']['contenido_fullhtml']);
$tpl->set('s', 'CONTENIDOPATH', $cfg["path"]["contenido_fullhtml"]."favicon.ico");
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['frameset']);

page_close();

?>