<?php
/**
 * Several functions to help the cli setup of CONTENIDO
 *
 * @package Setup
 * @subpackage Setup
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Prints some text to the console
 *
 * @param string $str string which should be printed
 * @param number $tab number of tab characters which should preceed the string
 */
function prnt($str = '', $tab = 0) {
    for ($i = 0; $i < $tab; $i++) {
        echo("\t");
    }
    echo($str);
}

/**
 * Prints some text and a new line to the console
 *
 * @param string $str string which should be printed
 * @param number $tab number of tab characters which should preceed the string
 */
function prntln($str = '', $tab = 0) {
    prnt($str . "\n\r", $tab);
}

/**
 * Prints some text to the console and jumps back to the beginning of the line
 *
 * @param string $str string which should be printed
 */
function prntst($str = '') {
    echo($str . "\r");
}

/**
 * Ask the user for a password and erase it if he is done entering it.
 * Since the Windows console does not understand ANSI sequences the
 * function will print a warning for the user instead.
 *
 * @param string $title label text
 * @param number $tab number of tabs which should preceed the label text
 * @return string user entered password
 */
function passwordPrompt($title, $tab = 0) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        prntln(i18n('Be careful! The password will be readable in the console window!', 'setup'), $tab);
    }
    prnt($title . ': ', $tab);
    $line = trim(fgets(STDIN));
    if (!(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')) {
        echo("\033[1A"); // move the cursor up one line
        prnt($title . ': ', $tab); // reprint the label
        for ($i = 0; $i < strlen($line); $i++) {
            echo("*"); // replace the password with asterisks
        }
        echo("\r\n");
    }
    return $line;
}

/**
 * Prints a progress bar to the console
 *
 * @param int $width Widht of the progress bar in characters
 * @param int $filled Percent value to which it should be filled (e.g. 45)
 */
function progressBar($width, $filled) {
    echo("\r");
    echo("|");
    $i = 0;
    for ($i = 0; $i <= $filled / 100 * $width; $i++) {
        echo("#");
    }
    for ($j = $i; $j <= $width; $j++) {
        echo(" ");
    }
    echo("| ");
    prnt(round($filled) . "%");
}

/**
 * Initializes the globals the CONTENIDO setup needs
 */
function initializeVariables() {
    global $cfg, $_SESSION;

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
    $_SESSION['dbprefix'] = '';
}

/**
 * Checks to see if all the installation settings are valid and have been entered
 *
 * @return boolean true if every setting has been entered
 */
function checkInstallationSettings() {
    global $cfg, $_SESSION;

    $fine = true;

    if ($cfg['db']['connection']['host'] == '') {
        prntln(i18n('You did not specify a database host!', 'setup'));
        $fine = false;
    }
    if ($cfg['db']['connection']['user'] == '') {
        prntln(i18n('You did not specify a database user!', 'setup'));
        $fine = false;
    }
    if ($cfg['db']['connection']['password'] == '') {
        prntln(i18n('You did not specify a database password!', 'setup'));
        $fine = false;
    }
    if ($cfg['db']['connection']['charset'] == '') {
        prntln(i18n('You did not specify a database charset!', 'setup'));
        $fine = false;
    }
    if ($_SESSION['dbcollation'] == '') {
        prntln(i18n('You did not specify a database collation!', 'setup'));
        $fine = false;
    }
    if ($cfg['db']['connection']['database'] == '') {
        prntln(i18n('You did not specify a database name!', 'setup'));
        $fine = false;
    }
    if ($_SESSION['dbprefix'] == '') {
        prntln(i18n('You did not specify a database prefix!', 'setup'));
        $fine = false;
    }

    // append a slash to the http path if it isn't there already
    if (!(substr($_SESSION['override_root_http_path'], -strlen("/")) === "/")) {
        $_SESSION['override_root_http_path'] = $_SESSION['override_root_http_path'] . "/";
    }
    if ($_SESSION['override_root_http_path'] == '') {
        prntln(i18n('You did not specify an http root path!', 'setup'));
        $fine = false;
    }
    if ($_SESSION['clientmode'] == '') {
        prntln(i18n('You did not specify if you want to install the example client or not!', 'setup'));
        $fine = false;
    }
    if (!($_SESSION['clientmode'] == "CLIENTEXAMPLES" || $_SESSION['clientmode'] == "CLIENTMODULES" || $_SESSION['clientmode'] == "NOCLIENT")) {
        prntln(i18n('You did not specify if you want to install the example client or not!', 'setup'));
        $fine = false;
    }
    if ($_SESSION['adminpass'] == '') {
        prntln(i18n('You did not specify an admin password!', 'setup'));
        $fine = false;
    }
    if ($_SESSION['adminmail'] == '') {
        prntln(i18n('You did not specify an admin email!', 'setup'));
        $fine = false;
    }

    return $fine;
}

/**
 * Converts unix-style CLI arguments into an array
 *
 * @return array An array representing the arguments and switches provided to the script
 */
function getArgs() {
    $args = $_SERVER['argv'];

    $out = array();
    $last_arg = null;

    for ($i = 1, $il = sizeof($args); $i < $il; $i++) {
        if (preg_match("/^--(.+)/", $args[$i], $match)) {
            $parts = explode("=", $match[1]);
            $key = preg_replace("/[^a-z0-9]+/", "", $parts[0]);

            if (isset($parts[1])) {
                $out[$key] = $parts[1];
            } else {
                $out[$key] = true;
            }
            $last_arg = $key;
        } else if (preg_match("/^-([a-zA-Z0-9]+)/", $args[$i], $match)) {
            for ($j = 0, $jl = strlen($match[1]); $j < $jl; $j++) {
                $key = $match[1]{$j};
                $out[$key] = true;
            }
            $last_arg = $key;
        } else if ($last_arg !== null) {
            $out[$last_arg] = $args[$i];
        }
    }
    return $out;
}

/**
 * Prints the help text
 */
function printHelpText() {
    prntln("\r" . i18n('CONTENIDO setup script', 'setup'));
    prntln(i18n('This script will install CONTENIDO to your computer', 'setup'));
    prntln();
    prntln(i18n("CONTENIDO can be installed using the interactive mode or a non-interactive mode.", 'setup'));
    prntln(i18n("If the script is executed without any parameters it will look for the\nautoinstall.ini file (or any other file specified with \"--file\").", 'setup'));
    prntln(i18n("If that file is present, the non-interactive mode will be used. The script will\nask for user input in the case of errors though.\nIf you want to prevent the script from ever waiting for user input use\n\"--non-interactive\".", 'setup'));
    prntln(i18n("In case the ini file can not be found, the script will wait for user input\non all relevant information.", 'setup'));
    prntln();
    prntln('--interactive, -i');
    prntln(i18n("Forces the script to be interactive and wait for user input even if the\n\tautoinstall.ini file is present.", 'setup'), 1);
    prntln('--non-interactive');
    prntln(i18n("Will prevent all waiting for user input. The script will abort\n\tin case of any errors", 'setup'), 1);
    prntln('--file [' . i18n('file', 'setup') . ']');
    prntln(i18n('Use [file] instead of the default autoinstall.ini.', 'setup'), 1);
    prntln('--locale [' . i18n('language code', 'setup') . ']');
    prntln(i18n("Provide a country and language code to use. Defaults to \"en_US\"", 'setup'), 1);
    prntln('--help, -h');
    prntln(i18n('Prints this help text', 'setup'), 1);
    prntln();
    prntln(i18n("Furthermore, you can use parameters to overwrite setup settings.\nUse \"--[ini-group].[ini-name]=\"value\" (e.g. --db.host=\"localhost\")", 'setup'));
    prntln();
    prntln('CONTENIDO version ' . CON_SETUP_VERSION);
}

?>
