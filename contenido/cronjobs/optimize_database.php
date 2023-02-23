<?php

/**
 * This file contains the cronjob to optimize all database tables.
 *
 * @package    Core
 * @subpackage Cronjob
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO path
$contenidoPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/';

// CONTENIDO startup process
include_once($contenidoPath . 'includes/startup.php');

if (!isRunningFromWeb() || function_exists('runJob') || $area == 'cronjobs') {
    $cfg = cRegistry::getConfig();
    $db = cRegistry::getDb();

    foreach ($cfg['tab'] as $key => $value) {
        if (is_string($value) && !empty($value)) {
            $sql = 'OPTIMIZE TABLE `%s`';
            $db->query($sql, $value);
        }
    }

    if ($cfg['statistics_heap_table']) {
        $sHeapTable = $cfg['tab']['stat_heap_table'];
        buildHeapTable($sHeapTable, $db);
    }
}

?>