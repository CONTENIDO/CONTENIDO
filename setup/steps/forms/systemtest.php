<?php
/**
 * This file contains the system test setup mask.
 *
 * @package Setup
 * @subpackage Form
 * @version SVN Revision $Rev:$
 *
 * @author Unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * System test setup mask.
 *
 * @package Setup
 * @subpackage Form
 */
class cSetupSystemtest extends cSetupMask {

    private $systemtest;

    function cSetupSystemtest($step, $previous, $next) {
        global $cfg;

        cSetupMask::cSetupMask("templates/setup/forms/systemtest.tpl", $step);

        $errors = false;

        $this->setHeader(i18n("System Test", "setup"));
        $this->_oStepTemplate->set("s", "TITLE", i18n("System Test", "setup"));
        $this->_oStepTemplate->set("s", "DESCRIPTION", i18n("Your system has been tested for compatibility with CONTENIDO:", "setup"));

        // reload i18n for contenido locale
        i18nInit('../data/locale/', $_SESSION['language']);
        
        $this->systemtest = new cSystemtest($cfg);
        $this->systemtest->runTests(false);
        $this->systemtest->testFilesystem($_SESSION["configmode"] == "save", false);
        $this->systemtest->testFrontendFolderCreation();

        $cHTMLErrorMessageList = new cHTMLErrorMessageList();

        if (is_null(getMySQLDatabaseExtension())) {
            $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("PHP MySQL Extension missing", "setup"), i18n("CONTENIDO requires the MySQL or MySQLi extension to access MySQL databases. Please configure PHP to use either MySQL or MySQLi.", "setup"));
        } else if ($this->systemtest->testMySQL($_SESSION["dbhost"], $_SESSION["dbuser"], $_SESSION["dbpass"]) == cSystemtest::CON_MYSQL_OK) {
            $this->initDB();
        }

        $this->checkCountryLanguageCode();

        $cHTMLFoldableErrorMessages = array();

        if ($_SESSION["setuptype"] == 'upgrade') {
            // Check if there is an old version of integrated plugins installed
            // in upgrademode.
            $this->doExistingOldPluginTests();

            // Check if user updates a system lower than 4.9
            $this->doChangedDirsFilesTest();
        }

        if((int)ini_get('max_execution_time') < 60){
            $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_WARNING, i18n("Unable to set max_execution_time", "setup"), i18n("Your PHP configuration for max_execution_time can not be changed via this script. We recommend setting the value for the installation or upgrade process to 60 seconds. You can try to execute the process with your current configuration. If the process is stopped, the system is not usable (any longer)", "setup"));
        }

        $results = $this->systemtest->getResults();

        foreach ($results as $result) {
            if ($result["result"]) {
                continue;
            }

            switch ($result["severity"]) {
                case cSystemtest::C_SEVERITY_INFO:
                    $icon = "images/icons/info.png";
                    $iconDescription = i18n("Information", "setup");
                    break;
                case cSystemtest::C_SEVERITY_WARNING:
                    $icon = "images/icons/warning.png";
                    $iconDescription = i18n("Warning", "setup");
                    break;
                case cSystemtest::C_SEVERITY_ERROR:
                    $icon = "images/icons/error.png";
                    $iconDescription = i18n("Fatal error", "setup");
                    $errors = true;
                    break;
            }
            $cHTMLFoldableErrorMessages[] = new cHTMLFoldableErrorMessage($result["headline"], $result["message"], $icon, $iconDescription);
        }

        if (count($cHTMLFoldableErrorMessages) == 0) {
            $cHTMLFoldableErrorMessages[] = new cHTMLFoldableErrorMessage(i18n("No problems detected", "setup"), i18n("Setup could not detect any problems with your system environment", "setup"), "images/icons/info.png");
        }

        $cHTMLErrorMessageList->setContent($cHTMLFoldableErrorMessages);

        $this->_oStepTemplate->set("s", "CONTROL_TESTRESULTS", $cHTMLErrorMessageList->render());

        if ($errors == true) {
            $this->setNavigation($previous, "");

            switch ($_SESSION['setuptype']) {
                case "upgrade":
                    $thisStep = 'upgrade' . $step;
                    break;
                case "setup":
                default:
                    $thisStep = 'setup' . $step;
                    break;
            }

            $link = new cHTMLLink("#");
            $link->attachEventDefinition("pageAttach", "onclick", "document.setupform.step.value = '" . $thisStep . "';");
            $link->attachEventDefinition("submitAttach", "onclick", "document.setupform.submit();");
            $link->setClass("nav navRefresh");
            $link->setContent("<span>R</span>"); // @todo traslation for "refresh"

            $this->_oStepTemplate->set("s", "NEXT", $link->render());
        } else {
            $this->setNavigation($previous, $next);
        }
    }

    function doExistingOldPluginTests() {
        $db = getSetupMySQLDBConnection(false);
        $message = '';

        // get all tables in database and list it into array
        $avariableTableNames = array();
        $tableNames = $db->getTableNames();
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
                $message .= sprintf(i18n('An old Version of Plugin %s is installed on your system.', "setup") . "<br>\n", $plugin);
            }
        }

        if ($message) {
            $message .= '<br>' . i18n('Please remove all old plugins before you continue. To transfer old plugin data, please copy the old plugin data tables into the new plugin data tables after the installation. The new plugintable names are the same, but contains the table prefix of CONTENIDO. Also delete the old plugin tables after data transfer.', "setup");

            $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_WARNING, i18n("Old Plugins are still installed", "setup"), $message);
        }
    }

    function doChangedDirsFilesTest() {
        global $cfg;

        $db = getSetupMySQLDBConnection(false);
        $version = getContenidoVersion($db, $cfg['tab']['system_prop']);

        // Display message about changed directories/files when user updates a
        // system lower than 4.9
        if ($version && version_compare('4.9', $version) > 0) {
            $message = i18n("You are updating a previous version of CONTENIDO to %s. Some directories/files have been moved to other sections in %s.\n\nPlease ensure to copy contenido/includes/config.php to data/config/production/config.php and also other configuration files within contenido/includes/ to data/config/production/.", "setup");
            $message = sprintf($message, '4.9', '4.9');
            $message = nl2br($message);
            $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_WARNING, i18n("Attention: Some directories/files have been moved", "setup"), $message);
        }
    }

    function checkCountryLanguageCode() {
        if ($_SESSION["setuptype"] != 'upgrade') {
            return;
        }

        $errors = array();

        cDb::setDefaultConfiguration($GLOBALS['cfg']['db']);

        $clientLanguageCollection = new cApiClientLanguageCollection();
        $clientLanguageCollection->query();

        while ($item = $clientLanguageCollection->next()) {
            $client = $item->getField('idclient');
            $lang = $item->getField('idlang');

            $oLanguage = new cApiLanguage();
            $oLanguage->loadByPrimaryKey($lang);

            $languageCode = $oLanguage->getProperty("language", "code", $client);
            $contryCode = $oLanguage->getProperty("country", "code", $client);

            $oClient = new cApiClient();
            $oClient->loadByPrimaryKey($client);
            $clientName = $oClient->getField('name');

            if (strlen($languageCode) == 0 || strlen($contryCode) == 0) {
                $langName = $oLanguage->getField('name');

                $oClient = new cApiClient();
                $oClient->loadByPrimaryKey($client);

                array_push($errors, sprintf(i18n('Language "%s" (%s) of the client "%s" (%s) is configured without ISO language code.', "setup"), $langName, $lang, $clientName, $client));
            }
        }

        if (count($errors) > 0) {
            $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("The ISO codes are necessary to convert module translations.", "setup"), implode('<br/>', $errors) );
        }
    }

    function initDB() {
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

                    if ($db->nextRecord()) {
                        $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("MySQL database already exists and seems to be filled", "setup"), sprintf(i18n("Setup checked the database %s and found the table %s. It seems that you already have a CONTENIDO installation in this database. If you want to install anyways, change the database prefix. If you want to upgrade from a previous version, choose 'upgrade' as setup type.", "setup"), $_SESSION["dbname"], sprintf("%s_actions", $_SESSION["dbprefix"])));
                        return;
                    }

                    // Check if data already exists
                    $db->query('SHOW TABLES LIKE "%s_test"', $_SESSION["dbprefix"]);
                    if ($db->nextRecord()) {
                        $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("MySQL test table already exists in the database", "setup"), sprintf(i18n("Setup checked the database %s and found the test table %s. Please remove it before continuing.", "setup"), $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"])));
                        return;
                    }

                    // Good, table doesn't exist. Check for database permisions
                    $status = checkMySQLTableCreation($db, $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"]));
                    if (!$status) {
                        $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("Unable to create tables in the selected MySQL database", "setup"), sprintf(i18n("Setup tried to create a test table in the database %s and failed. Please assign table creation permissions to the database user you entered, or ask an administrator to do so.", "setup"), $_SESSION["dbname"]));
                        return;
                    }

                    // Good, we could create a table. Now remove it again
                    $status = checkMySQLDropTable($db, $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"]));
                    if (!$status) {
                        $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_WARNING, i18n("Unable to remove the test table", "setup"), sprintf(i18n("Setup tried to remove the test table %s in the database %s and failed due to insufficient permissions. Please remove the table %s manually.", "setup"), sprintf("%s_test", $_SESSION["dbprefix"]), $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"])));
                    }
                } else {
                    $db->connect();
                    // Check if database can be created
                    $status = checkMySQLDatabaseCreation($db, $_SESSION["dbname"]);
                    if (!$status) {
                        $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("Unable to create the database in the MySQL server", "setup"), sprintf(i18n("Setup tried to create a test database and failed. Please assign database creation permissions to the database user you entered, ask an administrator to do so, or create the database manually.", "setup")));
                        return;
                    }

                    // Check for database permisions
                    $status = checkMySQLTableCreation($db, $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"]));
                    if (!$status) {
                        $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("Unable to create tables in the selected MySQL database", "setup"), sprintf(i18n("Setup tried to create a test table in the database %s and failed. Please assign table creation permissions to the database user you entered, or ask an administrator to do so.", "setup"), $_SESSION["dbname"]));
                        return;
                    }

                    // Good, we could create a table. Now remove it again
                    $status = checkMySQLDropTable($db, $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"]));
                    if (!$status) {
                        $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_WARNING, i18n("Unable to remove the test table", "setup"), sprintf(i18n("Setup tried to remove the test table %s in the database %s and failed due to insufficient permissions. Please remove the table %s manually.", "setup"), sprintf("%s_test", $_SESSION["dbprefix"]), $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"])));
                    }
                }
                break;
            case "upgrade":
                $db = getSetupMySQLDBConnection(false);

                // Check if the database exists
                $status = checkMySQLDatabaseExists($db, $_SESSION["dbname"]);
                if (!$status) {
                    $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("No data found for the upgrade", "setup"), sprintf(i18n("Setup tried to locate the data for the upgrade, however, the database %s doesn't exist. You need to copy your database first before running setup.", "setup"), $_SESSION["dbname"]));
                    return;
                }

                $db = getSetupMySQLDBConnection();

                // Check if data already exists
                $sql = 'SHOW TABLES LIKE "%s_actions"';
                $db->query(sprintf($sql, $_SESSION["dbprefix"]));
                if (!$db->nextRecord()) {
                    $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("No data found for the upgrade", "setup"), sprintf(i18n("Setup tried to locate the data for the upgrade, however, the database %s contains no tables. You need to copy your database first before running setup.", "setup"), $_SESSION["dbname"]));
                    return;
                }

                break;
        }
    }

}