<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * This file initializes the view of an article in the Backend.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 * @package    CONTENIDO Backend external
 * @version    4.9
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *     $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO startup process
include_once('../../includes/startup.php');

// Load base clients settings and change to current clients frontend directory
rereadClients();
chdir($cfgClient[$client]['path']['frontend']);

// Include the config file of the frontend to initialize client and language id
include_once('config.php');

// Include article view handler
include($cfg['path']['contenido'] . $cfg['path']['includes'] . '/frontend/include.front_content.php');

?>