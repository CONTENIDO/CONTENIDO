<?php

/**
 * This file contains the cZipArchive util class.
 *
 * @package Core
 * @subpackage Util
 * @version SVN Revision $Rev:$
 *
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 *
 * @author claus.schunk
 */
class cZipArchive {

    /**
     *
     * @param string $dirPath
     * @return multitype:string
     */
    public static function readExistingFiles($dirPath) {

        // check if $dirPath is a dir
        if (!is_dir($dirPath)) {
            return array();
        }

        // try to open $dirPath
        if (false === $handle = opendir($dirPath)) {
            return array();
        }

        // gather all files in $dirPath that are "valid" filenames
        $array = array();
        while (false !== $file = readdir($handle)) {
            // hotfix : fileHandler returns filename '.' als valid filename
            if (cFileHandler::validateFilename($file, FALSE) && $file[0] != '.') {
                $array[] = $file;
            }
        }

        // close dir handle
        closedir($handle);

        // return array of files
        return $array;
    }

    /**
     *
     * @param string $dirPath
     * @return boolean
     */
    public static function isExtracted($dirPath) {
        if (!file_exists($dirPath)) {
            return false;
        } else if (!is_dir($dirPath)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     *
     * @param string $file
     * @param string $extractPath
     * @param string $extractPathUserInput
     */
    public static function extractOverRide($file, $extractPath, $extractPathUserInput = NULL) {

        // validate user input
        if (isset($extractPathUserInput)) {
            $extractPath .= uplCreateFriendlyName($extractPathUserInput);
        }

        $zip = new ZipArchive();

        // try to open archive
        if (!$zip->open($file)) {
            echo ('can not open zip file!');
            return;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $file = $zip->getNameIndex($i);
            // remove '/' for validation -> directory names
            $tmpFile = str_replace('/', '', $file);
            // extract only file with valid filename
            if (cFileHandler::validateFilename($tmpFile, FALSE) && (substr($tmpFile, 0, 1) != '.') && (substr($tmpFile, 0, 1) != '_')) {
                $zip->extractTo($extractPath, $file);
            }
        }

        $zip->close();
    }

    /**
     *
     * @param string $file
     * @param string $extractPath
     * @param string $extractPathUserInput
     */
    public static function extract($file, $extractPath, $extractPathUserInput = NULL) {
        if (isset($extractPathUserInput)) {

            // validate user input
            $extractPath .= uplCreateFriendlyName($extractPathUserInput);
        }

        if (file_exists($extractPath) and is_dir($extractPath)) {
            $ar = cZipArchive::readExistingFiles($extractPath);
        }
        // :: OVERRIDE

        $zip = new ZipArchive();

        // try to open archive
        if (!$zip->open($file)) {
            echo ('can not open zip file!');
            return;
        }

        // check if directory already exist
        // TODO keep code DRY
        if (cZipArchive::isExtracted($extractPath)) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $file = $zip->getNameIndex($i);
                $tmpFile = str_replace('/', '', $file);
                if (cFileHandler::validateFilename($tmpFile, FALSE) && (substr($tmpFile, 0, 1) != '.') && (substr($tmpFile, 0, 1) != '_')) {
                    if (!file_exists($extractPath . '/' . $file)) {
                        $zip->extractTo($extractPath, $file);
                    }
                }
            }
        } else {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $file = $zip->getNameIndex($i);
                // remove '/' for validation -> directory names
                $tmpFile = str_replace('/', '', $file);
                if (cFileHandler::validateFilename($tmpFile, FALSE) && (substr($tmpFile, 0, 1) != '.') && (substr($tmpFile, 0, 1) != '_')) {
                    $zip->extractTo($extractPath, $file);
                }
            }
        }
        $zip->close();
    }

    /**
     *
     * @param string $zipFilePath
     * @param string $dirPath
     * @param array $filePathes
     */
    public static function createZip($zipFilePath, $dirPath, array $filePathes) {
        $zip = new ZipArchive();
        if ($zip->open($dirPath . $zipFilePath, ZipArchive::CREATE) == TRUE) {
            foreach ($filePathes as $key => $file) {
                $zip->addFile($dirPath . $file, $file);
            }
            $zip->close();
        }
    }
}
