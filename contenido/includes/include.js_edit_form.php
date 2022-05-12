<?php

/**
 * This file contains the backend page for editing javascript files.
 * @fixme: Rework logic for creation of cApiFileInformation entries
 * It may happpen, that we have already a file but not a entry or vice versa!
 *
 * @package Core
 * @subpackage Backend
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$tmpFile = (isset($_REQUEST['tmp_file'])) ? cSecurity::toString($_REQUEST['tmp_file']) : '';

// Initializing editor
$editor = new cGuiSourceEditor($tmpFile);

// Show notice message if backend_file_extension filter is active
if (empty($tmpFile)) {

    // Get system properties for extension filter
    $backend_file_extensions = getSystemProperty('backend', 'backend_file_extensions');

    if ($backend_file_extensions == 'enabled') {
        $editor->displayInfo(sprintf(i18n("Currently only files with the extension %s are displayed in the menu. If you create files with a different extension, they will not be shown on the left side!"), "js"));
    }

}

// Render source editor
$editor->render();
