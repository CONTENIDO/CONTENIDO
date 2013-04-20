<?php
/**
 * This file contains the upgrade job 11.
 *
 * @package    Setup
 * @subpackage UpgradeJob
 * @version    SVN Revision $Rev:$
 *
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 11.
 * Update the old system property for insite editing
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0011 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.0";

    public function _execute() {
        global $cfg, $db;

        if ($this->_setupType == 'upgrade') {
            $sql = "UPDATE `" . $cfg['tab']['system_prop'] . "` SET `name` = 'insite_editing_activated' WHERE `name` = 'insight_editing_activated'";
			$db->query($sql);
        }
    }

}
