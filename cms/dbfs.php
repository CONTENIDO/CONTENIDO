<?php
/**
 * Database file system file output.
 *
 * @package Frontend
 * @subpackage DBFS
 * @version SVN Revision $Rev:$
 * @version SVN Id $Id: dbfs.php 3288 2012-09-22 18:36:28Z dominik.ziegler $
 *
 * @author unknown
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

global $contenido_path, $contenido, $client, $load_client, $file;

// Set path to current frontend
$frontend_path = str_replace('\\', '/', realpath(dirname(__FILE__) . '/')) . '/';

// Include the config file of the frontend to init the Client and Language Id
include_once($frontend_path . 'data/config/config.php');

// CONTENIDO startup process
if (!is_file($contenido_path . 'includes/startup.php')) {
    die("<h1>Fatal Error</h1><br>Couldn't include CONTENIDO startup.");
}
include_once($contenido_path . 'includes/startup.php');

if ($contenido) {
    cRegistry::bootstrap(array(
        'sess' => 'cSession',
        'auth' => 'cAuthHandlerBackend',
        'perm' => 'cPermission'
    ));
} else {
    cRegistry::bootstrap(array(
        'sess' => 'cFrontendSession',
        'auth' => 'cAuthHandlerFrontend',
        'perm' => 'cPermission'
    ));
}

// Shorten load time
$client = $load_client;

$dbfs = new cApiDbfsCollection();
$dbfs->outputFile($file);

cRegistry::shutdown();

?>