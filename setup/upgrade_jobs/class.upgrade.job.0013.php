<?php
/**
 * This file contains the upgrade job 13.
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
 * Upgrade job 13.
 * Remove the imagemagick sysvalue
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0013 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.0";

    public function _execute() {
        global $cfg, $db;

        $db->query("DELETE FROM " . $cfg["tab"]["system_prop"] . " WHERE `name` = 'available' AND `type`='imagemagick'");
    }

}
?>