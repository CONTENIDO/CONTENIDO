<?php
/**
 * This file contains the upgrade job 15.
 *
 * @package Setup
 * @subpackage UpgradeJob
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 15.
 * Change the upload root folder from "/" to ""
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0015 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.4";

    public function _execute() {
        global $db, $cfg;

        if ($_SESSION['setuptype'] == 'upgrade') {
            $db->query('UPDATE ' . $cfg['tab']['upl'] . ' SET dirname="" WHERE dirname="/"');
        }
    }

}
