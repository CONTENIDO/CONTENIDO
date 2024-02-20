<?php

/**
 * Description: Language changer
 *
 * @package    Module
 * @subpackage NavigationLangChanger
 * @author     alexander.scheider@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
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
$catCheck = false;
$artCheck = false;
$startart = NULL;

// get all client language id's
$clientsLangInstance->select("idclient= " . $clientId);
$resultClientLangs = $clientsLangInstance->fetchArray('idlang', 'idlang');

// get all active languages of a client
foreach ($resultClientLangs as $clientLang) {
    $languageInstance->loadByMany(
        [
            'active' => '1',
            'idlang' => $clientLang,
        ]
    );
    if ($languageInstance->get('idlang')) {
        $allLanguageIds[] = cSecurity::toInteger($languageInstance->get('idlang'));
    }
}

if (count($allLanguageIds) != 1) {
    $idart = cSecurity::toInteger(cRegistry::getArticleId());
    $langName = '';

    // else check if there is more than one language
    $currentLanguage = cSecurity::toInteger(cRegistry::getLanguageId());

    // set next language if exists
    foreach ($allLanguageIds as $languageId) {
        if ($languageId > $currentLanguage) {
            $langName = conHtmlSpecialChars($languageCollectionInstance->getLanguageName($languageId));
            if ('' === trim($langName)) {
                $langName = mi18n("LANGUAGE_NAME_EMPTY");
            }
            $tpl->set('s', 'label', $langName);
            $tpl->set('s', 'title', $langName);

            $selectedLang = $languageId;
            $nextLang = true;
            break;
        }
    }

    // otherwise set first language
    if ($nextLang === false) {
        $languageName = conHtmlSpecialChars($languageCollectionInstance->getLanguageName(reset($allLanguageIds)));
        if ('' === trim($langName)) {
            $langName = mi18n("LANGUAGE_NAME_EMPTY");
        }

        $tpl->set('s', 'label', $languageName);
        $tpl->set('s', 'title', $languageName);
        $selectedLang = reset($allLanguageIds);
    }

    // check articles, if article exists and is online and not locked set the check to true
    $artCheck = $artCollection->select("idart = '" . $idart . "' AND idlang = '" . cSecurity::toInteger($selectedLang) . "' AND online = '1' AND locked = '0'", NULL, NULL, NULL);

    // check if this article is an startarticle
    $startart = $catCollection->getStartIdartByIdcatAndIdlang($idcatAuto, $selectedLang);

    if ($artCheck !== true || ($startart == $idart)) {
        // check category and articles, if category exists and has start article
        // which is online and not locked the set check to true
        $catCheck = $catCollection->select("idcat = '" . cSecurity::toInteger($idcatAuto) . "' AND idlang = '" . cSecurity::toInteger($selectedLang) . "' AND startidartlang != '0'", NULL, NULL, NULL);

        $catRetItem = new cApiCategoryLanguage();
        $catRetItem->loadByCategoryIdAndLanguageId(cSecurity::toInteger($idcatAuto), cSecurity::toInteger($selectedLang));

        if ($catCheck === true && $catRetItem) {
            $artRetItem = $artCollection->fetchById($catRetItem->get('startidartlang'));
        }
        if ($artRetItem) {
            if ($artRetItem->get('online') == 1 && $artRetItem->get('locked') == 0) {
                $checkedCatArt = true;
            }
        }
    }

    // if check is true then set url, otherwise check for next language
    if ($checkedCatArt === true) {
        $url = isset($catRetItem) ? $catRetItem->getLink($selectedLang) : '#';
    } else {
        $config = cRegistry::getClientConfig(cRegistry::getClientId());
        $url = cRegistry::getFrontendUrl() . 'front_content.php?idart=' . cSecurity::toInteger($idart) . '&changelang=' . cSecurity::toInteger($selectedLang);
    }

    $tpl->set('s', 'url', conHtmlSpecialChars($url));
    $tpl->generate('get.html');

}

?>