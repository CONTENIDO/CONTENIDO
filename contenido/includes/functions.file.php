<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Functions to edit files. Included in Area style,
 * js, htmltpl in Frame right_bottom.
 *
 * Contains also common file and directory related functions
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.5
 * @author     Willi Man
 * @copyright  four for business AG <info@contenido.org>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release >= 4.6
 *
 * {@internal
 *   created 2004-07-13
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Function removes file meta information from database (used when a file is deleted)
 *
 * @author Timo Trautmann
 * @param int $iIdClient - id of client which contains this file
 * @param string  $sFilename - name of corresponding file
 * @param string  $sType - type of file (css, js or templates)
 * @param DB_Contenido  $oDb - CONTENIDO database object
 */
function removeFileInformation($iIdClient, $sFilename, $sType, $oDb) {
    global $cfg;

    if (!isset($oDb) || !is_object($oDb)) {
        $oDb = cRegistry::getDb();
    }

    $iIdClient = cSecurity::toInteger($iIdClient);
    $sFilename = cSecurity::filter((string) $sFilename, $oDb);
    $sType = cSecurity::filter((string) $sType, $oDb);

    $sSql = "DELETE FROM `" . $cfg["tab"]["file_information"] . "` WHERE idclient=$iIdClient AND
                                                            filename='$sFilename' AND
                                                            type='$sType';";
    $oDb->query($sSql);
    $oDb->free();
}

/**
 * Function returns file meta information from database (used when files were versionned or description is displayed)
 *
 * @author Timo Trautmann
 * @param int $iIdClient - id of client which contains this file
 * @param string  $sFilename - name of corresponding file
 * @param string  $sType - type of file (css, js or templates)
 * @param DB_Contenido  $oDb - CONTENIDO database object
 * @return array   Indexes:
 *                           idsfi - Primary key of database record
 *                           created - Datetime when file was created
 *                           lastmodified - Datetime when file was last modified
 *                           author - Author of file (CONTENIDO Backend User)
 *                           modifiedby - Last modifier of file (CONTENIDO Backend User)
 *                           description - Description which was inserted for this file
 *
 */
function getFileInformation($iIdClient, $sFilename, $sType, $oDb) {
    global $cfg;

    if (!isset($oDb) || !is_object($oDb)) {
        $oDb = cRegistry::getDb();
    }

    $iIdClient = cSecurity::toInteger($iIdClient);
    $sFilename = cSecurity::filter((string) $sFilename, $oDb);
    $sType = cSecurity::filter((string) $sType, $oDb);

    $aFileInformation = array();
    $sSql = "SELECT * FROM `" . $cfg["tab"]["file_information"] . "` WHERE idclient=$iIdClient AND
                                                            filename='$sFilename' AND
                                                            type='$sType';";
    $oDb->query($sSql);
    if ($oDb->num_rows() > 0) {
        $oDb->next_record();
        $aFileInformation['idsfi'] = $oDb->f('idsfi');
        $aFileInformation['created'] = $oDb->f('created');
        $aFileInformation['lastmodified'] = $oDb->f('lastmodified');
        $aFileInformation['author'] = cSecurity::unFilter($oDb->f('author'));
        $aFileInformation['modifiedby'] = $oDb->f('modifiedby');
        $aFileInformation['description'] = cSecurity::unFilter($oDb->f('description'));
    }
    $oDb->free();

    return $aFileInformation;
}

/**
 * Function updates file meta information (used when files were created or edited).
 * It creates new database record for file meta informations if database record does
 * not exist. Otherwise, existing record will be updated
 *
 * @author Timo Trautmann
 * @param int $iIdClient - id of client which contains this file
 * @param string  $sFilename - name of corresponding file
 * @param string  $sType - type of file (css, js or templates)
 * @param string  $sAuthor - author of file
 * @param string  $sDescription - description of file
 * @param DB_Contenido  $oDb - CONTENIDO database object
 * @param string  $sFilenameNew - new filename if filename was changed (optional)
 */
function updateFileInformation($iIdClient, $sFilename, $sType, $sAuthor, $sDescription, $oDb, $sFilenameNew = '') {
    global $cfg;

    if (!isset($oDb) || !is_object($oDb)) {
        $oDb = cRegistry::getDb();
    }

    if ($sFilenameNew == '') {
        $sFilenameNew = $sFilename;
    }

    $iIdClient = cSecurity::toInteger($iIdClient);
    $sFilename = cSecurity::filter((string) $sFilename, $oDb);
    $sType = cSecurity::filter((string) $sType, $oDb);
    $sDescription = cSecurity::filter((string) stripslashes($sDescription), $oDb);
    $sAuthor = cSecurity::filter((string) $sAuthor, $oDb);

    $sSql = "SELECT * from `" . $cfg["tab"]["file_information"] . "` WHERE idclient=$iIdClient AND
                                                            filename='$sFilename' AND
                                                            type='$sType';";
    $oDb->query($sSql);
    if ($oDb->num_rows() == 0) {
        // $iNextId = $oDb->nextid('con_style_file_information');
        $sSql = "INSERT INTO `" . $cfg["tab"]["file_information"] . "` (
                                                            `idclient` ,
                                                            `type` ,
                                                            `filename` ,
                                                            `created` ,
                                                            `lastmodified` ,
                                                            `author` ,
                                                            `modifiedby` ,
                                                            `description` )
                                                        VALUES (
                                                            $iIdClient,
                                                            '$sType',
                                                            '$sFilenameNew',
                                                            NOW(),
                                                            '0000-00-00 00:00:00',
                                                            '$sAuthor',
                                                            '',
                                                            '$sDescription'
                                                        );";
    } else {
        $sSql = "UPDATE `" . $cfg["tab"]["file_information"] . "` SET `lastmodified` = NOW(),
                                                         `modifiedby` = '$sAuthor',
                                                         `description` = '$sDescription',
                                                         `filename` = '$sFilenameNew'

                                                         WHERE idclient=$iIdClient AND
                                                               filename='$sFilename' AND
                                                               type='$sType';";
    }

    $oDb->free();
    $oDb->query($sSql);
    $oDb->free();
}

/**
 * Writes passed data into a file using binary mode.
 *
 * Exits the script, if file could not opened!
 *
 * @deprecated [2012-07-04] These functions are now in cFileHandler
 *
 * @param   string  $filename  The file to write the content
 * @param   string  $sCode     File content to write
 * @param   string  $path      Path to the file
 * @return  (string|void)      Either content of file o nothing
 */
function fileEdit($filename, $sCode, $path) {
    global $notification;

    cDeprecated("This function was replaced by cFileHandler");

    // FIXME: fileValidateFilename does also the validation but display another message!
    if (strlen(trim($filename)) == 0) {
        $notification->displayNotification("error", i18n("Please insert file name."));
        return false;
    }

    fileValidateFilename($filename, true);

    if (cFileHandler::write($path . $filename, $sCode)) {
        return cFileHandler::read($path . $filename);
    } else {
        $notification->displayNotification("error", sprintf(i18n("%s is not writable"), $path . $filename));
        exit;
    }
}

/**
 * Reads content of file into memory using binary mode and returns it back.
 *
 * Exits the script, if file could not opened!
 *
 * @deprecated [2012-07-04] These functions are now in cFileHandler
 *
 * @param   string  $filename  The file to get the content
 * @param   string  $path      Path to the file
 * @return  (string|void)      Either content of file o nothing
 */
function getFileContent($filename, $path) {
    global $notification;

    cDeprecated("This function was replaced by cFileHandler");

    $ret = "";
    if (($ret = cFileHandler::read($path . $filename)) === false) {
        $notification->displayNotification("error", sprintf(i18n("Can not open file %s"), $path . $filename));
        exit;
    }

    return $ret;
}

/**
 * Returns the filetype (extension).
 *
 * @param   string  $filename  The file to get the type
 * @return  string  Filetype
 */
function getFileType($filename) {
    return cFileHandler::getExtension($filename);
}

/**
 * Creates a file.
 *
 * Exits the script, if filename is not valid or creation (touch or chmod) fails!
 *
 * @deprecated [2012-07-04] These functions are now in cFileHandler
 *
 * @param   string  $filename  The file to create
 * @param   string  $path      Path to the file
 * @return  (void|bool)  Either true on success or nothing
 */
function createFile($filename, $path) {
    global $notification;

    cDeprecated("This function was replaced by cFileHandler");

    fileValidateFilename($filename, true);

    // create the file
    if (cFileHandler::create($path . $filename)) {
        // change file access permission
        if (cFileHandler::chmod($path . $filename, 0777)) {
            return true;
        } else {
            $notification->displayNotification("error", $path . $filename . " " . i18n("Unable to change file access permission."));
            exit;
        }
    } else {
        $notification->displayNotification("error", sprintf(i18n("Unable to create file %s"), $path . $filename));
        exit;
    }
}

/**
 * Renames an existing file.
 *
 * Exits the script, if new filename is not valid or renaming fails!
 *
 * @deprecated [2012-07-04] These functions are now in cFileHandler
 *
 * @param   string  $sOldFile  Old filename
 * @param   string  $sNewFile  New filename
 * @param   string  $path      Path to the file
 * @return  (void|string)  Either new filename or nothing
 */
function renameFile($sOldFile, $sNewFile, $path) {
    global $notification;

    cDeprecated("This function was replaced by cFileHandler");

    fileValidateFilename($sNewFile, true);
    if (cFileHandler::rename($path . $sOldFile, $sNewFile)) {
        return $sNewFile;
    } else {
        $notification->displayNotification("error", sprintf(i18n("Can not rename file %s"), $path . $sOldFile));
        exit;
    }
}

/**
 * Validates passed filename. Filename can contain alphanumeric characters, dot, underscore or a hyphen.
 *
 * Exits the script, if second parameter is set to true and validation fails!
 *
 * @param   string  $filename  The filename to validate
 * @param   bool    $notifyAndExitOnFailure  Flag to display notification and to exit further script
 *                                           execution, ifd validation fails
 * @return  (void|bool)  Either validation result or nothing (depends on second parameter)
 */
function fileValidateFilename($filename, $notifyAndExitOnFailure = true) {
    global $notification;

    if (preg_match('/[^a-z0-9._-]/i', $filename)) {
        // validation failure...
        if ($notifyAndExitOnFailure == true) {
            // display notification and exit
            $notification->displayNotification('error', i18n('Wrong file name.'));
            exit;
        }
        return false;
    }
    return true;
}

/**
 * Returns MIME content-type for a file.
 *
 * @deprecated [2012-07-04] These functions are now in cFileHandler
 *
 * @param  string  $file  Full path and name of file
 * @return string|null  MIME content-type on success, or null
 */
function fileGetMimeContentType($file) {
    cDeprecated("This function was replaced by cFileHandler");

    $ret = cFileHandler::info($file);
    return $ret['mime'];
}

/**
 * Returns the size of a directory. AKA the combined filesizes of all files within it.
 * Note that this function uses filesize(). There could be problems with files that are larger than 2GiB
 *
 * @param string The directory
 * @param bool true if all the subdirectories should be included in the calculation
 * @return bool|int Returns false in case of an error or the size
 */
function getDirectorySize($sDirectory, $bRecursive = false) {
    $ret = 0;
    $files = scanDirectory($sDirectory, $bRecursive);
    if ($files === false) {
        return false;
    }

    foreach ($files as $file) {
        $temp = cFileHandler::info($file);
        $ret += $temp['size'];
    }

    return $ret;
}

/**
 * Scans passed directory and collects all found files
 *
 * @param   string  $sDirectory
 * @param   bool    $bRecursive
 * @return  bool|array  List of found files (full path and name) or false
 */
function scanDirectory($sDirectory, $bRecursive = false) {
    if (substr($sDirectory, strlen($sDirectory) - 1, 1) == '/') {
        $sDirectory = substr($sDirectory, 0, strlen($sDirectory) - 1);
    }

    if (!is_dir($sDirectory)) {
        return false;
    }

    $aFiles = array();
    $openDirs = array();
    $closedDirs = array();
    array_push($openDirs, $sDirectory);

    while (count(($openDirs)) >= 1) {
        $sDirectory = array_pop($openDirs);
        if ($hDirHandle = opendir($sDirectory)) {
            while (($sFile = readdir($hDirHandle)) !== false) {
                if ($sFile != '.' && $sFile != '..') {
                    $sFullpathFile = $sDirectory . '/' . $sFile;
                    if (is_file($sFullpathFile) && cFileHandler::readable($sFullpathFile)) {
                        array_push($aFiles, $sFullpathFile);
                    } elseif (is_dir($sFullpathFile) && $bRecursive == true) {
                        if (!in_array($sFullpathFile, $closedDirs)) {
                            array_push($openDirs, $sFullpathFile);
                        }
                    }
                }
            }
            closedir($hDirHandle);
        }
        array_push($closedDirs, $sDirectory);
    }

    return $aFiles;
}

/**
 * Copies source directory to destination directory.
 * @param  string  $sourcePath
 * @param  string  $destinationPath
 * @param  int     $mode  Octal representation of file mode (0644, 0750, etc.)
 */
function recursiveCopy($sourcePath, $destinationPath, $mode = 0777) {
    mkdir($destinationPath, $mode);
    $oldPath = getcwd();

    if (is_dir($sourcePath)) {
        chdir($sourcePath);
        $myhandle = opendir('.');

        while (($file = readdir($myhandle)) !== false) {
            if ($file != '.' && $file != '..') {
                if (is_dir($file)) {
                    recursiveCopy($sourcePath . $file . '/', $destinationPath . $file . '/');
                    chdir($sourcePath);
                } elseif (cFileHandler::exists($file)) {
                    copy($sourcePath . $file, $destinationPath . $file);
                }
            }
        }
        closedir($myhandle);
    }

    chdir($oldPath);
}

?>