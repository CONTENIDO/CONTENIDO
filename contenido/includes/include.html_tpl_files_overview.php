<?php

/**
 * This file contains the backend page for html template files overview.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Willi Man
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$oClient = cRegistry::getClient();

// Display critical error if client does not exist
if (!$oClient->isLoaded()) {
    $oPage = new cGuiPage("html_tpl_files_overview");
    $oPage->displayCriticalError(i18n('No Client selected'));
    $oPage->render();
    return;
}

$client = cSecurity::toInteger(cRegistry::getClientId());

$cfgClient = cRegistry::getClientConfig();

$file = cSecurity::toString($_REQUEST['file'] ?? '');

$files = new cGuiFileOverview($cfgClient[$client]['tpl']['path'], stripslashes($file), 'html');

// Get system properties for extension filter
$backend_file_extensions = getSystemProperty('backend', 'backend_file_extensions');

if ($backend_file_extensions == 'enabled') {
    $files->setFileExtension(['html', 'tpl']);
}

// Render file overview
$files->render();
