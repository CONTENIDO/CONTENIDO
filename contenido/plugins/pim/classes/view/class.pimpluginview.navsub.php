<?php

/**
 * This file contains abstract class for view navsub entries
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
 * View navigation entries
 * TODO: Later implement into new PIM view design
 *
 * @package    Plugin
 * @subpackage PluginManager
 * @author     frederic.schneider
 */
class PimPluginViewNavSub {
    /**
     * Pattern for navigation (nav_sub) xml entries
     */
    const PATTERN = '/;(.+)$/';
    /**
     * Filename of Xml configuration file for plugins
     */
    const PLUGIN_CONFIG_FILENAME = "plugin.xml";
    /**
     * Filename of Xml configuration file for CONTENIDO navigation
     */
    const CONTENIDO_NAVIGATION_FILENAME = "navigation.xml";

    /**
     * @var string
     */
    private $PluginFoldername;

    /**
     * CONTENIDO sub navigations: *_nav_sub
     *
     * @var SimpleXMLElement
     */
    public static $XmlNavSub;

    /**
     * Variable for counted nav entries
     *
     * @var int
     */
    protected $_NavCount = 0;

    /**
     * Variable for filepath to CONTENIDO base navigation.xml
     *
     * @todo check variable name
     * @var string
     */
    protected $_contenidoLanguageFileLang;

    /**
     * Variable for subnavigation name
     *
     * @var string
     */
    protected $_SubNav;

    /**
     * Class variable for DOMDocument
     *
     * @var DOMDocument
     */
    protected $_DOMDocument;

    /**
     * Class variable for cApiNavMainCollection
     *
     * @var cApiNavMainCollection
     */
    protected $_ApiNavMainCollection;

    /**
     * Class variable for cApiNavSubCollection
     *
     * @var cApiNavSubCollection
     */
    protected $_ApiNavSubCollection;

    /**
     * Initializing and set variable for DOMDocument
     *
     * @return DOMDocument
     */
    private function _setDOMDocument() {
        return $this->_DOMDocument = new DOMDocument();
    }

    /**
     * Initializing and set variable for cApiNavMainCollection
     *
     * @return cApiNavMainCollection
     */
    private function _setApiNavMainCollection() {
        return $this->_ApiNavMainCollection = new cApiNavMainCollection();
    }

    /**
     * Initializing and set variable for cApiNavSubCollection
     *
     * @return cApiNavSubCollection
     */
    private function _setApiNavSubCollection() {
        return $this->_ApiNavSubCollection = new cApiNavSubCollection();
    }

    /**
     * Set contenidoLanguageFileLang variable
     * (Filepath to CONTENIDO base navigation.xml)
     *
     * @param string $path
     * @return bool
     */
    private function _setNavigationXmlPath($path) {
    	$this->_contenidoLanguageFileLang = $path;
    	return true;
    }

    /**
     * Get contenidoLanguageFileLang variable
     * (Filepath to CONTENIDO base navigation.xml)
     *
     * @return string contenigoLanguageFileLang
     */
    private function _getNavigationXmlPath() {
    	return $this->_contenidoLanguageFileLang;
	}

    /**
     * Construct function
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
     * @return string
     *
     * @throws cException
     */
    public function getNavSubentries() {

        $cfg = cRegistry::getConfig();

        // Get contents of plugin.xml file
        $dataPluginXml = file_get_contents($cfg['path']['contenido'] . $cfg['path']['plugins'] . $this->PluginFoldername . DIRECTORY_SEPARATOR . self::PLUGIN_CONFIG_FILENAME);

        // Load xml strings
        $xmlPluginXml = simplexml_load_string($dataPluginXml);

        // Count nav_sub entries for this plugin
        $entries = $xmlPluginXml->contenido->nav_sub->nav;
        $this->_NavCount = is_object($entries) ? count($entries) : 0;

        // No navigation configured, so we can stop this process
        if ($this->_NavCount == 0) {
            return i18n('No navigation configuration founded', 'pim');
        }

        // Added nav_sub entries to variable XmlNavSub
        self::$XmlNavSub = $xmlPluginXml->contenido->nav_sub;

        // Check for CONTENIDO navigation entries
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
     * @return bool
     */
    private function _getCONTENIDONavigation() {
        $cfg = cRegistry::getConfig();

        // Path to CONTENIDO navigation xml file
        $this->_setNavigationXmlPath($cfg['path']['contenido'] . 'xml/' . self::CONTENIDO_NAVIGATION_FILENAME);

        if (cFileHandler::exists($this->_getNavigationXmlPath())) {

            for ($i = 0; $i < $this->_NavCount; $i++) {

                // Get only navigation value (pattern)
                preg_match(self::PATTERN, self::$XmlNavSub->nav[$i], $matches);

                // Get single navigation values
                $navSubEntries = explode("/", $matches[1]);

                if ($navSubEntries[0] == "navigation") {
                    // CONTENIDO navigation case

                	// Define subnavigation name (example: navigation/content/linkchecker)
                    $this->_SubNav = $this->_getTranslatedNavigationName('//language/navigation/' . $navSubEntries[1] . '/' . $navSubEntries[2] . '/main');

                    // Define navigation name (example: navigation/content)
                    return $this->_getTranslatedNavigationName('//language/navigation/' . $navSubEntries[1] . '/main');
                } else { // No CONTENIDO navigation case
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    /**
     * Get translated navigation name
     *
     * @param string $query
     * @return string XML of translated navigation nane
     */
    private function _getTranslatedNavigationName($query = '') {

    	if ($query == '') {
    		return false;
    	}

    	// Load CONTENIDO navigation xml file
    	$this->_DOMDocument->load($this->_getNavigationXmlPath());

    	// Create new DOMXPath
    	$xpath = new DOMXPath($this->_DOMDocument);

    	// Run defined query
    	$entriesLang = $xpath->query($query);

    	foreach ($entriesLang as $entry) {
    		return $entry->firstChild->nodeValue;
    	}
    }

    /**
     * Checks for plugin navigation entry and get navigation entries from
     * CONTENIDO navigation xml file
     *
     * @return bool
     *
     * @throws cDbException
     * @throws cException
     */
    private function _checkAndGetPluginNavigation() {
        $cfg = cRegistry::getConfig();

        // Path to CONTENIDO navigation xml file
        $contenidoLanguageFileLang = $cfg['path']['contenido'] . 'xml/' . self::CONTENIDO_NAVIGATION_FILENAME;

        if (cFileHandler::exists($contenidoLanguageFileLang)) {

            for ($i = 0; $i < $this->_NavCount; $i++) {

                if (cSecurity::toInteger(self::$XmlNavSub->nav[$i]->attributes()->navm) > 0)  {
                    $this->_ApiNavMainCollection->setWhere('idnavm', cSecurity::toInteger(self::$XmlNavSub->nav[$i]->attributes()->navm));
                } else {
                    $this->_ApiNavMainCollection->setWhere('name', cSecurity::escapeString(self::$XmlNavSub->nav[$i]->attributes()->navm));
                }

                $this->_ApiNavMainCollection->query();

                // If no entry at nav_sub database table founded,
                // return false
                if ($this->_ApiNavMainCollection->count() == 0) {
                    return false;
                }

                $row = $this->_ApiNavMainCollection->next();

                // Define query
                $query = '//' . $row->get('location');

                // Load plugin navigation xml file
                $this->_DOMDocument->load($contenidoLanguageFileLang);

                // Create new DOMXPath
                $xpath = new DOMXPath($this->_DOMDocument);

                // Run defined query
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
     *
     * @return string|bool
     */
    private function _getPluginNavigation($contenidoNav = "") {
        $belang = cRegistry::getBackendLanguage();
        $cfg = cRegistry::getConfig();

        // Path to plugin specific navigation xml file with selected backend
        // language
        $pluginLanguageFileLang = $cfg['path']['contenido'] . $cfg['path']['plugins'] . $this->PluginFoldername . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . $cfg['lang'][$belang];

        if (cFileHandler::exists($pluginLanguageFileLang) && $contenidoNav != "") {

            // Initializing founded array
            $founded = [];

            for ($i = 0; $i < $this->_NavCount; $i++) {

                // Get only navigation value (pattern)
                preg_match(self::PATTERN, self::$XmlNavSub->nav[$i], $matches);

                // Define query
                $query = '//' . $matches[1];

                // Load plugin navigation xml file
                $this->_DOMDocument->load($pluginLanguageFileLang);

                // Create new DOMXPath
                $xpath = new DOMXPath($this->_DOMDocument);

                // Run defined query
                $entriesLang = $xpath->query($query);

                // Prevent misarrangement
                if ($entriesLang->length == 0) {
                    return false;
                }

                foreach ($entriesLang as $entry) {

                    // If we have more then one navigation entry, define
                    // menuname for other entries
                    $menuName = '';
                    if (self::$XmlNavSub->nav[$i]->attributes()->level == 0 && $this->_NavCount > 1) {
                        $menuName = $entry->nodeValue;
                        continue;
                    } elseif (self::$XmlNavSub->nav[$i]->attributes()->level == 1 && $menuName == '') {
                    	// If we have an plugin with level one and no defined menuName, use subnavigation name
                    	// as menuName
						$menuName = $this->_SubNav;
                    }

                    $founded[] = i18n('You find this plugin at navigation section', 'pim') . " &quot;{$contenidoNav}&quot; " . i18n('as', 'pim') . (($menuName != '') ? ' &quot;' . $menuName . '&quot; ->' : '') . " &quot;{$entry->nodeValue}&quot;<br />";
                }
            }

            // Prevent double entries
            $founded = array_unique($founded);

            // Initializing output variable

            // Convert founded array to a string
            $output = implode('', $founded);

            return $output;
        } else {
            return false;
        }
    }

}
