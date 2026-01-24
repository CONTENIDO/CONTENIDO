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
$languageCollection = new cApiLanguageCollection();
$clientLanguageCollection = new cApiClientLanguageCollection();
$languageObj = new cApiLanguage();

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

// get all active languages of the client
$allLanguageIds = $clientLanguageCollection->getAllLanguageIdsByClient($clientId, true);

if (count($allLanguageIds) != 1) {
    $idart = cRegistry::getArticleId();
    $langName = '';

    // else check if there is more than one language
    $currentLanguage = cRegistry::getLanguageId();

    // set next language if exists
    foreach ($allLanguageIds as $languageId) {
        if ($languageId > $currentLanguage) {
            $langName = conHtmlSpecialChars($languageCollection->getLanguageName($languageId));
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
    if (!$nextLang) {
        $languageName = conHtmlSpecialChars($languageCollection->getLanguageName(reset($allLanguageIds)));
        if (empty(trim($langName))) {
            $langName = mi18n("LANGUAGE_NAME_EMPTY");
        }

        $tpl->set('s', 'label', $languageName);
        $tpl->set('s', 'title', $languageName);
        $selectedLang = reset($allLanguageIds);
    }

    // check articles, if article exists and is online and not locked set the check to true
    $artCheck = $artCollection->select(sprintf(
        "`idart` = %d AND `idlang` = %d AND `online` = 1 AND `locked` = 0",
        $idart,
        cSecurity::toInteger($selectedLang)
    ));

    // check if this article is a startarticle
    $startart = $catCollection->getStartIdartByIdcatAndIdlang($idcatAuto, $selectedLang);

    if (!$artCheck || ($startart == $idart)) {
        // check category and articles, if category exists and has start article
        // which is online and not locked the set check to true
        $catCheck = $catCollection->select(sprintf(
            "`idcat` = %d AND `idlang` = %d AND `startidartlang` != 0",
            cSecurity::toInteger($idcatAuto),
            cSecurity::toInteger($selectedLang)
        ));

        $catRetItem = new cApiCategoryLanguage();
        $catRetItem->loadByCategoryIdAndLanguageId(cSecurity::toInteger($idcatAuto), cSecurity::toInteger($selectedLang));

        if ($catCheck && $catRetItem->isLoaded()) {
            $artRetItem = $artCollection->fetchById($catRetItem->get('startidartlang'));
        }
        if ($artRetItem) {
            if ($artRetItem->get('online') == 1 && $artRetItem->get('locked') == 0) {
                $checkedCatArt = true;
            }
        }
    }

    // if check is true then set url, otherwise check for next language
    if ($checkedCatArt) {
        $url = isset($catRetItem) ? $catRetItem->getLink($selectedLang) : '#';
    } else {
        $config = cRegistry::getClientConfig(cRegistry::getClientId());
        $url = sprintf(
            '%sfront_content.php?idart=%d&changelang=%d',
            cRegistry::getFrontendUrl(),
            cSecurity::toInteger($idart),
            cSecurity::toInteger($selectedLang)
        );
    }

    $tpl->set('s', 'url', conHtmlSpecialChars($url));
    $tpl->generate('get.html');
}

?>
