<?php

/**
 * This file contains various helper functions to read specific values needed for setup checks.
 *
 * @package    Setup
 * @subpackage Helper_MySQL
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die ('Illegal call: Missing framework initialization - request aborted.');

function hasMySQLExtension(): bool
{
    return isPHPExtensionLoaded('mysql') === CON_EXTENSION_AVAILABLE;
}

function hasMySQLiExtension(): bool
{
    return isPHPExtensionLoaded('mysqli') === CON_EXTENSION_AVAILABLE;
}

function doMySQLConnect($host, $username, $password): array
{
    $db = null;
    $aOptions = [
        'connection' => [
            'host' => $host,
            'user' => $username,
            'password' => $password
        ]
    ];

    try {
        $db = new cDb($aOptions);
    } catch (Exception $e) {
        return [$db, false];
    }

    if ($db->connect() == 0) {
        return [$db, false];
    } else {
        return [$db, true];
    }
}

/**
 * Selects a desired database by the link identifier and database name
 *
 * @param resource|mysqli $linkid
 *            MySQLi/MySQL link identifier
 * @param string $database
 * @return boolean
 */
function doMySQLSelectDB($linkid, string $database): bool
{
    $extension = getMySQLDatabaseExtension();

    if (CON_SETUP_MYSQLI === $extension) {
        return @mysqli_select_db($linkid, $database);
    } elseif (CON_SETUP_MYSQL === $extension) {
        return (bool) @mysql_select_db($database, $linkid);
    } else {
        return false;
    }
}

function getSetupMySQLDBConnection($full = true): cDb
{
    global $cfg;

    $cfgDb = $cfg['db'];

    if ($full === false) {
        // Connection parameter without database
        unset($cfgDb['connection']['database']);
    }

    return new cDb($cfgDb);
}

/**
 * Checks existing MySQL extensions and returns 'mysqli' as default, 'mysql' or null.
 *
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

function fetchMySQLVersion(cDb $db)
{
    $db->query("SELECT VERSION()");

    return $db->nextRecord() ? $db->f(0) : false;
}

function fetchMySQLUser(cDb $db)
{
    $db->query("SELECT USER()");

    return $db->nextRecord() ? $db->f(0) : false;
}

function checkMySQLDatabaseCreation(cDb $db, string $database, string $charset = '', string $collation = ''): bool
{
    if (checkMySQLDatabaseExists($db, $database)) {
        return true;
    } else if ($collation == '') {
        $db->query("CREATE DATABASE `%s`", $database);
        return $db->getErrorNumber() == 0;
    } else {
        $db->query("CREATE DATABASE `%s` CHARACTER SET %s COLLATE %s", $database, $charset, $collation);
        return $db->getErrorNumber() == 0;
    }
}

function checkMySQLDatabaseExists(cDb $db, string $database): bool
{
    $db->connect();

    if (doMySQLSelectDB($db->getLinkId(), $database)) {
        return true;
    } else {
        $db->query("SHOW DATABASES LIKE '%s'", $database);
        return $db->nextRecord();
    }
}

function checkMySQLDatabaseUse(cDb $db, string $database): bool
{
    $db->connect();
    return doMySQLSelectDB($db->getLinkId(), $database);
}

function checkMySQLTableCreation(cDb $db, string $database, string $table): bool
{
    if (!checkMySQLDatabaseUse($db, $database)) {
        return false;
    }

    $db->query("CREATE TABLE `%s` (test INT(1) NOT NULL) ENGINE = %s;", $table, CON_DB_ENGINE);

    return $db->getErrorNumber() == 0;
}

function checkMySQLLockTable(cDb $db, string $database, string $table): bool
{
    if (!checkMySQLDatabaseUse($db, $database)) {
        return false;
    }

    $db->query("LOCK TABLES `%s` WRITE", $table);

    return $db->getErrorNumber() == 0;
}

function checkMySQLUnlockTables(cDb $db, string $database): bool
{
    if (!checkMySQLDatabaseUse($db, $database)) {
        return false;
    }

    $db->query("UNLOCK TABLES");

    return $db->getErrorNumber() == 0;
}

function checkMySQLDropTable(cDb $db, string $database, string $table): bool
{
    if (!checkMySQLDatabaseUse($db, $database)) {
        return false;
    }

    $db->query("DROP TABLE `%s`", $table);

    return $db->getErrorNumber() == 0;
}

function checkMySQLDropDatabase(cDb $db, string $database): bool
{
    $db->query("DROP DATABASE `%s`", $database);

    return $db->getErrorNumber() == 0;
}

function fetchMySQLStorageEngines(cDb $db): array
{
    $db->query("SHOW ENGINES");

    $engines = [];

    while ($db->nextRecord()) {
        $engines[] = $db->f(0);
    }

    return $engines;
}

/**
 * Returns all supported character sets (field Charset) from the MySQL database.
 *
 * @param cDB|null $db
 * @return array
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function fetchMySQLCharsets(cDb $db = null): array
{
    if (!is_object($db)) {
        // No DB object, return static list
        return [
            'armscii8',
            'ascii',
            'big5',
            'binary',
            'cp1250',
            'cp1251',
            'cp1256',
            'cp1257',
            'cp850',
            'cp852',
            'cp866',
            'cp932',
            'dec8',
            'eucjpms',
            'euckr',
            'gb2312',
            'gbk',
            'geostd8',
            'greek',
            'hebrew',
            'hp8',
            'keybcs2',
            'koi8r',
            'koi8u',
            'latin1',
            'latin2',
            'latin5',
            'latin7',
            'macce',
            'macroman',
            'sjis',
            'swe7',
            'tis620',
            'ucs2',
            'ujis',
            'utf16',
            'utf32',
            'utf8',
            'utf8mb4',
        ];
    }

    $db->query('SHOW CHARACTER SET');

    $charsets = [];
    while ($db->nextRecord()) {
        $charsets[] = $db->f('Charset');
    }
    sort($charsets);

    return $charsets;
}

/**
 * Returns all supported collations for a specific charset
 *
 * @param cDB|null $db
 * @param string $charset The charset for the collation
 * @return array
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function fetchMySQLCollations(cDb $db = null, string $charset = ""): array
{
    if (!is_object($db)) {
        // No DB object, return static list
        return [
            'armscii8_general_ci',
            'ascii_general_ci',
            'big5_chinese_ci',
            'binary',
            'cp1250_general_ci',
            'cp1251_general_ci',
            'cp1256_general_ci',
            'cp1257_general_ci',
            'cp850_general_ci',
            'cp852_general_ci',
            'cp866_general_ci',
            'cp932_japanese_ci',
            'dec8_swedish_ci',
            'eucjpms_japanese_ci',
            'euckr_korean_ci',
            'gb2312_chinese_ci',
            'gbk_chinese_ci',
            'geostd8_general_ci',
            'greek_general_ci',
            'hebrew_general_ci',
            'hp8_english_ci',
            'keybcs2_general_ci',
            'koi8r_general_ci',
            'koi8u_general_ci',
            'latin1_swedish_ci',
            'latin2_general_ci',
            'latin5_turkish_ci',
            'latin7_general_ci',
            'macce_general_ci',
            'macroman_general_ci',
            'sjis_japanese_ci',
            'swe7_swedish_ci',
            'tis620_thai_ci',
            'ucs2_general_ci',
            'ujis_japanese_ci',
            'utf16_general_ci',
            'utf32_general_ci',
            'utf8_general_ci',
            'utf8_unicode_ci',
            'utf8mb4_general_ci',
        ];
    }

    $db->query('SHOW COLLATION');

    $collations = [];
    while ($db->nextRecord()) {
        $collations[] = $db->f('Collation');
    }
    sort($collations);

    return $collations;
}
