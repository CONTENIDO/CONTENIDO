<?php
/**
 * This file contains abstract class for view navsub entries
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
 * Install class for new plugins, extends PimPluginSetup
 * TODO: Later implement into new PIM view design
 *
 * @package Plugin
 * @subpackage PluginManager
 * @author frederic.schneider
 */
class PimPluginViewNavSub {

    // Pattern for navigation (nav_sub) xml entries
    const PATTERN = '/;(.+)$/';

    // Filename of Xml configuration file for plugins
    const PLUGIN_CONFIG_FILENAME = "plugin.xml";

    // Filename of Xml configuration file for CONTENIDO navigation
    const CONTENIDO_NAVIGATION_FILENAME = "navigation.xml";

    // Initializing variables
    private $PluginFoldername;

    // CONTENIDO sub navigations: *_nav_sub
    public static $XmlNavSub;

    protected $_NavCount = 0;

    // Class variable for DOMDocument
    protected $_DOMDocument;

    // Class variable for cApiNavMainCollection
    protected $_ApiNavMainCollection;

    // Class variable for cApiNavSubCollection
    protected $_ApiNavSubCollection;

    /**
     * Initializing and set variable for DOMDocument
     *
     * @access private
     * @return DOMDocument
     */
    private function _setDOMDocument() {
        return $this->_DOMDocument = new DOMDocument();
    }

    /**
     * Initializing and set variable for cApiNavMainCollection
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
     * Construct function
     *
     * @access public
     * @return void
     */
    public function __construct() {

        // Initializing and set classes
        $this->_setDOMDocument();

        // cApiClasses
        $this->_setApiNavMainCollection();
        $this->_setApiNavSubCollection();
    }

    // GET and SET methods for installation routine
    /**
     * Set variable for plugin foldername
     *
     * @access public
     * @param string $foldername
     * @return string
     */
    public function setPluginFoldername($foldername) {
        return $this->PluginFoldername = cSecurity::escapeString($foldername);
    }

    // View methods
    /**
     * Get nav_sub entries
     *
     * @access public
     * @return Ambigous <string, string>|Ambigous <string, boolean>
     */
    public function getNavSubentries() {
        global $belang;

        $cfg = cRegistry::getConfig();

        // get contents of plugin.xml file
        $dataPluginXml = file_get_contents($cfg['path']['contenido'] . $cfg['path']['plugins'] . $this->PluginFoldername . DIRECTORY_SEPARATOR . self::PLUGIN_CONFIG_FILENAME);

        // load xml strings
        $xmlPluginXml = simplexml_load_string($dataPluginXml);

        // count nav_sub entries for this plugin
        $this->_NavCount = count($xmlPluginXml->contenido->nav_sub->nav);

        // no navigation configured, so we can stop this process
        if ($this->_NavCount == 0) {
            return i18n('No navigation configuration founded', 'pim');
        }

        // added nav_sub entries to variable XmlNavSub
        self::$XmlNavSub = $xmlPluginXml->contenido->nav_sub;

        // check for CONTENIDO navigation entries
        $contenidoNav = $this->_getCONTENIDONavigation();

        if ($contenidoNav != "") { // CONTENIDO navigation entry founded
            return $this->_getPluginNavigation($contenidoNav);
        } else { // No CONTENIDO navigation entry founded

            // Check for plugin navigation entry
            $pluginNav = $this->_checkAndGetPluginNavigation();

            if ($pluginNav != "") { // Plugin navigation entry founded
                return $this->_getPluginNavigation($pluginNav);
            } else { // No navigation entries founded
                return i18n('No navigation configuration founded', 'pim');
            }
        }
    }

    /**
     * Get founded CONTENIDO navigation entries
     *
     * @access private
     * @return boolean
     */
    private function _getCONTENIDONavigation() {
        $cfg = cRegistry::getConfig();

        // path to CONTENIDO navigation xml file
        $contenidoLanguageFileLang = $cfg['path']['contenido'] . 'xml/' . self::CONTENIDO_NAVIGATION_FILENAME;

        if (cFileHandler::exists($contenidoLanguageFileLang)) {

            for ($i = 0; $i < $this->_NavCount; $i++) {

                // get only navigation value (pattern)
                preg_match(self::PATTERN, self::$XmlNavSub->nav[$i], $matches);

                // get single navigation values
                $navSubEntries = explode("/", $matches[1]);

                if ($navSubEntries[0] == "navigation") { // CONTENIDO navigation
                                                         // case
                    $query = '//language/navigation/' . $navSubEntries[1] . '/main';
                } else { // No CONTENIDO navigation case
                    return false;
                }

                // load CONTENIDO navigation xml file
                $this->_DOMDocument->load($contenidoLanguageFileLang);

                // create new DOMXPath
                $xpath = new DOMXPath($this->_DOMDocument);

                // run defined query
                $entriesLang = $xpath->query($query);

                foreach ($entriesLang as $entry) {
                    return $entry->firstChild->nodeValue;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * Checks for plugin navigation entry and get navigation entries from
     * CONTENIDO navigation xml file
     *
     * @return boolean
     */
    private function _checkAndGetPluginNavigation() {
        $cfg = cRegistry::getConfig();

        // path to CONTENIDO navigation xml file
        $contenidoLanguageFileLang = $cfg['path']['contenido'] . 'xml/' . self::CONTENIDO_NAVIGATION_FILENAME;

        if (cFileHandler::exists($contenidoLanguageFileLang)) {

            for ($i = 0; $i < $this->_NavCount; $i++) {

                $this->_ApiNavMainCollection->setWhere('idnavm', cSecurity::toInteger(self::$XmlNavSub->nav[$i]->attributes()->navm));
                $this->_ApiNavMainCollection->query();

				// if no entry at nav_sub database table founded,
				// return false
				if ($this->_ApiNavMainCollection->count() == 0) {
					return false;
				}

				$row = $this->_ApiNavMainCollection->next();
				
                // define query
                $query = '//' . $row->get('location');

                // load plugin navigation xml file
                $this->_DOMDocument->load($contenidoLanguageFileLang);

                // create new DOMXPath
                $xpath = new DOMXPath($this->_DOMDocument);

                // run defined query
                $entriesLang = $xpath->query($query);

                foreach ($entriesLang as $entry) {
                    return $entry->firstChild->nodeValue;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * Get founded plugin navigation entries
     *
     * @param string $contenidoNav
     * @return string boolean
     */
    private function _getPluginNavigation($contenidoNav = "") {
        global $belang;
        $cfg = cRegistry::getConfig();

        // path to plugin specific navigation xml file with selected backend
        // language
        $pluginLanguageFileLang = $cfg['path']['contenido'] . $cfg['path']['plugins'] . $this->PluginFoldername . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . $cfg['lang'][$belang];

        if (cFileHandler::exists($pluginLanguageFileLang) && $contenidoNav != "") {

            // initializing founded variable
            $founded = "";

            for ($i = 0; $i < $this->_NavCount; $i++) {

                // get only navigation value (pattern)
                preg_match(self::PATTERN, self::$XmlNavSub->nav[$i], $matches);

                // define query
                $query = '//' . $matches[1];

                // load plugin navigation xml file
                $this->_DOMDocument->load($pluginLanguageFileLang);

                // create new DOMXPath
                $xpath = new DOMXPath($this->_DOMDocument);

                // run defined query
                $entriesLang = $xpath->query($query);

                // prevent misarrangement
                if ($entriesLang->length == 0) {
                    return false;
                }

                foreach ($entriesLang as $entry) {

                    // if we have more then one navigation entry, define
                    // menuname for other entries
                    if (self::$XmlNavSub->nav[$i]->attributes()->level == 0 && $this->_NavCount > 1) {
                        $menuName = $entry->nodeValue;
                        continue;
                    }

                    $founded[] = i18n('You find this plugin at navigation section', 'pim') . " &quot;{$contenidoNav}&quot; " . i18n('as', 'pim') . (($menuName != "")? ' &quot;' . $menuName . '&quot; ->' : '') . " &quot;{$entry->nodeValue}&quot;<br />";
                }
            }

            // prevent double entries
            $founded = array_unique($founded);

            // initializing output variable
            $output = "";

            // convert founded array to an string
            foreach ($founded as $entry) {
                $output .= $entry;
            }

            return $output;
        } else {
            return false;
        }
    }

}
?>