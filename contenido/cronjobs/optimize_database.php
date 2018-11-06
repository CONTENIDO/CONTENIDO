<?php
/**
 * This file contains the cronjob to optimize all database tables.
 *
 * @package    Core
 * @subpackage Cronjob
 *
 * @author     Timo Hummel
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

if (!isRunningFromWeb() || function_exists('runJob') || $area == 'cronjobs') {
    $db = cRegistry::getDb();

    foreach ($cfg['tab'] as $key => $value) {
        $sql = 'OPTIMIZE TABLE ' . $value;
        $db->query($sql);
    }

    if ($cfg['statistics_heap_table']) {
        $sHeapTable = $cfg['tab']['stat_heap_table'];
        buildHeapTable($sHeapTable, $db);
    }
}

?>