<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Database Functions
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.3.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2003-06-04
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *   modified 2008-07-11, Dominik Ziegler, removed deprecated functions
 *   modified 2011-05-17, Murat Purc, documented functions and some optimizations
 *   modified 2011-08-24, Dominik Ziegler, removed deprecated functions
 *
 *   $Id$:
 * }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Returns existing indexes of a specific table.
 * @param   DB_Contenido  $db
 * @param   string  $table
 * @return  array  Assoziative array where the key and the value is the index name
 */
function dbGetIndexes($db, $table)
{
    if (!is_object($db)) {
        return false;
    }

    $sql = "SHOW INDEX FROM ".Contenido_Security::escapeDB($table, $db);
    $db->query($sql);

    $indexes = array();

    while ($db->next_record()) {
        $indexes[$db->f("Key_name")] = $db->f("Key_name");
    }

    return ($indexes);
}


/**
 * Updates a specific table. Used e. g. by CONTENIDO setup to create or update
 * tables.
 * Function logic:
 * 1 .) Check, if the table exists
 * 2a.) If not, create it with the field specification, exit
 * 2b.) If the table exists, check, if the field exist
 * 3 .) If not, try to find the field using previous names (if specified in $field like "name1,name2")
 * 4a.) If the field hasn't been found, create the field as specified, exit
 * 4b.) If the field has been found using a previous name (if specified) rename the column to $field
 * 5 .) As the field has been found, check, if the field's type is matching
 * 5a.) If the type is matching, exit
 * 5b.) If the field's content type is not matching, try to convert first (e.g. string to int
 *      or int to string), then use the upgrade statement if applicable
 *
 * Note about the upgrade statement:
 *  - the code must be eval'able
 *  - the code needs to read $oldVal (old field value) and needs to set $newVal (value to which the field will be set)
 *  - $oldVal might be empty if the field didn't exist
 *  - $tableValues['fieldname'] contains the already existing values
 *
 * @param  DB_Contenido  $db  Database instance
 * @param  string  $table  Name of table to create/update
 * @param  string  $field  Name of field to create/update
 * @param  string  $type  Data type of field. Feasible values are all possible data types
 *                        e. g. int(10), varchar(32), datetime, varchar(255), text, tinyint(1)
 * @param  string  $null  Parameter to forbid null values, feasible values "", "NULL" or "YES"
 *                        where "NULL" or "YES" allows null values and "" doesn't
 * @param  string  $key   The field will be added as a primary key, if value is "PRI",
 *                        otherwhise the value should be empty ""
 * @param  string  $default  The default value for the field. Feasible is each possible
 *                           value depending on passed $type
 * @param  string  $extra  Additional info for the field, e. g. "auto_increment", if the
 *                         field should have the AUTO_INCREMENT attribute and empty otherwise.
 * @param  string  $upgradeStatement  NOT USED AT THE MOMENT
 * @param  bool  $bRemoveIndexes  Flag to remove all indexes
 * @return  bool
 */
function dbUpgradeTable($db, $table, $field, $type, $null, $key, $default, $extra, 
                        $upgradeStatement, $bRemoveIndexes = false)
{
    global $columnCache;
    global $tableCache;

    if (!is_object($db)) {
        return false;
    }

    $bDebug = false;
    if (($table == 'pica_alloc') &&  ($field == 'parentid')) {
        $bDebug = true;
    }

    $parameter = array();

    // Parameter checking for $null. If parameter is "" or "NULL" or "YES", we 
    // know that we want the colum to forbid null entries.
    if ($null == "NULL" || $null == "YES") {
        $parameter['NULL'] = "NULL";
        $null = "YES";
    } else {
        $parameter['NULL'] = "NOT NULL";
        $null = "";
    }

    // Parameter checking for $key. If parameter is "" or "NULL" or "YES", we 
    // know that we want the primary key.
    if ($key == "PRI") {
        $parameter['KEY'] = "PRIMARY KEY";
    } else {
        $parameter['KEY'] = "";
    }

    // Parameter check for $default. If set, create a default value
    if ($default != "") {
        if (((strpos($type, 'timestamp') !== FALSE) && ($default != '')) || ($default == 'NULL')) {
            $parameter['DEFAULT'] = "DEFAULT ".Contenido_Security::escapeDB($default, $db);
        } else {
            $parameter['DEFAULT'] = "DEFAULT '".Contenido_Security::escapeDB($default, $db)."'";
        }
    } else {
        $parameter['DEFAULT'] = '';
    }

    if (!dbTableExists($db, $table)) {
        $createTable = "  CREATE TABLE ".Contenido_Security::escapeDB($table, $db)." (".Contenido_Security::escapeDB($field, $db)." $type ".$parameter['NULL']." ".$parameter['DEFAULT']." ".$parameter['KEY'] .")";
        $db->query($createTable);
        $tableCache[] = $table;
        return true;
    }

    // Remove auto_increment
    $structure = dbGetColumns($db, $table);
    if (isset($structure[$field]) && $structure[$field]["Extra"] == "auto_increment") {
        if ($structure[$field]['NULL'] == "") {
            $structure[$field]['NULL'] = "NOT NULL";
        }
        $alterField = "ALTER TABLE ".Contenido_Security::escapeDB($table, $db)." CHANGE COLUMN ".Contenido_Security::escapeDB($field, $db)." ".Contenido_Security::escapeDB($field, $db)."
                       ".Contenido_Security::escapeDB($type, $db)." ".$structure[$field]['NULL']." ".$structure[$field]['DEFAULT']." ".$structure[$field]['KEY'];

        $db->query($alterField);
    }

    // Remove all keys, as they are being recreated during an upgrade
    if ($bRemoveIndexes == true) {
        $indexes = dbGetIndexes($db, $table);

        foreach ($indexes as $index) {
            if ($index == "PRIMARY") {
                if (isset($structure[$field]) && $structure[$field]['Key'] == "PRI") {
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

    // If $field contains "," previous names has been specified; separate from $field
    $sepPos = strpos($field, ",");
    if ($sepPos === false) {
        $previousName = "";
    } else {
        $previousName = substr($field, $sepPos + 1);
        $field = substr($field, 0, $sepPos);
    }

    if (!array_key_exists($field,$structure)) {
        // HerrB: Search field using $previousName
        $blnFound = false;
        if ($previousName != "") {
            $arrPreviousName = explode(",", $previousName);
            foreach ($arrPreviousName as $strPrevious) {
                // Maybe someone has used field1, field2, ..., trim spaces
                $strPrevious = trim($strPrevious);
                if (array_key_exists($strPrevious,$structure)) {
                    $blnFound = true;
                    break;
                }
            }
        }

        if ($blnFound) {
            // Rename column, update array, proceed
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
            // Add column as specified
            $createField = "  ALTER TABLE ".Contenido_Security::escapeDB($table, $db)." ADD COLUMN ".Contenido_Security::escapeDB($field, $db)." ".Contenido_Security::escapeDB($type, $db)."
            ".$parameter['NULL']." ".$parameter['DEFAULT']." ".$parameter['KEY'];
            $db->query($createField);
if ($bDebug) {echo 'createField:'.$createField.'<br />';}
            $columnCache[$table] = "";
            return true;
        }
    }

    $structure = dbGetColumns($db, $table);

    // Third check: Compare field properties
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


/**
 * Checks, if passed table exists in the database
 * @param   DB_Contenido  $db
 * @param   string  $table
 * @return  bool
 */
function dbTableExists($db, $table)
{
    global $tableCache;

    if (!is_object($db)) {
        return false;
    }

    if (!is_array($tableCache)) {
        $sql = "SHOW TABLES";
        $db->query($sql);

        $tableCache = array();

        while ($db->next_record()) {
            $tableCache[] = $db->f(0);
        }
    }

    if (in_array($table, $tableCache)) {
        return true;
    } else {
        return false;
    }
}


/**
 * Returns the column structure of a table
 * @param   DB_Contenido  $db
 * @param   string  $table
 * @return  array|bool  Either assoziative column array or false
 */
function dbGetColumns($db, $table)
{
    global $columnCache;

    if (!is_object($db)) {
        return false;
    }

    if (isset($columnCache[$table]) && is_array($columnCache[$table])) {
        return $columnCache[$table];
    }

    $sql = "SHOW COLUMNS FROM ".Contenido_Security::escapeDB($table, $db);
    $db->query($sql);

    $structure = array();

    while ($db->next_record()) {
        $structure[$db->f("Field")] = $db->toArray();
    }

    $columnCache[$table] = $structure;

    return $structure;
}


/**
 * Returns the primary key column of a table
 * @param   DB_Contenido  $db
 * @param   string  $table
 * @return  string
 */
function dbGetPrimaryKeyName($db, $table)
{
    $sReturn = "";
    $structure = dbGetColumns($db, $table);

    if (is_array($structure)) {
        foreach ($structure as $mykey => $value) {
            if ($value['Key'] == "PRI") {
                $sReturn = $mykey;
            }
        }
    }

    return $sReturn;
}


/**
 * Updates the sequence table, stores the highest primary key value of a table in it.
 * Retrieves the primary key field of the table, retrieves the highes value and
 * saves the value in the sequence table.
 *
 * @param   string  $sequencetable  Name of sequence table
 * @param   string  $table  Name of table
 * @param   DB_Contenido|bool  $db  Database instance or false
 */
function dbUpdateSequence($sequencetable, $table, $db = false)
{
    if ($db === false) {
        $bClose = true;
        $db = new DB_Upgrade;
    } else {
        $bClose = false;
    }

    $key = dbGetPrimaryKeyName($db, $table);

    if ($key != "" && $key != $sequencetable) {
        $sql = "SELECT ".Contenido_Security::escapeDB($key, $db)." FROM ". Contenido_Security::escapeDB($table, $db) ." ORDER BY " . Contenido_Security::escapeDB($key, $db) ." DESC";
        $db->query($sql);

        if ($db->next_record()) {
            $highestval = $db->f($key);
        } else {
            $highestval = 0;
        }

        $sql = "DELETE FROM " . Contenido_Security::escapeDB($sequencetable, $db) . " WHERE seq_name = '".Contenido_Security::escapeDB($table, $db)."'";
        $db->query($sql);

        $sql = "INSERT INTO " . Contenido_Security::escapeDB($sequencetable, $db) ." SET seq_name = '".Contenido_Security::escapeDB($table, $db)."', nextid = '".Contenido_Security::toInteger($highestval)."'";
        $db->query($sql);
    }

    if ($bClose == true) {
        $db->close();
    }
}
?>