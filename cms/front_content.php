<?php
/**
 * Frontend article view.
 *
 * @package Frontend
 * @subpackage Article
 * @version SVN Revision $Rev:$
 * @version SVN Id $Id: front_content.php 3074 2012-08-28 12:41:42Z konstantinos.katikak $
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

global $contenido_path, $cfg;

// Set path to current frontend
$frontend_path = str_replace('\\', '/', realpath(dirname(__FILE__) . '/')) . '/';

// Include the config file of the frontend to initialize client and language id
include_once($frontend_path . 'data/config/config.php');

// CONTENIDO startup process
include_once($contenido_path . 'includes/startup.php');

// Include article view handler
include(cRegistry::getBackendPath() . $cfg['path']['includes'] . '/frontend/include.front_content.php');

?>