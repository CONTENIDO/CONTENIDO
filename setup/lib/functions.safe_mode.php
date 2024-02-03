<?php

/**
 * This file contains various helper functions to read specific values needed for setup checks.
 *
 * @package    Setup
 * @subpackage Helper_PHP
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

function getSafeModeStatus()
{
    if (getPHPIniSetting("safe_mode") == "1") {
        return true;
    } else {
        return false;
    }
}

function getSafeModeGidStatus()
{
    if (getPHPIniSetting("safe_mode_gid") == "1") {
        return true;
    } else {
        return false;
    }
}

function getSafeModeIncludeDir()
{
    return getPHPIniSetting("safe_mode_include_dir");
}

function getOpenBasedir()
{
    return getPHPIniSetting("open_basedir");
}

function getDisabledFunctions()
{
    return getPHPIniSetting("disable_functions");
}