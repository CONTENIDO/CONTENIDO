<?php
/**
 * Mod Rewrite front_content.php controller. Does some preprocessing jobs, tries
 * to set following variables, depending on mod rewrite configuration and if
 * request part exists:
 * - $client
 * - $changeclient
 * - $lang
 * - $changelang
 * - $idart
 * - $idcat
 *
 * @package     Plugin
 * @subpackage  ModRewrite
 * @id          $Id$:
 * @author      Stefan Seifarth / stese
 * @author      Murat Purc <murat@purc.de>
 * @copyright   www.polycoder.de
 * @copyright   four for business AG <www.4fb.de>
 * @license     http://www.contenido.org/license/LIZENZ.txt
 * @link        http://www.4fb.de
 * @link        http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Processes mod_rewrite related job for created new tree.
 *
 * Will be called by chain 'Contenido.Action.str_newtree.AfterCall'.
 *
 * @param   array $data Assoziative array with some values
 *
 * @return  array  Passed parameter
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function mr_strNewTree(array $data) {
    global $lang;

    ModRewriteDebugger::log($data, 'mr_strNewTree $data');

    if ((int) $data['newcategoryid'] > 0) {
        $mrCatAlias = (trim($data['categoryalias']) !== '') ? trim($data['categoryalias']) : trim($data['categoryname']);
        // set new urlname - because original set urlname isn''t validated for double entries in same parent category
        ModRewrite::setCatWebsafeName($mrCatAlias, $data['newcategoryid'], $lang);
        ModRewrite::setCatUrlPath($data['newcategoryid'], $lang);
    }

    return $data;
}

/**
 * Processes mod_rewrite related job for created new category.
 *
 * Will be called by chain 'Contenido.Action.str_newcat.AfterCall'.
 *
 * @param   array $data Assoziative array with some values
 *
 * @return  array  Passed parameter
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function mr_strNewCategory(array $data) {
    global $lang;

    ModRewriteDebugger::log($data, 'mr_strNewCategory $data');

    if ((int) $data['newcategoryid'] > 0) {
        $mrCatAlias = (trim($data['categoryalias']) !== '') ? trim($data['categoryalias']) : trim($data['categoryname']);
        // set new urlname - because original set urlname isn''t validated for double entries in same parent category
        ModRewrite::setCatWebsafeName($mrCatAlias, $data['newcategoryid'], $lang);
        ModRewrite::setCatUrlPath($data['newcategoryid'], $lang);
    }

    return $data;
}

/**
 * Processes mod_rewrite related job for renamed category
 * 2010-02-01: and now all existing subcategories and modify their paths too...
 * 2010-02-01: max 50 recursion level
 *
 * Will be called by chain 'Contenido.Action.str_renamecat.AfterCall'.
 *
 * @param   array $data Assoziative array with some values
 *
 * @return  array  Passed parameter
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function mr_strRenameCategory(array $data) {
    ModRewriteDebugger::log($data, 'mr_strRenameCategory $data');

    // hes 20100102
    // maximal 50 recursion level
    $recursion = (is_int($data['recursion'])) ? $data['recursion'] : 1;
    if ($recursion > 50) {
        exit("#20100201-1503: sorry - maximum function nesting level of " . $recursion . " reached");
    }

    $mrCatAlias = (trim($data['newcategoryalias']) !== '') ? trim($data['newcategoryalias']) : trim($data['newcategoryname']);
    if ($mrCatAlias != '') {
        // set new urlname - because original set urlname isn''t validated for double entries in same parent category
        ModRewrite::setCatWebsafeName($mrCatAlias, $data['idcat'], $data['lang']);
        ModRewrite::setCatUrlPath($data['idcat'], $data['lang']);
    }

    // hes 20100102
    // now dive into all existing subcategories and modify their paths too...
    $str = 'parentid=' . $data['idcat'];
    $oCatColl = new cApiCategoryCollection($str);

    while ($oCat = $oCatColl->next()) {
        // hes 20100102
        $str = 'idcat=' . $oCat->get('idcat') . ' AND idlang=' . (int) $data['lang'];
        $oCatLanColl = new cApiCategoryLanguageCollection($str);
        if ($oCatLan = $oCatLanColl->next()) {
            // hes 20100102
            $childData = array(
                'idcat' => $oCat->get('idcat'),
                'lang' => (int) $data['lang'],
                'newcategoryname' => $oCatLan->get('name'),
                'newcategoryalias' => $oCatLan->get('urlname'),
                'recursion' => $recursion + 1
            );

            $resData = mr_strRenameCategory($childData);
        }
    }

    return $data;
}

/**
 * Processes mod_rewrite related job after moving a category up.
 *
 * Will be called by chain 'Contenido.Action.str_moveupcat.AfterCall'.
 *
 * @todo  do we really need processing of the category? there is no mr relevant data
 *        changes while moving the category on same level, level and name won't change
 *
 * @param   int $idcat Category id
 *
 * @return  int  Category id
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function mr_strMoveUpCategory($idcat) {
    ModRewriteDebugger::log($idcat, 'mr_strMoveUpCategory $idcat');

    // category check
    $cat = new cApiCategory((int) $idcat);
    if (!$cat->get('preid')) {
        return;
    }

    // get all cat languages
    $aIdLang = ModRewrite::getCatLanguages($idcat);

    // update ...
    foreach ($aIdLang as $iIdLang) {
        // get urlname
        $sCatname = ModRewrite::getCatName($idcat, $iIdLang);
        // set new urlname - because original set urlname isn't validated for double entries in same parent category
        ModRewrite::setCatWebsafeName($sCatname, $idcat, $iIdLang);
    }

    return $idcat;
}

/**
 * Processes mod_rewrite related job after moving a category down.
 *
 * Will be called by chain 'Contenido.Action.str_movedowncat.AfterCall'.
 *
 * @todo  do we really need processing of the category? there is no mr relevant data
 *        changes while moving the category on same level, level and name won't change
 *
 * @param   int $idcat Id of category beeing moved down
 *
 * @return  int  Category id
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function mr_strMovedownCategory($idcat) {
    ModRewriteDebugger::log($idcat, 'mr_strMovedownCategory $idcat');

    // category check
    $cat = new cApiCategory((int) $idcat);
    if (!$cat->get('id')) {
        return;
    }

    // get all cat languages
    $aIdLang = ModRewrite::getCatLanguages($idcat);
    // update ...
    foreach ($aIdLang as $iIdLang) {
        // get urlname
        $sCatname = ModRewrite::getCatName($idcat, $iIdLang);
        // set new urlname - because original set urlname isn't validated for double entries in same parent category
        ModRewrite::setCatWebsafeName($sCatname, $idcat, $iIdLang);
    }

    return $idcat;
}

/**
 * Processes mod_rewrite related job after moving a category subtree.
 *
 * Will be called by chain 'Contenido.Action.str_movesubtree.AfterCall'.
 *
 * @param   array $data Assoziative array with some values
 *
 * @return  array  Passed parameter
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function mr_strMoveSubtree(array $data) {
    ModRewriteDebugger::log($data, 'mr_strMoveSubtree $data');

    // category check
    if ((int) $data['idcat'] <= 0) {
        return;
    }

    // next category check
    $cat = new cApiCategory($data['idcat']);
    if (!$cat->get('idcat')) {
        return;
    }

    // get all cat languages
    $aIdLang = ModRewrite::getCatLanguages($data['idcat']);
    // update all languages
    foreach ($aIdLang as $iIdLang) {
        // get urlname
        $sCatname = ModRewrite::getCatName($data['idcat'], $iIdLang);
        // set new urlname - because original set urlname isn't validated for double entries in same parent category
        ModRewrite::setCatWebsafeName($sCatname, $data['idcat'], $iIdLang);
        ModRewrite::setCatUrlPath($data['idcat'], $iIdLang);
    }

    // now dive into all existing subcategories and modify their paths too...
    $oCatColl = new cApiCategoryCollection('parentid=' . $data['idcat']);
    while ($oCat = $oCatColl->next()) {
        mr_strMoveSubtree(array('idcat' => $oCat->get('idcat')));
    }

    return $data;
}

/**
 * Processes mod_rewrite related job after copying a category subtree.
 *
 * Will be called by chain 'Contenido.Category.strCopyCategory'.
 *
 * @param   array $data Assoziative array with some values
 *
 * @return  array  Passed parameter
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function mr_strCopyCategory(array $data) {
    ModRewriteDebugger::log($data, 'mr_strCopyCategory $data');

    $idcat = (int) $data['newcat']->get('idcat');
    if ($idcat <= 0) {
        return $data;
    }

    // get all cat languages
    $aIdLang = ModRewrite::getCatLanguages($idcat);
    // update ...
    foreach ($aIdLang as $iIdLang) {
        // get urlname
        $sCatname = ModRewrite::getCatName($idcat, $iIdLang);
        // set new urlname - because original set urlname isn't validated for double entries in same parent category
        ModRewrite::setCatWebsafeName($sCatname, $idcat, $iIdLang);
        ModRewrite::setCatUrlPath($idcat, $iIdLang);
    }
}

/**
 * Processes mod_rewrite related job during structure synchronisation process,
 * sets the urlpath of current category.
 *
 * Will be called by chain 'Contenido.Category.strSyncCategory_Loop'.
 *
 * @param   array $data Assoziative array with some values
 *
 * @return  array  Passed parameter
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function mr_strSyncCategory(array $data) {
    ModRewriteDebugger::log($data, 'mr_strSyncCategory $data');
    ModRewrite::setCatUrlPath($data['idcat'], $data['idlang']);
    return $data;
}

/**
 * Processes mod_rewrite related job for saved articles (new or modified article).
 *
 * Will be called by chain 'Contenido.Action.con_saveart.AfterCall'.
 *
 * @param   array $data Assoziative array with some article properties
 *
 * @return  array  Passed parameter
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function mr_conSaveArticle(array $data) {
    global $tmp_firstedit, $client;

    ModRewriteDebugger::log($data, 'mr_conSaveArticle $data');

    if ((int) $data['idart'] == 0) {
        return $data;
    }

    if (cString::getStringLength(trim($data['urlname'])) == 0) {
        $data['urlname'] = $data['title'];
    }

    if (1 == $tmp_firstedit) {
        // new article
        $aLanguages = getLanguagesByClient($client);

        foreach ($aLanguages as $iLang) {
            ModRewrite::setArtWebsafeName($data['urlname'], $data['idart'], $iLang, $data['idcat']);
        }
    } else {
        // modified article
        $aArticle = ModRewrite::getArtIdByArtlangId($data['idartlang']);

        if (isset($aArticle['idart']) && isset($aArticle['idlang'])) {
            ModRewrite::setArtWebsafeName($data['urlname'], $aArticle['idart'], $aArticle['idlang'], $data['idcat']);
        }
    }

    return $data;
}

/**
 * Processes mod_rewrite related job for articles beeing moved.
 *
 * Will be called by chain 'Contenido.Article.conMoveArticles_Loop'.
 *
 * @param   array $data Assoziative array with record entries
 *
 * @return  array  Loop through of arguments
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function mr_conMoveArticles($data) {
    ModRewriteDebugger::log($data, 'mr_conMoveArticles $data');

    // too defensive but secure way
    if (!is_array($data)) {
        return $data;
    } elseif (!isset($data['idartlang'])) {
        return $data;
    } elseif (!isset($data['idart'])) {
        return $data;
    }

    $arr_art = ModRewrite::getArtIds($data['idartlang']);
    if (count($arr_art) == 2) {
        ModRewrite::setArtWebsafeName($arr_art["urlname"], $data['idart'], $arr_art["idlang"]);
    }

    return $data;
}

/**
 * Processes mod_rewrite related job for duplicated articles.
 *
 * Will be called by chain 'Contenido.Article.conCopyArtLang_AfterInsert'.
 *
 * @param   array $data Assoziative array with record entries
 *
 * @return  array  Loop through of arguments
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function mr_conCopyArtLang($data) {
    ModRewriteDebugger::log($data, 'mr_conCopyArtLang $data');

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

    ModRewrite::setArtWebsafeName($data['title'], $data['idart'], $data['idlang']);

    return $data;
}

/**
 * Processes mod_rewrite related job for synchronized articles.
 *
 * Will be called by chain 'Contenido.Article.conSyncArticle_AfterInsert'.
 *
 * @param   array $data Assoziative array with record entries as follows:
 *                      <code>
 *                      array(
 *                      'src_art_lang'  => Recordset (assoziative array) of source item from con_art_lang table
 *                      'dest_art_lang' => Recordset (assoziative array) of inserted destination item from con_art_lang table
 *                      );
 *                      </code>
 *
 * @return  array  Loop through of argument
 * @throws cDbException
 * @throws cInvalidArgumentException
 */
function mr_conSyncArticle($data) {
    ModRewriteDebugger::log($data, 'mr_conSyncArticle $data');

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
        ModRewrite::setArtWebsafeName($urlname, $data['dest_art_lang']['idart'], $data['dest_art_lang']['idlang']);
    }

    return $data;
}

/**
 * Works as a wrapper for Contenido_Url.
 *
 * Will also be called by chain 'Contenido.Frontend.CreateURL'.
 *
 * @todo: Still exists bcause of downwards compatibility (some other modules/plugins are using it)
 *
 * @param   string $url URL to rebuild
 *
 * @return  string        New URL
 * @throws cInvalidArgumentException
 */
function mr_buildNewUrl($url) {
    global $lang;

    ModRewriteDebugger::add($url, 'mr_buildNewUrl() in -> $url');

    $oUrl = cUri::getInstance();
    $aUrl = $oUrl->parse($url);

    // add language, if not exists
    if (!isset($aUrl['params']['lang'])) {
        $aUrl['params']['lang'] = $lang;
    }

    // build url
    $newUrl = $oUrl->build($aUrl['params']);

    // add existing fragment
    if (isset($aUrl['fragment'])) {
        $newUrl .= '#' . $aUrl['fragment'];
    }

    $arr = array(
        'in' => $url,
        'out' => $newUrl,
    );
    ModRewriteDebugger::add($arr, 'mr_buildNewUrl() in -> out');

    return $newUrl;
}

/**
 * Replaces existing ancors inside passed code, while rebuilding the urls.
 *
 * Will be called by chain 'Contenido.Content.conGenerateCode' or
 * 'Contenido.Frontend.HTMLCodeOutput' depening on mod_rewrite settings.
 *
 * @param   string $code Code to prepare
 *
 * @return  string          New code
 * @throws cInvalidArgumentException
 */
function mr_buildGeneratedCode($code) {
    global $client, $cfgClient;

    ModRewriteDebugger::add($code, 'mr_buildGeneratedCode() in');

    $sseStartTime = getmicrotime();

    // mod rewrite is activated
    if (ModRewrite::isEnabled()) {

        // anchor hack
        $code = preg_replace_callback("/<a([^>]*)href\s*=\s*[\"|\'][\/]#(.?|.+?)[\"|\']([^>]*)>/i", function($match) {
            return ModRewrite::rewriteHtmlAnchor($match);
        }, $code);

        // remove fucking tinymce single quote entities:
        $code = str_replace("&#39;", "'", $code);

        // == IE hack with wrong base href interpretation
        // get base uri
        // $sBaseUri = cRegistry::getFrontendUrl();
        // $sBaseUri = cApiCecHook::execute("Contenido.Frontend.BaseHrefGeneration", $sBaseUri);
        // $code = preg_replace("/([\"|\'|=])upload\/(.?|.+?)([\"|\'|>])/ie", "stripslashes('\\1{$sBaseUri}upload/\\2\\3')", $code);

        $baseUri = cRegistry::getFrontendUrl();
		$baseUri = cApiCecHook::executeAndReturn("Contenido.Frontend.BaseHrefGeneration", $baseUri);

		// CON-1389 modifier /e is deprecated as of PHP 5.5
		$code = preg_replace_callback("/([\"|\'|=])upload\/(.?|.+?)([\"|\'|>])/i", function($match) use ($baseUri) {
            return stripslashes($match[1] . $baseUri . 'upload/' . $match[2] . $match[3]);
        }, $code);

        // define some preparations to replace /front_content.php & ./front_content.php
        // against front_content.php, because urls should start with front_content.php
        $aPattern = array(
            '/([\"|\'|=])\/front_content\.php(.?|.+?)([\"|\'|>])/i',
            '/([\"|\'|=])\.\/front_content\.php(.?|.+?)([\"|\'|>])/i'
        );

        $aReplace = array(
            '\1front_content.php\2\3',
            '\1front_content.php\2\3'
        );

        // perform the pre replacements
        $code = preg_replace($aPattern, $aReplace, $code);

        // create url stack object and fill it with found urls...
        $oMRUrlStack = ModRewriteUrlStack::getInstance();
        $oMRUrlStack->add('front_content.php');

        $matches = NULL;
        preg_match_all("/([\"|\'|=])front_content\.php(.?|.+?)([\"|\'|>])/i", $code, $matches, PREG_SET_ORDER);
        foreach ($matches as $val) {
            $oMRUrlStack->add('front_content.php' . $val[2]);
        }

        // ok let it beginn, start mod rewrite class
        $code = str_replace('"front_content.php"', '"' . mr_buildNewUrl('front_content.php') . '"', $code);
        $code = str_replace("'front_content.php'", "'" . mr_buildNewUrl('front_content.php') . "'", $code);
        $code = preg_replace_callback("/([\"|\'|=])front_content\.php(.?|.+?)([\"|\'|>])/i", function($match) {
            return $match[1] . mr_buildNewUrl('front_content.php' . $match[2]) . $match[3];
        }, $code);

        ModRewriteDebugger::add($code, 'mr_buildGeneratedCode() out');

    } else {
        // anchor hack for non modrewrite websites
        $code = preg_replace_callback("/<a([^>]*)href\s*=\s*[\"|\'][\/]#(.?|.+?)[\"|\']([^>]*)>/i", function($match) {
            return ModRewrite::contenidoHtmlAnchor($match, $GLOBALS['is_XHTML']);
        }, $code);
    }

    $sseEndTime = getmicrotime();

    ModRewriteDebugger::add(($sseEndTime - $sseStartTime), 'mr_buildGeneratedCode() total spend time');

    if ($debug = mr_debugOutput(false)) {
        $code = cString::iReplaceOnce("</body>", $debug . "\n</body>", $code);
    }

    return $code;
    // print "\n\n<!-- modrewrite generation time: " . ($sseEndTime - $sseStartTime) . " seconds -->";
}

/**
 * Sets language of client, like done in front_content.php
 *
 * @param  int $client Client id
 *
 * @throws cDbException
 */
function mr_setClientLanguageId($client) {
    global $lang, $load_lang, $cfg;

    if ((int) $lang > 0) {
        // there is nothing to do
        return;
    } elseif ($load_lang) {
        // use the first language of this client, load_client is set in __FRONTEND_PATH__/data/config/config.php
        $lang = $load_lang;
        return;
    }

    // try to get clients language from table
    $sql = "SELECT B.idlang FROM "
            . $cfg['tab']['clients_lang'] . " AS A, "
            . $cfg['tab']['lang'] . " AS B "
            . "WHERE "
            . "A.idclient='" . ((int) $client) . "' AND A.idlang=B.idlang"
            . "LIMIT 0,1";

    if ($aData = mr_queryAndNextRecord($sql)) {
        $lang = $aData['idlang'];
    }
}

/**
 * Loads Advanced Mod Rewrite configuration for passed client using serialized
 * file containing the settings.
 *
 * File is placed in /contenido/mod_rewrite/includes/and is named like
 * config.mod_rewrite_{client_id}.php.
 *
 * @param  int  $clientId      Id of client
 * @param  bool $forceReload   Flag to force to reload configuration, e. g. after
 *                             done changes on it
 *
 * @throws cInvalidArgumentException
 */
function mr_loadConfiguration($clientId, $forceReload = false) {
    global $cfg;
    static $aLoaded;

    $clientId = (int) $clientId;
    if (!isset($aLoaded)) {
        $aLoaded = array();
    } elseif (isset($aLoaded[$clientId]) && $forceReload == false) {
        return;
    }

    $mrConfig = mr_getConfiguration($clientId);

    if (is_array($mrConfig)) {
        // merge mod rewrite configuration with global cfg array
        $cfg = array_merge($cfg, $mrConfig);
    } else {
        // couldn't load configuration, set defaults
        $backendPath = cRegistry::getBackendPath();
        include_once($backendPath . $cfg['path']['plugins'] . 'mod_rewrite/includes/config.mod_rewrite_default.php');
    }

    $aLoaded[$clientId] = true;
}

/**
 * Returns the path of the mod rewrite configuration file of an client.
 *
 * File is placed within client frontend path in directory "data/config/{CENVIRONMENT}/"
 * and has the name "config.mod_rewrite.php"
 *
 * @param   int   $clientId     Id of client
 * @return  string  File name and path
 */
function mr_getConfigurationFilePath($clientId) {
    $clientConfig = cRegistry::getClientConfig((int) $clientId);
    $fePath = $clientConfig['path']['frontend'];
    return $fePath . 'data/config/' . CON_ENVIRONMENT . '/config.mod_rewrite.php';
}

/**
 * Returns the mod rewrite configuration array of an client.
 *
 * File is placed in /contenido/mod_rewrite/includes/and is named like
 * config.mod_rewrite_{client_id}.php.
 *
 * @param   int $clientId Id of client
 *
 * @return  array|NULL
 * @throws cInvalidArgumentException
 */
function mr_getConfiguration($clientId) {
    global $cfg;

    $clientId = (int) $clientId;

    $file = mr_getConfigurationFilePath($clientId);

    if (!is_file($file) || !is_readable($file)) {
        $backendPath = cRegistry::getBackendPath();
        $file = $backendPath . $cfg['path']['plugins'] . 'mod_rewrite/includes/config.mod_rewrite_' . $clientId . '.php';
    }

    if (!is_file($file) || !is_readable($file)) {
        return NULL;
    }
    if ($content = cFileHandler::read($file)) {
        return unserialize($content);
    } else {
        return NULL;
    }
}

/**
 * Saves the mod rewrite configuration array of an client.
 *
 * File is placed in /contenido/mod_rewrite/includes/and is named like
 * config.mod_rewrite_{client_id}.php.
 *
 * @param   int   $clientId Id of client
 * @param   array $config   Configuration to save
 *
 * @return  bool
 * @throws cInvalidArgumentException
 */
function mr_setConfiguration($clientId, array $config) {
    global $cfg;

    $clientId = (int) $clientId;

    $file = mr_getConfigurationFilePath($clientId);
    $result = cFileHandler::write($file, serialize($config));

    $backendPath = cRegistry::getBackendPath();
    $file = $backendPath . $cfg['path']['plugins'] . 'mod_rewrite/includes/config.mod_rewrite_' . $clientId . '.php';
    if (is_file($file) && is_writeable($file)) {
        cFileHandler::remove($file, serialize($config));
    }

    return ($result) ? true : false;
}

/**
 * Includes the frontend controller script which parses the url and extacts
 * needed data like idcat, idart, lang and client from it.
 *
 * Will be called by chain 'Contenido.Frontend.AfterLoadPlugins' at front_content.php.
 *
 * @return  bool  Just a return value
 * @throws cInvalidArgumentException
 */
function mr_runFrontendController() {
    $iStartTime = getmicrotime();

    plugin_include('mod_rewrite', 'includes/config.plugin.php');

    if (ModRewrite::isEnabled() == true) {
        plugin_include('mod_rewrite', 'includes/front_content_controller.php');

        $totalTime = sprintf('%.4f', (getmicrotime() - $iStartTime));
        ModRewriteDebugger::add($totalTime, 'mr_runFrontendController() total time');
    }

    return true;
}

/**
 * Cleanups passed string from characters beeing repeated two or more times
 *
 * @param   string  $char    Character to remove
 * @param   string  $string  String to clean from character
 * @return  string  Cleaned string
 */
function mr_removeMultipleChars($char, $string) {
    while (cString::findFirstPos($string, $char . $char) !== false) {
        $string = str_replace($char . $char, $char, $string);
    }
    return $string;
}

/**
 * Returns amr related translation text
 *
 * @param   string  $key    The message id as string
 * @return  string  Related message
 */
function mr_i18n($key) {
    global $lngAMR;
    return (is_array($lngAMR) && isset($lngAMR[$key])) ? $lngAMR[$key] : 'n. a.';
}

################################################################################
### Some helper functions, which are not plugin specific

/**
 * Database query helper. Used to execute a select statement and to return the
 * result of first recordset.
 *
 * Minimizes following code:
 * <code>
 * // default way
 * $db  = cRegistry::getDb();
 * $sql = "SELECT * FROM foo WHERE bar='foobar'";
 * $db->query($sql);
 * $db->nextRecord();
 * $data = $db->getRecord();
 *
 * // new way
 * $sql  = "SELECT * FROM foo WHERE bar='foobar'";
 * $data = mr_queryAndNextRecord($sql);
 * </code>
 *
 * @param   string $query Query to execute
 *
 * @return  mixed   Assoziative array including recordset or NULL
 * @throws cDbException
 */
function mr_queryAndNextRecord($query) {
    static $db;
    if (!isset($db)) {
        $db = cRegistry::getDb();
    }
    if (!$db->query($query)) {
        return NULL;
    }
    return ($db->nextRecord()) ? $db->getRecord() : NULL;
}

/**
 * Returns value of an array key (assoziative or indexed).
 *
 * Shortcut function for some ways to access to arrays:
 * <code>
 * // old way
 * if (is_array($foo) && isset($foo['bar']) && $foo['bar'] == 'yieeha') {
 *     // do something
 * }
 *
 * // new, more readable way:
 * if (mr_arrayValue($foo, 'bar') == 'yieeha') {
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
 * $jep = mr_arrayValue($foo, 'bar', 'yummy');
 * </code>
 *
 * @param   array  $array    The array
 * @param   mixed  $key      Position of an indexed array or key of an assoziative array
 * @param   mixed  $default  Default value to return
 * @return  mixed  Either the found value or the default value
 */
function mr_arrayValue($array, $key, $default = NULL) {
    if (!is_array($array)) {
        return $default;
    } elseif (!isset($array[$key])) {
        return $default;
    } else {
        return $array[$key];
    }
}

/**
 * Request cleanup function. Request data is allways tainted and must be filtered.
 * Pass the array to cleanup using several options.
 * Emulates array_walk_recursive().
 *
 * @param   mixed  $data     Data to cleanup
 * @param   array  $options  Default options array, provides only 'filter' key with several
 *                           filter functions which are to execute as follows:
 * <code>
 * $options['filter'] = array('trim', 'myFilterFunc');
 * </code>
 *                           If no filter functions are set, 'trim', 'strip_tags' and 'stripslashes'
 *                           will be used by default.
 *                           A userdefined function must accept the value as a parameter and must return
 *                           the filtered parameter, e. g.
 * <code>
 * function myFilter($data) {
 *    // do what you want with the data, e. g. cleanup of xss content
 *    return $data;
 * }
 * </code>
 *
 * @return  mixed  Cleaned data
 */
function mr_requestCleanup(&$data, $options = NULL) {
    if (!mr_arrayValue($options, 'filter')) {
        $options['filter'] = array('trim', 'strip_tags', 'stripslashes');
    }

    if (is_array($data)) {
        foreach ($data as $p => $v) {
            $data[$p] = mr_requestCleanup($v, $options);
        }
    } else {
        foreach ($options['filter'] as $filter) {
            if ($filter == 'trim') {
                $data = trim($data);
            } elseif ($filter == 'strip_tags') {
                $data = strip_tags($data);
            } elseif ($filter == 'stripslashes') {
                $data = stripslashes($data);
            } elseif (function_exists($filter)) {
                $data = call_user_func($filter, $data);
            }
        }
    }
    return $data;
}

/**
 * Minimalistic'n simple way to get request variables.
 *
 * Checks occurance in $_GET, then in $_POST. Uses trim() and strip_tags() to preclean data.
 *
 * @param   string  $key      Name of var to get
 * @param   mixed   $default  Default value to return
 * @return  mixed   The value
 */
function mr_getRequest($key, $default = NULL) {
    static $cache;
    if (!isset($cache)) {
        $cache = array();
    }
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    if (isset($_GET[$key])) {
        $val = $_GET[$key];
    } elseif (isset($_POST[$key])) {
        $val = $_POST[$key];
    } else {
        $val = $default;
    }
    $cache[$key] = strip_tags(trim($val));
    return $cache[$key];
}

/**
 * Replaces calling of header method for redirects in front_content.php,
 * used during development.
 *
 * @param  $header  string Header value for redirect
 */
function mr_header($header) {
    header($header);

    // $header = str_replace('Location: ', '', $header);
    // echo '<html>
    //     <head></head>
    //     <body>
    //     <p><a href="' . $header . '">' . $header . '</a></p>';
    // mr_debugOutput();
    // echo '</body></html>';
    // exit();
}

/**
 * Debug output only during development
 *
 * @param   bool $print Flag to echo the debug data
 *
 * @return  mixed  Either the debug data, if parameter $print is set to true, or nothing
 * @throws cInvalidArgumentException
 */
function mr_debugOutput($print = true) {
    global $DB_Contenido_QueryCache;
    if (isset($DB_Contenido_QueryCache) && is_array($DB_Contenido_QueryCache) &&
            count($DB_Contenido_QueryCache) > 0) {
        ModRewriteDebugger::add($DB_Contenido_QueryCache, 'sql statements');

        // calculate total time consumption of queries
        $timeTotal = 0;
        foreach ($DB_Contenido_QueryCache as $pos => $item) {
            $timeTotal += $item['time'];
        }
        ModRewriteDebugger::add($timeTotal, 'sql total time');
    }

    $sOutput = ModRewriteDebugger::getAll();
    if ($print == true) {
        echo $sOutput;
    } else {
        return $sOutput;
    }
}
