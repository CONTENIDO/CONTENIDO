<?php
/**
 * This file contains abstract class for installation new plugins
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
 * Uninstall class for existing plugins, extends PimPluginSetup
 *
 * @package Plugin
 * @subpackage PluginManager
 * @author frederic.schneider
 */
class PimPluginSetupUninstall extends PimPluginSetup {

    // Initializing variables
    // Plugin specific data
    // Foldername of installed plugin
    private $_PluginFoldername;

    // Classes
    // Class variable for PimPluginCollection
    protected $_PimPluginCollection;

    // Class variable for PimPluginRelationsCollection
    protected $_PimPluginRelationsCollection;

    // Class variable for cApiAreaCollection;
    protected $_ApiAreaCollection;

    // Class variable for cApiActionCollection
    protected $_ApiActionCollection;

    // Class variable for cApiFileCollection
    protected $_ApiFileCollection;

    // Class variable for cApiFrameFileCollection
    protected $_ApiFrameFileCollection;

    // Class variable for cApiNavMainCollection
    protected $_ApiNavMainCollection;

    // Class variable for cApiNavSubCollection
    protected $_ApiNavSubCollection;

    // Class variable for cApiTypeCollection
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
     * @return cApiNavSubCollection
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

        // Initializing and set classes
        // PluginManager classes
        $this->_setPimPluginCollection();
        $this->_setPimPluginRelationsCollection();

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
     * @param bool $sql Optional parameter to set sql true (standard) or
     *        false
     */
    public function uninstall($sql = true) {
        $cfg = cRegistry::getConfig();

        // Dependencies checks
        $this->_uninstallCheckDependencies();

        // get relations
        $this->_PimPluginRelationsCollection->setWhere('idplugin', parent::_getPluginId());
        $this->_PimPluginRelationsCollection->query();

        $relations = array();

        while (($relation = $this->_PimPluginRelationsCollection->next()) !== false) {
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

        // Get plugininformations
        $this->_PimPluginCollection->setWhere('idplugin', parent::_getPluginId());
        $this->_PimPluginCollection->query();
        $pimPluginSql = $this->_PimPluginCollection->next();

        // Set foldername
        $this->setPluginFoldername($pimPluginSql->get('folder'));

        // Delete specific sql entries or tables, run only if we have no update
        // sql file
        if ($sql == true && PimPluginSetup::_getUpdateSqlFileExist() == false) {
            $this->_uninstallDeleteSpecificSql();
        }

        // Pluginname
        $pluginname = $pimPluginSql->get('name');

        // Delete entries at *_plugins_rel and *_plugins
        $this->_PimPluginRelationsCollection->deleteByWhereClause('idplugin = ' . parent::_getPluginId());
        $this->_PimPluginCollection->deleteByWhereClause('idplugin = ' . parent::_getPluginId());

        // Write new execution order
        $this->_WriteNewExecutionOrder();

        // Success message for uninstall mode
        if (parent::$_GuiPage instanceof cGuiPage && parent::getMode() == 3) {
            parent::info(sprintf(i18n('The plugin <strong>%s</strong> has been successfully removed. To apply the changes please login into backend again.', 'pim'), $pluginname));
        }
    }

    /**
     * Check dependencies to other plugins (dependencies-Tag at plugin.xml)
     */
    private function _uninstallCheckDependencies() {

    	// Call checkDepenendencies function at PimPlugin class
    	// Function returns true or false
    	$result = $this->checkDependencies();

    	// Show an error message when dependencies could be found
    	if ($result === false) {
    		parent::error(sprintf(i18n('This plugin are required by the plugin <strong>%s</strong>, so you can not remove it.', 'pim'), parent::_getPluginName()));
    	}
    }

    /**
     * Delete specific sql entries or tables, full uninstall mode
     */
    protected function _uninstallDeleteSpecificSql() {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();

        $tempSqlFilename = $cfg['path']['contenido'] . $cfg['path']['plugins'] . $this->_getPluginFoldername() . DIRECTORY_SEPARATOR . 'plugin_uninstall.sql';

        if (!cFileHandler::exists($tempSqlFilename)) {
            return;
        }

        $tempSqlContent = cFileHandler::read($tempSqlFilename);
        $tempSqlContent = str_replace("\r\n", "\n", $tempSqlContent);
        $tempSqlContent = explode("\n", $tempSqlContent);
        $tempSqlLines = count($tempSqlContent);

        $pattern = '/^(DELETE FROM|DROP TABLE) `?' . parent::SQL_PREFIX . '`?\b/';

        for ($i = 0; $i < $tempSqlLines; $i++) {
            if (preg_match($pattern, $tempSqlContent[$i])) {
                $tempSqlContent[$i] = str_replace(parent::SQL_PREFIX, $cfg['sql']['sqlprefix'] . '_pi', $tempSqlContent[$i]);
                $db->query($tempSqlContent[$i]);
            }
        }
    }

    /**
     * Delete a installed plugin directory
     *
     * @param $foldername name of extracted plugin
     * @param $page page class for success or error message
     */
    public function uninstallDir() {
        $cfg = cRegistry::getConfig();

        // delete folders
        $folderpath = $cfg['path']['contenido'] . $cfg['path']['plugins'] . $this->_getPluginFoldername();
        cDirHandler::recursiveRmdir($folderpath);

        if (parent::$_GuiPage instanceof cGuiPage) {

            // success message
            if (!cFileHandler::exists($folderpath)) {
                parent::info(sprintf(i18n('The pluginfolder <strong>%s</strong> has been successfully uninstalled.', 'pim'), $this->_getPluginFoldername()));
            } else if (cFileHandler::exists($folderpath)) {
                parent::error(sprintf(i18n('The pluginfolder <strong>%s</strong> could not be uninstalled.', 'pim'), $this->_getPluginFoldername()));
            }
        }
    }

    /**
     * Generate (write) new execution order
     *
     * @return boolean
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
?>