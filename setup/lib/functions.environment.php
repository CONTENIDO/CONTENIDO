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

    if (isWriteable(".")) {
        $fp = fopen($sFilename, "w");
        fwrite($fp, "test");
        fclose($fp);
        $iUserId = fileowner($sFilename);
        unlink($sFilename);

        return ($iUserId);
    } else {
        if (isWriteable("/tmp/")) {
            $fp = fopen("/tmp/".$sFilename, "w");
            fwrite($fp, "test");
            fclose($fp);
            $iUserId = fileowner("/tmp/".$sFilename);
            unlink("/tmp/".$sFilename);

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

    if (isWriteable(".")) {
        $fp = fopen($sFilename, "w+");
        fclose($fp);
        $iUserId = filegroup($sFilename);
        unlink($sFilename);

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