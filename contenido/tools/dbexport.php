<?php


/*****************************************
* File      :   dbexport.php
* Project   :   Contenido
* Descr     :   Contenido database exporter
*
* Authors   :   Timo A. Hummel
*
* Created   :   17.06.2003
* Modified  :   17.06.2003
*
* © four for business AG, www.4fb.de
******************************************/

/*****************************************
  WARNING - THIS FILE EXPORTS THE CURRENT
  DATABASE IN ORDER TO CREATE AN UPGRADE-
  ABLE CONTENIDO VERSION. THIS FILE IS
  ONLY THOUGHT TO BE USED BY THE CONTENIDO
  DEVELOPERS, NOT BY END-USERS.
  IT DOES -NOT- EXPORT YOUR ARTICLES!
******************************************/

die("Access denied");

define("DB_EXPORT_CONTENIDO", 1);
define("DB_EXPORT_ALLTABLES", 2);
define("DB_EXPORT_ALLEXCEPTCONTENIDO", 3);

/**
 * Mode description
 * ----------------
 * 
 * DB_EXPORT_CONTENIDO:
 * Exports all Contenido Tables defined in $cfg["tab"]. Note that this will
 * also export any defined plugins! This is the mode used for creating a new
 * upgrade.php file for creating a new contenido version.
 * 
 * DB_EXPORT_ALLTABLES:
 * Exports all tables found in the database. This is useful to update a remote
 * system.
 * 
 * DB_EXPORT_ALLEXCEPTCONTENIDO:
 * Exports all database tables except the contenido tables. This is useful to
 * update a remote site and all plugins, especially if you have a different
 * version of Contenido to develop plugins as you have on the live system.  
 */
$mode = DB_EXPORT_CONTENIDO;

$rawtext = true;

include_once ('../includes/startup.php');
cInclude("includes", "functions.database.php");
cInclude("classes", "class.csv.php");

# Create Contenido classes
$db = new DB_Contenido;

$client = 1;
$lang = 1;

class DB_Upgrade extends DB_Contenido
{
};

$dbexport = new DB_Contenido;

switch ($mode)
{
	case DB_EXPORT_CONTENIDO :
	
		$tArray = array();
		/* Export anything in $cfg["tab"] */
		foreach ($cfg["tab"] as $key => $value)
		{
			$tArray[$value] = dbDumpStructure($dbexport, $value, $rawtext);
		}
		
		$csv = new CSV;
		
		$row = 1;
		echo "<pre>";
		ksort($tArray);
		
		foreach ($tArray as $table)
		{
			foreach ($table as $field)
			{
				$row++;
				$cell = 1;
				foreach ($field as $entry)
				{
					$cell++;
					$csv->setCell($row, $cell, $entry);
				}
			}	
		}
		
		echo $csv->make();
		
		print_r($tArray);
		echo "</pre>";
		break;
		
	case DB_EXPORT_ALLTABLES :
	
		/* Export just plain everything in the database */
		$sql = "SHOW TABLES";
		$db = new DB_Upgrade;
		$db->query($sql);

		$tablenames = array ();

		while ($db->next_record())
		{
			$tablenames[] = $db->f(0);
		}

		foreach ($tablenames as $tablename)
		{
			dbDumpStructure($dbexport, $tablename, $rawtext);
		}
		break;
		
	case DB_EXPORT_ALLEXCEPTCONTENIDO :
	
		/* This one is a bit trickier. To find out any tables which don't
		 * belong to the base contenido installation, we reload the
		 * SQL configuration. Better don't put any custom stuff into
		 * cfg_sql.inc.php ;)
		 */
		$sqlprefix = $cfg['sql']['sqlprefix'];

		unset ($cfg["tab"]);
		$cfg['sql']['sqlprefix'] = $sqlprefix;

		include ($cfg["path"]["contenido"].$cfg["path"]["includes"].'cfg_sql.inc.php');

		$sql = "SHOW TABLES";
		$db = new DB_Upgrade;
		$db->query($sql);

		$tablenames = array ();

		while ($db->next_record())
		{
			$useTable = true;
			foreach ($cfg["tab"] as $key => $table)
			{
				if ($table == $db->f(0))
				{
					$useTable = false;
				}
			}

			if ($useTable == true)
			{
				$tablenames[] = $db->f(0);
			}
		}

		foreach ($tablenames as $tablename)
		{
			dbDumpStructure($dbexport, $tablename, $rawtext);
		}
		break;
}
?>