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


class cUpgradeJob_0002 extends cUpgradeJobAbstract {

    public function execute() {
        global $cfg;
        global $client, $lang, $cfgClient;  // is used in LayoutInFile below!!!

        // Makes the new concept of modules (save the modules to the file) save the translation
        if ($this->_setupType == 'upgrade' || $this->_setupType == 'setup') {

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

            rereadClients();

            cModuleHandler::setEncoding('ISO-8859-1');

            // Save all modules from db-table to the filesystem
            $contenidoUpgradeJob = new Contenido_UpgradeJob($this->_oDb);
            $contenidoUpgradeJob->convertModulesToFile($this->_setupType);

            // Save layout from db-table to the file system
            // @fixme  LayoutInFile uses global $client, we can't do this for all clients...
            $layoutInFile = new LayoutInFile(1, '', $cfg, 1, $this->_oDb);
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

}
