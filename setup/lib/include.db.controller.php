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
    if ($count == CON_SETUP_MAX_CHUNKS_PER_STEP) {
        $count = 1;
        $step++;
    }

    if ($currentStep == $step) {
        if ($data[7] == '1') {
            $drop = true;
        } else {
            $drop = false;
        }
        dbUpgradeTable($db, $cfg['sql']['sqlprefix'] . '_' . $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], '', $drop);

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
    if ($count == CON_SETUP_MAX_CHUNKS_PER_STEP) {
        $count = 1;
        $step++;
    }

    if ($currentStep == $step) {
        if ($data[7] == '1') {
            $drop = true;
        } else {
            $drop = false;
        }
        dbUpgradeTable($db, $cfg['sql']['sqlprefix'] . '_' . $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], '', $drop);

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

$totalSteps = ceil($fullCount / CON_SETUP_MAX_CHUNKS_PER_STEP) + count($fullChunks) + 1;
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

echo '<script type="text/javascript">parent.updateProgressbar(' . $percent . ');</script>';

if ($currentStep < $totalSteps) {
    // Still processing database setup, output js code to run the next step
    printf('<script type="text/javascript">function nextStep() { window.location.href="index.php?c=db&step=%s"; };</script>', $currentStep + 1);
    if (!CON_SETUP_DEBUG) {
        echo '<script type="text/javascript">window.setTimeout(nextStep, 10);</script>';
    } else {
        echo '<a href="javascript:nextStep();">Next step</a>';
    }
} else {
    // Databasse setup is done, now do remaining upgrade jobs

    // For import mod_history rows to versioning
    if ($_SESSION['setuptype'] == 'migration' || $_SESSION['setuptype'] == 'upgrade') {
        setupInitializeCfgClient(true);
    }

    require_once(CON_SETUP_PATH . '/upgrade_jobs/class.upgrade.job.abstract.php');
    require_once(CON_SETUP_PATH . '/upgrade_jobs/class.upgrade.job.main.php');

    // Execute upgrade jobs
    $oUpgradeMain = new cUpgradeJobMain($db, $cfg, $cfgClient);
    $oUpgradeMain->execute();

    echo '
        <script type="text/javascript">
        parent.document.getElementById("installing").style.visibility = "hidden";
        parent.document.getElementById("installingdone").style.visibility = "visible";
        parent.document.getElementById("next").style.visibility = "visible";
        function nextStep() {
            window.location.href = "index.php?c=config";
        };
        </script>
    ';

    if (!CON_SETUP_DEBUG) {
        echo '<script type="text/javascript">window.setTimeout(nextStep, 10);</script>';
    } else {
        echo '<a href="javascript:nextStep();">Last step</a>';
    }
}

?>