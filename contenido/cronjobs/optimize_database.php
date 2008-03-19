<?php

/*****************************************
* File      :   $RCSfile: optimize_database.php,v $
* Project   :   Contenido
* Descr     :   Cron Job to move old statistics into the stat_archive table
*
* Author    :   Timo A. Hummel
*               
* Created   :   26.05.2003
* Modified  :   $Date: 2007/10/12 13:53:00 $
*
* © four for business AG, www.4fb.de
*
* $Id: optimize_database.php,v 1.8 2006/04/28 09:20:55 timo.hummel Exp $
******************************************/

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

global $cfg;

if($_SERVER["PHP_SELF"] == "" || function_exists("runJob") || $area == "cronjobs") {
    $db = new DB_Contenido;   
    
    foreach ($cfg["tab"] as $key => $value)
    {
    	$sql = "OPTIMIZE TABLE ".$value;
    	$db->query($sql);
		
    }
	
	if ($cfg["statistics_heap_table"]) {
		$sHeapTable = $cfg['tab']['stat_heap_table'];

		buildHeapTables ($sHeapTable, $db);	}
	}
}
?>
