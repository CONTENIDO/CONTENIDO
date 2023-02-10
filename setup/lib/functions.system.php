<?php
/**
 * This file contains various functions for the setup process.
 *
 * @package    Setup
 * @subpackage Setup
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Checks if a plugin is already installed
 * @param cDb $db
 * @param string $sPluginname
 * @return bool
 * @throws cDbException
 */
function checkExistingPlugin($db, $sPluginname) {
    global $cfg;

    #new install: all plugins are checked
    if ($_SESSION['setuptype'] == 'setup') {
        return true;
    }

    $sPluginname = (string) $sPluginname;
    $sTable = $cfg['tab']['nav_sub'];

    switch ($sPluginname) {
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
function updateSystemProperties($db, $table) {
    $table = $db->escape($table);

    $aStandardvalues = [
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

    foreach ($aStandardvalues as $aData) {
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
function updateContenidoVersion($db, $table, $version) {
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
 * @return string
 * @throws cDbException
 */
function getContenidoVersion($db, $table) {
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
function updateSysadminPassword($db, $table, $password, $mail) {
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
function listClients($db, $table) {
    global $cfgClient;

    $db->query("SELECT idclient, name FROM `%s`", $table);

    $clients = [];
    while ($db->nextRecord()) {
        $frontendPath = $cfgClient[$db->f('idclient')]['path']['frontend'];
        $htmlPath     = $cfgClient[$db->f('idclient')]['path']['htmlpath'];

        $clients[$db->f("idclient")] = [
            "name"         => $db->f("name"),
            "frontendpath" => $frontendPath,
            "htmlpath"     => $htmlPath,
        ];
    }

    return $clients;
}

/**
 * Updates the path information of a client and refreshes the configuration file.
 * @param cDb $db
 * @param string $table
 * @param int $idclient
 * @param string $frontendpath
 * @param string $htmlpath
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function updateClientPath($db, $table, $idclient, $frontendpath, $htmlpath) {
    global $cfg, $cfgClient;
    checkAndInclude($cfg['path']['contenido'] . 'includes/functions.general.php');

    updateClientCache($idclient, $htmlpath, $frontendpath);
}

/**
 * Removes the trailing slash of a string.
 * @param string $sInput
 *
 * @return string
 */
function stripLastSlash($sInput) {
    if (cString::getPartOfString($sInput, cString::getStringLength($sInput) - 1, 1) == "/") {
        $sInput = cString::getPartOfString($sInput, 0, cString::getStringLength($sInput) - 1);
    }

    return $sInput;
}

/**
 * Returns the paths to the system directory (filesystem and web).
 * @param bool $bOriginalPath
 *
 * @return array
 */
function getSystemDirectories($bOriginalPath = false) {
    $root_path = stripLastSlash(CON_FRONTEND_PATH);

    $root_http_path = dirname(dirname($_SERVER["REQUEST_URI"]));
    $root_http_path = str_replace("\\", "/", $root_http_path);

    $port = "";
    $protocol = "http://";

    if ($_SERVER["SERVER_PORT"] != 80) {
        if ($_SERVER["SERVER_PORT"] == 443) {
            $protocol = "https://";
        } else {
            $port = ":" . $_SERVER["SERVER_PORT"];
        }
    }

    $root_http_path = $protocol . $_SERVER["SERVER_NAME"] . $port . $root_http_path;

    if (cString::getPartOfString($root_http_path, cString::getStringLength($root_http_path) - 1, 1) == "/") {
        $root_http_path = cString::getPartOfString($root_http_path, 0, cString::getStringLength($root_http_path) - 1);
    }

    if ($bOriginalPath == true) {
        return [$root_path, $root_http_path];
    }

    if (isset($_SESSION['override_root_path'])) {
        $root_path = $_SESSION['override_root_path'];
    }

    if (isset($_SESSION['override_root_http_path'])) {
        $root_http_path = $_SESSION['override_root_http_path'];
    }

    $root_path = stripLastSlash($root_path);
    $root_http_path = stripLastSlash($root_http_path);

    return [$root_path, $root_http_path];
}

/**
 * Searchs for a string in a given text and returns the position of it.
 * @param $string1
 * @param $string2
 *
 * @return int
 */
function findSimilarText($string1, $string2) {
    for ($i = 0; $i < cString::getStringLength($string1); $i++) {
        if (cString::getPartOfString($string1, 0, $i) != cString::getPartOfString($string2, 0, $i)) {
            return $i - 1;
        }
    }

    return $i - 1;
}

?>