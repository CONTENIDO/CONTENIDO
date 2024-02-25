<?php

/**
 * This file handles the output of a dbfs file. It is used by the two main dbfs
 * file output handler. Preliminary work such as initializing and checking the
 * variables have already been done, the job of this script is to read the dbfs
 * file from the database and output it.
 *
 * @since      CONTENIDO 4.10.2
 * @package    Core
 * @subpackage Frontend
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $contenido_path, $main_dbfs_file_path, $client, $load_client, $file;

chdir($contenido_path);

if (cRegistry::getBackendSessionId()) {
    cRegistry::bootstrap([
        'sess' => 'cSession',
        'auth' => 'cAuthHandlerBackend',
        'perm' => 'cPermission',
    ]);
} else {
    cRegistry::bootstrap([
        'sess' => 'cFrontendSession',
        'auth' => 'cAuthHandlerFrontend',
        'perm' => 'cPermission',
    ]);
}

chdir($main_dbfs_file_path);

// Shorten load time
$client = $load_client;

$dbfs = new cApiDbfsCollection();
$dbfs->outputFile($file);

cRegistry::shutdown(false);
