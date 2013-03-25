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
            define('CON_SETUP_STEPS', 8);
            break;
        case 'upgrade':
            define('CON_SETUP_STEPS', 6);
            break;
    }
}

define('CON_SETUP_CONTENIDO_HTML_PATH', '../contenido/');

define('CON_SETUP_DEBUG', false);

define('CON_SETUP_MAX_CHUNKS_PER_STEP', 50);

define('CON_SETUP_MIN_PHP_VERSION', '5.2.3');

define('CON_SETUP_VERSION', '4.9.0-rc1');

?>