<?php

/**
 * This file handles request to the database filesystem of the frontend.
 *
 * @package    Core
 * @subpackage Frontend
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

global $contenido_path, $client, $load_client, $file;

// Use $_GET['file'] here, $file is not available yet!
if (empty($_GET['file'])) {
    // No need for further processing, if file is missing!
    exit();
}

// Set path to current frontend
$frontend_path = str_replace('\\', '/', realpath(dirname(__FILE__) . '/')) . '/';

// Include the environment definer file
include_once($frontend_path . 'environment.php');

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

$main_dbfs_file_path = dirname(__FILE__);

// Include article view handler
$cfg = cRegistry::getConfig();
include(cRegistry::getBackendPath() . $cfg['path']['includes'] . '/frontend/include.dbfs.php');
