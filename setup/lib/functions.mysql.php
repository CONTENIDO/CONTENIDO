<?php
/**
 * This file contains various helper functions to read specific values needed for setup checks.
 *
 * @package    Setup
 * @subpackage Helper_MySQL
 * @version    SVN Revision $Rev:$
 *
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

function hasMySQLExtension() {
    return (isPHPExtensionLoaded("mysql") == CON_EXTENSION_AVAILABLE) ? true : false;
}

function hasMySQLiExtension() {
    return (isPHPExtensionLoaded("mysqli") == CON_EXTENSION_AVAILABLE) ? true : false;
}

function doMySQLConnect($host, $username, $password) {
    $aOptions = array(
        'connection' => array(
            'host' => $host,
            'user' => $username,
            'password' => $password
        )
    );
    try {
        $db = new cDb($aOptions);
    } catch (Exception $e) {
        return array($db, false);
    }

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
function doMySQLSelectDB($linkid, $database) {
    $extension = getMySQLDatabaseExtension();

    if (CON_SETUP_MYSQLI === $extension) {
        return (@mysqli_select_db($linkid, $database)) ? true : false;
    } elseif (CON_SETUP_MYSQL === $extension) {
        return (@mysql_select_db($database, $linkid)) ? true : false;
    } else {
        return false;
    }
}

function getSetupMySQLDBConnection($full = true) {
    global $cfg;

    $cfgDb = $cfg['db'];

    if ($full === false) {
        // Connection parameter without database
        unset($cfgDb['connection']['database']);
    }

    $db = new cDb($cfgDb);
    return $db;
}

/**
 * Checks existing MySQL extensions and returns 'mysqli' as default, 'mysql' or null.
 * @return string|null
 */
function getMySQLDatabaseExtension() {
    if (hasMySQLiExtension()) {
        return CON_SETUP_MYSQLI;
    } elseif (hasMySQLExtension()) {
        return CON_SETUP_MYSQL;
    } else {
        return null;
    }
}

function fetchMySQLVersion($db) {
    $db->query("SELECT VERSION()");

    return ($db->nextRecord()) ? $db->f(0) : false;
}

function fetchMySQLUser($db) {
    $db->query("SELECT USER()");

    return ($db->nextRecord()) ? $db->f(0) : false;
}

function checkMySQLDatabaseCreation($db, $database) {
    if (checkMySQLDatabaseExists($db, $database)) {
        return true;
    } else {
        $db->query("CREATE DATABASE `%s`", $database);
        return ($db->getErrorNumber() == 0) ? true : false;
    }
}

function checkMySQLDatabaseExists($db, $database) {
    $db->connect();

    if (doMySQLSelectDB($db->getLinkId(), $database)) {
        return true;
    } else {
        $db->query("SHOW DATABASES LIKE '%s'", $database);
        return ($db->nextRecord()) ? true : false;
    }
}

function checkMySQLDatabaseUse($db, $database) {
    $db->connect();
    return doMySQLSelectDB($db->getLinkId(), $database);
}

function checkMySQLTableCreation($db, $database, $table) {
    if (checkMySQLDatabaseUse($db, $database) == false) {
        return false;
    }

    $db->query("CREATE TABLE `%s` (test INT(1) NOT NULL) ENGINE = MYISAM;", $table);

    return ($db->getErrorNumber() == 0) ? true : false;
}

function checkMySQLLockTable($db, $database, $table) {
    if (checkMySQLDatabaseUse($db, $database) == false) {
        return false;
    }

    $db->query("LOCK TABLES `%s` WRITE", $table);

    return ($db->getErrorNumber() == 0) ? true : false;
}

function checkMySQLUnlockTables($db, $database) {
    if (checkMySQLDatabaseUse($db, $database) == false) {
        return false;
    }

    $db->query("UNLOCK TABLES");

    return ($db->getErrorNumber() == 0) ? true : false;
}

function checkMySQLDropTable($db, $database, $table) {
    if (checkMySQLDatabaseUse($db, $database) == false) {
        return false;
    }

    $db->query("DROP TABLE `%s`", $table);

    return ($db->getErrorNumber() == 0) ? true : false;
}

function checkMySQLDropDatabase($db, $database) {
    $db->query("DROP DATABASE `%s`", $database);

    return ($db->getErrorNumber() == 0) ? true : false;
}

function fetchMySQLStorageEngines($db) {
    $db->query("SHOW ENGINES");

    $engines = array();

    while ($db->nextRecord()) {
        $engines[] = $db->f(0);
    }

    return $engines;
}

?>