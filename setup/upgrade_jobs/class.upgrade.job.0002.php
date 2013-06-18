<?php
/**
 * This file contains the upgrade job 2.
 *
 * @package Setup
 * @subpackage UpgradeJob
 * @version SVN Revision $Rev:$
 *
 * @author Murat Purc <murat@purc>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $cfg;
checkAndInclude($cfg['path']['contenido'] . 'includes/functions.api.string.php');

/**
 * Upgrade job 2.
 * Runs the upgrade job to takeover new module concept.
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0002 extends cUpgradeJobAbstract {

    public $maxVersion = "0";

    /**
     * This method clean the name of moduls table $cfg['tab']['mod'].
     * Clean means all the charecters (�,*+#...) will be replaced.
     */
    private function _changeNameCleanUrl() {
        global $cfg;

        $myDb = clone $this->_oDb;
        $db = clone $this->_oDb;

        // select all modules
        $sql = sprintf('SELECT * FROM %s', $cfg['tab']['mod']);
        $db->query($sql);

        while ($db->nextRecord()) {
            // clear name from not allow charecters
            $newName = cApiStrCleanURLCharacters($db->f('name'));
            if ($newName != $db->f('name')) {
                $mySql = sprintf("UPDATE %s SET name='%s' WHERE idmod=%s", $cfg['tab']['mod'], $newName, $db->f('idmod'));
                $myDb->query($mySql);
            }
        }
    }

    /**
     * This method will transfer the moduls from $cfg['tab']['mod'] to the
     * file system.
     * This Method will be called by setup
     */
    private function _convertModulesToFile() {
        global $cfg;

        $db = getSetupMySQLDBConnection();

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

            while ($db->nextRecord()) {
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

        // update input and output fields
        $sql = sprintf("UPDATE %s SET input = '', output = ''", $cfg['tab']['mod']);
        $db->query($sql);
    }

    public function _execute() {
        global $cfg;
        global $client, $lang, $cfgClient; // is used in cLayoutHandler below!!!

        // Makes the new concept of modules (save the modules to the file) save
                                           // the translation

        // @fixme Get rid of hacks below
                                           // @fixme Logic below works only for
                                           // setup, not for upgrade because of
                                           // different clients and languages

        if ($this->_setupType == 'upgrade') {
            $sql = "SHOW COLUMNS FROM %s LIKE 'frontendpath'";
            $sql = sprintf($sql, $cfg['tab']['clients']);

            $this->_oDb->query($sql);
            if ($this->_oDb->numRows() != 0) {
                $sql = "SELECT * FROM " . $cfg['tab']['clients'];
                $this->_oDb->query($sql);

                while ($this->_oDb->nextRecord()) {
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

        $clientBackup = $client;
        $langBackup = $lang;

        foreach ($cfgClient as $iClient => $aClient) {

            if ((int) $iClient == 0) {
                continue;
            }

            $client = $iClient; // this should work for all clients now

            $db2 = getSetupMySQLDBConnection();
            
            // Update module aliases
            $this->_oDb->query("SELECT * FROM `%s`", $cfg['tab']['mod']);
            while ($this->_oDb->nextRecord()) {
                if ($this->_oDb->f('alias') == '') {
                    $sql = $db2->prepare("UPDATE `%s` SET `alias` = '%s' WHERE `idmod` = %d;", $cfg['tab']['mod'], $this->_oDb->f('name'), $this->_oDb->f('idmod'));
                    $db2->query($sql);
                }
            }
            
            // Save all modules from db-table to the filesystem if exists
            $this->_oDb->query("SHOW COLUMNS FROM `%s` LIKE 'output'", $cfg['tab']['mod']);
            if ($this->_oDb->numRows() > 0) {
                cModuleHandler::setEncoding('ISO-8859-1');
                $this->_convertModulesToFile();
            }
            

            // Update layout aliases
            $this->_oDb->query("SELECT * FROM `%s`", $cfg['tab']['lay']);
            while ($this->_oDb->nextRecord()) {
                if ($this->_oDb->f('alias') == '') {
                    $sql = $db2->prepare("UPDATE `%s` SET `alias` = '%s' WHERE `idlay` = %d;", $cfg['tab']['lay'], $this->_oDb->f('name'), $this->_oDb->f('idlay'));
                    $db2->query($sql);
                }
            }

            // Save all layouts from db-table to the filesystem if exists
            $this->_oDb->query("SHOW COLUMNS FROM `%s` LIKE 'code'", $cfg['tab']['lay']);
            if ($this->_oDb->numRows() > 0) {
                cLayoutHandler::upgrade($this->_oDb, $cfg);
            }
        }

        $client = $clientBackup;
        $lang = $langBackup;
    }

}
