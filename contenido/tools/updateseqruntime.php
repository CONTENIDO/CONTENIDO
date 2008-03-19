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
include_once ('../includes/startup.php');
cInclude("includes",  'cfg_sql.inc.php');
cInclude("includes",  'functions.general.php');
cInclude("includes",  'functions.str.php');
cInclude("includes",  'functions.con.php');
cInclude("includes",  'functions.database.php');
cInclude("conlib",  'prepend.php');

cInclude("conlib",  'local.php');

class DB_Upgrade extends DB_Contenido {
}

$db = new DB_Contenido;
$db2 = new DB_Contenido;

$sql = "SHOW TABLES";
$db->query($sql);


		
while ($db->next_record())
{
	dbUpdateSequence($cfg['sql']['sqlprefix']."_sequence", $db->f(0), $db2);	
}
