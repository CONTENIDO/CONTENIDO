<?php

/**
 * This file contains the backend page for style files overview.
 *
 * @package Core
 * @subpackage Backend
 * @author Willi Man
 * @author Olaf Niemann
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$files = new cGuiFileOverview($cfgClient[$client]['css']['path'], stripslashes($_REQUEST['file']), 'css');

// Get system properties for extension filter
$backend_file_extensions = getSystemProperty('backend', 'backend_file_extensions');

if ($backend_file_extensions == "enabled") {
    $files->setFileExtension('css');
}

// Render file overview
$files->render();
