<?php

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO path
$contenidoPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/';

// CONTENIDO startup process
include_once($contenidoPath . 'includes/startup.php');


$db = cRegistry::getDb();
$db->free();


// first all values higher then 0
$filterTables = array($cfg['sql']['sqlprefix'].'_pica_alloc_con', $cfg['sql']['sqlprefix'].'_pica_lang', $cfg['sql']['sqlprefix'].'_sequence');
$sql2 = 'SELECT *
        FROM '.$cfg['db']['connection']['database'].'.con_sequence';

$sql = 'SHOW TABLES FROM  '.$cfg['db']['connection']['database'].'';
$db->query($sql);
if($db->Error !=0) {
    echo "<pre>" . $sql . "\nMysql Error:" . $db->Error . "(" . $db->Errno . ")</pre>";
}

$i = 0;
while ($row = mysql_fetch_row($db->Query_ID)) {

    if(in_array($row[0], $filterTables) === false) {
  # echo "<br/> Tabelle: {$row[0]}\n";
       getNextId($row);
       $i++;
    }
}
echo "\n Result Rows:".$i;

if($i > 70) {
    $sql = 'drop table if exists '.$cfg['sql']['sqlprefix'].'_sequence';
    $db->query($sql);
}

function getNextId($row) {
    $tableName = $row[0];
    //$nextId = $row[1];
    debug($row);

    $db = cRegistry::getDb();
    $sql = 'SHOW KEYS FROM '.$tableName.' WHERE Key_name="PRIMARY"';
    $db->query($sql);
     while ($row = mysql_fetch_row($db->Query_ID)) {

        $primaryKey = $row[4];
        $dbAlter = cRegistry::getDb();
        $sqlAlter = 'ALTER TABLE `'.$tableName.'` CHANGE `'.$primaryKey.'` `'.$primaryKey.'` INT( 10 ) NOT NULL AUTO_INCREMENT';
        #echo '<br/>query:'.$sqlAlter;
        $dbAlter->query($sqlAlter);
        if($db->Errno !=0) {
            echo "<pre>" . $sqlAlter . "\nMysql Error:" . $db->Error . "(" . $db->Errno . ")</pre>";
        }
    }

}

function debug($string) {
    echo '<pre>';
    print_r($string);
    echo '</pre>';
}
