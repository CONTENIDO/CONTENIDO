<?php
/**
 * This file handles request to the database filesystem of the frontend.
 *
 * @package          Core
 * @subpackage       Frontend
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

global $contenido_path, $client, $load_client, $file;

$file = $file ?? '';
if (empty($file)) {
    // No need for further processing, if file is missing!
    exit();
}

// Set path to current frontend
$frontend_path = str_replace('\\', '/', realpath(dirname(__FILE__) . '/')) . '/';

// Include the environment definer file
include_once($frontend_path . '../../environment.php');

// Include the config file of the frontend to init the Client and Language Id
include_once($frontend_path . 'data/config/' . CON_ENVIRONMENT . '/config.php');

// CONTENIDO startup process
if (!is_file($contenido_path . 'includes/startup.php')) {
    die("<h1>Fatal Error</h1><br>Couldn't include CONTENIDO startup.");
}
include_once($contenido_path . 'includes/startup.php');

chdir($contenido_path);

if (cRegistry::getBackendSessionId()) {
    cRegistry::bootstrap(
        [
            'sess' => 'cSession',
            'auth' => 'cAuthHandlerBackend',
            'perm' => 'cPermission',
        ]
    );
} else {
    cRegistry::bootstrap(
        [
            'sess' => 'cFrontendSession',
            'auth' => 'cAuthHandlerFrontend',
            'perm' => 'cPermission',
        ]
    );
}

chdir(dirname(__FILE__));

// Shorten load time
$client = $load_client;

$dbfs = new cApiDbfsCollection();
$dbfs->outputFile($file);

cRegistry::shutdown();
