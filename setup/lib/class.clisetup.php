<?php
/**
 * Provides functions to gather the necessary settings for autoinstall.php
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
 * Provides functions to gather the necessary settings for autoinstall.php
 *
 * @package Setup
 * @subpackage Setup
 */
class cCLISetup {

    /**
     * holds the setup settings
     * @var array
     */
    protected $settings;

    /**
     * holds the command line options
     * @var array
     */
    protected $args;

    /**
     * path to the settings files
     * @var string
     */
    protected $settingsFile;

    /**
     * Initiliazes the class and sets standard values for some settings
     *
     * @param array $args the parsed command line
     */
    public function __construct($args) {
        $settings = array();

        $this->settings['db']['host'] = 'localhost';
        $this->settings['db']['user'] = 'root';
        $this->settings['db']['password'] = '';
        $this->settings['db']['charset'] = 'utf8';
        $this->settings['db']['collation'] = 'utf8_general_ci';
        $this->settings['db']['database'] = 'contenido';
        $this->settings['db']['prefix'] = 'con';

        $this->settings['paths']['http_root_path'] = '';

        $this->settings['setup']['client_mode'] = '';

        $this->settings['admin_user']['password'] = '';
        $this->settings['admin_user']['email'] = '';

        $this->args = $args;

        $this->args['interactive'] = isset($this->args['interactive']) || isset($this->args['i']); // -i can be used instead of --interactive

        // the setup will search for autoinstall.ini first or use the file which is passed to the script with the --file switch
        $this->settingsFile = 'autoinstall.ini';
    }

    /**
     * Reads all parameters and gathers the settings for the installation accordingly
     */
    public function interpretCommandline() {
        global $belang;

        $belang = ($this->args['locale']) ? $this->args['locale'] : "en_US"; // setting the language

        cI18n::init(CON_SETUP_PATH . '/locale/', $belang, 'setup');

        // check if we just need to print the help text
        if (isset($this->args['h']) || $this->args['help']) {
            printHelpText();
            exit(0);
        }

        // set the configuration file
        if (isset($this->args['file'])) {
            $this->settingsFile = $this->args['file'];
        }

        prntln("\r" . i18n('This program will install CONTENIDO on this computer.', 'setup'));

        // first check for the interactive switch
        if ($this->args['interactive']) {
            // user wants the setup to be interactive - ignore any files and start the configuration
            prntln();
            prntln();
            // the settings from the command line overwrite the settings but the UI settings overwrite everything
            // settings from the command line and the file (if existent) will be provided to the user as standard values for the questions
            $this->getSettingsFromFile($this->settingsFile);
            $this->getSettingsFromCommandLine($this->args);
            $this->getUserInputSettings();
        } else if ($this->args['noninteractive']) {
            // user does not want the setup to be interactive - look for the file
            echo(i18n('Looking for ', 'setup') . $this->settingsFile . '...');
            if (file_exists($this->settingsFile)) {
                // file found - read the settings and start installing
                prntln(i18n('found', 'setup'));
                prntln(i18n('CONTENIDO will use the specified settings from ', 'setup') . $this->settingsFile);
                prntln();
                $this->getSettingsFromFile($this->settingsFile);
                $this->getSettingsFromCommandLine($this->args);
                $this->printSettings();
            } else {
                // file not found - print error message and exit, since the user specifically said he wanted to use the file
                prntln(i18n('not found', 'setup'));
                prntln(sprintf(i18n('CONTENIDO was unable to locate the configuration file %s, but you asked to use it.', 'setup'), $this->settingsFile));
                prntln(i18n('Setup can not continue.', 'setup'));
                exit(1);
            }
        } else {
            // default mode - look for the file. if it's there, use it. Otherwise start the interactive setup
            echo(i18n('Looking for ', 'setup') . $this->settingsFile . '...');
            if (file_exists($this->settingsFile)) {
                // read the file
                prntln(i18n('found', 'setup'));
                prntln(i18n('CONTENIDO will use the specified settings from ', 'setup') . $this->settingsFile);
                prntln();
                $this->getSettingsFromFile($this->settingsFile);
                $this->getSettingsFromCommandLine($this->args);
                $this->printSettings();
            } else {
                // start the interactive setup
                prntln(i18n('not found', 'setup'));
                prntln();
                prntln();
                $this->getSettingsFromFile($this->settingsFile);
                $this->getSettingsFromCommandLine($this->args);
                $this->getUserInputSettings();
            }
        }
    }

    /**
     * Prints the settings json encoded to the console but removes passwords from it
     */
    public function printSettings() {
        prntln(i18n('CONTENIDO will be installed with the following settings: ', 'setup'));
        $noPasswordArray = $this->settings;
        $noPasswordArray['db']['password'] = '**********';
        $noPasswordArray['admin_user']['password'] =  '**********';
        prntln(json_encode($noPasswordArray));
    }

    /**
     * Read the settings from various parameters from the command line
     *
     * @param array $args the parsed command line
     */
    public function getSettingsFromCommandLine() {
        $this->settings['db']['host'] = ($this->args['dbhost'] == '') ? $this->settings['db']['host'] : $this->args['dbhost'];
        $this->settings['db']['user'] = ($this->args['dbuser'] == '') ? $this->settings['db']['user'] : $this->args['dbuser'];
        $this->settings['db']['password'] = ($this->args['dbpassword'] == '') ? $this->settings['db']['password'] : $this->args['dbpassword'];
        $this->settings['db']['charset'] = ($this->args['dbcharset'] == '') ? $this->settings['db']['charset'] : $this->args['dbcharset'];
        $this->settings['db']['collation'] = ($this->args['dbcollation'] == '') ? $this->settings['db']['collation'] : $this->args['dbcollation'];
        $this->settings['db']['database'] = ($this->args['dbdatabase'] == '') ? $this->settings['db']['database'] : $this->args['dbdatabase'];
        $this->settings['db']['prefix'] = ($this->args['dbprefix'] == '') ? $this->settings['db']['prefix'] : $this->args['dbprefix'];

        $this->settings['paths']['http_root_path'] = ($this->args['pathshttprootpath'] == '') ? $this->settings['paths']['http_root_path'] : $this->args['pathshttprootpath'];

        $this->settings['setup']['client_mode'] = ($this->args['setupclientmode'] == '') ? $this->settings['setup']['client_mode'] : $this->args['setupclientmode'];

        $this->settings['admin_user']['password'] = ($this->args['adminuserpassword'] == '') ? $this->settings['admin_user']['password'] : $this->args['adminuserpassword'];
        $this->settings['admin_user']['email'] = ($this->args['adminuseremail'] == '') ? $this->settings['admin_user']['email'] : $this->args['adminuseremail'];
        $this->settings['advanced']['delete_database'] = ($this->args['advanceddeletedatabase'] == '') ? $this->settings['advanced']['delete_database'] : $this->args['advanceddeletedatabase'];
    }

    /**
     * Start a dialog with the user to get the settings
     */
    public function getUserInputSettings() {
        $settings = array();

        // print welcome message
        prntln('>>>>> '. i18n('Welcome to CONTENIDO', 'setup'). ' <<<<<');
        prntln('');
        prntln(i18n('Database settings:', 'setup'));

        // database host
        prnt(i18n('Host', 'setup') . ' [' . $this->settings['db']['host'] . ']: ', 1);
        $line = trim(fgets(STDIN));
        $this->settings['db']['host'] = ($line == "") ? $this->settings['db']['host'] : $line;

        // database user
        prnt(i18n('User', 'setup') . ' [' . $this->settings['db']['user'] . ']: ', 1);
        $line = trim(fgets(STDIN));
        $this->settings['db']['user'] = ($line == "") ? $this->settings['db']['user'] : $line;

        // database password
        $dbpw = passwordPrompt(i18n('Password', 'setup'), 1);
        $this->settings['db']['password'] = ($dbpw == '') ? $this->settings['db']['password'] : $dbpw;

        // database name
        prnt(i18n('Database name', 'setup') .' [' . $this->settings['db']['database'] . ']: ', 1);
        $line = trim(fgets(STDIN));
        $this->settings['db']['database'] = ($line == "") ? $this->settings['db']['database'] : $line;

        // database charset
        prnt(i18n('Charset', 'setup') . ' [' . $this->settings['db']['charset'] . ']: ', 1);
        $line = trim(fgets(STDIN));
        $this->settings['db']['charset'] = ($line == "") ? $this->settings['db']['charset'] : $line;

        // database collation
        prnt(i18n('Collation', 'setup') . ' [' . $this->settings['db']['collation'] . ']: ', 1);
        $line = trim(fgets(STDIN));
        $this->settings['db']['collation'] = ($line == "") ? $this->settings['db']['collation'] : $line;

        // database prefix
        prnt(i18n('Prefix', 'setup') . ' [' . $this->settings['db']['prefix'] . ']: ', 1);
        $line = trim(fgets(STDIN));
        $this->settings['db']['prefix'] = ($line == "") ? $this->settings['db']['prefix'] : $line;

        // http root path
        prntln();
        prntln(i18n('Please enter the http path to where the contenido/ folder resides', 'setup'));
        prntln(i18n('e.g. http://localhost/', 'setup'));
        prnt(i18n('Backend web path', 'setup') .' [' . $this->settings['paths']['http_root_path'] . ']: ', 1);
        $line = trim(fgets(STDIN));
        $this->settings['paths']['http_root_path'] = ($line == "") ? $this->settings['paths']['http_root_path'] : $line;

        // ask for the setup mode
        prntln();
        $displayStandard = i18n('yes', 'setup');
        if ($this->settings['setup']['client_mode'] == 'CLIENTMODULES') {
            $displayStandard = i18n('modules', 'setup');
        } else if ($this->settings['setup']['client_mode'] == 'NOCLIENT') {
            $displayStandard = i18n('no', 'setup');
        }
        $first = true;
        while ($this->settings['setup']['client_mode'] == "" || $first) {
            $first = false;
            prntln(i18n('Do you want to install the example client?', 'setup'));
            prntln(i18n('Please choose "yes" (to install the modules AND the content), "modules" (to install only the modules) or "no"', 'setup'));
            prnt(i18n('Examples? (yes/modules/no)', 'setup') . '[' . $displayStandard . ']: ', 1);
            $line = strtolower(trim(fgets(STDIN)));
            if ($line == "") {
                $line = $displayStandard;
            }
            if ($line == 'yes' || $line == i18n('yes', 'setup')) {
                $this->settings['setup']['client_mode'] = 'CLIENTEXAMPLES';
            } else if ($line == 'modules' || $line == i18n('modules', 'setup')) {
                $this->settings['setup']['client_mode'] = 'CLIENTMODULES';
            } else if ($line == 'no' || $line == i18n('no', 'setup')) {
                $this->settings['setup']['client_mode'] = 'NOCLIENT';
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
        $this->settings['admin_user']['password'] = ($password1 == '') ? $this->settings['admin_user']['password'] : $password1;

        // admin email
        prnt(i18n('Admin email', 'setup') . ' [' . $this->settings['admin_user']['email'] . ']: ', 1);
        $line = trim(fgets(STDIN));
        $this->settings['admin_user']['email'] = ($line == "") ? $this->settings['admin_user']['email'] : $line;

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

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        switch ($ext) {
            case 'ini':
                $this->settings = parse_ini_file($file, true);
                break;
            case 'xml':
                $xml = simplexml_load_file($file);
                if (!$xml) {
                    return;
                }

                $this->settings['db']['host'] = trim($xml->db->host);
                $this->settings['db']['user'] = trim($xml->db->user);
                $this->settings['db']['password'] = trim($xml->db->password);
                $this->settings['db']['charset'] = trim($xml->db->charset);
                $this->settings['db']['database'] = trim($xml->db->database);
                $this->settings['db']['prefix'] = trim($xml->db->prefix);
                $this->settings['db']['collation'] = trim($xml->db->collation);

                $this->settings['paths']['http_root_path'] = trim($xml->path->http_root_path);

                $this->settings['setup']['client_mode'] = trim($xml->client->client_mode);

                $this->settings['admin_user']['password'] = trim($xml->admin_user->password);
                $this->settings['admin_user']['email'] = trim($xml->admin_user->email);
                $this->settings['advanced']['delete_database'] = trim($xml->advanced->delete_database);
                break;
            case 'json':
                $this->settings = json_decode(file_get_contents($file), true);
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
        global $cfg, $args;

        if ($this->settings['advanced']['delete_database'] == '1' || $this->settings['advanced']['delete_database'] == 'YES') {
            $answer = '';
            while ($answer != 'Y' && $answer != 'N') {
                prnt(sprintf(i18n("You chose in the configuration file to delete the database '%s' before installing.\nDO YOU REALLY WANT TO CONTINUE WITH DELETING THIS DATABASE? (Y/N) [N]: ", 'setup'), $this->settings['db']['database']));
                $answer = trim(fgets(STDIN));
                if ($answer == "") {
                    $answer = "N";
                }
            }
            if ($answer != "Y") {
                exit(3);
            }

            $db = getSetupMySQLDBConnection(false);
            $db->query('DROP DATABASE ' . $this->settings['db']['database']);

            prntln();
            prntln(sprintf(i18n('THE DATABASE %s HAS BEEN DELETED!!!', 'setup'), $this->settings['db']['database']));
            prntln();
        }

        prnt(i18n('Testing your system...', 'setup'));

        $fine = true;

        // load the CONTENIDO locale for the test results
        i18nInit('../data/locale/', $belang);

        // run the tests
        $test = new cSystemtest($cfg);
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
            if (strtolower($answer) == "n" || strtolower($answer) == strtolower(i18n('n', 'setup'))) {
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
        global $cfg, $_SESSION;

        $cfg['db'] = array(
            'connection' => array(
                'host'     => $this->settings['db']['host'],
                'user'     => $this->settings['db']['user'],
                'password' => $this->settings['db']['password'],
                'charset'  => $this->settings['db']['charset'],
                'database' => $this->settings['db']['database']
            ),
            'haltBehavior'    => 'report',
            'haltMsgPrefix'   => (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] . ' ' : '',
            'enableProfiling' => false
        );
        $_SESSION['setuptype'] = 'setup';
        $_SESSION['dbname'] = $this->settings['db']['database'];
        $_SESSION['configmode'] = 'save';
        $_SESSION['override_root_http_path'] = $this->settings['paths']['http_root_path'];
        $_SESSION['clientmode'] = $this->settings['setup']['client_mode'];
        $_SESSION['adminpass'] = $this->settings['admin_user']['password'];
        $_SESSION['adminmail'] = $this->settings['admin_user']['email'];
        $_SESSION['dbprefix'] = $this->settings['db']['prefix'];
        $_SESSION['dbcollation'] = $this->settings['db']['collation'];
        $_SESSION['dbcharset'] = $this->settings['db']['charset'];
        $cfg['sql']['sqlprefix'] = $this->settings['db']['prefix'];
        // reload cfg_sql.inc.php because new sql prefix will change resulting array data
        include($cfg['path']['contenido_config'] . 'cfg_sql.inc.php');
    }
}
