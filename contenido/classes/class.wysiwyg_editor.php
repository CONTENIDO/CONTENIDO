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
}
