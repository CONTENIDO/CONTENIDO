<?php

/**
 * This file contains the upgrade job 8.
 *
 * @package    Setup
 * @subpackage UpgradeJob
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Upgrade job 8.
 * Update the old system property for insite editing
 *
 * @package    Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0008 extends cUpgradeJobAbstract
{

    public $maxVersion = "4.9.0";

    public function _execute()
    {
        $systemPropTable = cRegistry::getDbTableName('system_prop');

        if ($this->_setupType == 'upgrade') {
            $sql = "UPDATE `" . $systemPropTable . "` SET `name` = 'insite_editing_activated' WHERE `name` = 'insight_editing_activated'";
            $this->_oDb->query($sql);

            $this->_oDb->query("DELETE FROM " . $systemPropTable . " WHERE `name` = 'available' AND `type`='imagemagick'");
        }
    }
}
