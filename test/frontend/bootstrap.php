<?php
/**
 * Bootstrap file for UnitTest.
 *
 * This file should included at least once. It initializes the UnitTest Framework
 * and also CONTENIDO frontend.
 *
 * @package          Testing
 * @subpackage       Bootstrap
 * @version          SVN Revision $Rev:$
 *
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

################################################################################
# UnitTest Framework initialization

// set dir to PHPUnit location
define('CON_UNITTEST_LIB_DIR', '');

// set dir to CONTENIDO test location
define('CON_TEST_PATH', str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')));

// UnitTest sources
require_once(CON_UNITTEST_LIB_DIR . 'PHPUnit/Framework/TestCase.php');

// CONTENIDO test related classes
require_once(CON_TEST_PATH . '/lib/TestSuiteHelper.php');
require_once(CON_TEST_PATH . '/lib/ContenidoTestHelper.php');

if (!defined('CON_ENVIRONMENT')) {
    if (getenv('CONTENIDO_ENVIRONMENT')) {
        $sEnvironment = getenv('CONTENIDO_ENVIRONMENT');
    } elseif (getenv('CON_ENVIRONMENT')) {
        $sEnvironment = getenv('CON_ENVIRONMENT');
    } else {
        // @TODO: provide a possibility to set the environment value via file
        $sEnvironment = 'production';
    }

    define('CON_ENVIRONMENT', $sEnvironment);
}


################################################################################
# CONTENIDO frontend initialization

$currentWorkingDir = getcwd();
chdir(realpath(CON_TEST_PATH . '/../cms'));

global $contenido_host, $contenido_database, $contenido_user, $contenido_password;
global $contenido, $db, $auth, $sess, $perm, $lngAct, $_cecRegistry;
global $cfgClient, $client, $load_client, $lang, $load_lang, $frontend_debug;
global $idcat, $errsite_idcat, $errsite_idart, $encoding, $idart, $force;
global $PHP_SELF, $QUERY_STRING;

///////////////////// initial code from front_content.php //////////////////////

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// Include the config file of the frontend to init the Client and Language Id
include_once('data/config/' . CON_ENVIRONMENT . '/config.php');

// Contenido startup process
if (!is_file($contenido_path . 'includes/startup.php')) {
    die("<h1>Fatal Error</h1><br>Couldn't include CONTENIDO startup.");
}
include_once($contenido_path.'includes/startup.php');

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.con2.php');
cInclude('includes', 'functions.api.php');
cInclude('includes', 'functions.pathresolver.php');

// Initialize the Database Abstraction Layer, the Session, Authentication and Permissions Handler of the
if ($contenido) {
    // Backend
    cRegistry::bootstrap(array(
        'sess' => 'cSession',
        'auth' => 'cAuthHandlerBackend',
        'perm' => 'cPermission'
    ));
    i18nInit($cfg['path']['contenido_locale'], $belang);
} else {
    // Frontend
    cRegistry::bootstrap(array(
        'sess' => 'cFrontendSession',
        'auth' => 'cAuthHandlerFrontend',
        'perm' => 'cPermission'
    ));
}

require_once($cfg['path']['contenido'] . $cfg['path']['includes'] . 'functions.includePluginConf.php');

cApiCecHook::execute('Contenido.Frontend.AfterLoadPlugins');

$db = cRegistry::getDb();

$sess->register('cfgClient');
$sess->register('errsite_idcat');
$sess->register('errsite_idart');
$sess->register('encoding');

// Initialize encodings
if (!isset($encoding) || !is_array($encoding) || count($encoding) == 0) {
    // Get encodings of all languages
    $encoding = array();
    $oLangColl = new cApiLanguageCollection();
    $oLangColl->select('');
    while ($oLang = $oLangColl->next()) {
        $encoding[$oLang->get('idlang')] = $oLang->get('encoding');
    }
}

// update urlbuilder set http base path
cUri::getInstance()->getUriBuilder()->setHttpBasePath($cfgClient[$client]['htmlpath']['frontend']);

// Initialize language
if (!isset($lang)) {
    // If there is an entry load_lang in __FRONTEND_PATH__/data/config/config.php use it, else use the first language of this client
    if (isset($load_lang)) {
        // load_client is set in __FRONTEND_PATH__/data/config/config.php
        $lang = $load_lang;
    } else {
        $oClientLang = new cApiClientLanguageCollection();
        $lang = $oClientLang->getFirstLanguageIdByClient($client);
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
if (file_exists('config.local.php')) {
    @include('config.local.php');
}

// If the path variable was passed, try to resolve it to a Category Id
// e.g. front_content.php?path=/company/products/
if (isset($path) && strlen($path) > 1) {
    // Which resolve method is configured?
    if ($cfg['urlpathresolve'] == true) {
        $iLangCheck = 0;
        $idcat = prResolvePathViaURLNames($path, $iLangCheck);
    } else {
        $iLangCheck = 0;
        $idcat = prResolvePathViaCategoryNames($path, $iLangCheck);
        if (($lang != $iLangCheck) && ((int) $iLangCheck != 0)) {
            $lang = $iLangCheck;
        }
    }
}

// Error page
$aParams = array(
    'client' => $client, 'idcat' => $errsite_idcat[$client], 'idart' => $errsite_idart[$client],
    'lang' => $lang, 'error'=> '1'
);
$errsite = 'Location: ' . cUri::getInstance()->buildRedirect($aParams);

///////////////////// initial code from front_content.php //////////////////////

################################################################################
# Back to the roots

chdir($currentWorkingDir);
