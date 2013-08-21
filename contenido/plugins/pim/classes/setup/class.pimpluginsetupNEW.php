<?php
/**
 * This file contains abstract class for CONTENIDO plugins
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
class PimPluginSetup {

    // Initializing variables
    // Variable for installation / update mode:
    // Extracted or uploaded file?
    public static $mode = 0;

    // File name of Xml configuration file for plugins
    protected static $_PluginXmlFilename = "plugin.xml";

    // Specific sql prefix
    protected static $_SqlPrefix = "!PREFIX!";

    // Class variable for cGuiPage
    protected static $_GuiPage;

    // Class variable for PimPluginArchiveExtractor
    protected static $_PimPluginArchiveExtractor;

    // Xml variables
    // General informations of plugin
    public static $_XmlGeneral;

    // Plugin requirements
    public static $_XmlRequirements;

    // CONTENIDO areas: *_area
    public static $_XmlArea;

    // CONTENIDO actions: *_actions
    public static $_XmlActions;

    // CONTENIDO frames: *_frame_files and *_files
    public static $_XmlFrames;

    // CONTENIDO main navigations: *_nav_main
    public static $_XmlNavMain;

    // CONTENIDO sub navigations: *_nav_sub
    public static $_XmlNavSub;

    // CONTENIDO content types: *_type
    public static $_XmlContentType;

    // Id of selected / new plugin
    protected static $pluginId = 0;

    // GET and SET methods for installation routine
    /**
     * Set method for installation / update mode
     * Mode 1: Plugin is already extracted
     * Mode 2: Plugin is uploaded
     *
     * @access public
     * @param string $mode
     * @return void
     */
    public function setMode($mode) {
        switch ($mode) {
            case 'extracted':
                self::$mode = 1;
                break;
            case 'uploaded':
                self::$mode = 2;
                break;
        }
    }

    /**
     * Set method for cGuiPage class
     *
     * @access public
     * @param cGuiPage $page
     * @return void
     */
    public function _setPageClass($page) {
        return self::$_GuiPage = $page;
    }

    /**
     * Initialzing and set variable for PimPluginArchiveExtractor class
     *
     * @access private
     * @param string $tempArchiveNewPath Path to Zip archive
     * @param string $tempArchiveName Name of Zip archive
     * @return PimPluginArchiveExtractor
     */
    private function _setPimPluginArchiveExtractor($tempArchiveNewPath, $tempArchiveName) {
        return self::$_PimPluginArchiveExtractor = new PimPluginArchiveExtractor($tempArchiveNewPath, $tempArchiveName);
    }

    /**
     * Set temporary xml content to static variables
     *
     * @access private
     * @param string $xml
     * @return boid
     */
    private function _setXml($xml) {

        // General plugin informations
        self::$_XmlGeneral = $xml->general;

        // Plugin requirements
        self::$_XmlRequirements = $xml->requirements;

        // CONTENIDO areas: *_area
        self::$_XmlArea = $xml->contenido->areas;

        // CONTENIDO actions: *_actions
        self::$_XmlActions = $xml->contenido->actions;

        // CONTENIDO frames: *_frame_files and *_files
        self::$_XmlFrames = $xml->contenido->frames;

        // CONTENIDO main navigations: *_nav_main
        self::$_XmlNavMain = $xml->contenido->nav_main;

        // CONTENIDO sub navigations: *_nav_sub
        self::$_XmlNavSub = $xml->contenido->nav_sub;

        // CONTENIDO Content Types: *_type
        self::$_XmlContentType = $xml->type;
    }

    /**
     * Set method for PluginId
     *
     * @access protected
     * @param integer $pluginId
     * @return integer
     */
    protected function _setPluginId($pluginId = 0) {
        return $this->pluginId = $pluginId;
    }

    /**
     * Get method for installation / update mode
     *
     * @return integer
     */
    public static function _getMode() {
        return self::$mode;
    }

    /**
     * Get method for PluginId
     *
     * @access protected
     * @return integer
     */
    protected static function _getPluginId() {
        return self::$pluginId;
    }

    // Help methods
    /**
     * checkXml
     * Load plugin datas and run Xml checks
     *
     * @access public
     * @return void
     */
    public function checkXml() {
        $cfg = cRegistry::getConfig();

        if (self::_getMode() == 1) { // Plugin is already extracted
                                     // Get already extracted plugin.xml
            $XmlData = file_get_contents($cfg['path']['contenido'] . $cfg['path']['plugins'] . cSecurity::escapeString($_GET['pluginFoldername']) . DIRECTORY_SEPARATOR . self::$_PluginXmlFilename);
        } elseif (self::_getMode() == 2) { // Plugin is uploaded

            // Name of uploaded Zip archive
            $tempArchiveName = cSecurity::escapeString($_FILES['package']['name']);

            // Path to CONTENIDO temp dir
            $tempArchiveNewPath = $cfg['path']['frontend'] . '/' . $cfg['path']['temp'];

            // Move temporary archive files into CONTENIDO temp dir
            move_uploaded_file($_FILES['package']['tmp_name'], $tempArchiveNewPath . $tempArchiveName);

            // Initializing plugin archive extractor
            try {
                $this->_setPimPluginArchiveExtractor($tempArchiveNewPath, $tempArchiveName);
            } catch (cException $e) {
                self::$_PimPluginArchiveExtractor->destroyTempFiles();
            }

            // Get plugin.xml informations
            $XmlData = self::$_PimPluginArchiveExtractor->extractArchiveFileToVariable(self::$_PluginXmlFilename);
        }

        // Check and set plugin.xml
        if ($this->validXml($XmlData) === true) {
            $this->_setXml(simplexml_load_string($XmlData));
        } else {
            return $this->error(i18n('Invalid Xml document. Please contact the plugin author.', 'pim'));
        }
    }

    /**
     * Validate Xml source
     *
     * @access private
     * @param string $xml
     * @return boolean
     */
    private function validXml($xml) {
        // Initializing PHP DomDocument class
        $dom = new DomDocument();
        $dom->loadXML($xml);

        // Validate
        if ($dom->schemaValidate('plugins/pim/xml/plugin_info.xsd')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Error function with pim_error-Template
     *
     * @access protected
     * @param string $message
     * @return void
     */
    protected static function error($message = '') {

        // Get session variable
        $session = cRegistry::getSession();

        // Destroy temporary files if plugin is uploaded
        if (self::_getMode() == 2) {
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
     *
     * @access protected
     * @param string $message
     * @return void
     */
    protected static function info($message = '') {
        return self::$_GuiPage->displayInfo($message);
    }

}
?>