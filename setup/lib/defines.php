<?php

/**
 * This file contains the setup constants
 *
 * @package    Setup
 * @subpackage Setup
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (isset($_SESSION['setuptype'])) {
    switch ($_SESSION['setuptype']) {
        case 'setup':
            define('CON_SETUP_STEPS', 8);
            break;
        case 'upgrade':
            define('CON_SETUP_STEPS', 6);
            break;
    }
}

define('CON_SETUP_PATH', str_replace('\\', '/', realpath(__DIR__ . '/../')));
define('CON_FRONTEND_PATH', str_replace('\\', '/', realpath(__DIR__ . '/../../')));

define('CON_SETUP_MYSQLI', 'mysqli');
define('CON_SETUP_MYSQL', 'mysql');

define('CON_SETUP_CONTENIDO_HTML_PATH', '../contenido/');

define('CON_SETUP_DEBUG', false);

define('CON_SETUP_MAX_CHUNKS_PER_STEP', 50);

define('CON_SETUP_MIN_PHP_VERSION', '7.0.0');

define('CON_SETUP_DB_CHARSET', 'utf8');

define('CON_SETUP_DB_OPTION_MYSQLI_INIT_COMMAND', "SET SESSION sql_mode='';");

define('CON_SETUP_VERSION', '4.10.1');
