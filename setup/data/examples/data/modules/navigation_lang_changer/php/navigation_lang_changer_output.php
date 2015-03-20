<?php

/**
 * Description: Language changer
 *
 * @package Module
 * @subpackage NavigationLangChanger
 * @version SVN Revision $Rev:$
 *
 * @author alexander.scheider@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

// create instances and init vars
$catCollection = new cApiCategoryLanguageCollection();
$artCollection = new cApiArticleLanguageCollection();
$catArtCollection = new cApiCategoryArticleCollection();
$languageCollectionInstance = new cApiLanguageCollection();
$clientsLangInstance = new cApiClientLanguageCollection();
$languageInstance = new cApiLanguage();

$tpl = new cTemplate();
$nextLang = false;
$selectedLang = NULL;
$checkedCatArt = false;
$idcatAuto = cRegistry::getCategoryId();
$artRetItem = NULL;
$urlSet = false;
$currentLanguage = NULL;
$clientId = cRegistry::getClientId();

// get all client language id's
$clientsLangInstance->select("idclient= " . $clientId);
$resultClientLangs = $clientsLangInstance->fetchArray('idlang', 'idlang');

// get all active languages of a client
foreach ($resultClientLangs as $clientLang) {
    $languageInstance->loadByMany(array(
        'active' => '1',
        'idlang' => $clientLang
    ));
    if ($languageInstance->get('idlang')) {
        $allLanguages[] = $languageInstance->get('idlang');
    }
    $languageInstance = new cApiLanguage();
}

if (empty($allLanguages)) {
    // no active languages. handling was moved to include.front_content.php (lines 433 - 439).
} else if (count($allLanguages) != 1) {

    // else check if there more as one language
    $currentLanguage = cRegistry::getLanguageId();

    // set next language is exists
    foreach ($allLanguages as $langs) {
        if ($langs > $currentLanguage) {
            $langName = conHtmlSpecialChars($languageCollectionInstance->getLanguageName((int) $langs));
            $tpl->set('s', 'label', $langName);
            $tpl->set('s', 'title', $langName);

            $selectedLang = $langs;
            $nextLang = true;
            break;
        }
    }

    // otherwise set first language
    if ($nextLang === false) {
        $languageName = $languageCollectionInstance->getLanguageName(reset($allLanguages));

        $tpl->set('s', 'label', $languageName);
        $tpl->set('s', 'title', $languageName);
        $selectedLang = reset($allLanguages);
    }

    // check category and articles, if category exists and has start article
    // which
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
        $url = $catRetItem->getLink($selectedLang);
    } else {
        $config = cRegistry::getClientConfig(cRegistry::getClientId());
        $url = cRegistry::getFrontendUrl() . 'front_content.php?idart='.$idart.'&changelang=' . $selectedLang;
    }

    $tpl->set('s', 'url', conHtmlSpecialChars($url));
    $tpl->generate('get.html');

}

?>