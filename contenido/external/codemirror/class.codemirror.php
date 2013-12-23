<?php
/**
 * This file defines the CodeMirror editor integration class.
 *
 * @package    Core
 * @subpackage Backend
 * @version    SVN Revision $Rev:$
 *
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class for handling and displaying CodeMirror
 *
 * @package    Core
 * @subpackage Backend
 */
class CodeMirror {

    /**
     * Properties which were used to init CodeMirror
     *
     * @var array
     */
    private $_properties = array();

    /**
     * HTML-ID of textarea which is replaced by CodeMirror
     *
     * @var string
     */
    private $_textareaId = '';

    /**
     * defines if textarea is used or not (by system/client/user property)
     *
     * @var boolean
     */
    private $_activated = true;

    /**
     * defines if js-script for CodeMirror is included on rendering process
     *
     * @var boolean
     */
    private $_addScript = true;

    /**
     * The CONTENIDO configuration array
     *
     * @var array
     */
    private $_cfg = array();

    /**
     * Language of CodeMirror
     *
     * @var string
     */
    private $_language = '';

    /**
     * Syntax of CodeMirror
     *
     * @var string
     */
    private $_syntax = '';

    /**
     * Constructor of CodeMirror initializes class variables
     *
     * @param string $id - The id of textarea which is replaced by editor
     * @param string $syntax - Name of syntax highlighting which is used (html,
     *        css, js, php, ...)
     * @param string $lang - lang which is used into editor. Notice NOT
     *        CONTENIDO language id
     *        ex: de, en ... To get it from CONTENIDO language use:
     *        substr(strtolower($belang), 0, 2) in backend
     * @param bool $addScript - defines if CodeMirror script is included or
     *        not
     *        interesting when there is more than only one editor on page
     * @param array $cfg - The CONTENIDO configuration array
     * @param bool $editable - Optional defines if content is editable or not
     */
    public function __construct($id, $syntax, $lang, $addScript, $cfg, $editable = true) {
        // init class variables
        $this->_properties = array();
        $this->_cfg = (array) $cfg;
        $this->_addScript = (boolean) $addScript;
        $this->_textareaId = (string) $id;
        $this->_activated = true;
        $this->_language = (string) $lang;
        $this->_syntax = (string) $syntax;

        // make content not editable if not allowed
        if ($editable == false) {
            $this->setProperty('readOnly', 'true', true);
        }

        $this->setProperty('lineNumbers', 'true', true);
        $this->setProperty('lineWrapping', 'true', true);
        $this->setProperty('matchBrackets', 'true', true);
        $this->setProperty('indentUnit', 4, true);
        $this->setProperty('indentWithTabs', 'true', true);
        $this->setProperty('enterMode', 'keep', false);
        $this->setProperty('tabMode', 'shift', false);

        // internal function which appends more properties to $this->setProperty
        // wich where defined
        // by user or sysadmin in systemproperties / client settings / user
        // settings ...
        $this->_getSystemProperties();
    }

    /**
     * Function gets properties from CONTENIDO for CodeMirror and stores it into
     * $this->setProperty so user is able to overwride standard settings or
     * append
     * other settings.
     * Function also checks if CodeMirror is activated or deactivated
     * by user
     */
    private function _getSystemProperties() {
        // check if editor is disabled or enabled by user/admin
        if (getEffectiveSetting('codemirror', 'activated', 'true') == 'false') {
            $this->_activated = false;
        }

        $userSettings = getEffectiveSettingsByType('codemirror');
        foreach ($userSettings as $key => $value) {
            if ($key != 'activated') {
                if ($value == 'true' || $value == 'false' || is_numeric($value)) {
                    $this->setProperty($key, $value, true);
                } else {
                    $this->setProperty($key, $value, false);
                }
            }
        }
    }

    /**
     * Function for setting a property for CodeMirror to $this->setProperty
     * existing properties were overwritten
     *
     * @param string $name - Name of CodeMirror property
     * @param string $value - Value of CodeMirror property
     * @param bool $isNumeric - Defines if value is numeric or not
     *        in case of a numeric value, there is no need to use
     *        quotes
     */
    public function setProperty($name, $value, $isNumeric = false) {
        // datatype check
        $name = (string) $name;
        $value = (string) $value;
        $isNumeric = (boolean) $isNumeric;

        // generate a new array for new property
        $record = array();
        $record['name'] = $name;
        $record['value'] = $value;
        $record['is_numeric'] = $isNumeric;

        // append it to class variable $this->aProperties
        // when key already exists, overwride it
        $this->_properties[$name] = $record;
    }

    private function _getSyntaxScripts() {
        $path = $this->_cfg['path']['contenido_fullhtml'] . 'external/codemirror';

        $js = '';
        $jsTemplate = '<script type="text/javascript" src="%s/mode/%s/%s.js"></script>';

        $modes = array();

        $syntax = $this->_syntax;
        if ($syntax == 'js' || $syntax == 'html' || $syntax == 'php') {
            $modes[] = 'javascript';
        }

        if ($syntax == 'css' || $syntax == 'html' || $syntax == 'php') {
            $modes[] = 'css';
        }

        if ($syntax == 'html' || $syntax == 'php') {
            $modes[] = 'xml';
        }

        if ($syntax == 'php') {
            $modes[] = 'php';
            $modes[] = 'clike';
        }

        if ($syntax == 'html') {
            $modes[] = 'htmlmixed';
        }

        foreach ($modes as $mode) {
            $js .= sprintf($jsTemplate, $path, $mode, $mode) . PHP_EOL;
        }

        return $js;
    }

    private function _getSyntaxName() {
        if ($this->_syntax == 'php') {
            return 'application/x-httpd-php';
        }

        if ($this->_syntax == 'html') {
            return 'text/html';
        }

        if ($this->_syntax == 'css') {
            return 'text/css';
        }

        if ($this->_syntax == 'js') {
            return 'text/javascript';
        }
    }

    /**
     * Function renders js_script for inclusion into an header of a html file
     *
     * @return string - js_script for CodeMirror
     */
    public function renderScript() {
        // if editor is disabled, there is no need to render this script
        if ($this->_activated == false) {
            return '';
        }

        // if external js file for editor should be included, do this here
        $js = '';
        if ($this->_addScript) {
            $conPath = $this->_cfg['path']['contenido_fullhtml'];
            $path = $conPath . 'external/codemirror/';

            $language = $this->_language;
            if (!file_exists($this->_cfg['path']['contenido'] . 'external/codemirror/lib/lang/' . $language . '.js')) {
                $language = 'en';
            }

            $js .= '<script type="text/javascript" src="' . $path . 'lib/lang/' . $language . '.js"></script>' . PHP_EOL;
            $js .= '<script type="text/javascript" src="' . $path . 'lib/codemirror.js"></script>' . PHP_EOL;
            $js .= '<script type="text/javascript" src="' . $path . 'lib/util/foldcode.js"></script>' . PHP_EOL;
            $js .= '<script type="text/javascript" src="' . $path . 'lib/util/dialog.js"></script>' . PHP_EOL;
            $js .= '<script type="text/javascript" src="' . $path . 'lib/util/searchcursor.js"></script>' . PHP_EOL;
            $js .= '<script type="text/javascript" src="' . $path . 'lib/util/search.js"></script>' . PHP_EOL;
            $js .= '<script type="text/javascript" src="' . $path . 'lib/contenido_integration.js"></script>' . PHP_EOL;
            $js .= $this->_getSyntaxScripts();
            $js .= '<link rel="stylesheet" href="' . $path . 'lib/codemirror.css">' . PHP_EOL;
            $js .= '<link rel="stylesheet" href="' . $path . 'lib/util/dialog.css">' . PHP_EOL;
            $js .= '<link rel="stylesheet" href="' . $path . 'lib/contenido_integration.css">' . PHP_EOL;
        }

        // define template for CodeMirror script
        $js .= <<<JS
<script type="text/javascript">
(function(Con, $) {
    $(function() {
        if (!$('#{ID}')[0]) {
            // Node is missing, nothing to initialize here...
            return;
        }
        Con.CodeMirrorHelper.init('{ID}', {
            extraKeys: {
                'F11': function() {
                    Con.CodeMirrorHelper.toggleFullscreenEditor('{ID}');
                },
                'Esc': function() {
                    Con.CodeMirrorHelper.toggleFullscreenEditor('{ID}');
                }
            }
            {PROPERTIES}
        });
    });
})(Con, Con.$);
</script>
JS;

        $this->setProperty('mode', $this->_getSyntaxName(), false);
        $this->setProperty('theme', 'default ' . $this->_textareaId, false);

        // get all stored properties and convert it in order to insert it into
        // CodeMirror js template
        $properties = '';
        foreach ($this->_properties as $property) {
            if ($property['is_numeric'] == true) {
                $properties .= ', ' . $property['name'] . ': ' . $property['value'];
            } else {
                $properties .= ', ' . $property['name'] . ': "' . $property['value'] . '"';
            }
        }

        // fill js template
        $textareaId = $this->_textareaId;
        $jsResult = str_replace('{ID}', $textareaId, $js);
        $jsResult = str_replace('{PROPERTIES}', $properties, $jsResult);

        return $jsResult;
    }

}
