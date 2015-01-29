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

// define fallback WYSIWYG editor
define('DEFAULT_WYSIWYG_EDITOR', 'tinymce3');

// find out what the current WYSIWYG editor is
$curWysiwygEditor = getEffectiveSetting('wysiwyg', 'editor', 'tinymce3');
if (false !== strpos($curWysiwygEditor, '.')
|| false !== strpos($curWysiwygEditor, '/')
|| false !== strpos($curWysiwygEditor, '\\')) {
    $curWysiwygEditor = constant('DEFAULT_WYSIWYG_EDITOR');
}

// prepare to output template
$pathToWysiwygFolder = cRegistry::getBackendPath() . 'external/wysiwyg/';
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

$notSupportedMsg = i18n('Configuration of the current WYSIWYG editor using this page is not supported');
echo '<!DOCTYPE html><html><head></head><body>' . $notSupportedMsg . '</body></html>';
