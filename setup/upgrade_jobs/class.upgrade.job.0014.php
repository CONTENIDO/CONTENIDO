<?php
/**
 * This file contains the upgrade job 14.
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
 * Upgrade job 14.
 * Add the execution order column to the plugin table
 *
 * @package Setup
 * @subpackage UpgradeJob
 */
class cUpgradeJob_0014 extends cUpgradeJobAbstract {

    public $maxVersion = "4.9.1";

    public function _execute() {
        global $db, $cfg;

        plugin_include('pim', 'classes/class.pim.plugin.collection.php');

        $pluginColl = new PimPluginCollection();
        $pluginColl->select();
        $i = 1;
        while ($plugin = $pluginColl->next()) {
            $plugin->set('executionorder', $i);
            $plugin->store();

            $i++;
        }
    }

}
