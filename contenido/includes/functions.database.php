<?php

/**
 * This file contains CONTENIDO database functions.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Returns existing indexes of a specific table.
 *
 * @param cDb $db
 * @param string $table
 * @return array
 *         Assoziative array where the key and the value is the index name
 */
function dbGetIndexes($db, $table) {
    if (!is_object($db)) {
        return false;
    }

    $sql = 'SHOW INDEX FROM ' . $db->escape($table);
    $db->query($sql);

    $indexes = array();

    while ($db->nextRecord()) {
        $indexes[$db->f('Key_name')] = $db->f('Key_name');
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
 * 3 .) If not, try to find the field using previous names (if specified in $field like 'name1,name2')
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
 * @param cDb $db
 *         Database instance
 * @param string $table
 *         Name of table to create/update
 * @param string $field
 *         Name of field to create/update
 * @param string $type
 *         Data type of field. Feasible values are all possible data types
 *         e. g. int(10), varchar(32), datetime, varchar(255), text, tinyint(1)
 * @param string $null
 *         Parameter to forbid NULL values, feasible values '', 'NULL' or 'YES'
 *         where 'NULL' or 'YES' allows NULL values and '' doesn't
 * @param string $key
 *         The field will be added as a primary key, if value is 'PRI',
 *         otherwise the value should be empty ''
 * @param string $default
 *         The default value for the field. Feasible is each possible
 *         value depending on passed $type
 * @param string $extra
 *         Additional info for the field, e. g. 'auto_increment', if the
 *         field should have the AUTO_INCREMENT attribute and empty otherwise.
 * @param string $upgradeStatement
 *         NOT USED AT THE MOMENT
 * @param bool $bRemoveIndexes
 *         Flag to remove all indexes
 * @return bool
 */
function dbUpgradeTable($db, $table, $field, $type, $null, $key, $default, $extra, $upgradeStatement, $bRemoveIndexes = false) {
    global $columnCache;
    global $tableCache;

    if (!is_object($db)) {
        return false;
    }

    $parameter = array();

    // Parameter checking for $null. If parameter is 'NULL' or 'YES', we
    // know that we want the colum to allow null entries, otherwise forbid null entries.
    if ($null == 'NULL' || $null == 'YES') {
        $parameter['NULL'] = 'NULL';
        $null = 'YES';
    } else {
        $parameter['NULL'] = 'NOT NULL';
        $null = '';
    }

    // Parameter checking for $key. If parameter is '' or 'NULL' or 'YES', we
    // know that we want the primary key.
    if ($key == 'PRI') {
        $parameter['KEY'] = 'PRIMARY KEY';
    } else {
        $parameter['KEY'] = '';
    }

    // Parameter check for $default. If set, create a default value
    if ($default != '') {
        if (((strpos($type, 'timestamp') !== FALSE) && ($default != '')) || ($default == 'NULL')) {
            $parameter['DEFAULT'] = "DEFAULT " . $db->escape($default);
        } else {
            $parameter['DEFAULT'] = "DEFAULT '" . $db->escape($default) . "'";
        }
    } else {
        $parameter['DEFAULT'] = '';
    }

    if (!dbTableExists($db, $table)) {
        $sql = "CREATE TABLE `" . $db->escape($table) . "` (`" . $db->escape($field) . "` $type " . $parameter['NULL'] . " " . $parameter['DEFAULT'] . " " . $parameter['KEY'] . ")";
        $db->query($sql);
        $tableCache[] = $table;
        return true;
    }

    // Remove auto_increment
    $structure = dbGetColumns($db, $table);
    if (isset($structure[$field]) && $structure[$field]['Extra'] == 'auto_increment') {
        if ($structure[$field]['NULL'] == '') {
            $structure[$field]['NULL'] = 'NOT NULL';
        }
        $sql = "ALTER TABLE `" . $db->escape($table) . "` CHANGE COLUMN `" . $db->escape($field) . "` `" . $db->escape($field) . "` " . $db->escape($type) . " " . $structure[$field]['NULL'] . " " . $structure[$field]['DEFAULT'] . " " . $structure[$field]['KEY'];
        $db->query($sql);
    }

    // Remove all keys, as they are being recreated during an upgrade
    if ($bRemoveIndexes == true) {
        $indexes = dbGetIndexes($db, $table);
        foreach ($indexes as $index) {
            $sql = '';
            if ($index == 'PRIMARY') {
                if (isset($structure[$field]) && $structure[$field]['Key'] == 'PRI') {
                    $sql = 'ALTER TABLE `' . $db->escape($table) . '` DROP PRIMARY KEY';
                }
            } else {
                $sql = 'ALTER TABLE `' . $db->escape($table) . '` DROP INDEX ' . $db->escape($index);
            }
            if (!empty($sql)) {
                $db->query($sql);
            }
        }
        unset($columnCache[$table]);
    }

    $structure = dbGetColumns($db, $table);

    // If $field contains ',' previous names has been specified; separate from $field
    $sepPos = strpos($field, ',');
    if ($sepPos === false) {
        $previousName = '';
    } else {
        $previousName = substr($field, $sepPos + 1);
        $field = substr($field, 0, $sepPos);
    }

    if (!array_key_exists($field, $structure)) {
        // HerrB: Search field using $previousName
        $blnFound = false;
        if ($previousName != '') {
            $arrPreviousName = explode(',', $previousName);
            foreach ($arrPreviousName as $strPrevious) {
                // Maybe someone has used field1, field2, ..., trim spaces
                $strPrevious = trim($strPrevious);
                if (array_key_exists($strPrevious, $structure)) {
                    $blnFound = true;
                    break;
                }
            }
        }

        if ($blnFound) {
            // Rename column, update array, proceed
            if ($structure[$strPrevious]['Null'] == 'YES') {
                $sql = "ALTER TABLE `" . $db->escape($table) . "` CHANGE COLUMN `" . $db->escape($strPrevious) . "` `" . $db->escape($field) . "` " . $structure[$strPrevious]['Type'] . " DEFAULT '" . $structure[$strPrevious]['Default'] . "'";
            } else {
                $sql = "ALTER TABLE `" . $db->escape($table) . "` CHANGE COLUMN `" . $db->escape($strPrevious) . "` `" . $db->escape($field) . "` " . $structure[$strPrevious]['Type'] . " NOT NULL DEFAULT '" . $structure[$strPrevious]['Default'] . "'";
            }
            $db->query($sql);

            $columnCache[$table] = '';
            $structure = dbGetColumns($db, $table);
        } else {
            // Add column as specified
            $sql = "ALTER TABLE `" . $db->escape($table) . "` ADD COLUMN `" . $db->escape($field) . "` " . $db->escape($type) . " " . $parameter['NULL'] . " " . $parameter['DEFAULT'] . " " . $parameter['KEY'];
            $db->query($sql);

            $columnCache[$table] = '';
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

        if ($structure[$field]['Key'] == 'PRI') {
            $sql = "ALTER TABLE `" . $db->escape($table) . "` ADD PRIMARY KEY (" . $db->escape($field) . ") ";
        } else {
            $sql = "ALTER TABLE `" . $db->escape($table) . "` CHANGE COLUMN `" . $db->escape($field) . "` `" . $db->escape($field) . "` " . $db->escape($type) . " " . $parameter['NULL'] . " " . $parameter['DEFAULT'] . " " . $parameter['KEY'];
        }
        $db->query($sql);

        $columnCache[$table] = '';
    }

    return true;
}

/**
 * Checks, if passed table exists in the database
 *
 * @param cDb $db
 * @param string $table
 * @return bool
 */
function dbTableExists($db, $table) {
    global $tableCache;

    if (!is_object($db)) {
        return false;
    }

    if (!is_array($tableCache)) {
        $tableCache = array();
        $sql = 'SHOW TABLES';
        $db->query($sql);
        while ($db->nextRecord()) {
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
 *
 * @param cDb $db
 * @param string $table
 * @return array|bool
 *         Either assoziative column array or false
 */
function dbGetColumns($db, $table) {
    global $columnCache;

    if (!is_object($db)) {
        return false;
    }

    if (isset($columnCache[$table]) && is_array($columnCache[$table])) {
        return $columnCache[$table];
    }

    $structure = array();

    $sql = 'SHOW COLUMNS FROM ' . $db->escape($table);
    $db->query($sql);
    while ($db->nextRecord()) {
        $structure[$db->f('Field')] = $db->toArray();
    }

    $columnCache[$table] = $structure;

    return $structure;
}

/**
 * Returns the primary key column of a table
 *
 * @deprecated [2015-05-21]
 *         This method is no longer supported (no replacement)
 * @param cDb $db
 * @param string $table
 * @return string
 */
function dbGetPrimaryKeyName($db, $table) {
    cDeprecated('This method is deprecated and is not needed any longer');

    $sReturn = '';
    $structure = dbGetColumns($db, $table);

    if (is_array($structure)) {
        foreach ($structure as $mykey => $value) {
            if ($value['Key'] == 'PRI') {
                $sReturn = $mykey;
            }
        }
    }

    return $sReturn;
}
