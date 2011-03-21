<?php
 /**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Creates/Updates the database tables and fills them with entries (depending on 
 * selected options during setup process)
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    Contenido setup
 * @version    0.2.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 *
 * {@internal
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *   modified 2011-02-24, Murat Purc, extended mysql extension detection
 *   modified 2011-02-28, Murat Purc, normalized setup startup process and some cleanup/formatting
 *   modified 2011-03-21, Murat Purc, usage of new db connection
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}
define('C_CONTENIDO_PATH', '../contenido/');

include_once('lib/startup.php');


checkAndInclude(C_CONTENIDO_PATH . 'includes/functions.database.php');
checkAndInclude(C_CONTENIDO_PATH . 'classes/class.version.php');
checkAndInclude(C_CONTENIDO_PATH . 'classes/class.versionImport.php');


if (hasMySQLiExtension() && !hasMySQLExtension()) {
    // use MySQLi extension by default if available
    $cfg['database_extension'] = 'mysqli';
} elseif (hasMySQLExtension()) {
    // use MySQL extension if available
    $cfg['database_extension'] = 'mysql';
} else {
    die("Can't detect MySQLi or MySQL extension");
}

checkAndInclude('../conlib/prepend.php');

// Workaround for the 'bad' conlib functions
$cfg['tab']['sequence'] = $_SESSION['dbprefix'].'_sequence';

$db = getSetupMySQLDBConnection(false);

if (checkMySQLDatabaseCreation($db, $_SESSION['dbname'])) {
    $db = getSetupMySQLDBConnection();
}

$currentstep = $_GET['step'];

if ($currentstep == 0) {
    $currentstep = 1;
}

// Count DB Chunks
$file = fopen('data/tables.txt', 'r');
$step = 1;
while (($data = fgetcsv($file, 4000, ';')) !== false) {
    if ($count == 50) {
        $count = 1;
        $step++;
    }

    if ($currentstep == $step) {
        if ($data[7] == '1') {
            $drop = true;
        } else {
            $drop = false;
        }
        dbUpgradeTable($db, $_SESSION['dbprefix'].'_'.$data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], '', $drop);

        if ($db->errno != 0) {
            $_SESSION['install_failedupgradetable'] = true;
        }
    }

    $count++;
    $fullcount++;
}

// Count DB Chunks (plugins)
$file = fopen('data/tables_pi.txt', 'r');
$step = 1;
while (($data = fgetcsv($file, 4000, ';')) !== false) {
    if ($count == 50) {
        $count = 1;
        $step++;
    }

    if ($currentstep == $step) {
        if ($data[7] == '1') {
            $drop = true;
        } else {
            $drop = false;
        }
        dbUpgradeTable($db, $_SESSION['dbprefix'].'_'.$data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], '', $drop);

        if ($db->errno != 0) {
            $_SESSION['install_failedupgradetable'] = true;
        }
    }

    $count++;
    $fullcount++;
}

$pluginChunks = array();

$baseChunks = explode("\n", file_get_contents('data/base.txt'));

$clientChunks = explode("\n", file_get_contents('data/client.txt'));

$moduleChunks = explode("\n", file_get_contents('data/standard.txt'));

$contentChunks = explode("\n", file_get_contents('data/examples.txt'));

$sysadminChunk = explode("\n", file_get_contents('data/sysadmin.txt'));

if ($_SESSION['plugin_newsletter'] == 'true') {
    $newsletter = explode("\n", file_get_contents('data/plugin_newsletter.txt'));
    $pluginChunks = array_merge($pluginChunks, $newsletter);
}

if ($_SESSION['plugin_content_allocation'] == 'true') {
    $content_allocation = explode("\n", file_get_contents('data/plugin_content_allocation.txt'));
    $pluginChunks = array_merge($pluginChunks, $content_allocation);
}

if ($_SESSION['setuptype'] == 'setup') {
    switch ($_SESSION['clientmode']) {
        case 'CLIENT':
            $fullChunks = array_merge($baseChunks, $sysadminChunk, $clientChunks);
            break;
        case 'CLIENTMODULES':
            $fullChunks = array_merge($baseChunks, $sysadminChunk, $clientChunks, $moduleChunks);
            break;
        case 'CLIENTEXAMPLES':
            $fullChunks = array_merge($baseChunks, $sysadminChunk, $clientChunks, $moduleChunks, $contentChunks);
            break;
        default:
            $fullChunks = array_merge($baseChunks, $sysadminChunk);
            break;
    }
} else {
    $fullChunks = $baseChunks;
}

$fullChunks = array_merge($fullChunks, $pluginChunks);


list($root_path, $root_http_path) = getSystemDirectories();

$totalsteps = ceil($fullcount/50) + count($fullChunks) + 1;
foreach ($fullChunks as $fullChunk) {
    $step++;
    if ($step == $currentstep) {
        $failedChunks = array();

        $replacements = array(
            '<!--{contenido_root}-->' => addslashes($root_path),
            '<!--{contenido_web}-->' => addslashes($root_http_path)
        );

        injectSQL($db, $_SESSION['dbprefix'], 'data/' . $fullChunk, $replacements, $failedChunks);

        if (count($failedChunks) > 0) {
            @$fp = fopen('../contenido/logs/setuplog.txt', 'w');
            foreach ($failedChunks as $failedChunk) {
                @fwrite($fp, sprintf("Setup was unable to execute SQL. MySQL-Error: %s, MySQL-Message: %s, SQL-Statements:\n%s", $failedChunk['errno'], $failedChunk['error'], $failedChunk['sql']));
            }
            @fclose($fp);

            $_SESSION['install_failedchunks'] = true;
        }
    }
}

$percent = intval((100 / $totalsteps) * ($currentstep));

echo '<script language="JavaScript">parent.updateProgressbar('.$percent.');</script>';
if ($currentstep < $totalsteps) {
    printf('<script language="JavaScript">window.setTimeout("nextStep()", 10); function nextStep () { window.location.href=\'dbupdate.php?step=%s\'; }</script>', $currentstep + 1);
} else {
    $sql = 'SHOW TABLES';
    $db->query($sql);

    // For import mod_history rows to versioning
    if ($_SESSION['setuptype'] == 'migration' || $_SESSION['setuptype'] == 'upgrade') {
        $cfgClient = array();
        rereadClients_Setup();

        $oVersion = new VersionImport($cfg, $cfgClient, $db, $client, $area, $frame);
        $oVersion->CreateHistoryVersion();
    }

    $tables = array();

    while ($db->next_record()) {
        $tables[] = $db->f(0);
    }

    foreach ($tables as $table) {
        dbUpdateSequence($_SESSION['dbprefix'].'_sequence', $table, $db);
    }

    updateContenidoVersion($db, $_SESSION['dbprefix'].'_system_prop', C_SETUP_VERSION);
    updateSystemProperties($db, $_SESSION['dbprefix'].'_system_prop');

    if (isset($_SESSION['sysadminpass']) && $_SESSION['sysadminpass'] != '') {
        updateSysadminPassword($db, $_SESSION['dbprefix'].'_phplib_auth_user_md5', 'sysadmin');
    }

    $sql = 'DELETE FROM %s';
    $db->query(sprintf($sql, $_SESSION['dbprefix'].'_code'));

    // As con_code has been emptied, force code creation (on update)
    $sql = "UPDATE %s SET createcode = '1'";
    $db->query(sprintf($sql, $_SESSION['dbprefix'].'_cat_art'));

    if ($_SESSION['setuptype'] == 'migration') {
        $aClients = listClients($db, $_SESSION['dbprefix'].'_clients');

        foreach ($aClients as $iIdClient => $aInfo) {
            updateClientPath($db, $_SESSION['dbprefix'].'_clients', $iIdClient, $_SESSION['frontendpath'][$iIdClient], $_SESSION['htmlpath'][$iIdClient]);
        }
    }

    $_SESSION['start_compatible'] = false;

    if ($_SESSION['setuptype'] == 'upgrade') {
        $sql = "SELECT is_start FROM %s WHERE is_start = 1";
        $db->query(sprintf($sql, $_SESSION['dbprefix'].'_cat_art'));

        if ($db->next_record()) {
            $_SESSION['start_compatible'] = true;
        }
    }

    // Update Keys
    $aNothing = array();

    injectSQL($db, $_SESSION['dbprefix'], 'data/indexes.sql', array(), $aNothing);

    printf('<script language="JavaScript">parent.document.getElementById("installing").style.visibility="hidden";parent.document.getElementById("installingdone").style.visibility="visible";</script>');
    printf('<script language="JavaScript">parent.document.getElementById("next").style.visibility="visible"; window.setTimeout("nextStep()", 10); function nextStep () { window.location.href=\'makeconfig.php\'; }</script>');
}

?>