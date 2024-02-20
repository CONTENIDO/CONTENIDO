<?php

/**
 * This file contains various functions for the setup process.
 *
 * @package    Setup
 * @subpackage Setup
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Checks if a plugin is already installed
 * @param cDb $db
 * @param string $pluginName
 * @return bool
 * @throws cDbException
 */
function checkExistingPlugin(cDb $db, string $pluginName): bool
{
    // new install: all plugins are checked
    if ($_SESSION['setuptype'] == 'setup') {
        return true;
    }

    $sTable = cRegistry::getDbTableName('nav_sub');

    switch ($pluginName) {
        case 'plugin_cronjob_overview':
            $sSql = "SELECT * FROM `%s` WHERE idnavs=950";
            break;
        case 'plugin_conman':
            $sSql = "SELECT * FROM `%s` WHERE idnavs=900";
            break;
        case 'plugin_content_allocation':
            $sSql = "SELECT * FROM `%s` WHERE idnavs=800";
            break;
        case 'plugin_newsletter':
            $sSql = "SELECT * FROM `%s` WHERE idnavs=610";
            break;
        case 'plugin_mod_rewrite':
            $sSql = "SELECT * FROM `%s` WHERE idnavs=700 OR location='mod_rewrite/xml/;navigation/content/mod_rewrite'";
            break;
        default:
            $sSql = '';
            break;
    }

    if ($sSql) {
        $db->query($sSql, $sTable);
        if ($db->nextRecord()) {
            return true;
        }
    }

    return false;
}

/**
 * Updates system properties
 * @param cDb $db
 * @param string $table DB table name
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function updateSystemProperties(cDb $db, string $table)
{
    $table = $db->escape($table);

    $standardValues = [
        ['type' => 'pw_request', 'name' => 'enable', 'value' => 'true'],
        ['type' => 'system', 'name' => 'mail_transport', 'value' => 'smtp'],
        ['type' => 'system', 'name' => 'mail_sender_name', 'value' => 'CONTENIDO Backend'],
        ['type' => 'system', 'name' => 'mail_sender', 'value' => 'info@contenido.org'],
        ['type' => 'system', 'name' => 'mail_host', 'value' => 'localhost'],
        ['type' => 'maintenance', 'name' => 'mode', 'value' => 'disabled'],
        ['type' => 'codemirror', 'name' => 'activated', 'value' => 'true'],
        ['type' => 'update', 'name' => 'check', 'value' => 'false'],
        ['type' => 'update', 'name' => 'news_feed', 'value' => 'false'],
        ['type' => 'update', 'name' => 'check_period', 'value' => '60'],
        ['type' => 'system', 'name' => 'clickmenu', 'value' => 'false'],
        ['type' => 'versioning', 'name' => 'activated', 'value' => 'true'],
        ['type' => 'versioning', 'name' => 'prune_limit', 'value' => ''],
        ['type' => 'versioning', 'name' => 'path', 'value' => ''],
        ['type' => 'system', 'name' => 'insite_editing_activated', 'value' => 'true'],
        ['type' => 'backend', 'name' => 'backend_label', 'value' => ''],
        ['type' => 'generator', 'name' => 'xhtml', 'value' => 'true'],
        ['type' => 'generator', 'name' => 'basehref', 'value' => 'true'],
        ['type' => 'debug', 'name' => 'module_translation_message', 'value' => 'true'],
        ['type' => 'debug', 'name' => 'debug_for_plugins', 'value' => 'true'],
        ['type' => 'stats', 'name' => 'tracking', 'value' => 'disabled'],
    ];

    foreach ($standardValues as $aData) {
        $sql = $db->prepare("SELECT `value` FROM `%s` WHERE `type` = '%s' AND `name` = '%s'", $table, $aData['type'], $aData['name']);
        $db->query($sql);
        if ($db->nextRecord()) {
            $sValue = $db->f('value');
            if ($sValue == '') {
                $sql = $db->prepare("UPDATE `%s` SET `value` = '%s' WHERE `type` = '%s' AND `name` = '%s'", $table, $aData['value'], $aData['type'], $aData['name']);
                $db->query($sql);
            }
        } else {
            $sql = $db->prepare("INSERT INTO `%s` SET `type` = '%s', `name` = '%s', `value` = '%s'", $table, $aData['type'], $aData['name'], $aData['value']);
            $db->query($sql);
        }

        if ($db->getErrorNumber() != 0) {
            logSetupFailure("Unable to execute SQL statement:\n" . $sql . "\nMysql Error: " . $db->getErrorMessage() . " (" . $db->getErrorNumber() . ")");
        }
    }
}

/**
 * Updates contenido version in given table
 * @param cDb $db
 * @param string $table DB table name
 * @param string $version Version
 * @throws cDbException
 */
function updateContenidoVersion(cDb $db, string $table, string $version)
{
    $db->query("SELECT `idsystemprop` FROM `%s` WHERE `type` = 'system' AND `name` = 'version'", $table);

    if ($db->nextRecord()) {
        $db->query("UPDATE `%s` SET `value` = '%s' WHERE `type` = 'system' AND `name` = 'version'", $table, $version);
    } else {
        $db->query("INSERT INTO `%s` SET `type` = 'system', `name` = 'version', `value` = '%s'", $table, $version);
    }
}

/**
 * Returns current version
 * @param cDb $db
 * @param string $table DB table name
 * @return string|false
 * @throws cDbException
 */
function getContenidoVersion(cDb $db, string $table)
{
    $db->query("SELECT `value` FROM `%s` WHERE `type` = 'system' AND `name` = 'version'", $table);

    if ($db->nextRecord()) {
        return $db->f("value");
    } else {
        return false;
    }
}

/**
 * Updates the system administrators password.
 *
 * @param cDb $db
 * @param string $table
 * @param string $password
 * @param string $mail
 * @return bool
 * @throws cDbException
 */
function updateSysadminPassword(cDb $db, string $table, string $password, string $mail)
{
    $db->query("SELECT password FROM `%s` WHERE username='sysadmin'", $table);

    if ($db->nextRecord()) {
        $db->query("UPDATE `%s` SET password='%s', email='%s' WHERE username='sysadmin'", $table, md5($password), $mail);
        return true;
    } else {
        return false;
    }
}

/**
 * Reads and returns the total list of system clients.
 * @param cDb $db
 * @param string $table
 * @return array
 * @throws cDbException
 */
function listClients(cDb $db, string $table)
{
    $cfgClient = cRegistry::getClientConfig();

    $db->query("SELECT `idclient`, `name` FROM `%s`", $table);

    $clients = [];
    while ($db->nextRecord()) {
        $idClient = cSecurity::toInteger($db->f('idclient'));
        $clients[$idClient] = [
            "name" => $db->f("name"),
            "frontendpath" => $cfgClient[$idClient]['path']['frontend'],
            "htmlpath" => $cfgClient[$idClient]['path']['htmlpath'],
        ];
    }

    return $clients;
}

/**
 * Updates the path information of a client and refreshes the configuration file.
 * @param int $idclient
 * @param string $frontendpath
 * @param string $htmlpath
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function updateClientPath(int $idclient, string $frontendpath, string $htmlpath)
{
    $cfg = cRegistry::getConfig();

    checkAndInclude($cfg['path']['contenido'] . 'includes/functions.general.php');
    updateClientCache($idclient, $htmlpath, $frontendpath);
}

/**
 * Removes the trailing slash of a string.
 * @param string $sInput
 *
 * @return string
 */
function stripLastSlash(string $sInput): string
{
    if (cString::getPartOfString($sInput, cString::getStringLength($sInput) - 1, 1) == "/") {
        $sInput = cString::getPartOfString($sInput, 0, cString::getStringLength($sInput) - 1);
    }

    return $sInput;
}

/**
 * Returns the paths to the system directory (filesystem and web).
 * @param bool $originalPath
 *
 * @return array
 */
function getSystemDirectories(bool $originalPath = false): array
{
    $rootPath = stripLastSlash(CON_FRONTEND_PATH);

    $rootHttpPath = dirname($_SERVER["REQUEST_URI"], 2);
    $rootHttpPath = str_replace("\\", "/", $rootHttpPath);

    $port = "";
    $protocol = "http://";

    if ($_SERVER["SERVER_PORT"] != 80) {
        if ($_SERVER["SERVER_PORT"] == 443) {
            $protocol = "https://";
        } else {
            $port = ":" . $_SERVER["SERVER_PORT"];
        }
    }

    $rootHttpPath = $protocol . $_SERVER["SERVER_NAME"] . $port . $rootHttpPath;

    if (cString::getPartOfString($rootHttpPath, cString::getStringLength($rootHttpPath) - 1, 1) == "/") {
        $rootHttpPath = cString::getPartOfString($rootHttpPath, 0, cString::getStringLength($rootHttpPath) - 1);
    }

    if ($originalPath) {
        return [$rootPath, $rootHttpPath];
    }

    if (isset($_SESSION['override_root_path'])) {
        $rootPath = $_SESSION['override_root_path'];
    }

    if (isset($_SESSION['override_root_http_path'])) {
        $rootHttpPath = $_SESSION['override_root_http_path'];
    }

    $rootPath = stripLastSlash($rootPath);
    $rootHttpPath = stripLastSlash($rootHttpPath);

    return [$rootPath, $rootHttpPath];
}

/**
 * Searchs for a string in a given text and returns the position of it.
 * @param string $string1
 * @param string $string2
 *
 * @return int
 */
function findSimilarText(string $string1, string $string2): int
{
    for ($i = 0; $i < cString::getStringLength($string1); $i++) {
        if (cString::getPartOfString($string1, 0, $i) != cString::getPartOfString($string2, 0, $i)) {
            return $i - 1;
        }
    }

    return $i - 1;
}
