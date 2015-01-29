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
        $tmpl->set('s', 'TINYMCE4CONFIG_JSON_FIELD_EXPLANATION', 'Additional parameters (JSON passed to tinymce constructor)');
        $tmpl->set('s', 'TINY4CONFIG_JSON_REQUIRED_WARNING', i18n('Make sure this is valid json, otherwise input will not be accepted!'));
        
        
        // prepare to output template
        $pathToWysiwygFolder = cRegistry::getBackendPath() . 'external/wysiwyg/';
        $pathToTemplateInsideEditorFolder = '/contenido/templates/template.configuration.html';
        $tmpl->generate($pathToWysiwygFolder . $curWysiwygEditor . $pathToTemplateInsideEditorFolder);
    }
}

