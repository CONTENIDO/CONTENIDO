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
class PimPluginViewNavSub
{
    /**
     * Pattern for navigation (nav_sub) XML entries
     */
    public const PATTERN = '/;(.+)$/';
    /**
     * Filename of the XML configuration file for CONTENIDO navigation
     */
    public const CONTENIDO_NAVIGATION_FILENAME = 'navigation.xml';

    /**
     * @var string
     */
    private $pluginFoldername;

    /**
     * @var SimpleXMLElement CONTENIDO sub navigations: *_nav_sub
     */
    public static $xmlNavSub;

    /**
     * @var int Variable for counted nav entries
     */
    protected $navCount = 0;

    /**
     * @var string Variable for the filepath to CONTENIDO base navigation.xml
     */
    protected $navigationXmlPath = '';

    /**
     * @var string Variable for subnavigation name
     */
    protected $subNav;

    /**
     * @var DOMDocument Class variable for DOMDocument
     */
    protected $domDocument;

    /**
     * @var cApiNavMainCollection Class variable for cApiNavMainCollection
     */
    protected $apiNavMainCollection;

    /**
     * Class variable for cApiNavSubCollection
     *
     * @var cApiNavSubCollection
     */
    protected $apiNavSubCollection;

    /**
     * Initializing and set variable for DOMDocument
     */
    private function setDomDocument(DOMDocument $domDocument)
    {
        $this->domDocument = $domDocument;
    }

    /**
     * Initializing and set variable for cApiNavMainCollection
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
     * Setter for the navigationXmlPath variable.
     * (Filepath to CONTENIDO base navigation.xml)
     */
    private function setNavigationXmlPath(string $path)
    {
        $this->navigationXmlPath = $path;
    }

    /**
     * Getter for the navigationXmlPath variable.
     * (Filepath to CONTENIDO base navigation.xml)
     */
    private function getNavigationXmlPath(): string
    {
        return $this->navigationXmlPath;
    }

    /**
     * Construct function
     */
    public function __construct()
    {
        // Initializing and set classes
        $this->setDomDocument(new DOMDocument());

        // cApiClasses
        $this->setApiNavMainCollection(new cApiNavMainCollection());
        $this->setApiNavSubCollection(new cApiNavSubCollection());
    }

    // GET and SET methods for installation routine

    /**
     * Set variable for plugin foldername
     */
    public function setPluginFoldername(string $foldername)
    {
        $this->pluginFoldername = cSecurity::escapeString($foldername);
    }

    // View methods

    /**
     * Get nav_sub entries
     *
     * @return string|false
     * @throws cException
     */
    public function getNavSubentries()
    {
        // Get contents of plugin.xml file
        $dataPluginXml = file_get_contents(PimPluginHelper::getPluginConfigFile($this->pluginFoldername));

        // Load xml strings
        $xmlPluginXml = simplexml_load_string($dataPluginXml);

        // Count nav_sub entries for this plugin
        $entries = $xmlPluginXml->contenido->nav_sub->nav;
        $this->navCount = is_object($entries) ? count($entries) : 0;

        // No navigation configured, so we can stop this process
        if ($this->navCount == 0) {
            return i18n('No navigation configuration founded', 'pim');
        }

        // Added nav_sub entries to variable xmlNavSub
        self::$xmlNavSub = $xmlPluginXml->contenido->nav_sub;

        // Check for CONTENIDO navigation entries
        $contenidoNav = $this->getContenidoNavigation();
        if ($contenidoNav != '') {
            // CONTENIDO navigation entry found
            return $this->getPluginNavigation($contenidoNav);
        }

        // Check for plugin navigation entry
        $pluginNav = $this->checkAndGetPluginNavigation();
        if ($pluginNav != '') {
            // Plugin navigation entry found
            return $this->getPluginNavigation($pluginNav);
        }

        // No navigation entries found
        return i18n('No navigation configuration founded', 'pim');
    }

    /**
     * Get found CONTENIDO navigation entries
     *
     * @return string|false
     */
    private function getContenidoNavigation()
    {
        // Path to CONTENIDO navigation XML file
        $this->setNavigationXmlPath(cRegistry::getBackendPath() . 'xml/' . self::CONTENIDO_NAVIGATION_FILENAME);

        if (cFileHandler::exists($this->getNavigationXmlPath())) {
            for ($i = 0; $i < $this->navCount; $i++) {
                // Get only navigation value (pattern)
                preg_match(self::PATTERN, self::$xmlNavSub->nav[$i], $matches);

                // Get single navigation values
                $navSubEntries = explode('/', $matches[1]);

                if ($navSubEntries[0] == 'navigation') {
                    // CONTENIDO navigation case

                    // Define subnavigation name (example: navigation/content/linkchecker)
                    $this->subNav = $this->getTranslatedNavigationName('//language/navigation/' . $navSubEntries[1] . '/' . $navSubEntries[2] . '/main');

                    // Define navigation name (example: navigation/content)
                    return $this->getTranslatedNavigationName('//language/navigation/' . $navSubEntries[1] . '/main');
                } else { // No CONTENIDO navigation case
                    return false;
                }
            }
        }

        return false;
    }

    /**
     * Get translated navigation name
     *
     * @return string|false XML of translated navigation nane
     */
    private function getTranslatedNavigationName(string $query = '')
    {
        if ($query == '') {
            return false;
        }

        // Load CONTENIDO navigation XML file
        $this->domDocument->load($this->getNavigationXmlPath());

        // Create new DOMXPath
        $xpath = new DOMXPath($this->domDocument);

        // Run defined query
        $entriesLang = $xpath->query($query);

        return $entriesLang instanceof DOMNodeList && $entriesLang->length ? $entriesLang->item(0)->firstChild->nodeValue : false;
    }

    /**
     * Checks for plugin navigation entry and get navigation entries from CONTENIDO navigation XML file
     *
     * @return mixed|false
     * @throws cDbException|cException
     */
    private function checkAndGetPluginNavigation()
    {
        // Path to CONTENIDO navigation XML file
        $contenidoLanguageFileLang = cRegistry::getBackendPath() . 'xml/' . self::CONTENIDO_NAVIGATION_FILENAME;

        if (cFileHandler::exists($contenidoLanguageFileLang)) {
            for ($i = 0; $i < $this->navCount; $i++) {
                if (cSecurity::toInteger(self::$xmlNavSub->nav[$i]->attributes()->navm) > 0) {
                    $this->apiNavMainCollection->setWhere(
                        'idnavm',
                        cSecurity::toInteger(self::$xmlNavSub->nav[$i]->attributes()->navm)
                    );
                } else {
                    $this->apiNavMainCollection->setWhere(
                        'name',
                        cSecurity::escapeString(self::$xmlNavSub->nav[$i]->attributes()->navm)
                    );
                }

                $this->apiNavMainCollection->query();

                // If no entry at nav_sub database table found, return false
                if ($this->apiNavMainCollection->count() == 0) {
                    return false;
                }

                $row = $this->apiNavMainCollection->next();

                // Define query
                $query = '//' . $row->get('location');

                // Load plugin navigation xml file
                $this->domDocument->load($contenidoLanguageFileLang);

                // Create new DOMXPath
                $xpath = new DOMXPath($this->domDocument);

                // Run defined query
                $entries = $xpath->query($query);

                return $entries instanceof DOMNodeList && $entries->length ? $entries->item(0)->firstChild->nodeValue : false;
            }
        }

        return false;
    }

    /**
     * Get found plugin navigation entries
     *
     * @return string|bool
     * @throws cException
     */
    private function getPluginNavigation(string $contenidoNav = '')
    {
        $belang = cRegistry::getBackendLanguage();
        $cfg = cRegistry::getConfig();

        // Path to the plugin-specific navigation XML file with the selected backend language
        $pluginLanguageFile = PimPluginHelper::getPluginLanguageFile($this->pluginFoldername, $cfg['lang'][$belang]);

        if (cFileHandler::exists($pluginLanguageFile) && $contenidoNav != '') {
            // Initializing of the found array
            $found = [];

            for ($i = 0; $i < $this->navCount; $i++) {
                // Get only navigation value (pattern)
                preg_match(self::PATTERN, self::$xmlNavSub->nav[$i], $matches);

                // Define query
                $query = '//' . $matches[1];

                // Load plugin navigation xml file
                $this->domDocument->load($pluginLanguageFile);

                // Create new DOMXPath
                $xpath = new DOMXPath($this->domDocument);

                // Run defined query
                $entries = $xpath->query($query);

                // Prevent misarrangement
                if ($entries->length == 0) {
                    return false;
                }

                foreach ($entries as $entry) {
                    // If we have more than one navigation entry, define menu name for other entries
                    $menuName = '';
                    if (self::$xmlNavSub->nav[$i]->attributes()->level == 0 && $this->navCount > 1) {
                        $menuName = $entry->nodeValue;
                        continue;
                    } elseif (self::$xmlNavSub->nav[$i]->attributes()->level == 1 && $menuName == '') {
                        // If we have an plugin with level one and no defined menuName, use subnavigation name
                        // as menuName
                        $menuName = $this->subNav;
                    }

                    $found[] = i18n('You find this plugin at navigation section', 'pim')
                        . " &quot;$contenidoNav&quot; "
                        . i18n('as', 'pim')
                        . (($menuName != '') ? ' &quot;' . $menuName . '&quot; ->' : '')
                        . " &quot;$entry->nodeValue&quot;<br />";
                }
            }

            // Prevent double entries
            $found = array_unique($found);

            // Convert found array to a string
            return implode('', $found);
        } else {
            return false;
        }
    }

}
