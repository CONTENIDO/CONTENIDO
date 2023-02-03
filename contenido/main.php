<?php
/**
 * This is the main file of backend actions.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Olaf Niemann
 * @author           Jan Lengowski
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

/**
 * @var cPermission $perm
 * @var cAuth $auth
 * @var string $belang
 * @var array $cfg
 * @var cSession $sess
 * @var int $changelang
 * @var int $client
 * @var int $frame
 * @var string $area
 * @var int $idart
 * @var int $idcat
 * @var bool $bJobRunned
 */

// CONTENIDO startup process
include_once('./includes/startup.php');

//includes the backendpath
$backendPath = cRegistry::getBackendPath();

$cfg['debug']['backend_exectime']['fullstart'] = getmicrotime();

cInclude('includes', 'functions.api.php');

cRegistry::bootstrap([
    'sess' => 'cSession',
    'auth' => 'cAuthHandlerBackend',
    'perm' => 'cPermission'
]);

i18nInit($cfg['path']['contenido_locale'], $belang);

require_once($backendPath . $cfg['path']['includes'] . 'functions.includePluginConf.php');

require_once($cfg['path']['contenido_config'] . 'cfg_actions.inc.php');

$sess->register('belang');

// Include cronjob-Emulator (for frame 1 only)
if ($cfg['use_pseudocron'] == true) {
    if ($frame == 1) {
        $sess->freeze();

        $currentWorkingDirectory = getcwd();
        chdir($backendPath . $cfg['path']['cronjobs']);
        cInclude('includes', 'pseudo-cron.inc.php');
        chdir($currentWorkingDirectory);

        if ($bJobRunned == true) {
            // Some cronjobs might overwrite important system variables.
            // We are thaw'ing the session again to re-register these variables.
            $sess->thaw();
        }
    }
}

// Remove all own marks, only for frame 1 and 4 if $_REQUEST['appendparameters']
// == 'filebrowser'
// filebrowser is used in tiny in this case also do not remove session marks
$appendparameters = $_REQUEST['appendparameters'] ?? '';
if (in_array($frame, [1, 4]) && $appendparameters != 'filebrowser') {
    $col = new cApiInUseCollection();
    $col->removeSessionMarks($sess->id);
    $col->removeOldMarks();
}

// If the override flag is set, override a specific cApiInUse
if (isset($overrideid) && isset($overridetype)) {
    $col = new cApiInUseCollection();
    $col->removeItemMarks($overridetype, $overrideid);
}

// Create CONTENIDO classes
// FIXME: Correct variable names, instances of classes at objects, not classes!
$db = cRegistry::getDb();
$notification = new cGuiNotification();
$classarea = new cApiAreaCollection();
$classlayout = new cApiLayout();
$classclient = new cApiClientCollection();

$currentuser = new cApiUser($auth->auth['uid']);

// Change client
if (isset($changeclient) && is_numeric($changeclient)) {
    $client = $changeclient;
    unset($lang);
}

// Change language
if (isset($changelang) && is_numeric($changelang)) {
    unset($area_rights);
    unset($item_rights);
    $lang = $changelang;

    // If user switch language and the previously selected article is not existing in the new language
    // redirect to MyCONTENIDO area
    if ($area == 'con_editart' || $area == 'con_meta' || $area == 'con_tplcfg' || $area == 'con_content_list') {

    	$artLangColl = new cApiArticleLanguageCollection;
    	$artLangColl->setWhere('idart', $idart);
    	$artLangColl->setWhere('idlang', $lang);
    	$artLangColl->query();

		if ($artLangColl->count() == 0) {
			$frame = $sess->url('index.php?area=mycontenido&frame=4');
			echo "<script>parent.frames.top.location.href='" . $frame . "';</script>";
		}
    }

}

if (!is_numeric($client) || (!$perm->have_perm_client('client[' . $client . ']') && !$perm->have_perm_client('admin[' . $client . ']'))) {
    // use first client which is accessible
    $sess->register('client');
    $oClientColl = new cApiClientCollection();
    if ($oClient = $oClientColl->getFirstAccessibleClient()) {
        unset($lang);
        $client = $oClient->get('idclient');
    }
} else {
    $sess->register('client');
}

if (!is_numeric($lang) || $lang == '') {
    $sess->register('lang');
    // Search for the first language of this client
    $oClientLangColl = new cApiClientLanguageCollection();
    $lang = (int) $oClientLangColl->getFirstLanguageIdByClient($client);
} else {
    $sess->register('lang');
}

// send right encoding http header
sendEncodingHeader($db, $cfg, $lang);

$perm->load_permissions();

// Create CONTENIDO classes
$tpl = new cTemplate();
$backend = new cBackend();

// Register session variables
$sess->register('sess_area');

if (isset($area)) {
    $sess_area = $area;
} else {
    $area = (isset($sess_area) && $sess_area != '') ? $sess_area : 'login';
}

// Initialize CONTENIDO_Backend.
// Load all actions from the DB and check if permission is granted.
$oldmemusage = memory_get_usage();

// Select frameset
$backend->setFrame($frame);

// Select area
$backend->select($area);

$cfg['debug']['backend_exectime']['start'] = getmicrotime();

// Include all required 'include' files. Can be an array of files, if more than one file is required.
foreach ($backend->getFile('inc') as $filename) {
    include_once($backendPath . $filename);
}

// If $action is set -> User klicked some button/link
// get the appopriate code for this action and evaluate it.
if (isset($action) && $action != '') {
    if (!isset($idart)) {
        $idart = 0;
    }
    $backend->log($idcat, $idart, $client, $lang, $action);
}

// include action file if it exists
if (isset($action)) {
    $actionCodeFile = $backendPath . 'includes/type/action/include.' . $action . '.action.php';
    if (cFileHandler::exists($actionCodeFile)) {
        cDebug::out('Including action file for ' . $action);
        include_once($actionCodeFile);
    } else {
        cDebug::out('No action file found for ' . $action);
    }
}

// Include the 'main' file for the selected area. Usually there is only one main
// file
$sFilename = '';
if (count($backend->getFile('main')) > 0) {
    foreach ($backend->getFile('main') as $id => $filename) {
        $sFilename = $filename;
        include_once($backendPath . $filename);
    }
} elseif ($frame == 3) {
    include_once($backendPath . $cfg['path']['includes'] . 'include.default_subnav.php');
    $sFilename = 'include.default_subnav.php';
} else {
    include_once($backendPath . $cfg['path']['includes'] . 'include.blank.php');
    $sFilename = 'include.blank.php';
}

$cfg['debug']['backend_exectime']['end'] = getmicrotime();

$debugInfo = [
    'Building this page (excluding CONTENIDO includes) took: ' . ($cfg['debug']['backend_exectime']['end'] - $cfg['debug']['backend_exectime']['start']) . ' seconds',
    'Building the complete page took: ' . ($cfg['debug']['backend_exectime']['end'] - $cfg['debug']['backend_exectime']['fullstart']) . ' seconds',
    'Include memory usage: ' . humanReadableSize(memory_get_usage() - $oldmemusage),
    'Complete memory usage: ' . humanReadableSize(memory_get_usage()),
    '*****' . $sFilename . '*****'
];
cDebug::out(implode("\n", $debugInfo));

// Do user tracking (who is online)
$oActiveUser = new cApiOnlineUserCollection();
$oActiveUser->startUsersTracking();

cRegistry::shutdown();
