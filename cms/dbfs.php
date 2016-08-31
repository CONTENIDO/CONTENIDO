<?php
/**
 * This file handles request to the database filesystem of the frontend.
 *
 * @package          Core
 * @subpackage       Frontend
 *
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

global $contenido_path, $contenido, $client, $load_client, $file;

// Set path to current frontend
$frontend_path = str_replace('\\', '/', realpath(dirname(__FILE__) . '/')) . '/';

/*
 * Do not edit this value!
*
* If you want to set a different enviroment value please define it in your .htaccess file
* or in the server configuration.
*
* SetEnv CON_ENVIRONMENT development
*/
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

// Include the config file of the frontend to init the Client and Language Id
include_once($frontend_path . 'data/config/' . CON_ENVIRONMENT . '/config.php');

// CONTENIDO startup process
if (!is_file($contenido_path . 'includes/startup.php')) {
    die("<h1>Fatal Error</h1><br>Couldn't include CONTENIDO startup.");
}
include_once($contenido_path . 'includes/startup.php');

chdir($contenido_path);

if ($_REQUEST["contenido"]) {
    cRegistry::bootstrap(array(
        'sess' => 'cSession',
        'auth' => 'cAuthHandlerBackend',
        'perm' => 'cPermission'
    ));
} else {
    cRegistry::bootstrap(array(
        'sess' => 'cFrontendSession',
        'auth' => 'cAuthHandlerFrontend',
        'perm' => 'cPermission'
    ));
}

chdir(dirname(__FILE__));

// Shorten load time
$client = $load_client;

$dbfs = new cApiDbfsCollection();
$dbfs->outputFile($file);

cRegistry::shutdown();

?>