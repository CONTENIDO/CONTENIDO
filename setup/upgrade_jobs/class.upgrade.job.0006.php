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
     * List of source and destination folder or files based on clients frontend dir.
     * @var array
     */
    protected $_clientCopyList = array(
        'layouts/' => 'data/layouts/',
        'logs/' => 'data/logs/',
        'version/' => 'data/version/',
        'config.php' => 'data/config/config.php',
        'config.local.php' => 'data/config/config.local.php',
        'config.after.php' => 'data/config/config.after.php'
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
                foreach ($this->_clientCopyList as $src => $dst) {
                    $source = $cfgClient[$idclient]['path']['frontend'] . $src;
                    $destination = $cfgClient[$idclient]['path']['frontend'] . $dst;
                    if (is_dir($source)) {
                        if (!is_dir($destination)) {
                            @mkdir($destination, self::MODE, true);
                        }
                        if (!is_dir($destination)) {
                            logSetupFailure("Couldn't create client data directory $destination");
                            continue;
                        }
                        recursiveCopy($source, $destination, self::MODE);
                    } elseif (cFileHandler::exists($source)) {
                        if (cFileHandler::copy($source, $destination)) {
                            cFileHandler::chmod($destination, self::MODE);
                        } else {
                            logSetupFailure("Couldn't copy client file $source");
                        }
                    }
                }
            }
        }
    }

}