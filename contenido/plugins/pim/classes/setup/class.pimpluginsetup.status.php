<?php

/**
 * This file contains abstract class for change status of installed plugins
 *
 * @package    Plugin
 * @subpackage PluginManager
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class for change active status of installed plugins, extends PimPluginSetup
 *
 * @package    Plugin
 * @subpackage PluginManager
 * @author     frederic.schneider
 */
class PimPluginSetupStatus extends PimPluginSetup {

    /**
     * @var cApiNavSubCollection
     */
    protected $_ApiNavSubCollection;

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
        parent::__construct();

        // cApiClasses
        $this->_setApiNavSubCollection();
    }

    /**
     * Change plugin active status
     *
     * @param int $pluginId
     *
     * @throws cException
     */
    public function changeActiveStatus($pluginId) {
        // Set pluginId
        self::setPluginId($pluginId);

        // Build WHERE-Query for *_plugin table with $pluginId as parameter
        $this->_pimPluginCollection->setWhere('idplugin', cSecurity::toInteger($pluginId));
        $this->_pimPluginCollection->query();
        $plugin = $this->_pimPluginCollection->next();

        // Get name of selected plugin and his active status
        $pluginName = $plugin->get('name');
        $pluginActiveStatus = $plugin->get('active');

        // Get relations
        $this->_pimPluginRelationsCollection->setWhere('idplugin', cSecurity::toInteger($pluginId));
        $this->_pimPluginRelationsCollection->setWhere('type', 'navs');
        $this->_pimPluginRelationsCollection->query();

        if ($pluginActiveStatus == 1) {
            // Plugin is online and now we change status to offline

            // Dependencies check
            $this->_updateCheckDependencies();

            $plugin->set('active', 0);
            $plugin->store();

            // If this plugin has some navSub entries, we must also change menu
            // status to offline
            while (($relation = $this->_pimPluginRelationsCollection->next()) !== false) {
                $idnavs = $relation->get('iditem');
                $this->_changeNavSubStatus($idnavs, 0);
            }

            parent::info(sprintf(i18n('The plugin <strong>%s</strong> has been successfully disabled. To apply the changes please login into backend again.', 'pim'), $pluginName));
        } else {
            // Plugin is offline and now we change status to online

            $plugin->set('active', 1);
            $plugin->store();

            // If this plugin has some navSub entries, we must also change menu
            // status to online
            while (($relation = $this->_pimPluginRelationsCollection->next()) !== false) {
                $idnavs = $relation->get('iditem');
                $this->_changeNavSubStatus($idnavs, 1);
            }

            parent::info(sprintf(i18n('The plugin <strong>%s</strong> has been successfully enabled. To apply the changes please login into backend again.', 'pim'), $pluginName));
        }
    }

    /**
     * Check dependencies to other plugins (dependencies-Tag at plugin.xml)
     *
     * @throws cException
     * @throws cInvalidArgumentException
     */
    private function _updateCheckDependencies() {
        // Call checkDependencies function at PimPlugin class
        // Function returns true or false
        $result = $this->checkDependencies();

        // Show an error message when dependencies could be found
        if ($result === false) {
            parent::error(sprintf(i18n('This plugin is required by the plugin <strong>%s</strong>, so you can not deactivate it.', 'pim'), parent::_getPluginName()));
        }
    }

    /**
     * Change *_nav_sub online status
     *
     * @param int  $idnavs (equivalent to column name)
     * @param bool $online (equivalent to column name)
     *
     * @throws cDbException
     * @throws cException
     */
    private function _changeNavSubStatus($idnavs, $online) {
        $this->_ApiNavSubCollection->setWhere('idnavs', cSecurity::toInteger($idnavs));
        $this->_ApiNavSubCollection->query();

        $navSub = $this->_ApiNavSubCollection->next();
        $navSub->set('online', cSecurity::toInteger($online));
        $navSub->store();
    }

}
