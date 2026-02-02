<?php

/**
 * This file contains abstract class for CONTENIDO plugins
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
 * Standard class for Plugin Manager (PIM)
 *
 * @package    Plugin
 * @subpackage PluginManager
 * @author     frederic.schneider
 */
class PimPluginSetup
{
    /** @var int */
    public const MODE_EXTRACTED = 1;
    /** @var int */
    public const MODE_UPLOADED = 2;
    /** @var int */
    public const MODE_UNINSTALL = 3;
    /** @var int */
    public const MODE_UPDATE = 4;

    /** @var int[] */
    protected const SUPPORTED_MODES = [
        self::MODE_EXTRACTED,
        self::MODE_UPLOADED,
        self::MODE_UNINSTALL,
        self::MODE_UPDATE,
    ];

    /**
     * Specific sql prefix for plugins
     */
    public const PLUGIN_SQL_PREFIX = '!PLUGIN_PREFIX!';

    /**
     * PimPluginCollection instance
     *
     * @var PimPluginCollection
     */
    protected $pimPluginCollection;

    /**
     * PimPluginRelationsCollection instance
     *
     * @var PimPluginRelationsCollection
     */
    protected $pimPluginRelationsCollection;

    /**
     * Initializing variables
     * Variable for installation / update mode, extracted or uploaded file?
     * See also {@see self::SUPPORTED_MODES} constants.
     *
     * @var int
     */
    public static $mode = 0;

    /**
     * @var cGuiPage
     */
    protected static $guiPage;

    /**
     * @var PimPluginArchiveExtractor
     */
    protected static $pimPluginArchiveExtractor;

    /**
     * Help variable.
     * If this variable is true PIM does not run uninstall and install
     * sql file. Standard value: false (update sql file does not exist)
     *
     * @var bool
     */
    private static $updateSqlFileExist = false;

    /**
     * XML variables
     * General information of plugin
     *
     * @var SimpleXMLElement
     */
    public static $xmlGeneral;

    /**
     * Plugin requirements
     *
     * @var SimpleXMLElement
     */
    public static $xmlRequirements;

    /**
     * Plugin dependencies
     *
     * @var SimpleXMLElement
     */
    public static $xmlDependencies;

    /**
     * CONTENIDO areas: *_area
     *
     * @var SimpleXMLElement
     */
    public static $xmlArea;

    /**
     * CONTENIDO actions: *_actions
     *
     * @var SimpleXMLElement
     */
    public static $xmlActions;

    /**
     * CONTENIDO frames: *_frame_files and *_files
     *
     * @var SimpleXMLElement
     */
    public static $xmlFrames;

    /**
     * CONTENIDO main navigations: *_nav_main
     *
     * @var SimpleXMLElement
     */
    public static $xmlNavMain;

    /**
     * CONTENIDO sub navigations: *_nav_sub
     *
     * @var SimpleXMLElement
     */
    public static $xmlNavSub;

    /**
     * CONTENIDO content types: *_type
     *
     * @var SimpleXMLElement
     */
    public static $xmlContentType;

    /**
     * Id of selected/new plugin
     *
     * @var int
     */
    protected static $pluginId = 0;

    /**
     * Name of selected plugin
     *
     * @var string
     */
    protected static $pluginName = '';

    public function __construct()
    {
        $this->setPimPluginCollection(new PimPluginCollection());
        $this->setPimPluginRelationsCollection(new PimPluginRelationsCollection());
    }

    // GET and SET methods for the installation routine

    /**
     * Setter method for installation / update mode, see also {@see self::SUPPORTED_MODES} constants.
     *
     * - Mode 1: Plugin is already extracted
     * - Mode 2: Plugin is uploaded
     * - Mode 3: Plugin is uninstalled
     * - Mode 4: Plugin is updated
     */
    public static function setMode(int $mode)
    {
        if (in_array($mode, self::SUPPORTED_MODES)) {
            self::$mode = $mode;
        }
    }

    /**
     * Setter method for cGuiPage class
     */
    public function setPageClass(cGuiPage $page)
    {
        self::$guiPage = $page;
    }

    /**
     * Setter method to change updateSqlFileExist variable
     */
    protected function setUpdateSqlFileExist(bool $value)
    {
        self::$updateSqlFileExist = $value;
    }

    /**
     * Initialize and set variable for PimPluginArchiveExtractor class
     *
     * @param string $tempArchiveNewPath Path to Zip archive
     * @param string $tempArchiveName Name of Zip archive
     * @throws cException
     */
    protected static function setPimPluginArchiveExtractor(string $tempArchiveNewPath, string $tempArchiveName)
    {
        self::$pimPluginArchiveExtractor = new PimPluginArchiveExtractor($tempArchiveNewPath, $tempArchiveName);
    }

    /**
     * Set temporary xml content to static variables
     */
    private function setXml(SimpleXMLElement $xml)
    {
        // General plugin information
        self::$xmlGeneral = $xml->general;

        // Plugin requirements
        self::$xmlRequirements = $xml->requirements;

        // Plugin dependencies
        self::$xmlDependencies = $xml->dependencies;

        // CONTENIDO areas: *_area
        self::$xmlArea = $xml->contenido->areas;

        // CONTENIDO actions: *_actions
        self::$xmlActions = $xml->contenido->actions;

        // CONTENIDO frames: *_frame_files and *_files
        self::$xmlFrames = $xml->contenido->frames;

        // CONTENIDO main navigations: *_nav_main
        self::$xmlNavMain = $xml->contenido->nav_main;

        // CONTENIDO sub navigations: *_nav_sub
        self::$xmlNavSub = $xml->contenido->nav_sub;

        // CONTENIDO Content Types: *_type
        self::$xmlContentType = $xml->content_types;
    }

    /**
     * Setter method for PluginId
     */
    public function setPluginId(int $pluginId = 0)
    {
        self::$pluginId = $pluginId;
    }

    /**
     * Setter method for PluginName
     */
    public function setPluginName(string $pluginName = '')
    {
        self::$pluginName = $pluginName;
    }

    /**
     * Getter method for installation / update mode.
     */
    public static function getMode(): int
    {
        return self::$mode;
    }

    /**
     * Getter method for PluginId
     */
    protected static function getPluginId(): int
    {
        return self::$pluginId;
    }

    /**
     * Getter method for PluginName
     */
    protected static function getPluginName(): string
    {
        return self::$pluginName;
    }

    /**
     * Getter method for updateSqlFileExist variable
     */
    protected function getUpdateSqlFileExist(): bool
    {
        return self::$updateSqlFileExist;
    }

    // Help methods

    /**
     * Load plugin data and run XML checks
     *
     * @throws cException
     */
    public function checkXml(): bool
    {
        $cfg = cRegistry::getConfig();

        if (self::getMode() == 1) { // Plugin is already extracted
            $xmlData = file_get_contents(PimPluginHelper::getPluginConfigFile(
                cSecurity::escapeString($_GET['pluginFoldername']))
            );
        } elseif (self::getMode() == 2 || self::getMode() == 4) {
            // Plugin is uploaded / Update mode

            // Path to CONTENIDO temp dir
            $tempArchiveNewPath = $cfg['path']['frontend'] . '/' . $cfg['path']['temp'];

            // Check if the temp directory exists, otherwise try to create it
            if (!cDirHandler::exists($tempArchiveNewPath)) {
                $success = cDirHandler::create($tempArchiveNewPath);

                // If PIM cannot create a temporary directory (if it does not exist), throw an error message
                if (!$success) {
                    self::error(sprintf(
                        i18n('Plugin Manager could not find a temporary CONTENIDO directory. Also, it is not possible to create a temporary directory at <em>%s</em>. You have to create it manually.', 'pim'),
                        $tempArchiveNewPath
                    ));
                    return false;
                }
            }

            // Check valid Zip archive
            if (!$this->checkZip()) {
                return false;
            }

            // Name of uploaded Zip archive
            $tempArchiveName = cSecurity::escapeString($_FILES['package']['name']);

            // Move temporary archive files into CONTENIDO temp dir
            move_uploaded_file($_FILES['package']['tmp_name'], $tempArchiveNewPath . $tempArchiveName);

            // Initializing plugin archive extractor
            try {
                self::setPimPluginArchiveExtractor($tempArchiveNewPath, $tempArchiveName);
            } catch (cException $e) {
                if (self::$pimPluginArchiveExtractor instanceof PimPluginArchiveExtractor) {
                    self::$pimPluginArchiveExtractor->destroyTempFiles();
                }
                cLogError($e->getMessage());
                self::error(sprintf(
                    i18n('Plugin Manager could not open the archive file <em>%s</em>. See logs fore more details.', 'pim'),
                    $tempArchiveName
                ));
                return false;
            }

            // Get plugin.xml information
            $xmlData = self::$pimPluginArchiveExtractor->extractArchiveFileToVariable(PimPluginHelper::PLUGIN_CONFIG_FILENAME);
        }

        // Check and set plugin.xml
        if (isset($xmlData) && $this->validXml($xmlData) === true) {
            $this->setXml(simplexml_load_string($xmlData));
        } else {
            self::error(i18n('Invalid Xml document. Please contact the plugin author.', 'pim'));
            return false;
        }

        return true;
    }

    /**
     * Check dependencies to other plugins (dependencies-Tag at plugin.xml)
     * Global function for uninstall and status mode
     * Install mode uses an own dependencies function
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function checkDependencies(): bool
    {
        // Initializing
        $pluginsDir = PimPluginHelper::getPluginsFolderPath();

        // Get uuid from plugin to uninstall
        $this->pimPluginCollection->setWhere('idplugin', self::getPluginId());
        $this->pimPluginCollection->query();
        $pimPluginSql = $this->pimPluginCollection->next();
        $uuidUninstall = $pimPluginSql->get('uuid');

        // Reset query, so we can use PimPluginCollection later again...
        $this->pimPluginCollection->resetQuery();

        // Read all dirs
        $dirs = cDirHandler::read($pluginsDir);
        foreach ($dirs as $folderName) {
            // Skip plugin if it has no plugin.xml file
            if (!cFileHandler::exists(PimPluginHelper::getPluginConfigFile($folderName))) {
                continue;
            }

            // Read plugin.xml files from existing plugins at contenido/plugins dir
            $tempXmlContent = cFileHandler::read(PimPluginHelper::getPluginConfigFile($folderName));

            // Write plugin.xml content into temporary variable
            $tempXml = simplexml_load_string($tempXmlContent);

            $dependenciesCount = count($tempXml->dependencies);
            for ($i = 0; $i < $dependenciesCount; $i++) {
                // Security check
                $depend = cSecurity::escapeString($tempXml->dependencies->depend[$i]);

                // If is no dependencies name defined please go to next dependencies
                if ($depend == '') {
                    continue;
                }

                // Build uuid variable from attributes
                $uuidTemp = "";
                foreach ($tempXml->dependencies->depend[$i]->attributes() as $key => $value) {
                    // We use only uuid attribute and can ignore other attributes
                    if ($key == "uuid") {
                        $uuidTemp = cSecurity::escapeString($value);
                    }
                }

                // Return false if uuid from plugin to uninstall and depended on plugin is the same
                // AND depended on plugin is active
                if ($uuidTemp === $uuidUninstall) {
                    $this->pimPluginCollection->setWhere('uuid', $tempXml->general->uuid);
                    $this->pimPluginCollection->setWhere('active', '1');
                    $this->pimPluginCollection->query();

                    if ($this->pimPluginCollection->count() != 0) {
                        self::setPluginName(cSecurity::toString($tempXml->general->plugin_name));
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Check file type, Plugin Manager accepts only Zip archives
     *
     * @throws cException
     */
    private function checkZip(): bool
    {
        if (cString::getPartOfString($_FILES['package']['name'], -4) != ".zip") {
            self::error(i18n('Plugin Manager accepts only Zip archives', 'pim'));
            return false;
        }
        return true;
    }

    /**
     * Validate Xml source
     * @param string $xml
     */
    private function validXml(string $xml): bool
    {
        // Initializing PHP DomDocument class
        $dom = new DomDocument();
        $dom->loadXML($xml);

        // Validate
        return $dom->schemaValidate('plugins/pim/xml/plugin_info.xsd');
    }

    /**
     * Initialize and set variable for PimPluginCollection class
     */
    private function setPimPluginCollection(PimPluginCollection $pimPluginCollection)
    {
        $this->pimPluginCollection = $pimPluginCollection;
    }

    /**
     * Initialize and set variable for PimPluginRelationsCollection class
     */
    private function setPimPluginRelationsCollection(PimPluginRelationsCollection $pimPluginRelationsCollection)
    {
        $this->pimPluginRelationsCollection = $pimPluginRelationsCollection;
    }

    /**
     * Parses the plugin setup SQL file, performs replacements of placeholders, and executes
     * each matching SQL.
     *
     * @param string $file The plugin setup SQL file (full path).
     * @param string $pattern The pattern to match for found SQL to execute.
     * @return bool True on success otherwise false.
     * @throws cDbException|cInvalidArgumentException
     */
    protected function processSetupSql(string $file, string $pattern): bool
    {
        // Skip using plugin sql if it does not exist
        if (empty($file) || !cFileHandler::exists($file)) {
            return false;
        }

        // Create sql template instance & set prefix placeholder value fort plugins, e.g. 'con_pi'
        $sqlTemplate = new cSqlTemplate();
        $prefix = $sqlTemplate->getPlaceholderValue(cSqlTemplate::PREFIX_PLACEHOLDER) . '_pi';
        $sqlTemplate->addReplacements([cSqlTemplate::PREFIX_PLACEHOLDER => $prefix]);

        // Parse sql file content to perform the replacements
        $tempSqlContent = $sqlTemplate->parse(cFileHandler::read($file));
        $tempSqlContent = str_replace("\r\n", "\n", $tempSqlContent);
        $tempSqlContent = explode("\n", $tempSqlContent);
        $tempSqlLines = count($tempSqlContent);

        // Replace the plugin sql prefix placeholder in pattern
        $pattern = str_replace(self::PLUGIN_SQL_PREFIX, $prefix, $pattern);

        // Execute each matching SQL
        $db = cRegistry::getDb();
        for ($i = 0; $i < $tempSqlLines; $i++) {
            if (preg_match($pattern, $tempSqlContent[$i])) {
                $db->query($tempSqlContent[$i]);
            }
        }

        return true;
    }

    /**
     * Error function with pim_error-Template
     *
     * @throws cException|cInvalidArgumentException
     */
    protected static function error(string $message = '')
    {
        // Get session variable
        $session = cRegistry::getSession();

        // Destroy temporary files if plugin is uploaded
        if (self::getMode() == 2) {
            if (self::$pimPluginArchiveExtractor instanceof PimPluginArchiveExtractor) {
                self::$pimPluginArchiveExtractor->destroyTempFiles();
            }
        }

        // Error template
        $pimError = new cGuiPage('pim_error', 'pim');
        $pimError->set('s', 'BACKLINK', $session->url('main.php?area=pim&frame=4'));
        $pimError->set('s', 'LANG_BACKLINK', i18n('Back to Plugin Manager', 'pim'));
        $pimError->displayError($message);
        $pimError->render();
        exit();
    }

    /**
     * Info function, passes the message to the GUI pages' displayOk function.
     *
     * @param string $message
     */
    protected static function info(string $message = '')
    {
        self::$guiPage->displayOk($message);
    }

}
