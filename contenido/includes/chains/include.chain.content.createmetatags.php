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
 * Chain function
 * @throws cDbException|cException
 */
function cecCreateMetatags(array $metaTags): array
{
    // Function to retrieve the first value from two contents
    $getValueFromContentsFn = function($content1, $content2): string
    {
        if (!is_array($content1)) {
            $content1 = [];
        }

        if (!is_array($content2)) {
            $content2 = [];
        }

        $content = array_merge($content1, $content2);
        $returnValue = '';

        foreach ($content as $value) {
            if ($value != '') {
                $returnValue = $value;
                break;
            }
        }

        return strip_tags($returnValue);
    };

    // (Re)build meta-tags

    $db = cRegistry::getDb();
    $lang = cRegistry::getLanguageId();
    $idart = cRegistry::getArticleId();
    $idartlang = cRegistry::getArticleLanguageId();

    // Get idcat of homepage
    $idCatHomepage = cSecurity::toInteger(getEffectiveSetting('navigation', 'idcat-home', '0'));

    // Get first headline and first text for current article
    // @todo use this cApiArticleLanguage instance in code below, instead of
    // creating it again and again!
    $articleLanguage = new cApiArticleLanguage();
    $articleLanguage->loadByArticleAndLanguageId($idart, $lang);

    // Set idartlang, if not set
    if ($idartlang == '') {
        $idartlang = $articleLanguage->getField('idartlang');
    }

    $sHeadline = $getValueFromContentsFn(
        $articleLanguage->getContent('htmlhead'),
        $articleLanguage->getContent('head')
    );
    $sHeadline = cString::getPartOfString(str_replace("\r\n", ' ', $sHeadline), 0, 100);

    $sText = $getValueFromContentsFn(
        $articleLanguage->getContent('html'),
        $articleLanguage->getContent('text')
    );
    $sText = pirekd_keywordDensity('', $sText);

    $metaTypeService = new cMetaTypeService();
    $metaTypes = $metaTypeService->getAll();

    // Get meta-tags for homepage
    $arrHomepageMetaTags = [];

    $sql = "SELECT `startidartlang` FROM `%s` WHERE `idcat` = %d AND `idlang` = %d";
    $db->query($sql, cDb::getTableName('cat_lang'), $idCatHomepage, $lang);

    if ($db->nextRecord()) {
        $iIdArtLangHomepage = cSecurity::toInteger($db->f('startidartlang'));

        $metaTagItems = (new cApiMetaTagCollection())
            ->fetchByArticleLanguageId(cSecurity::toInteger($iIdArtLangHomepage));

        foreach ($metaTypes as $metaType) {
            $metaTag = cMetaTypeService::findMetaTagByMetaType($metaType, $metaTagItems);
            if ($metaTag) {
                $arrHomepageMetaTags[$metaType->get('metatype')] = $metaTag->get('metavalue');
            }
        }

        $articleLanguage->loadByPrimaryKey($iIdArtLangHomepage);

        $arrHomepageMetaTags['pagetitle'] = $articleLanguage->getField('title');
    }

    $metaTagItems = (new cApiMetaTagCollection())
        ->fetchByArticleLanguageId(cSecurity::toInteger($idartlang));

    // Cycle through all meta-tags
    foreach ($metaTypes as $metaType) {
        $metaTag = cMetaTypeService::findMetaTagByMetaType($metaType, $metaTagItems);
        if ($metaTag && cString::getStringLength($metaTag->get('metavalue') ?? '') > 0) {
            continue;
        }

        // Add values for meta-tags that don't have a value in the current article
        switch (cString::toLowerCase($metaType->get('metatype'))) {
            case 'author':
                // Build author metatag from name of last modifier
                $articleLanguage->loadByArticleAndLanguageId($idart, $lang);

                $lastModifier = $articleLanguage->getField('modifiedby');
                $user = $lastModifier ? new cApiUser(md5($lastModifier)) : null;
                $lastModifierName = $user ? $user->getRealName() : '';

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
                $sCurrentTag = $metaTag && cString::toLowerCase($metaTag->get('metavalue') ?? '');
                $iCheck = checkIfMetaTagExists($metaTags, $sCurrentTag);
                if ($sCurrentTag != '' && !empty($arrHomepageMetaTags[$sCurrentTag]) != '') {
                    $metaTags[$iCheck]['name'] = $sCurrentTag;
                    $metaTags[$iCheck]['content'] = $arrHomepageMetaTags[$sCurrentTag];
                }

                break;
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
