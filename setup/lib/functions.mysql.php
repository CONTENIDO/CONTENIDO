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
    if (isPHPExtensionLoaded("mysql") == CON_EXTENSION_AVAILABLE) {
        return true;
    } else {
        return false;
    }
}

function hasMySQLiExtension() {
    if (isPHPExtensionLoaded("mysqli") == CON_EXTENSION_AVAILABLE) {
        return true;
    } else {
        return false;
    }
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
        return array(
            $db,
            false
        );
    }

    if ($db->connect() == 0) {
        return array(
            $db,
            false
        );
    } else {
        return array(
            $db,
            true
        );
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

function fetchMySQLVersion($db) {
    $db->query("SELECT VERSION()");

    if ($db->nextRecord()) {
        return $db->f(0);
    } else {
        return false;
    }
}

function fetchMySQLUser($db) {
    $db->query("SELECT USER()");

    if ($db->nextRecord()) {
        return ($db->f(0));
    } else {
        return false;
    }
}

function checkMySQLDatabaseCreation($db, $database) {
    if (checkMySQLDatabaseExists($db, $database)) {
        return true;
    } else {
        $db->query("CREATE DATABASE `$database`");
        if ($db->getErrorNumber() != 0) {
            return false;
        } else {
            return true;
        }
    }
}

function checkMySQLDatabaseExists($db, $database) {
    $db->connect();

    if (hasMySQLiExtension()) {
        if (@mysqli_select_db($db->getLinkId(), $database)) {
            return true;
        } else {
            $db->query("SHOW DATABASES LIKE '$database'");
            if ($db->nextRecord()) {
                return true;
            } else {
                return false;
            }
        }
    } else {
        if (@mysql_select_db($database, $db->getLinkId())) {
            return true;
        } else {
            $db->query("SHOW DATABASES LIKE '$database'");
            if ($db->nextRecord()) {
                return true;
            } else {
                return false;
            }
        }
    }
}

function checkMySQLDatabaseUse($db, $database) {
    $db->connect();

    if (hasMySQLiExtension() && !hasMySQLExtension()) {
        if (@mysqli_select_db($db->getLinkId(), $database)) {
            return true;
        } else {
            return false;
        }
    } else {
        if (@mysql_select_db($database, $db->getLinkId())) {
            return true;
        } else {
            return false;
        }
    }
}

function checkMySQLTableCreation($db, $database, $table) {
    if (checkMySQLDatabaseUse($db, $database) == false) {
        return false;
    }

    $db->query("CREATE TABLE `$table` (test INT( 1 ) NOT NULL) ENGINE = MYISAM ;");

    if ($db->getErrorNumber() == 0) {
        return true;
    } else {
        return false;
    }
}

function checkMySQLLockTable($db, $database, $table) {
    if (checkMySQLDatabaseUse($db, $database) == false) {
        return false;
    }

    $db->query("LOCK TABLES `$table` WRITE");

    if ($db->getErrorNumber() == 0) {
        return true;
    } else {
        return false;
    }
}

function checkMySQLUnlockTables($db, $database) {
    if (checkMySQLDatabaseUse($db, $database) == false) {
        return false;
    }

    $db->query("UNLOCK TABLES");

    if ($db->getErrorNumber() == 0) {
        return true;
    } else {
        return false;
    }
}

function checkMySQLDropTable($db, $database, $table) {
    if (checkMySQLDatabaseUse($db, $database) == false) {
        return false;
    }

    $db->query("DROP TABLE `$table`");

    if ($db->getErrorNumber() == 0) {
        return true;
    } else {
        return false;
    }
}

function checkMySQLDropDatabase($db, $database) {
    $db->query("DROP DATABASE `$database`");

    if ($db->getErrorNumber() == 0) {
        return true;
    } else {
        return false;
    }
}

function fetchMySQLStorageEngines($db) {
    $db->query("SHOW ENGINES");

    $engines = array();

    while ($db->nextRecord()) {
        $engines[] = $db->f(0);
    }

    return ($engines);
}

?>