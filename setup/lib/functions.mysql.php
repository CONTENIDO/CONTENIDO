<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    Contenido Backend <Area>
 * @version    0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 *
 * {@internal
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *
 *   $Id: functions.mysql.php 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 *
 */
 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}


function hasMySQLExtension()
{
    return (isPHPExtensionLoaded("mysql") == E_EXTENSION_AVAILABLE) ? true : false;
}

function hasMySQLiExtension()
{
    return (isPHPExtensionLoaded("mysqli") == E_EXTENSION_AVAILABLE) ? true : false;
}

function doMySQLConnect ($host, $username, $password)
{
	$db = new DB_Contenido($host, "", $username, $password);

	if ($db->connect() == 0) {
		return array($db, false);
	} else {
		return array($db, true);
	}
}

/**
 * Selects a desired database by the link identifier and database name
 * @param resource $linkid  MySQLi/MySQL link identifier
 * @param string $database
 * @return boolean
 */
function doMySQLSelectDB($linkid, $database)
{
    $extension = getMySQLDatabaseExtension();

    if (CON_SETUP_MYSQLI === $extension) {
        return (@mysqli_select_db($linkid, $database));
    } elseif (CON_SETUP_MYSQL === $extension) {
        return (@mysql_select_db($database, $linkid));
    } else {
        return false;
    }
}

/**
 * Checks existing MySQL extensions and returns 'mysqli' as default, 'mysql' or null.
 * @return string|null
 */
function getMySQLDatabaseExtension()
{
    if (hasMySQLiExtension()) {
        return CON_SETUP_MYSQLI;
    } elseif (hasMySQLExtension()) {
        return CON_SETUP_MYSQL;
    } else {
        return null;
    }
}

function fetchMySQLVersion($db)
{
	$db->query("SELECT VERSION()");

    return ($db->next_record()) ? $db->f(0) : false;
}

function fetchMySQLUser ($db)
{
	$db->query("SELECT USER()");

    return ($db->next_record()) ? $db->f(0) : false;
}

function checkMySQLDatabaseCreation($db, $database)
{
	if (checkMySQLDatabaseExists($db,  $database)) {
		return true;
	} else {
		$db->query("CREATE DATABASE `$database`");
		return ($db->Errno == 0) ? true : false;
	}
}

function checkMySQLDatabaseExists($db, $database)
{
	$db->connect();

    if (doMySQLSelectDB($db->Link_ID, $database)) {
        return true;
    } else {
        $test = $db->query(sprintf("SHOW DATABASES LIKE '%s'", $database));
        return ($db->next_record()) ? true : false;
    }
}

function checkMySQLDatabaseUse($db, $database)
{
	$db->connect();
    return doMySQLSelectDB($db->Link_ID, $database);
}

function checkMySQLTableCreation($db, $database, $table)
{
	if (checkMySQLDatabaseUse($db, $database) == false) {
		return false;
	}

	$db->query("CREATE TABLE `$table` (test INT(1) NOT NULL) ENGINE = MYISAM;");

    return ($db->Errno == 0) ? true : false;
}

function checkMySQLLockTable($db, $database, $table)
{
	if (checkMySQLDatabaseUse($db, $database) == false) {
		return false;
	}

	$db->query("LOCK TABLES `$table` WRITE");

    return ($db->Errno == 0) ? true : false;
}

function checkMySQLUnlockTables($db, $database)
{
	if (checkMySQLDatabaseUse($db, $database) == false) {
		return false;
	}

	$db->query("UNLOCK TABLES");

    return ($db->Errno == 0) ? true : false;
}

function checkMySQLDropTable($db, $database, $table)
{
	if (checkMySQLDatabaseUse($db, $database) == false) {
		return false;
	}

	$db->query("DROP TABLE `$table`");

    return ($db->Errno == 0) ? true : false;
}

function checkMySQLDropDatabase($db, $database)
{
	$db->query("DROP DATABASE `$database`");

    return ($db->Errno == 0) ? true : false;
}

function fetchMySQLStorageEngines($db)
{
	$db->query("SHOW ENGINES");

	$engines = array();

	while ($db->next_record()) {
		$engines[] = $db->f(0);
	}

	return $engines;
}

/**
 * Returns all suppported character sets (field Charset) from the MySQL database.
 * @param DB_Contenido|null  $db
 * @return array
 */
function fetchMySQLCharsets ($db = null)
{
    if (!is_object($db)) {
        // No DB object, return static list
        return array(
            'big5', 'dec8', 'cp850', 'hp8', 'koi8r', 'latin1', 'latin2', 'swe7', 'ascii', 'ujis',
            'sjis', 'hebrew', 'tis620', 'euckr', 'koi8u', 'gb2312', 'greek', 'cp1250', 'gbk',
            'latin5', 'armscii8', 'utf8', 'ucs2', 'cp866', 'keybcs2', 'macce', 'macroman', 'cp852',
            'latin7', 'utf8mb4', 'cp1251', 'utf16', 'cp1256', 'cp1257', 'utf32', 'binary', 'geostd8',
            'cp932', 'eucjpms',
        );
    }

    $db->query('SHOW CHARACTER SET');

    $charsets = array();

    while ($db->next_record()) {
        $charsets[] = $db->f('Charset');
    }

    return $charsets;
}

?>