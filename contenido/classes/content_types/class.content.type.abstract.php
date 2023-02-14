<?php

/**
 * This file contains the cContentTypeAbstract class.
 *
 * @package Core
 * @subpackage ContentType
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
     *
     * @var string
     */
    const SETTINGS_TYPE_XML = 'xml';

    /**
     * Constant defining the PHP comment marker used to wrap content type code.
     *
     * @since CONTENIDO 4.10.2
     * @var string
     */
    const COMMENT_MARKER = '" . /*[CONTENT_TYPE]*/%s/*[/CONTENT_TYPE]*/ . "';

    /**
     * Constant defining the regular expression to detect wrapped content type code.
     *
     * @since CONTENIDO 4.10.2
     * @var string
     */
    const COMMENT_MARKER_PATTERN = '#(\s*).(\s*)/\*\[CONTENT_TYPE\]\*/(.*?)/\*\[/CONTENT_TYPE\]\*/(\s*).(\s*)#mis';

    /**
     * Name of the content type, e.g. 'CMS_TEASER'.
     *
     * @var string
     */
    protected $_type = '';

    /**
     * Prefix of the content type, e.g. 'teaser'.
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
     * ID of the content type, e.g. 3 if CMS_TEASER[3] is used.
     *
     * @var int
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
     * @var int
     */
    protected $_idArtLang;

    /**
     * idart of corresponding article
     *
     * @var int
     */
    protected $_idArt;

    /**
     * idcat of corresponding article
     *
     * @var int
     */
    protected $_idCat;

    /**
     * CONTENIDO client id
     *
     * @var int
     */
    protected $_client;

    /**
     * CONTENIDO language id
     *
     * @var int
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
     * @var bool
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
    protected $_rawSettings = [];

    /**
     * The parsed settings.
     *
     * @var array|string
     */
    protected $_settings = [];

    /**
     * List of form field names which are used by this content type!
     *
     * @var array
     */
    protected $_formFields = [];

    /**
     * Constructor to create an instance of this class.
     *
     * Initialises class attributes with values from cRegistry.
     *
     * @param string $rawSettings
     *         the raw settings in an XML structure or as plaintext
     * @param int $id
     *         ID of the content type, e.g. 3 if CMS_TEASER[3] is used
     * @param array $contentTypes
     *         array containing the values of all content types
     */
    public function __construct($rawSettings, $id, array $contentTypes) {
        // set props
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
        $this->_uploadPath = $this->_cfgClient[$this->_client]['upl']['path'] ?? '';

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
     * Wraps generated PHP code by content types with an anonymous function,
     * which will be executed immediately and will return the output as a
     * string, so it can be used to replace container placeholder in module
     * codes. Surrounds the wrapped code also with special PHP comment
     * marker like:
     * <pre>
     * / *[CONTENT_TYPE]* / . ' . $code . ' . / *[/CONTENT_TYPE]* /
     * </pre>
     * This has no effect to the
     *
     * Container placeholder in module outputs are defined as follows:
     * <pre>
     * echo "CMS_VALUE[123]";
     * // or
     * $myVariable = "CMS_VALUE[123]";
     * </pre>
     * The result of this function will be used to replace container
     * placeholder of a content type. The result will look like
     * <pre>
     * echo "" . (function(){ return $output; })() . "";
     * // or
     * $myVariable = "" . (function(){ return $output; })() . "";
     * </pre>
     *
     * @since CONTENIDO 4.10.2
     * @param string $code Content type PHP code.
     * @return string Content type PHP code wrapped with PHP comment marker
     *                and an immediately executed anonymous function.
     * @throws cInvalidArgumentException
     */
    protected function _wrapPhpViewCode($code) {
        $code = trim($code);

        /* Content type PHP code must start with an opening PHP tag `<?php`
         * and must end with a closing PHP tag `?>` as a convention. */
        if (
            cString::getPartOfString($code, 0, 5) !== '<?php'
            || cString::getPartOfString($code, -2) !== '?>'
        ) {
            throw new cInvalidArgumentException('Code should start with `<?php` and end with `?>`!');
        }

        /**
         * Wrap content type PHP code properly, so we have a result which
         * replaces a container placeholder like
         *     `echo "CMS_VALUE[123]";`
         * to something like
         *     `echo "" . (function(){ return $output; })() . "";`
         */

        // Strip starting and ending PHP tag in the code, and strip whitespace/semicolon
        // from the beginning and end of the code.
        $code = cString::getPartOfString($code, 5);
        $code = cString::getPartOfString($code, 0, -2);
        $code = trim($code, "\n;");

        // Wrap code with an immediately executed anonymous function. Buffer the
        // output of the content type code, so we can return it in the anonymous
        // function. This will enable us to use the result for a string
        // concatenation, thus as a replacement for content types.
        $code = "(function(){ ob_start(); $code \$output = ob_get_contents(); ob_end_clean(); return \$output; })()";

        return sprintf(self::COMMENT_MARKER, $code);
    }

    /**
     * Checks if the passed code is a wrapped content type PHP code.
     *
     * @since CONTENIDO 4.10.2
     * @param $code
     * @return false|int|null
     */
    public static function isWrappedContentTypeCodePhp($code) {
        return preg_match_all(self::COMMENT_MARKER_PATTERN, $code);
    }

    /**
     * @return array|string
     * @deprecated Since 4.10.2, use cContentTypeAbstract->getSettings() instead
     */
    public function getConfiguration() {
        cDeprecated("The function cContentTypeAbstract->includePlugins() is deprecated since CONTENIDO 4.10.2, use cContentTypeAbstract->getSettings() instead.");
        return $this->getSettings();
    }

    /**
     * Function returns current content type configuration as array
     *
     * @return array|string
     */
    public function getSettings() {
        return $this->_settings;
    }

    /**
     * Function returns current content type configuration item by its name
     *
     * @since CONTENIDO 4.10.2
     * @param string $key Configuration item key
     * @param mixed $default Default value to return
     * @return string|mixed
     */
    public function getSetting($key, $default = '') {
        return $this->_settings[$key] ?? $default;
    }

    /**
     * Checks wether the content type configuration exists
     *
     * @since CONTENIDO 4.10.2
     * @param string $key Configuration item key
     * @return string|mixed|null
     */
    public function hasSetting($key) {
        return isset($this->_settings[$key]);
    }

    /**
     * Sets current content type configuration item by its name and value
     *
     * @since CONTENIDO 4.10.2
     * @param string $key Configuration item key
     * @param mixed $value Value to set
     */
    public function setSetting($key, $value) {
        $this->_settings[$key] = $value;
    }

    /**
     * Stores all values from the $_POST array in the $_settings attribute
     * (associative array) and saves them in the database (XML).
     *
     * @throws cDbException
     */
    protected function _storeSettings() {
        $settingsToStore = '';
        if ($this->_settingsType === self::SETTINGS_TYPE_XML) {
            // if the settings should be stored as XML, process them accordingly
            $settings = [];
            // update the values in the settings array with the values from the
            // $_POST array
            foreach ($this->_formFields as $key) {
                $keyWithoutPrefix = str_replace($this->_prefix . '_', '', $key);
                if (isset($_POST[$key])) {
                    $this->setSetting($key, $_POST[$key]);
                } elseif (isset($_POST[$this->_prefix . '_array_' . $keyWithoutPrefix])) {
                    // key is of type prefix_array_field, so interpret value as an array
                    $value = explode(',', $_POST[$this->_prefix . '_array_' . $keyWithoutPrefix]);
                    $this->setSetting($key, $value);
                }
                $settings[$keyWithoutPrefix] = $this->getSetting($key);
            }
            $xml = cXmlBase::arrayToXml($settings, NULL, $this->_prefix);
            $settingsToStore = $xml->asXML();
        } else {
            $settingsToStore = $this->getSettings();
        }

        // store new settings in the database
        conSaveContentEntry($this->_idArtLang, $this->_type, $this->_id, $settingsToStore);

		$oArtLang = new cApiArticleLanguage($this->_idArtLang);
		$this->_rawSettings = $oArtLang->getContent($this->_type, $this->_id);
        $this->_readSettings();
    }

    /**
     * Since the content type code is evaled by php, the code has to be encoded.
     *
     * @param string $code
     *         code to encode
     * @return string
     *         encoded code
     */
    protected function _encodeForOutput($code) {
        if (is_string($code)) {
            $code = addslashes($code);
            $code = str_replace("\\'", "'", $code);
            $code = str_replace('$', '\\$', $code);
        }
        return $code;
    }

    /**
     * Builds an array with directory information from the given upload path.
     *
     * @SuppressWarnings docBlocks
     * @param string $uploadPath [optional]
     *         path to upload directory
     *         (default: root upload path of client)
     * @return array
     *         with directory information (keys: name, path, sub)
     */
    public function buildDirectoryList($uploadPath = '') {
        // make sure the upload path is set and ends with a slash
        if ($uploadPath === '') {
            $uploadPath = $this->_uploadPath;
        }
        if (cString::getPartOfString($uploadPath, -1) !== '/') {
            $uploadPath .= '/';
        }

        $directories = [];

        if (cDirHandler::exists($uploadPath)) {
            if (false !== ($handle = cDirHandler::read($uploadPath, false, true))) {
                foreach ($handle as $entry) {
                    if (cFileHandler::fileNameBeginsWithDot($entry) === false
                        && cDirHandler::exists($uploadPath . $entry)) {
                        $directory = [];
                        $directory['name'] = $entry;
                        $directory['path'] = str_replace($this->_uploadPath, '', $uploadPath);
                        $directory['sub'] = $this->buildDirectoryList($uploadPath . $entry);
                        $directories[] = $directory;
                    }
                }
            }
        }

        usort($directories, function($a, $b) {
            $a = cString::toLowerCase($a["name"]);
            $b = cString::toLowerCase($b["name"]);
            if ($a < $b) {
                return -1;
            } elseif ($a > $b) {
                return 1;
            } else {
                return 0;
            }
        });

        return $directories;
    }

    /**
     * Generates a directory list from the given directory information (which is
     * typically built by {@link cContentTypeAbstract::buildDirectoryList}).
     *
     * @param array $dirs
     *         directory information
     *
     * @return string
     *         HTML code showing a directory list
     * @throws cInvalidArgumentException
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

            $liClasses = [];
            // check if the directory should be shown expanded or collapsed
            if ($this->_shouldDirectoryBeExpanded($dirData)) {
                $template->set('d', 'SUBDIRLIST', $this->generateDirectoryList($dirData['sub']));
            } elseif (isset($dirData['sub']) && count($dirData['sub']) > 0) {
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

        return $template->generate(
            $this->_cfg['path']['contenido'] . 'templates/standard/template.cms_filelist_dirlistitem.html',
            true
        );
    }

    /**
     * Checks whether the directory defined by the given directory
     * information is the currently active directory.
     * Overwrite in subclasses if you use generateDirectoryList!
     *
     * @param array $dirData
     *         directory information
     * @return bool
     *         whether the directory is the currently active directory
     */
    protected function _isActiveDirectory(array $dirData) {
        return false;
    }

    /**
     * Checks whether the directory defined by the given directory information
     * should be shown expanded.
     * Overwrite in subclasses if you use getDirectoryList!
     *
     * @param array $dirData
     *         directory information
     * @return bool
     *         whether the directory should be shown expanded
     */
    protected function _shouldDirectoryBeExpanded(array $dirData) {
        return false;
    }

    /**
     * Checks whether the given $subDir is a subdirectory of the given $dir.
     *
     * @param string $subDir
     *         the potential subdirectory
     * @param string $dir
     *         the parent directory
     * @return bool
     *         whether the given $subDir is a subdirectory of $dir
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
     * This functions able to use a content type object directly for output
     * See also CON-2587
     *
     * @return string
     */
    public function __toString() {
        return $this->generateViewCode();
    }

    /**
     * Generates the code which should be shown if this content type is shown in
     * the frontend.
     *
     * @return string
     *         escaped HTML code which sould be shown if content type is shown in frontend
     */
    public abstract function generateViewCode();

    /**
     * Generates the code which should be shown if this content type is edited.
     *
     * @return string
     *         escaped HTML code which should be shown if content type is edited
     */
    public abstract function generateEditCode();

    /**
     * Checks if this content type can be edited by a WYSIWYG editor
     *
     * @return bool
     */
    public function isWysiwygCompatible() {
        return false;
    }

}
