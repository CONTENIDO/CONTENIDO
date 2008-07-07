<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Link Checker
 * 
 * Requirements: 
 * @con_php_req 5
 *
 * @package    Contenido Backend <Area>
 * @version    <version>
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  2007-11-01
 *   modified 2008-06-16, H. Librenz - Hotfix: checking for malicious calls added
 *   modified 2008-07-04, bilal arslan, added security fix
 *
 *   $Id$:
 * }}
 * 
 */
define("CON_FRAMEWORK", true);

if (isset($_REQUEST['cfg']) || isset ($_REQUEST['contenido_path'])) {
    die ('Illegal call!');
}

include_once('../includes/startup.php');

include_once($cfg['path']['contenido'] . 'plugins/linkchecker/includes/config.plugin.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.user.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.xml.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.navigation.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.template.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.backend.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.table.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.notification.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.area.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.layout.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.client.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.cat.php');
include_once($cfg['path']['contenido'].$cfg['path']['classes'] . 'class.treeitem.php');
include_once($cfg['path']['contenido'].$cfg['path']['includes'] . 'cfg_sql.inc.php');
include_once($cfg['path']['contenido'].$cfg['path']['includes'] . 'functions.general.php');

global $cfg;

if (!isRunningFromWeb() || function_exists("runJob") || $area == "cronjobs") {

    // Create Contenido DB_class
    $db = new DB_Contenido;

    // Start linkchecker
    $cronjob = true;
    $_REQUEST['mode'] = 2;

    $sql = "SELECT idlang FROM " . $cfg['tab']['lang'] . " WHERE active = '1'";
    $db->query($sql);

    if($db->num_rows() > 1) {
        $langart = 0;
    } else {
        $db->next_record();
        $langart = $db->f("idlang");
    }

    include_once($cfg['path']['contenido'] . 'plugins/linkchecker/includes/include.linkchecker.php');

}
?>