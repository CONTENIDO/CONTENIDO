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
 *   modified 2008-07-07, bilal arslan, added security fix
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
     die('Illegal call');
}


function injectSQL($db, $prefix, $file, $replacements = array(), &$failedChunks)
{
    $file = trim($file);

    if (!isReadable($file)) {
        return false;
    }

    $sqlFile = file_get_contents($file);

    $sqlFile = remove_comments($sqlFile);
    $sqlFile = remove_remarks($sqlFile);
    $sqlFile = str_replace("!PREFIX!", $prefix, $sqlFile);
    $sqlFile = trim($sqlFile);

    $sqlChunks = split_sql_file(trim($sqlFile), ";");

    foreach ($sqlChunks as $sqlChunk) {
        foreach ($replacements as $find => $replace) {
            $sqlChunk = str_replace($find, $replace, $sqlChunk);
        }

        $db->query($sqlChunk);

        if ($db->Errno != 0) {
            $failedChunks[] = array("sql" => $sqlChunk, "errno" => $db->Errno, "error" => $db->Error);
        }
    }

    return true;
}

function addAutoIncrementToTables($db, $cfg) {
    $errorLogHandle = fopen($cfg['path']['contenido']."logs/errorlog.txt", "wb+");
    $filterTables = array($cfg['sql']['sqlprefix'].'_pica_alloc_con',
                          $cfg['sql']['sqlprefix'].'_pica_lang',
                          $cfg['sql']['sqlprefix'].'_sequence',
                          $cfg['sql']['sqlprefix'].'_phplib_active_sessions',
                          $cfg['sql']['sqlprefix'].'_online_user',
                          $cfg['sql']['sqlprefix'].'_pi_linkwhitelist',);
    
    $sql = 'SHOW TABLES FROM  '.$cfg['db']['connection']['database'].'';
    $db->query($sql);
    
    if($db->Error !=0) {
        fwrite($errorLogHandle, "<pre>" . $sql . "\nMysql Error:" . $db->Error . "(" . $db->Errno . ")</pre>");
    }
    
    $i = 0;
    while ($row = mysql_fetch_row($db->Query_ID)) {
        if(in_array($row[0], $filterTables) === false && strpos($row[0], $cfg['sql']['sqlprefix'].'_') !== false) {
           alterTableHandling($row, $errorLogHandle);
           $i++;
        }
    }
    if($i > 70) {
        $sql = 'drop table if exists '.$cfg['sql']['sqlprefix'].'_sequence';
        $db->query($sql);
    }
    fclose($errorLogHandle);
}

function alterTableHandling($row, $errorLogHandle) {
    $tableName = $row[0];
    //$nextId = $row[1];
    //debug($row);
    
    $db = getSetupMySQLDBConnection(false);
    $sql = 'SHOW KEYS FROM '.$tableName.' WHERE Key_name="PRIMARY"';
    $db->query($sql);
     while ($row = mysql_fetch_row($db->Query_ID)) {
        
        $primaryKey = $row[4];
        $dbAlter = getSetupMySQLDBConnection(false);
        $sqlAlter = 'ALTER TABLE `'.$tableName.'` CHANGE `'.$primaryKey.'` `'.$primaryKey.'` INT( 10 ) NOT NULL AUTO_INCREMENT';
        $dbAlter->query($sqlAlter);
        if($db->Errno !=0) {
            fwrite($errorLogHandle, "<pre>" . $sql . "\nMysql Error:" . $db->Error . "(" . $db->Errno . ")</pre>");
        }
    }
}

//
// remove_comments will strip the sql comment lines out of an uploaded sql file
// specifically for mssql and postgres type files in the install....
//
function remove_comments(&$output)
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

//
// remove_remarks will strip the sql comment lines out of an uploaded sql file
//
function remove_remarks($sql)
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

//
// split_sql_file will split an uploaded sql file into single sql statements.
// Note: expects trim() to have already been run on $sql.
//
function split_sql_file($sql, $delimiter)
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
