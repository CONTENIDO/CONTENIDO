<?php

/**
 * This file contains various helper functions to prepare and handle SQL data.
 *
 * @package    Setup
 * @subpackage Helper_MySQL
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Executes a file of SQL queries
 *
 * @param cDb $db
 * @param string $prefix
 * @param string $file
 * @param array $replacements
 *
 * @return bool
 * @throws cInvalidArgumentException
 */
function injectSQL(cDb $db, string $prefix, string $file, array $replacements = [])
{
    $file = trim($file);

    if (!is_readable($file)) {
        return false;
    }

    $sqlFile = cFileHandler::read($file);

    $sqlFile = removeComments($sqlFile);
    $sqlFile = removeRemarks($sqlFile);
    $sqlFile = trim($sqlFile);

    $sqlTemplate = new cSqlTemplate();
    $sqlTemplate->addReplacements($replacements);
    $sqlFile = $sqlTemplate->parse($sqlFile);

    $sqlChunks = splitSqlFile(trim($sqlFile), ";");

    foreach ($sqlChunks as $sqlChunk) {
        $db->query($sqlChunk);

        if ($db->getErrorNumber() != 0) {
            logSetupFailure("Unable to execute SQL statement:\n" . $sqlChunk . "\nMysql Error: " . $db->getErrorMessage() . " (" . $db->getErrorNumber() . ")");
            $_SESSION['install_failedchunks'] = true;
        }
    }

    return true;
}

/**
 * Adds the autoincrement property to all primary keys in CONTENIDO tables
 * @param cDB $db
 * @param array $cfg
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function addAutoIncrementToTables(cDB $db, array $cfg)
{
    // All primary keys in tables except these below!
    $filterTables = [
        $cfg['sql']['sqlprefix'] . '_groups',
        $cfg['sql']['sqlprefix'] . '_pica_alloc_con',
        $cfg['sql']['sqlprefix'] . '_pica_lang',
        $cfg['sql']['sqlprefix'] . '_sequence',
        $cfg['sql']['sqlprefix'] . '_phplib_active_sessions',
        $cfg['sql']['sqlprefix'] . '_online_user',
        $cfg['sql']['sqlprefix'] . '_pi_linkwhitelist',
        $cfg['sql']['sqlprefix'] . '_phplib_auth_user_md5',
        $cfg['sql']['sqlprefix'] . '_user',
        $cfg['sql']['sqlprefix'] . '_iso_639_2',
        $cfg['sql']['sqlprefix'] . '_iso_3166',
    ];

    $sql = $db->prepare('SHOW TABLES FROM `%s`', $cfg['db']['connection']['database']);
    $db->query($sql);

    if ($db->getErrorNumber() != 0) {
        logSetupFailure("Unable to execute SQL statement:\n" . $sql . "\nMysql Error: " . $db->getErrorMessage() . " (" . $db->getErrorNumber() . ")");
        $_SESSION['install_failedupgradetable'] = true;
    }

    $aRows = [];
    while ($db->nextRecord()) {
        $aRows[] = $db->getRecord();
    }
    foreach ($aRows as $row) {
        if (in_array($row[0], $filterTables) === false && cString::findFirstPos($row[0], $cfg['sql']['sqlprefix'] . '_') !== false) {
            alterTableHandling($row[0]);
        }
    }

    // Security reason: Check iterator alter table before drop table. The count of Tables must be not less than 65.
    if (count($aRows) > 65) {
        $db->query('DROP TABLE IF EXISTS `%s`', $cfg['sql']['sqlprefix'] . '_sequence');
    }
}

/**
 * Adds salts to the passwords of the backend and frontend users. Converts old passwords into new ones
 * @param cDb $db The database object
 * @throws cDbException
 */
function addSaltsToTables(cDb $db)
{
    global $cfg;

    $db2 = getSetupMySQLDBConnection();

    $db->query("SHOW COLUMNS FROM `%s` LIKE 'salt'", $cfg['tab']['user']);
    if ($db->numRows() == 0) {
        $db2->query("ALTER TABLE `%s` CHANGE password password VARCHAR(64)", $cfg["tab"]["user"]);
        $db2->query("ALTER TABLE `%s` ADD salt VARCHAR(32) AFTER password", $cfg["tab"]["user"]);
    }

    $db->query("SELECT * FROM `%s`", $cfg["tab"]["user"]);
    while ($db->nextRecord()) {
        if ($db->f("salt") == "") {
            $salt = md5($db->f("username") . rand(1000, 9999) . rand(1000, 9999) . rand(1000, 9999));
            $hash = hash("sha256", $db->f("password") . $salt);
            $db2->query(
                "UPDATE `%s` SET salt='%s', password='%s' WHERE user_id='%s'",
                $cfg["tab"]["user"], $salt, $hash, $db->f("user_id")
            );
        }
    }

    $db->query("SHOW COLUMNS FROM `%s` LIKE 'salt'", $cfg['tab']['frontendusers']);
    if ($db->numRows() == 0) {
        $db2->query("ALTER TABLE `%s` CHANGE password password VARCHAR(64)", $cfg["tab"]["frontendusers"]);
        $db2->query("ALTER TABLE `%s` ADD salt VARCHAR(32) AFTER password", $cfg["tab"]["frontendusers"]);
    }

    $db->query("SELECT * FROM `%s`", $cfg["tab"]["frontendusers"]);
    while ($db->nextRecord()) {
        if ($db->f("salt") == "") {
            $salt = md5($db->f("username") . rand(1000, 9999) . rand(1000, 9999) . rand(1000, 9999));
            $hash = hash("sha256", $db->f("password") . $salt);
            $db2->query(
                "UPDATE `%s` SET salt='%s', password='%s' WHERE idfrontenduser='%s'",
                $cfg["tab"]["frontendusers"], $salt, $hash, $db->f("idfrontenduser")
            );
        }
    }
}

function urlDecodeTables(cDb $db)
{
    global $cfg;

    urlDecodeTable($db, $cfg['tab']['frontendusers']);
    urlDecodeTable($db, $cfg['tab']['content']);
    urlDecodeTable($db, $cfg['tab']['properties']);
    urlDecodeTable($db, $cfg['tab']['upl_meta']);
    urlDecodeTable($db, $cfg['tab']['container']);
    urlDecodeTable($db, $cfg['sql']['sqlprefix'] . '_pica_lang', true);
    urlDecodeTable($db, $cfg['sql']['sqlprefix'] . '_pi_news_rcp', true);
    urlDecodeTable($db, $cfg['tab']['art_lang']);
    urlDecodeTable($db, $cfg['tab']['user_prop']);
    urlDecodeTable($db, $cfg['tab']['system_prop']);
    urlDecodeTable($db, $cfg['tab']['art_spec']);
    urlDecodeTable($db, $cfg['sql']['sqlprefix'] . '_pi_news_jobs', true);
}

function urlDecodeTable(cDb $db, string $table, bool $checkTableExists = false)
{
    if ($checkTableExists === true) {
        $db->query("SHOW TABLES LIKE '%s'", $table);
        if ($db->nextRecord() === false) {
            return;
        }
    }

    $db->query("SELECT * FROM `%s`", $table);

    $db2 = getSetupMySQLDBConnection(false);

    while ($db->nextRecord()) {
        $row = $db->toArray();

        $sql = "UPDATE `" . $table . "` SET ";
        foreach ($row as $key => $value) {
            if (cString::getStringLength($value) > 0) {
                $sql .= "`" . $key . "`='" . $db->escape(urldecode($value)) . "', ";
            }
        }
        $sql = cString::getPartOfString($sql, 0, cString::getStringLength($sql) - 2) . " WHERE ";
        foreach ($row as $key => $value) {
            if (cString::getStringLength($value) > 0) {
                $sql .= "`" . $key . "`= '" . $db->escape($value) . "' AND ";
            }
        }
        $sql = cString::getPartOfString($sql, 0, cString::getStringLength($sql) - 5) . ";";

        $db2->query($sql);
    }
}

function convertToDatetime(cDb $db, array $cfg)
{
    $db->query("SHOW TABLES LIKE '%s'", $cfg['sql']['sqlprefix'] . '_piwf_art_allocation');
    if ($db->nextRecord()) {
        $db->query("ALTER TABLE `%s` CHANGE `starttime` `starttime` DATETIME NOT NULL", $cfg['sql']['sqlprefix'] . '_piwf_art_allocation');
    }

    $db->query("ALTER TABLE `%s` CHANGE `created` `created` DATETIME NOT NULL", $cfg['sql']['sqlprefix'] . '_template_conf');
}

/**
 * Converts a table field value of type date to a datetime format ('YYYY-MM-DD HH:MM:SS'),
 * if the value has the date format ('YYYY-MM-DD').
 *
 * @param cDb $db
 * @param string $table
 * @param string $field
 * @param string $defaultTime - Format has to be 'HH:MM:SS'
 * @throws cDbException
 */
function convertDateValuesToDateTimeValue(cDb $db, string $table, string $field, string $defaultTime = '00:00:00')
{
    // Update format 'YYYY-MM-DD' to 'YYYY-MM-DD 00:00:00'
    $sql = "UPDATE `:table` SET `:field` = CONCAT(`:field`, ' ', ':time') WHERE CHAR_LENGTH(`:field`) = 10";
    $db->query($sql, ['table' => $table, 'field' => $field, 'time' => $defaultTime]);
}

/**
 * Converts a table field value of type date to a datetime format ('YYYY-MM-DD HH:MM:SS'),
 * if the value is null (null or empty string).
 *
 * @param cDb $db
 * @param string $table
 * @param string $field
 * @param string $defaultDateTime - Format has to be 'YYYY-MM-DD HH:MM:SS'.
 *     You can also use 'CURRENT_TIMESTAMP' or 'NOW()' to update the field to current timestamp.
 * @throws cDbException
 */
function convertNullDateValuesToDateTimeValue(
    cDb $db, string $table, string $field, string $defaultDateTime = '0000-00-00 00:00:00'
)
{
    // Update '' or NULL values to '0000-00-00 00:00:00'
    if ($defaultDateTime === 'CURRENT_TIMESTAMP' || $defaultDateTime === 'NOW()') {
        $sql = "UPDATE `:table` SET :field = :datetime WHERE `:field` IS NULL";
    } else {
        $sql = "UPDATE `:table` SET :field = ':datetime' WHERE `:field` IS NULL";
    }
    $db->query($sql, ['table' => $table, 'field' => $field, 'datetime' => $defaultDateTime]);
}

/**
 * Changes the primary key of the given table to an auto increment type
 * @param string $tableName
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function alterTableHandling(string $tableName)
{
    $db = getSetupMySQLDBConnection(false);
    $dbAlter = getSetupMySQLDBConnection(false);

    $sql = $db->prepare("SHOW KEYS FROM `%s` WHERE Key_name='PRIMARY'", $tableName);
    $db->query($sql);
    while ($db->nextRecord()) {
        $row = $db->getRecord();
        $primaryKey = $row[4];
        $sqlAlter = $dbAlter->prepare('ALTER TABLE `%s` CHANGE `%s` `%s` INT(11) NOT NULL AUTO_INCREMENT', $tableName, $primaryKey, $primaryKey);
        $dbAlter->query($sqlAlter);
        if ($dbAlter->getErrorNumber() != 0) {
            logSetupFailure("Unable to execute SQL statement:\n" . $sqlAlter . "\nMysql Error: " . $dbAlter->getErrorMessage() . " (" . $dbAlter->getErrorNumber() . ")");
            $_SESSION['install_failedupgradetable'] = true;
        }
    }
}

/**
 * Will strip the sql comment lines out of an uploaded sql file
 * specifically for mssql and postgres type files in the install....
 * @param string $output
 * @return  string
 */
function removeComments(string &$output): string
{
    $lines = explode("\n", $output);
    $lineCount = count($lines);
    $output = "";
    $inComment = false;

    for ($i = 0; $i < $lineCount; $i++) {
        if (preg_match("/^\/\*/", preg_quote($lines[$i]))) {
            $inComment = true;
        }

        if (!$inComment) {
            $output .= $lines[$i] . "\n";
        }

        if (preg_match("/\*\/$/", preg_quote($lines[$i]))) {
            $inComment = false;
        }
    }

    unset($lines);
    return $output;
}

/**
 * Will strip the sql comment lines out of an uploaded sql file
 * @param string $sql
 * @return  string
 */
function removeRemarks(string $sql): string
{
    $lines = explode("\n", $sql);
    $lineCount = count($lines);
    $output = "";

    for ($i = 0; $i < $lineCount; $i++) {
        if (($i != ($lineCount - 1)) || (cString::getStringLength($lines[$i]) > 0)) {
            if (!empty($lines[$i]) && $lines[$i][0] != "#") {
                $output .= $lines[$i] . "\n";
            } else {
                $output .= "\n";
            }
            // Trading a bit of speed for lower mem. use here.
            $lines[$i] = "";
        }
    }

    return $output;
}

/**
 * Will split an uploaded sql file into single sql statements.
 * Note: expects trim() to have already been run on $sql.
 *
 * @param string $sql
 * @param string $delimiter
 * @return  array
 */
function splitSqlFile(string $sql, string $delimiter): array
{
    // Split up our string into "possible" SQL statements.
    $tokens = explode($delimiter, $sql);

    $output = [];

    // we don't actually care about the matches preg gives us.
    $matches = [];

    // this is faster than calling count($oktens) every time through the loop.
    $tokenCount = count($tokens);
    for ($i = 0; $i < $tokenCount; $i++) {
        // Don't want to add an empty string as the last thing in the array.
        if (($i != ($tokenCount - 1)) || (cString::getStringLength($tokens[$i] > 0))) {
            // This is the total number of single quotes in the token.
            $total_quotes = preg_match_all("/'/", $tokens[$i], $matches);
            // Counts single quotes that are preceded by an odd number of backslashes,
            // which means they're escaped quotes.
            $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$i], $matches);

            $unescaped_quotes = $total_quotes - $escaped_quotes;

            // If the number of unescaped quotes is even, then the delimiter did NOT occur inside a string literal.
            if (($unescaped_quotes % 2) == 0) {
                // It's a complete sql statement.
                $output[] = $tokens[$i];
                // save memory.
                $tokens[$i] = "";
            } else {
                // incomplete sql statement. keep adding tokens until we have a complete one.
                // $temp will hold what we have so far.
                $temp = $tokens[$i] . $delimiter;
                // save memory..
                $tokens[$i] = "";

                // Do we have a complete statement yet?
                $complete_stmt = false;

                for ($j = $i + 1; (!$complete_stmt && ($j < $tokenCount)); $j++) {
                    // This is the total number of single quotes in the token.
                    $total_quotes = preg_match_all("/'/", $tokens[$j], $matches);
                    // Counts single quotes that are preceded by an odd number of backslashes,
                    // which means they're escaped quotes.
                    $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$j], $matches);

                    $unescaped_quotes = $total_quotes - $escaped_quotes;

                    if (($unescaped_quotes % 2) == 1) {
                        // odd number of unescaped quotes. In combination with the previous incomplete
                        // statement(s), we now have a complete statement. (2 odds always make an even)
                        $output[] = $temp . $tokens[$j];

                        // save memory.
                        $tokens[$j] = "";
                        $temp = "";

                        // exit the loop.
                        $complete_stmt = true;
                        // make sure the outer loop continues at the right point.
                        $i = $j;
                    } else {
                        // even number of unescaped quotes. We still don't have a complete statement.
                        // (1 odd and 1 even always make an odd)
                        $temp .= $tokens[$j] . $delimiter;
                        // save memory.
                        $tokens[$j] = "";
                    }
                } // for..
            } // else
        }
    }

    return $output;
}
