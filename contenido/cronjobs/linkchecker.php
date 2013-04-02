<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Link Checker
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    CONTENIDO Backend Cronjob
 * @version    1.0.1
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}


// CONTENIDO path
$contenidoPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/';

// CONTENIDO startup process
include_once($contenidoPath . 'includes/startup.php');

include_once($backendPath . 'plugins/linkchecker/includes/config.plugin.php');

$backendPath = cRegistry::getBackendPath();

global $cfg;

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