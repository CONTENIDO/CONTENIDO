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
 * Install class for new plugins, extends PimPluginSetup
 *
 * @package    Plugin
 * @subpackage PluginManager
 * @author     frederic.schneider
 */
class PimPluginSetupInstall extends PimPluginSetup
{

    // Initializing variables
    // Plugin specific data
    // Foldername of the installed plugin
    private $pluginFoldername = '';

    // All area entries from the database in an array
    private $pluginInstalledAreas = [];

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
    private function setPluginFoldername(string $foldername)
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

    /**
     * Get method for installed areas
     */
    protected function getInstalledAreas(): array
    {
        return $this->pluginInstalledAreas;
    }

    /**
     * Get id of nav_main entry by its name.
     *
     * @return bool|int
     * @throws cDbException|cException
     */
    protected function getNavMainId(string $navMainName = '')
    {
        if (!$navMainName) {
            return false;
        }

        $this->apiNavMainCollection->setWhere('name', cSecurity::escapeString($navMainName));
        $this->apiNavMainCollection->query();
        if ($this->apiNavMainCollection->count() == 0) {
            return false;
        } else {
            $entry = $this->apiNavMainCollection->next();
            return cSecurity::toInteger($entry->get('idnavm'));
        }
    }

    // Begin of installation routine

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
     * Installation method
     *
     * @throws cException
     */
    public function install()
    {
        // Does this plugin already exist?
        $this->installCheckUuid();

        // Requirement checks
        $this->installCheckRequirements();

        // Dependencies checks
        $this->installCheckDependencies();

        // Add new plugin: *_plugins
        $this->installAddPlugin();

        // Get all area names from database
        $this->installFillAreas();

        // Add new CONTENIDO areas: *_area
        $this->installAddAreas();

        // Add new CONTENIDO actions: *_actions
        $this->installAddActions();

        // Add new CONTENIDO frames: *_frame_files and *_files
        $this->installAddFrames();

        // Add new CONTENIDO main navigations: *_nav_main
        $this->installAddNavMain();

        // Add new CONTENIDO sub navigations: *_nav_sub
        $this->installAddNavSub();

        // Add specific sql queries, run only if we have no update sql file
        if (parent::getUpdateSqlFileExist() === false) {
            $this->installAddSpecificSql();
        }

        // Add new CONTENIDO content types: *_type
        $this->installAddContentTypes();

        // Add new modules
        $this->installAddModules();

        // Add plugin dir for uploaded plugins
        if (parent::getMode() == 2) {
            $this->installAddDir();
        }

        // Success message for new plugins
        // Get only for extracted (1) and installed mode (2)
        if (parent::getMode() <= 2) {
            parent::info(i18n('The plugin has been successfully installed. To apply the changes please login into backend again.', 'pim'));
        }
    }

    /**
     * Check uuId: You can install a plugin only for one time
     *
     * @throws cException
     */
    private function installCheckUuid()
    {
        $this->pimPluginCollection->setWhere('uuid', parent::$xmlGeneral->uuid);
        $this->pimPluginCollection->query();
        if ($this->pimPluginCollection->count() > 0) {
            parent::error(i18n('You can install this plugin only for one time.', 'pim'));
        }
    }

    /**
     * This function checks requirements for one plugin
     *
     * @throws cException
     */
    private function installCheckRequirements()
    {
        // Check min CONTENIDO version
        $minVersion = parent::$xmlRequirements->attributes()->minversion;
        if ($minVersion) {
            if (version_compare(CON_VERSION, $minVersion, '<')) {
                parent::error(sprintf(
                    i18n('You have to install CONTENIDO <strong>%s</strong> or higher to install this plugin!', 'pim'),
                    $minVersion
                ));
            }
        }

        // Check max CONTENIDO version
        $maxVersion = parent::$xmlRequirements->attributes()->maxversion;
        if ($maxVersion) {
            if (version_compare(CON_VERSION, $maxVersion, '>')) {
                parent::error(sprintf(
                    i18n('Your current CONTENIDO version is to new - max CONTENIDO version: %s', 'pim'),
                    $maxVersion
                ));
            }
        }

        // Check PHP version
        $phpVersion = parent::$xmlRequirements->attributes()->php;
        if (version_compare(phpversion(), $phpVersion, '<')) {
            parent::error(sprintf(
                i18n('You have to install PHP <strong>%s</strong> or higher to install this plugin!', 'pim'),
                $phpVersion
            ));
        }

        // Check extensions
        if (count(parent::$xmlRequirements->extension) != 0) {
            for ($i = 0; $i < count(parent::$xmlRequirements->extension); $i++) {
                if (!extension_loaded(parent::$xmlRequirements->extension[$i]->attributes()->name)) {
                    parent::error(sprintf(
                        i18n('The plugin could not find the PHP extension <strong>%s</strong>. Because this is required by the plugin, it can not be installed.', 'pim'),
                        parent::$xmlRequirements->extension[$i]->attributes()->name
                    ));
                }
            }
        }

        // Check classes
        if (count(parent::$xmlRequirements->class) != 0) {
            for ($i = 0; $i < count(parent::$xmlRequirements->class); $i++) {
                if (!class_exists(parent::$xmlRequirements->class[$i]->attributes()->name)) {
                    parent::error(sprintf(
                        i18n('The plugin could not find the class <strong>%s</strong>. Because this is required by the plugin, it can not be installed.', 'pim'),
                        parent::$xmlRequirements->class[$i]->attributes()->name
                    ));
                }
            }
        }

        // Check functions
        if (count(parent::$xmlRequirements->function) != 0) {
            for ($i = 0; $i < count(parent::$xmlRequirements->function); $i++) {
                if (!function_exists(parent::$xmlRequirements->function[$i]->attributes()->name)) {
                    parent::error(sprintf(
                        i18n('The plugin could not find the function <strong>%s</strong>. Because this is required by the plugin, it can not be installed.', 'pim'),
                        parent::$xmlRequirements->function[$i]->attributes()->name
                    ));
                }
            }
        }
    }

    /**
     * Check dependencies to other plugins (dependencies-Tag at plugin.xml)
     *
     * @throws cException|cInvalidArgumentException
     */
    private function installCheckDependencies()
    {
        $dependenciesCount = count(parent::$xmlDependencies);

        for ($i = 0; $i < $dependenciesCount; $i++) {
            $attributes = [];

            // Build attributes
            foreach (parent::$xmlDependencies->depend[$i]->attributes() as $key => $value) {
                $attributes[$key] = $value;
            }

            // Security check
            $depend = cSecurity::escapeString(parent::$xmlDependencies->depend[$i]);

            if ($depend == '') {
                return;
            }

            // Add attributes "uuid", "min_version" and "max_version" to an array
            $attributes = [
                'uuid' => cSecurity::escapeString($attributes['uuid']),
                'minversion' => cSecurity::escapeString($attributes['min_version'] ?? ''),
                'maxversion' => cSecurity::escapeString($attributes['max_version'] ?? '')
            ];

            $this->pimPluginCollection->setWhere('uuid', $attributes['uuid']);
            $this->pimPluginCollection->setWhere('active', '1');
            $this->pimPluginCollection->query();
            if ($this->pimPluginCollection->count() == 0) {
                parent::error(sprintf(i18n('This plugin required the plugin <strong>%s</strong>.', 'pim'), $depend));
            }

            $plugin = $this->pimPluginCollection->next();

            // Check min plugin version
            $minVersion = parent::$xmlDependencies->depend[$i]->attributes()->minversion;
            if ($minVersion) {
                if (version_compare($plugin->get('version'), $minVersion, '<')) {
                    parent::error(sprintf(
                        i18n('You have to install<strong>%s %</strong> or higher to install this plugin!', 'pim'),
                        $depend,
                        $minVersion
                    ));
                }
            }

            // Check max plugin version
            $maxVersion = parent::$xmlDependencies->depend[$i]->attributes()->maxversion;
            if ($maxVersion) {
                if (version_compare($plugin->get('version'), $maxVersion, '>')) {
                    parent::error(sprintf(
                        i18n('You have to install <strong>%s %s</strong> or lower to install this plugin!', 'pim'),
                        $depend,
                        $maxVersion
                    ));
                }
            }
        }
    }

    /**
     * Add entries at *_plugins
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    private function installAddPlugin()
    {
        // Add entry at *_plugins
        $pimPlugin = $this->pimPluginCollection->create(
            cSecurity::toString(parent::$xmlGeneral->plugin_name),
            cSecurity::toString(parent::$xmlGeneral->description),
            cSecurity::toString(parent::$xmlGeneral->author),
            cSecurity::toString(parent::$xmlGeneral->copyright),
            cSecurity::toString(parent::$xmlGeneral->mail),
            cSecurity::toString(parent::$xmlGeneral->website),
            cSecurity::toString(parent::$xmlGeneral->version),
            cSecurity::toString(parent::$xmlGeneral->plugin_foldername),
            cSecurity::toString(parent::$xmlGeneral->uuid),
            cSecurity::toInteger(parent::$xmlGeneral->attributes()->active)
        );

        // Set pluginId
        parent::setPluginId($pimPlugin->get('idplugin'));

        // Set foldername of new plugin
        $this->setPluginFoldername(cSecurity::toString(parent::$xmlGeneral->plugin_foldername));
    }

    /**
     * Fetch and set all area names from database
     *
     * @throws cDbException|cException
     */
    private function installFillAreas()
    {
        $this->apiAreaCollection->select(NULL, NULL, 'name');
        while ($areas = $this->apiAreaCollection->next()) {
            $this->pluginInstalledAreas[] = $areas->get('name');
        }
    }

    /**
     * Add entries at *_area
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    private function installAddAreas()
    {
        $elements = parent::$xmlArea->area;
        if (empty($elements)) {
            return;
        }

        // Get Id of plugin
        $pluginId = parent::getPluginId();

        foreach ($elements as $element) {
            // Initializing attributes array
            $attributes = [];

            // Build attributes
            foreach ($element->attributes() as $key => $value) {
                $attributes[$key] = $value;
            }

            // Security check
            $area = cSecurity::escapeString($element);

            // Add attributes "parent", "relevant" and "menuless" to an array
            $attributes = [
                'parent' => isset($attributes['parent']) ? cSecurity::escapeString($attributes['parent']) : '',
                'relevant' => isset($attributes['relevant']) ? cSecurity::toInteger($attributes['relevant']) : 0,
                'menuless' => isset($attributes['menuless']) ? cSecurity::toInteger($attributes['menuless']) : 0
            ];

            // Fix for parent and relevant attributes
            if (empty($attributes['parent'])) {
                $attributes['parent'] = 0;
            }

            if (empty($attributes['relevant'])) {
                $attributes['relevant'] = 1;
            }

            // Create a new entry
            $item = $this->apiAreaCollection->create($area, $attributes['parent'], $attributes['relevant'], 1, $attributes['menuless']);

            // Set a relation
            $this->pimPluginRelationsCollection->create($item->get('idarea'), $pluginId, 'area');

            // Add new area to all area array
            $this->pluginInstalledAreas[] = $area;
        }
    }

    /**
     * Add entries at *_actions
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    private function installAddActions()
    {
        $elements = parent::$xmlActions->action;
        if (empty($elements)) {
            return;
        }

        // Initializing attributes array
        $attributes = [];

        // Get Id of plugin
        $pluginId = parent::getPluginId();

        foreach ($elements as $element) {
            // Build attributes
            foreach ($element->attributes() as $key => $value) {
                $attributes[$key] = $value;
            }

            // Set relevant value if it is empty
            if (empty($attributes['relevant'])) {
                $attributes['relevant'] = 1;
            }

            // Add attributes "area" and "relevant" to an safe array
            $attributes = [
                'area' => cSecurity::escapeString($attributes['area']),
                'relevant' => cSecurity::toInteger($attributes['relevant'])
            ];

            // Security check for action name
            $action = cSecurity::escapeString($element);

            // Check for valid area
            if (!in_array($attributes['area'], $this->getInstalledAreas())) {
                parent::error(sprintf(i18n('Defined area <strong>%s</strong> are not found on your CONTENIDO installation. Please contact your plugin author.', 'pim'), $attributes['area']));
            }

            // Create a new entry
            $item = $this->apiActionCollection->create($attributes['area'], $action, '', '', '', $attributes['relevant']);

            // Set a relation
            $this->pimPluginRelationsCollection->create($item->get('idaction'), $pluginId, 'action');
        }
    }

    /**
     * Add entries at *_frame_files and *_files
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    private function installAddFrames()
    {
        $elements = parent::$xmlFrames->frame;
        if (empty($elements)) {
            return;
        }

        // Initializing attributes array
        $attributes = [];

        // Get Id of plugin
        $pluginId = parent::getPluginId();

        foreach ($elements as $element) {
            // Build attributes with security checks
            foreach ($element->attributes() as $sKey => $sValue) {
                $attributes[$sKey] = cSecurity::escapeString($sValue);
            }

            // Check for valid area
            if (!in_array($attributes['area'], $this->getInstalledAreas())) {
                parent::error(sprintf(i18n('Defined area <strong>%s</strong> are not found on your CONTENIDO installation. Please contact your plugin author.', 'pim'), $attributes['area']));
            }

            // Create a new entry at *_files
            $file = $this->apiFileCollection->create($attributes['area'], $attributes['name'], $attributes['filetype']);

            // Create a new entry at *_frame_files
            if (!empty($attributes['frameId'])) {
                $item = $this->apiFrameFileCollection->create($attributes['area'], $attributes['frameId'], $file->get('idfile'));

                // Set a relation
                $this->pimPluginRelationsCollection->create($item->get('idframefile'), $pluginId, 'framefl');
            }
        }
    }

    /**
     * Add entries at *_nav_main
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    private function installAddNavMain()
    {
        $elements = parent::$xmlNavMain->nav;
        if (empty($elements)) {
            return;
        }

        $db = cRegistry::getDb();

        // Initializing attributes array
        $attributes = [];

        // Get Id of plugin
        $pluginId = parent::getPluginId();

        // Get idnavm information to build a new id
        $idnavm = 0;
        $sql = 'SELECT MAX(`idnavm`) AS `id` FROM ' . cDb::getTableName('nav_main');
        $db->query($sql);
        if ($db->nextRecord()) {
            $idnavm = $db->f('id');
        }
        // id must be over 10.000
        if ($idnavm < 10000) {
            $idnavm = 10000;
        }

        foreach ($elements as $element) {
            // Security check for location
            $location = cSecurity::escapeString($element);

            // Build attributes with security checks
            foreach ($element->attributes() as $sKey => $sValue) {
                $attributes[$sKey] = cSecurity::escapeString($sValue);
            }

            // Fallback for older plugins
            if (!$attributes['name']) {
                $attributes['name'] = cString::toLowerCase($location);
                $attributes['name'] = str_replace('/', '', $attributes['name']);
            }

            // Create new idnavm
            $idnavm = $idnavm + 10;

            // Removed the last number at idnavm
            $idnavm = cString::getPartOfString($idnavm, 0, cString::getStringLength($idnavm) - 1);

            // Last number is always a zero
            $idnavm = cSecurity::toInteger($idnavm . 0);

            // Create a new entry at *_nav_main
            $this->apiNavMainCollection->create($attributes['name'], $location, $idnavm);

            // Set a relation
            $this->pimPluginRelationsCollection->create($idnavm, $pluginId, 'navm');
        }
    }

    /**
     * Add entries at *_nav_sub
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    private function installAddNavSub()
    {
        $elements = parent::$xmlNavSub->nav;
        if (empty($elements)) {
            return;
        }

        // Initializing attributes array
        $attributes = [];

        // Get Id of plugin
        $pluginId = parent::getPluginId();

        foreach ($elements as $element) {
            // Build attributes
            foreach ($element->attributes() as $key => $value) {
                $attributes[$key] = $value;
            }

            // Convert area to string
            $attributes['area'] = cSecurity::toString($attributes['area']);

            // Check for valid area
            if (!in_array($attributes['area'], $this->getInstalledAreas())) {
                parent::error(sprintf(
                    i18n('Defined area <strong>%s</strong> are not found on your CONTENIDO installation. Please contact your plugin author.', 'pim'),
                    $attributes['area']
                ));
            }

            // If navm attribute is a string get its id
            if (!preg_match('/[^a-zA-Z]/u', $attributes['navm'])) {
                $mainNavId = $this->getNavMainId($attributes['navm']);
                if ($mainNavId === false) {
                    parent::error(sprintf(
                        i18n('Can not find <strong>%s</strong> entry at nav_main table on your CONTENIDO installation. Please contact your plugin author.', 'pim'),
                        $attributes['navm']
                    ));
                } else {
                    $attributes['navm'] = $mainNavId;
                }
            }

            // Create a new entry at *_nav_sub
            $item = $this->apiNavSubCollection->create($attributes['navm'], $attributes['area'], $attributes['level'], $element->__toString());

            // Set a relation
            $this->pimPluginRelationsCollection->create($item->get('idnavs'), $pluginId, 'navs');
        }
    }

    /**
     * Add specific sql queries
     *
     * @throws cDbException|cInvalidArgumentException
     */
    private function installAddSpecificSql(): bool
    {
        if (parent::getMode() == 1) {
            // Plugin is already extracted
            $tempSqlFilename = PimPluginHelper::getPluginInstallFile($this->getPluginFoldername());
        } elseif (parent::getMode() == 2 || parent::getMode() == 4) {
            // Plugin is uploaded or / and update mode
            $tempSqlFilename = parent::$pimPluginArchiveExtractor->extractArchiveFileToVariable(
                PimPluginHelper::PLUGIN_INSTALL_FILENAME,
                false
            );
        } else {
            $tempSqlFilename = '';
        }

        $pattern = '/^(CREATE TABLE IF NOT EXISTS|INSERT INTO|UPDATE|ALTER TABLE) `?' . parent::PLUGIN_SQL_PREFIX . '([a-zA-Z0-9\-_]+)`?\b/';

        return $this->processSetupSql($tempSqlFilename, $pattern);
    }

    /**
     * Add content types (*_type)
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    private function installAddContentTypes()
    {
        $elements = parent::$xmlContentType->type;
        if (empty($elements)) {
            return;
        }

        // Get Id of plugin
        $pluginId = parent::getPluginId();

        $pattern = '/^CMS_.+/';

        foreach ($elements as $element) {
            $type = cSecurity::toString($element);

            if (preg_match($pattern, $type)) {
                // Create new content type
                $item = $this->apiTypeCollection->create($type, '');

                // Set a relation
                $this->pimPluginRelationsCollection->create($item->get('idtype'), $pluginId, 'ctype');
            }
        }
    }

    /**
     * Add modules
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    private function installAddModules(): bool
    {
        $module = new cApiModule();

        // Set path to the modules path
        $modulesPath = PimPluginHelper::getPluginsFolderPath() . $this->getPluginFoldername() . '/modules/';

        if (!cDirHandler::exists($modulesPath)) {
            return false;
        }

        foreach (new DirectoryIterator($modulesPath) as $modulesFiles) {
            if ($modulesFiles->isFile() && $modulesFiles->getExtension() === 'zip') {
                // Import found module
                $module->import($modulesFiles->getBasename(), $modulesFiles->getBasename(), false);
            }
        }

        return cDirHandler::recursiveRmdir($modulesPath);
    }

    /**
     * Add plugin dir
     *
     * @throws cInvalidArgumentException
     */
    private function installAddDir()
    {
        // Build the new plugin dir
        $tempPluginDir = PimPluginHelper::getPluginFolderPath(parent::$xmlGeneral->plugin_foldername);

        // Set destination path
        try {
            parent::$pimPluginArchiveExtractor->setDestinationPath($tempPluginDir);
        } catch (cException $e) {
            parent::$pimPluginArchiveExtractor->destroyTempFiles();
        }

        // Extract Zip archive files into the new plugin dir
        try {
            parent::$pimPluginArchiveExtractor->extractArchive();
        } catch (cException $e) {
            parent::$pimPluginArchiveExtractor->destroyTempFiles();
        }
    }

}
