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

    private $systemtest;

    function cSetupSystemtest($step, $previous, $next) {
        global $cfg;

        cSetupMask::cSetupMask("templates/setup/forms/systemtest.tpl", $step);

        $errors = false;

        $this->setHeader(i18n("System Test"));
        $this->_oStepTemplate->set("s", "TITLE", i18n("System Test"));
        $this->_oStepTemplate->set("s", "DESCRIPTION", i18n("Your system has been tested for compatibility with CONTENIDO:"));

        $this->systemtest = new cSystemtest($cfg);
        $this->systemtest->runTests(false);
        $this->systemtest->testFilesystem($_SESSION["configmode"] == "save", $_SESSION["setuptype"] == "setup");

        $cHTMLErrorMessageList = new cHTMLErrorMessageList();

        if (!(hasMySQLExtension() || hasMySQLiExtension())) {
            $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("PHP MySQL Extension missing"), i18n("CONTENIDO requires the MySQL or MySQLi extension to access MySQL databases. Please configure PHP to use either MySQL or MySQLi."));
        } else if($this->systemtest->testMySQL($_SESSION["dbhost"], $_SESSION["dbuser"], $_SESSION["dbpass"]) == cSystemtest::CON_MYSQL_OK) {
            $this->initDB();
        }

        $cHTMLFoldableErrorMessages = array();

        if ($_SESSION["setuptype"] == 'upgrade') {
            // Check if there is an old version of integrated plugins installed
            // in upgrademode.
            $this->doExistingOldPluginTests();

            // Check if user updates a system lower than 4.9
            $this->doChangedDirsFilesTest();
        }

        $results = $this->systemtest->getResults();

        foreach ($results as $result) {
            if($result["result"]) {
                continue;
            }

            switch ($result["severity"]) {
                case cSystemtest::C_SEVERITY_INFO:
                    $icon = "images/icons/info.png";
                    $iconDescription = i18n("Information");
                    break;
                case cSystemtest::C_SEVERITY_WARNING:
                    $icon = "images/icons/warning.png";
                    $iconDescription = i18n("Warning");
                    break;
                case cSystemtest::C_SEVERITY_ERROR:
                    $icon = "images/icons/error.png";
                    $iconDescription = i18n("Fatal error");
                    $errors = true;
                    break;
            }
            $cHTMLFoldableErrorMessages[] = new cHTMLFoldableErrorMessage($result["headline"], $result["message"], $icon, $iconDescription);
        }

        if (count($cHTMLFoldableErrorMessages) == 0) {
            $cHTMLFoldableErrorMessages[] = new cHTMLFoldableErrorMessage(i18n("No problems detected"), i18n("Setup could not detect any problems with your system environment"), "images/icons/info.png");
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
            $link->attachEventDefinition("pageAttach", "onclick", "document.setupform.step.value = '".$thisStep."';");
            $link->attachEventDefinition("submitAttach", "onclick", "document.setupform.submit();");

            $refreshSetup = new cHTMLAlphaImage();
            $refreshSetup->setSrc(CON_SETUP_CONTENIDO_HTML_PATH . "images/but_refresh.gif");
            $refreshSetup->setMouseOver(CON_SETUP_CONTENIDO_HTML_PATH . "images/but_refresh.gif");
            $refreshSetup->setClass("button");

            $link->setContent($refreshSetup);

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
                $message .= sprintf(i18n('An old Version of Plugin %s is installed on your system.') . "<br>\n", $plugin);
            }
        }

        if ($message) {
            $message .= '<br>' . i18n('Please remove all old plugins before you continue. To transfer old plugin data, please copy the old plugin data tables into the new plugin data tables after the installation. The new plugintable names are the same, but contains the table prefix of CONTENIDO. Also delete the old plugin tables after data transfer.');

            $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_WARNING, i18n("Old Plugins are still installed"), $message);
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
            $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_WARNING, i18n("Attention: Some directories/files have been moved"), $message);
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

                    if ($db->next_record()) {
                        $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("MySQL database already exists and seems to be filled"), sprintf(i18n("Setup checked the database %s and found the table %s. It seems that you already have a CONTENIDO installation in this database. If you want to install anyways, change the database prefix. If you want to upgrade from a previous version, choose 'upgrade' as setup type."), $_SESSION["dbname"], sprintf("%s_actions", $_SESSION["dbprefix"])));
                        return;
                    }

                    // Check if data already exists
                    $db->query('SHOW TABLES LIKE "%s_test"', $_SESSION["dbprefix"]);
                    if ($db->next_record()) {
                        $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("MySQL test table already exists in the database"), sprintf(i18n("Setup checked the database %s and found the test table %s. Please remove it before continuing."), $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"])));
                        return;
                    }

                    // Good, table doesn't exist. Check for database permisions
                    $status = checkMySQLTableCreation($db, $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"]));
                    if (!$status) {
                        $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("Unable to create tables in the selected MySQL database"), sprintf(i18n("Setup tried to create a test table in the database %s and failed. Please assign table creation permissions to the database user you entered, or ask an administrator to do so."), $_SESSION["dbname"]));
                        return;
                    }

                    // Good, we could create a table. Now remove it again
                    $status = checkMySQLDropTable($db, $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"]));
                    if (!$status) {
                        $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_WARNING, i18n("Unable to remove the test table"), sprintf(i18n("Setup tried to remove the test table %s in the database %s and failed due to insufficient permissions. Please remove the table %s manually."), sprintf("%s_test", $_SESSION["dbprefix"]), $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"])));
                    }
                } else {
                    $db->connect();
                    // Check if database can be created
                    $status = checkMySQLDatabaseCreation($db, $_SESSION["dbname"]);
                    if (!$status) {
                        $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("Unable to create the database in the MySQL server"), sprintf(i18n("Setup tried to create a test database and failed. Please assign database creation permissions to the database user you entered, ask an administrator to do so, or create the database manually.")));
                        return;
                    }

                    // Check for database permisions
                    $status = checkMySQLTableCreation($db, $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"]));
                    if (!$status) {
                        $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("Unable to create tables in the selected MySQL database"), sprintf(i18n("Setup tried to create a test table in the database %s and failed. Please assign table creation permissions to the database user you entered, or ask an administrator to do so."), $_SESSION["dbname"]));
                        return;
                    }

                    // Good, we could create a table. Now remove it again
                    $status = checkMySQLDropTable($db, $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"]));
                    if (!$status) {
                        $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_WARNING, i18n("Unable to remove the test table"), sprintf(i18n("Setup tried to remove the test table %s in the database %s and failed due to insufficient permissions. Please remove the table %s manually."), sprintf("%s_test", $_SESSION["dbprefix"]), $_SESSION["dbname"], sprintf("%s_test", $_SESSION["dbprefix"])));
                    }
                }
                break;
            case "upgrade":
                $db = getSetupMySQLDBConnection(false);

                // Check if the database exists
                $status = checkMySQLDatabaseExists($db, $_SESSION["dbname"]);
                if (!$status) {
                    $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("No data found for the upgrade"), sprintf(i18n("Setup tried to locate the data for the upgrade, however, the database %s doesn't exist. You need to copy your database first before running setup."), $_SESSION["dbname"]));
                    return;
                }

                $db = getSetupMySQLDBConnection();

                // Check if data already exists
                $sql = 'SHOW TABLES LIKE "%s_actions"';
                $db->query(sprintf($sql, $_SESSION["dbprefix"]));
                if (!$db->next_record()) {
                    $this->systemtest->storeResult(false, cSystemtest::C_SEVERITY_ERROR, i18n("No data found for the upgrade"), sprintf(i18n("Setup tried to locate the data for the upgrade, however, the database %s contains no tables. You need to copy your database first before running setup."), $_SESSION["dbname"]));
                    return;
                }

                break;
        }
    }
}