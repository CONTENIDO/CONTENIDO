#!/usr/bin/php
<?php
/**
 * This is the automated install script for CONTENIDO
 *
 * @package Setup
 * @subpackage Setup
 * @version SVN Revision $Rev:$
 *
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

// first, check if the file is being called by the CLI
if(PHP_SAPI != 'cli') {
    die('This program is suppsoed to be run from the command line.');
}

echo('startup...');

if(!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// include the console helper functions
include_once('lib/functions.cliinstall.php');

// parse the arguments of the script and store them in an array
$args = getArgs();
$belang = ($args['locale']) ? $args['locale'] : "en_US"; // setting the language
$args['interactive'] = ($args['interactive']) ? true : $args['i']; // -i can be used instead of --interactive

// the setup will search for autoinstall.ini first or uses the file which is passed to the script with the --ini switch
$settingsFile = "autoinstall.ini";
if(isset($args['ini'])) {
    $settingsFile = $args['ini'];
}

// if the user used -h or --help, print the help text and exit
if($args['h'] || $args['help']) {
    printHelpText();
    exit(0);
}

// define the necessary constants and include the setup startup.php
define('CON_SETUP_PATH', str_replace('\\', '/', realpath(dirname(__FILE__))));
define('CON_FRONTEND_PATH', str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')));
include_once('lib/startup.php');

prntln("\r" . i18n('This program will install CONTENIDO on this computer.'));

// initialize the variables we will need to make sure they are all set to an empty value or their standard value
$cfg['db'] = array(
    'connection' => array(
        'host'     => '',
        'user'     => '',
        'password' => '',
        'charset'  => '',
        'database' => ''
    ),
    'haltBehavior'    => 'report',
    'haltMsgPrefix'   => (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] . ' ' : '',
    'enableProfiling' => false
);
$_SESSION['setuptype'] = 'setup';
$_SESSION['dbname'] = '';
$_SESSION['configmode'] = 'save';
$_SESSION["override_root_http_path"] = '';
$_SESSION['clientmode'] = '';
$_SESSION['adminpass'] = '';
$_SESSION['adminmail'] = '';

// first check for the interactive switch
if($args['interactive']) {
    // user wants the setup to be interactive - ignore any ini files and start the configuration
    prntln();
    prntln();
    gatherConfiguration();
} else if($args['noninteractive']) {
    // user does not want the setup to be interactive - look for the ini file
    echo(i18n('Looking for ' . $settingsFile . '...'));
    if(file_exists($settingsFile)) {
        // file found - read the settings and start installing
        prntln(i18n('found'));
        prntln(i18n('CONTENIDO will use the specified settings from ' . $settingsFile));
        prntln();
        readAutoinstallINI($settingsFile);
    } else {
        // file not found - print error message and exit, since the user specifically said he wanted to use the file
        prntln(i18n('not found'));
        prntln(i18n('CONTENIDO was unable to locate the configuration file "' . $settingsFile . '", but you asked to use it.'));
        prntln(i18n('Setup can not continue.'));
        exit(1);
    }
} else {
    // default mode - look for the ini file. if it's there, use it. Otherwise start the interactive setup
    echo(i18n('Looking for ' . $settingsFile . '...'));
    if(file_exists($settingsFile)) {
        // read the ini file
        prntln(i18n('found'));
        prntln(i18n('CONTENIDO will use the specified settings from ' . $settingsFile));
        prntln();
        readAutoinstallINI($settingsFile);
    } else {
        // start the interactive setup
        prntln(i18n('not found'));
        prntln();
        prntln();
        gatherConfiguration();
    }
}

// check every parameter that is needed for the setup
if(!checkInstallationSettings()) {
    prntln(i18n('The setup can not continue with missing information.'));
    exit(2);
}

prnt(i18n('Testing your system...'));

// execute the system tests
executeSystemTests();

// if we are here, the user either ignored errors or everything is fine - start the installation
prntln(i18n('Starting setup'));
prnt(i18n('Writing data...'));
if(!$args['noninteractive']) { // print the fancy progress bar only if the user did not specify the non-interactive mode
    prntln();
    progressBar(50, 0);
}

// include the db controller for the first time. It will calculate the totalsteps needed and execute the first step
ob_start();
include('lib/include.db.controller.php');
$output = ob_get_contents();
ob_end_clean();

// stat the loop until totalSteps is reached
for($i = 2; $i < $totalSteps; $i++) {
    // set the variable for include.db.controller.php
    $_GET['step'] = $i;

    if(!$args['noninteractive']) {
        progressBar(50, round($i * 100 / ($totalSteps - 1)));
    }

    // execute the db controller step
    ob_start();
    include('lib/include.db.controller.php');
    $output = ob_get_contents();
    ob_end_clean();
}
if($args['noninteractive']) {
    prnt(i18n('done'));
}

echo("\n\r" . i18n('Finishing setup...'));

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

prntln(i18n('done'));

prntln(i18n('Installation successful!'));

?>
