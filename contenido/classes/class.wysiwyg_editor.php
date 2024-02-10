<?php

/**
 * This file contains the abstract WYSIWYG editor class.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Base class for all WYSIWYG editors
 *
 * @package    Core
 * @subpackage Backend
 */
abstract class cWYSIWYGEditor
{

    /**
     * Access key under which the wysiwyg editor settings will be stored
     * @var string
     */
    protected static $_configPrefix = '[\'wysiwyg\']';

    /**
     * Stores base url of page
     *
     * @var string
     */
    protected $_baseURL;

    /**
     * Path to wysiwyg folder in CONTENIDO backend.
     *
     * @var string
     */
    protected $_sPath;

    /**
     * URL to wysiwyg folder in CONTENIDO backend.
     *
     * @var string
     */
    protected $_sUrl;

    /**
     * URL to CONTENIDO backend.
     *
     * @var string
     */
    protected $_sBackendUrl;

    /**
     * URL to clients frontend.
     *
     * @var string
     */
    protected $_sFrontendUrl;

    /**
     *
     * @var string
     */
    protected $_sEditor;

    /**
     *
     * @var string
     */
    protected $_sEditorName;

    /**
     *
     * @var string
     */
    protected $_sEditorContent;

    /**
     *
     * @var array
     */
    protected $_aSettings;

    /**
     * Stores, if GZIP compression will be used
     *
     * @var bool
     */
    protected $_useGZIP = false;

    /**
     * Article id.
     *
     * @var int
     */
    protected $_idart;

    /**
     * Current language id.
     *
     * @var int
     */
    protected $_lang;

    /**
     * Current backend language.
     *
     * @var string
     */
    protected $_belang;

    /**
     * Current client id.
     *
     * @var int
     */
    protected $_client;

    /**
     * Constructor to create an instance of this class.
     *
     * @param string $editorName
     * @param string $editorContent
     */
    public function __construct(string $editorName, string $editorContent)
    {
        $cfg = cRegistry::getConfig();

        $this->_sPath = $cfg['path']['all_wysiwyg'];
        $this->_sUrl = $cfg['path']['all_wysiwyg_html'];
        $this->_sBackendUrl = cRegistry::getBackendUrl();
        $this->_sFrontendUrl = cRegistry::getFrontendUrl();
        $this->_lang = cSecurity::toInteger(cRegistry::getLanguageId());
        $this->_client = cSecurity::toInteger(cRegistry::getClientId());
        $this->_belang = cRegistry::getBackendLanguage();
        $this->_idart = cSecurity::toInteger(cRegistry::getArticleId());

        $this->_setEditorName($editorName);
        $this->_setEditorContent($editorContent);
    }

    /**
     *
     * @param string $sEditorContent
     */
    protected function _setEditorContent(string $sEditorContent)
    {
        $this->_sEditorContent = $sEditorContent;
    }

    /**
     *
     * @param string $sEditor
     */
    protected function _setEditor(string $sEditor)
    {
        if (is_dir($this->_sPath . $sEditor)) {
            if (cString::getPartOfString($sEditor, cString::getStringLength($sEditor) - 1, 1) != '/') {
                $sEditor = $sEditor . '/';
            }

            $this->_sEditor = $sEditor;
        }
    }

    /**
     * Sets given setting if setting was not yet defined.
     * Overwriting defined setting can be achieved with $forceSetting = true.
     *
     * @param string|null $type Normally unused (counterpart of {@see cTinyMCE4Editor::setSetting})
     * @param string $key
     *         of setting to set
     * @param string|mixed $value
     *         of setting to set
     * @param bool $forceSetting [optional]
     *         to overwrite defined setting
     */
    public function setSetting($type = null, string $key = null, $value = '', bool $forceSetting = false)
    {
        if ($key === null) {
            cWarning(__FILE__, __LINE__, 'Key can not be null');
            return;
        }
        if ($forceSetting || !array_key_exists($key, $this->_aSettings)) {
            $this->_aSettings[$key] = $value;
        }
    }

    /**
     *
     * @param string $key
     */
    protected function _unsetSetting(string $key)
    {
        unset($this->_aSettings[$key]);
    }

    /**
     * Returns the path to the editor.
     *
     * @return string
     */
    protected function _getEditorPath(): string
    {
        return $this->_sPath . $this->_sEditor;
    }

    /**
     * Returns the URL to the editor.
     *
     * @return string
     */
    protected function _getEditorUrl(): string
    {
        return $this->_sUrl . $this->_sEditor;
    }

    /**
     *
     * @param string $sEditorName
     */
    protected function _setEditorName(string $sEditorName)
    {
        $this->_sEditorName = $sEditorName;
    }

    /**
     *
     * @throws cBadMethodCallException if this method is not overridden in the
     *         subclass
     */
    protected function getScripts(): string
    {
        throw new cBadMethodCallException('You need to override the method _getScripts');
    }

    /**
     *
     * @throws cBadMethodCallException if this method is not overridden in the
     *         subclass
     */
    protected function getEditor(): string
    {
        throw new cBadMethodCallException('You need to override the method _getEditor');
    }

    /**
     * Convert formats
     *
     * @param string $input
     * @return string
     */
    public function convertFormat(string $input): string
    {
        $aFormatCodes = [
            'y' => '%y',
            'Y' => '%Y',
            'd' => '%d',
            'm' => '%m',
            'H' => '%H',
            'h' => '%I',
            'i' => '%M',
            's' => '%S',
            'a' => '%P',
            'A' => '%P',
        ];

        foreach ($aFormatCodes as $sFormatCode => $sReplacement) {
            $input = str_replace($sFormatCode, $sReplacement, $input);
        }

        return $input;
    }

    /**
     * Set if editor should be loaded using tinymces gzip compression
     *
     * @param bool $bEnabled
     */
    protected function setGZIPMode(bool $bEnabled)
    {
        if ($bEnabled) {
            $this->_useGZIP = true;
        } else {
            $this->_useGZIP = false;
        }
    }

    /**
     * Returns the gzip mode.
     *
     * @return boolean
     *         if editor is loaded using gzip compression
     */
    public function getGZIPMode(): bool
    {
        return $this->_useGZIP;
    }

    /**
     * Sets the base url.
     *
     * @param string $baseUrl
     */
    public function setBaseURL(string $baseUrl)
    {
        $this->_baseURL = $baseUrl;
    }

    /**
     * Function to obtain a comma separated list of plugins that are
     * tried to be loaded.
     *
     * @return string
     *        plugins the plugins
     */
    public function getPlugins(): string
    {
        return cSecurity::toString($this->_aSettings['plugins'] ?? '');
    }

    /**
     * Function to obtain a comma separated list of themes that are
     * tried to be loaded.
     *
     * @return string
     *        Returns the themes
     */
    public function getThemes(): string
    {
        return cSecurity::toString($this->_aSettings['theme'] ?? '');
    }

    /**
     * Add path before filename
     *
     * @param string $file
     * @return string
     */
    public function addPath(string $file): string
    {
        // Quick and dirty hack
        if (!preg_match('/^(http|https):\/\/((?:[a-zA-Z0-9_-]+\.?)+):?(\d*)/', $file)) {
            if (preg_match('/^\//', $file)) {
                $file = 'http://' . $_SERVER['HTTP_HOST'] . $file;
            } else {
                $file = $this->_sFrontendUrl . $file;
            }
        }

        return $file;
    }

    /**
     * Find out which WYSIWYG editor is currently chosen
     * @return string
     *         The name of current WYSIWYG editor
     */
    public static function getCurrentWysiwygEditorName(): string
    {
        // define fallback WYSIWYG editor
        if (!defined('DEFAULT_WYSIWYG_EDITOR')) {
            define('DEFAULT_WYSIWYG_EDITOR', cRegistry::getConfigValue('wysiwyg', 'editor', 'tinymce3'));
        }

        $curWysiwygEditor = getEffectiveSetting('wysiwyg', 'editor', constant('DEFAULT_WYSIWYG_EDITOR'));

        // no paths are allowed in WYSIWYG editor
        // fall back to defaults if editor folder does not exist
        if (0 === cString::getStringLength($curWysiwygEditor)
            || false === cFileHandler::exists(cRegistry::getConfigValue('path', 'all_wysiwyg') . $curWysiwygEditor)
            || false !== cString::findFirstPos($curWysiwygEditor, '.')
            || false !== cString::findFirstPos($curWysiwygEditor, '/')
            || false !== cString::findFirstPos($curWysiwygEditor, '\\')) {
            $curWysiwygEditor = constant('DEFAULT_WYSIWYG_EDITOR');
        }

        return $curWysiwygEditor;
    }

    /**
     * Saves configuration of WYSIWYG editor into a file
     * This function does not validate input! This has to be done by classes that extend cWYSIWYGEditor
     * because this class does not know what each WYSIWYG editor expects.
     *
     * @param array $config
     *         Array with configuration values for the current WYSIWYG editor to save
     *
     * @return array
     *         Array with values that were not accepted
     *
     * @throws cInvalidArgumentException
     */
    public static function saveConfig(array $config): array
    {
        // Use the global variable $cfg here, the function modifies it!
        global $cfg;

        // specify filename scheme
        // for tinymce 4 this will be config.wysiwyg_tinymce4.php
        $configFile = 'config.wysiwyg_' . static::getCurrentWysiwygEditorName() . '.php';

        // get path to current config folder
        $configPath = cRegistry::getConfigValue('path', 'contenido_config');

        // write content to file as array in $cfg['tinymce4']
        $filePrefix = '<?php ' . PHP_EOL;
        $filePrefix .= "defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');\n";
        $filePrefix .= 'global $cfg;' . PHP_EOL . PHP_EOL;

        $content = $filePrefix . '$cfg' . static::$_configPrefix . ' = ' . var_export($config, true) . ';' . PHP_EOL;

        // first try to write then check what went wrong in case of error
        if (true !== cFileHandler::write($configPath . $configFile, $content)) {
            $erroneousSettings = [];

            // just pass back that the file could not be written
            $erroneousSettings['saving'] = ['config_file' => 'wysiwyg config file could not be written'];
            // write more detailed information with sensitive information such as full path into error log
            error_log('Error writing ' . $configPath . $configFile);
            return $erroneousSettings;
        }

        // apply changes to current config
        $cfg['wysiwyg'][static::getCurrentWysiwygEditorName()] = $config;

        return [];
    }

}
