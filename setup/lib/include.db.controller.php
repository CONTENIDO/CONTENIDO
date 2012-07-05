<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Creates/Updates the database tables and fills them with entries (depending on
 * selected options during setup process)
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO setup
 * @version    0.2.6
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

global $db;

checkAndInclude($cfg['path']['contenido'] . 'includes/functions.database.php');
checkAndInclude($cfg['path']['contenido'] . 'includes/functions.general.php');

$db = getSetupMySQLDBConnection(false);

if (checkMySQLDatabaseCreation($db, $_SESSION['dbname'])) {
    $db = getSetupMySQLDBConnection();
}

$currentStep = (isset($_GET['step']) && (int) $_GET['step'] > 0) ? (int) $_GET['step'] : 0;

if ($currentStep == 0) {
    $currentStep = 1;
}

$count = 0;
$fullCount = 0;

// Count DB Chunks
$file = fopen('data/tables.txt', 'r');
$step = 1;
while (($data = fgetcsv($file, 4000, ';')) !== false) {
    if ($count == C_SETUP_MAX_CHUNKS_PER_STEP) {
        $count = 1;
        $step++;
    }

    if ($currentStep == $step) {
        if ($data[7] == '1') {
            $drop = true;
        } else {
            $drop = false;
        }
        dbUpgradeTable($db, $cfg['sql']['sqlprefix'].'_'.$data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], '', $drop);

        if ($db->Errno != 0) {
            $_SESSION['install_failedupgradetable'] = true;
        }
    }

    $count++;
    $fullCount++;
}

// Count DB Chunks (plugins)
$file = fopen('data/tables_pi.txt', 'r');
$step = 1;
while (($data = fgetcsv($file, 4000, ';')) !== false) {
    if ($count == C_SETUP_MAX_CHUNKS_PER_STEP) {
        $count = 1;
        $step++;
    }

    if ($currentStep == $step) {
        if ($data[7] == '1') {
            $drop = true;
        } else {
            $drop = false;
        }
        dbUpgradeTable($db, $cfg['sql']['sqlprefix'].'_'.$data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], '', $drop);

        if ($db->Errno != 0) {
            $_SESSION['install_failedupgradetable'] = true;
        }
    }

    $count++;
    $fullCount++;
}

$pluginChunks = array();

$baseChunks = explode("\n", cFileHandler::read('data/base.txt'));

$clientChunks = explode("\n", cFileHandler::read('data/client.txt'));

$moduleChunks = explode("\n", cFileHandler::read('data/standard.txt'));

$contentChunks = explode("\n", cFileHandler::read('data/examples.txt'));

$sysadminChunk = explode("\n", cFileHandler::read('data/sysadmin.txt'));

if ($_SESSION['plugin_newsletter'] == 'true') {
    $newsletter = explode("\n", cFileHandler::read('data/plugin_newsletter.txt'));
    $pluginChunks = array_merge($pluginChunks, $newsletter);
}

if ($_SESSION['plugin_content_allocation'] == 'true') {
    $tagging = explode("\n", cFileHandler::read('data/plugin_content_allocation.txt'));
    $pluginChunks = array_merge($pluginChunks, $tagging);
}

if ($_SESSION['plugin_mod_rewrite'] == 'true') {
    $mod_rewrite = explode("\n", cFileHandler::read('data/plugin_mod_rewrite.txt'));
    $pluginChunks = array_merge($pluginChunks, $mod_rewrite);
}

if ($_SESSION['plugin_cronjob_overview'] == 'true') {
    $cronjob_overview = explode("\n", cFileHandler::read('data/plugin_cronjob_overview.txt'));
    $pluginChunks = array_merge($pluginChunks, $cronjob_overview);
}

list($rootPath, $rootHttpPath) = getSystemDirectories();

if ($_SESSION['setuptype'] == 'setup') {
    switch ($_SESSION['clientmode']) {
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

$totalSteps = ceil($fullCount/C_SETUP_MAX_CHUNKS_PER_STEP) + count($fullChunks) + 1;
foreach ($fullChunks as $fullChunk) {
    $step++;
    if ($step == $currentStep) {
        $replacements = array(
            '<!--{contenido_root}-->' => addslashes($rootPath),
            '<!--{contenido_web}-->' => addslashes($rootHttpPath)
        );

        injectSQL($db, $cfg['sql']['sqlprefix'], 'data/' . $fullChunk, $replacements);
    }
}

$percent = intval((100 / $totalSteps) * ($currentStep));

echo '<script type="text/javascript">parent.updateProgressbar('.$percent.');</script>';

if ($currentStep < $totalSteps) {

    printf('<script type="text/javascript">function nextStep() { window.location.href="index.php?c=db&step=%s"; };</script>', $currentStep + 1);
    if (!C_SETUP_DEBUG) {
        echo '<script type="text/javascript">window.setTimeout(nextStep, 10);</script>';
    } else {
        echo '<a href="javascript:nextStep();">Next step</a>';
    }

} else {
    // For import mod_history rows to versioning
    if ($_SESSION['setuptype'] == 'migration' || $_SESSION['setuptype'] == 'upgrade') {
        $cfgClient = array();
        rereadClients();

        $oVersion = new VersionImport($cfg, $cfgClient, $db, $client, $area, $frame);
        $oVersion->CreateHistoryVersion();
    }

    updateContenidoVersion($db, $cfg['tab']['system_prop'], C_SETUP_VERSION);

    if (isset($_SESSION['sysadminpass']) && $_SESSION['sysadminpass'] != '') {
        updateSysadminPassword($db, $cfg['tab']['phplib_auth_user_md5'], 'sysadmin');
    }

    $db->query('DELETE FROM %s', $cfg['tab']['code']);

    // As con_code has been emptied, force code creation (on update)
    $db->query('UPDATE %s SET createcode=1', $cfg['tab']['cat_art']);

    if ($_SESSION['setuptype'] == 'migration') {
        $aClients = listClients($db, $cfg['tab']['clients']);
        foreach ($aClients as $iIdClient => $aInfo) {
            updateClientPath($db, $cfg['tab']['clients'], $iIdClient, $_SESSION['frontendpath'][$iIdClient], $_SESSION['htmlpath'][$iIdClient]);
        }
    }

    if ($_SESSION['setuptype'] == 'upgrade') {
        $sql = "SELECT * FROM ".$cfg["tab"]["lang"];
        $db->query($sql);

        while ($db->next_record()) {
            $langs[] = $db->f("idlang");
        }

        $sql = "SELECT * FROM ".$cfg["tab"]["cat_art"]." WHERE is_start='1'";
        $db->query($sql);

        $db2 = getSetupMySQLDBConnection();

        while ($db->next_record()) {
            $startidart = $db->f("idart");
            $idcat = $db->f("idcat");

            foreach ($langs as $vlang) {
                $sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE idart='$startidart' AND idlang='$vlang'";
                $db2->query($sql);
                if ($db2->next_record()) {
                    $idartlang = $db2->f("idartlang");

                    $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET startidartlang='$idartlang' WHERE idcat='$idcat' AND idlang='$vlang'";
                    $db2->query($sql);
                }
            }
        }

        $sql = "UPDATE ".$cfg["tab"]["cat_art"]." SET is_start='0'";
        $db->query($sql);
    }

    // Update Keys
    $aNothing = array();

    injectSQL($db, $cfg['sql']['sqlprefix'], 'data/indexes.sql', array(), $aNothing);

    // update to autoincrement
    addAutoIncrementToTables($db, $cfg);

    // insert or update default system properties
    updateSystemProperties($db, $cfg['tab']['system_prop']);

    if ($_SESSION['setuptype'] == 'setup') {
        switch ($_SESSION['clientmode']) {
            case 'CLIENTMODULES':
            case 'CLIENTEXAMPLES':
                global $cfgClient;
                updateClientPath($db, $cfg['tab']['clients'], 1, $rootPath . '/cms/', $rootHttpPath . '/cms/');
                break;

            default:
                break;
        }
    }

    // Makes the new concept of moduls (save the moduls to the file) save the translation
    if ($_SESSION['setuptype'] == 'upgrade' || $_SESSION['setuptype'] == 'setup') {

        // @fixme  Get rid of hacks below
        // @fixme  Logic below works only for setup, not for upgrade because of different clients and languages

        global $client, $lang, $cfgClient;  // is used in LayoutInFile below!!!
        $clientBackup = $client;
        $langBackup = $lang;
        $client = 1;
        $lang = 1;

        if ($_SESSION['setuptype'] == 'upgrade') {
            $sql = "SHOW COLUMNS FROM %s LIKE 'frontendpath'";
            $sql = sprintf($sql, $cfg['tab']['clients']);

            $db->query($sql);
            if ($db->num_rows() != 0) {
                $sql = "SELECT * FROM ".$cfg['tab']['clients'];
                $db->query($sql);

                while ($db->next_record()) {
                    updateClientCache($db->f("idclient"), $db->f("htmlpath"), $db->f("frontendpath"));
                }

                $sql = sprintf("ALTER TABLE %s DROP htmlpath", $cfg['tab']['clients']);
                $db->query($sql);

                $sql = sprintf("ALTER TABLE %s DROP frontendpath", $cfg['tab']['clients']);
                $db->query($sql);
            }
            checkAndInclude($cfg['path']['contenido_config'] . 'config.clients.php');
        }

        rereadClients();

        Contenido_Module_Handler::setEncoding('ISO-8859-1');

        //set default configuration for connection,

        //for all db objects in Contenido_UpgradeJob

        DB_Contenido::setDefaultConfiguration($cfg['db']);

        // Save all modules from db-table to the filesystem
        $contenidoUpgradeJob = new Contenido_UpgradeJob($db);
        $contenidoUpgradeJob->convertModulesToFile($_SESSION['setuptype']);

        // Save layout from db-table to the file system
        $layoutInFile = new LayoutInFile(1, '', $cfg, 1, $db);
        $layoutInFile->upgrade();

        $db2 = getSetupMySQLDBConnection();
        $sql = "SELECT * FROM ".$cfg['tab']['lay'];
        $db->query($sql);
        while ($db->next_record()) {
            if($db->f("alias") == "") {
                $sql = "UPDATE ".$cfg['tab']['lay']." SET `alias`='".$db->f("name")."' WHERE `idlay`='".$db->f("idlay")."';";
                $db2->query($sql);
            }
        }

        $sql = "SELECT * FROM ".$cfg['tab']['mod'];
        $db->query($sql);
        while ($db->next_record()) {
               if($db->f("alias") == "") {
                $sql = "UPDATE ".$cfg['tab']['mod']." SET `alias`='".$db->f("name")."' WHERE `idmod`='".$db->f("idmod")."';";
                $db2->query($sql);
            }
        }

        $client = $clientBackup;
        $lang = $langBackup;
        unset($clientBackup, $langBackup);
    }

    convertToDatetime($db, $cfg);

    URLDecodeTables($db);

    echo '
        <script type="text/javascript">
        parent.document.getElementById("installing").style.visibility="hidden";
        parent.document.getElementById("installingdone").style.visibility="visible";
        parent.document.getElementById("next").style.visibility="visible";
        function nextStep() {
            window.location.href="index.php?c=config";
        };
        </script>
    ';

    if (!C_SETUP_DEBUG) {
        echo '<script type="text/javascript">window.setTimeout(nextStep, 10);</script>';
    } else {
        echo '<a href="javascript:nextStep();">Last step</a>';
    }

}

?>