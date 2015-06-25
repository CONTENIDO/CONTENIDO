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
defined ( 'CON_FRAMEWORK' ) || die ( 'Illegal call: Missing framework initialization - request aborted.' );
function hasMySQLExtension() {
	return (isPHPExtensionLoaded ( "mysql" ) == CON_EXTENSION_AVAILABLE) ? true : false;
}
function hasMySQLiExtension() {
	return (isPHPExtensionLoaded ( "mysqli" ) == CON_EXTENSION_AVAILABLE) ? true : false;
}
function doMySQLConnect($host, $username, $password) {
	$aOptions = array (
			'connection' => array (
					'host' => $host,
					'user' => $username,
					'password' => $password
			)
	);
	try {
		$db = new cDb ( $aOptions );
	} catch ( Exception $e ) {
		return array (
				$db,
				false
		);
	}

	if ($db->connect () == 0) {
		return array (
				$db,
				false
		);
	} else {
		return array (
				$db,
				true
		);
	}
}

/**
 * Selects a desired database by the link identifier and database name
 *
 * @param resource $linkid
 *        	MySQLi/MySQL link identifier
 * @param string $database
 * @return boolean
 */
function doMySQLSelectDB($linkid, $database) {
	$extension = getMySQLDatabaseExtension ();

	if (CON_SETUP_MYSQLI === $extension) {
		return (@mysqli_select_db ( $linkid, $database )) ? true : false;
	} elseif (CON_SETUP_MYSQL === $extension) {
		return (@mysql_select_db ( $database, $linkid )) ? true : false;
	} else {
		return false;
	}
}
function getSetupMySQLDBConnection($full = true) {
	global $cfg;

	$cfgDb = $cfg ['db'];

	if ($full === false) {
		// Connection parameter without database
		unset ( $cfgDb ['connection'] ['database'] );
	}

	$db = new cDb ( $cfgDb );
	return $db;
}

/**
 * Checks existing MySQL extensions and returns 'mysqli' as default, 'mysql' or null.
 *
 * @return string null
 */
function getMySQLDatabaseExtension() {
	if (hasMySQLiExtension ()) {
		return CON_SETUP_MYSQLI;
	} elseif (hasMySQLExtension ()) {
		return CON_SETUP_MYSQL;
	} else {
		return null;
	}
}
function fetchMySQLVersion($db) {
	$db->query ( "SELECT VERSION()" );

	return ($db->nextRecord ()) ? $db->f ( 0 ) : false;
}
function fetchMySQLUser($db) {
	$db->query ( "SELECT USER()" );

	return ($db->nextRecord ()) ? $db->f ( 0 ) : false;
}
function checkMySQLDatabaseCreation($db, $database, $charset = '', $collation = '') {
	if (checkMySQLDatabaseExists ( $db, $database )) {
		return true;
	} else if($collation == '') {
		$db->query ( "CREATE DATABASE `%s`", $database );
		return ($db->getErrorNumber () == 0) ? true : false;
	} else {
		$db->query ( "CREATE DATABASE `%s` CHARACTER SET %s COLLATE %s", $database, $charset, $collation );
		return ($db->getErrorNumber () == 0) ? true : false;
	}
}
function checkMySQLDatabaseExists($db, $database) {
	$db->connect ();

	if (doMySQLSelectDB ( $db->getLinkId (), $database )) {
		return true;
	} else {
		$db->query ( "SHOW DATABASES LIKE '%s'", $database );
		return ($db->nextRecord ()) ? true : false;
	}
}
function checkMySQLDatabaseUse($db, $database) {
	$db->connect ();
	return doMySQLSelectDB ( $db->getLinkId (), $database );
}
function checkMySQLTableCreation($db, $database, $table) {
	if (checkMySQLDatabaseUse ( $db, $database ) == false) {
		return false;
	}

	$db->query ( "CREATE TABLE `%s` (test INT(1) NOT NULL) ENGINE = MYISAM;", $table );

	return ($db->getErrorNumber () == 0) ? true : false;
}
function checkMySQLLockTable($db, $database, $table) {
	if (checkMySQLDatabaseUse ( $db, $database ) == false) {
		return false;
	}

	$db->query ( "LOCK TABLES `%s` WRITE", $table );

	return ($db->getErrorNumber () == 0) ? true : false;
}
function checkMySQLUnlockTables($db, $database) {
	if (checkMySQLDatabaseUse ( $db, $database ) == false) {
		return false;
	}

	$db->query ( "UNLOCK TABLES" );

	return ($db->getErrorNumber () == 0) ? true : false;
}
function checkMySQLDropTable($db, $database, $table) {
	if (checkMySQLDatabaseUse ( $db, $database ) == false) {
		return false;
	}

	$db->query ( "DROP TABLE `%s`", $table );

	return ($db->getErrorNumber () == 0) ? true : false;
}
function checkMySQLDropDatabase($db, $database) {
	$db->query ( "DROP DATABASE `%s`", $database );

	return ($db->getErrorNumber () == 0) ? true : false;
}
function fetchMySQLStorageEngines($db) {
	$db->query ( "SHOW ENGINES" );

	$engines = array ();

	while ( $db->nextRecord () ) {
		$engines [] = $db->f ( 0 );
	}

	return $engines;
}

/**
 * Returns all suppported character sets (field Charset) from the MySQL database.
 *
 * @param cDB|null $db
 * @return array
 */
function fetchMySQLCharsets($db = null) {
	if (! is_object ( $db )) {
		// No DB object, return static list
		return array (
				'big5',
				'dec8',
				'cp850',
				'hp8',
				'koi8r',
				'latin1',
				'latin2',
				'swe7',
				'ascii',
				'ujis',
				'sjis',
				'hebrew',
				'tis620',
				'euckr',
				'koi8u',
				'gb2312',
				'greek',
				'cp1250',
				'gbk',
				'latin5',
				'armscii8',
				'utf8',
				'ucs2',
				'cp866',
				'keybcs2',
				'macce',
				'macroman',
				'cp852',
				'latin7',
				'utf8mb4',
				'cp1251',
				'utf16',
				'cp1256',
				'cp1257',
				'utf32',
				'binary',
				'geostd8',
				'cp932',
				'eucjpms'
		);
	}

	$db->query ( 'SHOW CHARACTER SET' );

	$charsets = array ();

	while ( $db->nextRecord () ) {
		$charsets [] = $db->f ( 'Charset' );
	}

	return $charsets;
}

/**
 * Returns all suppported collations for a specific charset
 *
 * @param cDB|null $db
 * @param
 *        	string The charset for the collation
 * @return array
 */
function fetchMySQLCollations($db = null, $charset = "") {
	if (! is_object ( $db )) {
		// No DB object, return static list
		return array (
				'big5_chinese_ci',
				'dec8_swedish_ci',
				'cp850_general_ci',
				'hp8_english_ci',
				'koi8r_general_ci',
				'latin1_swedish_ci',
				'latin2_general_ci',
				'swe7_swedish_ci',
				'ascii_general_ci',
				'ujis_japanese_ci',
				'sjis_japanese_ci',
				'hebrew_general_ci',
				'tis620_thai_ci',
				'euckr_korean_ci',
				'koi8u_general_ci',
				'gb2312_chinese_ci',
				'greek_general_ci',
				'cp1250_general_ci',
				'gbk_chinese_ci',
				'latin5_turkish_ci',
				'armscii8_general_ci',
				'utf8_general_ci',
				'utf8_unicode_ci',
				'ucs2_general_ci',
				'cp866_general_ci',
				'keybcs2_general_ci',
				'macce_general_ci',
				'macroman_general_ci',
				'cp852_general_ci',
				'latin7_general_ci',
				'utf8mb4_general_ci',
				'cp1251_general_ci',
				'utf16_general_ci',
				'cp1256_general_ci',
				'cp1257_general_ci',
				'utf32_general_ci',
				'binary',
				'geostd8_general_ci',
				'cp932_japanese_ci',
				'eucjpms_japanese_ci'
		);
	}

	$db->query ( 'SHOW COLLATION' );

	$charsets = array ();

	while ( $db->nextRecord () ) {
		$charsets [] = $db->f ( 'Collation' );
	}

	return $charsets;
}

?>