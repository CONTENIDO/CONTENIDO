<?php
/**
 * Bootstrap file for UnitTest.
 *
 * This file should included at least once. It initializes the UnitTest Framework and also
 * Contenido frontend.
 *
 * @author      Murat Purc <murat@purc.de>
 * @date        26.12.2008
 * @category    Testing
 * @package     Contenido_Frontend
 * @subpackage  Bootstrap
 */



####################################################################################################
# UnitTest Framework initialization

// set dir to PHPUnit location
define('UNITTEST_LIB_DIR', '');

// set dir to Contenido test location
define('CONTENIDO_TEST_PATH', str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')));


// UnitTest sources
require_once(UNITTEST_LIB_DIR . 'PHPUnit/Framework/TestCase.php');

// Contenido test related classes
require_once(CONTENIDO_TEST_PATH . '/lib/TestSuiteHelper.php');
require_once(CONTENIDO_TEST_PATH . '/lib/ContenidoTestHelper.php');


####################################################################################################
# Contenido frontend initialization

$currentWorkingDir = getcwd();
chdir(realpath(CONTENIDO_TEST_PATH . '/../cms'));

global $contenido_host, $contenido_database, $contenido_user, $contenido_password;
global $contenido, $db, $auth, $sess, $perm, $lngAct, $_cecRegistry;
global $cfgClient, $client, $load_client, $lang, $load_lang, $frontend_debug;
global $idcat, $errsite_idcat, $errsite_idart, $encoding, $idart, $force;
global $PHP_SELF, $QUERY_STRING;

/////////////////////////////// initial code from front_content.php ////////////////////////////////

if (!defined("CON_FRAMEWORK")) {
    define("CON_FRAMEWORK", true);
}

$contenido_path = '';
# include the config file of the frontend to init the Client and Language Id
include_once ("config.php");

# Contenido startup process
include_once ($contenido_path."includes/startup.php");

// check HTTP parameters, if requested
if ($cfg['http_params_check']['enabled'] === true) {
    cInclude('classes', 'class.httpinputvalidator.php');
    $oHttpInputValidator =
        new HttpInputValidator($cfg["path"]["contenido"] . $cfg["path"]["includes"] . '/config.http_check.php');
}

cInclude("includes", "functions.con.php");
cInclude("includes", "functions.con2.php");
cInclude("includes", "functions.api.php");
cInclude("includes", "functions.pathresolver.php");
/*
if ($cfg["use_pseudocron"] == true)
{
    // Include cronjob-Emulator
    $oldpwd = getcwd();
    chdir($cfg["path"]["contenido"].$cfg["path"]["cronjobs"]);
    cInclude("includes", "pseudo-cron.inc.php");
    chdir($oldpwd);
}
*/
/*
 * Initialize the Database Abstraction Layer, the Session, Authentication and Permissions Handler of the
 * PHPLIB application development toolkit
 * @see http://sourceforge.net/projects/phplib
 */
if ($contenido)
{
    //Backend
    page_open(array ('sess' => 'Contenido_Session', 'auth' => 'Contenido_Challenge_Crypt_Auth', 'perm' => 'Contenido_Perm'));
    i18nInit($cfg["path"]["contenido"].$cfg["path"]["locale"], $belang);
}
else
{
    //Frontend
    page_open(array ('sess' => 'Contenido_Frontend_Session', 'auth' => 'Contenido_Frontend_Challenge_Crypt_Auth', 'perm' => 'Contenido_Perm'));
}

/**
 * Bugfix
 * @see http://contenido.org/forum/viewtopic.php?t=18291
 *
 * added by H. Librenz (2007-12-07)
 */
//includePluginConf();
/**
 * fixed bugfix - using functions brokes variable scopes!
 *
 * added by H. Librenz (2007-12-21) based on an idea of A. Lindner
 */
require_once $cfg['path']['contenido'] . $cfg['path']['includes'] . 'functions.includePluginConf.php';

// Call hook after plugins are loaded, added by Murat Purc, 2008-09-07
CEC_Hook::execute('Contenido.Frontend.AfterLoadPlugins');

$db = new DB_Contenido;

$sess->register("cfgClient");
$sess->register("errsite_idcat");
$sess->register("errsite_idart");
$sess->register("encoding");

if ($cfgClient["set"] != "set")
{
    rereadClients();
}

$sql = "SELECT idlang, encoding FROM ".$cfg["tab"]["lang"];
$db->query($sql);
// get encodings of all languages
while ($db->next_record())
{
    $encoding[$db->f("idlang")] = $db->f("encoding");
}


// Check frontend globals
// @TODO: Should be outsourced into startup process but requires a better detection (frontend or backend)
Contenido_Security::checkFrontendGlobals();


// update urlbuilder set http base path
Contenido_Url::getInstance()->getUrlBuilder()->setHttpBasePath($cfgClient[$client]['htmlpath']['frontend']);


// Initialize language
if (!isset($lang)) {

    // if there is an entry load_lang in frontend/config.php use it, else use the first language of this client
    if(isset($load_lang)){
        // load_client is set in frontend/config.php
        $lang = $load_lang;
    }else{

        $sql = "SELECT
                    B.idlang
                FROM
                    ".$cfg["tab"]["clients_lang"]." AS A,
                    ".$cfg["tab"]["lang"]." AS B
                WHERE
                    A.idclient='".Contenido_Security::toInteger($client)."' AND
                    A.idlang = B.idlang
                LIMIT
                    0,1";

        $db->query($sql);
        $db->next_record();

        $lang = $db->f("idlang");
    }
}

if (!$sess->is_registered("lang") ) $sess->register("lang");
if (!$sess->is_registered("client") ) $sess->register("client");

if (isset ($username))
{
    $auth->login_if(true);
}

/*
 * Send HTTP header with encoding
 */
header("Content-Type: text/html; charset={$encoding[$lang]}");

/*
 * if http global logout is set e.g. front_content.php?logout=true
 * log out the current user.
 */
if (isset ($logout))
{
    $auth->logout(true);
    $auth->unauth(true);
    $auth->auth["uname"] = "nobody";
}

/*
 * local configuration
 */
if (file_exists("config.local.php"))
{
    @ include ("config.local.php");
}

/*
 * If the path variable was passed, try to resolve it to a Category Id
 * e.g. front_content.php?path=/company/products/
 */
if (isset($path) && strlen($path) > 1)
{
    /* Which resolve method is configured? */
    if ($cfg["urlpathresolve"] == true)
    {

        $iLangCheck = 0;
        $idcat = prResolvePathViaURLNames($path, $iLangCheck);

    }
    else
    {
        $iLangCheck = 0;

        $idcat = prResolvePathViaCategoryNames($path, $iLangCheck);
        if(($lang != $iLangCheck) && ((int)$iLangCheck != 0)){
            $lang = $iLangCheck;
        }

    }
}

// error page
$aParams = array (
    'client' => $client, 'idcat' => $errsite_idcat[$client], 'idart' => $errsite_idart[$client],
    'lang' => $lang, 'error'=> '1'
);
$errsite = 'Location: ' . Contenido_Url::getInstance()->buildRedirect($aParams);

/////////////////////////////// initial code from front_content.php ////////////////////////////////


####################################################################################################
# Back to the roots

chdir($currentWorkingDir);
