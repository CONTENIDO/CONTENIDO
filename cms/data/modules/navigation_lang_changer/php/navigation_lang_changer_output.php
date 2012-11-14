<?php

/**
 * Description: Language changer
 *
 * @version    1.0.0
 * @author     A. Scheider
 * @copyright  four for business AG <www.4fb.de>
 *
 */

// create instances and initial vars
$catCollection = new cApiCategoryLanguageCollection ();
$artCollection = new cApiArticleLanguageCollection ();
$catArtCollection = new cApiCategoryArticleCollection ();
$languageInstance = new cApiLanguageCollection ();
$tpl = new cTemplate ();
$nextLang = FALSE;
$selectedLang = null;
$checkedCatArt = FALSE;
$idcatAuto = cRegistry::getCategoryId ( true );
$idcatlangAuto = cRegistry::getCategoryLanguageId ( true );
$artRetItem = null;
$urlSet = FALSE;
$currentLanguage = null;

// fetch all existing languages, if no languages available, exit
$allLanguages = $languageInstance->getAllIds ();
if (empty ( $allLanguages )) {
	echo "No languages present";
	exit ();
}
// fetch current language
$currentLanguage = cRegistry::getLanguageId ();

// set next language is exists
foreach ( $allLanguages as $langs ) {
	if ($langs > $currentLanguage) {
		$tpl->set ( 's', 'label', $languageInstance->getLanguageName ( $langs ) );
		$tpl->set ( 's', 'title', $languageInstance->getLanguageName ( $langs ) );
		$selectedLang = $langs;
		$nextLang = TRUE;
		break;
	}
}

// otherwise set first language
if ($nextLang === FALSE) {
	$tpl->set ( 's', 'label', $languageInstance->getLanguageName ( reset ( $allLanguages ) ) );
	$selectedLang = reset ( $allLanguages );
}

// check category and articles, if category exists and has start article which
// is online and not locked the set check to true
$catCheck = $catCollection->select ( "idcat = " . $idcatAuto . " AND " . " idlang = " . $selectedLang . " AND " . "startidartlang != 0", null, null, null );

$catRetItem = $catCollection->fetchById ( $idcatAuto );
if ($catCheck === TRUE && $catRetItem) {
	$artRetItem = $artCollection->fetchById ( $catRetItem->get ( 'startidartlang' ) );
}
if ($artRetItem) {
	if ($artRetItem->get ( 'online' ) == 1 && $artRetItem->get ( 'locked' ) == 0) {
		$checkedCatArt = TRUE;
	}
}

// if check is true then set url, otherwise check for next language
if ($checkedCatArt === TRUE) {

	$url = $catRetItem->getLink() . '&' . "changelang=" . $selectedLang;
	$urlSet = TRUE;
} else if ($checkedCatArt === FALSE) {
	foreach ( $allLanguages as $langs ) {
		if ($langs > $selectedLang) {
			if ($catCollection->select ( "idcat = " . $idcatAuto . " AND " . " idlang = " . $langs . " AND " . "startidartlang != 0", null, null, null )) {
				if ($artRetItem) {
					if ($artRetItem->get ( 'online' ) == 1 && $artRetItem->get ( 'locked' ) == 0) {
						$url = $catRetItem->getLink()  . '&' . "changelang=" . $langs;
						$urlSet = TRUE;
						$tpl->set ( 's', 'url', $url );
					}
				}
			} else {
				$url = cRegistry::getFrontendUrl () . 'front_content.php?' . '&' . "changelang=" . $selectedLang;
				$urlSet = TRUE;
			}
		}

		else if ($currentLanguage != reset ( $allLanguages )) {
			if ($catCollection->select ( "idcat = " . $idcatAuto . " AND " . " idlang = " . reset ( $allLanguages ) . " AND " . "startidartlang != 0", null, null, null )) {
				$artRetItem = $artCollection->fetchById ( $catRetItem->get ( 'startidartlang' ) );
				if ($artRetItem) {
					if ($artRetItem->get ( 'online' ) == 1 && $artRetItem->get ( 'locked' ) == 0) {
						$url = $catRetItem->getLink()  . '&' . "changelang=" . reset ( $allLanguages );
						$urlSet = TRUE;
					} else {
						$urlSet = TRUE;
						$url = cRegistry::getFrontendUrl () . 'front_content.php?' . "changelang=" . reset ( $allLanguages );
					}
				}
			}
		} else if ($currentLanguage == reset ( $allLanguages )) {
			if ($catCollection->select ( "idcat = " . $idcatAuto . " AND " . " idlang = " . next ( $allLanguages ) . " AND " . "startidartlang != 0", null, null, null )) {
				$url = $catRetItem->getLink()  . '&' . "changelang=" . next ( $allLanguages );
				$urlSet = TRUE;
			}
		}
	}
}
// if url is not set, then set fallback url to the homepage
if ($urlSet === FALSE) {
	$url = cRegistry::getFrontendUrl () . 'front_content.php?' . '&' . "changelang=" . reset ( $allLanguages );
}

$tpl->set ( 's', 'url', $url );
$tpl->next ();

$tpl->generate ( 'lang_changer.html' );

?>

