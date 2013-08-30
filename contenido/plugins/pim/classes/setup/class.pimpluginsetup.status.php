<?php
/**
 * This file contains abstract class for change status of installed plugins
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
class PimPluginSetupStatus extends PimPluginSetup {

    // Classes
    // Class variable for PimPluginCollection
    protected $_PimPluginCollection;

    // Class variable for PimPluginRelationsCollection
    protected $_PimPluginRelationsCollection;

    // Class variable for cApiNavSubCollection
    protected $_ApiNavSubCollection;

    /**
     * Initializing and set variable for PimPluginCollection class
     *
     * @access private
     * @return PimPluginCollection
     */
    private function _setPimPluginCollection() {
        return $this->_PimPluginCollection = new PimPluginCollection();
    }

    /**
     * Initializing and set variable for PimPluginRelationsCollection class
     *
     * @access private
     * @return PimPluginRelationsCollection
     */
    private function _setPimPluginRelationsCollection() {
        return $this->_PimPluginRelationsCollection = new PimPluginRelationsCollection();
    }

    /**
     * Initializing and set variable for cApiNavSubCollection
     *
     * @access private
     * @return cApiNavSubCollection
     */
    private function _setApiNavSubCollection() {
        return $this->_ApiNavSubCollection = new cApiNavSubCollection();
    }

    // Begin of installation routine
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
        $this->_setPimPluginRelationsCollection();

        // cApiClasses
        $this->_setApiNavSubCollection();
    }

    /**
     * Change plugin active status
     *
     * @access public
     * @param integer $pluginId
     * @return void
     */
    public function changeActiveStatus($pluginId) {

        // Build WHERE-Query for *_plugin table with $pluginId as parameter
        $this->_PimPluginCollection->setWhere('idplugin', cSecurity::toInteger($pluginId));
        $this->_PimPluginCollection->query();
        $plugin = $this->_PimPluginCollection->next();

        // Get name of selected plugin and his active status
        $pluginName = $plugin->get('name');
        $pluginActiveStatus = $plugin->get('active');

        // get relations
        $this->_PimPluginRelationsCollection->setWhere('idplugin', cSecurity::toInteger($pluginId));
        $this->_PimPluginRelationsCollection->setWhere('type', 'navs');
        $this->_PimPluginRelationsCollection->query();

        if ($pluginActiveStatus == 1) { // Plugin is online and now we change
                                        // status to offline
            $plugin->set('active', 0);
            $plugin->store();

            // If this plugin has some navSub entries, we must also change menu
            // status to offline
            while (($relation = $this->_PimPluginRelationsCollection->next()) !== false) {
                $idnavs = $relation->get('iditem');
                $this->changeNavSubStatus($idnavs, 0);
            }

            parent::info(i18n('The plugin', 'pim') . ' <strong>' . $pluginName . '</strong> ' . i18n('has been sucessfully disabled. To apply the changes please login into backend again.', 'pim'));
        } else { // Plugin is offline and now we change status to online
            $plugin->set('active', 1);
            $plugin->store();

            // If this plugin has some navSub entries, we must also change menu
            // status to online
            while (($relation = $this->_PimPluginRelationsCollection->next()) !== false) {
                $idnavs = $relation->get('iditem');
                $this->changeNavSubStatus($idnavs, 1);
            }

            parent::info(i18n('The plugin', 'pim') . ' <strong>' . $pluginName . '</strong> ' . i18n('has been sucessfully enabled. To apply the changes please login into backend again.', 'pim'));
        }
    }

    /**
     * Change *_nav_sub online status
     *
     * @access private
     * @param integer $idnavs (equivalent to column name)
     * @param boolean $online (equivalent to column name)
     * @return true
     */
    private function changeNavSubStatus($idnavs, $online) {
        $this->_ApiNavSubCollection->setWhere('idnavs', cSecurity::toInteger($idnavs));
        $this->_ApiNavSubCollection->query();

        $navSub = $this->_ApiNavSubCollection->next();
        $navSub->set('online', cSecurity::toInteger($online));
        $navSub->store();

        return true;
    }

}
?>