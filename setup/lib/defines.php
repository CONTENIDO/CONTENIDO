<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * 
 * Requirements: 
 * @con_php_req 5
 * 
 *
 * @package    Contenido Backend <Area>
 * @version    0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *
 *   $Id: defines.php 1228 2010-10-13 08:24:14Z timo.trautmann $:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

if (array_key_exists('setuptype', $_SESSION)) {
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

define('CON_SETUP_MYSQL', 'mysql');
define('CON_SETUP_MYSQLI', 'mysqli');

define('CON_SETUP_DEBUG', false);

define('C_SETUP_STEPFILE', 'images/steps/s%d.png');
define('C_SETUP_STEPFILE_ACTIVE', 'images/steps/s%da.png');
define('C_SETUP_STEPWIDTH', 28);
define('C_SETUP_STEPHEIGHT', 28);
define('C_SETUP_DBCHARSET', 'latin1');
define('C_SETUP_VERSION', '4.8.19');

?>