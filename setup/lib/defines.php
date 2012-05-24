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
 *   modified 2008-07-07, bilal arslan, added security fix
 *   modified 2011-02-23, Murat Purc, added constant for minimum required PHP version
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
     die('Illegal call');
}

if (isset($_SESSION['setuptype'])) {
    switch ($_SESSION['setuptype']) {
        case 'setup':
            define('C_SETUP_STEPS', 8);
            break;
        case 'upgrade':
            define('C_SETUP_STEPS', 7);
            break;
        case 'migration':
            define('C_SETUP_STEPS', 8);
            break;
    }
}

define('C_SETUP_STEPFILE', 'images/steps/s%d.png');
define('C_SETUP_STEPFILE_ACTIVE', 'images/steps/s%da.png');
define('C_SETUP_CONTENIDO_HTML_PATH', '../contenido/');
define('C_SETUP_STEPWIDTH', 28);
define('C_SETUP_STEPHEIGHT', 28);
define('C_SETUP_DEBUG', false);
define('C_SETUP_MAX_CHUNKS_PER_STEP', 50);
define('C_SETUP_MIN_PHP_VERSION', '5.2.0');
define('C_SETUP_VERSION', '4.9.0-alpha2');

?>