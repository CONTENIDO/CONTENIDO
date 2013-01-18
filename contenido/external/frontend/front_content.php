<?php
/**
 * Frontend article view.
 *
 * @package Frontend
 * @subpackage Article
 * @version SVN Revision $Rev:$
 * @version SVN Id $Id$
 *
 * @author Olaf Niemann, Jan Lengowski, Timo A. Hummel et al., Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// Set path to current frontend
$frontend_path = str_replace('\\', '/', realpath(dirname(__FILE__) . '/')) . '/';

// Include the config file of the frontend to initialize client and language id
include_once($frontend_path . 'data/config/config.php');

// CONTENIDO startup process
if (!is_file($contenido_path . 'includes/startup.php')) {
    die("<h1>Fatal Error</h1><br>Couldn't include CONTENIDO startup.");
}
include_once($contenido_path . 'includes/startup.php');

// Include article view handler
include(cRegistry::getBackendPath() . $cfg['path']['includes'] . '/frontend/include.front_content.php');

?>