<?php

/**
 * Provides functions to gather the necessary settings for autoinstall.php
 *
 * @package    Setup
 * @subpackage Setup
 * @author     Mischa Holz
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Provides functions to gather the necessary settings for autoinstall.php
 *
 * @package    Setup
 * @subpackage Setup
 */
class cCLISetup {

    /**
     * holds the setup settings
     * @var array
     */
    protected $_settings;

    /**
     * holds the command line options
     * @var array
     */
    protected $_args;

    /**
     * path to the settings files
     * @var string
     */
    protected $_settingsFile;

    /**
     * Initiliazes the class and sets standard values for some settings
     *
     * @param array $args the parsed command line
     */
    public function __construct($args) {
        $this->_settings['db']['host'] = 'localhost';
        $this->_settings['db']['user'] = 'root';
        $this->_settings['db']['password'] = '';
        $this->_settings['db']['charset'] = 'utf8';
        $this->_settings['db']['collation'] = 'utf8_general_ci';
        $this->_settings['db']['database'] = 'contenido';
        $this->_settings['db']['prefix'] = 'con';
        $this->_settings['db']['option_mysqli_init_command'] = CON_SETUP_DB_OPTION_MYSQLI_INIT_COMMAND;

        $this->_settings['paths']['http_root_path'] = '';

        $this->_settings['setup']['client_mode'] = '';

        $this->_settings['admin_user']['password'] = '';
        $this->_settings['admin_user']['email'] = '';

        $this->_args = $args;

        $this->_args['interactive'] = isset($this->_args['interactive']) || isset($this->_args['i']); // -i can be used instead of --interactive

        // the setup will search for autoinstall.ini first or use the file which is passed to the script with the --file switch
        $this->_settingsFile = 'autoinstall.ini';
    }

    /**
     * Reads all parameters and gathers the settings for the installation accordingly
     */
    public function interpretCommandline() {
        global $belang;

        $belang = ($this->_args['locale']) ? $this->_args['locale'] : "en_US"; // setting the language

        cI18n::init(CON_SETUP_PATH . '/locale/', $belang, 'setup');

        // check if we just need to print the help text
        if (isset($this->_args['h']) || $this->_args['help']) {
            printHelpText();
            exit(0);
        }

        // set the configuration file
        if (isset($this->_args['file'])) {
            $this->_settingsFile = $this->_args['file'];
        }

        prntln("\r" . i18n('This program will install CONTENIDO on this computer.', 'setup'));

        // first check for the interactive switch
        if ($this->_args['interactive']) {
            // user wants the setup to be interactive - ignore any files and start the configuration
            prntln();
            prntln();
            // the settings from the command line overwrite the settings but the UI settings overwrite everything
            // settings from the command line and the file (if existent) will be provided to the user as standard values for the questions
            $this->getSettingsFromFile($this->_settingsFile);
            $this->getSettingsFromCommandLine();
            $this->getUserInputSettings();
        } else if ($this->_args['noninteractive']) {
            // user does not want the setup to be interactive - look for the file
            echo(i18n('Looking for ', 'setup') . $this->_settingsFile . '...');
            if (file_exists($this->_settingsFile)) {
                // file found - read the settings and start installing
                prntln(i18n('found', 'setup'));
                prntln(i18n('CONTENIDO will use the specified settings from ', 'setup') . $this->_settingsFile);
                prntln();
                $this->getSettingsFromFile($this->_settingsFile);
                $this->getSettingsFromCommandLine();
                $this->printSettings();
            } else {
                // file not found - print error message and exit, since the user specifically said he wanted to use the file
                prntln(i18n('not found', 'setup'));
                prntln(sprintf(i18n('CONTENIDO was unable to locate the configuration file %s, but you asked to use it.', 'setup'), $this->_settingsFile));
                prntln(i18n('Setup can not continue.', 'setup'));
                exit(1);
            }
        } else {
            // default mode - look for the file. if it's there, use it. Otherwise, start the interactive setup
            echo(i18n('Looking for ', 'setup') . $this->_settingsFile . '...');
            if (file_exists($this->_settingsFile)) {
                // read the file
                prntln(i18n('found', 'setup'));
                prntln(i18n('CONTENIDO will use the specified settings from ', 'setup') . $this->_settingsFile);
                prntln();
                $this->getSettingsFromFile($this->_settingsFile);
                $this->getSettingsFromCommandLine();
                $this->printSettings();
            } else {
                // start the interactive setup
                prntln(i18n('not found', 'setup'));
                prntln();
                prntln();
                $this->getSettingsFromFile($this->_settingsFile);
                $this->getSettingsFromCommandLine();
                $this->getUserInputSettings();
            }
        }
    }

    /**
     * Prints the settings json encoded to the console but removes passwords from it
     */
    public function printSettings() {
        prntln(i18n('CONTENIDO will be installed with the following settings: ', 'setup'));
        $noPasswordArray = $this->_settings;
        $noPasswordArray['db']['password'] = '**********';
        $noPasswordArray['admin_user']['password'] =  '**********';
        prntln(json_encode($noPasswordArray));
    }

    /**
     * Read the settings from various parameters from the command line
     */
    public function getSettingsFromCommandLine() {
        $this->_settings['db']['host'] = ($this->_args['dbhost'] == '') ? $this->_settings['db']['host'] : $this->_args['dbhost'];
        $this->_settings['db']['user'] = ($this->_args['dbuser'] == '') ? $this->_settings['db']['user'] : $this->_args['dbuser'];
        $this->_settings['db']['password'] = ($this->_args['dbpassword'] == '') ? $this->_settings['db']['password'] : $this->_args['dbpassword'];
        $this->_settings['db']['charset'] = ($this->_args['dbcharset'] == '') ? $this->_settings['db']['charset'] : $this->_args['dbcharset'];
        $this->_settings['db']['collation'] = ($this->_args['dbcollation'] == '') ? $this->_settings['db']['collation'] : $this->_args['dbcollation'];
        $this->_settings['db']['database'] = ($this->_args['dbdatabase'] == '') ? $this->_settings['db']['database'] : $this->_args['dbdatabase'];
        $this->_settings['db']['prefix'] = ($this->_args['dbprefix'] == '') ? $this->_settings['db']['prefix'] : $this->_args['dbprefix'];
        $this->_settings['db']['option_mysqli_init_command'] = ($this->_args['dboptionmysqliinitcommand'] == '') ? $this->_settings['db']['option_mysqli_init_command'] : $this->_args['dboptionmysqliinitcommand'];

        $this->_settings['paths']['http_root_path'] = ($this->_args['pathshttprootpath'] == '') ? $this->_settings['paths']['http_root_path'] : $this->_args['pathshttprootpath'];

        $this->_settings['setup']['client_mode'] = ($this->_args['setupclientmode'] == '') ? $this->_settings['setup']['client_mode'] : $this->_args['setupclientmode'];

        $this->_settings['admin_user']['password'] = ($this->_args['adminuserpassword'] == '') ? $this->_settings['admin_user']['password'] : $this->_args['adminuserpassword'];
        $this->_settings['admin_user']['email'] = ($this->_args['adminuseremail'] == '') ? $this->_settings['admin_user']['email'] : $this->_args['adminuseremail'];
        $this->_settings['advanced']['delete_database'] = ($this->_args['advanceddeletedatabase'] == '') ? $this->_settings['advanced']['delete_database'] : $this->_args['advanceddeletedatabase'];
    }

    /**
     * Start a dialog with the user to get the settings
     */
    public function getUserInputSettings() {
        // print welcome message
        prntln('>>>>> '. i18n('Welcome to CONTENIDO', 'setup'). ' <<<<<');
        prntln('');
        prntln(i18n('Database settings:', 'setup'));

        // database host
        prnt(i18n('Host', 'setup') . ' [' . $this->_settings['db']['host'] . ']: ', 1);
        $line = trim(fgets(STDIN));
        $this->_settings['db']['host'] = ($line == "") ? $this->_settings['db']['host'] : $line;

        // database user
        prnt(i18n('User', 'setup') . ' [' . $this->_settings['db']['user'] . ']: ', 1);
        $line = trim(fgets(STDIN));
        $this->_settings['db']['user'] = ($line == "") ? $this->_settings['db']['user'] : $line;

        // database password
        $dbpw = passwordPrompt(i18n('Password', 'setup'), 1);
        $this->_settings['db']['password'] = ($dbpw == '') ? $this->_settings['db']['password'] : $dbpw;

        // database name
        prnt(i18n('Database name', 'setup') .' [' . $this->_settings['db']['database'] . ']: ', 1);
        $line = trim(fgets(STDIN));
        $this->_settings['db']['database'] = ($line == "") ? $this->_settings['db']['database'] : $line;

        // database charset
        prnt(i18n('Charset', 'setup') . ' [' . $this->_settings['db']['charset'] . ']: ', 1);
        $line = trim(fgets(STDIN));
        $this->_settings['db']['charset'] = ($line == "") ? $this->_settings['db']['charset'] : $line;

        // database collation
        prnt(i18n('Collation', 'setup') . ' [' . $this->_settings['db']['collation'] . ']: ', 1);
        $line = trim(fgets(STDIN));
        $this->_settings['db']['collation'] = ($line == "") ? $this->_settings['db']['collation'] : $line;

        // database prefix
        prnt(i18n('Prefix', 'setup') . ' [' . $this->_settings['db']['prefix'] . ']: ', 1);
        $line = trim(fgets(STDIN));
        $this->_settings['db']['prefix'] = ($line == "") ? $this->_settings['db']['prefix'] : $line;

        // Database option MYSQLI_INIT_COMMAND
        prnt(i18n('Database option MYSQLI_INIT_COMMAND', 'setup') . ' [' . $this->_settings['db']['option_mysqli_init_command'] . ']: ', 1);
        $line = trim(fgets(STDIN));
        $this->_settings['db']['option_mysqli_init_command'] = ($line == "") ? $this->_settings['db']['option_mysqli_init_command'] : $line;

        // http root path
        prntln();
        prntln(i18n('Please enter the http path to where the contenido/ folder resides', 'setup'));
        prntln(i18n('e.g. http://localhost/', 'setup'));
        prnt(i18n('Backend web path', 'setup') .' [' . $this->_settings['paths']['http_root_path'] . ']: ', 1);
        $line = trim(fgets(STDIN));
        $this->_settings['paths']['http_root_path'] = ($line == "") ? $this->_settings['paths']['http_root_path'] : $line;

        // ask for the setup mode
        prntln();
        $displayStandard = i18n('yes', 'setup');
        if ($this->_settings['setup']['client_mode'] == 'CLIENTMODULES') {
            $displayStandard = i18n('modules', 'setup');
        } else if ($this->_settings['setup']['client_mode'] == 'NOCLIENT') {
            $displayStandard = i18n('no', 'setup');
        }
        $first = true;
        while ($this->_settings['setup']['client_mode'] == "" || $first) {
            $first = false;
            prntln(i18n('Do you want to install the example client?', 'setup'));
            prntln(i18n('Please choose "yes" (to install the modules AND the content), "modules" (to install only the modules) or "no"', 'setup'));
            prnt(i18n('Examples? (yes/modules/no)', 'setup') . '[' . $displayStandard . ']: ', 1);
            $line = cString::toLowerCase(trim(fgets(STDIN)));
            if ($line == "") {
                $line = $displayStandard;
            }
            if ($line == 'yes' || $line == i18n('yes', 'setup')) {
                $this->_settings['setup']['client_mode'] = 'CLIENTEXAMPLES';
            } else if ($line == 'modules' || $line == i18n('modules', 'setup')) {
                $this->_settings['setup']['client_mode'] = 'CLIENTMODULES';
            } else if ($line == 'no' || $line == i18n('no', 'setup')) {
                $this->_settings['setup']['client_mode'] = 'NOCLIENT';
            }
        }

        // admin password
        $password1 = "something";
        $password2 = "something else";
        prntln();
        prntln(i18n('Admin information:'));
        while ($password1 != $password2) {
            prntln(i18n('You have to enter the password twice and they have to match', 'setup'), 1);
            $password1 = passwordPrompt(i18n('Admin password', 'setup'), 1);

            $password2 = passwordPrompt(i18n('Repeat admin password', 'setup'), 1);
        }
        $this->_settings['admin_user']['password'] = ($password1 == '') ? $this->_settings['admin_user']['password'] : $password1;

        // admin email
        prnt(i18n('Admin email', 'setup') . ' [' . $this->_settings['admin_user']['email'] . ']: ', 1);
        $line = trim(fgets(STDIN));
        $this->_settings['admin_user']['email'] = ($line == "") ? $this->_settings['admin_user']['email'] : $line;

        // print thank you
        prntln(i18n('Thank you.', 'setup'));
        prntln();
        prntln();
    }

    /**
     * Reads the specified file and saves the values in the appropriate places
     *
     * @param string $file path to the file
     */
    public function getSettingsFromFile($file) {
        if (!cFileHandler::exists($file)) {
            return;
        }

        $ext = cString::toLowerCase(pathinfo($file, PATHINFO_EXTENSION));

        switch ($ext) {
            case 'ini':
                $this->_settings = parse_ini_file($file, true);
                break;
            case 'xml':
                $xml = simplexml_load_file($file);
                if (!$xml) {
                    return;
                }

                $this->_settings['db']['host'] = trim($xml->db->host);
                $this->_settings['db']['user'] = trim($xml->db->user);
                $this->_settings['db']['password'] = trim($xml->db->password);
                $this->_settings['db']['charset'] = trim($xml->db->charset);
                $this->_settings['db']['database'] = trim($xml->db->database);
                $this->_settings['db']['prefix'] = trim($xml->db->prefix);
                $this->_settings['db']['collation'] = trim($xml->db->collation);
                $this->_settings['db']['option_mysqli_init_command'] = trim($xml->db->option_mysqli_init_command);

                $this->_settings['paths']['http_root_path'] = trim($xml->path->http_root_path);

                $this->_settings['setup']['client_mode'] = trim($xml->client->client_mode);

                $this->_settings['admin_user']['password'] = trim($xml->admin_user->password);
                $this->_settings['admin_user']['email'] = trim($xml->admin_user->email);
                $this->_settings['advanced']['delete_database'] = trim($xml->advanced->delete_database);
                break;
            case 'json':
                $this->_settings = json_decode(file_get_contents($file), true);
                break;
        }

    }

    /**
     * Executes the CONTENIDO system tests and prints the result to the user.
     * In case of an error it asks if the user wants to continue anyway and,
     * if not, quits the script.
     *
     */
    public function executeSystemTests() {
        global $args, $belang;

        $cfg = cRegistry::getConfig();

        if ($this->_settings['advanced']['delete_database'] == '1' || $this->_settings['advanced']['delete_database'] == 'YES') {
            $answer = '';
            while ($answer != 'Y' && $answer != 'N') {
                prnt(sprintf(i18n("You chose in the configuration file to delete the database '%s' before installing.\nDO YOU REALLY WANT TO CONTINUE WITH DELETING THIS DATABASE? (Y/N) [N]: ", 'setup'), $this->_settings['db']['database']));
                $answer = trim(fgets(STDIN));
                if ($answer == "") {
                    $answer = "N";
                }
            }
            if ($answer != "Y") {
                exit(3);
            }

            $db = getSetupMySQLDBConnection(false);
            $db->query('DROP DATABASE `%s`', $this->_settings['db']['database']);

            prntln();
            prntln(sprintf(i18n('THE DATABASE %s HAS BEEN DELETED!!!', 'setup'), $this->_settings['db']['database']));
            prntln();
        }

        prnt(i18n('Testing your system...', 'setup'));

        $fine = true;

        // load the CONTENIDO locale for the test results
        i18nInit('../data/locale/', $belang);

        // run the tests
        $test = new cSystemtest($cfg, $_SESSION['setuptype']);
        $test->runTests(false); // general php tests
        $test->testFilesystem(true, false); // file system permission tests
        $test->testFrontendFolderCreation(); // more file system permission tests
        $test->checkSetupMysql('setup', $cfg['db']['connection']['database'], $_SESSION['dbprefix'], $_SESSION['dbcharset'], $_SESSION['dbcollation']); // test the SQL connection and database creation

        $testResults = $test->getResults();

        foreach ($testResults as $testResult) {
            if ($testResult["severity"] == cSystemtest::C_SEVERITY_NONE) {
                continue;
            }

            if ($testResult['result'] == false) {
                $fine = false;
            }
        }
        if (!$fine) {
            prntln(i18n('error', 'setup'));
            foreach ($testResults as $testResult) {
                if ($testResult["severity"] == cSystemtest::C_SEVERITY_NONE) {
                    continue;
                }

                if ($testResult['result'] == false) {
                    prntln(html_entity_decode(strip_tags($testResult['headline'], 1)));
                    prntln(html_entity_decode(strip_tags($testResult['message'], 2)));
                }
            }
            prntln();
            prntln(i18n('There have been errors during the system check.', 'setup'));
            prntln(i18n('However this might be caused by differing configurations between the CLI php and the CGI php.', 'setup'));

            if ($args['noninteractive']) {
                exit(3);
            }

            $answer = '';
            while ($answer != 'y' && $answer != 'n' && $answer != i18n('y', 'setup') && $answer != i18n('n', 'setup')) {
                prnt(i18n('Do you want to continue despite the errors? (y/n) [n]: ', 'setup'));
                $answer = trim(fgets(STDIN));
                if ($answer == "") {
                    $answer = "n";
                }
            }
            if (cString::toLowerCase($answer) == "n" || cString::toLowerCase($answer) == cString::toLowerCase(i18n('n', 'setup'))) {
                exit(3);
            }
        } else {
            prntln(i18n('Your system seems to be okay.', 'setup'));
            prntln();
        }
    }

    /**
     * Take the settings from the settings array and write them to the appropriate places
     */
    public function applySettings() {
        // NOTE: Use global $cfg variable!
        global $cfg;

        $cfg['db'] = [
            'connection' => [
                'host'     => $this->_settings['db']['host'],
                'user'     => $this->_settings['db']['user'],
                'password' => $this->_settings['db']['password'],
                'charset'  => $this->_settings['db']['charset'],
                'database' => $this->_settings['db']['database'],
                'options'  => [],
            ],
            'haltBehavior'    => 'report',
            'haltMsgPrefix'   => (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] . ' ' : '',
            'enableProfiling' => false
        ];
        if (!empty($this->_settings['db']['option_mysqli_init_command'])) {
            $cfg['db']['connection']['options'][MYSQLI_INIT_COMMAND] = $this->_settings['db']['option_mysqli_init_command'];
        }

        $_SESSION['setuptype'] = 'setup';
        $_SESSION['dbname'] = $this->_settings['db']['database'];
        $_SESSION['configmode'] = 'save';
        $_SESSION['override_root_http_path'] = $this->_settings['paths']['http_root_path'];
        $_SESSION['clientmode'] = $this->_settings['setup']['client_mode'];
        $_SESSION['adminpass'] = $this->_settings['admin_user']['password'];
        $_SESSION['adminmail'] = $this->_settings['admin_user']['email'];
        $_SESSION['dbprefix'] = $this->_settings['db']['prefix'];
        $_SESSION['dbcollation'] = $this->_settings['db']['collation'];
        $_SESSION['dbcharset'] = $this->_settings['db']['charset'];
        $_SESSION['dboptions'] = [];
        if (!empty($this->_settings['db']['option_mysqli_init_command'])) {
            $_SESSION['dboptions'][MYSQLI_INIT_COMMAND] = $this->_settings['db']['option_mysqli_init_command'];
        }
        $cfg['sql']['sqlprefix'] = $this->_settings['db']['prefix'];
        // reload cfg_sql.inc.php because new sql prefix will change resulting array data
        include($cfg['path']['contenido_config'] . 'cfg_sql.inc.php');
    }
}
