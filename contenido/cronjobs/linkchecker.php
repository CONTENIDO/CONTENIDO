<?php

/**
 * This file contains the cronjob of the linkchecker plugin.
 *
 * @package    Plugin
 * @subpackage Linkchecker
 * @author     Frederic Schneider
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
$contenidoPath = str_replace('\\', '/', realpath(__DIR__  . '/../')) . '/';

// CONTENIDO startup process
include_once($contenidoPath . 'includes/startup.php');

$backendPath = cRegistry::getBackendPath();
$area = cRegistry::getArea();

include_once($backendPath . 'plugins/linkchecker/includes/config.plugin.php');

if (!isRunningFromWeb() || function_exists('runJob') || $area == 'cronjobs') {
    $db = cRegistry::getDb();

    // Start linkchecker
    cRegistry::setAppVar('pluginLinkcheckerIsCronjob', true);
    $_REQUEST['mode'] = 2;

    $sql = "SELECT idlang FROM " . cDb::getTableName('lang') . " WHERE active = '1'";
    $db->query($sql);

    if ($db->numRows() > 1) {
        $langart = 0;
    } else {
        $db->nextRecord();
        $langart = $db->f('idlang');
    }

    include_once($backendPath . 'plugins/linkchecker/includes/include.linkchecker.php');
}
