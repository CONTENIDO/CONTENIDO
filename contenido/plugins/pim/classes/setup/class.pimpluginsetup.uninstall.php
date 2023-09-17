<?php

/**
 * This file contains abstract class for installation new plugins
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
 * Uninstall class for existing plugins, extends PimPluginSetup
 *
 * @package    Plugin
 * @subpackage PluginManager
 * @author     frederic.schneider
 */
class PimPluginSetupUninstall extends PimPluginSetup {
    /**
     * Foldername of installed plugin
     *
     * @var string
     */
    private $_PluginFoldername;

    /**
     * @var cApiAreaCollection
     */
    protected $_ApiAreaCollection;

    /**
     * @var cApiActionCollection
     */
    protected $_ApiActionCollection;

    /**
     * @var cApiFileCollection
     */
    protected $_ApiFileCollection;

    /**
     * @var cApiFrameFileCollection
     */
    protected $_ApiFrameFileCollection;

    /**
     * @var cApiNavMainCollection
     */
    protected $_ApiNavMainCollection;

    /**
     * @var cApiNavSubCollection
     */
    protected $_ApiNavSubCollection;

    /**
     * @var cApiTypeCollection
     */
    protected $_ApiTypeCollection;

    // GET and SET methods for installation routine

    /**
     * Set variable for plugin foldername
     *
     * @param string $foldername
     * @return string
     */
    public function setPluginFoldername($foldername) {
        return $this->_PluginFoldername = cSecurity::escapeString($foldername);
    }

    /**
     * Initializing and set variable for cApiAreaCollection
     *
     * @return cApiAreaCollection
     */
    private function _setApiAreaCollection() {
        return $this->_ApiAreaCollection = new cApiAreaCollection();
    }

    /**
     * Initializing and set variable for cApiActionCollection
     *
     * @return cApiActionCollection
     */
    private function _setApiActionCollection() {
        return $this->_ApiActionCollection = new cApiActionCollection();
    }

    /**
     * Initializing and set variable for cApiAFileCollection
     *
     * @return cApiFileCollection
     */
    private function _setApiFileCollection() {
        return $this->_ApiFileCollection = new cApiFileCollection();
    }

    /**
     * Initializing and set variable for cApiFrameFileCollection
     *
     * @return cApiFrameFileCollection
     */
    private function _setApiFrameFileCollection() {
        return $this->_ApiFrameFileCollection = new cApiFrameFileCollection();
    }

    /**
     * Initializing and set variable for cApiNavMainFileCollection
     *
     * @return cApiNavMainCollection
     */
    private function _setApiNavMainCollection() {
        return $this->_ApiNavMainCollection = new cApiNavMainCollection();
    }

    /**
     * Initializing and set variable for cApiNavSubCollection
     *
     * @return cApiNavSubCollection
     */
    private function _setApiNavSubCollection() {
        return $this->_ApiNavSubCollection = new cApiNavSubCollection();
    }

    /**
     * Initializing and set variable for cApiTypeCollection
     *
     * @return cApiTypeCollection
     */
    private function _setApiTypeCollection() {
        return $this->_ApiTypeCollection = new cApiTypeCollection();
    }

    /**
     * Get method for foldername of installed plugin
     *
     * @return string
     */
    protected function _getPluginFoldername() {
        return $this->_PluginFoldername;
    }

    // Begin of uninstallation routine
    /**
     * Construct function
     */
    public function __construct() {
        parent::__construct();

        // cApiClasses
        $this->_setApiAreaCollection();
        $this->_setApiActionCollection();
        $this->_setApiFileCollection();
        $this->_setApiFrameFileCollection();
        $this->_setApiNavMainCollection();
        $this->_setApiNavSubCollection();
        $this->_setApiTypeCollection();
    }

    /**
     * Uninstall function
     *
     * @param bool $sql Optional parameter to set sql true (standard) or false
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function uninstall($sql = true) {
        // Dependencies checks
        $this->_uninstallCheckDependencies();

        // get relations
        $this->_pimPluginRelationsCollection->setWhere('idplugin', parent::_getPluginId());
        $this->_pimPluginRelationsCollection->query();

        // Initializing relations array
        $relations = [];

        while (($relation = $this->_pimPluginRelationsCollection->next()) !== false) {
            // Relation to tables *_action_, *_area, *_nav_main, *_nav_sub and
            // *_type
            $index = $relation->get('type');

            // Is equivalent to idaction, idarea, idnavm, idnavs or idtype
            // column
            $value = $relation->get('iditem');
            $relations[$index][] = $value;
        }

        // Delete entries with relations to *_actions
        if (!empty($relations['action'])) {
            $this->_ApiActionCollection->deleteByWhereClause("idaction IN('" . join("', '", $relations['action']) . "')");
        }

        // Delete entries with relations to *_frame_files
        if (!empty($relations['framefl'])) {
        	$this->_ApiFrameFileCollection->deleteByWhereClause("idframefile IN('" . join("', '", $relations['framefl']) . "')");
        }

        // Delete entries with relations to *_area
        if (!empty($relations['area'])) {
            $this->_ApiFileCollection->deleteByWhereClause("idarea IN('" . join("', '", $relations['area']) . "')");
            $this->_ApiFrameFileCollection->deleteByWhereClause("idarea IN('" . join("', '", $relations['area']) . "')");
            $this->_ApiAreaCollection->deleteByWhereClause("idarea IN('" . join("', '", $relations['area']) . "')");
        }

        // Delete entries with relations to *_nav_main
        if (!empty($relations['navm'])) {
            $this->_ApiNavMainCollection->deleteByWhereClause("idnavm IN('" . join("', '", $relations['navm']) . "')");
        }

        // Delete entries with relations to *_nav_sub
        if (!empty($relations['navs'])) {
            $this->_ApiNavSubCollection->deleteByWhereClause("idnavs IN('" . join("', '", $relations['navs']) . "')");
        }

        // Delete content types
        if (!empty($relations['ctype'])) {
            $this->_ApiTypeCollection->deleteByWhereClause("idtype IN('" . join("', '", $relations['ctype']) . "')");
        }

        // Get plugin-information
        $this->_pimPluginCollection->resetQuery();
        $this->_pimPluginCollection->setWhere('idplugin', parent::_getPluginId());
        $this->_pimPluginCollection->query();
        $pimPluginSql = $this->_pimPluginCollection->next();

        // Set foldername
        $this->setPluginFoldername($pimPluginSql->get('folder'));

        // Delete specific sql entries or tables, run only if we have no update
        // sql file
        if ($sql && !parent::_getUpdateSqlFileExist()) {
            $this->_uninstallDeleteSpecificSql();
        }

        // Plugin name
        $pluginName = $pimPluginSql->get('name');

        // Delete entries at *_plugins_rel and *_plugins
        $this->_pimPluginRelationsCollection->deleteByWhereClause('idplugin = ' . parent::_getPluginId());
        $this->_pimPluginCollection->deleteByWhereClause('idplugin = ' . parent::_getPluginId());

        // Write new execution order
        $this->_writeNewExecutionOrder();

        // Success message for uninstall mode
        if (parent::$_GuiPage instanceof cGuiPage && parent::getMode() == 3) {
            parent::info(sprintf(i18n('The plugin <strong>%s</strong> has been successfully removed. To apply the changes please login into backend again.', 'pim'), $pluginName));
        }
    }

    /**
     * Check dependencies to other plugins (dependencies-Tag at plugin.xml)
     *
     * @throws cException
     * @throws cInvalidArgumentException
     */
    private function _uninstallCheckDependencies() {
        // Call checkDependencies function at PimPlugin class
        // Function returns true or false
        $result = $this->checkDependencies();

        // Show an error message when dependencies could be found
        if ($result === false) {
            parent::error(sprintf(i18n('This plugin is required by the plugin <strong>%s</strong>, so you can not remove it.', 'pim'), parent::_getPluginName()));
        }
    }

    /**
     * Delete specific sql entries or tables, full uninstall mode
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    protected function _uninstallDeleteSpecificSql() {
        $cfg = cRegistry::getConfig();

        $tempSqlFilename = cRegistry::getBackendPath() . $cfg['path']['plugins'] . $this->_getPluginFoldername() . DIRECTORY_SEPARATOR . 'plugin_uninstall.sql';

        $pattern = '/^(DELETE FROM|DROP TABLE) `?' . parent::PLUGIN_SQL_PREFIX . '([a-zA-Z0-9\-_]+)`?\b/';
        return $this->_processSetupSql($tempSqlFilename, $pattern);
    }

    /**
     * Delete a installed plugin directory
     *
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function uninstallDir() {
        $cfg = cRegistry::getConfig();

        // delete folders
        $folderPath = cRegistry::getBackendPath() . $cfg['path']['plugins'] . $this->_getPluginFoldername();
        cDirHandler::recursiveRmdir($folderPath);

        if (parent::$_GuiPage instanceof cGuiPage) {
            // success message
            if (!cFileHandler::exists($folderPath)) {
                parent::info(sprintf(i18n('The plugin folder <strong>%s</strong> has been successfully uninstalled.', 'pim'), $this->_getPluginFoldername()));
            } elseif (cFileHandler::exists($folderPath)) {
                parent::error(sprintf(i18n('The plugin folder <strong>%s</strong> could not be uninstalled.', 'pim'), $this->_getPluginFoldername()));
            }
        }
    }

    /**
     * Generate (write) new execution order
     *
     * @return bool
     *
     * @throws cDbException
     * @throws cException
     */
    protected function _writeNewExecutionOrder() {
        // Lowest executionorder is one
        $i = 1;

        $pimPluginColl = new PimPluginCollection();
        $pimPluginColl->setOrder('executionorder ASC');
        $pimPluginColl->query();
        while ($pimPluginSql = $pimPluginColl->next()) {
            $pimPluginSql->set('executionorder', $i);
            $pimPluginSql->store();

            $i++;
        }

        return true;
    }

}
