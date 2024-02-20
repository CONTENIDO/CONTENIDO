<?php

/**
 * This file contains the cronjob for time management and moving articles.
 *
 * @package    Core
 * @subpackage Cronjob
 * @author     Holger Librenz
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

global $cfg;

// CONTENIDO path
$contenidoPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/';

// CONTENIDO startup process
include_once($contenidoPath . 'includes/startup.php');

// con_cli Plugin
if ('cli' === cString::getPartOfString(PHP_SAPI, 0, 3)) {
    $client = $lang = 1;
    require_once($contenidoPath . $cfg['path']['includes'] . 'functions.includePluginConf.php');
    cApiCecHook::execute('Contenido.Frontend.AfterLoadPlugins');
}

include_once(cRegistry::getBackendPath() . $cfg['path']['includes'] . 'functions.con.php');

if (!isRunningFromWeb() || function_exists('runJob') || $area == 'cronjobs') {
    $db = cRegistry::getDb();

    conFlagOnOffline();

    conMoveArticles();
}

?>