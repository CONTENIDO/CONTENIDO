<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Job to set frontendusers active / inactive depending on the date entered in BE
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    CONTENIDO Backend Cronjob
 * @version    1.0.1
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  2007-07-24
 *   modified 2008-06-16, H. Librenz - Hotfix: Added check for malicious script call
 *   modified 2008-07-04, bilal arslan, added security fix
 *   modified 2010-05-20, Murat Purc, standardized CONTENIDO startup and security check invocations, see [#CON-307]
 *   modified 2011-05-12, Dominik Ziegler, forced include of startup.php [#CON-390]
 *   modified 2011-10-12, Murat Purc, absolute path to startup [#CON-447] and some cleanup
 *
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


require_once($cfg['path']['contenido'].$cfg['path']['includes'] . 'pseudo-cron.inc.php');

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