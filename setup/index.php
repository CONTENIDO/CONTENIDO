<?php
/**
 * CONTENIDO setup script. Main entry point for the setup requests.
 *
 * @package    Setup
 * @subpackage Setup
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

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
$filePathName = CON_SETUP_PATH . '/lib/' . $fileName;
if (is_file($filePathName)) {
    include($filePathName);
} else {
    die('Illegal setup call 2');
}

?>