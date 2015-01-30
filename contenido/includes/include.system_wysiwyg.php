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
    $notSupportedMsg = i18n('Configuration of the current WYSIWYG editor using this page is not supported');
    echo '<!DOCTYPE html><html><head></head><body>' . $notSupportedMsg . '</body></html>';

    // do not process any further input values
    return;
}

// prepare to output template
$configClass = 'c' . strtoupper($curWysiwygEditor[0]) . substr($curWysiwygEditor, 1) . 'Configuration';

if (class_exists($configClass)) {

    // call WYSIWYG editor configuration code
    if (class_exists($configClass)) {
        // create class instance based on variable value
        $configClassInstance = new $configClass();

        // check if form has been sent
        if (isset($_POST['form_sent'])
        && 'true' === $_POST['form_sent']) {
            // we got form data
            
            // clean form from form_sent marker
            $formData = $_POST;
            unset($formData['form_sent']);

            // if form data is correct
            if (false !== ($formData = $configClassInstance->validateForm($formData))) {
                // input is processed inside WYSIWYG editor class
                // call used implementation to save input
                $wysiwygEditorClass = cRegistry::getConfigValue('wysiwyg', $curWysiwygEditor . '_editorclass');

                $wysiwygEditorClass::safeConfig($formData);
            }
        }

        $configClassInstance->showConfigurationForm();
        return;
    }
}
