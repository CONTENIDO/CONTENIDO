<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Base class for all WYSIWYG editors
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package CONTENIDO Backend Classes
 * @version 1.0.6
 * @author Timo A. Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release <= 4.6
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}
class cWYSIWYGEditor {

    protected $_sPath;

    protected $_sEditor;

    protected $_sEditorName;

    protected $_sEditorContent;

    protected $_aSettings;

    public function __construct($sEditorName, $sEditorContent) {
        $cfg = cRegistry::getConfig();

        $this->_sPath = $cfg['path']['all_wysiwyg_html'];
        $this->_setEditorName($sEditorName);
        $this->_setEditorContent($sEditorContent);
    }

    protected function _setEditorContent($sContent) {
        $this->_sEditorContent = $sContent;
    }

    protected function _setEditor($sEditor) {
        global $cfg;

        if (is_dir($cfg['path']['all_wysiwyg'] . $sEditor)) {
            if (substr($sEditor, strlen($sEditor) - 1, 1) != "/") {
                $sEditor = $sEditor . "/";
            }

            $this->_sEditor = $sEditor;
        }
    }

    protected function _setSetting($sKey, $sValue, $bForceSetting = false) {
        if ($bForceSetting) {
            $this->_aSettings[$sKey] = $sValue;
        } else if (!array_key_exists($sKey, $this->_aSettings)) {
            $this->_aSettings[$sKey] = $sValue;
        }
    }

    protected function _unsetSetting($sKey) {
        unset($this->_aSettings[$sKey]);
    }

    protected function _getEditorPath() {
        return ($this->_sPath . $this->_sEditor);
    }

    protected function _setEditorName($sEditorName) {
        $this->_sEditorName = $sEditorName;
    }

    /**
     * @throws cBadMethodCallException if this method is not overridden in the subclass
     */
    protected function _getScripts() {
        throw new cBadMethodCallException('You need to override the method _getScripts');
    }

    /**
     * @throws cBadMethodCallException if this method is not overridden in the subclass
     */
    protected function _getEditor() {
        throw new cBadMethodCallException('You need to override the method _getEditor');
    }

}
