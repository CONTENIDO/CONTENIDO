<?php

declare(strict_types=1);

/**
 * AMR functions utility class.
 * Functions are ported from the functions file `__THIS_PLUGIN_PATH__/includes/functions.mod_rewrite.php`.
 *
 * @package    Plugin
 * @subpackage ModRewrite
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 * @since      Advanced Mod Rewrite 2.1.0
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * AMR functions utility class.
 *
 * @package    Plugin
 * @subpackage ModRewrite
 */
class PiModRewriteUtil
{
    /**
     * Processes the mod_rewrite related job for a new created tree.
     * Will be called by the chain 'Contenido.Action.str_newtree.AfterCall'.
     *
     * @param array $data Associative array with some values
     * @return array Passed parameter
     * @throws cDbException|cInvalidArgumentException
     */
    public static function strNewTree(array $data): array
    {
        $languageId = cRegistry::getLanguageId();

        PiModRewriteDebugger::log($data, __METHOD__ . ' $data');

        if (cSecurity::toInteger($data['newcategoryid']) > 0) {
            $mrCatAlias = trim($data['categoryalias']) !== '' ? trim($data['categoryalias']) : trim($data['categoryname']);
            // set new urlname - because original set urlname isn't validated for double entries in the same parent category
            PiModRewrite::setCatWebsafeName($mrCatAlias, cSecurity::toInteger($data['newcategoryid']), $languageId);
            PiModRewrite::setCatUrlPath(cSecurity::toInteger($data['newcategoryid']), $languageId);
        }

        return $data;
    }

    /**
     * Processes the mod_rewrite related job for created new category.
     * Will be called by the chain 'Contenido.Action.str_newcat.AfterCall'.
     *
     * @param array $data Associative array with some values
     * @return array Passed parameter
     * @throws cDbException|cInvalidArgumentException
     */
    public static function strNewCategory(array $data): array
    {
        $languageId = cRegistry::getLanguageId();

        PiModRewriteDebugger::log($data, __METHOD__ . ' $data');

        if (cSecurity::toInteger($data['newcategoryid']) > 0) {
            $mrCatAlias = trim($data['categoryalias']) !== '' ? trim($data['categoryalias']) : trim($data['categoryname']);
            // set new urlname - because original set urlname isn't validated for double entries in the same parent category
            PiModRewrite::setCatWebsafeName($mrCatAlias, cSecurity::toInteger($data['newcategoryid']), $languageId);
            PiModRewrite::setCatUrlPath(cSecurity::toInteger($data['newcategoryid']), $languageId);
        }

        return $data;
    }

    /**
     * Processes the mod_rewrite related job for renamed category
     * 2010-02-01: and now all existing subcategories and modify their paths too...
     * 2010-02-01: max 50 recursion levels
     *
     * Will be called by the chain 'Contenido.Action.str_renamecat.AfterCall'.
     *
     * @param array $data Associative array with some values
     * @return array Passed parameter
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public static function strRenameCategory(array $data): array
    {
        PiModRewriteDebugger::log($data, __METHOD__ . ' $data');

        // hes 20100102
        // maximal 50 recursion level
        $recursion = cSecurity::toInteger($data['recursion'] ?? '1');
        if ($recursion > 50) {
            exit("#20100201-1503: sorry - maximum function nesting level of " . $recursion . " reached");
        }

        $mrCatAlias = trim($data['newcategoryalias']) !== '' ? trim($data['newcategoryalias']) : trim($data['newcategoryname']);
        if ($mrCatAlias != '') {
            // set new urlname - because original set urlname isn't validated for double entries in the same parent category
            PiModRewrite::setCatWebsafeName($mrCatAlias, cSecurity::toInteger($data['idcat']), cSecurity::toInteger($data['lang']));
            PiModRewrite::setCatUrlPath(cSecurity::toInteger($data['idcat']), cSecurity::toInteger($data['lang']));
        }

        // hes 20100102
        // now dive into all existing subcategories and modify their paths too...
        $oCatColl = new cApiCategoryCollection('`parentid` = ' . $data['idcat']);

        while ($oCat = $oCatColl->next()) {
            // hes 20100102
            $oCatLanColl = new cApiCategoryLanguageCollection(
                '`idcat` = ' . $oCat->get('idcat') . ' AND `idlang` = ' . cSecurity::toInteger($data['lang'])
            );
            if ($oCatLan = $oCatLanColl->next()) {
                // hes 20100102
                $childData = [
                    'idcat' => $oCat->get('idcat'),
                    'lang' => cSecurity::toInteger($data['lang']),
                    'newcategoryname' => $oCatLan->get('name'),
                    'newcategoryalias' => $oCatLan->get('urlname'),
                    'recursion' => $recursion + 1
                ];

                self::strRenameCategory($childData);
            }
        }

        return $data;
    }

    /**
     * Processes the mod_rewrite-related job after moving a category up.
     * Will be called by the chain 'Contenido.Action.str_moveupcat.AfterCall'.
     *
     * @param int $categoryId Category id
     * @return int|void Category id
     * @throws cDbException|cInvalidArgumentException|cException
     * @todo  do we really need processing of the category? there is no mr relevant data
     *        changes while moving the category on the same level, level and name won't change
     */
    public static function strMoveUpCategory(int $categoryId)
    {
        PiModRewriteDebugger::log($categoryId, __METHOD__ . ' $categoryId');

        // category check
        $cat = new cApiCategory($categoryId);
        if (!$cat->get('preid')) {
            return;
        }

        // get all cat languages
        $aIdLang = PiModRewrite::getCatLanguages($categoryId);

        // update ...
        foreach ($aIdLang as $iIdLang) {
            $iIdLang = cSecurity::toInteger($iIdLang);
            // get urlname
            $categoryName = PiModRewrite::getCatName($categoryId, $iIdLang);
            // set new urlname - because original set urlname isn't validated for double entries in the same parent category
            PiModRewrite::setCatWebsafeName($categoryName, $categoryId, $iIdLang);
        }

        return $categoryId;
    }

    /**
     * Processes the mod_rewrite related job after moving a category down.
     *
     * Will be called by the chain 'Contenido.Action.str_movedowncat.AfterCall'.
     *
     * @param int $categoryId Id of category being moved down
     * @return int|void Category id
     * @throws cDbException|cInvalidArgumentException|cException
     * @todo Do we really need processing of the category? there is no mr relevant data
     *       changes while moving the category on the same level, level and name won't change
     */
    public static function strMovedownCategory(int $categoryId)
    {
        PiModRewriteDebugger::log($categoryId, __METHOD__ . ' $categoryId');

        // category check
        $cat = new cApiCategory($categoryId);
        if (!$cat->get('id')) {
            return;
        }

        // get all cat languages
        $languageIds = PiModRewrite::getCatLanguages($categoryId);

        // update ...
        foreach ($languageIds as $languageId) {
            // get urlname
            $categoryName = PiModRewrite::getCatName($categoryId, $languageId);
            // set new urlname - because original set urlname isn't validated for double entries in the same parent category
            PiModRewrite::setCatWebsafeName($categoryName, $categoryId, $languageId);
        }

        return $categoryId;
    }

    /**
     * Processes the mod_rewrite related job after moving a category subtree.
     * Will be called by the chain 'Contenido.Action.str_movesubtree.AfterCall'.
     *
     * @param array $data Associative array with some values
     * @return ?array Passed parameter
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public static function strMoveSubtree(array $data): ?array
    {
        PiModRewriteDebugger::log($data, __METHOD__ . ' $data');

        // category check
        if (cSecurity::toInteger($data['idcat']) <= 0) {
            return null;
        }

        // next category check
        $cat = new cApiCategory($data['idcat']);
        if (!$cat->get('idcat')) {
            return null;
        }

        // get all cat languages
        $languageIds = PiModRewrite::getCatLanguages($data['idcat']);

        // update all languages
        foreach ($languageIds as $languageId) {
            // get urlname
            $categoryName = PiModRewrite::getCatName(cSecurity::toInteger($data['idcat']), $languageId);
            // set new urlname - because original set urlname isn't validated for double entries in the same parent category
            PiModRewrite::setCatWebsafeName($categoryName, cSecurity::toInteger($data['idcat']), $languageId);
            PiModRewrite::setCatUrlPath(cSecurity::toInteger($data['idcat']), $languageId);
        }

        // now dive into all existing subcategories and modify their paths too...
        $categoryCollection = new cApiCategoryCollection('`parentid` = ' . $data['idcat']);
        while ($item = $categoryCollection->next()) {
            self::strMoveSubtree(['idcat' => cSecurity::toInteger($item->get('idcat'))]);
        }

        return $data;
    }

    /**
     * Processes the mod_rewrite related job after copying a category subtree.
     * Will be called by the chain 'Contenido.Category.strCopyCategory'.
     *
     * @param array $data Associative array with some values
     * @return ?array  Passed parameter
     * @throws cDbException|cInvalidArgumentException
     */
    public static function strCopyCategory(array $data): ?array
    {
        PiModRewriteDebugger::log($data, __METHOD__ . ' $data');

        $categoryId = (int)$data['newcat']->get('idcat');
        if ($categoryId <= 0) {
            return $data;
        }

        // get all cat languages
        $languageIds = PiModRewrite::getCatLanguages($categoryId);

        // update ...
        foreach ($languageIds as $languageId) {
            // get urlname
            $categoryName = PiModRewrite::getCatName($categoryId, $languageId);
            // set new urlname - because original set urlname isn't validated for double entries in the same parent category
            PiModRewrite::setCatWebsafeName($categoryName, $categoryId, $languageId);
            PiModRewrite::setCatUrlPath($categoryId, $languageId);
        }

        return null;
    }

    /**
     * Processes the mod_rewrite related job during the structure synchronization process,
     * sets the urlpath of the current category.
     * Will be called by the chain 'Contenido.Category.strSyncCategory_Loop'.
     *
     * @param array $data Associative array with some values
     * @return array Passed parameter
     * @throws cDbException|cInvalidArgumentException
     */
    public static function strSyncCategory(array $data): array
    {
        PiModRewriteDebugger::log($data, __METHOD__ . ' $data');
        PiModRewrite::setCatUrlPath(cSecurity::toInteger($data['idcat']), cSecurity::toInteger($data['idlang']));

        return $data;
    }

    /**
     * Processes the mod_rewrite related job for saved articles (new or modified article).
     * Will be called by the chain 'Contenido.Action.con_saveart.AfterCall'.
     *
     * @param array $data Associative array with some article properties
     * @return array Passed parameter
     * @throws cDbException|cInvalidArgumentException
     */
    public static function conSaveArticle(array $data): array
    {
        global $tmp_firstedit;

        PiModRewriteDebugger::log($data, __METHOD__ . ' $data');

        if (cSecurity::toInteger($data['idart']) == 0) {
            return $data;
        }

        if (cString::getStringLength(trim($data['urlname'])) == 0) {
            $data['urlname'] = $data['title'];
        }

        if ($tmp_firstedit == 1) {
            // new article
            $languageIds = (new cApiClientLanguageCollection())->getLanguagesByClient(cRegistry::getClientId());

            foreach ($languageIds as $languageId) {
                PiModRewrite::setArtWebsafeName(
                    cSecurity::toString($data['urlname']),
                    cSecurity::toInteger($data['idart']),
                    cSecurity::toInteger($languageId),
                    cSecurity::toInteger($data['idcat'])
                );
            }
        } else {
            // modified article
            $aArticle = PiModRewrite::getArtIdByArtlangId(cSecurity::toInteger($data['idartlang']));

            if (isset($aArticle['idart']) && isset($aArticle['idlang'])) {
                PiModRewrite::setArtWebsafeName(
                    cSecurity::toString($data['urlname']),
                    cSecurity::toInteger($aArticle['idart']),
                    cSecurity::toInteger($aArticle['idlang']),
                    cSecurity::toInteger($data['idcat'])
                );
            }
        }

        return $data;
    }

    /**
     * Processes the mod_rewrite related job for articles being moved.
     * Will be called by the chain 'Contenido.Article.conMoveArticles_Loop'.
     *
     * @param array|mixed $data Associative array with record entries
     * @return array|mixed Loop through of arguments
     * @throws cDbException|cInvalidArgumentException
     */
    public static function conMoveArticles($data)
    {
        PiModRewriteDebugger::log($data, __METHOD__ . ' $data');

        // too defensive but secure way
        if (!is_array($data)) {
            return $data;
        } elseif (!isset($data['idartlang'])) {
            return $data;
        } elseif (!isset($data['idart'])) {
            return $data;
        }

        $articleData = PiModRewrite::getArtIds(cSecurity::toInteger($data['idartlang']));
        if (count($articleData) == 2) {
            PiModRewrite::setArtWebsafeName(
                cSecurity::toString($articleData['urlname']),
                cSecurity::toInteger($data['idart']),
                cSecurity::toInteger($articleData['idlang'])
            );
        }

        return $data;
    }

    /**
     * Processes the mod_rewrite related job for duplicated articles.
     * Will be called by the chain 'Contenido.Article.conCopyArtLang_AfterInsert'.
     *
     * @param array|mixed $data Associative array with record entries
     * @return array|mixed Loop through of arguments
     * @throws cDbException|cInvalidArgumentException
     */
    public static function conCopyArtLang($data)
    {
        PiModRewriteDebugger::log($data, __METHOD__ . ' $data');

        // too defensive but secure way
        if (!is_array($data)) {
            return $data;
        } elseif (!isset($data['title'])) {
            return $data;
        } elseif (!isset($data['idart'])) {
            return $data;
        } elseif (!isset($data['idlang'])) {
            return $data;
        }

        PiModRewrite::setArtWebsafeName(
            cSecurity::toString($data['title']),
            cSecurity::toInteger($data['idart']),
            cSecurity::toInteger($data['idlang'])
        );

        return $data;
    }

    /**
     * Processes the mod_rewrite related job for synchronized articles.
     * Will be called by the chain 'Contenido.Article.conSyncArticle_AfterInsert'.
     *
     * @param array|mixed $data Associative array with record entries as follows:
     *      <code>
     *      [
     *          'src_art_lang' => Recordset (associative array) of source item from con_art_lang table
     *          'dest_art_lang' => Recordset (associative array) of inserted destination item from con_art_lang table
     *      ]
     *      </code>
     * @return array|mixed Loop through of argument
     * @throws cDbException|cInvalidArgumentException|cException
     */
    public static function conSyncArticle($data)
    {
        PiModRewriteDebugger::log($data, __METHOD__ . ' $data');

        // too defensive but secure way
        if (!is_array($data)) {
            return $data;
        } elseif (!isset($data['src_art_lang']) || !is_array($data['src_art_lang'])) {
            return $data;
        } elseif (!isset($data['dest_art_lang']) || !is_array($data['dest_art_lang'])) {
            return $data;
        } elseif (!isset($data['dest_art_lang']['idart'])) {
            return $data;
        } elseif (!isset($data['dest_art_lang']['idlang'])) {
            return $data;
        }

        if (!isset($data['src_art_lang']['urlname'])) {
            $artLang = new cApiArticleLanguage($data['src_art_lang']['idartlang']);
            $urlname = $artLang->get('urlname');
        } else {
            $urlname = $data['src_art_lang']['urlname'];
        }

        if ($urlname) {
            PiModRewrite::setArtWebsafeName(
                $urlname,
                cSecurity::toInteger($data['dest_art_lang']['idart']),
                cSecurity::toInteger($data['dest_art_lang']['idlang'])
            );
        }

        return $data;
    }

    /**
     * Works as a wrapper for Contenido_Url.
     * Will also be called by the chain 'Contenido.Frontend.CreateURL'.
     *
     * @param string $url URL to rebuild
     * @return string New URL
     * @throws cInvalidArgumentException|cException|cDbException
     * @todo: Still exists because of downwards compatibility (some other modules/plugins are using it)
     */
    public static function buildNewUrl(string $url): string
    {
        PiModRewriteDebugger::add($url, __METHOD__ . ' in -> $url');

        $languageId = cRegistry::getLanguageId();
        $oUrl = cUri::getInstance();
        $aUrl = $oUrl->parse($url);

        // add language, if not exists
        if (!isset($aUrl['params']['lang'])) {
            $aUrl['params']['lang'] = $languageId;
        }

        // build url
        $newUrl = $oUrl->build($aUrl['params']);

        // add existing fragment
        if (isset($aUrl['fragment'])) {
            $newUrl .= '#' . $aUrl['fragment'];
        }

        $arr = [
            'in' => $url,
            'out' => $newUrl,
        ];
        PiModRewriteDebugger::add($arr, __METHOD__ . ' out -> $arr');

        return $newUrl;
    }

    /**
     * Replaces existing anchors inside the passed code while rebuilding the urls.
     * Will be called by the chain 'Contenido.Content.conGenerateCode' or
     * 'Contenido.Frontend.HTMLCodeOutput' depending on mod_rewrite settings.
     *
     * @param string $code Code to prepare
     * @return string New code
     * @throws cInvalidArgumentException|cException|cDbException
     */
    public static function buildGeneratedCode(string $code): string
    {
        PiModRewriteDebugger::add($code, __METHOD__ . ' in');

        $sseStartTime = getmicrotime();

        // mod rewrite is activated
        if (PiModRewrite::isEnabled()) {
            // anchor hack
            $code = preg_replace_callback("/<a([^>]*)href\s*=\s*[\"|\'][\/]#(.?|.+?)[\"|\']([^>]*)>/i", function ($match) {
                return PiModRewrite::rewriteHtmlAnchor($match);
            }, $code);

            // remove tinymce single quote entities:
            $code = str_replace("&#39;", "'", $code);

            // == IE hack with wrong base href interpretation
            // get base uri
            // $sBaseUri = cRegistry::getFrontendUrl();
            // $sBaseUri = cApiCecHook::execute('Contenido.Frontend.BaseHrefGeneration', $sBaseUri);
            // $code = preg_replace("/([\"|\'|=])upload\/(.?|.+?)([\"|\'|>])/ie", "stripslashes('\\1{$sBaseUri}upload/\\2\\3')", $code);

            $baseUri = cRegistry::getFrontendUrl();
            $baseUri = cApiCecHook::executeAndReturn('Contenido.Frontend.BaseHrefGeneration', $baseUri);

            // CON-1389 modifier /e is deprecated as of PHP 5.5
            $code = preg_replace_callback("/([\"|\'|=])upload\/(.?|.+?)([\"|\'|>])/i", function ($match) use ($baseUri) {
                return stripslashes($match[1] . $baseUri . 'upload/' . $match[2] . $match[3]);
            }, $code);

            // define some preparations to replace /front_content.php & ./front_content.php
            // against front_content.php, because urls should start with front_content.php
            $aPattern = [
                '/([\"|\'|=])\/front_content\.php(.?|.+?)([\"|\'|>])/i',
                '/([\"|\'|=])\.\/front_content\.php(.?|.+?)([\"|\'|>])/i'
            ];

            $aReplace = [
                '\1front_content.php\2\3',
                '\1front_content.php\2\3'
            ];

            // perform the pre-replacements
            $code = preg_replace($aPattern, $aReplace, $code);

            // create url stack object and fill it with found urls...
            $oMRUrlStack = PiModRewriteUrlStackService::getInstance();
            $oMRUrlStack->add('front_content.php');

            $matches = NULL;
            preg_match_all("/([\"|\'|=])front_content\.php(.?|.+?)([\"|\'|>])/i", $code, $matches, PREG_SET_ORDER);
            foreach ($matches as $val) {
                $oMRUrlStack->add('front_content.php' . $val[2]);
            }

            // ok let it beginn, start mod rewrite class
            $code = str_replace('"front_content.php"', '"' . self::buildNewUrl('front_content.php') . '"', $code);
            $code = str_replace("'front_content.php'", "'" . self::buildNewUrl('front_content.php') . "'", $code);
            $code = preg_replace_callback("/([\"|\'|=])front_content\.php(.?|.+?)([\"|\'|>])/i", function ($match) {
                return $match[1] . self::buildNewUrl('front_content.php' . $match[2]) . $match[3];
            }, $code);

            PiModRewriteDebugger::add($code, __METHOD__ . ' out');

        } else {
            // anchor hack for non mod_rewrite websites
            $code = preg_replace_callback("/<a([^>]*)href\s*=\s*[\"|\'][\/]#(.?|.+?)[\"|\']([^>]*)>/i", function ($match) {
                return PiModRewrite::contenidoHtmlAnchor($match, cSecurity::toBoolean($GLOBALS['is_XHTML']));
            }, $code);
        }

        $sseEndTime = getmicrotime();

        PiModRewriteDebugger::add(($sseEndTime - $sseStartTime), __METHOD__ . ' total spend time');

        if ($debug = PiModRewriteDebugger::output(false)) {
            $code = cString::iReplaceOnce("</body>", $debug . "\n</body>", $code);
        }

        return $code;
    }

    /**
     * Sets language of the client, like done in front_content.php
     *
     * @param int $clientId Client id
     * @throws cDbException
     */
    public static function setClientLanguageId(int $clientId)
    {
        // NOTE: Use globals here!
        global $lang;

        if (cSecurity::toInteger($lang) > 0) {
            // there is nothing to do
            return;
        } elseif (cRegistry::getLoadLanguageId()) {
            // use the first language of this client
            $lang = cRegistry::getLoadLanguageId();
            return;
        }

        // Search for the first language of this client
        $languageId = (new cApiClientLanguageCollection())->getFirstLanguageIdByClient($clientId);
        if ($languageId) {
            $lang = $languageId;
        }
    }

    /**
     * Includes the frontend controller script which parses the url and extracts
     * the necessary data like idcat, idart, lang and client from it.
     * Will be called by the chain 'Contenido.Frontend.AfterLoadPlugins' at front_content.php.
     *
     * @return bool Just a return value
     * @throws cInvalidArgumentException
     */
    public static function runFrontendController(): bool
    {
        $startTime = getmicrotime();

        plugin_include('mod_rewrite', 'includes/config.plugin.php');

        if (PiModRewrite::isEnabled()) {
            plugin_include('mod_rewrite', 'includes/front_content_controller.php');

            $totalTime = sprintf('%.4f', (getmicrotime() - $startTime));
            PiModRewriteDebugger::add($totalTime, __METHOD__ . ' total time');
        }

        return true;
    }

    /**
     * Cleanups passed string from characters being repeated two or more times
     *
     * @param string $char Character to remove
     * @param string $string String to clean from character
     * @return string Cleaned string
     */
    public static function removeMultipleChars(string $char, string $string): string
    {
        while (cString::findFirstPos($string, $char . $char) !== false) {
            $string = str_replace($char . $char, $char, $string);
        }

        return $string;
    }

    /**
     * Returns the value of an array key (associative or indexed).
     *
     * Shortcut function for some ways to access to arrays:
     * <code>
     * // old way
     * if (is_array($foo) && isset($foo['bar']) && $foo['bar'] == 'yieeha') {
     *     // do something
     * }
     *
     * // new, more readable way:
     * if (PiModRewriteUtil::arrayValue($foo, 'bar') == 'yieeha') {
     *     // do something
     * }
     *
     * // old way
     * if (is_array($foo) && isset($foo['bar'])) {
     *     $jep = $foo['bar'];
     * } else {
     *     $jep = 'yummy';
     * }
     *
     * // new way
     * $jep = PiModRewriteUtil::arrayValue($foo, 'bar', 'yummy');
     * </code>
     *
     * @param array|mixed $array The array
     * @param mixed $key Position of an indexed array or key of an associative array
     * @param mixed $default Default value to return
     * @return mixed Either the found value or the default value
     */
    public static function arrayValue($array, $key, $default = NULL)
    {
        if (!is_array($array)) {
            return $default;
        } elseif (!isset($array[$key])) {
            return $default;
        } else {
            return $array[$key];
        }
    }

    /**
     * Replaces calling of the header method for redirects in front_content.php,
     * used during development.
     *
     * @param string $header Header value for redirect
     */
    public static function responseHeader(string $header)
    {
        header($header);

        // $header = str_replace('Location: ', '', $header);
        // echo '<html>
        //     <head></head>
        //     <body>
        //     <p><a href="' . $header . '">' . $header . '</a></p>';
        // PiModRewriteDebugger::output();
        // echo '</body></html>';
        // exit();
    }

}
