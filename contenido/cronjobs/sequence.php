<?php 

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO path
$contenidoPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/';

// CONTENIDO startup process
include_once($contenidoPath . 'includes/startup.php');


$db = new DB_Contenido();

// first all values higher then 0
$filterTables = array('con_pica_alloc_con', 'con_pica_lang', 'con_sequence');
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
echo '<br/> Result Rows:'.$i;

function getNextId($row) {
    $tableName = $row[0];
    $nextId = $row[1];
    $db = new DB_Contenido();
    $sql = 'SHOW KEYS FROM '.$tableName.' WHERE Key_name="PRIMARY"';
    $db->query($sql);
     while ($row = mysql_fetch_row($db->Query_ID)) {
        
        $primaryKey = $row[4];
        $dbAlter = new DB_Contenido();
        $sqlAlter = 'ALTER TABLE `'.$tableName.'` CHANGE `'.$primaryKey.'` `'.$primaryKey.'` INT( 10 ) NOT NULL AUTO_INCREMENT';
        echo '<br/>query:'.$sqlAlter;
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
