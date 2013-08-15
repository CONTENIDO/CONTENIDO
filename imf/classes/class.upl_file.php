<?php
 cInclude('frontend', 'classes/class.upl_content.php');
  Class UplFile extends uplContent {

     public function __construct($idupl = 0, $currFilePath = 0, $type = 0, $date = 0) {
         parent::__construct($idupl, $currFilePath, $type, $date);
     }

     public function isDir() {
         return false;
     }

 }
?>
