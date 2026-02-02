<?php

/**
 * This file contains the cZipArchive util class.
 *
 * @package    Core
 * @subpackage Util
 * @author     claus.schunk@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains the functionalities to handle zip archives.
 * @author claus.schunk@4fb.de
 */
class cZipArchive
{
    /**
     * Read all files from given path excluding files which names start with a
     * dot or are not valid according to CONTENIDO standards (validateFilename()).
     *
     * @return array List of files
     * @throws cInvalidArgumentException|cException
     * @see cFileHandler::validateFilename()
     */
    public static function readExistingFiles(string $dirPath): array
    {
        // check if $dirPath is a dir
        if (!is_dir($dirPath)) {
            return [];
        }

        // try to read $dirPath
        if (false === ($handle = cDirHandler::read($dirPath))) {
            return [];
        }

        $array = [];
        foreach ($handle as $file) {
            if (cFileHandler::fileNameBeginsWithDot($file)) {
                // exclude file if name starts with a dot
                // hotfix : fileHandler returns filename '.' als valid filename
                continue;
            } elseif (!cFileHandler::validateFilename($file, false)) {
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
     */
    public static function isExtracted(string $dirPath): bool
    {
        if (!file_exists($dirPath)) {
            return false;
        } elseif (!is_dir($dirPath)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * This function contains the functionality to extract archive and overwrite
     * existing files.
     *
     * @param string $file zip file
     * @param string $extractPath extraction path
     * @param ?string $extractPathUserInput user specified extraction path
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public static function extractOverRide(string $file, string $extractPath, ?string $extractPathUserInput = NULL)
    {
        // validate user input
        if (isset($extractPathUserInput)) {
            $extractPath .= uplCreateFriendlyName($extractPathUserInput);
        }

        $zip = new ZipArchive();
        $result = $zip->open($file);
        if ($result !== true) {
            self::handleZipArchiveOpenError($result);
            return;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $file = $zip->getNameIndex($i);
            // remove '/' for validation -> directory names
            $tmpFile = str_replace('/', '', $file);
            // extract only file with valid filename
            if (self::isValidFilename($tmpFile)) {
                $zip->extractTo($extractPath, $file);
            }
        }

        $zip->close();
    }

    /**
     * This function contains the functionality to extract archive.
     *
     * @param string $file Zip file
     * @param string $extractPath Extraction path
     * @param ?string $extractPathUserInput User specified extraction path
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public static function extract(string $file, string $extractPath, ?string $extractPathUserInput = NULL)
    {
        if (isset($extractPathUserInput)) {
            // validate user input
            $extractPath .= uplCreateFriendlyName($extractPathUserInput);
        }

        $zip = new ZipArchive();
        $result = $zip->open($file);
        if ($result !== true) {
            self::handleZipArchiveOpenError($result);
            return;
        }

        // check if directory already exist
        if (self::isExtracted($extractPath)) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $file = $zip->getNameIndex($i);
                $tmpFile = str_replace('/', '', $file);
                if (self::isValidFilename($tmpFile)) {
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
                if (self::isValidFilename($tmpFile)) {
                    $zip->extractTo($extractPath, $file);
                }
            }
        }

        $zip->close();
    }

    /**
     * This function contains the functionality to create archives.
     *
     * @param string $zipFilePath File path
     * @param string $dirPath Directory path
     * @param string[] $filePaths Files to store in archive
     */
    public static function createZip(string $zipFilePath, string $dirPath, array $filePaths)
    {
        $zip = new ZipArchive();
        $result = $zip->open($dirPath . $zipFilePath, ZipArchive::CREATE);
        if ($result !== true) {
            self::handleZipArchiveOpenError($result);
            return;
        }

        foreach ($filePaths as $file) {
            $zip->addFile($dirPath . $file, $file);
        }
        $zip->close();
    }

    private static function handleZipArchiveOpenError($result)
    {
        $message = 'Can not open zip file!';
        echo $message;
        if (is_numeric($result)) {
            $message .= ' ZipArchive->open() result: ' . $result;
            cWarning($message);
        }
    }

    private static function isValidFilename(string $filename): bool
    {
        try {
            return cFileHandler::validateFilename($filename, false)
                && (cString::getPartOfString($filename, 0, 1) != '.')
                && (cString::getPartOfString($filename, 0, 1) != '_');
        } catch (cInvalidArgumentException|cException $e) {
            cWarning(__CLASS__ . ': Could not validate filename: ' . $e->getMessage());
            return false;
        }
    }
}
