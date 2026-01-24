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
class PimPluginSetupUninstall extends PimPluginSetup
{
    /**
     * Foldername of installed plugin
     *
     * @var string
     */
    private $pluginFoldername = '';

    /**
     * @var cApiAreaCollection
     */
    protected $apiAreaCollection;

    /**
     * @var cApiActionCollection
     */
    protected $apiActionCollection;

    /**
     * @var cApiFileCollection
     */
    protected $apiFileCollection;

    /**
     * @var cApiFrameFileCollection
     */
    protected $apiFrameFileCollection;

    /**
     * @var cApiNavMainCollection
     */
    protected $apiNavMainCollection;

    /**
     * @var cApiNavSubCollection
     */
    protected $apiNavSubCollection;

    /**
     * @var cApiTypeCollection
     */
    protected $apiTypeCollection;

    // GET and SET methods for the installation routine

    /**
     * Set variable for plugin foldername
     */
    public function setPluginFoldername(string $foldername)
    {
        $this->pluginFoldername = cSecurity::escapeString($foldername);
    }

    /**
     * Initializing and set variable for cApiAreaCollection
     */
    private function setApiAreaCollection(cApiAreaCollection $apiAreaCollection)
    {
        $this->apiAreaCollection = $apiAreaCollection;
    }

    /**
     * Initializing and set variable for cApiActionCollection
     */
    private function setApiActionCollection(cApiActionCollection $apiActionCollection)
    {
        $this->apiActionCollection = $apiActionCollection;
    }

    /**
     * Initializing and set variable for cApiAFileCollection
     */
    private function setApiFileCollection(cApiFileCollection $apiFileCollection)
    {
        $this->apiFileCollection = $apiFileCollection;
    }

    /**
     * Initializing and set variable for cApiFrameFileCollection
     */
    private function setApiFrameFileCollection(cApiFrameFileCollection $apiFrameFileCollection)
    {
        $this->apiFrameFileCollection = $apiFrameFileCollection;
    }

    /**
     * Initializing and set variable for cApiNavMainFileCollection
     */
    private function setApiNavMainCollection(cApiNavMainCollection $apiNavMainCollection)
    {
        $this->apiNavMainCollection = $apiNavMainCollection;
    }

    /**
     * Initializing and set variable for cApiNavSubCollection
     */
    private function setApiNavSubCollection(cApiNavSubCollection $apiNavSubCollection)
    {
        $this->apiNavSubCollection = $apiNavSubCollection;
    }

    /**
     * Initializing and set variable for cApiTypeCollection
     */
    private function setApiTypeCollection(cApiTypeCollection $apiTypeCollection)
    {
        $this->apiTypeCollection = $apiTypeCollection;
    }

    /**
     * Get method for foldername of installed plugin
     */
    protected function getPluginFoldername(): string
    {
        return $this->pluginFoldername;
    }

    // Begin of uninstallation routine

    /**
     * Construct function
     */
    public function __construct()
    {
        parent::__construct();

        // cApiClasses
        $this->setApiAreaCollection(new cApiAreaCollection());
        $this->setApiActionCollection(new cApiActionCollection());
        $this->setApiFileCollection(new cApiFileCollection());
        $this->setApiFrameFileCollection(new cApiFrameFileCollection());
        $this->setApiNavMainCollection(new cApiNavMainCollection());
        $this->setApiNavSubCollection(new cApiNavSubCollection());
        $this->setApiTypeCollection(new cApiTypeCollection());
    }

    /**
     * Uninstall function
     *
     * @param bool $sql Optional parameter to set sql true (standard) or false
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function uninstall($sql = true)
    {
        // Dependencies checks
        $this->uninstallCheckDependencies();

        // get relations
        $this->pimPluginRelationsCollection->setWhere('idplugin', parent::getPluginId());
        $this->pimPluginRelationsCollection->query();

        // Initializing relations array
        $relations = [];

        while ($relation = $this->pimPluginRelationsCollection->next()) {
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
            $this->apiActionCollection->deleteByWhereClause("idaction IN('" . join("', '", $relations['action']) . "')");
        }

        // Delete entries with relations to *_frame_files
        if (!empty($relations['framefl'])) {
            $this->apiFrameFileCollection->deleteByWhereClause("idframefile IN('" . join("', '", $relations['framefl']) . "')");
        }

        // Delete entries with relations to *_area
        if (!empty($relations['area'])) {
            $this->apiFileCollection->deleteByWhereClause("idarea IN('" . join("', '", $relations['area']) . "')");
            $this->apiFrameFileCollection->deleteByWhereClause("idarea IN('" . join("', '", $relations['area']) . "')");
            $this->apiAreaCollection->deleteByWhereClause("idarea IN('" . join("', '", $relations['area']) . "')");
        }

        // Delete entries with relations to *_nav_main
        if (!empty($relations['navm'])) {
            $this->apiNavMainCollection->deleteByWhereClause("idnavm IN('" . join("', '", $relations['navm']) . "')");
        }

        // Delete entries with relations to *_nav_sub
        if (!empty($relations['navs'])) {
            $this->apiNavSubCollection->deleteByWhereClause("idnavs IN('" . join("', '", $relations['navs']) . "')");
        }

        // Delete content types
        if (!empty($relations['ctype'])) {
            $this->apiTypeCollection->deleteByWhereClause("idtype IN('" . join("', '", $relations['ctype']) . "')");
        }

        // Get plugin-information
        $this->pimPluginCollection->resetQuery();
        $this->pimPluginCollection->setWhere('idplugin', parent::getPluginId());
        $this->pimPluginCollection->query();
        $pimPluginSql = $this->pimPluginCollection->next();

        // Set foldername
        $this->setPluginFoldername($pimPluginSql->get('folder'));

        // Delete specific sql entries or tables, run only if we have no update
        // sql file
        if ($sql && !parent::getUpdateSqlFileExist()) {
            $this->uninstallDeleteSpecificSql();
        }

        // Plugin name
        $pluginName = $pimPluginSql->get('name');

        // Delete entries at *_plugins_rel and *_plugins
        $this->pimPluginRelationsCollection->deleteByWhereClause('idplugin = ' . parent::getPluginId());
        $this->pimPluginCollection->deleteByWhereClause('idplugin = ' . parent::getPluginId());

        // Write new execution order
        $this->writeNewExecutionOrder();

        // Success message for uninstall mode
        if (parent::$guiPage instanceof cGuiPage && parent::getMode() == 3) {
            parent::info(sprintf(
                i18n('The plugin <strong>%s</strong> has been successfully removed. To apply the changes please login into backend again.', 'pim'),
                $pluginName
            ));
        }
    }

    /**
     * Check dependencies to other plugins (dependencies-Tag at plugin.xml)
     *
     * @throws cException
     * @throws cInvalidArgumentException
     */
    private function uninstallCheckDependencies()
    {
        // Call checkDependencies function at PimPlugin class
        // Function returns true or false
        $result = $this->checkDependencies();

        // Show an error message when dependencies could be found
        if ($result === false) {
            parent::error(sprintf(
                i18n('This plugin is required by the plugin <strong>%s</strong>, so you can not remove it.', 'pim'),
                parent::getPluginName()
            ));
        }
    }

    /**
     * Delete specific sql entries or tables, full uninstall mode
     *
     * @throws cDbException|cInvalidArgumentException
     */
    protected function uninstallDeleteSpecificSql(): bool
    {
        $tempSqlFilename = PimPluginHelper::getPluginUninstallFile($this->getPluginFoldername());

        $pattern = '/^(DELETE FROM|DROP TABLE) `?' . parent::PLUGIN_SQL_PREFIX . '([a-zA-Z0-9\-_]+)`?\b/';

        return $this->processSetupSql($tempSqlFilename, $pattern);
    }

    /**
     * Delete an installed plugin directory
     *
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function uninstallDir()
    {
        // delete folders
        $folderPath = PimPluginHelper::getPluginsFolderPath() . $this->getPluginFoldername();
        cDirHandler::recursiveRmdir($folderPath);

        if (parent::$guiPage instanceof cGuiPage) {
            // success message
            if (!cFileHandler::exists($folderPath)) {
                parent::info(sprintf(i18n('The plugin folder <strong>%s</strong> has been successfully uninstalled.', 'pim'), $this->getPluginFoldername()));
            } elseif (cFileHandler::exists($folderPath)) {
                parent::error(sprintf(i18n('The plugin folder <strong>%s</strong> could not be uninstalled.', 'pim'), $this->getPluginFoldername()));
            }
        }
    }

    /**
     * Generate (write) new execution order
     *
     * @throws cDbException|cException
     */
    protected function writeNewExecutionOrder(): bool
    {
        // Lowest executionorder is one
        $i = 1;

        $pimPluginColl = new PimPluginCollection();
        $pimPluginColl->setOrder('`executionorder` ASC');
        $pimPluginColl->query();
        while ($pimPluginSql = $pimPluginColl->next()) {
            $pimPluginSql->set('executionorder', $i);
            $pimPluginSql->store();

            $i++;
        }

        return true;
    }

}
