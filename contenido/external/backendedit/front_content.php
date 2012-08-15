<?php

/**
 * Frontend article view for CONTENIDO backend.
 *
 * @package Backend
 * @subpackage Article
 * @version SVN Revision $Rev:$
 * @version SVN Id $Id$
 *
 * @author unknown, Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO startup process
include_once('../../includes/startup.php');

// Load base clients settings and change to current clients frontend directory
rereadClients();
chdir($cfgClient[$client]['path']['frontend']);

// Set path to current frontend
cRegistry::setAppVar('frontend_path', $cfgClient[$client]['path']['frontend']);

// Include the config file of the frontend to initialize client and language id
include_once($cfgClient[$client]['config']['path'] . 'config.php');

// Include article view handler
include($cfg['path']['contenido'] . $cfg['path']['includes'] . '/frontend/include.front_content.php');

?>