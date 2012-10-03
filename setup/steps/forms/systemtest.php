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
 * @package CONTENIDO setup
 * @version 0.3.3
 * @author unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}
class cSetupSystemtest extends cSetupMask {

    const C_SEVERITY_NONE = 1;

    const C_SEVERITY_INFO = 2;

    const C_SEVERITY_WARNING = 3;

    const C_SEVERITY_ERROR = 4;

    const C_FILETYPE_FILE = 'file';

    const C_FILETYPE_DIR = 'dir';

    private $_aMessages = array();

    function cSetupSystemtest($step, $previous, $next) {
        cSetupMask::cSetupMask("templates/setup/forms/systemtest.tpl", $step);

        $errors = false;

        $this->setHeader(i18n("System Test"));
        $this->_oStepTemplate->set("s", "TITLE", i18n("System Test"));
        $this->_oStepTemplate->set("s", "DESCRIPTION", i18n("Your system has been tested for compatibility with CONTENIDO:"));

        $cHTMLErrorMessageList = new cHTMLErrorMessageList();

        // Run PHP tests
        $this->doPHPTests();

        // Run GD tests if available
        if (isPHPExtensionLoaded("gd")) {
            $this->doGDTests();
        }

        if (hasMySQLExtension() || hasMySQLiExtension()) {
            $this->doMySQLTests();
        } else {
            $this->runTest(false, self::C_SEVERITY_ERROR, i18n("PHP MySQL Extension missing"), i18n("CONTENIDO requires the MySQL or MySQLi extension to access MySQL databases. Please configure PHP to use either MySQL or MySQLi."));
        }

        $this->doFileSystemTests();

        if ($_SESSION["setuptype"] == 'upgrade') {
            // Check if there is an old version of integrated plugins installed
            // in upgrademode.
            $this->doExistingOldPluginTests();

            // Check if user updates a system lower than 4.9
            $this->doChangedDirsFilesTest();
        }

        $cHTMLFoldableErrorMessages = array();

        foreach ($this->_aMessages as $severity => $messageEntry) {
            switch ($severity) {
                case 2:
                    $icon = "images/icons/info.png";
                    $iconDescription = i18n("Information");
                    break;
                case 3:
                    $icon = "images/icons/warning.png";
                    $iconDescription = i18n("Warning");
                    break;
                case 4:
                    $icon = "images/icons/error.png";
                    $iconDescription = i18n("Fatal error");
                    $errors = true;
                    break;
            }

            foreach ($messageEntry as $message) {
                $cHTMLFoldableErrorMessages[] = new cHTMLFoldableErrorMessage($message[0], $message[1], $icon, $iconDescription);
            }
        }

        if (count($cHTMLFoldableErrorMessages) == 0) {
            $cHTMLFoldableErrorMessages[] = new cHTMLFoldableErrorMessage(i18n("No problems detected"), i18n("Setup could not detect any problems with your system environment"), "images/icons/info.png");
        }

        $cHTMLErrorMessageList->setContent($cHTMLFoldableErrorMessages);

        $this->_oStepTemplate->set("s", "CONTROL_TESTRESULTS", $cHTMLErrorMessageList->render());

        if ($errors == true) {
            $this->setNavigation($previous, "");
        } else {
            $this->setNavigation($previous, $next);
        }
    }

    function doExistingOldPluginTests() {
        $db = getSetupMySQLDBConnection(false);
        $message = '';

        // get all tables in database and list it into array
        $avariableTableNames = array();
        $tableNames = $db->table_names();
        if (!is_array($tableNames)) {
            return;
        }

        foreach ($tableNames as $table) {
            $avariableTableNames[] = $table['table_name'];
        }

        // list of plugin tables to copy into new plugin tables
        $oldPluginTables = array(
            'Workflow' => array(
                'piwf_actions',
                'piwf_allocation',
                'piwf_art_allocation',
                'piwf_items',
                'piwf_user_sequences',
                'piwf_workflow'
            ),
            'Content allocation' => array(
                'pica_alloc',
                'pica_alloc_con',
                'pica_lang'
            ),
            'Linkchecker' => array(
                'pi_externlinks',
                'pi_linkwhitelist'
            )
        );

        foreach ($oldPluginTables as $plugin => $tables) {
            $pluginExists = false;
            foreach ($tables as $currentTable) {
                if (in_array($currentTable, $avariableTableNames)) {
                    $pluginExists = true;
                }
            }

            if ($pluginExists) {
                $message .= sprintf(i18n('An old Version of Plugin %s is installed on your system.') . "<br>\n", $plugin);
            }
        }

        if ($message) {
            $message .= '<br>' . i18n('Please remove all old plugins before you continue. To transfer old plugin data, please copy the old plugin data tables into the new plugin data tables after the installation. The new plugintable names are the same, but contains the table prefix of CONTENIDO. Also delete the old plugin tables after data transfer.');

            $this->runTest(false, self::C_SEVERITY_WARNING, i18n("Old Plugins are still installed"), $message);
        }
    }

    function doChangedDirsFilesTest() {
        global $cfg;

        $db = getSetupMySQLDBConnection(false);
        $version = getContenidoVersion($db, $cfg['tab']['system_prop']);

        // Display message about changed directories/files when user updates a
        // system lower than 4.9
        if ($version && version_compare('4.9', $version) > 0) {
            $message = i18n("You are updating a previous version of CONTENIDO to %s. Some directories/files have been moved to other sections in %s.\n\nPlease ensure to copy contenido/includes/config.php to data/config/production/config.php and also other configuration files within contenido/includes/ to data/config/production/.");
            $message = sprintf($message, '4.9', '4.9');
            $message = nl2br($message);
            $this->runTest(false, self::C_SEVERITY_WARNING, i18n("Attention: Some directories/files have been moved"), $message);
        }
    }

    function runTest($result, $severity, $headline = "", $errorMessage = "") {
        // @todo: Store results into an external file
        if ($result == false && $severity != self::C_SEVERITY_NONE) {
            $this->_aMessages[$severity][] = array(
                $headline,
                $errorMessage
            );
        }
    }

    function doPHPTests() {
        $this->runTest(isPHPCompatible(), self::C_SEVERITY_ERROR, sprintf(i18n("PHP Version lower than %s"), CON_SETUP_MIN_PHP_VERSION), sprintf(i18n("CONTENIDO requires PHP %s or higher as it uses functionality first introduced with this version. Please update your PHP version."), CON_SETUP_MIN_PHP_VERSION));

        $this->runTest(getPHPFileUploadSetting(), self::C_SEVERITY_WARNING, i18n("File uploads disabled"), sprintf(i18n("Your PHP version is not configured for file uploads. You can't upload files using CONTENIDO's file manager unless you configure PHP for file uploads. See %s for more information"), '<a target="_blank" href="http://www.php.net/manual/en/ini.core.php#ini.file-uploads">http://www.php.net/manual/en/ini.core.php#ini.file-uploads</a>'));

        $this->runTest(!getPHPMagicQuotesRuntime(), self::C_SEVERITY_ERROR, i18n("PHP setting 'magic_quotes_runtime' is turned on"), i18n("The PHP setting 'magic_quotes_runtime' is turned on. CONTENIDO has been developed to comply with magic_quotes_runtime=Off as this is the PHP default setting. You have to change this directive to make CONTENIDO work."));

        $this->runTest(!getPHPMagicQuotesSybase(), self::C_SEVERITY_ERROR, i18n("PHP Setting 'magic_quotes_sybase' is turned on"), i18n("The PHP Setting 'magic_quotes_sybase' is turned on. CONTENIDO has been developed to comply with magic_quotes_sybase=Off as this is the PHP default setting. You have to change this directive to make CONTENIDO work."));

        $this->runTest(intval(getPHPMaxExecutionTime()) >= 30, self::C_SEVERITY_WARNING, i18n("PHP maximum execution time is less than 30 seconds"), i18n("PHP is configured for a maximum execution time of less than 30 seconds. This could cause problems with slow web servers and/or long operations in the backend. Our recommended execution time is 120 seconds on slow web servers, 60 seconds for medium ones and 30 seconds for fast web servers."));

        $this->runTest(class_exists("ZipArchive"), self::C_SEVERITY_WARNING, i18n("The class ZipArchive could not be found"), i18n("This could cause some problems, but CONTENIDO is able to run without it. You should check your PHP installation."));

        $result = checkOpenBasedirCompatibility();
        switch ($result) {
            case CON_BASEDIR_NORESTRICTION:
                $this->runTest(false, self::C_SEVERITY_NONE);
                break;
            case CON_BASEDIR_DOTRESTRICTION:
                $this->runTest(false, self::C_SEVERITY_ERROR, i18n("open_basedir directive set to '.'"), i18n("The directive open_basedir is set to '.' (e.g. current directory). This means that CONTENIDO is unable to access files in a logical upper level in the filesystem. This will cause problems managing the CONTENIDO frontends. Either add the full path of this CONTENIDO installation to the open_basedir directive, or turn it off completely."));
                break;
            case CON_BASEDIR_RESTRICTIONSUFFICIENT:
                $this->runTest(false, self::C_SEVERITY_INFO, i18n("open_basedir setting might be insufficient"), i18n("Setup believes that the PHP directive open_basedir is configured sufficient, however, if you encounter errors like 'open_basedir restriction in effect. File <filename> is not within the allowed path(s): <path>', you have to adjust the open_basedir directive"));
                break;
            case CON_BASEDIR_INCOMPATIBLE:
                $this->runTest(false, self::C_SEVERITY_ERROR, i18n("open_basedir directive incompatible"), i18n("Setup has checked your PHP open_basedir directive and reckons that it is not sufficient. Please change the directive to include the CONTENIDO installation or turn it off completely."));
                break;
        }

        $memoryLimit = getAsBytes(getPHPIniSetting("memory_limit"));
        if ($memoryLimit > 0) {
            $this->runTest(($memoryLimit > 1024 * 1024 * 32), self::C_SEVERITY_WARNING, i18n("PHP memory_limit directive too small"), i18n("The memory_limit directive is set to 32 MB or lower. This might be not enough for CONTENIDO to operate correctly. We recommend to disable this setting completely, as this can cause problems with large CONTENIDO projects."));
        }

        $this->runTest(!checkPHPSQLSafeMode(), self::C_SEVERITY_ERROR, i18n("PHP sql.safe_mode turned on"), i18n("The PHP directive sql.safe_mode is turned on. This causes problems with the SQL queries issued by CONTENIDO. Please turn that directive off."));

        $this->runTest(isPHPExtensionLoaded("gd"), self::C_SEVERITY_WARNING, i18n("PHP GD-Extension is not loaded"), i18n("The PHP GD-Extension is not loaded. Some third-party modules rely on the GD functionality. If you don't enable the GD extension, you will encounter problems with modules like galleries."));

        $this->runTest(isPHPExtensionLoaded("pcre"), self::C_SEVERITY_ERROR, i18n("PHP PCRE Extension is not loaded"), i18n("The PHP PCRE Extension is not loaded. CONTENIDO uses PCRE-functions like preg_repace and preg_match and won't work without the PCRE Extension."));

        $this->runTest(class_exists("DOMDocument"), self::C_SEVERITY_ERROR, i18n("PHP XML Extension is not loaded"), i18n("The PHP XML Extension is not loaded. CONTENIDO won't work without the XML Extension."));

        $this->runTest(isPHPExtensionLoaded("xml"), self::C_SEVERITY_ERROR, i18n("PHP XML Extension is not loaded"), i18n("The PHP XML Extension is not loaded. CONTENIDO won't work without the XML Extension."));

        $this->runTest(function_exists("xml_parser_create"), self::C_SEVERITY_ERROR, i18n("PHP XML Extension is not loaded"), i18n("The PHP XML Extension is not loaded. CONTENIDO won't work without the XML Extension."));

        $result = checkImageResizer();
        switch ($result) {
            case CON_IMAGERESIZE_CANTCHECK:
                $this->runTest(false, self::C_SEVERITY_WARNING, i18n("Unable to check for a suitable image resizer"), i18n("Setup has tried to check for a suitable image resizer (which is, for exampl, required for thumbnail creation), but was not able to clearly identify one. If thumbnails won't work, make sure you've got either the GD-extension or ImageMagick available."));
                break;
            case CON_IMAGERESIZE_NOTHINGAVAILABLE:
                $this->runTest(false, self::C_SEVERITY_ERROR, i18n("No suitable image resizer available"), i18n("Setup checked your image resizing support, however, it was unable to find a suitable image resizer. Thumbnails won't work correctly or won't be looking good. Install the GD-Extension or ImageMagick"));
                break;
        }

        $this->runTest(function_exists("iconv"), self::C_SEVERITY_ERROR, i18n("PHP iconv functions are not available."), i18n("PHP has been compiled with the --without-iconv directive. CONTENIDO won't work without the iconv functions."));

        // @todo: Check if ini_set can be used
    }

    function doGDTests() {
        $this->runTest(function_exists("imagecreatefromgif"), self::C_SEVERITY_INFO, i18n("GD-Library GIF read support missing"), i18n("Your GD version doesn't support reading GIF files. This might cause problems with some modules."));

        $this->runTest(function_exists("imagegif"), self::C_SEVERITY_INFO, i18n("GD-Library GIF write support missing"), i18n("Your GD version doesn't support writing GIF files. This might cause problems with some modules."));

        $this->runTest(function_exists("imagecreatefromjpeg"), self::C_SEVERITY_INFO, i18n("GD-Library JPEG read support missing"), i18n("Your GD version doesn't support reading JPEG files. This might cause problems with some modules."));

        $this->runTest(function_exists("imagejpeg"), self::C_SEVERITY_INFO, i18n("GD-Library JPEG write support missing"), i18n("Your GD version doesn't support writing JPEG files. This might cause problems with some modules."));

        $this->runTest(function_exists("imagecreatefrompng"), self::C_SEVERITY_INFO, i18n("GD-Library PNG read support missing"), i18n("Your GD version doesn't support reading PNG files. This might cause problems with some modules."));

        $this->runTest(function_exists("imagepng"), self::C_SEVERITY_INFO, i18n("GD-Library PNG write support missing"), i18n("Your GD version doesn't support writing PNG files. This might cause problems with some modules."));
    }

    function doMySQLTests() {
        list($handle, $status) = doMySQLConnect($_SESSION["dbhost"], $_SESSION["dbuser"], $_SESSION["dbpass"]);

        if (hasMySQLiExtension() && !hasMySQLExtension()) {
            $errorMessage = mysqli_error($handle->Link_ID);
        } else {
            $errorMessage = mysql_error();
        }

        $this->runTest($status, self::C_SEVERITY_ERROR, i18n("MySQL database connect failed"), sprintf(i18n("Setup was unable to connect to the MySQL Server (Server %s, Username %s). Please correct the MySQL data and try again.<br><br>The error message given was: %s"), $_SESSION["dbhost"], $_SESSION["dbuser"], $errorMessage));

        if ($status == false) {
            return;
        }

        $db = getSetupMySQLDBConnection(false);

        $this->runTest(!$this->isSqlModeStrict(), self::C_SEVERITY_ERROR, i18n('MySQL is running in strict mode'), 'MySql is running in strict mode, CONTENIDO will not running. Please change your sql_mode!');

        switch ($_SESSION["setuptype"]) {
            case "setup":

                $db = getSetupMySQLDBConnection(false);

                // Check if the database exists
                $status = checkMySQLDatabaseExists($db, $_SESSION["dbname"]);

                if ($status) {
                    // Yes, database exists
                    $db = getSetupMySQLDBConnection();
                    $db->connect();

                    // Check if data already exists
                    $db->query('SHOW TABLES LIKE "%s_actions"', $_SESSION["dbprefix"]);

                    if ($db->next_record()) {
                        $this->runTest(false, self::C_SEVERITY_ERROR, i18n("MySQL database already exists and seems to be filled"), sprintf(i18n("Setup checked the database %s and found the table %s. It seems that you already have a CONTENIDO installation in this database. If you want to install anyways, change the database prefix. If you want to upgrade from a previous version, choose 'upgrade' as setup type."), $_SESSION["dbname"], sprintf("%s_actions", $_SESSION["dbprefix"])));
                        return;
                    }

                    // Check if data already exists
                    $db->query('SHOW TABLES LIKE "%s_test"', $_SESSION["dbprefix"]);
                    if ($db->next_record()) {
                        $this->runTest(false, self::C_SEVERITY_ERROR, i18n("MySQL test table already exists in the database"), sprintf(i18n("Setup checked the database %s and found the test table %s. Please remove it before continuing."), $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"])));
                        return;
                    }

                    // Good, table doesn't exist. Check for database permisions
                    $status = checkMySQLTableCreation($db, $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"]));
                    if (!$status) {
                        $this->runTest(false, self::C_SEVERITY_ERROR, i18n("Unable to create tables in the selected MySQL database"), sprintf(i18n("Setup tried to create a test table in the database %s and failed. Please assign table creation permissions to the database user you entered, or ask an administrator to do so."), $_SESSION["dbname"]));
                        return;
                    }

                    // Good, we could create a table. Now remove it again
                    $status = checkMySQLDropTable($db, $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"]));
                    if (!$status) {
                        $this->runTest(false, self::C_SEVERITY_WARNING, i18n("Unable to remove the test table"), sprintf(i18n("Setup tried to remove the test table %s in the database %s and failed due to insufficient permissions. Please remove the table %s manually."), sprintf("%s_test", $_SESSION["dbprefix"]), $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"])));
                    }
                } else {
                    $db->connect();
                    // Check if database can be created
                    $status = checkMySQLDatabaseCreation($db, $_SESSION["dbname"]);
                    if (!$status) {
                        $this->runTest(false, self::C_SEVERITY_ERROR, i18n("Unable to create the database in the MySQL server"), sprintf(i18n("Setup tried to create a test database and failed. Please assign database creation permissions to the database user you entered, ask an administrator to do so, or create the database manually.")));
                        return;
                    }

                    // Check for database permisions
                    $status = checkMySQLTableCreation($db, $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"]));
                    if (!$status) {
                        $this->runTest(false, self::C_SEVERITY_ERROR, i18n("Unable to create tables in the selected MySQL database"), sprintf(i18n("Setup tried to create a test table in the database %s and failed. Please assign table creation permissions to the database user you entered, or ask an administrator to do so."), $_SESSION["dbname"]));
                        return;
                    }

                    // Good, we could create a table. Now remove it again
                    $status = checkMySQLDropTable($db, $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"]));
                    if (!$status) {
                        $this->runTest(false, self::C_SEVERITY_WARNING, i18n("Unable to remove the test table"), sprintf(i18n("Setup tried to remove the test table %s in the database %s and failed due to insufficient permissions. Please remove the table %s manually."), sprintf("%s_test", $_SESSION["dbprefix"]), $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"])));
                    }
                }
                break;
            case "migration":
                $db = getSetupMySQLDBConnection(false);

                // Check if the database exists
                $status = checkMySQLDatabaseExists($db, $_SESSION["dbname"]);
                if (!$status) {
                    $this->runTest(false, self::C_SEVERITY_ERROR, i18n("No data found for the migration"), sprintf(i18n("Setup tried to locate the data for the migration, however, the database %s doesn't exist. You need to copy your database first before running setup."), $_SESSION["dbname"]));
                    return;
                }

                $db = getSetupMySQLDBConnection();

                // Check if data already exists
                $sql = 'SHOW TABLES LIKE "%s_actions"';
                $db->query(sprintf($sql, $_SESSION["dbprefix"]));
                if (!$db->next_record()) {
                    $this->runTest(false, self::C_SEVERITY_ERROR, i18n("No data found for the migration"), sprintf(i18n("Setup tried to locate the data for the migration, however, the database %s contains no tables. You need to copy your database first before running setup."), $_SESSION["dbname"]));
                    return;
                }

                break;
            case "upgrade":
                $db = getSetupMySQLDBConnection(false);

                // Check if the database exists
                $status = checkMySQLDatabaseExists($db, $_SESSION["dbname"]);
                if (!$status) {
                    $this->runTest(false, self::C_SEVERITY_ERROR, i18n("No data found for the upgrade"), sprintf(i18n("Setup tried to locate the data for the upgrade, however, the database %s doesn't exist. You need to copy your database first before running setup."), $_SESSION["dbname"]));
                    return;
                }

                $db = getSetupMySQLDBConnection();

                // Check if data already exists
                $sql = 'SHOW TABLES LIKE "%s_actions"';
                $db->query(sprintf($sql, $_SESSION["dbprefix"]));
                if (!$db->next_record()) {
                    $this->runTest(false, self::C_SEVERITY_ERROR, i18n("No data found for the upgrade"), sprintf(i18n("Setup tried to locate the data for the upgrade, however, the database %s contains no tables. You need to copy your database first before running setup."), $_SESSION["dbname"]));
                    return;
                }

                break;
        }
    }

    /**
     * Is mysql strict modus active
     *
     * @return boolean true if stric modus is detected
     */
    function isSqlModeStrict() {
        global $cfg;

        // host, user and password
        $dbCfg = $cfg['db'];
        unset($dbCfg['connection']['database']);

        $db = new cDb($dbCfg);
        $db->query('SELECT LOWER(@@GLOBAL.sql_mode) AS sql_mode');
        if ($db->next_record()) {
            if (strpos($db->f('sql_mode'), 'strict_trans_tables') !== false || strpos($db->f('sql_mode'), 'strict_all_tables') !== false) {
                return true;
            }
        }
        return false;
    }

    function doFileSystemTests() {
        global $cfg;

        $this->logFilePrediction($cfg['path']['contenido_logs'] . "errorlog.txt", self::C_SEVERITY_WARNING, self::C_FILETYPE_FILE);
        $this->logFilePrediction($cfg['path']['contenido_logs'] . "setuplog.txt", self::C_SEVERITY_WARNING, self::C_FILETYPE_FILE);
        $this->logFilePrediction($cfg['path']['contenido_cronlog'] . "pseudo-cron.log", self::C_SEVERITY_WARNING, self::C_FILETYPE_FILE);
        $this->logFilePrediction($cfg['path']['contenido_cronlog'] . "session_cleanup.php.job", self::C_SEVERITY_WARNING, self::C_FILETYPE_FILE);
        $this->logFilePrediction($cfg['path']['contenido_cronlog'] . "send_reminder.php.job", self::C_SEVERITY_WARNING, self::C_FILETYPE_FILE);
        $this->logFilePrediction($cfg['path']['contenido_cronlog'] . "optimize_database.php.job", self::C_SEVERITY_WARNING, self::C_FILETYPE_FILE);
        $this->logFilePrediction($cfg['path']['contenido_cronlog'] . "move_old_stats.php.job", self::C_SEVERITY_WARNING, self::C_FILETYPE_FILE);
        $this->logFilePrediction($cfg['path']['contenido_cronlog'] . "move_articles.php.job", self::C_SEVERITY_WARNING, self::C_FILETYPE_FILE);
        $this->logFilePrediction($cfg['path']['contenido_cronlog'] . "linkchecker.php.job", self::C_SEVERITY_WARNING, self::C_FILETYPE_FILE);
        $this->logFilePrediction($cfg['path']['contenido_cronlog'] . "run_newsletter_job.php.job", self::C_SEVERITY_WARNING, self::C_FILETYPE_FILE);
        $this->logFilePrediction($cfg['path']['contenido_cronlog'] . "setfrontenduserstate.php.job", self::C_SEVERITY_WARNING, self::C_FILETYPE_FILE);
        $this->logFilePrediction($cfg['path']['contenido_cronlog'] . "advance_workflow.php.job", self::C_SEVERITY_WARNING, self::C_FILETYPE_FILE);
        $this->logFilePrediction($cfg['path']['contenido_cache'], self::C_SEVERITY_WARNING, self::C_FILETYPE_DIR);
        $this->logFilePrediction($cfg['path']['contenido_temp'], self::C_SEVERITY_WARNING, self::C_FILETYPE_DIR);

        if ($_SESSION["setuptype"] == "setup" || ($_SESSION["setuptype"] == "migration" && is_dir(CON_FRONTEND_PATH . '/cms/'))) {
            // Setup mode or migration mode with a existing default client
            // frontend path
            $this->logFilePrediction($cfg['path']['frontend'] . "/cms/cache/", self::C_SEVERITY_WARNING, self::C_FILETYPE_DIR);
            $this->logFilePrediction($cfg['path']['frontend'] . "/cms/cache/code/", self::C_SEVERITY_WARNING, self::C_FILETYPE_DIR);
            $this->logFilePrediction($cfg['path']['frontend'] . "/cms/css/", self::C_SEVERITY_WARNING, self::C_FILETYPE_DIR);
            $this->logFilePrediction($cfg['path']['frontend'] . "/cms/data/layouts/", self::C_SEVERITY_WARNING, self::C_FILETYPE_DIR);
            $this->logFilePrediction($cfg['path']['frontend'] . "/cms/data/logs/", self::C_SEVERITY_WARNING, self::C_FILETYPE_DIR);
            $this->logFilePrediction($cfg['path']['frontend'] . "/cms/data/modules/", self::C_SEVERITY_WARNING, self::C_FILETYPE_DIR);
            $this->logFilePrediction($cfg['path']['frontend'] . "/cms/data/version/", self::C_SEVERITY_WARNING, self::C_FILETYPE_DIR);
            $this->logFilePrediction($cfg['path']['frontend'] . "/cms/data/version/css/", self::C_SEVERITY_WARNING, self::C_FILETYPE_DIR);
            $this->logFilePrediction($cfg['path']['frontend'] . "/cms/data/version/js/", self::C_SEVERITY_WARNING, self::C_FILETYPE_DIR);
            $this->logFilePrediction($cfg['path']['frontend'] . "/cms/data/version/layout/", self::C_SEVERITY_WARNING, self::C_FILETYPE_DIR);
            $this->logFilePrediction($cfg['path']['frontend'] . "/cms/data/version/module/", self::C_SEVERITY_WARNING, self::C_FILETYPE_DIR);
            $this->logFilePrediction($cfg['path']['frontend'] . "/cms/data/version/templates/", self::C_SEVERITY_WARNING, self::C_FILETYPE_DIR);
            $this->logFilePrediction($cfg['path']['frontend'] . "/cms/js/", self::C_SEVERITY_WARNING, self::C_FILETYPE_DIR);
            $this->logFilePrediction($cfg['path']['frontend'] . "/cms/templates/", self::C_SEVERITY_WARNING, self::C_FILETYPE_DIR);
            $this->logFilePrediction($cfg['path']['frontend'] . "/cms/upload/", self::C_SEVERITY_WARNING, self::C_FILETYPE_DIR);
        }

        if ($_SESSION["configmode"] == "save") {
            $this->logFilePrediction($cfg['path']['contenido_config'] . "config.php", self::C_SEVERITY_ERROR, self::C_FILETYPE_FILE);
        }
    }

    function logFilePrediction($file, $severity, $filetype) {
        if (strpos($file, CON_FRONTEND_PATH) === 0) {
            $shortFilename = substr($file, strlen(CON_FRONTEND_PATH));
        }

        if ($filetype == self::C_FILETYPE_FILE) {
            $status = canWriteFile($file);
        } else {
            $status = canWriteDir($file);
        }

        $title = sprintf(i18n("Can't write %s"), $shortFilename);
        $message = sprintf(i18n("Setup or CONTENIDO can't write to the file %s. Please change the file permissions to correct this problem."), $shortFilename);

        if ($status == false) {
            if (cFileHandler::exists($file)) {
                $perm = predictCorrectFilepermissions($file);

                switch ($perm) {
                    case CON_PREDICT_WINDOWS:
                        $predictMessage = i18n("Your Server runs Windows. Due to that, Setup can't recommend any file permissions.");
                        break;
                    case CON_PREDICT_NOTPREDICTABLE:
                        $predictMessage = sprintf(i18n("Due to a very restrictive environment, an advise is not possible. Ask your system administrator to enable write access to the file %s, especially in environments where ACL (Access Control Lists) are used."), $shortFilename);
                        break;
                    case CON_PREDICT_CHANGEPERM_SAMEOWNER:
                        $mfileperms = substr(sprintf("%o", fileperms($file)), -3);
                        $mfileperms{0} = intval($mfileperms{0}) | 0x6;
                        $predictMessage = sprintf(i18n("Your web server and the owner of your files are identical. You need to enable write access for the owner, e.g. using chmod u+rw %s, setting the file mask to %s or set the owner to allow writing the file."), $shortFilename, $mfileperms);
                        break;
                    case CON_PREDICT_CHANGEPERM_SAMEGROUP:
                        $mfileperms = substr(sprintf("%o", fileperms($file)), -3);
                        $mfileperms{1} = intval($mfileperms{1}) | 0x6;
                        $predictMessage = sprintf(i18n("Your web server's group and the group of your files are identical. You need to enable write access for the group, e.g. using chmod g+rw %s, setting the file mask to %s or set the group to allow writing the file."), $shortFilename, $mfileperms);
                        break;
                    case CON_PREDICT_CHANGEPERM_OTHERS:
                        $mfileperms = substr(sprintf("%o", fileperms($file)), -3);
                        $mfileperms{2} = intval($mfileperms{2}) | 0x6;
                        $predictMessage = sprintf(i18n("Your web server is not equal to the file owner, and is not in the webserver's group. It would be highly insecure to allow world write acess to the files. If you want to install anyways, enable write access for all others, e.g. using chmod o+rw %s, setting the file mask to %s or set the others to allow writing the file."), $shortFilename, $mfileperms);
                        break;
                }
            } else {
                $target = dirname($file);

                $perm = predictCorrectFilepermissions($target);

                switch ($perm) {
                    case CON_PREDICT_WINDOWS:
                        $predictMessage = i18n("Your Server runs Windows. Due to that, Setup can't recommend any directory permissions.");
                        break;
                    case CON_PREDICT_NOTPREDICTABLE:
                        $predictMessage = sprintf(i18n("Due to a very restrictive environment, an advise is not possible. Ask your system administrator to enable write access to the file or directory %s, especially in environments where ACL (Access Control Lists) are used."), dirname($shortFilename));
                        break;
                    case CON_PREDICT_CHANGEPERM_SAMEOWNER:
                        $mfileperms = substr(sprintf("%o", @fileperms($target)), -3);
                        $mfileperms{0} = intval($mfileperms{0}) | 0x6;
                        $predictMessage = sprintf(i18n("Your web server and the owner of your directory are identical. You need to enable write access for the owner, e.g. using chmod u+rw %s, setting the directory mask to %s or set the owner to allow writing the directory."), dirname($shortFilename), $mfileperms);
                        break;
                    case CON_PREDICT_CHANGEPERM_SAMEGROUP:
                        $mfileperms = substr(sprintf("%o", @fileperms($target)), -3);
                        $mfileperms{1} = intval($mfileperms{1}) | 0x6;
                        $predictMessage = sprintf(i18n("Your web server's group and the group of your directory are identical. You need to enable write access for the group, e.g. using chmod g+rw %s, setting the directory mask to %s or set the group to allow writing the directory."), dirname($shortFilename), $mfileperms);
                        break;
                    case CON_PREDICT_CHANGEPERM_OTHERS:
                        $mfileperms = substr(sprintf("%o", @fileperms($target)), -3);
                        $mfileperms{2} = intval($mfileperms{2}) | 0x6;
                        $predictMessage = sprintf(i18n("Your web server is not equal to the directory owner, and is not in the webserver's group. It would be highly insecure to allow world write acess to the directory. If you want to install anyways, enable write access for all others, e.g. using chmod o+rw %s, setting the directory mask to %s or set the others to allow writing the directory."), dirname($shortFilename), $mfileperms);
                        break;
                }
            }

            $this->runTest(false, $severity, $title, $message . "<br><br>" . $predictMessage);
        }
    }

}