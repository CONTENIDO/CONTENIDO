<?php

/**
 * This file contains the backend article view.
 *
 * @package    Core
 * @subpackage Backend
 *
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO startup process
include_once('../../includes/startup.php');

$frontendPath = cRegistry::getFrontendPath();

// If directory does not exist, show error message
if (!is_dir($frontendPath)) {
    // Don't use `cGuiNotification()` or `i18n()`, we don't have an initialized  `$belang` at this stage!
    die(sprintf(
        'The given client\'s frontend directory (%s) is not a directory.' 
            . (isset($client) ? '' : ' !! \$client is not set !! Check your request parameters or session'),
        $frontendPath
    ));
}
chdir($frontendPath);

$cfg = cRegistry::getConfig();
$cfgClient = cRegistry::getClientConfig();
$client = cRegistry::getClientId();

// Include the config file of the frontend to initialize client and language id
include_once($cfgClient[$client]['config']['path'] . '/config.php');

// Clients local configuration
if (file_exists($cfgClient[$client]['config']['path'] . '/config.local.php')) {
    @include($cfgClient[$client]['config']['path'] . '/config.local.php');
}

// Include article view handler
include(cRegistry::getBackendPath() . $cfg['path']['includes'] . 'frontend/include.front_content.php');
