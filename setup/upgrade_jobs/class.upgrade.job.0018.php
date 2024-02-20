<?php

/**
 * This file contains the upgrade job 18.
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
 * Upgrade job 18
 * Add system settings for stats_tracking
 * CON-2718
 *
 * @package    Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0018 extends cUpgradeJobAbstract
{

    public $maxVersion = "4.9.12";

    public function _execute()
    {
        if ($_SESSION['setuptype'] == 'upgrade') {

            // Delete old statistic client configurations
            $sql = "DELETE FROM " . cRegistry::getDbTableName('properties') . " WHERE type = 'stats' AND name = 'tracking'";
            $this->_oDb->query($sql);
        }

        // Create a system configuration and turn the statistic off (default)
        $systemProp = new cApiSystemPropertyCollection();
        $systemProp->create('stats', 'tracking', 'disabled');
    }

}
