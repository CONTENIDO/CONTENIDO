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
class PimPluginSetup {

    # Initializing variables
    // Xml variables
    public static $_XmlGeneral;

    public static $_XmlArea;

    public static $_XmlActions;

    public static $_XmlFrames;

    public static $_XmlNavMain;

    public static $_XmlNavSub;

    // Id of new plugin
    protected static $_PluginId = 0;

    # GET and SET methods for installation routine
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
    }

    /**
     * Set method for PluginId
     *
     * @access protected
     * @param integer $pluginId
     * @return integer
     */
    protected function _setPluginId($pluginId = 0) {
        return $this->_PluginId = $pluginId;
    }

    /**
     * Get method for PluginId
     *
     * @access protected
     * @return integer
     */
    protected function _getPluginId() {
        return self::$_PluginId;
    }

    # Help methods for construct function
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
        $dom->loadXML($Xml);

        // Validate
        if ($dom->schemaValidate('plugins/pim/xml/plugin_info.xsd')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Error function
     *
     * @access private
     * @param string $message
     * @return void
     */
    private function error($message = '') {
        echo "No Valid Xml<br />";
    }

    # Begin of program
    /**
     * Construct function
     *
     * @access public
     * @param string $Xml
     * @return void
     */
    public function __construct($Xml) {
        if ($this->validXml($Xml) === true) {
            $this->_setXml(simplexml_load_string($Xml));
        } else {
            return $this->error("No valid Xml");
        }
    }

}
?>