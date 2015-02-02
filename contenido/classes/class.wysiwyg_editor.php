<?php
/**
 * This file contains the abstract WYSIWYG editor class.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Base class for all WYSIWYG editors
 *
 * @package Core
 * @subpackage Backend
 */
abstract class cWYSIWYGEditor {
    /**
     * 
     * @var string
     */
    protected static $_sConfigPrefix = '[\'wysiwyg\']';

    /**
     *
     * @var string
     */
    protected $_sPath;

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
     *
     * @param string $sEditorName
     * @param string $sEditorContent
     */
    public function __construct($sEditorName, $sEditorContent) {
        $cfg = cRegistry::getConfig();

        $this->_sPath = $cfg['path']['all_wysiwyg_html'];
        $this->_setEditorName($sEditorName);
        $this->_setEditorContent($sEditorContent);
    }

    /**
     *
     * @param string $sContent
     */
    protected function _setEditorContent($sEditorContent) {
        $this->_sEditorContent = $sEditorContent;
    }

    /**
     *
     * @param string $sEditor
     */
    protected function _setEditor($sEditor) {
        global $cfg;

        if (is_dir($cfg['path']['all_wysiwyg'] . $sEditor)) {
            if (substr($sEditor, strlen($sEditor) - 1, 1) != "/") {
                $sEditor = $sEditor . "/";
            }

            $this->_sEditor = $sEditor;
        }
    }

    /**
     * Sets given setting if setting was not yet defined.
     * Overwriting defined setting can be achieved with $bForceSetting = true.
     *
     * @param string $sKey of setting to set
     * @param string $sValue of setting to set
     * @param bool $bForceSetting to overwrite defined setting
     */
    protected function _setSetting($sKey, $sValue, $bForceSetting = false) {
        if ($bForceSetting || !array_key_exists($sKey, $this->_aSettings)) {
            $this->_aSettings[$sKey] = $sValue;
        }
    }

    /**
     *
     * @param string $sKey
     */
    protected function _unsetSetting($sKey) {
        unset($this->_aSettings[$sKey]);
    }

    /**
     *
     * @return string
     */
    protected function _getEditorPath() {
        return $this->_sPath . $this->_sEditor;
    }

    /**
     *
     * @param string $sEditorName
     */
    protected function _setEditorName($sEditorName) {
        $this->_sEditorName = $sEditorName;
    }

    /**
     *
     * @throws cBadMethodCallException if this method is not overridden in the
     *         subclass
     */
    protected function _getScripts() {
        throw new cBadMethodCallException('You need to override the method _getScripts');
    }

    /**
     *
     * @throws cBadMethodCallException if this method is not overridden in the
     *         subclass
     */
    protected function _getEditor() {
        throw new cBadMethodCallException('You need to override the method _getEditor');
    }

    /**
     * Find out which WYSIWYG editor is currently chosen
     * @return string The name of current WYSIWYG editor
     */
    public static function getCurrentWysiwygEditorName() {
        // define fallback WYSIWYG editor
        define('DEFAULT_WYSIWYG_EDITOR', 'tinymce3');

        $curWysiwygEditor = getEffectiveSetting('wysiwyg', 'editor', constant('DEFAULT_WYSIWYG_EDITOR'));

        // no paths are allowed in WYSIWYG editor
        // fall back to defaults if editor folder does not exist
        if (0 === strlen($curWysiwygEditor)
        || false === cFileHandler::exists(cRegistry::getConfigValue('path', 'all_wysiwyg') . $curWysiwygEditor)
        || false !== strpos($curWysiwygEditor, '.')
        || false !== strpos($curWysiwygEditor, '/')
        || false !== strpos($curWysiwygEditor, '\\')) {
            $curWysiwygEditor = constant('DEFAULT_WYSIWYG_EDITOR');
        }

        return $curWysiwygEditor;
    }

    /**
     * Saves configuration of WYSIWYG editor into a file
     * This function does not validate input! This has to be done by classes that extend cWYSIWYGEditor
     * because this class does not know what each WYSIWYG editor expects.
     * @param array Array with configuration values for the current WYSIWYG editor to save
     * @return array Array with values that were not accepted
     */
    public static function safeConfig($config) {
        $erroneousSettings = array();

        // specify filename scheme
        // for tinymce 4 this will be config.wysiwyg_tinymce4.php
        $configFile = 'config.wysiwyg_' . static::getCurrentWysiwygEditorName() . '.php';

        // get path to current config folder
        $configPath = cRegistry::getConfigValue('path', 'contenido_config');

        // write content to file as array in $cfg['tinymce4']
        $filePrefix = '<?php ' . PHP_EOL;
        $filePrefix .= "defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');\n";
        $filePrefix .= 'global $cfg;' . PHP_EOL . PHP_EOL;

        $content = $filePrefix . '$cfg' . static::$_sConfigPrefix . ' = ' . var_export($config, true) . ';' . PHP_EOL;

        // first try to write then check what went wrong in case of error
        if (true !== cFileHandler::write($configPath . $configFile, $content)) {
            // just pass back that the file could not be written
            $erroneusSettings['saving'] = array('config_file' => 'wysiwyg config file could not be written');
            // write more detailed information with sensitive information such as full path into error log
            error_log('Error writing ' . $configPath . $configFile);
            return $erroneusSettings;
        }

        return $erroneousSettings;
    }
}
