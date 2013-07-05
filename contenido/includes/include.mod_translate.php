<?php
/**
 * This file contains the backend page for managing module translations.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$langobj = new cApiLanguage($lang);

$langstring = $langobj->get('name') . ' (' . $lang . ')';

$page = new cGuiPage("mod_translate");

$module = new cApiModule($idmod);

$originalString = '';
$translationString = '';
$moduleTranslation = new cModuleFileTranslation($idmod);

if ($action == 'mod_translation_save') {
    $originalString = $t_orig;
    $translationString = $t_trans;

    $translationArray = $moduleTranslation->getTranslationArray();

    $translationArray[stripslashes($t_orig)] = stripslashes($t_trans);

    if ($moduleTranslation->saveTranslationArray($translationArray)) {
        $page->displayInfo(i18n('Saved translation successfully!'));
    } else {
        $page->displayError(i18n("Can't save translation!"));
    }
}

if (!isset($idmodtranslation)) {
    $idmodtranslation = 0;
}

// Get the mi18n strings from modul input/output
$strings = $module->parseModuleForStringsLoadFromFile($cfg, $client, $lang);

// Get the strings from translation file
$translationArray = $moduleTranslation->getTranslationArray();

$myTrans = array();
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

// If changed save in file
if (count(array_diff_assoc($myTrans, $translationArray)) > 0 || count(array_diff_assoc($translationArray, $myTrans)) > 0) {
	$moduleTranslation->saveTranslationArray($myTrans);
}

if (!isset($row)) {
    $row = 0; // first string
    $lastString = reset($strings);
    $lastTranslation = $myTrans[$lastString];
} else {
    // Get the string
    $index = 0;
    foreach ($myTrans as $key => $value) {
        if ($index == $row) {
            $lastString = $key;
            $lastTranslation = $value;
            break;
        }
        $index++;
    }
}

$page->set("s", "IDMOD", $idmod);
$page->set("s", "ROW", $row);
$page->set("s", "HEADER", sprintf(i18n("Translate module '%s'"), $module->get('name')));
$page->set("s", "TRANSLATION_FOR", sprintf(i18n("Translation for %s"), $langstring));
$page->set("s", "LAST_STRING", conHtmlSpecialChars($lastString));
$page->set("s", "LAST_TRANSLATION", conHtmlSpecialChars($lastTranslation));

$page->setMarkScript(2);
$page->setEncoding($langobj->get('encoding'));

$page->render();

?>