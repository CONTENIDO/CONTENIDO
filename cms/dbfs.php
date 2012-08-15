<?php
/**
 * Database file system file output.
 *
 * @package Frontend
 * @subpackage DBFS
 * @version SVN Revision $Rev:$
 * @version SVN Id $Id$
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

// Set path to current frontend
cRegistry::setAppVar('frontend_path', str_replace('\\', '/', realpath(dirname(__FILE__) . '/')) . '/');

// Include the config file of the frontend to init the Client and Language Id
include_once(cRegistry::getAppVar('frontend_path') . 'data/config/config.php');

// CONTENIDO startup process
include_once($contenido_path . 'includes/startup.php');

if ($contenido) {
    cRegistry::bootstrap(array('sess' => 'cSession',
                    'auth' => 'Contenido_Challenge_Crypt_Auth',
                    'perm' => 'cPermission'));
} else {
    cRegistry::bootstrap(array('sess' => 'cFrontendSession',
                    'auth' => 'Contenido_Frontend_Challenge_Crypt_Auth',
                    'perm' => 'cPermission'));
}

// Shorten load time
$client = $load_client;

$dbfs = new cApiDbfsCollection();
$dbfs->outputFile($file);

cRegistry::shutdown();

?>