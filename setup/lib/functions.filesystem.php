<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO setup
 * @version    0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *
 *   $Id$:
 * }}
 *
 */


if (!defined('CON_FRAMEWORK')) {
     die('Illegal call');
}


define("C_PREDICT_SUFFICIENT",            1);
define("C_PREDICT_NOTPREDICTABLE",        2);
define("C_PREDICT_CHANGEPERM_SAMEOWNER",  3);
define("C_PREDICT_CHANGEPERM_SAMEGROUP",  4);
define("C_PREDICT_CHANGEPERM_OTHERS",     5);
define("C_PREDICT_CHANGEUSER",            6);
define("C_PREDICT_CHANGEGROUP",           7);
define("C_PREDICT_WINDOWS",               8);

define("E_BASEDIR_NORESTRICTION",         1);
define("E_BASEDIR_DOTRESTRICTION",        2);
define("E_BASEDIR_RESTRICTIONSUFFICIENT", 3);
define("E_BASEDIR_INCOMPATIBLE",          4);

/**
 * Checks if a specific file is writeable. Includes a PHP 4.0.4
 * workaround where is_writable doesn't return a value of type
 * boolean. Also clears the stat cache and checks if the file
 * exists.
 *
 * @param $file string    Path to the file, accepts absolute and relative files
 * @return boolean true if the file exists and is writeable, false otherwise
 */
function isWriteable($file)
{
    clearstatcache();
    if (!cFileHandler::exists($file)) {
        return false;
    }

    $bStatus = is_writable($file);
    // PHP 4.0.4 workaround
    settype($bStatus, "boolean");

    return $bStatus;
}

/**
 * Checks if a file is readable.
 *
 * @param $file string Path to the file, accepts absolute and relative files
 * @return boolean true if the file exists and is readable, false otherwise
 */
function isReadable($file)
{
    return is_readable($file);
}

function canReadFile($sFilename)
{
    if (isReadable(dirname($sFilename))) {
        if (isReadable($sFilename)) {
            return (bool) cFileHandler::read($sFilename);
        }
    }
    return false;
}

function canWriteFile($sFilename)
{
    // Check dir perms, create a new file read it and delete it
    if (is_dir($sFilename)) {

        $sRandFilenamePath = $sFilename;
        $i = 0;

        // Try to find a random filename for write test, which does not exist
        while (cFileHandler::exists($sRandFilenamePath) &&  $i < 100) {
            $sRandFilename = 'con_test'.rand(0,1000000000).'con_test';
            $sRandFilenamePath = '';

            if ($sFilename{strlen($sFilename)-1} == '/') {
                $sRandFilenamePath = $sFilename.$sRandFilename;
            } else {
                $sRandFilenamePath = $sFilename.'/'.$sRandFilename;
            }

            $i++;
        }

        // Tthere is no file name which does not exist, exit after 100 trials
        if ($i == 100) {
            return false;
        }

        // Ignore errors in case isWriteable() returns a wrong information
        cFileHandler::create($sRandFilenamePath, "test");
        return cFileHandler::remove($sRandFilenamePath);
    }

    if (isWriteable(dirname($sFilename))) {
        if (cFileHandler::exists($sFilename)) {
            if (!isWriteable($sFilename)) {
                return false;
            } else {
                return true;
            }
        }

        // Ignore errors in case isWriteable() returns a wrong information
        cFileHandler::create($sFilename, "test");
        return cFileHandler::remove($sFilename);
    } else {
        if (cFileHandler::exists($sFilename)) {
            if (!isWriteable($sFilename)) {
                return false;
            } else {
                return true;
            }
        }
    }
}

function canDeleteFile($sFilename)
{
    if (isWriteable($sFilename)) {
        unlink($sFilename);

        if (cFileHandler::exists($sFilename)) {
            return false;
        } else {
            return true;
        }
    } else {
        return false;
    }
}

function getFileInfo($sFilename)
{
    if (!cFileHandler::exists($sFilename)) {
        return false;
    }

    $oiFilePermissions = fileperms($sFilename);
    if ($oiFilePermissions === false) {
        return false;
    }

    switch (true) {
        case (($oiFilePermissions & 0xC000) == 0xC000):
            $info = 's';
            $type = "socket";
            break;
        case (($oiFilePermissions & 0xA000) == 0xA000):
            $info = 'l';
            $type = "symbolic link";
            break;
        case (($oiFilePermissions & 0x8000) == 0x8000):
            $info = '-';
            $type = "regular file";
            break;
        case (($oiFilePermissions & 0x6000) == 0x6000):
            $info = 'b';
            $type = "block special";
            break;
        case (($oiFilePermissions & 0x4000) == 0x4000):
            $info = 'd';
            $type = "directory";
            break;
        case (($oiFilePermissions & 0x2000) == 0x2000):
            $info = 'c';
            $type = "character special";
            break;
        case (($oiFilePermissions & 0x1000) == 0x1000):
            $info = 'p';
            $type = "FIFO pipe";
            break;
        default:
            $info = "u";
            $type = "Unknown";
            break;
    }

    $aFileinfo = array();
    $aFileinfo["info"] = $info;
    $aFileinfo["type"] = $type;
    $aFileinfo["owner"]["read"]   = ($oiFilePermissions & 0x0100) ? true : false;
    $aFileinfo["owner"]["write"]  = ($oiFilePermissions & 0x0080) ? true : false;
    $aFileinfo["group"]["read"]   = ($oiFilePermissions & 0x0020) ? true : false;
    $aFileinfo["group"]["write"]  = ($oiFilePermissions & 0x0010) ? true : false;
    $aFileinfo["others"]["read"]  = ($oiFilePermissions & 0x0004) ? true : false;
    $aFileinfo["others"]["write"] = ($oiFilePermissions & 0x0002) ? true : false;
    $aFileinfo["owner"]["id"]     = fileowner($sFilename);
    $aFileinfo["group"]["id"]     = filegroup($sFilename);
    return ($aFileinfo);
}

function checkOpenBasedirCompatibility()
{
    $value = getPHPIniSetting("open_basedir");

    if (isWindows()) {
        $aBasedirEntries = explode(";", $value);
    } else {
        $aBasedirEntries = explode(":", $value);
    }

    if (count($aBasedirEntries) == 1 && $aBasedirEntries[0] == $value) {
        return E_BASEDIR_NORESTRICTION;
    }

    if (in_array(".", $aBasedirEntries) && count($aBasedirEntries) == 1) {
        return E_BASEDIR_DOTRESTRICTION;
    }

    $sCurrentDirectory = getcwd();

    foreach ($aBasedirEntries as $entry) {
        if (stristr($sCurrentDirectory, $entry)) {
            return E_BASEDIR_RESTRICTIONSUFFICIENT;
        }
    }

    return E_BASEDIR_INCOMPATIBLE;
}

function predictCorrectFilepermissions ($file) {
    // Check if the system is a windows system. If yes, we can't predict anything.
    if (isWindows()) {
        return C_PREDICT_WINDOWS;
    }

    // Check if the file is read- and writeable. If yes, we don't need to do any further checks.
    if (isWriteable($file) && isReadable($file)) {
        return C_PREDICT_SUFFICIENT;
    }

    // If we can't find out the web server UID, we cannot predict the correct mask.
    $iServerUID = getServerUID();
    if ($iServerUID === false) {
        return C_PREDICT_NOTPREDICTABLE;
    }

    // If we can't find out the web server GID, we cannot predict the correct mask.
    $iServerGID = getServerGID();
    if ($iServerGID === false) {
        return C_PREDICT_NOTPREDICTABLE;
    }

    $aFilePermissions = getFileInfo($file);

    if (getSafeModeStatus()) {
        // SAFE-Mode related checks
        if ($iServerUID == $aFilePermissions["owner"]["id"]) {
            return C_PREDICT_CHANGEPERM_SAMEOWNER;
        }

        if (getSafeModeGidStatus()) {
            // SAFE-Mode GID related checks
            if ($iServerGID == $aFilePermissions["group"]["id"]) {
                return C_PREDICT_CHANGEPERM_SAMEGROUP;
            }

            return C_PREDICT_CHANGEGROUP;
        }

    } else {
        // Regular checks
        if ($iServerUID == $aFilePermissions["owner"]["id"]) {
            return C_PREDICT_CHANGEPERM_SAMEOWNER;
        }

        if ($iServerGID == $aFilePermissions["group"]["id"]) {
            return C_PREDICT_CHANGEPERM_SAMEGROUP;
        }

        return C_PREDICT_CHANGEPERM_OTHERS;
    }
}

?>