<?php
/**
 * This file contains abstract class for installation new plugins
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
class PimPluginSetupInstall extends PimPluginSetup {

    // Initializing variables
    // All area entries from database in an array
    protected $installAreas = array();

    // Class variable for PimPluginCollection
    protected $_PimPluginCollection;

    // Class variable for PimPluginRelationsCollection
    protected $_PimPluginRelationsCollection;

    // Class variable for cApiAreaCollection;
    protected $_ApiAreaCollection;

    // GET and SET methods for installation routine
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
     * Initializing and set variable for cApiAreaCollection
     *
     * @access private
     * @return cApiAreaCollection
     */
    private function _setApiAreaCollection() {
        return $this->_ApiAreaCollection = new cApiAreaCollection();
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
        $this->_setApiAreaCollection();

        // Start with new database entries
        // Add new plugin: *_plugins
        $this->installAddPlugin();

        // Get all area names from database
        $this->installFillAreas();

        // Add new CONTENIDO areas: *_area
        $this->installAddAreas();
    }

    private function installAddPlugin() {
        // Add entry at *_plugins
        $pimPlugin = $this->_PimPluginCollection->create(parent::$_XmlGeneral->plugin_name, parent::$_XmlGeneral->description, parent::$_XmlGeneral->author, parent::$_XmlGeneral->copyright, parent::$_XmlGeneral->mail, parent::$_XmlGeneral->website, parent::$_XmlGeneral->version, parent::$_XmlGeneral->plugin_foldername, parent::$_XmlGeneral->uuid, parent::$_XmlGeneral->attributes()->active);

        // Get id of new plugin
        $pluginId = $pimPlugin->get('idplugin');

        // Set pluginId
        parent::_setPluginId($pluginId);
    }

    /**
     * Get all area names from database
     *
     * @access protected
     * @return void
     */
    protected function installFillAreas() {
        $oItem = $this->_ApiAreaCollection;
        $this->_ApiAreaCollection->select(null, null, 'name');
        while (($areas = $this->_ApiAreaCollection->next()) !== false) {
            $this->installAreas[] = $areas->get('name');
        }
    }

    /**
     * Add entries at *_area
     *
     * @access protected
     * @return void
     */
    protected function installAddAreas() {

        // Initializing attribute array
        $attributes = array();

        // Get id of plugin
        $pluginId = parent::_getPluginId();

        $areaCount = count(parent::$_XmlArea->area);
        for ($i = 0; $i < $areaCount; $i++) {

            // Build attributes
            foreach (parent::$_XmlArea->area[$i]->attributes() as $key => $value) {
                $attributes[$key] = $value;
            }

            // Security check
            $area = cSecurity::escapeString(parent::$_XmlArea->area[$i]);

            // Add attributes "parent" and "menuless" to an array
            $attributes = array(
                'parent' => cSecurity::escapeString($attributes['parent']),
                'menuless' => cSecurity::toInteger($attributes['menuless'])
            );

            // Fix for parent attribute
            if (empty($attributes['parent'])) {
                $attributes['parent'] = 0;
            }

            // Create a new entry
            $item = $this->_ApiAreaCollection->create($area, $attributes['parent'], 1, 1, $attributes['menuless']);

            // Set a relation
            $this->_PimPluginRelationsCollection->create($item->get('idarea'), $pluginId, 'area');

            // Add new area to all area array
            $this->installAreas[] = $area;
        }
    }

}
?>