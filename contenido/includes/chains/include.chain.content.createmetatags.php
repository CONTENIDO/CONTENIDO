<?php

/**
 * CONTENIDO Chain.
 * Generate meta-tags for the current article if they are not set in article
 * properties
 *
 * @package    Core
 * @subpackage Chain
 * @author     Andreas Lindner
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('plugins', 'repository/keyword_density.php');

/**
 * @param array $metaTags
 * @return array
 * @throws cDbException|cException
 */
function cecCreateMetatags($metaTags)
{
    // (Re)build meta-tags

    $db = cRegistry::getDb();
    $lang = cRegistry::getLanguageId();
    $idart = cRegistry::getArticleId();
    $idartlang = cRegistry::getArticleLanguageId();

    // Get encoding
    $oLang = new cApiLanguage($lang);
    if ($oLang->get('encoding')) {
        $sEncoding = cString::toUpperCase($oLang->get('encoding'));
    } else {
        $sEncoding = 'ISO-8859-1';
    }

    // Get idcat of homepage
    $sql = "SELECT a.idcat
        FROM
            " . cDb::getTableName('cat_tree') . " AS a,
            " . cDb::getTableName('cat_lang') . " AS b
        WHERE
            (a.idcat = b.idcat) AND
            (b.visible = 1) AND
            (b.idlang = " . $lang . ")
        ORDER BY a.idtree LIMIT 1";

    $db->query($sql);

    $idCatHomepage = $db->nextRecord() ? cSecurity::toInteger($db->f('idcat')) : 0;

    $availableTags = conGetAvailableMetaTagTypes();

    // Get first headline and first text for current article
    // @todo use this cApiArticleLanguage instance in code below, instead of
    // creating it again and again!
    $oArt = new cApiArticleLanguage();
    $oArt->loadByArticleAndLanguageId($idart, $lang);

    // Set idartlang, if not set
    if ($idartlang == '') {
        $idartlang = $oArt->getField('idartlang');
    }

    $arrHead1 = $oArt->getContent('htmlhead');
    $arrHead2 = $oArt->getContent('head');

    if (!is_array($arrHead1)) {
        $arrHead1 = [];
    }

    if (!is_array($arrHead2)) {
        $arrHead2 = [];
    }

    $arrHeadlines = array_merge($arrHead1, $arrHead2);
    $sHeadline = '';

    foreach ($arrHeadlines as $key => $value) {
        if ($value != '') {
            $sHeadline = $value;
            break;
        }
    }

    $sHeadline = strip_tags($sHeadline);
    $sHeadline = cString::getPartOfString(str_replace("\r\n", ' ', $sHeadline), 0, 100);

    $arrText1 = $oArt->getContent('html');
    $arrText2 = $oArt->getContent('text');

    if (!is_array($arrText1)) {
        $arrText1 = [];
    }

    if (!is_array($arrText2)) {
        $arrText2 = [];
    }

    $arrText = array_merge($arrText1, $arrText2);
    $sText = '';

    foreach ($arrText as $key => $value) {
        if ($value != '') {
            $sText = $value;
            break;
        }
    }

    $sText = strip_tags(urldecode($sText));
    $sText = keywordDensity('', $sText);

    // Get meta-tags for homepage
    $arrHomepageMetaTags = [];

    $sql = "SELECT `startidartlang` FROM `%s` WHERE `idcat` = %d AND `idlang` = %d";
    $db->query($sql, cDb::getTableName('cat_lang'), $idCatHomepage, $lang);

    if ($db->nextRecord()) {
        $iIdArtLangHomepage = cSecurity::toInteger($db->f('startidartlang'));

        // Get idart of homepage
        $sql = "SELECT `idart` FROM `%s` WHERE `idartlang` = %d";
        $db->query($sql, cDb::getTableName('art_lang'), $iIdArtLangHomepage);
        $iIdArtHomepage = $db->nextRecord() ? cSecurity::toInteger($db->f('idart')) : 0;

        $t1 = cDb::getTableName('meta_tag');
        $t2 = cDb::getTableName('meta_type');

        $sql = "SELECT " . $t1 . ".metavalue," . $t2 . ".metatype FROM " . $t1 . " INNER JOIN " . $t2 . " ON " . $t1 . ".idmetatype = " . $t2 . ".idmetatype WHERE " . $t1 . ".idartlang =" . $iIdArtLangHomepage . " ORDER BY " . $t2 . ".metatype";

        $db->query($sql);

        while ($db->nextRecord()) {
            $arrHomepageMetaTags[$db->f('metatype')] = $db->f('metavalue');
        }

        $oArt = new cApiArticleLanguage();
        $oArt->loadByArticleAndLanguageId($iIdArtHomepage, $lang);

        $arrHomepageMetaTags['pagetitle'] = $oArt->getField('title');
    }

    // Cycle through all meta-tags
    foreach ($availableTags as $key => $value) {
        $metavalue = conGetMetaValue($idartlang, $key);

        if (cString::getStringLength($metavalue) == 0) {
            // Add values for meta-tags that don't have a value in the current
            // article
            switch (cString::toLowerCase($value['metatype'])) {
                case 'author':
                    // Build author metatag from name of last modifier
                    $oArt = new cApiArticleLanguage();
                    $oArt->loadByArticleAndLanguageId($idart, $lang);

                    $lastModifier = $oArt->getField('modifiedby');
                    $oUser = new cApiUser(md5($lastModifier));
                    $lastModifierName = $oUser->getRealName();

                    $iCheck = checkIfMetaTagExists($metaTags, 'author');
                    $metaTags[$iCheck]['name'] = 'author';
                    $metaTags[$iCheck]['content'] = $lastModifierName;

                    break;
                case 'description':
                    // Build description metatag from first headline on page
                    $iCheck = checkIfMetaTagExists($metaTags, 'description');
                    $metaTags[$iCheck]['name'] = 'description';
                    $metaTags[$iCheck]['content'] = $sHeadline;

                    break;
                case 'keywords':
                    $iCheck = checkIfMetaTagExists($metaTags, 'keywords');
                    $metaTags[$iCheck]['name'] = 'keywords';
                    $metaTags[$iCheck]['content'] = $sText;

                    break;
                case 'revisit-after':
                case 'robots':
                case 'expires':
                    // Build these 3 meta-tags from entries in homepage
                    $sCurrentTag = isset($value['name']) ? cString::toLowerCase($value['name']) : '';
                    $iCheck = checkIfMetaTagExists($metaTags, $sCurrentTag);
                    if ($sCurrentTag != '' && $arrHomepageMetaTags[$sCurrentTag] != '') {
                        $metaTags[$iCheck]['name'] = $sCurrentTag;
                        $metaTags[$iCheck]['content'] = $arrHomepageMetaTags[$sCurrentTag];
                    }

                    break;
            }
        }
    }

    return $metaTags;
}

/**
 * Checks if the metatag already exists inside the metatag list.
 *
 * @param array|mixed $metaTags List of meta-tags or not a list
 * @param string $checkForMetaTag The metatag to check
 * @return int Position of metatag inside the metatag list or the next available position
 * TODO: Remove this function from global scope, it meant to be used only in `cecCreateMetatags()`.
 */
function checkIfMetaTagExists($metaTags, $checkForMetaTag): int
{
    if (!is_array($metaTags) || count($metaTags) == 0) {
        // metatag list ist not set or empty, return initial position
        return 0;
    }

    // loop through existing meta-tags and check against the list-item name
    foreach ($metaTags as $pos => $item) {
        if (isset($item['name']) && $item['name'] == $checkForMetaTag && $item['name'] != '') {
            // metatag found -> return the position
            return $pos;
        }
    }

    // metatag doesn't exist, return next position
    return count($metaTags);
}
