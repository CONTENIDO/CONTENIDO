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

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');
class PimPluginSetupUninstall extends PimPluginSetup {

    // Initializing variables
    // Plugin specific data
    // Id of installed plugin
    private $PluginId;

    // Foldername of installed plugin
    private $PluginFoldername;

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
     * @access public
     * @param string $foldername
     * @return string
     */
    public function _setPluginFoldername($foldername) {
        return $this->PluginFoldername = cSecurity::escapeString($foldername);
    }

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

    /**
     * Initializing and set variable for cApiActionCollection
     *
     * @access private
     * @return cApiActionCollection
     */
    private function _setApiActionCollection() {
        return $this->_ApiActionCollection = new cApiActionCollection();
    }

    /**
     * Initializing and set variable for cApiAFileCollection
     *
     * @access private
     * @return cApiFileCollection
     */
    private function _setApiFileCollection() {
        return $this->_ApiFileCollection = new cApiFileCollection();
    }

    /**
     * Initializing and set variable for cApiFrameFileCollection
     *
     * @access private
     * @return cApiFrameFileCollection
     */
    private function _setApiFrameFileCollection() {
        return $this->_ApiFrameFileCollection = new cApiFrameFileCollection();
    }

    /**
     * Initializing and set variable for cApiNavMainFileCollection
     *
     * @access private
     * @return cApiNavMainCollection
     */
    private function _setApiNavMainCollection() {
        return $this->_ApiNavMainCollection = new cApiNavMainCollection();
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

    /**
     * Initializing and set variable for cApiTypeCollection
     *
     * @access private
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
        return $this->PluginFoldername;
    }

    // Begin of uninstallation routine
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
     * @access public
     * @param boolean $sql Optional parameter to set sql true (standard) or
     *            false
     * @return void
     */
    public function uninstall($sql = true) {
        $cfg = cRegistry::getConfig();

        // get relations
        $this->_PimPluginRelationsCollection->setWhere('idplugin', parent::_getPluginId());
        $this->_PimPluginRelationsCollection->query();

        $relations = array();

        while (($relation = $this->_PimPluginRelationsCollection->next()) !== false) {
            // Relation to tables *_area, *_nav_main and *_type
            $index = $relation->get('type');

            // Is equivalent to idarea, idnavm or idtype column
            $value = $relation->get('iditem');
            $relations[$index][] = $value;
        }

        // Delete entries with relations to *_area
        if (!empty($relations['area'])) {
            $this->_ApiActionCollection->deleteByWhereClause("idarea IN('" . join("', '", $relations['area']) . "')");
            $this->_ApiFileCollection->deleteByWhereClause("idarea IN('" . join("', '", $relations['area']) . "')");
            $this->_ApiFrameFileCollection->deleteByWhereClause("idarea IN('" . join("', '", $relations['area']) . "')");
            $this->_ApiNavSubCollection->deleteByWhereClause("idarea IN('" . join("', '", $relations['area']) . "')");
            $this->_ApiAreaCollection->deleteByWhereClause("idarea IN('" . join("', '", $relations['area']) . "')");
        }

        // Delete entries from *_nav_main
        if (!empty($relations['navm'])) {
            $this->_ApiNavMainCollection->deleteByWhereClause("idnavm IN('" . join("', '", $relations['navm']) . "')");
        }

        // Delete content types
        if (!empty($relations['ctype'])) {
            $this->_ApiTypeCollection->deleteByWhereClause("idtype IN('" . join("', '", $relations['ctype']) . "')");
        }

        // Get plugininformations
        $this->_PimPluginCollection->setWhere('idplugin', parent::_getPluginId());
        $this->_PimPluginCollection->query();
        $pimPluginSql = $this->_PimPluginCollection->next();

        $foldername = $pimPluginSql->get('folder');

        // Delete specific sql entries or tables
        if ($sql == true) {

            if (parent::_getMode() == 3) { // Uninstall mode
                $this->_uninstallFullDeleteSpecificSql($foldername);
            } elseif (parent::_getMode() == 4) { // Update mode
                $this->_uninstallUpdateDeleteSpecificSql($foldername);
            }
        }

        // Pluginname
        $pluginname = $pimPluginSql->get('name');

        // Delete entries at *_plugins_rel and *_plugins
        $this->_PimPluginRelationsCollection->deleteByWhereClause('idplugin = ' . parent::_getPluginId());
        $this->_PimPluginCollection->deleteByWhereClause('idplugin = ' . parent::_getPluginId());

        // Success message
        if (parent::$_GuiPage instanceof cGuiPage) {
            parent::info(i18n('The plugin', 'pim') . ' <strong>' . $pluginname . '</strong> ' . i18n('has been successfully uninstalled. To apply the changes please login into backend again.', 'pim'));
        }
    }

    /**
     * Delete specific sql entries or tables, full uninstall mode
     *
     * @access protected
     * @param $foldername foldername of installed plugin
     * @return void
     */
    protected function _uninstallFullDeleteSpecificSql($foldername) {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();

        $tempSqlFilename = $cfg['path']['contenido'] . $cfg['path']['plugins'] . $foldername . '/plugin_uninstall.sql';

        if (!cFileHandler::exists($tempSqlFilename)) {
            return;
        }

        $tempSqlContent = cFileHandler::read($tempSqlFilename);
        $tempSqlContent = str_replace("\r\n", "\n", $tempSqlContent);
        $tempSqlContent = explode("\n", $tempSqlContent);
        $tempSqlLines = count($tempSqlContent);

        $pattern = '/(DELETE FROM|DROP TABLE) ' . $this->sqlPrefix . '\b/';

        for ($i = 0; $i < $tempSqlLines; $i++) {
            if (preg_match($pattern, $tempSqlContent[$i])) {
                $tempSqlContent[$i] = str_replace($this->sqlPrefix, $cfg['sql']['sqlprefix'] . '_pi', $tempSqlContent[$i]);
                $db->query($tempSqlContent[$i]);
            }
        }
    }

    /**
     * Delete specific sql entries or tables, update uninstall mode
     *
     * TODO: Optimize this function (CON-1358)
     *
     * @access protected
     * @param $foldername foldername of installed plugin
     * @return void
     */
    protected function _uninstallUpdateDeleteSpecificSql($foldername) {
        $cfg = cRegistry::getConfig();
        $db = cRegistry::getDb();

        // name of uploaded file
        $tempFileName = cSecurity::escapeString($_FILES['package']['name']);

        // path to temporary dir
        $tempFileNewPath = $cfg['path']['frontend'] . '/' . $cfg['path']['temp'];

        parent::_setPimPluginArchiveExtractor($tempFileNewPath, $tempFileName);
        $tempSqlContent = self::$_PimPluginArchiveExtractor->extractArchiveFileToVariable('plugin_update.sql');

        if (empty($tempSqlContent)) {
            return;
        }

        $tempSqlContent = str_replace("\r\n", "\n", $tempSqlContent);
        $tempSqlContent = explode("\n", $tempSqlContent);
        $tempSqlLines = count($tempSqlContent);

        $pattern = '/(UPDATE|ALTER TABLE|DELETE FROM|DROP TABLE) ' . $this->sqlPrefix . '\b/';

        for ($i = 0; $i < $tempSqlLines; $i++) {
            if (preg_match($pattern, $tempSqlContent[$i])) {
                $tempSqlContent[$i] = str_replace($this->sqlPrefix, $cfg['sql']['sqlprefix'] . '_pi', $tempSqlContent[$i]);
                $db->query($tempSqlContent[$i]);
            }
        }
    }

    /**
     * Delete a installed plugin directory
     *
     * @access public
     * @param $foldername name of extracted plugin
     * @param $page page class for success or error message
     * @return void
     */
    public function uninstallDir($foldername, $page = null) {
        $cfg = cRegistry::getConfig();

        // delete folders
        $folderpath = $cfg['path']['contenido'] . $cfg['path']['plugins'] . cSecurity::escapeString($foldername);
        cFileHandler::recursiveRmdir($folderpath);

        if ($page instanceof cGuiPage) {

            // success message
            if (!cFileHandler::exists($folderpath)) {
                parent::info(i18n('The pluginfolder', 'pim') . ' <strong>' . $foldername . '</strong> ' . i18n('has been successfully uninstalled.', 'pim'));
            } else if (cFileHandler::exists($folderpath)) {
                parent::info(i18n('The pluginfolder', 'pim') . ' <strong>' . $foldername . '</strong> ' . i18n('could not be uninstalled.', 'pim'));
            }
        }
    }

}
?>