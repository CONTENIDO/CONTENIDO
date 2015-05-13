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


// find out what the current WYSIWYG editor is
$curWysiwygEditor = cWYSIWYGEditor::getCurrentWysiwygEditorName();

if ('tinymce3' === $curWysiwygEditor) {
    $page = new cGuiPage('system_wysiwyg', '', '5');
    $page->displayError(i18n('Configuration of the current WYSIWYG editor using this page is not supported.'));
    $page->displayInfo(i18n('Configuration values can be set through system expert settings, client and user settings.'));
    $page->render();

    // do not process any further input values
    return;
}

// prepare to output template
// example config class name: cTinymce4Configuration
$configClass = 'c' . strtoupper($curWysiwygEditor[0]) . substr($curWysiwygEditor, 1) . 'Configuration';

if (class_exists($configClass)) {

    // call WYSIWYG editor configuration code
    if (class_exists($configClass)) {
        // create class instance based on variable value
        $configClassInstance = new $configClass();

        // check if form has been sent
        $formSubmitBtn = isset($_POST['action']) || isset($_GET['action']);
        // if config should be saved
        if ($formSubmitBtn) {
            // we got form data

            // pass post data to configuration class to validate content
            $formData = $_POST;

            // check form data is correct
            // validation function must also sanitise form data thus use its return value
            if (false !== ($formData = $configClassInstance->validateForm($formData))) {
                // input is processed inside WYSIWYG editor class
                // call used implementation to save input
                $wysiwygEditorClass = cRegistry::getConfigValue('wysiwyg', $curWysiwygEditor . '_editorclass');
                $wysiwygEditorClass::saveConfig($formData);
            }
        }

        $configClassInstance->showConfigurationForm();
        return;
    }
}
