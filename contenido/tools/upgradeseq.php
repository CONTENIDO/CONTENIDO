<?php

/*****************************************
* File      :   upgrade.php
* Project   :   Contenido
* Descr     :   Contenido upgrade script
*
* Authors   :   Timo A. Hummel
*
* Created   :   20.06.2003
* Modified  :   20.06.2003
*
* � four for business AG, www.4fb.de
******************************************/
if ( $_REQUEST['cfg'] ) { exit; }
$cfg["path"]["classes"] = getcwd() . "/classes/";
$cfg["path"]["includes"] = getcwd() . "/includes/";
$cfg["path"]["conlib"] = getcwd() . "/../conlib/";
include_once ($cfg["path"]["includes"] ."startup.php");
include_once ($cfg["path"]["conlib"] . 'prepend.php');
include_once ($cfg["path"]["includes"] . 'cfg_sql.inc.php');
include_once ($cfg["path"]["includes"] . 'functions.general.php');
include_once ($cfg["path"]["includes"] . 'functions.str.php');
include_once ($cfg["path"]["includes"] . 'functions.con.php');
include_once ($cfg["path"]["includes"] . 'functions.database.php');
include_once ($cfg["path"]["conlib"] . 'local.php');


$db = new DB_Upgrade;
$db2 = new DB_Contenido;

$sql = "SHOW TABLES";
$db->query($sql);
		
while ($db->next_record())
{
	dbUpdateSequence($prefix."_sequence", $db->f(0), $db2);	
}
