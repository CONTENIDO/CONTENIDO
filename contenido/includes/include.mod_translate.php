<?php

/**
 * This file contains the backend page for managing module translations.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $t_orig, $t_trans;

$langObj = new cApiLanguage($lang);

$langString = $langObj->get('name') . ' (' . $lang . ')';

$readOnly = (getEffectiveSetting("client", "readonly", "false") == "true");

if ($readOnly) {
    cRegistry::addWarningMessage(i18n('This area is read only! The administrator disabled edits!'));
}

$page = new cGuiPage("mod_translate");

$module = new cApiModule($idmod);

$originalString = '';
$translationString = '';
$moduleTranslation = new cModuleFileTranslation($idmod);

if ((!$readOnly) && $action == 'mod_translation_save') {
    $originalString = $t_orig;
    $translationString = $t_trans;

    $translationArray = $moduleTranslation->getTranslationArray();

    $translationArray[stripslashes($t_orig)] = stripslashes($t_trans);

    if ($moduleTranslation->saveTranslationArray($translationArray)) {
        $page->displayOk(i18n('Saved translation successfully!'));
    } else {
        $page->displayError(i18n("Can't save translation!"));
    }
}

if (!isset($idmodtranslation)) {
    $idmodtranslation = 0;
}

// Get the mi18n strings from module input/output
$strings = $module->parseModuleForStringsLoadFromFile($cfg, $client, $lang);
if (!is_array($strings)) {
    $strings = [];
}

// Get the strings from translation file
$translationArray = $moduleTranslation->getTranslationArray();

$myTrans = [];
$save = false;
// Insert new strings
foreach ($strings as $string) {
    if (isset($translationArray[$string])) {
        $myTrans[$string] = $translationArray[$string];
    } else {
        $myTrans[$string] = '';
    }
}

// sort array
ksort($myTrans);

// count translations (counter started with zero)
$myTransCount = count($myTrans)-1;

// If changed save in file
if (count(array_diff_assoc($myTrans, $translationArray)) > 0 || count(array_diff_assoc($translationArray, $myTrans)) > 0) {
    $moduleTranslation->saveTranslationArray($myTrans);
}

if (!isset($row)) {
    $row = 0; // first string
    $current = 0;
    $lastString = reset($strings);
    $lastTranslation = isset($myTrans[$lastString]) ? $myTrans[$lastString] : '';
} else {
    // Get the string
    $index = 0;
    foreach ($myTrans as $key => $value) {
        if ($index == $row) {
            $lastString = $key;
            $lastTranslation = $value;

            // Current string
            $current = $row;

            // Jump to next entry
            if ($myTransCount == $index) {
                $row = 0;
            } else {
                $row++;
            }
            break;
        }
        // increase index
        $index++;
    }
}

$page->set("s", "IDMOD", $idmod);
$page->set("s", "CURRENT", $current);
$page->set("s", "ROW", $row);
$page->set("s", "HEADER", sprintf(i18n("Translate module '%s'"), conHtmlSpecialChars($module->get('name'))));
$page->set("s", "TRANSLATION_FOR", sprintf(i18n("Translation for %s"), $langString));
$page->set("s", "LAST_STRING", conHtmlSpecialChars($lastString));
$page->set("s", "LAST_TRANSLATION", conHtmlSpecialChars($lastTranslation));

if ($readOnly) {
    $page->set("s", "DISABLED", "disabled='disabled'");
    $page->set("s", "READONLY", "_off");
} else {
    $page->set("s", "DISABLED", "");
    $page->set("s", "READONLY", "");
}

$page->setMarkScript(2);
$page->setEncoding($langObj->get('encoding'));

$page->render();
