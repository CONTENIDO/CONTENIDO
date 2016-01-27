<?php

/**
 * This file contains the cZipArchive util class.
 *
 * @package Core
 * @subpackage Util
 * @author claus.schunk@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains the functionalities to handle zip archives.
 *
 * @author claus.schunk@4fb.de
 */
class cZipArchive {

    /**
     * Read all files from given path excluding files which names start with a
     * dot or are not valid according to CONTENIDO standards
     * (validateFilename()).
     *
     * @see cFileHandler::validateFilename()
     * @param string $dirPath
     * @return array
     *         of files
     */
    public static function readExistingFiles($dirPath) {

        // check if $dirPath is a dir
        if (!is_dir($dirPath)) {
            return array();
        }

        // try to read $dirPath
        if (false === ($handle = cDirHandler::read($dirPath))) {
            return array();
        }

        $array = array();
        foreach ($handle as $file) {
            if (cFileHandler::fileNameBeginsWithDot($file)) {
                // exclude file if name starts with a dot
                // hotfix : fileHandler returns filename '.' als valid filename
                continue;
            } else if (!cFileHandler::validateFilename($file, false)) {
                // exclude file if name is not valid according to CONTENIDO
                // standards
                continue;
            } else {
                $array[] = $file;
            }
        }

        // return array of files
        return $array;
    }

    /**
     * This function checks if the given path already exists.
     *
     * @param string $dirPath
     * @return bool
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
     * This function contains the functionality to extract archive and overwrite
     * existing files.
     *
     * @param string $file
     *         zip file
     * @param string $extractPath
     *         extraction path
     * @param string $extractPathUserInput [optional]
     *         user specified extraction path
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
     * This function contains the functionality to extract archive.
     *
     * @param string $file
     *         zip file
     * @param string $extractPath
     *         extraction path
     * @param string $extractPathUserInput [optional]
     *         user specified extraction path
     */
    public static function extract($file, $extractPath, $extractPathUserInput = NULL) {
        if (isset($extractPathUserInput)) {

            // validate user input
            $extractPath .= uplCreateFriendlyName($extractPathUserInput);
        }

        if (file_exists($extractPath) and is_dir($extractPath)) {
            $ar = cZipArchive::readExistingFiles($extractPath);
        }
        $zip = new ZipArchive();

        // try to open archive
        if (!$zip->open($file)) {
            echo ('can not open zip file!');
            return;
        }

        // check if directory already exist
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
     * This function contains the functionality to create archives.
     *
     * @param string $zipFilePath
     *         file path
     * @param string $dirPath
     *         directory path
     * @param array $filePathes
     *         files to store in archive
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
