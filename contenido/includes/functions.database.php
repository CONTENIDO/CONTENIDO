<?php

/*****************************************
* File      :   $RCSfile: functions.database.php,v $
* Project   :   Contenido
* Descr     :   Contenido Database Functions
*
* Author    :   Timo A. Hummel
*               
* Created   :   04.06.2003
* Modified  :   $Date: 2006/04/28 09:20:54 $
*
* © four for business AG, www.4fb.de
*
* $Id: functions.database.php,v 1.31 2006/04/28 09:20:54 timo.hummel Exp $
******************************************/


function dbGetIndexes ($db, $table)
{
	if (!is_object($db))
	{
		return false;	
	}
	
	$sql = "SHOW INDEX FROM $table";
	$db->query($sql);
	
	$indexes = array();
	
	while ($db->next_record())
	{
		$indexes[$db->f("Key_name")] = $db->f("Key_name");		
	}
	
	return ($indexes);
}

function dbDumpArea ($db, $id)
{
	if (!is_object($db))
	{
		return false;	
	}
	
	$sql = 'SELECT name FROM con_area WHERE idarea = $id';
	$db->query($sql);
	if (!$db->next_record()) { return false; }
	$name = $db->f("name");
	
	// First step: Dump
	$sql = "SELECT * FROM con_area WHERE idarea = $id OR parent_id = '$name'";
	$db->query($sql);
	
	//if (!$db->next_record()) { return; }
	
	//echo '$area['.$id.'] = array (';
	
	dbDumpAreasAsArray('$area',$db);
	
	return true;
	 
}

//function dbDump

function dbDumpAreasAsArray ($arrayname, $db)
{
  		$values = array();
  		
  		$metadata = $db->metadata();
		
		if (!is_array($metadata))
		{
			return false;
		}
		
		echo '$startidarea = $db->nextid( $cfg["tab"]["area"] );'."\n\n";
		
		$nextid = 0;
		while ($db->next_record())
		{
			$nextid += 1;
    		foreach ($metadata as $entry)
    		{
    			
    			$key = $entry['name'];
    			$value = $db->f($entry['name']);
    			
    			if ($key == "idarea")
    			{
    				$value = '$startidarea+'.$nextid;
    			}
    			echo $arrayname.'[$startidarea+'.$nextid."]"."['"
    			     .$key.
                     "'] = '".
                     $value."';\n";
                     
                $sql = 'SELECT * FROM '.$cfg["tab"]["nav_sub"].' WHERE idarea = '.$db->f("idarea");
                $db2 = new DB_Upgrade;
                $db2->query($sql);
                dbDumpNavSub('$navsub', $db2, $nextid);
    		}
    		echo 'dbInsertData( $cfg["tab"]["area"], '.$arrayname.'[$startidarea+'.$nextid."]);\n";
    		echo "\n";
		}		
}

function dbDumpNavSub ($arrayname, $db, $nextidarea)
{
	$values = array();
  		
  		$metadata = $db->metadata();
		
		if (!is_array($metadata))
		{
			return false;
		}
		
		echo ' $startidnavs = $db->nextid( $cfg["tab"]["nav_sub"] );'."\n\n";
		
		$nextid = 0;
		while ($db->next_record())
		{
			$nextid += 1;
    		foreach ($metadata as $entry)
    		{
    			
    			$key = $entry['name'];
    			$value = $db->f($entry['name']);
    			
    			if ($key == "idarea")
    			{
    				$value = '$startidarea+'.$nextidarea;
    			}
    			echo " ". $arrayname.'[$startidnavs+'.$nextid."]"."['"
    			     .$key.
                     "'] = '".
                     $value."';\n";
                     
                $sql = 'SELECT * FROM '.$cfg["tab"]["nav_sub"].' WHERE idarea = '.$db->f("idarea");
                $db2 = new DB_Upgrade;
                //dbDumpNavSub('$navsub', $db2);
    		}
    		echo 'dbInsertData( $cfg["tab"]["area"], '.$arrayname.'[$startidarea+'.$nextid."]);\n";
    		echo "\n";
		}		
	
	
}
 
	
function dbInsertData ( $table, $data )
{
	$db = new DB_Upgrade;
	
	$sql = "INSERT INTO $table SET ";
	
	foreach ($data as $key => $value)
	{
		$sql .= $key . ", '".$value."' ";
	}


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
			$parameter['DEFAULT'] = "DEFAULT $default";
		} else {
			$parameter['DEFAULT'] = "DEFAULT '$default'";
		}
	}
   
	if (!dbTableExists($db, $table)) {
		$createTable = "  CREATE TABLE $table ($field $type ".$parameter['NULL']." ".$parameter['DEFAULT']." ".$parameter['KEY'] .")";
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
		$alterField = "  ALTER TABLE $table CHANGE COLUMN $field $field $type ".$structure[$field]['NULL']." ".$structure[$field]['DEFAULT']." ".$structure[$field]['KEY'];

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
   					$sql = "ALTER TABLE $table DROP PRIMARY KEY";
   				} else {
   					$sql = "";	
   				}	
   			} else {
	   			$sql = "ALTER TABLE $table DROP INDEX $index";
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
				$alterField = "  ALTER TABLE `$table` CHANGE COLUMN `$strPrevious` `$field` ".$structure[$strPrevious]['Type']." DEFAULT '".$structure[$strPrevious]['Default']."'";
			} else {
				$alterField = "  ALTER TABLE `$table` CHANGE COLUMN `$strPrevious` `$field` ".$structure[$strPrevious]['Type']." NOT NULL DEFAULT '".$structure[$strPrevious]['Default']."'";
			}

			$db->query($alterField);
			
			$columnCache[$table] = "";
			$structure = dbGetColumns($db, $table);
		} else {
			/* Add column as specified */
			$createField = "  ALTER TABLE $table ADD COLUMN $field $type ".$parameter['NULL']." ".$parameter['DEFAULT']." ".$parameter['KEY'];
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
			$alterField = "  ALTER TABLE $table ADD PRIMARY KEY ('$field') ";
		} else {
			$alterField = "  ALTER TABLE $table CHANGE COLUMN $field $field $type ".$parameter['NULL']." ".$parameter['DEFAULT']." ".$parameter['KEY'];	
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
	
	$sql = "SHOW COLUMNS FROM $table";
	
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
	$structure = dbGetColumns($db, $table);
	
	if (is_array($structure))
	{
		foreach ($structure as $mykey => $value)
		{
			if ($value['Key'] == "PRI")
			{
				return ($mykey);
			}
		}
	}

}

function dbDumpStructure ($db, $table, $return = false)
{
	global $cfg;

	$prefix = $cfg['sql']['sqlprefix'];
	
	if ($return === false)
	{
		echo "<pre>";
	}
	$structure = dbGetColumns($db, $table);
	
	$returnArray = array();
	
	foreach ($structure as $key => $value)
	{
		if (substr($table, 0, strlen($prefix)+1) == $prefix."_")
		{
    		$tab = str_replace("con_","",$table);

			if ($value['Key'] == "PRI")
			{
				if ($return == false)
				{
		    		echo "dbUpgradeTable(\$prefix.\"_$tab\", '$key', '"
		    		      .addslashes($value['Type']).
		                  "', '"
		                  .$value['Null'].
		                  "', '"
		                  .$value['Key'].
		                  "', '"
		                  .$value['Default'].
		                  "', '"
		                  .$value['Extra'].
		                  "','', true);";
				} else {
					 $returnArray[] = array($tab, $key, $value['Type'], $value['Null'], $value['Key'], $value['Default'], $value['Extra'], true);
				}
			} else {
				if ($return == false)
				{
		    		echo "dbUpgradeTable(\$prefix.\"_$tab\", '$key', '"
		    		      .addslashes($value['Type']).
		                  "', '"
		                  .$value['Null'].
		                  "', '"
		                  .$value['Key'].
		                  "', '"
		                  .$value['Default'].
		                  "', '"
		                  .$value['Extra'].
		                  "','');";
				} else {
					$returnArray[] = array($tab, $key, $value['Type'], $value['Null'], $value['Key'], $value['Default'], $value['Extra'], false);
				}				
			}

			if ($return != true)
			{			
	    		echo "\n";
			}
	    	
		} else {
			$tab = $cfg["tab"][$table];
			
			if ($return == false)
			{
	    		echo "dbUpgradeTable(\"$table\", '$key', '"
	    		      .addslashes($value['Type']).
	                  "', '"
	                  .$value['Null'].
	                  "', '"
	                  .$value['Key'].
	                  "', '"
	                  .$value['Default'].
	                  "', '"
	                  .$value['Extra'].
	                  "','');";
	    		echo "\n";
			} else {
				$returnArray[] = array($tab, $key, $value['Type'], $value['Null'], $value['Key'], $value['Default'], $value['Extra'], false);
			}
		}
	}
	
	if ($return == false)
	{
		echo "</pre>";
	} else {
		return $returnArray;
	}
}

function dbDumpData ($table)
{
	global $cfg;
	$db = new DB_Upgrade;
	
	echo "<pre>";
	$structure = dbGetColumns($cfg["tab"][$table]);
	
	$sql = "SELECT * FROM " . $cfg["tab"][$table];
	//echo $sql;
	
	echo '$db = new DB_Upgrade; $db->query("DELETE FROM ".$cfg["tab"]["'.$table.'"]);'."\n";
	$db->query($sql);
	
	while ($db->next_record())
	{
		$count++;
		
		echo '$'.$table.$count.' = array(';

		foreach ($structure as $key => $value)
		{
			$entry[$key] = "'$key' => '".addslashes($db->f($key))."'";
			
		}
		
		
		echo implode(', ',$entry);
		echo ');'."\n";
		echo $targetLink ."\n";
		echo "dbUpgradeData('$table', \$".$table.$count.");";
		echo "\n\n";	
	}
}

function dbUpgradeData ($table, $valuesArray)
{
	global $cfg;
	$db = new DB_Upgrade;
	
	$sql = "INSERT INTO ".$cfg["tab"][$table]." SET ";
	foreach ($valuesArray as $key => $value)
		{
			$addValues[] = "$key = '$value'";
		}
		
		$param = implode(', ', $addValues);
		
		$sql .= $param;
	
		//echo $sql;	
		$db->query($sql);
	
}

function dbUpdateSequence($sequencetable, $table, $db = false)
{
	global $cfg;
	
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
    	$sql = "SELECT ".$key." FROM ". $table ." ORDER BY " . $key ." DESC";
    	$db->query($sql);
    	
    	if ($db->next_record())
    	{
    		$highestval = $db->f($key);
    	} else {
    		$highestval = 0;
    	}
    	
    	$sql = "DELETE FROM " . $sequencetable . " WHERE seq_name = '".$table."'";
    	$db->query($sql);
    	
    	$sql = "INSERT INTO " . $sequencetable ." SET seq_name = '".$table."', nextid = '".($highestval)."'";
    	$db->query($sql);
	}
	
	if ($bClose == true)
	{
		$db->close();	
	}
}


?>
