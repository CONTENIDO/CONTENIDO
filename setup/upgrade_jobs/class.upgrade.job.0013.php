<?php
/**
 * This file contains the upgrade job 13.
 *
 * @package Setup
 * @subpackage UpgradeJob
 * @version SVN Revision $Rev:$
 *
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 13.
 * Adds the missing login action so that it can be logged again.
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0013 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.0";

    public function _execute() {
        global $db, $cfg;

        $needsUpdate = true;

        $actionColl = new cApiActionCollection();
        $actionArray = $actionColl->getAvailableActions();

        $needsUpdate = !isset($actionArray[330]);

        foreach($actionArray as $action) {
            if($action["name"] == "login") {
                $needsUpdate = false;
            }
        }

        if($needsUpdate) {
            $db->query("INSERT INTO " . $cfg['tab']['actions'] . " VALUES('330', '0', '0', 'login', '', '', '1');");
        }
    }

}
