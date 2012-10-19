<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    CONTENIDO Setup
 * @version    0.2.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

if (isset($_SESSION['setuptype'])) {
    switch ($_SESSION['setuptype']) {
        case 'setup':
            /**
             * @deprecated 2012-09-06 Constant has been renamed to CON_SETUP_STEPS
             */
            define('C_SETUP_STEPS', 8);
            define('CON_SETUP_STEPS', 8);
            break;
        case 'upgrade':
            /**
             * @deprecated 2012-09-06 Constant has been renamed to CON_SETUP_STEPS
             */
            define('C_SETUP_STEPS', 7);
            define('CON_SETUP_STEPS', 7);
            break;
    }
}

/**
 * @deprecated 2012-09-06 Unused in CONTENIDO core - should not be used any longer
 */
define('C_SETUP_STEPFILE', 'images/steps/s%d.png');

/**
 * @deprecated 2012-09-06 Unused in CONTENIDO core - should not be used any longer
 */
define('C_SETUP_STEPFILE_ACTIVE', 'images/steps/s%da.png');

/**
 * @deprecated 2012-09-06 Constant has been renamed to CON_SETUP_CONTENIDO_HTML_PATH
 */
define('C_SETUP_CONTENIDO_HTML_PATH', '../contenido/');
define('CON_SETUP_CONTENIDO_HTML_PATH', '../contenido/');

/**
 * @deprecated 2012-09-06 Unused in CONTENIDO core - should not be used any longer
 */
define('C_SETUP_STEPWIDTH', 28);

/**
 * @deprecated 2012-09-06 Unused in CONTENIDO core - should not be used any longer
 */
define('C_SETUP_STEPHEIGHT', 28);

/**
 * @deprecated 2012-09-06 Constant has been renamed to CON_SETUP_DEBUG
 */
define('C_SETUP_DEBUG', false);
define('CON_SETUP_DEBUG', false);

/**
 * @deprecated 2012-09-06 Constant has been renamed to CON_SETUP_MAX_CHUNKS_PER_STEP
 */
define('C_SETUP_MAX_CHUNKS_PER_STEP', 50);
define('CON_SETUP_MAX_CHUNKS_PER_STEP', 50);

/**
 * @deprecated 2012-09-06 Constant has been renamed to CON_SETUP_MIN_PHP_VERSION
 */
define('C_SETUP_MIN_PHP_VERSION', '5.2.0');
define('CON_SETUP_MIN_PHP_VERSION', '5.2.0');

/**
 * @deprecated 2012-09-06 Constant has been renamed to CON_SETUP_VERSION
 */
define('C_SETUP_VERSION', '4.9.0-beta1');
define('CON_SETUP_VERSION', '4.9.0-beta1');

?>