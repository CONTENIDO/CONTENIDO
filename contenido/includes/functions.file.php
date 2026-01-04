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
 * @package    Core
 * @subpackage Backend
 * @author     Willi Man
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @deprecated [2015-05-21] This method is no longer supported (no replacement)
 */
function removeFileInformation($iIdClient, $sFilename, $sType, $oDb)
{
    global $cfg;

    cDeprecated('This method is deprecated and is not needed any longer');

    if (!isset($oDb) || !is_object($oDb)) {
        $oDb = cRegistry::getDb();
    }

    $iIdClient = cSecurity::toInteger($iIdClient);
    $sFilename = cSecurity::filter((string)$sFilename, $oDb);
    $sType = cSecurity::filter((string)$sType, $oDb);

    $sSql = "DELETE FROM `" . cDb::getTableName('file_information') . "` WHERE idclient = $iIdClient AND
            filename = '$sFilename' AND type = '$sType';";
    $oDb->query($sSql);
    $oDb->free();
}

/**
 * @deprecated [2015-05-21] This method is no longer supported (no replacement)
 */
function getFileInformation($iIdClient, $sFilename, $sType, $oDb)
{
    global $cfg;

    cDeprecated('This method is deprecated and is not needed any longer');

    if (!isset($oDb) || !is_object($oDb)) {
        $oDb = cRegistry::getDb();
    }

    $iIdClient = cSecurity::toInteger($iIdClient);
    $sFilename = cSecurity::filter((string)$sFilename, $oDb);
    $sType = cSecurity::filter((string)$sType, $oDb);

    $sSql = "SELECT * FROM `" . cDb::getTableName('file_information') . "` WHERE idclient = $iIdClient AND
            filename = '$sFilename' AND type = '$sType';";
    $oDb->query($sSql);

    $aFileInformation = [];
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
 * @deprecated [2015-05-21] This method is no longer supported (no replacement)
 */
function updateFileInformation($iIdClient, $sFilename, $sType, $sAuthor, $sDescription, $oDb, $sFilenameNew = '')
{
    global $cfg;

    cDeprecated('This method is deprecated and is not needed any longer');

    if (!isset($oDb) || !is_object($oDb)) {
        $oDb = cRegistry::getDb();
    }

    if ($sFilenameNew == '') {
        $sFilenameNew = $sFilename;
    }

    $iIdClient = cSecurity::toInteger($iIdClient);
    $sFilename = cSecurity::filter((string)$sFilename, $oDb);
    $sType = cSecurity::filter((string)$sType, $oDb);
    $sDescription = cSecurity::filter((string)stripslashes($sDescription), $oDb);
    $sAuthor = cSecurity::filter((string)$sAuthor, $oDb);

    $sSql = "SELECT * from `" . cDb::getTableName('file_information') . "` WHERE idclient = $iIdClient AND
            filename = '$sFilename' AND type = '$sType';";
    $oDb->query($sSql);
    if ($oDb->numRows() == 0) {
        // $iNextId = $oDb->nextid('con_style_file_information');
        $sSql = "INSERT INTO `" . cDb::getTableName('file_information') . "` (
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
        $sSql = "UPDATE `" . cDb::getTableName('file_information') . "` SET `lastmodified` = NOW(),
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
 * @deprecated [2015-05-21] use {@see cFileHandler::getExtension()} instead
 *
 */
function getFileType($filename)
{
    cDeprecated('This method is deprecated and is not needed any longer');
    return cFileHandler::getExtension($filename);
}

/**
 * @deprecated [2015-05-21] use {@see cDirHandler::getDirectorySize()} instead
 */
function getDirectorySize($sDirectory, $bRecursive = false)
{
    cDeprecated('This method is deprecated and is not needed any longer');
    return cDirHandler::getDirectorySize($sDirectory, $bRecursive);
}

/**
 * @deprecated [2015-05-21] use {@see cDirHandler::read()} instead
 */
function scanDirectory($sDirectory, $bRecursive = false)
{
    cDeprecated('This method is deprecated and is not needed any longer');
    return cDirHandler::read($sDirectory, $bRecursive, false, true);
}

/**
 * @deprecated [2015-05-21] use {@see cDirHandler::recursiveCopy()} instead
 */
function recursiveCopy($sourcePath, $destinationPath, $mode = null, array $options = [])
{
    cDeprecated('This method is deprecated and is not needed any longer');

    return cDirHandler::recursiveCopy($sourcePath, $destinationPath, $mode);
}
