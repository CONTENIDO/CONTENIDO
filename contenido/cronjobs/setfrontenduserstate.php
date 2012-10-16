<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Job to set frontendusers active / inactive depending on the date entered in BE
 * 
 * Requirements: 
 * @con_php_req 5
 * @con_template <Templatefiles>
 * @con_notice <Notice>
 * 
 *
 * @package    Contenido Backend <Area>
 * @version    1.0.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   $Id: setfrontenduserstate.php 1157 2010-05-20 14:10:43Z xmurrix $:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

global $cfg, $area;

// CONTENIDO path
$contenidoPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/';

// CONTENIDO startup process
include_once($contenidoPath . 'includes/startup.php');

require_once($cfg['path']['contenido'] . $cfg['path']['includes'] . 'pseudo-cron.inc.php');

if (!isRunningFromWeb() || function_exists('runJob') || $area == 'cronjobs') {
    $db = new DB_Contenido();

    $sSql = "UPDATE " . $cfg['tab']['frontendusers'] . "
            SET active = 0
            WHERE (valid_to < NOW() AND valid_to != '0000-00-00 00:00:00')
            OR (valid_from > NOW() AND valid_from != '0000-00-00 00:00:00')";
    //echo $sSql;
    $db->query($sSql);
}

?>