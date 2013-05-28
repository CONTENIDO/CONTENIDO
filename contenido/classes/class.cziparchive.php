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

     public static function isExtracted($pathDir) {

         if (file_exists($pathDir) and is_dir($pathDir)) {
             return true;
         } else {
             return false;
         }
     }

     public static function extractOverRide($file, $extractPath, $extractPathUserInput = NULL) {

         if (isset($extractPathUserInput)) {

             // validate user input
             $extractPath .= uplCreateFriendlyName($extractPathUserInput);
             $extractPath = str_replace('.', '', $extractPath);
         }

         $zip = new ZipArchive;
         $state = $zip->open($file);

         if ($state === TRUE) {

             for ($i = 0; $i < $zip->numFiles; $i++) {

                 $file = $zip->getNameIndex($i);
                 //remove '/' for validation -> directory names
                 $tmpFile = str_replace('/', '', $file);

                 //extract only file with valid filename
                 if (cFileHandler::validateFilename($tmpFile, FALSE)) {
                     $zip->extractTo($extractPath, $file);
                   }
                }

             $zip->close();
         } else {
             echo('can not open zip file!');
         }
     }

     public static function extract($file, $extractPath, $extractPathUserInput = NULL) {


         if (isset($extractPathUserInput)) {

             // validate user input
             $extractPath .= uplCreateFriendlyName($extractPathUserInput);
             $extractPath = str_replace('.', '', $extractPath);
         }

         if (file_exists($extractPath) and is_dir($extractPath)) {
             $ar = cZipArchive::readExistingFiles($extractPath);

         }
         // :: OVERRIDE
         $zip = new ZipArchive;
         $state = $zip->open($file);

         //Does the directory already exists ?
         if (cZipArchive::isExtracted($extractPath)) {

             if ($state === TRUE) {

                 for ($i = 0; $i < $zip->numFiles; $i++) {

                     $file = $zip->getNameIndex($i);
                      $tmpFile = str_replace('/', '', $file);

                     if (cFileHandler::validateFilename($tmpFile, FALSE)) {

                         if (!array_search($file, $ar)) {
                             $zip->extractTo($extractPath, $file);
                     }
                }
                 }

                 $zip->close();
                 }else {
                 echo('can not open zip file!');
             }
         } else {
             if ($state === TRUE) {

                 for ($i = 0; $i < $zip->numFiles; $i++) {

                     $file = $zip->getNameIndex($i);
                    //remove '/' for validation -> directory names
                     $tmpFile = str_replace('/', '', $file);

                   if (cFileHandler::validateFilename($tmpFile, FALSE)) {
                         $zip->extractTo($extractPath, $file);
                    }
                }
                 $zip->close();
             } else {
                 echo('can not open zip file!');
             }
         }
     }

 }

?>
