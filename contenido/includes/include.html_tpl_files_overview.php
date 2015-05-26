<?php

/**
 * This file contains the backend page for html template files overview.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Willi Man
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$files = new cGuiFileOverview($cfgClient[$client]['tpl']['path'], stripslashes($_REQUEST['file']), 'html');

// Get system properties for extension filter
$backend_file_extensions = getSystemProperty('backend', 'backend_file_extensions');

if ($backend_file_extensions == "enabled") {
    $files->setFileExtension(array('html', 'tpl'));
}

// Render file overview
$files->render();
