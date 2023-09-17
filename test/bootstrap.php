<?php

/**
 * Bootstrap file for UnitTest.
 *
 * This file should include at least once. It initializes the UnitTest Framework
 * and also CONTENIDO frontend.
 *
 * @package    Testing
 * @subpackage Bootstrap
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

################################################################################
# UnitTest Framework initialization

// Set directory to CONTENIDO test location
define('CON_TEST_PATH', __DIR__);

// Set folder name of CONTENIDO test
define('CON_TEST_BASENAME', basename(__DIR__));

// Set the SQL prefix (table prefix) for tests
define('CON_TEST_SQL_PREFIX', 'test');

// Use composer's autoload
require_once __DIR__ . '/../vendor/autoload.php';

################################################################################
# CONTENIDO frontend initialization

$currentWorkingDir = getcwd();
chdir(realpath(CON_TEST_PATH . '/../cms'));

// Include the environment definer file
include_once(__DIR__ . '/environment.php');

global $cfg, $contenido_host, $contenido_database, $contenido_user, $contenido_password;
global $contenido, $db, $auth, $sess, $perm, $lngAct, $_cecRegistry, $belang;
global $cfgClient, $client, $load_client, $lang, $load_lang, $frontend_debug;
global $idcat, $errsite_idcat, $errsite_idart, $encoding, $idart, $force, $contenido_path;
global $PHP_SELF, $QUERY_STRING;

///////////////////// initial code from front_content.php //////////////////////

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// Include the config file of the frontend to initialize client and language id
include_once('data/config/' . CON_ENVIRONMENT . '/config.php');

// Contenido startup process
if (!is_file($contenido_path . 'includes/startup.php')) {
    die("<h1>Fatal Error</h1><br>Couldn't include CONTENIDO startup.");
}
include_once($contenido_path.'includes/startup.php');

// Add all CONTENIDO test related classes
cAutoload::addClassmapConfig([
    'cTestingException' => CON_TEST_BASENAME . '/lib/class.testing.exception.php',
    'cTestingTestCase' => CON_TEST_BASENAME . '/lib/class.testing.test.case.php',
    'cTestingTestHelper' => CON_TEST_BASENAME . '/lib/class.testing.test.helper.php',
    'cUnitTestSession' => CON_TEST_BASENAME . '/lib/class.unit.testsession.php',
    'DogCollection' => CON_TEST_BASENAME . '/contenido/genericdb/mockup/class.dog_item.php',
    'DogItem' => CON_TEST_BASENAME . '/contenido/genericdb/mockup/class.dog_item.php',
    'DogRfidCollection' => CON_TEST_BASENAME . '/contenido/genericdb/mockup/class.dog_rfid_item.php',
    'DogRfidItem' => CON_TEST_BASENAME . '/contenido/genericdb/mockup/class.dog_rfid_item.php',
    'SqlItemCollection' => CON_TEST_BASENAME . '/contenido/genericdb/mockup/class.sql_item_collection.php',
    'SqlItem' => CON_TEST_BASENAME . '/contenido/genericdb/mockup/class.sql_item.php',
    'TestCollection' => CON_TEST_BASENAME . '/contenido/genericdb/mockup/class.test_item.php',
    'TestItem' => CON_TEST_BASENAME . '/contenido/genericdb/mockup/class.test_item.php',
    'TFCollection' => CON_TEST_BASENAME . '/contenido/genericdb/mockup/class.tf_item.php',
    'TFItem' => CON_TEST_BASENAME . '/contenido/genericdb/mockup/class.tf_item.php',
]);


// Initialize common variables
$idcat    = isset($idcat) ? $idcat : 0;
$idart    = isset($idart) ? $idart : 0;
$idcatart = isset($idcatart) ? $idcatart : 0;
$error    = isset($error) ? $error : 0;

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.con2.php');
cInclude('includes', 'functions.api.php');
cInclude('includes', 'functions.pathresolver.php');

$backendPath = cRegistry::getBackendPath();

// Initialize the Database Abstraction Layer, the Session, Authentication and Permissions Handler of the
if (cRegistry::getBackendSessionId()) {
    // Backend
    cRegistry::bootstrap([
        'sess' => 'cUnitTestSession',  // cSession
        'auth' => 'cAuthHandlerBackend',
        'perm' => 'cPermission'
    ]);
    i18nInit($cfg['path']['contenido_locale'], $belang);
} else {
    // Frontend
    cRegistry::bootstrap([
        'sess' => 'cUnitTestSession', // cFrontendSession
        'auth' => 'cAuthHandlerFrontend',
        'perm' => 'cPermission'
    ]);
}

// Include plugins & call hook after plugins are loaded
require_once $backendPath . $cfg['path']['includes'] . 'functions.includePluginConf.php';
cApiCecHook::execute('Contenido.Frontend.AfterLoadPlugins');

$db = cRegistry::getDb();

$sess->register('encoding');

// Initialize encodings
if (!isset($encoding) || !is_array($encoding) || count($encoding) == 0) {
    // Get encodings of all languages
    $encoding  = [];
    $oLangColl = new cApiLanguageCollection();
    $oLangColl->select('');
    while ($oLang = $oLangColl->next()) {
        $encoding[$oLang->get('idlang')] = $oLang->get('encoding');
    }
}

// Update UriBuilder, set http base path
cUri::getInstance()->getUriBuilder()->setHttpBasePath(cRegistry::getFrontendUrl());

// Initialize client
if (!isset($client)) {
    // load_client defined in __FRONTEND_PATH__/data/config/config.php
    $client = $load_client;
}

// Initialize language
if (!isset($lang)) {
    // If there is an entry load_lang in
    // __FRONTEND_PATH__/data/config/config.php use it, else use the first
    // language of this client
    if (isset($load_lang)) {
        // load_client is set in __FRONTEND_PATH__/data/config/config.php
        $lang = $load_lang;
    } else {
        $oClientLangColl = new cApiClientLanguageCollection();
        $lang = (int) $oClientLangColl->getFirstLanguageIdByClient($client);
    }
}

if (!$sess->isRegistered('lang')) {
    $sess->register('lang');
}
if (!$sess->isRegistered('client')) {
    $sess->register('client');
}

if (isset($username)) {
    $auth->restart();
}

// Send HTTP header with encoding
header("Content-Type: text/html; charset={$encoding[$lang]}");

// If http global logout is set e.g. front_content.php?logout=true
// log out the current user.
if (isset($logout)) {
    $auth->logout(true);
    $auth->resetAuthInfo(true);
    $auth->auth['uname'] = 'nobody';
}

// Local configuration
if (file_exists('data/config/' . CON_ENVIRONMENT . '/config.local.php')) {
    @include('data/config/' . CON_ENVIRONMENT . '/config.local.php');
}

// If the path variable was passed, try to resolve it to a Category Id
// e.g. front_content.php?path=/company/products/
if (isset($path) && cString::getStringLength($path) > 1) {
    // Which resolve method is configured?
    if ($cfg['urlpathresolve'] == true) {
        $idcat = prResolvePathViaURLNames($path);
    } else {
        $iLangCheck = 0;
        $idcat      = prResolvePathViaCategoryNames($path, $iLangCheck);
        if (($lang != $iLangCheck) && ((int)$iLangCheck != 0)) {
            $lang = $iLangCheck;
        }
    }
}

// Error page
$aParams = [
    'client' => $client,
    'idcat'  => $cfgClient[$client]['errsite']['idcat'],
    'idart'  => $cfgClient[$client]['errsite']['idart'],
    'lang'   => $lang,
    'error'  => '1'
];
$errsite = 'Location: ' . cUri::getInstance()->buildRedirect($aParams);

///////////////////// initial code from front_content.php //////////////////////

################################################################################
# Back to the roots

chdir($currentWorkingDir);
