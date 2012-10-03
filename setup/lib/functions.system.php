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
 * @version    0.2.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Checks if a plugin is already installed
 * @param   cDb  $db
 * @param   string  $sPluginname
 * @return  bool
 */
function checkExistingPlugin($db, $sPluginname) {
    global $cfg;

    #new install: all plugins are checked
    if ($_SESSION["setuptype"] == "setup") {
        return true;
    }

    $sPluginname = (string) $sPluginname;
    $sTable = $cfg['tab']['nav_sub'];
    $sSql = '';

    switch ($sPluginname) {
        case 'plugin_cronjob_overview':
            $sSql = "SELECT * FROM %s WHERE idnavs=950";
            break;
        case 'plugin_conman':
            $sSql = "SELECT * FROM %s WHERE idnavs=900";
            break;
        case 'plugin_content_allocation':
            $sSql = "SELECT * FROM %s WHERE idnavs=800";
            break;
        case 'plugin_newsletter':
            $sSql = "SELECT * FROM %s WHERE idnavs=610";
            break;
        case 'plugin_mod_rewrite':
            $sSql = "SELECT * FROM %s WHERE idnavs=700 OR location='mod_rewrite/xml/;navigation/content/mod_rewrite'";
            break;
        default:
            $sSql = '';
            break;
    }

    if ($sSql) {
        $db->query($sSql, $sTable);
        if ($db->next_record()) {
            return true;
        }
    }

    return false;
}

/**
 * Updates system properties
 * @param  cDb $db
 * @param  string  $table  DB table name
 */
function updateSystemProperties($db, $table) {
    $table = cSecurity::escapeDB($table, $db);

    $aStandardvalues = array(
        array('type' => 'pw_request', 'name' => 'enable', 'value' => 'true'),
        array('type' => 'system', 'name' => 'mail_sender_name', 'value' => 'CONTENIDO Backend'),
        array('type' => 'system', 'name' => 'mail_sender', 'value' => 'info@contenido.org'),
        array('type' => 'system', 'name' => 'mail_host', 'value' => 'localhost'),
        array('type' => 'maintenance', 'name' => 'mode', 'value' => 'disabled'),
        array('type' => 'codemirror', 'name' => 'activated', 'value' => 'true'),
        array('type' => 'update', 'name' => 'check', 'value' => 'false'),
        array('type' => 'update', 'name' => 'news_feed', 'value' => 'false'),
        array('type' => 'update', 'name' => 'check_period', 'value' => '60'),
        array('type' => 'system', 'name' => 'clickmenu', 'value' => 'false'),
        array('type' => 'versioning', 'name' => 'activated', 'value' => 'true'),
        array('type' => 'versioning', 'name' => 'prune_limit', 'value' => '0'),
        array('type' => 'versioning', 'name' => 'path', 'value' => ''),
        array('type' => 'system', 'name' => 'insight_editing_activated', 'value' => 'true')
    );

    foreach ($aStandardvalues as $aData) {
        $sql = "SELECT value FROM %s WHERE type='%s' AND name='%s'";
        $db->query(sprintf($sql, $table, $aData['type'], $aData['name']));
        if ($db->next_record()) {
            $sValue = $db->f('value');
            if ($sValue == '') {
                $sql = "UPDATE %s SET value = '%s' WHERE type='%s' AND name='%s'";
                $sql = sprintf($sql, $table, $aData['value'], $aData['type'], $aData['name']);
                $db->query($sql);
            }
        } else {
            $sql = "INSERT INTO %s SET type='%s', name='%s', value='%s'";
            $sql = sprintf($sql, $table, $aData['type'], $aData['name'], $aData['value']);
            $db->query($sql);
        }

        if ($db->Errno != 0) {
            logSetupFailure("Unable to execute SQL statement:\n" . $sql . "\nMysql Error: " . $db->Error . " (" . $db->Errno . ")");
        }
    }
}

/**
 * Updates contenido version in given table
 * @param  cDb $db
 * @param  string  $table  DB table name
 * @param  string  $version  Version
 */
function updateContenidoVersion($db, $table, $version) {
    $sql = "SELECT idsystemprop FROM %s WHERE type='system' AND name='version'";
    $db->query(sprintf($sql, cSecurity::escapeDB($table, $db)));

    if ($db->next_record()) {
        $sql = "UPDATE %s SET value = '%s' WHERE type='system' AND name='version'";
        $db->query(sprintf($sql, cSecurity::escapeDB($table, $db), cSecurity::escapeDB($version, $db)));
    } else {
        //$id = $db->nextid($table);
        $sql = "INSERT INTO %s SET type='system', name='version', value='%s'";
        $db->query(sprintf($sql, cSecurity::escapeDB($table, $db), cSecurity::escapeDB($version, $db)));
    }
}

/**
 * Returns current version
 * @param  cDb $db
 * @param  string  $table  DB table name
 * @return string
 */
function getContenidoVersion($db, $table) {
    $sql = "SELECT value FROM %s WHERE type='system' AND name='version'";
    $db->query(sprintf($sql, cSecurity::escapeDB($table, $db)));

    if ($db->next_record()) {
        return $db->f("value");
    } else {
        return false;
    }
}

// @FIXME: Comment me plz!
function updateSysadminPassword($db, $table, $password) {
    $sql = "SELECT password FROM %s WHERE username='sysadmin'";
    $db->query(sprintf($sql, cSecurity::escapeDB($table, $db)));

    if ($db->next_record()) {
        $sql = "UPDATE %s SET password='%s' WHERE username='sysadmin'";
        $db->query(sprintf($sql, cSecurity::escapeDB($table, $db), md5($password)));
        return true;
    } else {

        return false;
    }
}

// @FIXME: Comment me plz!
function listClients($db, $table) {
    global $cfgClient;

    $sql = "SELECT idclient, name FROM %s";

    $db->query(sprintf($sql, cSecurity::escapeDB($table, $db)));

    $clients = array();

    while ($db->next_record()) {
        $frontendPath = $cfgClient[$db->f('idclient')]['path']['frontend'];
        $htmlPath = $cfgClient[$db->f('idclient')]['path']['htmlpath'];
        $clients[$db->f("idclient")] = array("name" => $db->f("name"), "frontendpath" => $frontendPath, "htmlpath" => $htmlPath);
    }

    return $clients;
}

// @FIXME: Comment me plz!
function updateClientPath($db, $table, $idclient, $frontendpath, $htmlpath) {
    global $cfg, $cfgClient;
    checkAndInclude($cfg['path']['contenido'] . 'includes/functions.general.php');

    rereadClients();
    updateClientCache($idclient, $htmlpath, $frontendpath);
}

// @FIXME: Comment me plz!
function stripLastSlash($sInput) {
    if (substr($sInput, strlen($sInput) - 1, 1) == "/") {
        $sInput = substr($sInput, 0, strlen($sInput) - 1);
    }

    return $sInput;
}

// @FIXME: Comment me plz!
function getSystemDirectories($bOriginalPath = false) {
    $root_path = stripLastSlash(CON_FRONTEND_PATH);

    $root_http_path = dirname(dirname($_SERVER["PHP_SELF"]));
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

    if (substr($root_http_path, strlen($root_http_path) - 1, 1) == "/") {
        $root_http_path = substr($root_http_path, 0, strlen($root_http_path) - 1);
    }

    if ($bOriginalPath == true) {
        return array($root_path, $root_http_path);
    }

    if (isset($_SESSION["override_root_path"])) {
        $root_path = $_SESSION["override_root_path"];
    }

    if (isset($_SESSION["override_root_http_path"])) {
        $root_http_path = $_SESSION["override_root_http_path"];
    }

    $root_path = stripLastSlash($root_path);
    $root_http_path = stripLastSlash($root_http_path);

    return array($root_path, $root_http_path);
}

// @FIXME: Comment me plz!
function findSimilarText($string1, $string2) {
    for ($i = 0; $i < strlen($string1); $i++) {
        if (substr($string1, 0, $i) != substr($string2, 0, $i)) {
            return $i - 1;
        }
    }

    return $i - 1;
}

?>