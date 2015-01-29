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
    function __construct() {
        $curWysiwygEditor = getEffectiveSetting('wysiwyg', 'editor', 'tinymce3');
        $tmpl = new cTemplate();
        
        echo '<pre>';
        var_dump($_POST);
        echo '</pre>';
        
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
        
        
        // prepare to output template
        $pathToWysiwygFolder = cRegistry::getBackendPath() . 'external/wysiwyg/';
        $pathToTemplateInsideEditorFolder = '/contenido/templates/template.configuration.html';
        $tmpl->generate($pathToWysiwygFolder . $curWysiwygEditor . $pathToTemplateInsideEditorFolder);
    }
}

