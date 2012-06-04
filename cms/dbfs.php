<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Database file system file output.
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    CONTENIDO Frontend
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
    define('CON_FRAMEWORK', true);
}

$contenido_path = '';
// Include the config file of the frontend to init the Client and Language Id
include_once('config.php');

// CONTENIDO startup process
include_once($contenido_path . 'includes/startup.php');

if ($contenido) {
    cRegistry::bootstrap(array('sess' => 'Contenido_Session',
                    'auth' => 'Contenido_Challenge_Crypt_Auth',
                    'perm' => 'Contenido_Perm'));
} else {
    cRegistry::bootstrap(array('sess' => 'Contenido_Frontend_Session',
                    'auth' => 'Contenido_Frontend_Challenge_Crypt_Auth',
                    'perm' => 'Contenido_Perm'));
}

// Shorten load time
$client = $load_client;

$dbfs = new cApiDbfsCollection();
$dbfs->outputFile($file);

cRegistry::shutdown();

?>