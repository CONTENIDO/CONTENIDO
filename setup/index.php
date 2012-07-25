<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO setup script. Main entry point for the setup requests.
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO setup
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  2011-11-01  Murat Purc
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}
define('C_SETUP_PATH', str_replace('\\', '/', realpath(dirname(__FILE__))));
define('C_FRONTEND_PATH', str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')));

include_once('lib/startup.php');

// Detect controller
$controller = 'index';
if (isset($_GET['c'])) {
    switch ($_GET['c']) {
        case 'index';
            $controller = 'index';
            break;
        case 'db';
            $controller = 'db';
            break;
        case 'config';
            $controller = 'config';
            break;
        default:
            // we should never land here
            die('Illegal setup call');
    }
}

// Include detected controller
$fileName = 'include.' . $controller . '.controller.php';
$filePathName = C_SETUP_PATH . '/lib/' . $fileName;
if (is_file($filePathName)) {
    include($filePathName);
} else {
    die('Illegal setup call 2');
}

?>