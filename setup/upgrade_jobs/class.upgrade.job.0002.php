<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Runs the upgrade job to takeover new module concept
 *
 * @package    CONTENIDO Setup upgrade
 * @version    0.1
 * @author     Murat Purc <murat@purc>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9
 */

if (!defined('CON_FRAMEWORK')) {
     die('Illegal call');
}


global $cfg;
checkAndInclude($cfg['path']['contenido'] . 'includes/functions.api.string.php');

class cUpgradeJob_0002 extends cUpgradeJobAbstract {

    /**
     * This method clean the name of moduls table $cfg['tab']['mod'].
     * Clean means all the charecters (�,*+#...) will be replaced.
     */
    private function _changeNameCleanUrl() {
        global $cfg;

        $myDb = clone $this->_oDb;
        $db = clone $this->_oDb;

        // select all moduls
        $sql = sprintf('SELECT * FROM %s', $cfg['tab']['mod']);
        $db->query($sql);

        while ($db->next_record()) {
            // clear name from not allow charecters
            $newName = cApiStrCleanURLCharacters($db->f('name'));
            if ($newName != $db->f('name')) {
                $mySql = sprintf("UPDATE %s SET name='%s' WHERE idmod=%s", $cfg['tab']['mod'], $newName, $db->f('idmod'));
                $myDb->query($mySql);
            }
        }
    }

    /**
     * This method will be transfer the moduls from $cfg['tab']['mod'] to the
     * file system.
     * This Method will be call by setup
     */
    private function _convertModulesToFile() {
        global $cfg;

        $db = clone $this->_oDb;

        if ($this->_setupType == 'upgrade') {
            // clean name of module (Umlaute, not allowed character ..),
            // prepare for file system
            $this->_changeNameCleanUrl();

            // select all frontendpaht of the clients, frontendpaht is in the
            // table $cfg['tab']['clients']
            $sql = sprintf('SELECT * FROM %s ORDER BY idmod', $cfg['tab']['mod']);
            $db->query($sql);

            $moduleHandler = new cModuleHandler();
            // create all main module directories
            $moduleHandler->createAllMainDirectories();

            while ($db->next_record()) {
                // init the ModulHandler with all data of the modul
                // inclusive client
                $moduleHandler->initWithDatabaseRow($db);

                // make new module only if modul not exist in directory
                if ($moduleHandler->modulePathExists() != true) {
                    // we need no error handling here because module could still
                    // exist from previous version
                    if ($moduleHandler->createModule($db->f('input'), $db->f('output')) == true) {
                        // save module translation
                        $translations = new cModuleFileTranslation($db->f('idmod'));
                        $translations->saveTranslations();
                    }
                }
            }
        }

        // remove input and output fields from db
        $sql = sprintf('ALTER TABLE %s DROP input, DROP output', $cfg['tab']['mod']);
        $db->query($sql);
    }

    public function execute() {
        global $cfg;
        global $client, $lang, $cfgClient;  // is used in cLayoutHandler below!!!

        // Makes the new concept of modules (save the modules to the file) save the translation

        // @fixme  Get rid of hacks below
        // @fixme  Logic below works only for setup, not for upgrade because of different clients and languages

        $clientBackup = $client;
        $langBackup = $lang;
        $client = 1;
        $lang = 1;

        if ($this->_setupType == 'upgrade') {
            $sql = "SHOW COLUMNS FROM %s LIKE 'frontendpath'";
            $sql = sprintf($sql, $cfg['tab']['clients']);

            $this->_oDb->query($sql);
            if ($this->_oDb->num_rows() != 0) {
                $sql = "SELECT * FROM " . $cfg['tab']['clients'];
                $this->_oDb->query($sql);

                while ($this->_oDb->next_record()) {
                    updateClientCache($this->_oDb->f("idclient"), $this->_oDb->f("htmlpath"), $this->_oDb->f("frontendpath"));
                }

                $sql = sprintf("ALTER TABLE %s DROP htmlpath", $cfg['tab']['clients']);
                $this->_oDb->query($sql);

                $sql = sprintf("ALTER TABLE %s DROP frontendpath", $cfg['tab']['clients']);
                $this->_oDb->query($sql);
            }
            checkAndInclude($cfg['path']['contenido_config'] . 'config.clients.php');
        }

        $cfgClient = updateClientCache();

        // Save all modules from db-table to the filesystem if exists
        $sql = "SHOW COLUMNS FROM %s LIKE 'OUTPUT'";
        $sql = sprintf($sql, $cfg['tab']['mod']);

        $this->_oDb->query($sql);
        if ($this->_oDb->num_rows() == 0) {
            cModuleHandler::setEncoding('ISO-8859-1');
            $this->_convertModulesToFile();
        }

        // Save layout from db-table to the file system
        // @fixme  cLayoutHandler uses global $client, we can't do this for all clients...
        $layoutInFile = new cLayoutHandler(1, '', $cfg, 1, $this->_oDb);
        $layoutInFile->upgrade();

        $db2 = getSetupMySQLDBConnection();
        $sql = "SELECT * FROM " . $cfg['tab']['lay'];
        $this->_oDb->query($sql);
        while ($this->_oDb->next_record()) {
            if ($this->_oDb->f("alias") == "") {
                $sql = "UPDATE " . $cfg['tab']['lay'] . " SET `alias`='" . $this->_oDb->f("name") . "' WHERE `idlay`='" . $this->_oDb->f("idlay") . "';";
                $db2->query($sql);
            }
        }

        $sql = "SELECT * FROM " . $cfg['tab']['mod'];
        $this->_oDb->query($sql);
        while ($this->_oDb->next_record()) {
            if ($this->_oDb->f("alias") == "") {
                $sql = "UPDATE " . $cfg['tab']['mod'] . " SET `alias`='" . $this->_oDb->f("name") . "' WHERE `idmod`='" . $this->_oDb->f("idmod") . "';";
                $db2->query($sql);
            }
        }

        $client = $clientBackup;
        $lang = $langBackup;

    }

}
