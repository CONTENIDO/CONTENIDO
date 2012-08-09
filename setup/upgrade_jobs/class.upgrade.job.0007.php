<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Runs the upgrade job to move files/from old client folders to new client's data folders
 *
 * Requirements:
 * @con_php_req 5.0
 *
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


class cUpgradeJob_0007 extends cUpgradeJobAbstract {

    const MODE = 0777;

    /**
     * List of source and destination folder
     * @var array
     */
    protected $_folderToCopy = array(
        'layouts/' => 'data/layouts/',
        'logs/' => 'data/logs/',
        'version/' => 'data/version/',
    );

    public function execute() {

        global $cfg, $cfgClient;

        include_once($cfg['path']['contenido'] . 'includes/functions.file.php');

        if ($this->_setupType == 'upgrade') {

            // Load client configuration
            if (!isset($cfgClient) || !isset($cfgClient['set'])) {
                if (cFileHandler::exists($cfg['path']['contenido_config'] . 'config.clients.php')) {
                    require_once($cfg['path']['contenido_config'] . 'config.clients.php');
                }
                rereadClients();
            }

            $allClients = $this->_getAllClients();

            // Loop thru al clients and copy old data folder to new data folder
            foreach ($allClients as $idclient => $oClient) {
                foreach ($this->_folderToCopy as $src => $dst) {
                    $sourceDir = $cfgClient[$idclient]['path']['frontend'] . $src;
                    $destDir = $cfgClient[$idclient]['path']['frontend'] . $dst;
                    if (!is_dir($sourceDir)) {
                        continue;
                    }
                    if (!is_dir($destDir)) {
                        @mkdir($destDir, self::MODE, true);
                    }
                    if (!is_dir($destDir)) {
                        logSetupFailure("Couldn't create client data directory $destDir");
                        continue;
                    }
                    recursiveCopy($sourceDir, $destDir, 0777);
                }
            }
        }
    }

}