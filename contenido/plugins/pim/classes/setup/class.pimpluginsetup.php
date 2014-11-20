<?php
/**
 * This file contains abstract class for CONTENIDO plugins
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
 * Standard class for Plugin Manager (PIM)
 *
 * @package Plugin
 * @subpackage PluginManager
 * @author frederic.schneider
 */
class PimPluginSetup {

    // Initializing variables
    // Variable for installation / update mode:
    // Extracted or uploaded file?
    public static $mode = 0;

    // File name of Xml configuration file for plugins
    const PLUGIN_XML_FILENAME = "plugin.xml";

    // Specific sql prefix
    const SQL_PREFIX = "!PREFIX!";

    // Class variable for cGuiPage
    protected static $_GuiPage;

    // Class variable for PimPluginArchiveExtractor
    protected static $_PimPluginArchiveExtractor;

    /**
     * Help variable.
     * If this variable is true PIM does not run uninstall and install
     * sql file. Standard value: false (update sql file does not exist)
     *
     * @var boolean
     */
    private static $_updateSqlFileExist = false;

    // Xml variables
    // General informations of plugin
    public static $XmlGeneral;

    // Plugin requirements
    public static $XmlRequirements;

    // Plugin dependencies
    public static $XmlDependencies;

    // CONTENIDO areas: *_area
    public static $XmlArea;

    // CONTENIDO actions: *_actions
    public static $XmlActions;

    // CONTENIDO frames: *_frame_files and *_files
    public static $XmlFrames;

    // CONTENIDO main navigations: *_nav_main
    public static $XmlNavMain;

    // CONTENIDO sub navigations: *_nav_sub
    public static $XmlNavSub;

    // CONTENIDO content types: *_type
    public static $XmlContentType;

    // Id of selected/new plugin
    protected static $_pluginId = 0;

    // Name of selected plugin
    protected static $_pluginName;

    // GET and SET methods for installation routine
    /**
     * Set method for installation / update mode
     * Mode 1: Plugin is already extracted
     * Mode 2: Plugin is uploaded
     *
     * @param string $mode
     */
    public static function setMode($mode) {
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
     */
    public function setPageClass($page) {
        return self::$_GuiPage = $page;
    }

    /**
     * Set method to change updateSqlFileExist variable
     *
     * @param bool $value
     */
    protected function _setUpdateSqlFileExist($value) {
        self::$_updateSqlFileExist = cSecurity::toBoolean($value);
    }

    /**
     * Initialzing and set variable for PimPluginArchiveExtractor class
     *
     * @param string $tempArchiveNewPath Path to Zip archive
     * @param string $tempArchiveName Name of Zip archive
     * @return PimPluginArchiveExtractor
     */
    protected static function _setPimPluginArchiveExtractor($tempArchiveNewPath, $tempArchiveName) {
        return self::$_PimPluginArchiveExtractor = new PimPluginArchiveExtractor($tempArchiveNewPath, $tempArchiveName);
    }

    /**
     * Set temporary xml content to static variables
     *
     * @param string $xml
     */
    private function _setXml($xml) {

        // General plugin informations
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
     * @return int
     */
    public function setPluginId($pluginId = 0) {
        return self::$_pluginId = $pluginId;
    }

    /**
     * Set method for PluginName
     *
     * @param string $pluginName
     * @return string
     */
    public function setPluginName($pluginName = '') {
    	return self::$_pluginName = $pluginName;
    }

    /**
     * Get method for installation / update mode
     *
     * @return int
     */
    public static function getMode() {
        return self::$mode;
    }

    /**
     * Get method for PluginId
     *
     * @return int
     */
    protected static function _getPluginId() {
        return self::$_pluginId;
    }

    /**
     * Get methos for PluginName
     *
     * @return string
     */
    protected static function _getPluginName() {
    	return self::$_pluginName;
    }

    /**
     * Set method for updateSqlFileExist variable
     *
     * @return bool
     */
    protected function _getUpdateSqlFileExist() {
        return self::$_updateSqlFileExist;
    }

    // Help methods
    /**
     * checkXml
     * Load plugin datas and run Xml checks
     */
    public function checkXml() {
        $cfg = cRegistry::getConfig();

        if (self::getMode() == 1) { // Plugin is already extracted
            $XmlData = file_get_contents($cfg['path']['contenido'] . $cfg['path']['plugins'] . cSecurity::escapeString($_GET['pluginFoldername']) . DIRECTORY_SEPARATOR . self::PLUGIN_XML_FILENAME);
        } elseif (self::getMode() == 2 || self::getMode() == 4) { // Plugin is
                                                                  // uploaded /
                                                                  // Update mode

            // Path to CONTENIDO temp dir
            $tempArchiveNewPath = $cfg['path']['frontend'] . DIRECTORY_SEPARATOR . $cfg['path']['temp'];

            // Name of uploaded Zip archive
            $tempArchiveName = cSecurity::escapeString($_FILES['package']['name']);

            // Move temporary archive files into CONTENIDO temp dir
            move_uploaded_file($_FILES['package']['tmp_name'], $tempArchiveNewPath . $tempArchiveName);

            // Initializing plugin archive extractor
            try {
                self::_setPimPluginArchiveExtractor($tempArchiveNewPath, $tempArchiveName);
            } catch (cException $e) {
                self::$_PimPluginArchiveExtractor->destroyTempFiles();
            }

            // Check valid Zip archive
            $this->checkZip();

            // Get plugin.xml informations
            $XmlData = self::$_PimPluginArchiveExtractor->extractArchiveFileToVariable(self::PLUGIN_XML_FILENAME);
        }

        // Check and set plugin.xml
        if ($this->validXml($XmlData) === true) {
            $this->_setXml(simplexml_load_string($XmlData));
        } else {
            return self::error(i18n('Invalid Xml document. Please contact the plugin author.', 'pim'));
        }
    }

    /**
     * Check dependencies to other plugins (dependencies-Tag at plugin.xml)
     * Global function for uninstall and status mode
     * Install mode uses an own dependencies function
     *
     * @return boolean
     */
    public function checkDependencies() {

    	// Initializings
    	$cfg = cRegistry::getConfig();
    	$pluginsDir = $cfg['path']['contenido'] . $cfg['path']['plugins'];

    	// Get uuid from plugin to uninstall
    	$this->_PimPluginCollection->setWhere('idplugin', self::_getPluginId());
    	$this->_PimPluginCollection->query();
    	$pimPluginSql = $this->_PimPluginCollection->next();
    	$uuidUninstall = $pimPluginSql->get('uuid');

    	// Reset query so we can use PimPluginCollection later again...
    	$this->_PimPluginCollection->resetQuery();

    	// Read all dirs
    	$dirs = cDirHandler::read($pluginsDir);
    	foreach ($dirs as $dirname) {

    		// Skip plugin if it has no plugin.xml file
    		if (!cFileHandler::exists($pluginsDir . $dirname . DIRECTORY_SEPARATOR . self::PLUGIN_XML_FILENAME)) {
    			continue;
    		}

    		// Read plugin.xml files from existing plugins at contenido/plugins dir
    		$tempXmlContent = cFileHandler::read($pluginsDir . $dirname . DIRECTORY_SEPARATOR . self::PLUGIN_XML_FILENAME);

    		// Write plugin.xnl content into temporary variable
    		$tempXml = simplexml_load_string($tempXmlContent);

    		$dependenciesCount = count($tempXml->dependencies);
    		for ($i = 0; $i < $dependenciesCount; $i++) {

    			// Security check
    			$depend = cSecurity::escapeString($tempXml->dependencies->depend[$i]);

    			// If is no dependencie name defined please go to next dependencie
    			if ($depend == "") {
    				continue;
    			}

    			// Build uuid variable from attributes
    			foreach ($tempXml->dependencies->depend[$i]->attributes() as $key => $value) {

    				// We use only uuid attribute and can ignore other attributes
    				if ($key  == "uuid") {
    					$uuidTemp = cSecurity::escapeString($value);
    				}
    			}

    			// Return false if uuid from plugin to uninstall and depended plugin is the same
    			// AND depended plugin is active
    			if ($uuidTemp === $uuidUninstall) {

    				$this->_PimPluginCollection->setWhere('uuid', $tempXml->general->uuid);
    				$this->_PimPluginCollection->setWhere('active', '1');
    				$this->_PimPluginCollection->query();
    				if ($this->_PimPluginCollection->count() != 0) {
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
     */
    private function checkZip() {
        if (substr($_FILES['package']['name'], -4) != ".zip") {
            self::error(i18n('Plugin Manager accepts only Zip archives', 'pim'));
        }
    }

    /**
     * Validate Xml source
     * @param string $xml
     * @return bool
     */
    private function validXml($xml) {
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
     * Error function with pim_error-Template
     * @param string $message
     */
    protected static function error($message = '') {

        // Get session variable
        $session = cRegistry::getSession();

        // Destroy temporary files if plugin is uploaded
        if (self::getMode() == 2) {
            self::$_PimPluginArchiveExtractor->destroyTempFiles();
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
     * Info function
     * @param string $message
     */
    protected static function info($message = '') {
        return self::$_GuiPage->displayInfo($message);
    }

}

?>