<?php
/**
 * This file contains the cronjob to activate/deactivate frontend users by time.
 *
 * @package    Core
 * @subpackage Cronjob
 *
 * @author     Rudi Bieller
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

// @todo Do we really need this include here?
require_once(cRegistry::getBackendPath() . $cfg['path']['includes'] . 'pseudo-cron.inc.php');

if (!isRunningFromWeb() || function_exists('runJob') || $area == 'cronjobs') {
    $db = cRegistry::getDb();

    $sSql = "UPDATE " . $cfg['tab']['frontendusers'] . "
            SET active = 0
            WHERE (valid_to < NOW() AND valid_to != '0000-00-00 00:00:00')
            OR (valid_from > NOW() AND valid_from != '0000-00-00 00:00:00')";
    //echo $sSql;
    $db->query($sSql);
}

?>