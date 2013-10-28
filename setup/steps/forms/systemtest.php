<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    Contenido Backend <Area>
 * @version    0.3
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 *
 * {@internal
 *   created  unknown
 *   modified 2008-07-07, bilal arslan, added security fix
 *   modified 2009-12-17, Dominik Ziegler, added check for write permission on missing cronjob files
 *   modified 2010-07-26, Ortwin Pinke, [CON-329] added check for write permission of temp-folder
 *
 *   $Id: systemtest.php 1834 2012-02-07 14:23:04Z dominik.ziegler $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


define("C_SEVERITY_NONE",    1);
define("C_SEVERITY_INFO",    2);
define("C_SEVERITY_WARNING", 3);
define("C_SEVERITY_ERROR",   4);


class cSetupSystemtest extends cSetupMask {

    function cSetupSystemtest($step, $previous, $next) {

        cSetupMask::cSetupMask("templates/setup/forms/systemtest.tpl", $step);

        $bErrors = false;

        $this->setHeader(i18n("System Test"));
        $this->_oStepTemplate->set("s", "TITLE", i18n("System Test"));
        $this->_oStepTemplate->set("s", "DESCRIPTION", i18n("Your system has been tested for compatibility with Contenido:"));

        $cHTMLErrorMessageList = new cHTMLErrorMessageList;

        $this->_aMessages = array();

        // Run PHP tests
        $this->doPHPTests();

        // Run GD tests if available
        if (isPHPExtensionLoaded("gd")) {
            $this->doGDTests();
        }

        if (hasMySQLExtension() || hasMySQLiExtension()) {
            $this->doMySQLTests();
        } else {
            $this->runTest(
                false,
                C_SEVERITY_ERROR,
                i18n("PHP MySQL Extension missing"),
                i18n("Contenido requires the MySQL or MySQLi extension to access MySQL databases. Please configure PHP to use either MySQL or MySQLi.")
            );
        }

        $this->doFileSystemTests();

        // Check if there is an old version of integrated plugins installed in upgrademode.
        if ($_SESSION["setuptype"] == 'upgrade') {
            $this->doExistingOldPluginTests();
        }

        $cHTMLFoldableErrorMessages = array();

        foreach ($this->_aMessages as $iSeverity => $aMessageEntry) {
            switch ($iSeverity) {
                case 2: $sIcon = "images/icons/info.png";
                        $sIconDescription = i18n("Information");
                        break;
                case 3: $sIcon = "images/icons/warning.png";
                        $sIconDescription = i18n("Warning");
                        break;
                case 4: $sIcon = "images/icons/error.png";
                        $sIconDescription = i18n("Fatal error");
                        $bErrors = true;
                        break;
            }

            foreach ($aMessageEntry as $aMessage) {
                $cHTMLFoldableErrorMessages[] = new cHTMLFoldableErrorMessage($aMessage[0], $aMessage[1], $sIcon, $sIconDescription);
            }
        }

        if (count($cHTMLFoldableErrorMessages) == 0) {
            $cHTMLFoldableErrorMessages[] = new cHTMLFoldableErrorMessage(i18n("No problems detected"), i18n("Setup could not detect any problems with your system environment"), "images/icons/info.png");
        }

        $cHTMLErrorMessageList->setContent($cHTMLFoldableErrorMessages);

        $this->_oStepTemplate->set("s", "CONTROL_TESTRESULTS", $cHTMLErrorMessageList->render());

        if ($bErrors == true) {
            $this->setNavigation($previous, "");
        } else {
            $this->setNavigation($previous, $next);
        }
    }

    function doExistingOldPluginTests() {
        $db = new DB_Contenido($_SESSION["dbhost"], "", $_SESSION["dbuser"], $_SESSION["dbpass"]);
        $sMessage = '';

        //get all tables in database and list it into array
        $aAvariableTableNames = array();
        $aTableNames = $db->table_names();
        if (!is_array($aTableNames)) {
            return;
        }

        foreach ($aTableNames as $aTable) {
            array_push($aAvariableTableNames, $aTable['table_name']);
        }

        //list of plugin tables to copy into new plugin tables
        $aOldPluginTables = array('Workflow'              => array('piwf_actions', 'piwf_allocation', 'piwf_art_allocation',
                                                                    'piwf_items', 'piwf_user_sequences', 'piwf_workflow'),
                                   'Content Allocation'   => array ('pica_alloc', 'pica_alloc_con', 'pica_lang'),
                                   'Linkchecker'          => array ('pi_externlinks', 'pi_linkwhitelist'));

        foreach ($aOldPluginTables as $sPlugin => $aTables) {
            $bPluginExists = false;
            foreach ($aTables as $sCurrentTable) {
                if (in_array($sCurrentTable, $aAvariableTableNames)) {
                    $bPluginExists = true;
                }
            }

            if ($bPluginExists) {
                $sMessage .= sprintf(i18n("An old Version of Plugin %s is installed on your system.")."<br>\n", $sPlugin);
            }
        }

        if ($sMessage) {
            $sMessage .= '<br>'.i18n("Please remove all old plugins before you continue. To transfer old plugin data, please copy the old plugin data tables into the new plugin data tables after the installation. The new plugintable names are the same, but contains the table prefix of contenido. Also delete the old plugin tables after data transfer.");

            $this->runTest(false,
                           C_SEVERITY_WARNING,
                           i18n("Old Plugins are still installed"),
                           $sMessage);
        }
    }

    function runTest($mResult, $iSeverity, $sHeadline = "", $sErrorMessage = "") {
        /**
         * @todo: Store results into an external file
         */

        if ($mResult == false && $iSeverity != C_SEVERITY_NONE) {
            $this->_aMessages[$iSeverity][] = array($sHeadline, $sErrorMessage);
        }
    }

    function doPHPTests() {
        // new demo client requires PHP5
        if (!version_compare(phpversion(), "5.0.0", ">=") && $_SESSION["setuptype"] == 'setup') {
            $this->runTest(false,
                           C_SEVERITY_WARNING,
                           i18n("Contenido demo client requires PHP 5 or higher"),
                           i18n("The Contenido demo client requires PHP 5 or higher. If you want to install the demo client, please update your PHP version."));
        }

        $this->runTest(phpversion(),
                       C_SEVERITY_NONE,
                       "PHP Version");

        $this->runTest(php_uname(),
                       C_SEVERITY_NONE,
                       "php_uname()");

        $this->runTest($_SERVER["SERVER_SOFTWARE"],
                       C_SEVERITY_NONE,
                       "Server Software");

        $this->runTest(isPHPCompatible(),
                C_SEVERITY_ERROR,
                i18n("PHP Version lower than 4.1.0"),
                i18n("Contenido requires PHP 4.1.0 or higher as it uses functions first introduced with PHP 4.1.0. Please update your PHP4 version."));

        $this->runTest(!isPHP423(),
                       C_SEVERITY_WARNING,
                       i18n("PHP Version equal to 4.2.3"),
                       sprintf(i18n("The versions 4.2.2/4.2.3 of PHP can contain critical bugs which make it impossible to run Contenido with. You may continue, however, please update your PHP4 version first if any kind of problems occur. For more information, have a look at %s"),
                       '<a target="_blank" href="http://bugs.php.net/bug.php?id=19404">http://bugs.php.net/bug.php?id=19404</a>'));

        $this->runTest(getSafeModeStatus(),
                       C_SEVERITY_NONE,
                       "getSafeModeStatus()");

        $this->runTest(getSafeModeGidStatus(),
                       C_SEVERITY_NONE,
                       "getSafeModeGidStatus()");

        $this->runTest(getSafeModeIncludeDir(),
                       C_SEVERITY_NONE,
                       "getSafeModeIncludeDir()");

        $this->runTest(getOpenBasedir(),
                       C_SEVERITY_NONE,
                       "getOpenBasedir()");

        $this->runTest(getDisabledFunctions(),
                       C_SEVERITY_NONE,
                       "getDisabledFunctions()");

        $this->runTest(canPHPurlfopen(),
                       C_SEVERITY_NONE,
                       "canPHPurlfopen()");

        $this->runTest(getPHPDisplayErrorSetting(),
                       C_SEVERITY_NONE,
                       "getPHPDisplayErrorSetting()");

        $this->runTest(getPHPFileUploadSetting(),
                       C_SEVERITY_WARNING,
                       i18n("File uploads disabled"),
                       sprintf(i18n("Your PHP version is not configured for file uploads. You can't upload files using Contenido's file manager unless you configure PHP for file uploads. See %s for more information"),
                       '<a target="_blank" href="http://www.php.net/manual/en/ini.core.php#ini.file-uploads">http://www.php.net/manual/en/ini.core.php#ini.file-uploads</a>'));

        $this->runTest(getPHPGPCOrder(),
                       C_SEVERITY_NONE,
                       "getPHPGPCOrder()");

        $this->runTest(!getPHPMagicQuotesRuntime(),
                       C_SEVERITY_ERROR,
                       i18n("PHP setting 'magic_quotes_runtime' is turned on"),
                       i18n("The PHP setting 'magic_quotes_runtime' is turned on. Contenido has been developed to comply with magic_quotes_runtime=Off as this is the PHP default setting. You have to change this directive to make Contenido work."));

        $this->runTest(!getPHPMagicQuotesSybase(),
                       C_SEVERITY_ERROR,
                       i18n("PHP Setting 'magic_quotes_sybase' is turned on"),
                       i18n("The PHP Setting 'magic_quotes_sybase' is turned on. Contenido has been developed to comply with magic_quotes_sybase=Off as this is the PHP default setting. You have to change this directive to make Contenido work."));

        $this->runTest(getPHPMaxExecutionTime(),
                       C_SEVERITY_NONE,
                       "getPHPMaxExecutionTime()");

        $this->runTest(intval(getPHPMaxExecutionTime()) >= 30,
                       C_SEVERITY_WARNING,
                       i18n("PHP maximum execution time is less than 30 seconds"),
                       i18n("PHP is configured for a maximum execution time of less than 30 seconds. This could cause problems with slow web servers and/or long operations in the backend. Our recommended execution time is 120 seconds on slow web servers, 60 seconds for medium ones and 30 seconds for fast web servers."));

        $this->runTest(getPHPOpenBasedirSetting(),
                       C_SEVERITY_NONE,
                       "getPHPOpenBasedirSetting()");

        $iResult = checkOpenBasedirCompatibility();

        switch ($iResult) {
            case E_BASEDIR_NORESTRICTION:
                $this->runTest(false, C_SEVERITY_NONE);
                break;
            case E_BASEDIR_DOTRESTRICTION:
                $this->runTest(false,
                               C_SEVERITY_ERROR,
                               i18n("open_basedir directive set to '.'"),
                               i18n("The directive open_basedir is set to '.' (e.g. current directory). This means that Contenido is unable to access files in a logical upper level in the filesystem. This will cause problems managing the Contenido frontends. Either add the full path of this Contenido installation to the open_basedir directive, or turn it off completely."));
                break;
            case E_BASEDIR_RESTRICTIONSUFFICIENT:
                $this->runTest(false,
                               C_SEVERITY_INFO,
                               i18n("open_basedir setting might be insufficient"),
                               i18n("Setup believes that the PHP directive open_basedir is configured sufficient, however, if you encounter errors like 'open_basedir restriction in effect. File <filename> is not within the allowed path(s): <path>', you have to adjust the open_basedir directive"));
                break;
            case E_BASEDIR_INCOMPATIBLE:
                $this->runTest(false,
                               C_SEVERITY_ERROR,
                               i18n("open_basedir directive incompatible"),
                               i18n("Setup has checked your PHP open_basedir directive and reckons that it is not sufficient. Please change the directive to include the Contenido installation or turn it off completely."));
                break;
        }

        $iMemoryLimit = return_bytes(getPHPIniSetting("memory_limit"));

        if ($iMemoryLimit > 0) {
            $this->runTest(($iMemoryLimit > 1024 * 1024 * 32),
                           C_SEVERITY_WARNING,
                           i18n("PHP memory_limit directive too small"),
                           i18n("The memory_limit directive is set to 32 MB or lower. This might be not enough for Contenido to operate correctly. We recommend to disable this setting completely, as this can cause problems with large Contenido projects."));
        }

        $this->runTest(!checkPHPSQLSafeMode(),
                       C_SEVERITY_ERROR,
                       i18n("PHP sql.safe_mode turned on"),
                       i18n("The PHP directive sql.safe_mode is turned on. This causes problems with the SQL queries issued by Contenido. Please turn that directive off."));

        $this->runTest(isPHPExtensionLoaded("gd"),
                       C_SEVERITY_WARNING,
                       i18n("PHP GD-Extension is not loaded"),
                       i18n("The PHP GD-Extension is not loaded. Some third-party modules rely on the GD functionality. If you don't enable the GD extension, you will encounter problems with modules like galleries."));

        $this->runTest(isPHPExtensionLoaded("pcre"),
                       C_SEVERITY_ERROR,
                       i18n("PHP PCRE Extension is not loaded"),
                       i18n("The PHP PCRE Extension is not loaded. Contenido uses PCRE-functions like preg_repace and preg_match and won't work without the PCRE Extension."));

        $this->runTest(isPHPExtensionLoaded("xml"),
                       C_SEVERITY_ERROR,
                       i18n("PHP XML Extension is not loaded"),
                       i18n("The PHP XML Extension is not loaded. Contenido uses XML-functions like xml_parser_create and won't work without the XML Extension."));

        $this->runTest(function_exists("xml_parser_create"),
                       C_SEVERITY_ERROR,
                       i18n("PHP XML Extension is not loaded"),
                       i18n("The PHP XML Extension is not loaded. Contenido uses XML-functions like xml_parser_create and won't work without the XML Extension."));

        $iResult = checkImageResizer();

        switch ($iResult) {
            case E_IMAGERESIZE_CANTCHECK:
                    $this->runTest(false,
                                   C_SEVERITY_WARNING,
                                   i18n("Unable to check for a suitable image resizer"),
                                   i18n("Setup has tried to check for a suitable image resizer (which is, for exampl, required for thumbnail creation), but was not able to clearly identify one. If thumbnails won't work, make sure you've got either the GD-extension or ImageMagick available."));
                    break;
            case E_IMAGERESIZE_NOTHINGAVAILABLE:
                    $this->runTest(false,
                                   C_SEVERITY_ERROR,
                                   i18n("No suitable image resizer available"),
                                   i18n("Setup checked your image resizing support, however, it was unable to find a suitable image resizer. Thumbnails won't work correctly or won't be looking good. Install the GD-Extension or ImageMagick"));
                    break;
        }

        /**
         * @todo: Check if ini_set can be used
         */

    }

    function doGDTests() {
        $this->runTest(function_exists("imagecreatefromgif"),
                       C_SEVERITY_INFO,
                       i18n("GD-Library GIF read support missing"),
                       i18n("Your GD version doesn't support reading GIF files. This might cause problems with some modules."));

        $this->runTest(function_exists("imagegif"),
                       C_SEVERITY_INFO,
                       i18n("GD-Library GIF write support missing"),
                       i18n("Your GD version doesn't support writing GIF files. This might cause problems with some modules."));

        $this->runTest(function_exists("imagecreatefromjpeg"),
                       C_SEVERITY_INFO,
                       i18n("GD-Library JPEG read support missing"),
                       i18n("Your GD version doesn't support reading JPEG files. This might cause problems with some modules."));

        $this->runTest(function_exists("imagejpeg"),
                       C_SEVERITY_INFO,
                       i18n("GD-Library JPEG write support missing"),
                       i18n("Your GD version doesn't support writing JPEG files. This might cause problems with some modules."));

        $this->runTest(function_exists("imagecreatefrompng"),
                       C_SEVERITY_INFO,
                       i18n("GD-Library PNG read support missing"),
                       i18n("Your GD version doesn't support reading PNG files. This might cause problems with some modules."));

        $this->runTest(function_exists("imagepng"),
                       C_SEVERITY_INFO,
                       i18n("GD-Library PNG write support missing"),
                       i18n("Your GD version doesn't support writing PNG files. This might cause problems with some modules."));
    }

    function doMySQLTests() {

        list($handle, $status) = doMySQLConnect($_SESSION["dbhost"], $_SESSION["dbuser"], $_SESSION["dbpass"]);

        $extension = getMySQLDatabaseExtension();
        if (CON_SETUP_MYSQLI == $extension) {
            $sErrorMessage = mysqli_error($handle->Link_ID);
        } elseif (CON_SETUP_MYSQL == $extension) {
            $sErrorMessage = mysql_error();
        } else {
            $sErrorMessage = '';
        }

        $this->runTest($status,
                       C_SEVERITY_ERROR,
                       i18n("MySQL database connect failed"),
                       sprintf(i18n("Setup was unable to connect to the MySQL Server (Server %s, Username %s). Please correct the MySQL data and try again.<br><br>The error message given was: %s"),
                       $_SESSION["dbhost"], $_SESSION["dbuser"], $sErrorMessage));

        if ($status == false) {
            return;
        }

        $db = new DB_Contenido($_SESSION["dbhost"], "", $_SESSION["dbuser"], $_SESSION["dbpass"]);

        $this->runTest(!$this->isSqlModeStrict(),
                       C_SEVERITY_ERROR, i18n("MySQL is running in strict mode"),
                       i18n("MySQL is running in strict mode, Contenido will not work with this mode. Please change your sql_mode!"));

        switch ($_SESSION["setuptype"]) {
            case "setup":

                $db = new DB_Contenido($_SESSION["dbhost"], "", $_SESSION["dbuser"], $_SESSION["dbpass"]);

                // Check if the database exists
                $status = checkMySQLDatabaseExists($db, $_SESSION["dbname"]);

                if ($status) {
                    // Yes, database exists
                    $db = new DB_Contenido($_SESSION["dbhost"], $_SESSION["dbname"], $_SESSION["dbuser"], $_SESSION["dbpass"]);
                    $db->connect();

                    // Check MySQLi extension
                    /*if(hasMySQLiExtension() === false) {
                        $this->runTest(false, C_SEVERITY_INFO, i18n("MySQLi extension is not installed"), i18n("The MySQLi extension is not installed on this server. You can proceed with the installation, but the MySQL extension is deprecated and will be removed in a further PHP version."));
                    }*/

                    // Check if data already exists
                    $sql = 'SHOW TABLES LIKE "%s_actions"';

                    $db->query(sprintf($sql, $_SESSION["dbprefix"]));

                    if ($db->next_record()) {
                        $this->runTest(false,
                                       C_SEVERITY_ERROR,
                                       i18n("MySQL database already exists and seems to be filled"),
                                       sprintf(i18n("Setup checked the database %s and found the table %s. It seems that you already have a Contenido installation in this database. If you want to install anyways, change the database prefix. If you want to upgrade from a previous version, choose 'upgrade' as setup type."),
                                       $_SESSION["dbname"], sprintf("%s_actions", $_SESSION["dbprefix"])));
                        return;
                    }

                    // Check if data already exists
                    $sql = 'SHOW TABLES LIKE "%s_test"';

                    $db->query(sprintf($sql, $_SESSION["dbprefix"]));

                    if ($db->next_record()) {
                        $this->runTest(false,
                                       C_SEVERITY_ERROR,
                                       i18n("MySQL test table already exists in the database"),
                                       sprintf(i18n("Setup checked the database %s and found the test table %s. Please remove it before continuing."),
                                       $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"])));
                        return;
                    }
                    // Good, table doesn't exist. Check for database permisions
                    $status = checkMySQLTableCreation($db, $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"]));

                    if (!$status) {
                        $this->runTest(false,
                                       C_SEVERITY_ERROR,
                                       i18n("Unable to create tables in the selected MySQL database"),
                                       sprintf(i18n("Setup tried to create a test table in the database %s and failed. Please assign table creation permissions to the database user you entered, or ask an administrator to do so."),
                                       $_SESSION["dbname"]));
                        return;
                    }

                    // Good, check if we can lock the table
                    $status = checkMySQLLockTable ($db, $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"]));

                    if (!$status) {
                        $this->runTest(false,
                                       C_SEVERITY_WARNING,
                                       i18n("Unable to lock tables in the selected MySQL database"),
                                       sprintf(i18n("Setup tried to lock a test table in the database %s and failed. You can continue, however, you should be aware of possible data losses due to missing locking. It is highly recommended that you assign the LOCK TABLES permission to your database user!"),
                                       $_SESSION["dbname"]));
                        $_SESSION["nolock"] = 'true';
                    } else {
                        $_SESSION["nolock"] = 'false';
                    }

                    // Good, we could create a table. Now remove it again
                    $status = checkMySQLDropTable ($db, $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"]));

                    if (!$status) {
                        $this->runTest(false,
                                       C_SEVERITY_WARNING,
                                       i18n("Unable to remove the test table"),
                                       sprintf(i18n("Setup tried to remove the test table %s in the database %s and failed due to insufficient permissions. Please remove the table %s manually."),
                                       sprintf("%s_test", $_SESSION["dbprefix"]), $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"])));
                    }

                } else {
                    $db->connect();
                    // Check if database can be created
                    $status = checkMySQLDatabaseCreation($db, $_SESSION["dbname"]);

                    if (!$status) {
                        $this->runTest(false,
                                       C_SEVERITY_ERROR,
                                       i18n("Unable to create the database in the MySQL server"),
                                       sprintf(i18n("Setup tried to create a test database and failed. Please assign database creation permissions to the database user you entered, ask an administrator to do so, or create the database manually.")
                        ));
                        return;
                    }

                    // Check for database permisions
                    $status = checkMySQLTableCreation($db, $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"]));

                    if (!$status) {
                        $this->runTest(false,
                                       C_SEVERITY_ERROR,
                                       i18n("Unable to create tables in the selected MySQL database"),
                                       sprintf(i18n("Setup tried to create a test table in the database %s and failed. Please assign table creation permissions to the database user you entered, or ask an administrator to do so."),
                                       $_SESSION["dbname"]));
                        return;
                    }

                    // Good, check if we can lock the table
                    $status = checkMySQLLockTable($db, $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"]));

                    if (!$status) {
                        $this->runTest(false,
                                       C_SEVERITY_WARNING,
                                       i18n("Unable to lock tables in the selected MySQL database"),
                                       sprintf(i18n("Setup tried to lock a test table in the database %s and failed. You can continue, however, you should be aware of possible data losses due to missing locking. It is highly recommended that you assign the LOCK TABLES permission to your database user!"),
                                       $_SESSION["dbname"]));
                        $_SESSION["nolock"] = 'true';
                    } else {
                        $_SESSION["nolock"] = 'false';
                    }

                    // Good, we could create a table. Now remove it again
                    $status = checkMySQLDropTable ($db, $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"]));

                    if (!$status) {
                        $this->runTest(false,
                                       C_SEVERITY_WARNING,
                                       i18n("Unable to remove the test table"),
                                       sprintf(i18n("Setup tried to remove the test table %s in the database %s and failed due to insufficient permissions. Please remove the table %s manually."),
                                       sprintf("%s_test", $_SESSION["dbprefix"]), $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"])));
                    }
                }
                break;
            case "migration":
                $db = new DB_Contenido($_SESSION["dbhost"], "", $_SESSION["dbuser"], $_SESSION["dbpass"]);

                // Check if the database exists
                $status = checkMySQLDatabaseExists($db, $_SESSION["dbname"]);

                if (!$status) {
                    $this->runTest(false,
                                   C_SEVERITY_ERROR,
                                   i18n("No data found for the migration"),
                                   sprintf(i18n("Setup tried to locate the data for the migration, however, the database %s doesn't exist. You need to copy your database first before running setup."),
                                   $_SESSION["dbname"]));
                        return;
                }

                $db = new DB_Contenido($_SESSION["dbhost"], $_SESSION["dbname"], $_SESSION["dbuser"], $_SESSION["dbpass"]);

                // Check if data already exists
                $sql = 'SHOW TABLES LIKE "%s_actions"';

                $db->query(sprintf($sql, $_SESSION["dbprefix"]));

                if (!$db->next_record()) {
                    $this->runTest(false,
                                   C_SEVERITY_ERROR,
                                   i18n("No data found for the migration"),
                                   sprintf(i18n("Setup tried to locate the data for the migration, however, the database %s contains no tables. You need to copy your database first before running setup."),
                                   $_SESSION["dbname"]));
                        return;
                }

                // Good, check if we can lock the table
                $status = checkMySQLLockTable ($db, $_SESSION["dbname"], sprintf("%s_actions", $_SESSION["dbprefix"]));

                if (!$status) {
                    $this->runTest(false,
                                   C_SEVERITY_WARNING,
                                   i18n("Unable to lock tables in the selected MySQL database"),
                                   sprintf(i18n("Setup tried to lock a test table in the database %s and failed. You can continue, however, you should be aware of possible data losses due to missing locking. It is highly recommended that you assign the LOCK TABLES permission to your database user!"),
                                   $_SESSION["dbname"]));
                    $_SESSION["nolock"] = 'true';
                } else {
                    $_SESSION["nolock"] = 'false';
                }
                break;
            case "upgrade":
                $db = new DB_Contenido($_SESSION["dbhost"], "", $_SESSION["dbuser"], $_SESSION["dbpass"]);

                // Check if the database exists
                $status = checkMySQLDatabaseExists($db, $_SESSION["dbname"]);

                if (!$status) {
                    $this->runTest(false,
                                   C_SEVERITY_ERROR,
                                   i18n("No data found for the upgrade"),
                                   sprintf(i18n("Setup tried to locate the data for the upgrade, however, the database %s doesn't exist. You need to copy your database first before running setup."),
                                   $_SESSION["dbname"]));
                    return;
                }

                $db = new DB_Contenido($_SESSION["dbhost"], $_SESSION["dbname"], $_SESSION["dbuser"], $_SESSION["dbpass"]);

                // Check if data already exists
                $sql = 'SHOW TABLES LIKE "%s_actions"';

                $db->query(sprintf($sql, $_SESSION["dbprefix"]));

                if (!$db->next_record()) {
                    $this->runTest(false,
                                   C_SEVERITY_ERROR,
                                   i18n("No data found for the upgrade"),
                                   sprintf(i18n("Setup tried to locate the data for the upgrade, however, the database %s contains no tables. You need to copy your database first before running setup."),
                                   $_SESSION["dbname"]));
                    return;
                }

                // Good, check if we can lock the table
                $status = checkMySQLLockTable($db, $_SESSION["dbname"], sprintf("%s_actions", $_SESSION["dbprefix"]));

                if (!$status) {
                    $this->runTest(false,
                                   C_SEVERITY_WARNING,
                                   i18n("Unable to lock tables in the selected MySQL database"),
                                   sprintf(i18n("Setup tried to lock a test table in the database %s and failed. You can continue, however, you should be aware of possible data losses due to missing locking. It is highly recommended that you assign the LOCK TABLES permission to your database user!"),
                                   $_SESSION["dbname"]));
                    $_SESSION["nolock"] = 'true';
                } else {
                    $_SESSION["nolock"] = 'false';
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
        $db = new DB_Contenido($_SESSION["dbhost"], "", $_SESSION["dbuser"], $_SESSION["dbpass"]);

        $db->query('SELECT LOWER(@@GLOBAL.sql_mode) AS sql_mode');
        if ($db->next_record()) {
            if (strpos($db->f('sql_mode'), 'strict_trans_tables') !== false || strpos($db->f('sql_mode'), 'strict_all_tables') !== false) {
                return true;
            }
        }
        return false;
    }

    function doFilesystemTests() {
        $this->logFilePrediction("contenido/logs/errorlog.txt",
                                 C_SEVERITY_WARNING);

        $this->logFilePrediction("contenido/logs/setuplog.txt",
                                 C_SEVERITY_WARNING);

        $this->logFilePrediction("contenido/cronjobs/pseudo-cron.log",
                                 C_SEVERITY_WARNING);

        $this->logFilePrediction("contenido/cronjobs/session_cleanup.php.job",
                                 C_SEVERITY_WARNING);

        $this->logFilePrediction("contenido/cronjobs/send_reminder.php.job",
                                 C_SEVERITY_WARNING);

        $this->logFilePrediction("contenido/cronjobs/optimize_database.php.job",
                                 C_SEVERITY_WARNING);

        $this->logFilePrediction("contenido/cronjobs/move_old_stats.php.job",
                                 C_SEVERITY_WARNING);

        $this->logFilePrediction("contenido/cronjobs/move_articles.php.job",
                                 C_SEVERITY_WARNING);

        $this->logFilePrediction("contenido/cronjobs/linkchecker.php.job",
                                 C_SEVERITY_WARNING);

        $this->logFilePrediction("contenido/cronjobs/run_newsletter_job.php.job",
                                 C_SEVERITY_WARNING);

        $this->logFilePrediction("contenido/cronjobs/setfrontenduserstate.php.job",
                                 C_SEVERITY_WARNING);

        $this->logFilePrediction("contenido/cache/",
                                 C_SEVERITY_WARNING);

        $this->logFilePrediction("contenido/temp/",
                                 C_SEVERITY_WARNING);

        if ($_SESSION["setuptype"] == "setup" || ($_SESSION["setuptype"] == "migration" && is_dir("../" . "cms/") )) {
            // Setup mode or migration mode with a existing default client frontend path
            $this->logFilePrediction("cms/cache/",
                                     C_SEVERITY_WARNING);
            $this->logFilePrediction("cms/css/",
                                     C_SEVERITY_WARNING);
            $this->logFilePrediction("cms/js/",
                                     C_SEVERITY_WARNING);
            $this->logFilePrediction("cms/logs/",
                                     C_SEVERITY_WARNING);
            $this->logFilePrediction("cms/templates/",
                                     C_SEVERITY_WARNING);
            $this->logFilePrediction("cms/upload/",
                                     C_SEVERITY_WARNING);
            $this->logFilePrediction("cms/version/",
                                     C_SEVERITY_WARNING);
            $this->logFilePrediction("cms/version/css/",
                                     C_SEVERITY_WARNING);
            $this->logFilePrediction("cms/version/js/",
                                     C_SEVERITY_WARNING);
            $this->logFilePrediction("cms/version/layout/",
                                     C_SEVERITY_WARNING);
            $this->logFilePrediction("cms/version/module/",
                                     C_SEVERITY_WARNING);
            $this->logFilePrediction("cms/version/templates/",
                                     C_SEVERITY_WARNING);
            $this->logFilePrediction("cms/config.php",
                                     C_SEVERITY_WARNING);
        }

        if ($_SESSION["configmode"] == "save") {
            $this->logFilePrediction("contenido/includes/config.php",
                                     C_SEVERITY_ERROR);
        }
    }

    function logFilePrediction($sFile, $iSeverity) {
        $status = canWriteFile("../" . $sFile);

        $sTitle = sprintf(i18n("Can't write %s"), $sFile);
        $sMessage = sprintf(i18n("Setup or Contenido can't write to the file %s. Please change the file permissions to correct this problem."), $sFile);

        if ($status == false) {
            if (file_exists("../" . $sFile)) {
                $sTarget = "../" . $sFile;

                $iPerm = predictCorrectFilepermissions("../" . $sFile);

                switch ($iPerm) {
                    case C_PREDICT_WINDOWS:
                        $sPredictMessage = i18n("Your Server runs Windows. Due to that, Setup can't recommend any file permissions.");
                        break;
                    case C_PREDICT_NOTPREDICTABLE:
                        $sPredictMessage = sprintf(i18n("Due to a very restrictive environment, an advise is not possible. Ask your system administrator to enable write access to the file %s, especially in environments where ACL (Access Control Lists) are used."), $sFile);
                        break;
                    case C_PREDICT_CHANGEPERM_SAMEOWNER:
                        $mfileperms = substr(sprintf("%o", fileperms("../".$sFile)), -3);
                        $mfileperms{0} = intval($mfileperms{0}) | 0x6;
                        $sPredictMessage = sprintf(i18n("Your web server and the owner of your files are identical. You need to enable write access for the owner, e.g. using chmod u+rw %s, setting the file mask to %s or set the owner to allow writing the file."), $sFile, $mfileperms);
                        break;
                    case C_PREDICT_CHANGEPERM_SAMEGROUP:
                        $mfileperms = substr(sprintf("%o", fileperms("../".$sFile)), -3);
                        $mfileperms{1} = intval($mfileperms{1}) | 0x6;
                        $sPredictMessage = sprintf(i18n("Your web server's group and the group of your files are identical. You need to enable write access for the group, e.g. using chmod g+rw %s, setting the file mask to %s or set the group to allow writing the file."), $sFile, $mfileperms);
                        break;
                    case C_PREDICT_CHANGEPERM_OTHERS:
                        $mfileperms = substr(sprintf("%o", fileperms("../".$sFile)), -3);
                        $mfileperms{2} = intval($mfileperms{2}) | 0x6;
                        $sPredictMessage = sprintf(i18n("Your web server is not equal to the file owner, and is not in the webserver's group. It would be highly insecure to allow world write acess to the files. If you want to install anyways, enable write access for all others, e.g. using chmod o+rw %s, setting the file mask to %s or set the others to allow writing the file."), $sFile, $mfileperms);
                        break;
                }
            } else {
                $sTarget = dirname("../".$sFile);

                $iPerm = predictCorrectFilepermissions($sTarget);

                switch ($iPerm) {
                    case C_PREDICT_WINDOWS:
                        $sPredictMessage = i18n("Your Server runs Windows. Due to that, Setup can't recommend any directory permissions.");
                        break;
                    case C_PREDICT_NOTPREDICTABLE:
                        $sPredictMessage = sprintf(i18n("Due to a very restrictive environment, an advise is not possible. Ask your system administrator to enable write access to the file or directory %s, especially in environments where ACL (Access Control Lists) are used."), dirname($sFile));
                        break;
                    case C_PREDICT_CHANGEPERM_SAMEOWNER:
                        $mfileperms = substr(sprintf("%o", @fileperms($sTarget)), -3);
                        $mfileperms{0} = intval($mfileperms{0}) | 0x6;
                        $sPredictMessage = sprintf(i18n("Your web server and the owner of your directory are identical. You need to enable write access for the owner, e.g. using chmod u+rw %s, setting the directory mask to %s or set the owner to allow writing the directory."), dirname($sFile), $mfileperms);
                        break;
                    case C_PREDICT_CHANGEPERM_SAMEGROUP:
                        $mfileperms = substr(sprintf("%o", @fileperms($sTarget)), -3);
                        $mfileperms{1} = intval($mfileperms{1}) | 0x6;
                        $sPredictMessage = sprintf(i18n("Your web server's group and the group of your directory are identical. You need to enable write access for the group, e.g. using chmod g+rw %s, setting the directory mask to %s or set the group to allow writing the directory."), dirname($sFile), $mfileperms);
                        break;
                    case C_PREDICT_CHANGEPERM_OTHERS:
                        $mfileperms = substr(sprintf("%o", @fileperms($sTarget)), -3);
                        $mfileperms{2} = intval($mfileperms{2}) | 0x6;
                        $sPredictMessage = sprintf(i18n("Your web server is not equal to the directory owner, and is not in the webserver's group. It would be highly insecure to allow world write acess to the directory. If you want to install anyways, enable write access for all others, e.g. using chmod o+rw %s, setting the directory mask to %s or set the others to allow writing the directory."), dirname($sFile), $mfileperms);
                        break;
                }
            }

            $this->runTest(false,
                           $iSeverity,
                           $sTitle,
                           $sMessage . "<br><br>" . $sPredictMessage);
        }
    }

}

?>