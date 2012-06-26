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
 *
 * {@internal
 *   created  2007-11-01
 *   modified 2008-06-16, H. Librenz - Hotfix: checking for malicious calls added
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

include_once($cfg['path']['contenido'] . 'plugins/linkchecker/includes/config.plugin.php');

global $cfg;

if (!isRunningFromWeb() || function_exists('runJob') || $area == 'cronjobs') {
    $db = cRegistry::getDb();

    // Start linkchecker
    $cronjob = true;
    $_REQUEST['mode'] = 2;

    $sql = "SELECT idlang FROM " . $cfg['tab']['lang'] . " WHERE active = '1'";
    $db->query($sql);

    if($db->num_rows() > 1) {
        $langart = 0;
    } else {
        $db->next_record();
        $langart = $db->f('idlang');
    }

    include_once($cfg['path']['contenido'] . 'plugins/linkchecker/includes/include.linkchecker.php');
}

?>