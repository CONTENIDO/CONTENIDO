<?php
/**
 * This file contains various helper functions to read specific values needed for setup checks.
 *
 * @package    Setup
 * @subpackage Helper_Environment
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

function isWindows()
{
    if (strtolower(substr(PHP_OS, 0, 3)) == "win") {
        return true;
    } else {
        return false;
    }
}

function getServerUID()
{
    if (function_exists("posix_getuid")) {
        return posix_getuid();
    }

    $sFilename = md5(mt_rand()) . ".txt";

    if (is_writeable(".")) {
        cFileHandler::create($sFilename, "test");
        $iUserId = fileowner($sFilename);
        cFileHandler::remove($sFilename);

        return ($iUserId);
    } else {
        if (is_writeable("/tmp/")) {
            cFileHandler::create("/tmp/".$sFilename, "w");
            $iUserId = fileowner("/tmp/".$sFilename);
            cFileHandler::remove("/tmp/".$sFilename);

            return ($iUserId);
        }
        return false;
    }
}

function getServerGID() {
    if (function_exists("posix_getgid")) {
        return posix_getgid();
    }

    $sFilename = md5(mt_rand()) . ".txt";

    if (is_writeable(".")) {
        cFileHandler::create($sFilename, "test");
        $iUserId = filegroup($sFilename);
        cFileHandler::remove($sFilename);

        return ($iUserId);
    } else {
        return false;
    }
}

function getUsernameByUID($iUid)
{
    if (function_exists("posix_getpwuid")) {
        $aInfo = posix_getpwuid($iUid);
        return ($aInfo["name"]);
    } else {
        return false;
    }
}

function getGroupnameByGID($iGid)
{
    if (function_exists("posix_getgrgid")) {
        $aInfo = posix_getgrgid($iGid);
        return ($aInfo["name"]);
    } else {
        return false;
    }
}

?>