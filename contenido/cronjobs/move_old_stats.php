<?php
/**
 * This file contains the cronjob for moving old statistics into the stat_archive table
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

// CONTENIDO path
$contenidoPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/';

// CONTENIDO startup process
include_once($contenidoPath . 'includes/startup.php');

require_once($cfg['path']['contenido_config'] . 'cfg_actions.inc.php');
include_once(cRegistry::getBackendPath() . $cfg['path']['includes'] . 'functions.stat.php');

if (!isRunningFromWeb() || function_exists('runJob') || $area == 'cronjobs') {
    $db = cRegistry::getDb();
    $year = date('Y');
    $month = date('m');

    if ($month == 1) {
        $month = 12;
        $year = $year - 1;
    } else {
        $month = $month - 1;
    }

    statsArchive(sprintf('%04d%02d', $year, $month));
}

?>