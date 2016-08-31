<?php

/**
 * This is the main AJAX file of the backend.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Olaf Niemann
 * @author           Jan Lengowski
 * @author           Ingo van Peeren
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO startup process
include_once('./includes/startup.php');

$backendPath = cRegistry::getBackendPath();

$cfg['debug']['backend_exectime']['fullstart'] = getmicrotime();

cInclude('includes', 'functions.api.php');

cRegistry::bootstrap(array(
    'sess' => 'cSession',
    'auth' => 'cAuthHandlerBackend',
    'perm' => 'cPermission'
));

i18nInit($cfg['path']['contenido_locale'], $belang);

require_once($backendPath . $cfg['path']['includes'] . 'functions.includePluginConf.php');

require_once($cfg['path']['contenido_config'] . 'cfg_actions.inc.php');

// Create CONTENIDO classes
// FIXME: Correct variable names, instances of classes are objects, not classes!
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
}

if (!is_numeric($client)
|| (!$perm->have_perm_client('client[' . $client . ']')
&& !$perm->have_perm_client('admin[' . $client . ']'))) {

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
    // search for the first language of this client
    $db->query("
        SELECT
            *
        FROM
            " . $cfg['tab']['lang'] . " AS A
            , " . $cfg['tab']['clients_lang'] . " AS B
        WHERE
            A.idlang=B.idlang
            AND idclient=" . cSecurity::toInteger($client) . "
        ORDER BY
            A.idlang ASC
        ;");
    $db->nextRecord();
    $lang = $db->f('idlang');
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
if ($cfg['debug']['rendering'] == true) {
    $oldmemusage = memory_get_usage();
}

// Select area
$backend->select($area);

$cfg['debug']['backend_exectime']['start'] = getmicrotime();

// If $action is set -> User klicked some button/link
// get the appopriate code for this action and evaluate it.
if (isset($action) && $action != '') {
    if (!isset($idart)) {
        $idart = 0;
    }
    $backend->log($idcat, $idart, $client, $lang, $action);
}


if (isset($action)) {
    $actionCodeFile = $backendPath . 'includes/type/action/include.' . $action . '.action.php';
    if (cFileHandler::exists($actionCodeFile)) {
        cDebug::out('Including action file for ' . $action);
        include_once($actionCodeFile);
    } else {
        cDebug::out('No action file found for ' . $action);
    }
}

if (isset($_REQUEST['ajax']) && $_REQUEST['ajax'] != '') {
    $oAjax = new cAjaxRequest();
    $sReturn = $oAjax->handle($_REQUEST['ajax']);
    echo $sReturn;
} else {
    include_once($backendPath . $cfg['path']['includes'] . 'ajax/include.ajax.' . $area . '.php');
}

$cfg['debug']['backend_exectime']['end'] = getmicrotime();

$debugInfo = array(
    'Building this page (excluding CONTENIDO includes) took: ' .
    ($cfg['debug']['backend_exectime']['end'] - $cfg['debug']['backend_exectime']['start']).' seconds',
    'Building the complete page took: ' .
    ($cfg['debug']['backend_exectime']['end'] - $cfg['debug']['backend_exectime']['fullstart']).' seconds',
    'Include memory usage: ' . humanReadableSize(memory_get_usage()-$oldmemusage),
    'Complete memory usage: ' . humanReadableSize(memory_get_usage()),
    "*****" . $sFilename . "*****"
);
cDebug::out(implode("\n", $debugInfo));

// User Tracking (who is online)
$oActiveUser = new cApiOnlineUserCollection();
$oActiveUser->startUsersTracking();

cRegistry::shutdown(false);
