<?php

/**
 * This file contains various helper functions to read specific values needed for setup checks.
 *
 * @package    Setup
 * @subpackage Helper_Filesystem
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');


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
    return cFileHandler::typeOwnerInfo(cSecurity::toString($sFilename));
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
        if (cString::findFirstOccurrenceCI($sCurrentDirectory, $entry)) {
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
