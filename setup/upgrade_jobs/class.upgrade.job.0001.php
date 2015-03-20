<?php
/**
 * This file contains the upgrade job 1.
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
 * Upgrade job 1.
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0001 extends cUpgradeJobAbstract {

    public $maxVersion = "0"; //this will be executed every time

    public function _execute() {
        global $cfg, $cfgClient;
        if ($this->_setupType == 'setup') {
            switch ($_SESSION['clientmode']) {
                case 'CLIENTMODULES':
                case 'CLIENTEXAMPLES':
                    updateClientPath($this->_oDb, $cfg['tab']['clients'], 1, self::$_rootPath . '/cms/', self::$_rootHttpPath . '/cms/');
                    break;
                default:
                    break;
            }
        }
        if (false === cFileHandler::exists($cfg['path']['contenido_config'] . 'config.clients.php')) {
            updateClientPath($this->_oDb, $cfg['tab']['clients'], 0, self::$_rootPath . '/cms/', self::$_rootHttpPath . '/cms/');
        }
    }

}
