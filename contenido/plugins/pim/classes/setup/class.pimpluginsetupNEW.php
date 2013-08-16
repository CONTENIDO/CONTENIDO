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

    // Class variable for PimPluginArchiveExtractor
    protected $_PimPluginArchiveExtractor;

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
     * Initialzing and set variable for PimPluginArchiveExtractor class
     *
     * @access private
     * @return PimPluginArchiveExtractor
     */
    private function _setPimPluginArchiveExtractor() {
        return $this->_PimPluginArchiveExtractor = new PimPluginArchiveExtractor();
    }

    /**
     * Set temporary xml content to static variables
     *
     * @access private
     * @param string $Xml
     * @return boid
     */
    private function _setXml($Xml) {

        // General plugin informations
        self::$_XmlGeneral = $Xml->general;
		
		// Plugin requirements
        self::$_XmlRequirements = $Xml->requirements;

        // CONTENIDO areas: *_area
        self::$_XmlArea = $Xml->contenido->areas;

        // CONTENIDO actions: *_actions
        self::$_XmlActions = $Xml->contenido->actions;

        // CONTENIDO frames: *_frame_files and *_files
        self::$_XmlFrames = $Xml->contenido->frames;

        // CONTENIDO main navigations: *_nav_main
        self::$_XmlNavMain = $Xml->contenido->nav_main;

        // CONTENIDO sub navigations: *_nav_sub
        self::$_XmlNavSub = $Xml->contenido->nav_sub;

        // CONTENIDO Content Types: *_type
        self::$_XmlContentType = $Xml->type;
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
    public function _getMode() {
        return self::$mode;
    }

    /**
     * Get method for PluginId
     *
     * @access protected
     * @return integer
     */
    protected function _getPluginId() {
        return self::$pluginId;
    }

    // Help methods for construct function
    /**
     * Validate Xml source
     *
     * @access private
     * @param string $Xml
     * @return boolean
     */
    private function validXml($Xml) {
        // Initializing PHP DomDocument class
        $dom = new DomDocument();
        $dom->load($Xml);

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
    protected function error($message = '') {

        // Get session variable
        $session = cRegistry::getSession();

        // Destroy temporary files if plugin is uploaded
        if ($this->mode == 2) {
            parent::$_PimPluginArchiveExtractor->destroyTempFiles();
        }

        // Error template
        $pimError = new cGuiPage('pim_error', 'pim');
        $pimError->set('s', 'BACKLINK', $session->url('main.php?area=pim&frame=4'));
        $pimError->set('s', 'LANG_BACKLINK', i18n('Back to Plugin Manager', 'pim'));
        $pimError->displayError($message);
        $pimError->render();
        exit();
    }

    // Begin of program
    /**
     * Construct function
     *
     * @access public
	 * @param string $Xml
     * @return void
     */
    public function __construct($Xml) {
        //self::_setPimPluginArchiveExtractor();

		$XmlPath = cSecurity::escapeString($Xml) . DIRECTORY_SEPARATOR . self::$_PluginXmlFilename;

        if ($this->validXml($XmlPath) === true) {
            $this->_setXml(simplexml_load_string(file_get_contents($XmlPath)));
        } else {
            return $this->error(i18n('Invalid Xml document. Please contact the plugin author.', 'pim'));
        }
    }

}
?>