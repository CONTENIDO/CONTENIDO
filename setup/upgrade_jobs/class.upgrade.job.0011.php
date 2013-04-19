<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Update the old system property for insite editing
 *
 * @package CONTENIDO Setup upgrade
 * @version 1.0
 * @author Frederic Schneider <frederic.schneider@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release 4.9
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}
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
