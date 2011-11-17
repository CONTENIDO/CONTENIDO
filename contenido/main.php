<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO main file
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend
 * @version    1.0.5
 * @author     Olaf Niemann, Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  2003-01-20
 *   modified 2008-06-16, Holger Librenz, Hotfix: added check for invalid calls
 *   modified 2008-06-25, Timo Trautmann, CONTENIDO Framework Constand added
 *   modified 2008-07-02, Frederic Schneider, add security fix and include security_class
 *   modified 2010-05-20, Murat Purc, standardized CONTENIDO startup and security check invocations, see [#CON-307]
 *   modified 2011-02-08, Dominik Ziegler, removed old PHP compatibility stuff as CONTENIDO now requires at least PHP 5
 *   modified 2011-06-15, Rusmir Jusufovic, add CONTENIDO Vars for modul
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO startup process
include_once('./includes/startup.php');

$cfg['debug']['backend_exectime']['fullstart'] = getmicrotime();

cInclude('includes', 'functions.api.php');
cInclude('includes', 'functions.forms.php');

page_open(array('sess' => 'Contenido_Session',
                'auth' => 'Contenido_Challenge_Crypt_Auth',
                'perm' => 'Contenido_Perm'));

i18nInit($cfg['path']['contenido'].$cfg['path']['locale'], $belang);

require_once($cfg['path']['contenido'] . $cfg['path']['includes'] . 'functions.includePluginConf.php');

cInclude('includes', 'cfg_language_de.inc.php');

if ($cfg['use_pseudocron'] == true) {
    // Include cronjob-Emulator, but only for frame 1
    if ($frame == 1) {
        $sess->freeze();

        $oldpwd = getcwd();

        chdir($cfg['path']['contenido'].$cfg['path']['cronjobs']);
        cInclude('includes', 'pseudo-cron.inc.php');
        chdir($oldpwd);

        if ($bJobRunned == true) {
            // Some cronjobs might overwrite important system variables.
            // We are thaw'ing the session again to re-register these variables.
            $sess->thaw();
        }
    }
}


// Remove all own marks, only for frame 1 and 4  if $_REQUEST['appendparameters'] == 'filebrowser'
// filebrowser is used in tiny in this case also do not remove session marks
if (($frame == 1 || $frame == 4) && $_REQUEST['appendparameters'] != 'filebrowser') {
    $col = new cApiInUseCollection();
    $col->removeSessionMarks($sess->id);
}

// If the override flag is set, override a specific cApiInUse
if (isset($overrideid) && isset($overridetype)) {
    $col = new cApiInUseCollection();
    $col->removeItemMarks($overridetype, $overrideid);
}

// Create CONTENIDO classes
$db = new DB_Contenido();
$notification = new Contenido_Notification();
$classarea = new Area();
$classlayout = new Layout();
$classclient = new Client();
$classuser = new User();

$currentuser = new User();
$currentuser->loadUserByUserID($auth->auth['uid']);


// change Client
if (isset($changeclient) && is_numeric($changeclient) ) {
    $client = $changeclient;
    unset($lang);
}

// Sprache wechseln
if (isset($changelang) && is_numeric($changelang) ) {
    unset($area_rights);
    unset($item_rights);
    $lang = $changelang;
}

if (!is_numeric($client) ||
    (!$perm->have_perm_client('client['.$client.']') &&
    !$perm->have_perm_client('admin['.$client.']')))
{
     // use first client which is accessible
    $sess->register('client');
    $sql = 'SELECT idclient FROM '.$cfg['tab']['clients'].' ORDER BY idclient ASC';
    $db->query($sql);

    while ($db->next_record()) {
        $mclient = $db->f('idclient');

        if ($perm->have_perm_client('client['.$mclient.']') ||
            $perm->have_perm_client('admin['.$mclient.']') )
        {
            unset($lang);
            $client = $mclient;
            break;
        }
    }
} else {
    $sess->register('client');
}

if (!is_numeric($lang) || $lang == '') {
    $sess->register('lang');
    // search for the first language of this client
    $sql = "SELECT * FROM ".$cfg['tab']['lang']." AS A, ".$cfg['tab']['clients_lang']." AS B WHERE A.idlang=B.idlang AND idclient=".Contenido_Security::toInteger($client)." ORDER BY A.idlang ASC";
    $db->query($sql);
    $db->next_record();
    $lang = $db->f('idlang');
} else {
    $sess->register('lang');
}

//  Set CONTENIDO vars
Contenido_Vars::setVar('db', $db);
Contenido_Vars::setVar('lang', $lang);
Contenido_Vars::setVar('cfg', $cfg);
Contenido_Vars::setEncoding($db,$cfg,$lang);
Contenido_Vars::setVar('cfgClient', $cfgClient);
Contenido_Vars::setVar('client', $client);
Contenido_Vars::setVar('fileEncoding', getEffectiveSetting('encoding', 'file_encoding','UTF-8'));

// send right encoding http header
sendEncodingHeader($db, $cfg, $lang);

$perm->load_permissions();

// Create CONTENIDO classes
$xml      = new XML_doc();
$tpl      = new Template();
$backend  = new Contenido_Backend();
//$backend->debug=true;

// Register session variables
$sess->register('sess_area');

if (isset($area)) {
    $sess_area = $area;
} else {
    $area = (isset($sess_area) && $sess_area != '') ? $sess_area : 'login';
}

$sess->register('cfgClient');
$sess->register('errsite_idcat');
$sess->register('errsite_idart');

if ($cfgClient['set'] != 'set') {
     rereadClients();
}

// Initialize CONTENIDO_Backend. Load all actions from the DB and check if 
// permission is granted.
if ($cfg['debug']['rendering'] == true) {
    $oldmemusage = memory_get_usage();
}
// Select frameset
$backend->setFrame($frame);

// Select area
$backend->select($area);

$cfg['debug']['backend_exectime']['start'] = getmicrotime();

// Include all required 'include' files. Can be an array of files, if more than
// one file is required.
if (is_array($backend->getFile('inc'))) {
    foreach ($backend->getFile('inc') as $filename) {
        include_once($cfg['path']['contenido'].$filename);
    }
}

// If $action is set -> User klicked some button/link
// get the appopriate code for this action and evaluate it.
if (isset($action) && $action != '') {
    if (!isset($idart)) {
        $idart = 0;
    }
    $backend->log($idcat, $idart, $client, $lang, $action);
}


if (isset($action)) {
    if ($backend->getCode($action) != '') {
        if ($backend->debug == 1) {
            echo '<pre style="font-family: verdana; font-size: 10px"><b>Executing:</b>'."\n";
            echo $backend->getCode($action)."\n";
            echo '</pre>';
        }
        eval($backend->getCode($action));
    } else {
        if ($backend->debug == 1) {
            echo '<pre style="font-family: verdana; font-size: 10px"><b>Executing:</b>'."\n";
            echo "no code available in action\n";
            echo '</pre>';
        }
    }
}

// Include the 'main' file for the selected area. Usually there is only one main file
if (is_array($backend->getFile('main'))) {
    foreach ($backend->getFile('main') as $id => $filename) {
        include_once($cfg['path']['contenido'].$filename);
    }
} elseif ($frame == 3 ) {
    include_once($cfg['path']['contenido'].$cfg['path']['includes'] .'include.default_subnav.php' );
} else {
    include_once($cfg['path']['contenido'].$cfg['path']['includes'] .'include.blank.php');
}

$cfg['debug']['backend_exectime']['end'] = getmicrotime();

if ($cfg['debug']['rendering'] == true) {
    echo 'Building this page (excluding CONTENIDO includes) took: ' . ($cfg['debug']['backend_exectime']['end'] - $cfg['debug']['backend_exectime']['start']).' seconds<br>';
    echo 'Building the complete page took: ' . ($cfg['debug']['backend_exectime']['end'] - $cfg['debug']['backend_exectime']['fullstart']).' seconds<br>';
    echo 'Include memory usage: '.human_readable_size(memory_get_usage()-$oldmemusage).'<br>';
    echo 'Complete memory usage: '.human_readable_size(memory_get_usage()).'<br>';
}

// Do user tracking (who is online)
$oActiveUser = new ActiveUsers($db, $cfg, $auth);
$oActiveUser->startUsersTracking();

page_close();

?>