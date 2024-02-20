<?php

/**
 * This file contains the upgrade job 2.
 *
 * @package    Setup
 * @subpackage UpgradeJob
 * @author     Murat Purc <murat@purc>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$cfg = cRegistry::getConfig();
checkAndInclude($cfg['path']['contenido'] . 'includes/functions.api.string.php');

/**
 * Upgrade job 2.
 * Runs the upgrade job to takeover new module concept.
 *
 * @package    Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0002 extends cUpgradeJobAbstract
{

    public $maxVersion = "4.9.0-alpha1";

    /**
     * This method will transfer the moduls from $cfg['tab']['mod'] to the
     * file system.
     * This Method will be called by setup
     */
    private function _convertModulesToFile($clientId)
    {
        $db = getSetupMySQLDBConnection();
        $modulesTable = cRegistry::getDbTableName('mod');
        $db->query("SELECT * FROM `%s` WHERE `idclient` = %d ORDER BY `idmod`", $modulesTable, $clientId);

        $moduleHandler = new cModuleHandler();

        while ($db->nextRecord()) {
            // init the ModulHandler with all data of the modul
            // inclusive client
            $moduleHandler->initWithDatabaseRow($db);

            // make new module only if modul not exist in directory
            if (!$moduleHandler->modulePathExists()) {
                // we need no error handling here because module could still
                // exist from previous version
                if ($moduleHandler->createModule($db->f('input'), $db->f('output'))) {
                    // save module translation
                    $translations = new cModuleFileTranslation($db->f('idmod'));
                    $translations->saveTranslations();
                }
            }
        }

        // update input and output fields
        $sql = sprintf("UPDATE %s SET input = '', output = '' WHERE idclient='%s'", $modulesTable, $clientId);
        $db->query($sql);
    }

    public function _execute()
    {
        // NOTE: Use globals here!
        global $cfg, $client, $lang, $cfgClient;

        if ($this->_setupType != 'upgrade') {
            return;
        }

        $clientsTable = cRegistry::getDbTableName('clients');

        $this->_oDb->query("SHOW COLUMNS FROM `%s` LIKE 'frontendpath'", $clientsTable);
        if ($this->_oDb->numRows() != 0) {
            $this->_oDb->query("SELECT * FROM `%s`", $clientsTable);

            while ($this->_oDb->nextRecord()) {
                updateClientCache($this->_oDb->f("idclient"), $this->_oDb->f("htmlpath"), $this->_oDb->f("frontendpath"));
            }

            $this->_oDb->query(
                "ALTER TABLE `%s` DROP COLUMN `htmlpath`, DROP COLUMN `frontendpath`", $clientsTable
            );
        }

        $cfgClient = updateClientCache();

        $clientBackup = $client;
        $langBackup = $lang;

        $db2 = getSetupMySQLDBConnection();

        // Update module aliases
        $modulesTable = cRegistry::getDbTableName('mod');
        $this->_oDb->query("SELECT * FROM `%s`", $modulesTable);
        while ($this->_oDb->nextRecord()) {
            $newName = cString::toLowerCase(cModuleHandler::getCleanName($this->_oDb->f('name')));
            $sql = $db2->prepare("UPDATE `%s` SET `alias` = '%s' WHERE `idmod` = %d", $modulesTable, $newName, $this->_oDb->f('idmod'));
            $db2->query($sql);
        }

        // Update layout aliases
        $layoutsTable = cRegistry::getDbTableName('lay');
        $this->_oDb->query("SELECT * FROM `%s`", $layoutsTable);
        while ($this->_oDb->nextRecord()) {
            $newName = cModuleHandler::getCleanName(cString::toLowerCase($this->_oDb->f('name')));
            $sql = $db2->prepare("UPDATE `%s` SET `alias` = '%s' WHERE `idlay` = %d", $layoutsTable, $newName, $this->_oDb->f('idlay'));
            $db2->query($sql);
        }

        // Makes the new concept of modules (save the modules to the file) save the translation
        foreach ($cfgClient as $iClient => $aClient) {
            if ((int)$iClient == 0) {
                continue;
            }

            $client = $iClient; // this should work for all clients now

            // Save all modules from db-table to the filesystem if exists
            $this->_oDb->query("SHOW COLUMNS FROM `%s` LIKE 'output'", $modulesTable);
            if ($this->_oDb->numRows() > 0) {
                $this->_convertModulesToFile($client);
            }

            // Save all layouts from db-table to the filesystem if exists
            $this->_oDb->query("SHOW COLUMNS FROM `%s` LIKE 'code'", $layoutsTable);
            if ($this->_oDb->numRows() > 0) {
                cLayoutHandler::upgrade($this->_oDb, $cfg, $client);
            }
        }

        $client = $clientBackup;
        $lang = $langBackup;
    }
}
