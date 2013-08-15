<?php
 
 global $idart, $file;
 $idart = 83;
 $file = $_REQUEST['file'];
 $_GET['file'] = $file;

 include (dirname(__FILE__) . DIRECTORY_SEPARATOR . 'front_content.php');
?>