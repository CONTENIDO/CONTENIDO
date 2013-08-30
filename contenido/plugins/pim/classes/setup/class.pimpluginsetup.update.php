<?php
/**
 * This file contains abstract class for update plugins
 *
 * @package CONTENIDO Plugins
 * @subpackage PluginManager
 * @version SVN Revision $Rev:$
 *
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');
class PimPluginSetupUpdate extends PimPluginSetup {

    // Classes
    // Class variable for PimPluginCollection
    protected $_PimPluginCollection;

    /**
     * Initializing and set variable for PimPluginCollection class
     *
     * @access private
     * @return PimPluginCollection
     */
    private function _setPimPluginCollection() {
        return $this->_PimPluginCollection = new PimPluginCollection();
    }

    // Begin of update routine
    /**
     * Construct function
     *
     * @access public
     * @return void
     */
    public function __construct() {

        // Initializing and set classes
        // PluginManager classes
        $this->_setPimPluginCollection();

        // Check same plugin (uuid)
        $this->checkSamePlugin();

        // Delete "old" plugin
        $delete = new PimPluginSetupUninstall();
        $delete->uninstall();

        // Install new plugin
        $new = new PimPluginSetupInstall();
        $new->install();

        // Success message
        parent::info(i18n('The plugin has been successfully updated. To apply the changes please login into backend again.', 'pim'));
    }

    /**
     * Check uuId: You can update only the same plugin
     *
     * @access private
     * @return void
     */
    private function checkSamePlugin() {
        $this->_PimPluginCollection->setWhere('idplugin', parent::_getPluginId());
        $this->_PimPluginCollection->query();
        while ($result = $this->_PimPluginCollection->next()) {

            if (parent::$_XmlGeneral->uuid != $result->get('uuid')) {
                parent::error(i18n('You have to update the same plugin', 'pim'));
            }
        }
    }

}