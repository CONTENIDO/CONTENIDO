<?php
/**
 * This file contains the upgrade job 6.
 *
 * @package    Setup
 * @subpackage UpgradeJob
 * @author     Murat Purc <murat@purc>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 6.
 * Runs the upgrade job to move files/from old client folders to new client's data folders
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0006 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.0-beta1";

    /**
     * @throws cInvalidArgumentException
     */
    public function _execute() {

        global $cfg, $cfgClient;

        include_once($cfg['path']['contenido'] . 'includes/functions.file.php');

        if ($this->_setupType == 'upgrade') {

            // List of source and destination folder or files based on clients frontend dir.
            $clientCopyList = array(
                'layouts/' => 'data/layouts/',
                'logs/' => 'data/logs/',
                'version/' => 'data/version/',
                'data/config/config.php' => 'data/config/' . CON_ENVIRONMENT . '/config.php',
                'data/config/config.local.php' => 'data/config/' . CON_ENVIRONMENT . '/config.local.php',
                'data/config/config.after.php' => 'data/config/' . CON_ENVIRONMENT . '/config.after.php',
                'config.php' => 'data/config/' . CON_ENVIRONMENT . '/config.php',
                'config.local.php' => 'data/config/' . CON_ENVIRONMENT . '/config.local.php',
                'config.after.php' => 'data/config/' . CON_ENVIRONMENT . '/config.after.php'
            );

            // Load client configuration
            setupInitializeCfgClient();

            $allClients = $this->_getAllClients();

            // Loop thru al clients and copy old data folder to new data folder
            foreach ($allClients as $idclient => $oClient) {
                // check if config directory exists
                $configDir = $cfgClient[$idclient]['path']['frontend'] . 'data/config/' . CON_ENVIRONMENT;
                if (!is_dir($configDir)) {
                    @mkdir($configDir, cDirHandler::getDefaultPermissions(), true);
                }

                foreach ($clientCopyList as $src => $dst) {
                    $source = $cfgClient[$idclient]['path']['frontend'] . $src;
                    $destination = $cfgClient[$idclient]['path']['frontend'] . $dst;
                    if (is_dir($source)) {
                        if (!is_dir($destination)) {
                            @mkdir($destination, cDirHandler::getDefaultPermissions(), true);
                        }
                        if (!is_dir($destination)) {
                            logSetupFailure("Couldn't create client data directory $destination");
                            continue;
                        }

                        cDirHandler::recursiveCopy($source, $destination, cDirHandler::getDefaultPermissions());
                    } elseif (cFileHandler::exists($source) && cFileHandler::exists($destination)) {
                        if (cFileHandler::move($source, $destination)) {
                            cFileHandler::chmod($destination, cDirHandler::getDefaultPermissions());
                        } else {
                            logSetupFailure("Couldn't copy client file $source");
                        }
                    }
                }
            }
        }
    }

}
