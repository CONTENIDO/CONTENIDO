<?php
/**
 * This file contains the upgrade job 6.
 *
 * @package    Setup
 * @subpackage UpgradeJob
 * @version    SVN Revision $Rev:$
 *
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

    public function _execute() {

        global $cfg, $cfgClient;

        include_once($cfg['path']['contenido'] . 'includes/functions.file.php');

        if ($this->_setupType == 'upgrade') {

            // Load client configuration
            setupInitializeCfgClient();

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
