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
        // decide whether user is allowed to change values
        $this->_perm = ('sysadmin' === cRegistry::getAuth()->getPerms());
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
        
        // User must be system administrator to change the settings
        if ('sysadmin' !== cRegistry::getAuth()->getPerms()) {
            return false;
        }

        // remove not used area field
        unset($config['area']);
        // remove not used frame field
        unset($config['frame']);
        // remove not used contenido field
        unset($config['contenido']);
        
        // remove x and y values from image submit button in in form
        unset($config['submit_x']);
        unset($config['submit_y']);


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
            'tinymce4'/*
,
            'externalplugin'
*/
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

    private function _addLabelWithTextbox($description, $name, $width = 150) {
        $label = new cHTMLLabel($description, $name);
        $label->setClass("sys_config_txt_lbl");
        $label->setStyle('width:' . $width . 'px;');
        
        $textbox = new cHTMLTextbox($name);
        if (false === $this->_perm) {
            $textbox->updateAttribute('disabled', 'disabled');
        }
        $div = new cHTMLDiv($label .  $textbox, 'systemSetting');

        return $div;
    }
    
    private function _addLabelWithCheckbox($description, $name, $value, $checked) {
        $checkBox = new cHTMLCheckbox($name, $value, str_replace('[]', '_', $name . $value), (true === $checked));
        $checkBox->setLabelText($description);
        
        if (false === $this->_perm) {
            $checkBox->updateAttribute('disabled', 'disabled');
        }
        
        return $checkBox;
    }
    
    public function showConfigurationForm() {
        $page = new cGuiPage('system_wysiwyg_tinymce4', '', '5');
        $auth = cRegistry::getAuth();
        $frame = cRegistry::getFrame();
        $area = cRegistry::getArea();

        // validate if user has permission to edit this area
        if (false === cRegistry::getPerm()->have_perm_area_action($area, 'edit_system_wysiwyg_tinymce4')) {
            $page->displayCriticalError(i18n('Access denied'));
            $page->render();
            return;
        }
        
        $curWysiwygEditor = getEffectiveSetting('wysiwyg', 'editor', 'tinymce3');
        $tmpl = new cTemplate();
        
        //if (false === cRegistry::getPerm())
        
        $page->displayInfo(i18n('Currently active WYSIWYG editor: ' . cWYSIWYGEditor::getCurrentWysiwygEditorName()));
        $form = new cGuiTableForm('system_wysiwyg_tinymce4');
        $form->addHeader(i18n('Tinymce 4 configuration'));

        $form->setVar('area', $area);
        $form->setVar('frame', $frame);
        $form->setVar('action', 'edit_tinymce4');


        $containerDiv = new cHTMLDiv();
        $containerDiv->appendContent($this->_addLabelWithTextbox('Toolbar 1:', 'tinymce4_full[toolbar1]'));
        $containerDiv->appendContent($this->_addLabelWithTextbox('Toolbar 2:', 'tinymce4_full[toolbar2]'));
        $containerDiv->appendContent($this->_addLabelWithTextbox('Toolbar 3:', 'tinymce4_full[toolbar3]'));
        $containerDiv->appendContent($this->_addLabelWithTextbox('Plugins:', 'tinymce4_full[plugins]'));
        $form->add(i18n('Settings of inline editor in fullscreen mode'), $containerDiv->render());
        
        $containerDiv = new cHTMLDiv();
        $containerDiv->appendContent($this->_addLabelWithTextbox('Toolbar 1:', 'tinymce4_fullscreen[toolbar1]'));
        $containerDiv->appendContent($this->_addLabelWithTextbox('Toolbar 2:', 'tinymce4_fullscreen[toolbar2]'));
        $containerDiv->appendContent($this->_addLabelWithTextbox('Toolbar 3:', 'tinymce4_fullscreen[toolbar3]'));
        $containerDiv->appendContent($this->_addLabelWithTextbox('Plugins:', 'tinymce4_fullscreen[plugins]'));
        $form->add(i18n('Settings of editor in separate editor page'), $containerDiv->render());
        
        // GZIP editor over HTTP using tinymce's library
        $containerDiv = new cHTMLDiv();
        $containerDiv->appendContent($this->_addLabelWithCheckbox('Gzip Tinymce (only activate if server does not compress content already)', 'contenido_gzip', 'contenido_gzip', false));
        $form->add(i18n('contenido_gzip'), $containerDiv->render());
        
        // Add jump lists to tinymce's dialogs
        $containerDiv = new cHTMLDiv();
        $containerDiv->appendContent($this->_addLabelWithCheckbox('Provide jump lists in image insertion dialog', 'contenido_lists[]', 'image'));
        $containerDiv->appendContent($this->_addLabelWithCheckbox('Provide jump lists in link insertion dialog', 'contenido_lists[]', 'link'));
        $form->add(i18n('contenido_lists'), $containerDiv->render());
        
        //add textarea for custom tinymce 4 settings
        $textarea = new cHTMLTextarea('tinymce4');
        $form->add(i18n('Additional parameters (JSON passed to tinymce constructor)'), $textarea->render());
        
        // check permission to save system wysiwyg editor settings
        if (false === $this->_perm) {
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
