<?php
/**
 * This is the automated install script for CONTENIDO
 *
 * @package Setup
 * @subpackage Setup
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

// first, check if the file is being called by the CLI
if ('cli' !== PHP_SAPI) {
    die('This program is suppsoed to be run from the command line.');
}

// some standard configurations of the php cli have a max execution limit
set_time_limit(0);
// some systems also report notices to the console which is rather annoying
error_reporting(E_ALL ^ E_NOTICE);

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// include the console helper functions
include_once('lib/functions.clisetup.php');
include_once('lib/class.clisetup.php');

// parse the arguments of the script and store them in an array
$args = getArgs();
$cliSetup = new cCLISetup($args);

// define the necessary constants and include the setup's startup.php
include_once('lib/startup.php');

echo('startup...');

// initialize the variables we will need to make sure they are all set to an empty value or their standard value
initializeVariables();

// read the command line and gather all setup settings accordingly
$cliSetup->interpretCommandline();

// write the settings to the places where the CONTENIDO setup expects them
$cliSetup->applySettings();

// check every parameter that is needed for the setup
if (!checkInstallationSettings()) {
    if (!$args['noninteractive']) {
        do {
            // ask the user for the settings if some are missing and the interactive mode is allowed
            prntln(i18n('Please enter the missing settings below:', 'setup'));
            $cliSetup->getUserInputSettings();
            $cliSetup->applySettings();
        } while (!checkInstallationSettings());
    } else {
        exit(2);
    }
}

// execute the system tests
$cliSetup->executeSystemTests();

// if we are here, the user either ignored errors or everything is fine - start the installation
prntln(i18n('Starting setup', 'setup'));
prnt(i18n('Writing data...', 'setup'));
if (!$args['noninteractive']) { // print the fancy progress bar only if the user did not specify the non-interactive mode
    prntln();
    progressBar(50, 0);
}

// include the db controller for the first time. It will calculate the totalsteps needed and execute the first step
ob_start();
include('lib/include.db.controller.php');
$output = ob_get_contents();
ob_end_clean();

// loop until totalSteps is reached
for ($i = 2; $i < $totalSteps; $i++) {
    // set the variable for include.db.controller.php
    $_GET['step'] = $i;

    if (!$args['noninteractive']) {
        progressBar(50, round($i * 100 / ($totalSteps - 1)));
    }

    // execute the db controller step
    ob_start();
    include('lib/include.db.controller.php');
    $output = ob_get_contents();
    ob_end_clean();
}
if ($args['noninteractive']) {
    prnt(i18n('done', 'setup'));
}

echo("\n\r" . i18n('Finishing setup...', 'setup'));

// include the necessary classes for the upgrade jobs
require_once(CON_SETUP_PATH . '/upgrade_jobs/class.upgrade.job.abstract.php');
require_once(CON_SETUP_PATH . '/upgrade_jobs/class.upgrade.job.main.php');

// Execute upgrade jobs
$oUpgradeMain = new cUpgradeJobMain($db, $cfg, $cfgClient, "0");
$oUpgradeMain->_execute();

// write the config.php and finish the setup
ob_start();
include('lib/include.config.controller.php');
$output = ob_get_contents();
ob_end_clean();

prntln(i18n('done', 'setup'));

prntln(i18n('Installation successful!', 'setup'));

?>
