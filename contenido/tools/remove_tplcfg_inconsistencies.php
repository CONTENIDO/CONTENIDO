<?php

/* This script cleans up any inconsistencies which were created when syncing
   articles from one language to another in old versions. This script makes sure
   that a template configuration is unique for a single language */
   
include_once ('../includes/startup.php');

include_once ($cfg["path"]["contenido"] . $cfg["path"]["includes"] . 'cfg_sql.inc.php');
include_once ($cfg["path"]["contenido"] . $cfg["path"]["includes"] . 'cfg_language_de.inc.php');
include_once ($cfg["path"]["contenido"] . $cfg["path"]["includes"] . 'functions.general.php');
include_once ($cfg["path"]["contenido"] . $cfg["path"]["includes"] . 'functions.str.php');
include_once ($cfg["path"]["contenido"] . $cfg["path"]["includes"] . 'functions.con.php');
include_once ($cfg["path"]["contenido"] . $cfg["path"]["includes"] . 'functions.database.php');

set_time_limit(0);

$oDB = new DB_Contenido;
$oDB2 = new DB_Contenido;

$sql = "SELECT DISTINCT b.idartlang, b.idtplcfg FROM con_art_lang AS a, con_art_lang AS b WHERE b.idtplcfg = a.idtplcfg AND b.idtplcfg != 0 AND a.idtplcfg != 0 AND  a.idlang != b.idlang";
echo $sql;

$oDB->query($sql);

while ($oDB->next_record())
{
	$iInvalid_idtplcfg = $oDB->f("idtplcfg");

	$iValid_idtplcfg = tplcfgDuplicate($iInvalid_idtplcfg);
	
	$sql = "UPDATE con_art_lang SET idtplcfg='{$iValid_idtplcfg}' WHERE idartlang = '".$oDB->f("idartlang")."' AND idtplcfg='{$iInvalid_idtplcfg}'";
	$oDB2->query($sql);
	
}
?>