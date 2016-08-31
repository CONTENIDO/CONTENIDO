<?php
/**
 * This file contains various helper functions to read specific values needed for setup checks.
 *
 * @package    Setup
 * @subpackage Helper_Filesystem
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

define('CON_PREDICT_SUFFICIENT', 1);

define('CON_PREDICT_NOTPREDICTABLE', 2);

define('CON_PREDICT_CHANGEPERM_SAMEOWNER', 3);

define('CON_PREDICT_CHANGEPERM_SAMEGROUP', 4);

define('CON_PREDICT_CHANGEPERM_OTHERS', 5);

define('CON_PREDICT_CHANGEUSER', 6);

define('CON_PREDICT_CHANGEGROUP', 7);

define('CON_PREDICT_WINDOWS', 8);

define('CON_BASEDIR_NORESTRICTION', 1);

define('CON_BASEDIR_DOTRESTRICTION', 2);

define('CON_BASEDIR_RESTRICTIONSUFFICIENT', 3);

define('CON_BASEDIR_INCOMPATIBLE', 4);

function canWriteFile($filename) {
    clearstatcache();
    if (is_file($filename)) {
        return is_writable($filename);
    } else {
        return is_writable(dirname($filename));
    }
}

function canWriteDir($dirname) {
    clearstatcache();
    return is_dir($dirname) && is_writable($dirname);
}

function getFileInfo($sFilename) {
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
    $aFileinfo["owner"]["read"] = ($oiFilePermissions & 0x0100) ? true : false;
    $aFileinfo["owner"]["write"] = ($oiFilePermissions & 0x0080) ? true : false;
    $aFileinfo["group"]["read"] = ($oiFilePermissions & 0x0020) ? true : false;
    $aFileinfo["group"]["write"] = ($oiFilePermissions & 0x0010) ? true : false;
    $aFileinfo["others"]["read"] = ($oiFilePermissions & 0x0004) ? true : false;
    $aFileinfo["others"]["write"] = ($oiFilePermissions & 0x0002) ? true : false;
    $aFileinfo["owner"]["id"] = fileowner($sFilename);
    $aFileinfo["group"]["id"] = filegroup($sFilename);
    return ($aFileinfo);
}

function checkOpenBasedirCompatibility() {
    $value = getPHPIniSetting("open_basedir");

    if (isWindows()) {
        $aBasedirEntries = explode(";", $value);
    } else {
        $aBasedirEntries = explode(":", $value);
    }

    if (count($aBasedirEntries) == 1 && $aBasedirEntries[0] == $value) {
        return CON_BASEDIR_NORESTRICTION;
    }

    if (in_array(".", $aBasedirEntries) && count($aBasedirEntries) == 1) {
        return CON_BASEDIR_DOTRESTRICTION;
    }

    $sCurrentDirectory = getcwd();

    foreach ($aBasedirEntries as $entry) {
        if (stristr($sCurrentDirectory, $entry)) {
            return CON_BASEDIR_RESTRICTIONSUFFICIENT;
        }
    }

    return CON_BASEDIR_INCOMPATIBLE;
}

function predictCorrectFilepermissions($file) {
    // Check if the system is a windows system. If yes, we can't predict
    // anything.
    if (isWindows()) {
        return CON_PREDICT_WINDOWS;
    }

    // Check if the file is read- and writeable. If yes, we don't need to do any
    // further checks.
    if (is_writable($file) && is_readable($file)) {
        return CON_PREDICT_SUFFICIENT;
    }

    // If we can't find out the web server UID, we cannot predict the correct
    // mask.
    $iServerUID = getServerUID();
    if ($iServerUID === false) {
        return CON_PREDICT_NOTPREDICTABLE;
    }

    // If we can't find out the web server GID, we cannot predict the correct
    // mask.
    $iServerGID = getServerGID();
    if ($iServerGID === false) {
        return CON_PREDICT_NOTPREDICTABLE;
    }

    $aFilePermissions = getFileInfo($file);

    if (getSafeModeStatus()) {
        // SAFE-Mode related checks
        if ($iServerUID == $aFilePermissions["owner"]["id"]) {
            return CON_PREDICT_CHANGEPERM_SAMEOWNER;
        }

        if (getSafeModeGidStatus()) {
            // SAFE-Mode GID related checks
            if ($iServerGID == $aFilePermissions["group"]["id"]) {
                return CON_PREDICT_CHANGEPERM_SAMEGROUP;
            }

            return CON_PREDICT_CHANGEGROUP;
        }
    } else {
        // Regular checks
        if ($iServerUID == $aFilePermissions["owner"]["id"]) {
            return CON_PREDICT_CHANGEPERM_SAMEOWNER;
        }

        if ($iServerGID == $aFilePermissions["group"]["id"]) {
            return CON_PREDICT_CHANGEPERM_SAMEGROUP;
        }

        return CON_PREDICT_CHANGEPERM_OTHERS;
    }

    return CON_PREDICT_NOTPREDICTABLE;
}