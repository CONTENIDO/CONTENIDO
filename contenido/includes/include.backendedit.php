<?php

/**
 * This file contains the backend edit include.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Unknown
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
 * @var string $belang
 * @var array $cfg
 * @var cSession $sess
 * @var string $type
 */

// CONTENIDO startup process
include_once('../includes/startup.php');

$fullstart = getmicrotime();

cInclude('includes', 'functions.api.php');
cInclude('includes', 'functions.con.php');

cRegistry::bootstrap([
    'sess' => 'cSession',
    'auth' => 'cAuthHandlerBackend',
    'perm' => 'cPermission'
]);

// The following lines load hooks (CON-2491)
// It is a duplicated of the hook execution code of include.front_content.php (TODO)
// this is done because in this file the loading process includes include.front_content.php
// after this file, therefore some hooks can never be executed
$backendPath = cRegistry::getBackendPath();

// Include plugins
require_once($backendPath . $cfg['path']['includes'] . 'functions.includePluginConf.php');

// Call hook after plugins are loaded
cApiCecHook::execute('Contenido.Frontend.AfterLoadPlugins');

// - End of loading hooks

i18nInit($cfg['path']['contenido_locale'], $belang);

require_once($cfg['path']['contenido_config'] . 'cfg_actions.inc.php');

$changeclient = $changeclient ?? '';

// Create CONTENIDO classes
// FIXME: Correct variable names, instances of classes at objects, not classes!
$db = cRegistry::getDb();
$notification = new cGuiNotification();
$classarea = new cApiAreaCollection();
$classlayout = new cApiLayout();
$classclient = new cApiClientCollection();

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

if (!is_numeric($client) || $client == '') {
    $sess->register('client');
    $oClientColl = new cApiClientCollection();
    $oClientColl->select('', '', 'idclient ASC', '1');
    if ($oClient = $oClientColl->next()) {
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

$start = getmicrotime();

include(cRegistry::getBackendPath() . $cfg['path']['includes'] . 'include.' . $type . '.php');

$end = getmicrotime();

cDebug::out('Rendering this page took: ' . ($end - $start) . ' seconds<br>');
cDebug::out('Building the complete page took: ' . ($end - $fullstart) . ' seconds<br>');

cRegistry::shutdown();
