<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    CONTENIDO setup
 * @version    0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
     die('Illegal call');
}


// @FIXME: Comment me plz!
function injectSQL($db, $prefix, $file, $replacements = array())
{
    $file = trim($file);

    if (!isReadable($file)) {
        return false;
    }

    $sqlFile = cFileHandler::read($file);

    $sqlFile = removeComments($sqlFile);
    $sqlFile = removeRemarks($sqlFile);
    $sqlFile = str_replace("!PREFIX!", $prefix, $sqlFile);
    $sqlFile = trim($sqlFile);

    $sqlChunks = splitSqlFile(trim($sqlFile), ";");

    foreach ($sqlChunks as $sqlChunk) {
        foreach ($replacements as $find => $replace) {
            $sqlChunk = str_replace($find, $replace, $sqlChunk);
        }

        $db->query($sqlChunk);

        if ($db->Errno != 0) {
            logSetupFailure("Unable to execute SQL statement:\n" . $sqlChunk . "\nMysql Error: " . $db->Error . " (" . $db->Errno . ")");
            $_SESSION['install_failedchunks'] = true;
        }
    }

    return true;
}

// @FIXME: Comment me plz!
function addAutoIncrementToTables($db, $cfg)
{
    $filterTables = array(
        $cfg['sql']['sqlprefix'].'_pica_alloc_con',
        $cfg['sql']['sqlprefix'].'_pica_lang',
        $cfg['sql']['sqlprefix'].'_sequence',
        $cfg['sql']['sqlprefix'].'_phplib_active_sessions',
        $cfg['sql']['sqlprefix'].'_online_user',
        $cfg['sql']['sqlprefix'].'_pi_linkwhitelist',
        $cfg['sql']['sqlprefix'].'_phplib_auth_user_md5',
    );

    $sql = 'SHOW TABLES FROM  ' . $cfg['db']['connection']['database'] . '';
    $db->query($sql);

    if ($db->Error != 0) {
        logSetupFailure("Unable to execute SQL statement:\n" . $sql . "\nMysql Error: " . $db->Error . " (" . $db->Errno . ")");
        $_SESSION['install_failedupgradetable'] = true;
    }

    $i = 0;
    while ($row = mysql_fetch_row($db->Query_ID)) {
        if (in_array($row[0], $filterTables) === false && strpos($row[0], $cfg['sql']['sqlprefix'].'_') !== false) {
           alterTableHandling($row);
           $i++;
        }
    }

    // Security reason: Check iterator alter table before drop table. The count of Tables must be not less than 65.
    if ($i > 65) {
        $sql = 'DROP TABLE IF EXISTS '.$cfg['sql']['sqlprefix'].'_sequence';
        $db->query($sql);
    }
}

function URLDecodeTables($db) {
	global $cfg;

	URLDecodeTable($db, $cfg['tab']['frontendusers']);
	URLDecodeTable($db, $cfg['tab']['content']);
	URLDecodeTable($db, $cfg['tab']['properties']);
	URLDecodeTable($db, $cfg['tab']['upl_meta']);
	URLDecodeTable($db, $cfg['tab']['container']);
	URLDecodeTable($db, $cfg['tab']['pica_lang']);
	URLDecodeTable($db, $cfg['tab']['news_rcp']);
	URLDecodeTable($db, $cfg['tab']['art_lang']);
	URLDecodeTable($db, $cfg['tab']['user_prop']);
	URLDecodeTable($db, $cfg['tab']['system_prop']);
	URLDecodeTable($db, $cfg['tab']['art_spec']);
	URLDecodeTable($db, $cfg['tab']['news_jobs']);
}

function URLDecodeTable($db, $table) {
	$sql = "SELECT * FROM ".$table;
	$db->query($sql);

	while($db->next_record()) {
		$row = $db->toArray(FETCH_ASSOC);
		$sql = "UPDATE ".$table." SET ";
		foreach($row as $key => $value) {
			$sql .= "`".$key."`='".cSecurity::escapeDB(urldecode($value), $db)."', ";
		}
		$sql = substr($sql, 0, strlen($sql) - 2)." WHERE ";
		foreach($row as $key => $value) {
			$sql .= "`".$key."`= '".$value."' AND ";
		}
		$sql = substr($sql, 0, strlen($sql) - 5).";";
		$db2 = getSetupMySQLDBConnection(false);
		$db2->query($sql);
	}
}

function convertToDatetime($db, $cfg) {
    $db->query("ALTER TABLE ".$cfg['sql']['sqlprefix']."_piwf_art_allocation CHANGE  `starttime`  `starttime` DATETIME NOT NULL");
    $db->query("ALTER TABLE ".$cfg['sql']['sqlprefix']."_template_conf CHANGE  `created`  `created` DATETIME NOT NULL");
}

// @FIXME: Comment me plz!
function alterTableHandling($row)
{
    $tableName = $row[0];

    $db = getSetupMySQLDBConnection(false);
    $sql = 'SHOW KEYS FROM `' . $tableName . '` WHERE Key_name="PRIMARY"';
    $db->query($sql);
    while ($row = mysql_fetch_row($db->Query_ID)) {
        $primaryKey = $row[4];
        $dbAlter = getSetupMySQLDBConnection(false);
        $sqlAlter = 'ALTER TABLE `' . $tableName . '` CHANGE `' . $primaryKey . '` `' . $primaryKey . '` INT(10) NOT NULL AUTO_INCREMENT';
        $dbAlter->query($sqlAlter);
        if ($dbAlter->Errno != 0) {
            logSetupFailure("Unable to execute SQL statement:\n" . $sqlAlter . "\nMysql Error: " . $dbAlter->Error . " (" . $dbAlter->Errno . ")");
            $_SESSION['install_failedupgradetable'] = true;
        }
    }
}

/**
 * Will strip the sql comment lines out of an uploaded sql file
 * specifically for mssql and postgres type files in the install....
 * @param   string  $output
 * @return  string
 */
function removeComments(&$output)
{
    $lines = explode("\n", $output);
    $output = "";

    // try to keep mem. use down
    $linecount = count($lines);

    $in_comment = false;
    for ($i = 0; $i < $linecount; $i++) {
        if (preg_match("/^\/\*/", preg_quote($lines[$i]))) {
            $in_comment = true;
        }

        if (!$in_comment) {
            $output .= $lines[$i] . "\n";
        }

        if (preg_match("/\*\/$/", preg_quote($lines[$i]))) {
            $in_comment = false;
        }
    }

    unset($lines);
    return $output;
}

/**
 * Will strip the sql comment lines out of an uploaded sql file
 * @param   string  $sql
 * @return  string
 */
function removeRemarks($sql)
{
    $lines = explode("\n", $sql);

    // try to keep mem. use down
    $sql = "";

    $linecount = count($lines);
    $output = "";

    for ($i = 0; $i < $linecount; $i++) {
        if (($i != ($linecount - 1)) || (strlen($lines[$i]) > 0)) {
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
 * @param   string  $sql
 * @param   string  $delimiter
 * @return  string
 */
function splitSqlFile($sql, $delimiter)
{
    // Split up our string into "possible" SQL statements.
    $tokens = explode($delimiter, $sql);

    // try to save mem.
    $sql = "";
    $output = array();

    // we don't actually care about the matches preg gives us.
    $matches = array();

    // this is faster than calling count($oktens) every time thru the loop.
    $token_count = count($tokens);
    for ($i = 0; $i < $token_count; $i++) {
        // Don't wanna add an empty string as the last thing in the array.
        if (($i != ($token_count - 1)) || (strlen($tokens[$i] > 0))) {
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

                for ($j = $i + 1; (!$complete_stmt && ($j < $token_count)); $j++) {
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

?>