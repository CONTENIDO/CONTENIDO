<?php
/**
 * Functions to edit files.
 * Included in Area style,
 * js, htmltpl in Frame right_bottom.
 *
 * Contains also common file and directory related functions
 *
 * TODO: merge with cFileHandler and cDirHandler
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Willi Man, Timo Trautmann
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Function removes file meta information from database (used when a file is
 * deleted)
 *
 * @param int $iIdClient - id of client which contains this file
 * @param string $sFilename - name of corresponding file
 * @param string $sType - type of file (css, js or templates)
 * @param cDb $oDb - CONTENIDO database object
 */
function removeFileInformation($iIdClient, $sFilename, $sType, $oDb) {
    global $cfg;

    if (!isset($oDb) || !is_object($oDb)) {
        $oDb = cRegistry::getDb();
    }

    $iIdClient = cSecurity::toInteger($iIdClient);
    $sFilename = cSecurity::filter((string) $sFilename, $oDb);
    $sType = cSecurity::filter((string) $sType, $oDb);

    $sSql = "DELETE FROM `" . $cfg["tab"]["file_information"] . "` WHERE idclient = $iIdClient AND
            filename = '$sFilename' AND type = '$sType';";
    $oDb->query($sSql);
    $oDb->free();
}

/**
 * Function returns file meta information from database (used when files were
 * versionned or description is displayed)
 *
 * @param int $iIdClient - id of client which contains this file
 * @param string $sFilename - name of corresponding file
 * @param string $sType - type of file (css, js or templates)
 * @param cDb $oDb - CONTENIDO database object
 * @return array Indexes:
 *         idsfi - Primary key of database record
 *         created - Datetime when file was created
 *         lastmodified - Datetime when file was last modified
 *         author - Author of file (CONTENIDO Backend User)
 *         modifiedby - Last modifier of file (CONTENIDO Backend User)
 *         description - Description which was inserted for this file
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
    $sSql = "SELECT * FROM `" . $cfg["tab"]["file_information"] . "` WHERE idclient = $iIdClient AND
            filename = '$sFilename' AND type = '$sType';";
    $oDb->query($sSql);
    if ($oDb->numRows() > 0) {
        $oDb->nextRecord();
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
 * Function updates file meta information (used when files were created or
 * edited).
 * It creates new database record for file meta informations if database record
 * does
 * not exist. Otherwise, existing record will be updated
 *
 * @param int $iIdClient - id of client which contains this file
 * @param string $sFilename - name of corresponding file
 * @param string $sType - type of file (css, js or templates)
 * @param string $sAuthor - author of file
 * @param string $sDescription - description of file
 * @param cDb $oDb - CONTENIDO database object
 * @param string $sFilenameNew - new filename if filename was changed (optional)
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

    $sSql = "SELECT * from `" . $cfg["tab"]["file_information"] . "` WHERE idclient = $iIdClient AND
            filename = '$sFilename' AND type = '$sType';";
    $oDb->query($sSql);
    if ($oDb->numRows() == 0) {
        // $iNextId = $oDb->nextid('con_style_file_information');
        $sSql = "INSERT INTO `" . $cfg["tab"]["file_information"] . "` (
                    `idclient` ,
                    `type` ,
                    `filename` ,
                    `created` ,
                    `lastmodified` ,
                    `author` ,
                    `modifiedby` ,
                    `description`)
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
 * Returns the filetype (extension).
 *
 * @param string $filename The file to get the type
 * @return string Filetype
 */
function getFileType($filename) {
    return cFileHandler::getExtension($filename);
}

/**
 * Returns the size of a directory.
 * AKA the combined filesizes of all files within it.
 * Note that this function uses filesize(). There could be problems with files
 * that are larger than 2GiB
 *
 * @param string $sDirectory The directory
 * @param bool $bRecursive true if all the subdirectories should be included in the
 *            calculation
 * @return bool int false in case of an error or the size
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
 * @param string $sDirectory
 * @param bool $bRecursive
 * @return bool array of found files (full path and name) or false
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
        if (is_dir($sDirectory)) {
            if (($hDirHandle = opendir($sDirectory)) !== false) {
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
        }
        array_push($closedDirs, $sDirectory);
    }

    return $aFiles;
}

/**
 * Copies source directory to destination directory.
 *
 * @param string $sourcePath
 * @param string $destinationPath
 * @param int $mode Octal representation of file mode (0644, 0750, etc.)
 * @param array $options Some additional options as follows
 *        <pre>
 *        $options['force_overwrite'] (bool) Flag to overwrite existing
 *            destination file, default value is false
 *        </pre>
 */
function recursiveCopy($sourcePath, $destinationPath, $mode = 0777, array $options = array()) {
    if (!is_dir($destinationPath)) {
        mkdir($destinationPath, $mode);
    }

    $forceOverwrite = (isset($options['force_overwrite'])) ? (bool) $options['force_overwrite'] : false;
    $oldPath = getcwd();

    if (is_dir($sourcePath)) {
        chdir($sourcePath);
        $myhandle = opendir('.');

        while (($file = readdir($myhandle)) !== false) {
            if ($file != '.' && $file != '..') {
                if (is_dir($file)) {
                    // Copy directory
                    recursiveCopy($sourcePath . $file . '/', $destinationPath . $file . '/', $mode, $options);
                    chdir($sourcePath);
                } elseif (cFileHandler::exists($sourcePath . $file)) {
                    // Copy file
                    if (cFileHandler::exists($destinationPath . $file)) {
                        if ($forceOverwrite) {
                            copy($sourcePath . $file, $destinationPath . $file);
                        }
                    } else {
                        copy($sourcePath . $file, $destinationPath . $file);
                    }
                }
            }
        }
        closedir($myhandle);
    }

    chdir($oldPath);
}
