<?php
/**
 * This file contains the upgrade job 8.
 *
 * @package    Setup
 * @subpackage UpgradeJob
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 8.
 * Update the old system property for insite editing
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0008 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.0";

    public function _execute() {
        global $cfg, $db;

        if ($this->_setupType == 'upgrade') {
            $sql = "UPDATE `" . $cfg['tab']['system_prop'] . "` SET `name` = 'insite_editing_activated' WHERE `name` = 'insight_editing_activated'";
            $db->query($sql);

            $db->query("DELETE FROM " . $cfg["tab"]["system_prop"] . " WHERE `name` = 'available' AND `type`='imagemagick'");
        }
    }
}
