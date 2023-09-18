<?php

/**
 * This file contains CONTENIDO constants.
 *
 * @since      CONTENIDO 4.10.2
 * @package    Core
 * @subpackage Backend
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// CONTENIDO version
defined('CON_VERSION') || define('CON_VERSION', '4.10.1');

// Minimum supported PHP version
define('CON_MIN_PHP_VERSION', '7.0.0');

// Not supported MySQL SQL modes
if (!defined('CON_DB_NOT_SUPPORTED_SQL_MODES')) {
    define(
        'CON_DB_NOT_SUPPORTED_SQL_MODES',
        'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE'
    );
}

// Database defaults
define('CON_DB_PREFIX', 'con');
define('CON_DB_CHARSET', 'utf8mb4');
define('CON_DB_COLLATION', 'utf8mb4_general_ci');
define('CON_DB_ENGINE', 'InnoDB');

// Flag to strip slashes
if (function_exists('get_magic_quotes_gpc')) {
    define('CON_STRIPSLASHES', !@get_magic_quotes_gpc());
} else {
    define('CON_STRIPSLASHES', true);
}

define('CON_PREDICT_SUFFICIENT', 1);
define('CON_PREDICT_NOTPREDICTABLE', 2);
define('CON_PREDICT_CHANGEPERM_SAMEOWNER', 3);
define('CON_PREDICT_CHANGEPERM_SAMEGROUP', 4);
define('CON_PREDICT_CHANGEPERM_OTHERS', 5);
define('CON_PREDICT_CHANGEUSER', 6);
define('CON_PREDICT_CHANGEGROUP', 7);
define('CON_PREDICT_WINDOWS', 8);

define('CON_BASEDIR_NORESTRICTION', 1);
define('CON_BASEDIR_DOTRESTRICTION', 2);
define('CON_BASEDIR_RESTRICTIONSUFFICIENT', 3);
define('CON_BASEDIR_INCOMPATIBLE', 4);

define('CON_IMAGERESIZE_GD', 1);
define('CON_IMAGERESIZE_IMAGEMAGICK', 2);
define('CON_IMAGERESIZE_CANTCHECK', 3);
define('CON_IMAGERESIZE_NOTHINGAVAILABLE', 4);

define('CON_EXTENSION_AVAILABLE', 1);
define('CON_EXTENSION_UNAVAILABLE', 2);
define('CON_EXTENSION_CANTCHECK', 3);
