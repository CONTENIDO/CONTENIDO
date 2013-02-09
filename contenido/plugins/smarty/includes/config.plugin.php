<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Integration of smarty as plugin
 *
 *
 *
 * @package    Contenido Template classes
 * @version    1.3.0
 * @author     Andreas Dieter
 * @copyright  four for business AG <info@contenido.org>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *     created     2010-07-22
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

$client = (isset($client)) ? $client : $load_client;

// Load smarty
if (!defined('SMARTY_DIR')) {
    define('SMARTY_DIR', $cfg['path']['contenido'] . 'plugins/smarty/smarty_source/');
}

require_once(SMARTY_DIR . 'Smarty.class.php');

plugin_include('smarty', 'classes/class.smarty.wrapper.php');
plugin_include('smarty', 'classes/class.smarty.frontend.php');
plugin_include('smarty', 'classes/class.smarty.backend.php');

try {
    new cSmartyFrontend($cfg, $cfgClient[$client], true);
} catch (Exception $e) {
    cWarning($e->getFile(), $e->getLine(), $e->getMessage());
}
?>