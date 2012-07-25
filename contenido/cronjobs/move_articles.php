<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Cron Job to implement the move article feature
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    CONTENIDO Backend Cronjob
 * @version    1.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  2003-05-26
 *   modified 2008-06-16, H. Librenz - Hotfix: Added check for malicious calls
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

include_once($cfg['path']['contenido'].$cfg['path']['includes'] . 'functions.con.php');

if (!isRunningFromWeb() || function_exists('runJob') || $area == 'cronjobs') {
    $db = cRegistry::getDb();

    conFlagOnOffline();

    conMoveArticles();
}

?>