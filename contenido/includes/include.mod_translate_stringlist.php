<?php

/**
 * This file contains the backend page for managing module translations.
 *
 * @package Core
 * @subpackage Backend
 * @author Unknown
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$moduleTranslation = new cModuleFileTranslation($idmod);
$translationArray = $moduleTranslation->getTranslationArray();

// sort array
ksort($translationArray);

$page = new cGuiPage("mod_translate_stringlist");

$link = new cHTMLLink();
$link->setCLink("mod_translate", 4, "");

$mylink = new cHTMLLink();

$rowCount = 0;

foreach ($translationArray as $key => $value) {
    $link->setCustom("idmod", cSecurity::toInteger($idmod));
    $link->setCustom("row", $rowCount);

    $style = ($rowCount == $current) ? 'active' : '';

    $href = $link->getHref();
    $mylink->setLink(conHtmlSpecialChars('javascript:parent.location="' . $href . '"'));
    $mylink->setContent($key);

    $page->set("d", "STYLE", $style);
    $page->set("d", "TRANSLATION_LINK", $mylink->render());
    $page->set("d", "TRANSLATION", $value);
    $page->set("d", "ROWCOUNT", $rowCount);
    $page->next();

    $rowCount++;
}

$clang = new cApiLanguage($lang);

$page->setEncoding($clang->get("encoding"));

$page->render();

?>