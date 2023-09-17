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

    /**
     * File name of Xml configuration file for plugins
     */
    const PLUGIN_XML_FILENAME = "plugin.xml";

    /**
     * Specific sql prefix for plugins
     */
    const PLUGIN_SQL_PREFIX = '!PLUGIN_PREFIX!';

    /**
     * PimPluginCollection instance
     *
     * @var PimPluginCollection
     */
    protected $_pimPluginCollection;

    /**
     * PimPluginRelationsCollection instance
     *
     * @var PimPluginRelationsCollection
     */
    protected $_pimPluginRelationsCollection;

    /**
     * Initializing variables
     * Variable for installation / update mode:
     * Extracted or uploaded file?
     *
     * @var int
     */
    public static $mode = 0;

    /**
     * @var cGuiPage
     */
    protected static $_GuiPage;

    /**
     * @var PimPluginArchiveExtractor
     */
    protected static $_PimPluginArchiveExtractor;

    /**
     * Help variable.
     * If this variable is true PIM does not run uninstall and install
     * sql file. Standard value: false (update sql file does not exist)
     *
     * @var bool
     */
    private static $_updateSqlFileExist = false;

    /**
     * Xml variables
     * General information of plugin
     *
     * @var SimpleXMLElement
     */
    public static $XmlGeneral;

    /**
     * Plugin requirements
     *
     * @var SimpleXMLElement
     */
    public static $XmlRequirements;

    /**
     * Plugin dependencies
     *
     * @var SimpleXMLElement
     */
    public static $XmlDependencies;

    /**
     * CONTENIDO areas: *_area
     *
     * @var SimpleXMLElement
     */
    public static $XmlArea;

    /**
     * CONTENIDO actions: *_actions
     *
     * @var SimpleXMLElement
     */
    public static $XmlActions;

    /**
     * CONTENIDO frames: *_frame_files and *_files
     *
     * @var SimpleXMLElement
     */
    public static $XmlFrames;

    /**
     * CONTENIDO main navigations: *_nav_main
     *
     * @var SimpleXMLElement
     */
    public static $XmlNavMain;

    /**
     * CONTENIDO sub navigations: *_nav_sub
     *
     * @var SimpleXMLElement
     */
    public static $XmlNavSub;

    /**
     * CONTENIDO content types: *_type
     *
     * @var SimpleXMLElement
     */
    public static $XmlContentType;

    /**
     * Id of selected/new plugin
     *
     * @var int
     */
    protected static $_pluginId = 0;

    /**
     * Name of selected plugin
     *
     * @var string
     */
    protected static $_pluginName;

    public function __construct()
    {
        $this->_setPimPluginCollection();
        $this->_setPimPluginRelationsCollection();
    }

    // GET and SET methods for installation routine

    /**
     * Set method for installation / update mode
     * Mode 1: Plugin is already extracted
     * Mode 2: Plugin is uploaded
     *
     * @param string $mode
     */
    public static function setMode($mode)
    {
        switch ($mode) {
            case 'extracted':
                self::$mode = 1;
                break;
            case 'uploaded':
                self::$mode = 2;
                break;
            case 'uninstall':
                self::$mode = 3;
                break;
            case 'update':
                self::$mode = 4;
                break;
        }
    }

    /**
     * Set method for cGuiPage class
     *
     * @param cGuiPage $page
     *
     * @return cGuiPage
     */
    public function setPageClass($page)
    {
        return self::$_GuiPage = $page;
    }

    /**
     * Set method to change updateSqlFileExist variable
     *
     * @param bool $value
     */
    protected function _setUpdateSqlFileExist($value)
    {
        self::$_updateSqlFileExist = cSecurity::toBoolean($value);
    }

    /**
     * Initialize and set variable for PimPluginArchiveExtractor class
     *
     * @param string $tempArchiveNewPath Path to Zip archive
     * @param string $tempArchiveName Name of Zip archive
     * @return PimPluginArchiveExtractor
     * @throws cException
     */
    protected static function _setPimPluginArchiveExtractor($tempArchiveNewPath, $tempArchiveName)
    {
        return self::$_PimPluginArchiveExtractor = new PimPluginArchiveExtractor($tempArchiveNewPath, $tempArchiveName);
    }

    /**
     * Set temporary xml content to static variables
     *
     * @param SimpleXMLElement $xml
     */
    private function _setXml($xml)
    {
        // General plugin information
        self::$XmlGeneral = $xml->general;

        // Plugin requirements
        self::$XmlRequirements = $xml->requirements;

        // Plugin dependencies
		self::$XmlDependencies = $xml->dependencies;

        // CONTENIDO areas: *_area
        self::$XmlArea = $xml->contenido->areas;

        // CONTENIDO actions: *_actions
        self::$XmlActions = $xml->contenido->actions;

        // CONTENIDO frames: *_frame_files and *_files
        self::$XmlFrames = $xml->contenido->frames;

        // CONTENIDO main navigations: *_nav_main
        self::$XmlNavMain = $xml->contenido->nav_main;

        // CONTENIDO sub navigations: *_nav_sub
        self::$XmlNavSub = $xml->contenido->nav_sub;

        // CONTENIDO Content Types: *_type
        self::$XmlContentType = $xml->content_types;
    }

    /**
     * Set method for PluginId
     *
     * @param int $pluginId
     *
     * @return int
     */
    public function setPluginId($pluginId = 0)
    {
        return self::$_pluginId = $pluginId;
    }

    /**
     * Set method for PluginName
     *
     * @param string $pluginName
     *
     * @return string
     */
    public function setPluginName($pluginName = '')
    {
    	return self::$_pluginName = $pluginName;
    }

    /**
     * Get method for installation / update mode
     *
     * @return int
     */
    public static function getMode()
    {
        return self::$mode;
    }

    /**
     * Get method for PluginId
     *
     * @return int
     */
    protected static function _getPluginId()
    {
        return self::$_pluginId;
    }

    /**
     * Get methos for PluginName
     *
     * @return string
     */
    protected static function _getPluginName()
    {
    	return self::$_pluginName;
    }

    /**
     * Set method for updateSqlFileExist variable
     *
     * @return bool
     */
    protected function _getUpdateSqlFileExist()
    {
        return self::$_updateSqlFileExist;
    }

    // Help methods

    /**
     * checkXml
     * Load plugin data and run Xml checks
     *
     * @throws cException
     */
    public function checkXml()
    {
        $cfg = cRegistry::getConfig();

        if (self::getMode() == 1) { // Plugin is already extracted
            $XmlData = file_get_contents(cRegistry::getBackendPath() . $cfg['path']['plugins'] . cSecurity::escapeString($_GET['pluginFoldername']) . DIRECTORY_SEPARATOR . self::PLUGIN_XML_FILENAME);
        } elseif (self::getMode() == 2 || self::getMode() == 4) {
            // Plugin is uploaded / Update mode

            // Path to CONTENIDO temp dir
            $tempArchiveNewPath = $cfg['path']['frontend'] . DIRECTORY_SEPARATOR . $cfg['path']['temp'];

            // Check if temp directory exists, otherwise try to create it
            if (!cDirHandler::exists($tempArchiveNewPath)) {
				$success = cDirHandler::create($tempArchiveNewPath);

				// If PIM can not create a temporary directory (if it does not exist), throw an error message
				if (!$success) {
					self::error(sprintf(i18n('Plugin Manager could not find a temporary CONTENIDO directory. Also, it is not possible to create a temporary directory at <em>%s</em>. You have to create it manually.', 'pim'), $tempArchiveNewPath));
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
                self::_setPimPluginArchiveExtractor($tempArchiveNewPath, $tempArchiveName);
            } catch (cException $e) {
                if (self::$_PimPluginArchiveExtractor instanceof PimPluginArchiveExtractor) {
                    self::$_PimPluginArchiveExtractor->destroyTempFiles();
                }
                cLogError($e->getMessage());
                self::error(sprintf(i18n('Plugin Manager could not open the archive file <em>%s</em>. See logs fore more details.', 'pim'), $tempArchiveName));
                return false;
            }

            // Get plugin.xml information
            $XmlData = self::$_PimPluginArchiveExtractor->extractArchiveFileToVariable(self::PLUGIN_XML_FILENAME);
        }

        // Check and set plugin.xml
        if ($this->validXml($XmlData) === true) {
            $this->_setXml(simplexml_load_string($XmlData));
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
     * @return bool
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function checkDependencies()
    {
    	// Initializing
    	$cfg = cRegistry::getConfig();
    	$pluginsDir = cRegistry::getBackendPath() . $cfg['path']['plugins'];

    	// Get uuid from plugin to uninstall
    	$this->_pimPluginCollection->setWhere('idplugin', self::_getPluginId());
    	$this->_pimPluginCollection->query();
    	$pimPluginSql = $this->_pimPluginCollection->next();
    	$uuidUninstall = $pimPluginSql->get('uuid');

    	// Reset query, so we can use PimPluginCollection later again...
    	$this->_pimPluginCollection->resetQuery();

    	// Read all dirs
    	$dirs = cDirHandler::read($pluginsDir);
    	foreach ($dirs as $dirname) {
    		// Skip plugin if it has no plugin.xml file
    		if (!cFileHandler::exists($pluginsDir . $dirname . DIRECTORY_SEPARATOR . self::PLUGIN_XML_FILENAME)) {
    			continue;
    		}

    		// Read plugin.xml files from existing plugins at contenido/plugins dir
    		$tempXmlContent = cFileHandler::read($pluginsDir . $dirname . DIRECTORY_SEPARATOR . self::PLUGIN_XML_FILENAME);

    		// Write plugin.xml content into temporary variable
    		$tempXml = simplexml_load_string($tempXmlContent);

    		$dependenciesCount = count($tempXml->dependencies);
    		for ($i = 0; $i < $dependenciesCount; $i++) {
    			// Security check
    			$depend = cSecurity::escapeString($tempXml->dependencies->depend[$i]);

    			// If is no dependencies name defined please go to next dependencies
    			if ($depend == "") {
    				continue;
    			}

    			// Build uuid variable from attributes
                $uuidTemp = "";
    			foreach ($tempXml->dependencies->depend[$i]->attributes() as $key => $value) {
    				// We use only uuid attribute and can ignore other attributes
    				if ($key  == "uuid") {
    					$uuidTemp = cSecurity::escapeString($value);
    				}
    			}

    			// Return false if uuid from plugin to uninstall and depended on plugin is the same
    			// AND depended on plugin is active
    			if ($uuidTemp === $uuidUninstall) {
    				$this->_pimPluginCollection->setWhere('uuid', $tempXml->general->uuid);
    				$this->_pimPluginCollection->setWhere('active', '1');
    				$this->_pimPluginCollection->query();

    				if ($this->_pimPluginCollection->count() != 0) {
    					self::setPluginName($tempXml->general->plugin_name);
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
     * @return bool
     * @throws cException
     */
    private function checkZip()
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
     * @return bool
     */
    private function validXml($xml)
    {
        // Initializing PHP DomDocument class
        $dom = new DomDocument();
        $dom->loadXML($xml);

        // Validate
        if ($dom->schemaValidate('plugins' . DIRECTORY_SEPARATOR . 'pim' . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'plugin_info.xsd')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Initialize and set variable for PimPluginCollection class
     */
    private function _setPimPluginCollection()
    {
        $this->_pimPluginCollection = new PimPluginCollection();
    }

    /**
     * Initialize and set variable for PimPluginRelationsCollection class
     */
    private function _setPimPluginRelationsCollection()
    {
        $this->_pimPluginRelationsCollection = new PimPluginRelationsCollection();
    }

    /**
     * Parses the plugin setup SQL file, performs replacements of placeholders, and executes
     * each matching SQL.
     *
     * @param string $file The plugin setup SQL file (full path).
     * @param string $pattern The pattern to match for found SQL to execute.
     * @return bool True on success otherwise false.
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    protected function _processSetupSql(string $file, string $pattern): bool
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
     * @param string $message
     *
     * @throws cException
     * @throws cInvalidArgumentException
     */
    protected static function error($message = '')
    {
        // Get session variable
        $session = cRegistry::getSession();

        // Destroy temporary files if plugin is uploaded
        if (self::getMode() == 2) {
            if (self::$_PimPluginArchiveExtractor instanceof PimPluginArchiveExtractor) {
                self::$_PimPluginArchiveExtractor->destroyTempFiles();
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
     * Info function, used displayOk CONTENIDO method
     *
     * @param string $message
     */
    protected static function info($message = '')
    {
        self::$_GuiPage->displayOk($message);
    }

}
