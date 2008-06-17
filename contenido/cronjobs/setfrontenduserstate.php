<?php
/**
* $RCSfile$
*
* Description: Job to set frontendusers active / inactive depending on the date entered in BE
*
* @version $Revision$
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2007-07-24
* modified 2007-10-12
*  modified 2008-06-16, H. Librenz - Hotfix: Added check for malicious script call
* }}
*
* $Id$
*/
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
include_once ($cfg['path']['contenido'].$cfg["path"]["classes"] . 'class.inuse.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["includes"] . 'cfg_sql.inc.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["includes"] . 'cfg_language_de.inc.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["includes"] . 'functions.general.php');
include_once ($cfg['path']['contenido'].$cfg["path"]["includes"] . 'functions.stat.php');

require_once($cfg['path']['contenido'].$cfg["path"]["includes"] . 'pseudo-cron.inc.php');

if (!isRunningFromWeb() || function_exists("runJob") || $area == "cronjobs")
{
	$db = new DB_Contenido();

	$sSql = "UPDATE " . $cfg['tab']['frontendusers'] . "
				SET active = 0
				WHERE
					(valid_to < NOW() AND valid_to != '0000-00-00 00:00:00')
					OR
					(valid_from > NOW() AND valid_from != '0000-00-00 00:00:00') ";
	//echo $sSql;
	$db->query($sSql);

}
?>
