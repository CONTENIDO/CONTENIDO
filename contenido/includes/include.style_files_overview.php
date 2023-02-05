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

$client = cSecurity::toInteger(cRegistry::getClientId());

// Display critical error if no valid client is selected
if ($client < 1) {
    $oPage = new cGuiPage('style_files_overview');
    $oPage->displayCriticalError(i18n("No Client selected"));
    $oPage->render();
    return;
}

$cfgClient = cRegistry::getClientConfig();

$file = cSecurity::toString($_REQUEST['file'] ?? '');

$files = new cGuiFileOverview($cfgClient[$client]['css']['path'], stripslashes($file), 'css');

// Get system properties for extension filter
$backend_file_extensions = getSystemProperty('backend', 'backend_file_extensions');

if ($backend_file_extensions == 'enabled') {
    $files->setFileExtension('css');
}

// Render file overview
$files->render();
