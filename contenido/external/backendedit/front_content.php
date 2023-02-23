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

// if directory does not exist, show error message
if (!is_dir($frontendPath)) {
    $notification = new cGuiNotification();
    $notification->displayMessageBox(cGuiNotification::LEVEL_ERROR, i18n('The given client\'s frontend directory (%s) is not a directory.', $frontendPath));
    exit;
}
chdir($frontendPath);

// Include the config file of the frontend to initialize client and language id
include_once($cfgClient[$client]['config']['path'] . '/config.php');

// Clients local configuration
if (file_exists($cfgClient[$client]['config']['path'] . '/config.local.php')) {
    @include($cfgClient[$client]['config']['path'] . '/config.local.php');
}

// Include article view handler
include(cRegistry::getBackendPath() . $cfg['path']['includes'] . 'frontend/include.front_content.php');

?>
