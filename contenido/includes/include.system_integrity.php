<?php
/**
 * Project: CONTENIDO
 *
 * Description:
 * CONTENIDO Purge include file to reset some datas(con_code, con_cat_art) and files (log, cache, history)
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.1
 * @author     Munkh-Ulzii Balidar
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.8.12
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('classes', 'class.systemcheck.php');

$tpl->reset();

// Common includes
include_once('../setup/lib/defines.php');
include_once($cfg['path']['contenido'] . 'includes/functions.i18n.php');
include_once($cfg['path']['contenido'] . 'includes/api/functions.api.general.php');
include_once($cfg['path']['contenido'] . 'includes/functions.general.php');
include_once($cfg['path']['contenido'] . 'classes/class.template.php');
include_once('../setup/lib/class.setupcontrols.php');
include_once('../setup/lib/functions.filesystem.php');
include_once('../setup/lib/functions.environment.php');
include_once('../setup/lib/functions.safe_mode.php');
include_once('../setup/lib/functions.mysql.php');
include_once('../setup/lib/functions.phpinfo.php');
include_once('../setup/lib/functions.libraries.php');
include_once('../setup/lib/functions.system.php');
include_once('../setup/lib/functions.sql.php');
include_once('../setup/lib/functions.setup.php');
include_once('../setup/lib/class.setupmask.php');

$cSystemCheck = new cSystemCheck(4, "setup3", "setup5", $db, $cfg, $client, $lang, $cfgClient);
$cSystemCheck->renderSystemCheck();

/*
$cSystemCheck = new cSystemCheck(4, "upgrade3", "upgrade5", $db, $cfg, $client, $lang, $cfgClient);
$cSystemCheck->renderSystemCheck();

$cSystemCheck = new cSystemCheck(4, "migration3", "migration5", $db, $cfg, $client, $lang, $cfgClient);
$cSystemCheck->renderSystemCheck();
*/

?>