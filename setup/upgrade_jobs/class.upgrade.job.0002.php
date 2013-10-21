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

    public $maxVersion = "4.9.0-alpha1";

    /**
     * This method will transfer the moduls from $cfg['tab']['mod'] to the
     * file system.
     * This Method will be called by setup
     */
    private function _convertModulesToFile($clientId) {
        global $cfg;

        $db = getSetupMySQLDBConnection();

        $sql = sprintf("SELECT * FROM %s WHERE idclient='%s' ORDER BY idmod", $cfg['tab']['mod'], $clientId);
        $db->query($sql);

        $moduleHandler = new cModuleHandler();

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

        // update input and output fields
        $sql = sprintf("UPDATE %s SET input = '', output = '' WHERE idclient='%s'", $cfg['tab']['mod'], $clientId);
        $db->query($sql);
    }

    public function _execute() {
        global $cfg;
        global $client, $lang, $cfgClient;

        if ($this->_setupType != 'upgrade') {
            return;
        }

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

        $cfgClient = updateClientCache();

        $clientBackup = $client;
        $langBackup = $lang;

        $db2 = getSetupMySQLDBConnection();

        // Update module aliases
        $this->_oDb->query("SELECT * FROM `%s`", $cfg['tab']['mod']);
        while ($this->_oDb->nextRecord()) {
            $newName = strtolower(cModuleHandler::getCleanName($this->_oDb->f('name')));
            $sql = $db2->prepare("UPDATE `%s` SET `alias` = '%s' WHERE `idmod` = %d", $cfg['tab']['mod'], $newName, $this->_oDb->f('idmod'));
            $db2->query($sql);
        }

        // Update layout aliases
        $this->_oDb->query("SELECT * FROM `%s`", $cfg['tab']['lay']);
        while ($this->_oDb->nextRecord()) {
            $newName = cModuleHandler::getCleanName(strtolower($this->_oDb->f('name')));
            $sql = $db2->prepare("UPDATE `%s` SET `alias` = '%s' WHERE `idlay` = %d", $cfg['tab']['lay'], $newName, $this->_oDb->f('idlay'));
            $db2->query($sql);
        }

        // Makes the new concept of modules (save the modules to the file) save the translation
        foreach ($cfgClient as $iClient => $aClient) {
            if ((int) $iClient == 0) {
                continue;
            }

            $client = $iClient; // this should work for all clients now

            // Save all modules from db-table to the filesystem if exists
            $this->_oDb->query("SHOW COLUMNS FROM `%s` LIKE 'output'", $cfg['tab']['mod']);
            if ($this->_oDb->numRows() > 0) {
                $this->_convertModulesToFile($client);
            }

            // Save all layouts from db-table to the filesystem if exists
            $this->_oDb->query("SHOW COLUMNS FROM `%s` LIKE 'code'", $cfg['tab']['lay']);
            if ($this->_oDb->numRows() > 0) {
                cLayoutHandler::upgrade($this->_oDb, $cfg, $client);
            }
        }

        $client = $clientBackup;
        $lang = $langBackup;
    }
}
