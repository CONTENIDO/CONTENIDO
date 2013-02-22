<?php

/**
 * Description: Language changer
 *
 * @version 1.0.0
 * @author A. Scheider
 * @copyright four for business AG <www.4fb.de>
 */

// create instances and init vars
$catCollection = new cApiCategoryLanguageCollection();
$artCollection = new cApiArticleLanguageCollection();
$catArtCollection = new cApiCategoryArticleCollection();
$languageInstance = new cApiLanguageCollection();

$tpl = new cTemplate();
$nextLang = false;
$selectedLang = NULL;
$checkedCatArt = false;
$idcatAuto = cRegistry::getCategoryId();
$artRetItem = NULL;
$urlSet = false;
$currentLanguage = NULL;

// fetch all existing languages, if no languages available, exit
$allLanguages = $languageInstance->getAllIds();
if (empty($allLanguages)) {
    echo "No languages present";
    exit();
}
// fetch current language
$currentLanguage = cRegistry::getLanguageId();

// set next language is exists
foreach ($allLanguages as $langs) {
    if ($langs > $currentLanguage) {
        $tpl->set('s', 'label', $languageInstance->getLanguageName($langs));
        $tpl->set('s', 'title', $languageInstance->getLanguageName($langs));
        $selectedLang = $langs;
        $nextLang = true;
        break;
    }
}

// otherwise set first language
if ($nextLang === false) {
    $tpl->set('s', 'label', $languageInstance->getLanguageName(reset($allLanguages)));
    $selectedLang = reset($allLanguages);
}

// check category and articles, if category exists and has start article which
// is online and not locked the set check to true
$catCheck = $catCollection->select("idcat = " . $idcatAuto . " AND " . " idlang = " . $selectedLang . " AND " . "startidartlang != 0", NULL, NULL, NULL);

$catRetItem = new cApiCategoryLanguage();
$catRetItem->loadByCategoryIdAndLanguageId($idcatAuto, $selectedLang);

if ($catCheck === true && $catRetItem) {
    $artRetItem = $artCollection->fetchById($catRetItem->get('startidartlang'));
}
if ($artRetItem) {
    if ($artRetItem->get('online') == 1 && $artRetItem->get('locked') == 0) {
        $checkedCatArt = true;
    }
}

// if check is true then set url, otherwise check for next language
if ($checkedCatArt === true) {

    $url = $catRetItem->getLink() . '?' . "changelang=" . $selectedLang;
    $urlSet = true;
} else {
    $config = cRegistry::getClientConfig(cRegistry::getClientId());
    $url = $config[path][htmlpath];
}
// if url is not set, then set fallback url to the homepage
if ($urlSet === false) {
    $url = cRegistry::getFrontendUrl() . 'front_content.php' . '?' . "changelang=" . $selectedLang;
}

$tpl->set('s', 'url', $url);
$tpl->generate('get.html');

?>

