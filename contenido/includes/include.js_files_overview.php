<?php

/**
 * This file contains the backend page for javascript files overview.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Willi Man
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$client = cRegistry::getClientId();
$cfgClient = cRegistry::getClientConfig();

$file = (isset($_REQUEST['file'])) ? cSecurity::toString($_REQUEST['file']) : '';

$files = new cGuiFileOverview($cfgClient[$client]['js']['path'], stripslashes($file), 'js');

// Get system properties for extension filter
$backend_file_extensions = getSystemProperty('backend', 'backend_file_extensions');

if ($backend_file_extensions == 'enabled') {
    $files->setFileExtension('js');
}

// Render file overview
$files->render();
