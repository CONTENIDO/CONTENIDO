<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Database Functions
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.3.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-06-04
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *   modified 2008-07-11, Dominik Ziegler, removed deprecated functions
 *
 *   $Id: functions.database.php 596 2008-07-11 11:06:27Z dominik.ziegler $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

function dbGetIndexes ($db, $table)
{
	if (!is_object($db))
	{
		return false;	
	}
	
	$sql = "SHOW INDEX FROM ".Contenido_Security::escapeDB($table, $db);
	$db->query($sql);
	
	$indexes = array();
	
	while ($db->next_record())
	{
		$indexes[$db->f("Key_name")] = $db->f("Key_name");		
	}
	
	return ($indexes);
}

function dbUpgradeTable ($db, $table, $field, $type, $null, $key, $default, $extra, $upgradeStatement, $bRemoveIndexes = false) {
	global $columnCache;
	global $tableCache;
   
	if (!is_object($db)) {
		return false;
	}

	$bDebug = false;
	if (($table == 'pica_alloc') &&  ($field == 'parentid')) {
		$bDebug = true;
	}

	/* Function logic:
	*  1 .) Check, if the table exists
	*  2a.) If not, create it with the field specification, exit
	*  2b.) If the table exists, check, if the field exist
	*  3 .) If not, try to find the field using previous names (if specified in $field like "name1,name2")
	*  4a.) If the field hasn't been found, create the field as specified, exit
	*  4b.) If the field has been found using a previous name (if specified) rename the column to $field
	*  5 .) As the field has been found, check, if the field's type is matching
	*  5a.) If the type is matching, exit
	*  5b.) If the field's content type is not matching, try to convert first (e.g. string to int
	*       or int to string), then use the upgrade statement if applicable
	*
	*  Note about the upgrade statement:
	*   - the code must be eval'able
	*   - the code needs to read $oldVal (old field value) and needs to set $newVal (value to which the field will be set)
	*   - $oldVal might be empty if the field didn't exist
	*   - $tableValues['fieldname'] contains the already existing values */

	/* Parameter checking for $null
	*  If parameter is "" or "NULL" or "YES", we know that we want the colum to forbid null entries. */
	if ($null == "NULL" || $null == "YES") {
		$parameter['NULL'] = "NULL";
		$null = "YES";
	} else {
		$parameter['NULL'] = "NOT NULL";
		$null = "";
	}

	/* Parameter checking for $key
	*  If parameter is "" or "NULL" or "YES", we know that
	*  we want the primary key. */   
	if ($key == "PRI") {
		$parameter['KEY'] = "PRIMARY KEY";
	} else {
		$parameter['KEY'] = "";
	}

	/* Parameter check for $default
	*  If set, create a default value */
	if ($default != "") {
		if (((strpos($type, 'timestamp') !== FALSE) && ($default != '')) || ($default == 'NULL')) {
			$parameter['DEFAULT'] = "DEFAULT ".Contenido_Security::escapeDB($default, $db);
		} else {
			$parameter['DEFAULT'] = "DEFAULT '".Contenido_Security::escapeDB($default, $db)."'";
		}
	}
   
	if (!dbTableExists($db, $table)) {
		$createTable = "  CREATE TABLE ".Contenido_Security::escapeDB($table, $db)." (".Contenido_Security::escapeDB($field, $db)." $type ".$parameter['NULL']." ".$parameter['DEFAULT']." ".$parameter['KEY'] .")";
		$db->query($createTable);
		$tableCache[] = $table;
		return true;
	}
   
   	/* Remove auto_increment */
   	$structure = dbGetColumns($db, $table);
   	
	if ($structure[$field]["Extra"] == "auto_increment")
	{
		if ($structure[$field]['NULL'] == "")
		{
			$structure[$field]['NULL'] = "NOT NULL";	
		}
		$alterField = "ALTER TABLE ".Contenido_Security::escapeDB($table, $db)." CHANGE COLUMN ".Contenido_Security::escapeDB($field, $db)." ".Contenido_Security::escapeDB($field, $db)."
                       ".Contenido_Security::escapeDB($type, $db)." ".$structure[$field]['NULL']." ".$structure[$field]['DEFAULT']." ".$structure[$field]['KEY'];

		$db->query($alterField);
	}		

   	/* Remove all keys, as they are being recreated during an upgrade */
   	if ($bRemoveIndexes == true)
   	{
   		$indexes = dbGetIndexes($db, $table);
   		
   		foreach ($indexes as $index)
   		{
   			if ($index == "PRIMARY")
   			{
   				if ($structure[$field]['Key'] == "PRI")
   				{
   					$sql = "   ALTER TABLE ".Contenido_Security::escapeDB($table, $db)." DROP PRIMARY KEY";
   				} else {
   					$sql = "";	
   				}	
   			} else {
	   			$sql = "   ALTER TABLE ".Contenido_Security::escapeDB($table, $db)."' DROP INDEX ".Contenido_Security::escapeDB($index, $db);
   			}
   			
   			$db->query($sql);
   			unset($columnCache[$table]);
   		}
   	}
   	
	$structure = dbGetColumns($db, $table);

	/* If $field contains "," previous names has been specified; separate from $field */
	$sepPos = strpos($field, ",");
	if ($sepPos === false) {
		$previousName = "";
	} else {
		$previousName = substr($field, $sepPos + 1);
		$field = substr($field, 0, $sepPos);
	}
      
	if (!array_key_exists($field,$structure)) {
		/* HerrB: Search field using $previousName */
		$blnFound = false;
		if ($previousName != "") {
			$arrPreviousName = explode(",", $previousName);
			foreach ($arrPreviousName as $strPrevious) {
				$strPrevious = trim($strPrevious); // Maybe someone has used field1, field2, ..., trim spaces
				if (array_key_exists($strPrevious,$structure)) {
					$blnFound = true;
					break;
				}
			}
		}

		if ($blnFound) {
			/* Rename column, update array, proceed */
			if ($structure[$strPrevious]['Null'] == 'YES') {
				$alterField = "  ALTER TABLE `".Contenido_Securiy::escapeDB($table, $db)."` CHANGE COLUMN `".Contenido_Security::escapeDB($strPrevious, $db)."` `".Contenido_Security::escapeDB($field, $db)."`
                ".$structure[$strPrevious]['Type']." DEFAULT '".$structure[$strPrevious]['Default']."'";
			} else {
				$alterField = "  ALTER TABLE `".Contenido_Security::escapeDB($table, $db)."` CHANGE COLUMN `".Contenido_Security::escapeDB($strPrevious, $db)."` `".Contenido_Security::escapeDB($field, $db)."`
                ".$structure[$strPrevious]['Type']." NOT NULL DEFAULT '".$structure[$strPrevious]['Default']."'";
			}

			$db->query($alterField);
			
			$columnCache[$table] = "";
			$structure = dbGetColumns($db, $table);
		} else {
			/* Add column as specified */
			$createField = "  ALTER TABLE ".Contenido_Security::escapeDB($table, $db)." ADD COLUMN ".Contenido_Security::escapeDB($field, $db)." ".Contenido_Security::escapeDB($type, $db)."
            ".$parameter['NULL']." ".$parameter['DEFAULT']." ".$parameter['KEY'];
			$db->query($createField);
if ($bDebug) {echo 'createField:'.$createField.'<br />';}			
			$columnCache[$table] = "";
			return true;
		}
	}

	$structure = dbGetColumns($db, $table);
	
	/* Third check: Compare field properties */
	if (($structure[$field]['Type'] != $type) ||
	   ($structure[$field]['Null'] != $null) ||
	   ($structure[$field]['Key'] != $key) ||
	   ($structure[$field]['Default'] != $default) ||
	   ($structure[$field]['Extra'] != $extra)) {
      
		if ($structure[$field]['Key'] == "PRI") {
			$alterField = "  ALTER TABLE ".Contenido_Security::escapeDB($table, $db)." ADD PRIMARY KEY ('".Contenido_Security::escapeDB($field, $db)."') ";
		} else {
			$alterField = "  ALTER TABLE ".Contenido_Security::escapeDB($table, $db)." CHANGE COLUMN $field $field $type ".$parameter['NULL']." ".$parameter['DEFAULT']." ".$parameter['KEY'];	
		}
      
		$db->query($alterField);
		
		$columnCache[$table] = "";
	}

	return true;
}

function dbTableExists ($db, $table)
{
	if (!is_object($db))
	{
		return false;
	}
	global $tableCache;
	
	if (!is_array($tableCache))
	{
    	$sql = "SHOW TABLES";
    	$db->query($sql);
    	
    	$tableCache = array();
    	
    	while ($db->next_record())
    	{
    		$tableCache[] = $db->f(0);
		}
	}
	
	if (in_array($table, $tableCache))
	{
		return true;
	} else {
		return false;
	}
}

function dbGetColumns ($db, $table)
{
	global $columnCache;
	
	if (!is_object($db))
	{
		return false;
	}
	
	if (is_array($columnCache[$table]))
	{
		return $columnCache[$table];
	}
	
	$sql = "SHOW COLUMNS FROM ".Contenido_Security::escapeDB($table, $db);
	
	$db->query($sql);
	
	$structure = array();
	
	while ($db->next_record())
	{
		$structure[$db->f("Field")] = $db->copyResultToArray();
	}
	
	$columnCache[$table] = $structure;
	
	return $structure;
}

function dbGetPrimaryKeyName ($db, $table)
{
    $sReturn = "";
	$structure = dbGetColumns($db, $table);
	
	if (is_array($structure))
	{
		foreach ($structure as $mykey => $value)
		{
			if ($value['Key'] == "PRI")
			{
				$sReturn = $mykey;
			}
		}
	}
	
	return $sReturn;
}

function dbUpdateSequence($sequencetable, $table, $db = false)
{

	if ($db === false)
	{
		$bClose = true;
		$db = new DB_Upgrade;	
	} else {
		$bClose = false;	
	}
	
	$key = dbGetPrimaryKeyName($db, $table);
	
	if ($key != "" && $key != $sequencetable)
	{
    	$sql = "SELECT ".Contenido_Security::escapeDB($key, $db)." FROM ". Contenido_Security::escapeDB($table, $db) ." ORDER BY " . Contenido_Security::escapeDB($key, $db) ." DESC";
    	$db->query($sql);
    	
    	if ($db->next_record())
    	{
    		$highestval = $db->f($key);
    	} else {
    		$highestval = 0;
    	}
    	
    	$sql = "DELETE FROM " . Contenido_Security::escapeDB($sequencetable, $db) . " WHERE seq_name = '".Contenido_Security::escapeDB($table, $db)."'";
    	$db->query($sql);
    	
    	$sql = "INSERT INTO " . Contenido_Security::escapeDB($sequencetable, $db) ." SET seq_name = '".Contenido_Security::escapeDB($table, $db)."', nextid = '".Contenido_Security::toInteger($highestval)."'";
    	$db->query($sql);
	}
	
	if ($bClose == true)
	{
		$db->close();	
	}
}

/**
 * @deprecated
 * @since 2008-07-11
 */
function dbDumpStructure ($db, $table, $return = false)
{
    /* this function is deprecated since Contenido 4.8.7 - 2008-07-11 */
    return;
}

/**
 * @deprecated
 * @since 2008-07-11
 */
function dbDumpArea ($db, $id)
{
    /* this function is deprecated since Contenido 4.8.7 - 2008-07-11 */
    return;
}

/**
 * @deprecated
 * @since 2008-07-11
 */
function dbDumpAreasAsArray ($arrayname, $db)
{
    /* this function is deprecated since Contenido 4.8.7 - 2008-07-11 */
    return;
}

/**
 * @deprecated
 * @since 2008-07-11
 */
function dbDumpNavSub ($arrayname, $db, $nextidarea)
{
    /* this function is deprecated since Contenido 4.8.7 - 2008-07-11 */
    return;
}

/**
 * @deprecated
 * @since 2008-07-11
 */
function dbInsertData ( $table, $data )
{
    /* this function is deprecated since Contenido 4.8.7 - 2008-07-11 */
    return;
}

/**
 * @deprecated
 * @since 2008-07-11
 */
function dbDumpData ($table)
{
    /* this function is deprecated since Contenido 4.8.7 - 2008-07-11 */
    return; 
}

/**
 * @deprecated
 * @since 2008-07-11
 */
function dbUpgradeData ($table, $valuesArray)
{
    /* this function is deprecated since Contenido 4.8.7 - 2008-07-11 */
    return; 
}
?>