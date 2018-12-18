<?php
/**
 * This file contains the cronjob of the linkchecker plugin.
 *
 * @package    Plugin
 * @subpackage Linkchecker
 *
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

global $cfg;

// CONTENIDO path
$contenidoPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/';

// CONTENIDO startup process
include_once($contenidoPath . 'includes/startup.php');

$backendPath = cRegistry::getBackendPath();

include_once($backendPath . 'plugins/linkchecker/includes/config.plugin.php');

if (!isRunningFromWeb() || function_exists('runJob') || $area == 'cronjobs') {
    $db = cRegistry::getDb();

    // Start linkchecker
    $cronjob = true;
    $_REQUEST['mode'] = 2;

    $sql = "SELECT idlang FROM " . $cfg['tab']['lang'] . " WHERE active = '1'";
    $db->query($sql);

    if ($db->numRows() > 1) {
        $langart = 0;
    } else {
        $db->nextRecord();
        $langart = $db->f('idlang');
    }

    include_once($backendPath . 'plugins/linkchecker/includes/include.linkchecker.php');
}
?>