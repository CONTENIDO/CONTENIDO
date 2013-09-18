<?php
/**
 * Several functions to help the cli setup of CONTENIDO
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

/**
 * Prints some text to the console
 *
 * @param string $str string which should be printed
 * @param number $tab number of tab characters which should preceed the string
 */
function prnt($str = '', $tab = 0) {
    for($i = 0; $i < $tab; $i++) {
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
    if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        prntln(i18n('Be careful! The password will be readable in the console window!'));
    }
    prnt($title . ' []: ', $tab);
    $line = trim(fgets(STDIN));
    if(!(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')) {
        echo("\033[1A");
        prnt($title . ' []: ', $tab);
        for($i = 0; $i < strlen($line); $i++) {
            echo(" ");
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
    for($i = 0; $i <= $filled / 100 * $width; $i++) {
        echo("#");
    }
    for($j = $i; $j <= $width; $j++) {
        echo(" ");
    }
    echo("| ");
    prnt(round($filled) . "%");
}

/**
 * Utilizes the other functions to ask the user for all the necessary paramaters for the setup
 */
function gatherConfiguration() {
    global $cfg, $_SESSION;

    // print welcome message
    prntln('>>>>> '. i18n('Welcome to CONTENIDO'). ' <<<<<');
    prntln('');
    prntln(i18n('Database settings:'));

    // database host
    prnt(i18n('Host') . ' [localhost]: ', 1);
    $line = trim(fgets(STDIN));
    $cfg['db']['connection']['host'] = ($line == "") ? "localhost" : $line;

    // database user
    prnt(i18n('User') . ' [root]: ', 1);
    $line = trim(fgets(STDIN));
    $cfg['db']['connection']['user'] = ($line == "") ? "root" : $line;

    $cfg['db']['connection']['password'] = passwordPrompt(i18n('Password'), 1);

    // database name
    prnt(i18n('Database name [contenido]: '), 1);
    $line = trim(fgets(STDIN));
    $cfg['db']['connection']['database'] = ($line == "") ? "contenido" : $line;
    $_SESSION['dbname'] = $cfg['db']['connection']['database'];

    // database charset
    prnt(i18n('Charset [utf8]: '), 1);
    $line = trim(fgets(STDIN));
    $cfg['db']['connection']['charset'] = ($line == "") ? "utf8" : $line;

    // http root path
    prntln();
    prntln(i18n('Please enter the http path to where the contenido/ folder resides'));
    prntln(i18n('e.g. http://localhost/'));
    prnt(i18n('Backend web path []: '));
    $line = trim(fgets(STDIN));
    $_SESSION["override_root_http_path"] = ($line == "") ? "contenido" : $line;

    // ask for the setup mode
    $_SESSION['clientmode'] = "";
    while($_SESSION['clientmode'] == "") {
        prntln(i18n('Do you want to install the example client?'));
        prntln(i18n('Please choose "yes" (to install the modules AND the content), "modules" (to install only the modules) or "no"'));
        prnt(i18n('Examples? (yes/modules/no) [yes]: '));
        $line = strtolower(trim(fgets(STDIN)));
        if($line == 'yes' || $line == '') {
            $_SESSION['clientmode'] = 'CLIENTEXAMPLES';
        } else if($line == 'modules') {
            $_SESSION['clientmode'] = 'CLIENTMODULES';
        } else if($line == 'no') {
            $_SESSION['clientmode'] = 'NOCLIENT';
        }
    }

    // admin password
    $password1 = "something";
    $password2 = "something else";
    prntln(i18n('Admin information:'));
    while($password1 != $password2) {
        prntln(i18n('You have to enter the password twice and they have to match'), 1);
        $password1 = passwordPrompt(i18n('Admin password'), 1);

        $password2 = passwordPrompt(i18n('Repeat admin password'), 1);
    }
    $_SESSION['adminpass'] = $password1;

    // admin email
    prnt(i18n('Admin email []: '), 1);
    $line = trim(fgets(STDIN));
    $_SESSION['adminmail'] = ($line == "") ? "" : $line;

    // print thank you
    prntln(i18n('Thank you.'));
    prntln();
    prntln();
}

/**
 * Reads the specified ini file and saves the values in the appropriate places
 *
 * @param string $file path to the ini file
 */
function readAutoinstallINI($file) {
    global $cfg, $_SESSION;

    $iniarray = parse_ini_file($file, true);

    $cfg['db'] = array(
        'connection' => array(
            'host'     => $iniarray['database']['host'],
            'user'     => $iniarray['database']['user'],
            'password' => $iniarray['database']['password'],
            'charset'  => $iniarray['database']['charset'],
            'database' => $iniarray['database']['database']
        ),
        'haltBehavior'    => 'report',
        'haltMsgPrefix'   => (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] . ' ' : '',
        'enableProfiling' => false
    );
    $_SESSION['setuptype'] = 'setup';
    $_SESSION['dbname'] = $iniarray['database']['database'];
    $_SESSION['configmode'] = 'save';
    $_SESSION['override_root_http_path'] = $iniarray['paths']['http_root_path'];
    $_SESSION['clientmode'] = $iniarray['setup']['client_mode'];
    $_SESSION['adminpass'] = $iniarray['admin_user']['password'];
    $_SESSION['adminmail'] = $iniarray['admin_user']['email'];
}

/**
 * Checks to see if all the installation settings are valid and have been entered
 *
 * @return boolean true if every setting has been entered
 */
function checkInstallationSettings() {
    global $cfg, $_SESSION;

    $fine = true;

    if($cfg['db']['connection']['host'] == '') {
        prntln(i18n('You did not specify a database host!'));
        $fine = false;
    }
    if($cfg['db']['connection']['user'] == '') {
        prntln(i18n('You did not specify a database user!'));
        $fine = false;
    }
    if($cfg['db']['connection']['password'] == '') {
        prntln(i18n('You did not specify a database password!'));
        $fine = false;
    }
    if($cfg['db']['connection']['charset'] == '') {
        prntln(i18n('You did not specify a database charset!'));
        $fine = false;
    }
    if($cfg['db']['connection']['database'] == '') {
        prntln(i18n('You did not specify a database name!'));
        $fine = false;
    }

    // append a slash to the http path if it isn't there already
    if(!(substr($_SESSION['override_root_http_path'], -strlen("/")) === "/")) {
        $_SESSION['override_root_http_path'] = $_SESSION['override_root_http_path'] . "/";
    }
    if($_SESSION['override_root_http_path'] == '') {
        prntln(i18n('You did not specify an http root path!'));
        $fine = false;
    }
    if($_SESSION['clientmode'] == '') {
        prntln(i18n('You did not specify if you want to install the example client or not!'));
        $fine = false;
    }
    if(!($_SESSION['clientmode'] == "CLIENTEXAMPLES" || $_SESSION['clientmode'] == "CLIENTMODULES" || $_SESSION['clientmode'] == "NOCLIENT")) {
        prntln(i18n('You did not specify if you want to install the example client or not!'));
        $fine = false;
    }
    if($_SESSION['adminpass'] == '') {
        prntln(i18n('You did not specify an admin password!'));
        $fine = false;
    }
    if($_SESSION['adminmail'] == '') {
        prntln(i18n('You did not specify an admin email!'));
        $fine = false;
    }

    return $fine;
}

/**
 * Converts unix-style CLI arguments into an array
 *
 * @return array An array representing the arguments and switches provided to the scriüt
 */
function getArgs() {
    $args = $_SERVER['argv'];

    $out = array();
    $last_arg = null;

    for ($i = 1, $il = sizeof($args); $i < $il; $i++) {
        if(preg_match("/^--(.+)/", $args[$i], $match)) {
            $parts = explode("=", $match[1]);
            $key = preg_replace("/[^a-z0-9]+/", "", $parts[0]);

            if (isset($parts[1])) {
                $out[$key] = $parts[1];
            } else {
                $out[$key] = true;
            }
            $last_arg = $key;
        } else if(preg_match("/^-([a-zA-Z0-9]+)/", $args[$i], $match)) {
            for ($j = 0, $jl = strlen($match[1]); $j < $jl; $j++) {
                $key = $match[1]{$j};
                $out[$key] = true;
            }
            $last_arg = $key;
        } else if($last_arg !== null) {
            $out[$last_arg] = $args[$i];
        }
    }
    return $out;
}

/**
 * Executes the CONTENIDO system tests and prints the result to the user
 * In case of an error it asks if the user wants to continue anyway and,
 * if not, quits the script.
 *
 */
function executeSystemTests() {
    global $cfg, $args;

    $fine = true;
    $test = new cSystemtest($cfg);
    $test->runTests(false);
    $test->testFilesystem(true, false);
    $test->testFrontendFolderCreation();
    $testResults = $test->getResults();
    foreach($testResults as $testResult) {
        if ($testResult["severity"] == cSystemtest::C_SEVERITY_NONE) {
            continue;
        }

        if($testResult['result'] == false) {
            $fine = false;
        }
    }
    if(!$fine) {
        prntln(i18n('error'));
        foreach($testResults as $testResult) {
            if ($testResult["severity"] == cSystemtest::C_SEVERITY_NONE) {
                continue;
            }

            if($testResult['result'] == false) {
                prntln(html_entity_decode(strip_tags($testResult['headline'], 1)));
                prntln(html_entity_decode(strip_tags($testResult['message'], 2)));
            }
        }
        prntln();
        prntln(i18n('There have been errors during the system check.'));
        prntln(i18n('However this might be caused by differing configurations between the CLI php and the CGI php.'));

        if($args['noninteractive']) {
            exit(3);
        }

        $answer = '';
        while($answer != 'y' && $answer != 'n') {
            prnt(i18n('Do you want to continue despite the errors? (y/n) [n]: '));
            $answer = trim(fgets(STDIN));
            if($answer == "") {
                $answer = "n";
            }
        }
        if(strtolower($answer) == "n") {
            exit(3);
        }
    } else {
        prntln(i18n('Your system seems to be okay.'));
        prntln();
    }
}

/**
 * Prints the help text
 */
function printHelpText() {

}

?>
