<?php
/**
 * This file is the main entrance point for the frontend.
 *
 * @package          Core
 * @subpackage       Frontend
 *
 * @author           Olaf Niemann, Jan Lengowski, Timo A. Hummel et al.
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

global $contenido_path, $cfg;

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

// Include the config file of the frontend to initialize client and language id
include_once($frontend_path . 'data/config/' . CON_ENVIRONMENT . '/config.php');

// Clients local configuration
if (file_exists($frontend_path . 'data/config/' . CON_ENVIRONMENT . '/config.local.php')) {
    @include($frontend_path . 'data/config/' . CON_ENVIRONMENT . '/config.local.php');
}

// CONTENIDO startup process
if (!is_file($contenido_path . 'includes/startup.php')) {
    die("<h1>Fatal Error</h1><br>Couldn't include CONTENIDO startup.");
}
include_once($contenido_path . 'includes/startup.php');

// Include article view handler
include(cRegistry::getBackendPath() . $cfg['path']['includes'] . '/frontend/include.front_content.php');

?>