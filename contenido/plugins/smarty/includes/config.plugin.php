<?php
/**
 * Wrapper class for Integration of smarty.
 *
 * @package Plugin
 * @subpackage SmartyWrapper
 * @version SVN Revision $Rev:$
 *
 * @author Andreas Dieter
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

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