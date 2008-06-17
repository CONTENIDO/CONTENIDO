<?php
/**
 * File      :   $RCSfile: move_old_stats.php,v $
 * Project   :   Contenido
 * Descr     :   Cron Job to move old statistics into the stat_archive table
 *
 * Author    :   Timo A. Hummel
 *
 * Created   :   26.05.2003
 * Modified  :   $Date: 2007/10/12 13:53:00 $
 *
 * @version $Revision$
 * @copyright four for business AG, www.4fb.de
 *
 * @internal  {
 *  modified 2008-06-16, H. Librenz - Hotfix: Added check for malicious script call
 *
 *  $Id: move_old_stats.php,v 1.11 2006/04/28 09:20:55 timo.hummel Exp $
 * }
 **/
if (isset($_REQUEST['cfg']) || isset($_REQUEST['contenido_path'])) {
    die ('Illegal call!');
}

if (isset($cfg['path']['contenido'])) {
	include_once ($cfg['path']['contenido'].$cfg["path"]["includes"] . 'startup.php');
} else {
	include_once ('../includes/startup.php');
}

include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.user.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.xml.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.navigation.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.template.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.backend.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.table.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.notification.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.area.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.layout.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.client.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.cat.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.treeitem.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["includes"] . 'cfg_sql.inc.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["includes"] . 'cfg_language_de.inc.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["includes"] . 'functions.general.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["includes"] . 'functions.stat.php');

if (!isRunningFromWeb() || function_exists("runJob") || $area == "cronjobs")
{

    $db = new DB_Contenido;
    $year = date("Y");
    $month = date("m");

    if ($month == 1)
    {
    	$month = 12;
    	$year = $year -1;
    } else {
    	$month = $month -1;
    }

    statsArchive(sprintf("%04d%02d",$year,$month));

}
?>
