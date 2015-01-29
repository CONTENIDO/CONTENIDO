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

$pathToWysiwygFolder = cRegistry::getBackendPath() . 'external/wysiwyg/';

// check if form has been sent
if (isset($_POST['form_sent'])
&& 'true' === $_POST['form_sent']) {
    // we got form data
    
    // input is processed inside WYSIWYG editor class
    // call used implementation to save input
    $wysiwygEditorClass = cRegistry::getConfigValue('wysiwyg', $curWysiwygEditor . '_editorclass');
    var_dump($wysiwygEditorClass);
    $wysiwygEditorClass::safeConfig($_POST);
    echo "sent";
}

// prepare to output template
$pathToConfigClass = '/contenido/classes/class.' . $curWysiwygEditor . '.configuration.php';

$classFile = $pathToWysiwygFolder . $curWysiwygEditor . $pathToConfigClass;

if (cFileHandler::exists($classFile)) {
    require($classFile);
    
    // call WYSIWYG editor configuration code
    $configClass = 'c' . strtoupper($curWysiwygEditor[0]) . substr($curWysiwygEditor, 1) . 'Configuration';
    if (class_exists($configClass)) {
        // create class based on variable value
        new $configClass();
        return;
    }
}

