<?php

// Load environment config file
$configEnv = str_replace('\\', '/', realpath(dirname(__FILE__) . '/')) . '/data/config/config.environment.php';
if (file_exists($configEnv)) {
    include_once($configEnv);
}

/**
 * Do not edit this value!
 *
 * If you want to set a different environment value please define it in
 * your .htaccess file or in the server configuration.
 *
 * SetEnv CON_ENVIRONMENT development
 */
if (!defined('CON_ENVIRONMENT')) {
    if (getenv('CONTENIDO_ENVIRONMENT')) {
        define('CON_ENVIRONMENT', getenv('CONTENIDO_ENVIRONMENT'));
    } elseif (getenv('CON_ENVIRONMENT')) {
        define('CON_ENVIRONMENT', getenv('CON_ENVIRONMENT'));
    } else {
        define('CON_ENVIRONMENT', 'production');
    }
}
