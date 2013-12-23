<?php
/**
 * This file contains the cContentTypeAbstract class.
 *
 * @package Core
 * @subpackage ContentType
 * @version SVN Revision $Rev:$
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Abstract content type from which every content type should inherit.
 *
 * @package Core
 * @subpackage ContentType
 */
abstract class cContentTypeAbstract {

    /**
     * Constant defining that the settings should be interpreted as plaintext.
     *
     * @var string
     */
    const SETTINGS_TYPE_PLAINTEXT = 'plaintext';

    /**
     * Constant defining that the settings should be interpreted as XML.
     * @var string
     */
    const SETTINGS_TYPE_XML = 'xml';

    /**
     * Name of the content type, e.g.
     * 'CMS_TEASER'.
     *
     * @var string
     */
    protected $_type = '';

    /**
     * Prefix of the content type, e.g.
     * 'teaser'.
     *
     * @var string
     */
    protected $_prefix = 'abstract';

    /**
     * Whether the settings should be interpreted as plaintext or XML.
     *
     * @var string
     */
    protected $_settingsType = self::SETTINGS_TYPE_PLAINTEXT;

    /**
     * ID of the content type, e.g.
     * 3 if CMS_TEASER[3] is used.
     *
     * @var integer
     */
    protected $_id;

    /**
     * Array containing the values of all content types.
     *
     * @var array
     */
    protected $_contentTypes;

    /**
     * CONTENIDO configuration array
     *
     * @var array
     */
    protected $_cfg;

    /**
     * idartlang of corresponding article
     *
     * @var integer
     */
    protected $_idArtLang;

    /**
     * idart of corresponding article
     *
     * @var integer
     */
    protected $_idArt;

    /**
     * idcat of corresponding article
     *
     * @var integer
     */
    protected $_idCat;

    /**
     * CONTENIDO client id
     *
     * @var integer
     */
    protected $_client;

    /**
     * CONTENIDO language id
     *
     * @var integer
     */
    protected $_lang;

    /**
     * CONTENIDO session object
     *
     * @var cSession
     */
    protected $_session;

    /**
     * CONTENIDO configuration array for currently active client
     *
     * @var array
     */
    protected $_cfgClient;

    /**
     * Whether to generate XHTML
     *
     * @var boolean
     */
    protected $_useXHTML;

    /**
     * The path to the upload directory.
     *
     * @var string
     */
    protected $_uploadPath;

    /**
     * The raw settings from the DB.
     *
     * @var string
     */
    protected $_rawSettings = array();

    /**
     * The parsed settings.
     *
     * @var array string
     */
    protected $_settings = array();

    /**
     * List of form field names which are used by this content type!
     *
     * @var array
     */
    protected $_formFields = array();

    /**
     * Initialises class attributes with values from cRegistry.
     *
     * @param string $rawSettings the raw settings in an XML structure or as
     *        plaintext
     * @param int $id ID of the content type, e.g. 3 if CMS_TEASER[3] is
     *        used
     * @param array $contentTypes array containing the values of all content
     *        types
     */
    public function __construct($rawSettings, $id, array $contentTypes) {
        $this->_rawSettings = $rawSettings;
        $this->_id = $id;
        $this->_contentTypes = $contentTypes;

        $this->_idArtLang = cRegistry::getArticleLanguageId();
        $this->_idArt = cRegistry::getArticleId();
        $this->_idCat = cRegistry::getCategoryId();
        $this->_cfg = cRegistry::getConfig();
        $this->_client = cRegistry::getClientId();
        $this->_lang = cRegistry::getLanguageId();
        $this->_cfgClient = cRegistry::getClientConfig();
        $this->_session = cRegistry::getSession();
        $this->_useXHTML = cSecurity::toBoolean(getEffectiveSetting('generator', 'xhtml', 'false'));
        $this->_uploadPath = $this->_cfgClient[$this->_client]['upl']['path'];

        $this->_readSettings();
    }

    /**
     * Reads all settings from the $_rawSettings attribute (XML or plaintext)
     * and stores them in the $_settings attribute (associative array or
     * plaintext).
     */
    protected function _readSettings() {
        // if no settings have been given, do nothing
        if (empty($this->_rawSettings)) {
            return;
        }
        if ($this->_settingsType === self::SETTINGS_TYPE_XML) {
            // if the settings should be interpreted as XML, process them
            // accordingly
            $this->_settings = cXmlBase::xmlStringToArray($this->_rawSettings);
            // add the prefix to the settings array keys
            foreach ($this->_settings as $key => $value) {
                $this->_settings[$this->_prefix . '_' . $key] = $value;
                unset($this->_settings[$key]);
            }
        } else {
            // otherwise do not process the raw setting
            $this->_settings = $this->_rawSettings;
        }
    }

    /**
     * Function returns curren content type configuration as array
     *
     * @return array
     */
    public function getConfiguration() {
        return $this->_settings;
    }

    /**
     * Stores all values from the $_POST array in the $_settings attribute
     * (associative array) and saves them in the database (XML).
     */
    protected function _storeSettings() {
        $settingsToStore = '';
        if ($this->_settingsType === self::SETTINGS_TYPE_XML) {
            // if the settings should be stored as XML, process them accordingly
            $settings = array();
            // update the values in the settings array with the values from the
            // $_POST array
            foreach ($this->_formFields as $key) {
                $keyWithoutPrefix = str_replace($this->_prefix . '_', '', $key);
                if (isset($_POST[$key])) {
                    $this->_settings[$key] = $_POST[$key];
                } else if (isset($_POST[$this->_prefix . '_array_' . $keyWithoutPrefix])) {
                    // key is of type prefix_array_field, so interpret value as
                    // an array
                    $this->_settings[$key] = explode(',', $_POST[$this->_prefix . '_array_' . $keyWithoutPrefix]);
                }
                $settings[$keyWithoutPrefix] = $this->_settings[$key];
            }
            $xml = cXmlBase::arrayToXml($settings, NULL, $this->_prefix);
            $settingsToStore = $xml->asXML();
        } else {
            $settingsToStore = $this->_settings;
        }
        // store new settings in the database
        conSaveContentEntry($this->_idArtLang, $this->_type, $this->_id, $settingsToStore);
    }

    /**
     * Since the content type code is evaled by php, the code has to be encoded.
     *
     * @param string $code code to encode
     * @return string encoded code
     */
    protected function _encodeForOutput($code) {
        $code = addslashes($code);
        $code = str_replace("\\'", "'", $code);
        $code = str_replace('\$', '\\$', $code);

        return $code;
    }

    /**
     * Builds an array with directory information from the given upload path.
     *
     * @param string $uploadPath path to upload directory (optional, default:
     *        root upload path
     *        of client)
     * @return array with directory information (keys: name, path, sub)
     */
    public function buildDirectoryList($uploadPath = '') {
        // make sure the upload path is set and ends with a slash
        if ($uploadPath === '') {
            $uploadPath = $this->_uploadPath;
        }
        if (substr($uploadPath, -1) !== '/') {
            $uploadPath .= '/';
        }

        $directories = array();

        if (is_dir($uploadPath)) {
            if ($handle = opendir($uploadPath)) {
                while (($entry = readdir($handle)) !== false) {
                    // ignore .svn directories as well as links to upper dirs
                    if ($entry != '.svn' && $entry != '.' && $entry != '..' && is_dir($uploadPath . $entry)) {
                        $directory = array();
                        $directory['name'] = $entry;
                        $directory['path'] = str_replace($this->_uploadPath, '', $uploadPath);
                        $directory['sub'] = $this->buildDirectoryList($uploadPath . $entry);
                        $directories[] = $directory;
                    }
                }
            }
            closedir($handle);
        }
        return $directories;
    }

    /**
     * Generates a directory list from the given directory information (which is
     * typically built by {@link cContentTypeAbstract::buildDirectoryList}).
     *
     * @param array $dirs directory information
     * @return string HTML code showing a directory list
     */
    public function generateDirectoryList(array $dirs) {
        $template = new cTemplate();
        $i = 1;

        foreach ($dirs as $dirData) {
            // set the active class if this is the chosen directory
            $divClass = ($this->_isActiveDirectory($dirData)) ? 'active' : '';
            $template->set('d', 'DIVCLASS', $divClass);

            $template->set('d', 'TITLE', $dirData['path'] . $dirData['name']);
            $template->set('d', 'DIRNAME', $dirData['name']);

            $liClasses = array();
            // check if the directory should be shown expanded or collapsed
            if ($this->_shouldDirectoryBeExpanded($dirData)) {
                $template->set('d', 'SUBDIRLIST', $this->generateDirectoryList($dirData['sub']));
            } else if (isset($dirData['sub']) && count($dirData['sub']) > 0) {
                $liClasses[] = 'collapsed';
                $template->set('d', 'SUBDIRLIST', '');
            } else {
                $template->set('d', 'SUBDIRLIST', '');
            }
            if ($i === count($dirs)) {
                $liClasses[] = 'last';
            }
            $template->set('d', 'LICLASS', implode(' ', $liClasses));

            $i++;
            $template->next();
        }

        return $template->generate($this->_cfg['path']['contenido'] . 'templates/standard/template.cms_filelist_dirlistitem.html', true);
    }

    /**
     * Checks whether the directory defined by the given directory
     * information is the currently active directory.
     * Overwrite in subclasses if you use generateDirectoryList!
     *
     * @param array $dirData directory information
     * @return boolean whether the directory is the currently active directory
     */
    protected function _isActiveDirectory(array $dirData) {
        return false;
    }

    /**
     * Checks whether the directory defined by the given directory information
     * should be shown expanded.
     * Overwrite in subclasses if you use getDirectoryList!
     *
     * @param array $dirData directory information
     * @return boolean whether the directory should be shown expanded
     */
    protected function _shouldDirectoryBeExpanded(array $dirData) {
        return false;
    }

    /**
     * Checks whether the given $subDir is a subdirectory of the given $dir.
     *
     * @param string $subDir the potential subdirectory
     * @param string $dir the parent directory
     * @return boolean whether the given $subDir is a subdirectory of $dir
     */
    protected function _isSubdirectory($subDir, $dir) {
        $dirArray = explode('/', $dir);
        $expand = false;
        $checkDir = '';

        // construct the whole directory in single steps and check if the given
        // directory can be found
        foreach ($dirArray as $dirPart) {
            $checkDir .= '/' . $dirPart;
            if ($checkDir === '/' . $subDir) {
                $expand = true;
            }
        }

        return $expand;
    }

    /**
     * Generates the code which should be shown if this content type is shown in
     * the frontend.
     *
     * @return string escaped HTML code which sould be shown if content type is
     *         shown in frontend
     */
    public abstract function generateViewCode();

    /**
     * Generates the code which should be shown if this content type is edited.
     *
     * @return string escaped HTML code which should be shown if content type is
     *         edited
     */
    public abstract function generateEditCode();

}