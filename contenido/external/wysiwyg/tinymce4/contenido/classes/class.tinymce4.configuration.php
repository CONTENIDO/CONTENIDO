<?php
/**
 * This file contains the system integrity backend page.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Thomas Stauer
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

class cTinymce4Configuration {
    public function __construct() {
        
    }
    
    private function _checkType($type, $value) {
        if (true === empty($value)) {
            return true;
        }
        if (true === isset($value)) {
            // parameter is k   nown, check it using type expression
            return preg_match($type, $value);
        }
        
        return false;
    }

    private function _checkIsset(array $haystack, array $needles) {
        if (count($haystack) !== count($needles)) {
            return false;
        }
        foreach ($needles as $needle) {
            if (false === isset($haystack[$needle])) {
                return false;
            }
        }

        return true;
    }

    private function _validateToolbarN($toolbarData) {
        // do not use cRequestValidator instance because it does not support multi-dimensional arrays
        if (false === $this->_checkType('/^[a-zA-Z0-9 \-\|_]*$/', $toolbarData)
                || false !== strpos($toolbarData, '||')) {
                    return false;
                }
    
                return true;
    }
    
    /**
     * 
     * @param unknown $config
     * @return multitype:string |boolean
     */
    public function validateForm($config) {
        // Checks for cross site requests and cross site scripting are omitted due to time constraints

        // remove x and y values from image submit button in in form
        unset($config['x']);
        unset($config['y']);

        // check if all array entries actually exist
        // abort if too many values are encountered
        $shouldArrayStructure =  array (
            'tinymce4_full' =>
            array (
                    'toolbar1',
                    'toolbar2',
                    'toolbar3',
                    'plugins'
            ),
            'tinymce4_fullscreen' =>
            array (
                    'toolbar1',
                    'toolbar2',
                    'toolbar3',
                    'plugins'
            ),
            'tinymce4',
            'externalplugin'
        );
        if (false === $this->_checkIsset($config['tinymce4_full'], $shouldArrayStructure['tinymce4_full'])) {
            return false;
        }
        if (false === $this->_checkIsset($config['tinymce4_fullscreen'], $shouldArrayStructure['tinymce4_fullscreen'])) {
            return false;
        }
        if (false === isset($config['tinymce4'])) {
            return false;
        }
        if (count($shouldArrayStructure) !== count($config)) {
            return false;
        }

        // do not use cRequestValidator instance because it does not support multi-dimensional arrays
        if (false === $this->_validateToolbarN($config['tinymce4_full']['toolbar1'])
        || false === $this->_validateToolbarN($config['tinymce4_full']['toolbar2'])
        || false === $this->_validateToolbarN($config['tinymce4_full']['toolbar3'])
        || false === $this->_validateToolbarN($config['tinymce4_fullscreen']['toolbar1'])
        || false === $this->_validateToolbarN($config['tinymce4_fullscreen']['toolbar2'])
        || false === $this->_validateToolbarN($config['tinymce4_fullscreen']['toolbar3'])) {
            return false;
        }

        // $config contains only valid content
        return $config;
    }

    private function _addLabelWithTextbox($description, $name) {
        $label = new cHTMLLabel($description, $name);
        $label->setStyle('padding:3px;display:block;float:left;width:' . $width . 'px;');
        
        $div = new cHTMLDiv($label .  new cHTMLTextbox($name));
        return $div;
    }
    
    public function showConfigurationForm() {
        $curWysiwygEditor = getEffectiveSetting('wysiwyg', 'editor', 'tinymce3');
        $tmpl = new cTemplate();
        
        $page = new cGuiPage('system_wysiwyg_tinymce4', '', '5');
        $auth = cRegistry::getAuth();
        //if (false === cRegistry::getPerm())
        
        $page->displayInfo(i18n('Currently active WYSIWYG editor: ' . cWYSIWYGEditor::getCurrentWysiwygEditorName()));
        $form = new cGuiTableForm('system_wysiwyg_tinymce4');
        $form->addHeader(i18n('Tinymce 4 configuration'));

        $frame = cRegistry::getFrame();
        $area = cRegistry::getArea();
        $form->setVar('area', $area);
        $form->setVar('frame', $frame);
        $form->setVar('action', 'edit_tinymce4');

        $toolbar1 = $this->_addLabelWithTextbox('Toolbar 1:', 'full_toolbar1');
        $form->add(i18n('Settings of inline editor in fullscreen mode'), $toolbar1->render());
//         $form->appendContent($content)

        // only system administrators can save system wysiwyg editor settings
        if ('sysadmin' !== cRegistry::getAuth()->getPerms()) {
            $form->setActionButton('submit', cRegistry::getBackendUrl() . 'images/but_ok_off.gif', i18n("You are not sysadmin. You can't change these settings."), 's');
        }

        $page->set('s', 'FORM', $form->render());
        $page->set('s', 'RELOAD_HEADER', (false) ? 'true' : 'false');
        $page->render();
//return;
        // set general form values
        $tmpl->set('s', 'WYSIWYG_EDITOR_PATH', cRegistry::getBackendUrl() . '/external/wysiwyg/tinymce4/');
        $tmpl->set('s', 'BACKEND_URL', cRegistry::getBackendUrl());
        $tmpl->set('s', 'SAVE_CHANGES', i18n('Save changes'));

        // fill out form values with localised strings
        $tmpl->set('s', 'WYSIWYG_CONFIG_TITLE', i18n('Tinymce 4 configuration'));
        $tmpl->set('s', 'TINYMCE_INLINE_FULLSCREEN_DESCRIPTION', i18n('Settings of inline editor in fullscreen mode'));
        $tmpl->set('s', 'TINYMCE_EDITORPAGE_DESCRIPTION', i18n('Settings of editor in separate editor page'));
        $tmpl->set('s', 'TOOLBARS_AND_PLUGINS', i18n('Toolbars and plugins'));
        $tmpl->set('s', 'SPACE_SEPARATED_LIST_VALUES', i18n('Enter lists of values, separate entries by space charactes.'));
        $tmpl->set('s', 'TOOLBAR_1', 'Toolbar 1');
        $tmpl->set('s', 'TOOLBAR_2', 'Toolbar 2');
        $tmpl->set('s', 'TOOLBAR_3', 'Toolbar 3');
        $tmpl->set('s', 'PLUGINS', 'Plugins');
        $tmpl->set('s', 'CONTENIDO_GZIP_DESCRIPTION', i18n('Gzip Tinymce (only activate if server does not compress content already)'));
        $tmpl->set('s', 'CONTENIDO_LISTS_IMAGE_DESCRIPTION', i18n('Provide jump lists in image insertion dialog'));
        $tmpl->set('s', 'CONTENIDO_LISTS_LINK_DESCRIPTION', i18n('Provide jump lists in link insertion dialog'));
        $tmpl->set('s', 'TINYMCE4CONFIG_JSON_FIELD_EXPLANATION', i18n('Additional parameters (JSON passed to tinymce constructor)'));
        $tmpl->set('s', 'TINY4CONFIG_JSON_REQUIRED_WARNING', i18n('Make sure your input is valid JSON, otherwise input will not be accepted!'));
        $tmpl->set('s', 'PLUGINS_TO_LOAD', i18n('Plugins to load'));
        $tmpl->set('s', 'PLUGIN_NAME', i18n('Plugin name'));
        $tmpl->set('s', 'PLUGIN_URL', i18n('Plugin URL'));

        // prepare to output template
        $pathToWysiwygFolder = cRegistry::getBackendPath() . 'external/wysiwyg/';
        $pathToTemplateInsideEditorFolder = '/contenido/templates/template.configuration.html';
        $tmpl->generate($pathToWysiwygFolder . $curWysiwygEditor . $pathToTemplateInsideEditorFolder);
    }
}
