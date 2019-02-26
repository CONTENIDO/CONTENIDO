<?php

/**
 * CONTENIDO Chain.
 * Generate metatags for current article if they are not set in article
 * properties
 *
 * @package          Core
 * @subpackage       Chain
 * @author           Andreas Lindner
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('plugins', 'repository/keyword_density.php');

/**
 *
 * @param array $metatags
 *
 * @return array
 *
 * @throws cDbException
 * @throws cException
 */
function cecCreateMetatags($metatags) {
    global $cfg, $lang, $idart, $client, $cfgClient, $idcat, $idartlang;

    // (Re)build metatags
    $db = cRegistry::getDb();

    // Get encoding
    $oLang = new cApiLanguage((int) $lang);
    if ($oLang->get('encoding')) {
        $sEncoding = cString::toUpperCase($oLang->get('encoding'));
    } else {
        $sEncoding = 'ISO-8859-1';
    }

    // Get idcat of homepage
    $sql = "SELECT a.idcat
        FROM
            " . $cfg['tab']['cat_tree'] . " AS a,
            " . $cfg['tab']['cat_lang'] . " AS b
        WHERE
            (a.idcat = b.idcat) AND
            (b.visible = 1) AND
            (b.idlang = " . (int) $lang . ")
        ORDER BY a.idtree LIMIT 1";

    $db->query($sql);

    if ($db->next_record()) {
        $idcat_homepage = $db->f('idcat');
    }

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
        $arrHead1 = array();
    }

    if (!is_array($arrHead2)) {
        $arrHead2 = array();
    }

    $arrHeadlines = array_merge($arrHead1, $arrHead2);

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
        $arrText1 = array();
    }

    if (!is_array($arrText2)) {
        $arrText2 = array();
    }

    $arrText = array_merge($arrText1, $arrText2);

    foreach ($arrText as $key => $value) {
        if ($value != '') {
            $sText = $value;
            break;
        }
    }

    $sText = strip_tags(urldecode($sText));
    $sText = keywordDensity('', $sText);

    // Get metatags for homeapge
    $arrHomepageMetaTags = array();

    $sql = "SELECT startidartlang FROM " . $cfg['tab']['cat_lang'] . " WHERE (idcat=" . (int) $idcat_homepage . ") AND(idlang=" . (int) $lang . ")";
    $db->query($sql);

    if ($db->next_record()) {
        $iIdArtLangHomepage = $db->f('startidartlang');

        // Get idart of homepage
        $sql = "SELECT idart FROM " . $cfg['tab']['art_lang'] . " WHERE idartlang=" . (int) $iIdArtLangHomepage;
        $db->query($sql);
        if ($db->next_record()) {
            $iIdArtHomepage = $db->f('idart');
        }

        $t1 = $cfg['tab']['meta_tag'];
        $t2 = $cfg['tab']['meta_type'];

        $sql = "SELECT " . $t1 . ".metavalue," . $t2 . ".metatype FROM " . $t1 . " INNER JOIN " . $t2 . " ON " . $t1 . ".idmetatype = " . $t2 . ".idmetatype WHERE " . $t1 . ".idartlang =" . $iIdArtLangHomepage . " ORDER BY " . $t2 . ".metatype";

        $db->query($sql);

        while ($db->next_record()) {
            $arrHomepageMetaTags[$db->f('metatype')] = $db->f('metavalue');
        }

        $oArt = new cApiArticleLanguage();
        $oArt->loadByArticleAndLanguageId($iIdArtHomepage, $lang);

        $arrHomepageMetaTags['pagetitle'] = $oArt->getField('title');
    }

    // Cycle through all metatags
    foreach ($availableTags as $key => $value) {
        $metavalue = conGetMetaValue($idartlang, $key);

        if (cString::getStringLength($metavalue) == 0) {
            // Add values for metatags that don't have a value in the current
            // article
            switch (cString::toLowerCase($value['metatype'])) {
                case 'author':
                    // Build author metatag from name of last modifier
                    $oArt = new cApiArticleLanguage();
                    $oArt->loadByArticleAndLanguageId($idart, $lang);

                    $lastmodifier = $oArt->getField('modifiedby');
                    $oUser = new cApiUser(md5($lastmodifier));
                    $lastmodifier_real = $oUser->getRealName();

                    $iCheck = CheckIfMetaTagExists($metatags, 'author');
                    $metatags[$iCheck]['name'] = 'author';
                    $metatags[$iCheck]['content'] = $lastmodifier_real;

                    break;
                case 'description':
                    // Build description metatag from first headline on page
                    $iCheck = CheckIfMetaTagExists($metatags, 'description');
                    $metatags[$iCheck]['name'] = 'description';
                    $metatags[$iCheck]['content'] = $sHeadline;

                    break;
                case 'keywords':
                    $iCheck = CheckIfMetaTagExists($metatags, 'keywords');
                    $metatags[$iCheck]['name'] = 'keywords';
                    $metatags[$iCheck]['content'] = $sText;

                    break;
                case 'revisit-after':
                case 'robots':
                case 'expires':
                    // Build these 3 metatags from entries in homepage
                    $sCurrentTag = cString::toLowerCase($value['name']);
                    $iCheck = CheckIfMetaTagExists($metatags, $sCurrentTag);
                    if($sCurrentTag != '' && $arrHomepageMetaTags[$sCurrentTag] != "") {
                        $metatags[$iCheck]['name'] = $sCurrentTag;
                        $metatags[$iCheck]['content'] = $arrHomepageMetaTags[$sCurrentTag];
                    }

                    break;
            }
        }
    }

    return $metatags;
}

/**
 * Checks if the metatag allready exists inside the metatag list.
 *
 * @param array|mixed $arrMetatags
 *         List of metatags or not a list
 * @param string $sCheckForMetaTag
 *         The metatag to check
 * @return int
 *         Position of metatag inside the metatag list or the next available position
 */
function CheckIfMetaTagExists($arrMetatags, $sCheckForMetaTag) {
    if (!is_array($arrMetatags) || count($arrMetatags) == 0) {
        // metatag list ist not set or empty, return initial position
        return 0;
    }

    // loop thru existing metatags and check against the listitem name
    foreach ($arrMetatags as $pos => $item) {
        if ($item['name'] == $sCheckForMetaTag && $item['name'] != '') {
            // metatag found -> return the position
            return $pos;
        }
    }

    // metatag doesn't exists, return next position
    return count($arrMetatags);
}

?>