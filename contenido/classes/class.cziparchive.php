<?php

 defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

 class cZipArchive {

     public static function readExistingFiles($dirPath) {
         $ar = array();
         if ($handle = opendir($dirPath)) {

             while (false !== ($file = readdir($handle))) {

                 //  hotfix : fileHandler returns filename '.' als valid filename
                 if (cFileHandler::validateFilename($file, FALSE) && $file[0] != '.') {

                     $ar[] = $file;
                 }
             }

             closedir($handle);
         }
         return $ar;
     }

     public static function isExtracted($file) {


         // name without file ending.
         $pathDir = strstr($file, '.', true);
         if (file_exists($pathDir) and is_dir($pathDir)) {
             cZipArchive::readExistingFiles($pathDir);
         }
         return false;
     }

     public static function extractOverRide($file, $extractPath, $extractPathUserInput = NULL) {
         //   try {
         if (isset($extractPathUserInput)) {

             // validate user input
             $extractPath .= uplCreateFriendlyName($extractPathUserInput);
             $extractPath = str_replace('.', '_', $extractPath);
         }

         $zip = new ZipArchive;
         $state = $zip->open($file);

         if ($state === TRUE) {

             for ($i = 0; $i < $zip->numFiles; $i++) {

                 $file = $zip->getNameIndex($i);
                 $zipFileName = substr(strrchr($file, '/'), 1);

                 //extract only file with valid filename

                 if (cFileHandler::validateFilename($zipFileName, FALSE)) {
                     if (cFileHandler::validateFilename($zipFileName, FALSE)) {
                         $zip->extractTo($extractPath, $file);
                     }
                 }
             }

             $zip->close();
         }
         else {
              echo('can not open zip file!');
         }
     }

     public static function extract($file, $extractPath, $extractPathUserInput = NULL) {

         if (isset($extractPathUserInput)) {

             // validate user input
             $extractPath .= uplCreateFriendlyName($extractPathUserInput);
             $extractPath = str_replace('.', '', $extractPath);
         }

         $pathDir = strstr($file, '.', true);
         if (file_exists($pathDir) and is_dir($pathDir)) {
             $ar = cZipArchive::readExistingFiles($pathDir);
         }
         // :: OVERRIDE
         $zip = new ZipArchive;
         $state = $zip->open($file);
         //Does the directory already exists ?
         if (cZipArchive::isExtracted($file)) {

             if ($state === TRUE) {

                 for ($i = 0; $i < $zip->numFiles; $i++) {

                     $file = $zip->getNameIndex($i);
                     $zipFileName = substr(strrchr($file, '/'), 1);

                     //extract only new files
                     if (cFileHandler::validateFilename($zipFileName, FALSE)) {

                         if (array_search($zipFileName, $ar) == FALSE) {
                             $zip->extractTo($extractPath, $file);
                         }
                     }
                 }
                 $zip->close();
             }
             else {
                 echo('can not open zip file!');
             }
         } else {
             if ($state === TRUE) {

                 for ($i = 0; $i < $zip->numFiles; $i++) {

                     $file = $zip->getNameIndex($i);
                     $zipFileName = substr(strrchr($file, '/'), 1);

                     //extract only file with valid filename
                     if (cFileHandler::validateFilename($zipFileName, FALSE)) {
                         if (cFileHandler::validateFilename($zipFileName, FALSE)) {
                             $zip->extractTo($extractPath, $file);
                         }
                     }
                 }

                 $zip->close();
             }
             else {
                  echo('can not open zip file!');
             }
         }
     }
 }
?>
