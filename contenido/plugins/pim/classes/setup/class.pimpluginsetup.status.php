<?php
/**
 * This file contains abstract class for change status of installed plugins
 *
 * @package Plugin
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

/**
 * Class for change active status of installed plugins, extends PimPluginSetup
 *
 * @package Plugin
 * @subpackage PluginManager
 * @author frederic.schneider
 */
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
     * @return PimPluginCollection
     */
    private function _setPimPluginCollection() {
        return $this->_PimPluginCollection = new PimPluginCollection();
    }

    /**
     * Initializing and set variable for PimPluginRelationsCollection class
     *
     * @return PimPluginRelationsCollection
     */
    private function _setPimPluginRelationsCollection() {
        return $this->_PimPluginRelationsCollection = new PimPluginRelationsCollection();
    }

    /**
     * Initializing and set variable for cApiNavSubCollection
     *
     * @return cApiNavSubCollection
     */
    private function _setApiNavSubCollection() {
        return $this->_ApiNavSubCollection = new cApiNavSubCollection();
    }

    // Begin of installation routine
    /**
     * Construct function
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
     * @param int $pluginId
     */
    public function changeActiveStatus($pluginId) {

    	// Set pluginId
    	self::setPluginId($pluginId);

        // Build WHERE-Query for *_plugin table with $pluginId as parameter
        $this->_PimPluginCollection->setWhere('idplugin', cSecurity::toInteger($pluginId));
        $this->_PimPluginCollection->query();
        $plugin = $this->_PimPluginCollection->next();

        // Get name of selected plugin and his active status
        $pluginName = $plugin->get('name');
        $pluginActiveStatus = $plugin->get('active');

        // Get relations
        $this->_PimPluginRelationsCollection->setWhere('idplugin', cSecurity::toInteger($pluginId));
        $this->_PimPluginRelationsCollection->setWhere('type', 'navs');
        $this->_PimPluginRelationsCollection->query();

        if ($pluginActiveStatus == 1) { // Plugin is online and now we change
                                        // status to offline

        	// Dependencies check
        	$this->_updateCheckDependencies();

        	$plugin->set('active', 0);
            $plugin->store();

            // If this plugin has some navSub entries, we must also change menu
            // status to offline
            while (($relation = $this->_PimPluginRelationsCollection->next()) !== false) {
                $idnavs = $relation->get('iditem');
                $this->_changeNavSubStatus($idnavs, 0);
            }

            parent::info(i18n('The plugin', 'pim') . ' <strong>' . $pluginName . '</strong> ' . i18n('has been sucessfully disabled. To apply the changes please login into backend again.', 'pim'));
        } else { // Plugin is offline and now we change status to online
            $plugin->set('active', 1);
            $plugin->store();

            // If this plugin has some navSub entries, we must also change menu
            // status to online
            while (($relation = $this->_PimPluginRelationsCollection->next()) !== false) {
                $idnavs = $relation->get('iditem');
                $this->_changeNavSubStatus($idnavs, 1);
            }

            parent::info(i18n('The plugin', 'pim') . ' <strong>' . $pluginName . '</strong> ' . i18n('has been sucessfully enabled. To apply the changes please login into backend again.', 'pim'));
        }
    }

    /**
     * Check dependencies to other plugins (dependencies-Tag at plugin.xml)
     */
    private function _updateCheckDependencies() {

    	// Call checkDepenendencies function at PimPlugin class
    	// Function returns true or false
    	$result = $this->checkDependencies();

    	// Show an error message when dependencies could be found
    	if ($result === false) {
    		parent::error(sprintf(i18n('This plugin are required by the plugin <strong>%s</strong>, so you can not deactivate it.', 'pim'), parent::_getPluginName()));
    	}
    }

    /**
     * Change *_nav_sub online status
     *
     * @param int $idnavs (equivalent to column name)
     * @param bool $online (equivalent to column name)
     * @return  bool true
     */
    private function _changeNavSubStatus($idnavs, $online) {
        $this->_ApiNavSubCollection->setWhere('idnavs', cSecurity::toInteger($idnavs));
        $this->_ApiNavSubCollection->query();

        $navSub = $this->_ApiNavSubCollection->next();
        $navSub->set('online', cSecurity::toInteger($online));
        $navSub->store();

        return true;
    }

}
?>