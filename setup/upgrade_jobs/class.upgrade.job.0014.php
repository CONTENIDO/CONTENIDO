<?php
/**
 * This file contains the upgrade job 14.
 *
 * @package    Setup
 * @subpackage UpgradeJob
 * @version    SVN Revision $Rev:$
 *
 * @author     Mischa Holz
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 14.
 * Move the config files for the clients to the new {environment} folder
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0014 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.0";

    public function _execute() {
        global $cfg, $db, $cfgClient;

        foreach ($cfgClient as $aClient) {
            if (cFileHandler::exists($aClient["path"]["frontend"] . "/data/config/config.php")) {
                cFileHandler::move($aClient["path"]["frontend"] . "/data/config/config.php", $aClient["path"]["frontend"] . "/data/config/" . CON_ENVIRONMENT . "config.php");
            }
        }
    }

}

?>