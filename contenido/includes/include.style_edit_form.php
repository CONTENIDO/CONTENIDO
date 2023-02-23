<?php

/**
 * This file contains the backend page for editing style files.
 * @fixme: Rework logic for creation of cApiFileInformation entries
 * It may happpen, that we have already a file but not a entry or vice versa!
 *
 * @package    Core
 * @subpackage Backend
 * @author     Willi Man
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$oClient = cRegistry::getClient();

// Display critical error if client does not exist
if (!$oClient->isLoaded()) {
    $oPage = new cGuiPage("style_edit_form");
    $oPage->displayCriticalError(i18n('No Client selected'));
    $oPage->render();
    return;
}

$client = cSecurity::toInteger(cRegistry::getClientId());

$tmpFile = cSecurity::toString($_REQUEST['tmp_file'] ?? '');

// Initializing editor
$editor = new cGuiSourceEditor($tmpFile);

// Show notice message if backend_file_extension filter is active
if (empty($tmpFile)) {

    // Get system properties for extension filter
    $backend_file_extensions = getSystemProperty('backend', 'backend_file_extensions');

    if ($backend_file_extensions == 'enabled') {
        $editor->displayInfo(sprintf(i18n("Currently only files with the extension %s are displayed in the menu. If you create files with a different extension, they will not be shown on the left side!"), "css"));
    }

}

// Render source editor
$editor->render();
