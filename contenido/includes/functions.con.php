<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Defines the 'con' related functions in CONTENIDO
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.1.8
 * @author     Olaf Niemann, Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * @todo: Rework code
 *
 * {@internal
 *   created unknown
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

// Compatibility: Include new functions.con2.php
cInclude('includes', 'functions.con2.php');

/**
 * Create a new Article
 *
 * @param mixed many
 * @return int Id of the new article
 */
function conEditFirstTime($idcat, $idcatnew, $idart, $isstart, $idtpl, $idartlang, $idlang,
                          $title, $summary, $artspec, $created, $lastmodified, $author,
                          $online, $datestart, $dateend, $artsort, $keyart = 0) {
    global $client, $lang, $auth, $urlname, $page_title;
    //Some stuff for the redirect
    global $redirect, $redirect_url, $external_redirect;
    global $time_move_cat; // Used to indicate "move to cat"
    global $time_target_cat; // Used to indicate the target category
    global $time_online_move; // Used to indicate if the moved article should be online
    global $timemgmt;

    $page_title = addslashes($page_title);
    $urlname = (trim($urlname) == '') ? trim($title) : trim($urlname);

    if ($isstart == 1) {
        $timemgmt = 0;
    }

    if (!is_array($idcatnew)) {
        $idcatnew[0] = 0;
    }

    // Create article entry
    $oArtColl = new cApiArticleCollection();
    $oArt = $oArtColl->create($client);
    $idart = $oArt->get('idart');

    $status = 0;

    // Create an category article entry
    $oCatArtColl = new cApiCategoryArticleCollection();
    $oCatArt = $oCatArtColl->create($idcat, $idart, $status);
    $idcatart = $oCatArt->get('idcatart');

    $aLanguages = array($lang);

    // Table 'con_art_lang', one entry for every language
    foreach ($aLanguages as $curLang) {
        $lastmodified = ($lang == $curLang) ? $lastmodified : 0;

        if ($online == 1) {
            $published_value = date('Y-m-d H:i:s');
            $publishedby_value = $auth->auth['uname'];
        } else {
            $published_value = '';
            $publishedby_value = '';
        }

        // Create an stat entry
        $oStatColl = new cApiStatCollection();
        $oStat = $oStatColl->create($idcatart, $curLang, $client, 0);

        // Create an article language entry
        $oArtLangColl = new cApiArticleLanguageCollection();
        $oArtLang = $oArtLangColl->create(
                $idart, $curLang, $title, $urlname, $page_title, $summary, $artspec, $created,
                $lastmodified, $auth->auth['uname'], $published_value, $publishedby_value,
                $online, $redirect, $redirect_url, $external_redirect, $artsort, $timemgmt,
                $datestart, $dateend, $status, $time_move_cat, $time_target_cat, $time_online_move
        );

        conMakeStart($idcatart, 0);

        $lastId = $oArtLang->get('idartlang');
        $availableTags = conGetAvailableMetaTagTypes();
        foreach ($availableTags as $key => $value) {
            conSetMetaValue($lastId, $key, $_POST['META' . $value['name']]);
        }
    }

    // Get all idcats that contain art
    $oCatArtColl = new cApiCategoryArticleCollection();
    $aCatsForArt = $oCatArtColl->getCategoryIdsByArticleId($idart);
    if (count($aCatsForArt) == 0) {
        $aCatsForArt[0] = 0;
    }

    $aLanguages = getLanguagesByClient($client);

    foreach ($idcatnew as $value) {
        if (!in_array($value, $aCatsForArt)) {
            // New category article entry
            $oCatArtColl = new cApiCategoryArticleCollection();
            $oCatArt = $oCatArtColl->create($value, $idart);
            $curIdcatart = $oCatArt->get('idcatart');

            // New statistics entry for each language
            foreach ($aLanguages as $curLang) {
                $oStatColl = new cApiStatCollection();
                $oStatColl->create($curIdcatart, $curLang, $client, 0);
            }
        }
    }

    foreach ($aCatsForArt as $value) {
        if (!in_array($value, $idcatnew)) {
            // Delete category article and other related entries that will no longer exist
            conRemoveOldCategoryArticle($value, $idart, $idartlang, $client, $lang);
        }
    }

    if (!$title) {
        $title = '--- ' . i18n("Default title") . ' ---';
    }

    // Update article language for all languages
    foreach ($aLanguages as $curLang) {
        $curOnline = ($lang == $curLang) ? $online : 0;
        $curLastmodified = ($lang == $curLang) ? $lastmodified : 0;

        $oArtLang = new cApiArticleLanguage();
        $oArtLang->loadByArticleAndLanguageId($idart, $lang);
        if (!$oArtLang->isLoaded()) {
            continue;
        }

        $oArtLang->set('title', $title);
        $oArtLang->set('urlname', $urlname);
        $oArtLang->set('pagetitle', $page_title);
        $oArtLang->set('summary', $summary);
        $oArtLang->set('artspec', $artspec);
        $oArtLang->set('created', $created);
        $oArtLang->set('lastmodified', $curLastmodified);
        $oArtLang->set('modifiedby', $author);
        $oArtLang->set('online', $curOnline);
        $oArtLang->set('redirect', $redirect);
        $oArtLang->set('redirect_url', $redirect_url);
        $oArtLang->set('external_redirect', $external_redirect);
        $oArtLang->set('artsort', $artsort);
        $oArtLang->set('datestart', $datestart);
        $oArtLang->set('dateend', $dateend);
        $oArtLang->store();
    }

    return $idart;
}

/**
 * Edit an existing article
 *
 * @param mixed many
 * @return void
 */
function conEditArt($idcat, $idcatnew, $idart, $isstart, $idtpl, $idartlang, $idlang,
                    $title, $summary, $artspec, $created, $lastmodified, $author,
                    $online, $datestart, $dateend, $artsort, $keyart = 0) {
    global $client, $lang, $redirect, $redirect_url, $external_redirect, $perm;
    global $urlname, $page_title;
    global $time_move_cat, $time_target_cat;
    global $time_online_move; // Used to indicate if the moved article should be online
    global $timemgmt;

    // Add slashes because single quotes will crash the db
    $page_title = addslashes($page_title);

    $urlname = (trim($urlname) == '') ? trim($title) : trim($urlname);
    $usetimemgmt = ((int) $timemgmt == 1) ? 1 : 0;
    if ($timemgmt == '1' && (($datestart == '' && $dateend == '') ||
            ($datestart == '0000-00-00 00:00:00' && $dateend == '0000-00-00 00:00:00'))) {
        $usetimemgmt = 0;
    }

    if ($isstart == 1) {
        $usetimemgmt = 0;
    }

    if (!is_array($idcatnew)) {
        $idcatnew[0] = 0;
    }

    $oArtLang = new cApiArticleLanguage((int) $idartlang);
    if (!$oArtLang->isLoaded()) {
        return;
    }

    // Get all idcats that contain art
    $oCatArtColl = new cApiCategoryArticleCollection();
    $aCatsForArt = $oCatArtColl->getCategoryIdsByArticleId($idart);
    if (count($aCatsForArt) == 0) {
        $aCatsForArt[0] = 0;
    }

    foreach ($idcatnew as $value) {
        if (!in_array($value, $aCatsForArt)) {
            // New category article entry
            $oCatArtColl = new cApiCategoryArticleCollection();
            $oCatArt = $oCatArtColl->create($value, $idart);
            $curIdcatart = $oCatArt->get('idcatart');

            // New statistics entry for each language
            foreach ($aLanguages as $curLang) {
                $oStatColl = new cApiStatCollection();
                $oStatColl->create($curIdcatart, $curLang, $client, 0);
            }
        }
    }

    foreach ($aCatsForArt as $value) {
        if (!in_array($value, $idcatnew)) {
            // Delete category article and other related entries that will no longer exist
            conRemoveOldCategoryArticle($value, $idart, $idartlang, $client, $lang);
        }
    }


    if ($title == '') {
        $title = '--- ' . i18n('Default title') . ' ---';
    }

    $oArtLang->set('title', $title);
    $oArtLang->set('urlname', $urlname);
    $oArtLang->set('summary', $summary);
    $oArtLang->set('artspec', $artspec);
    $oArtLang->set('created', $created);
    $oArtLang->set('lastmodified', $lastmodified);
    $oArtLang->set('modifiedby', $author);
    $oArtLang->set('timemgmt', $usetimemgmt);
    $oArtLang->set('redirect', $redirect);
    $oArtLang->set('external_redirect', $external_redirect);
    $oArtLang->set('redirect_url', $redirect_url);
    $oArtLang->set('artsort', $artsort);

    // If the user has right for makeonline, update some properties.
    if ($perm->have_perm_area_action('con', 'con_makeonline') ||
            $perm->have_perm_area_action_item('con', 'con_makeonline', $idcat)) {
        $oldOnline = $oArtLang->get('online');
        $oArtLang->set('online', $online);

        // Check if old online value was 0, update published data if value changed from 0 to 1
        if ((int) $online == 1 && $oldOnline == 0) {
            $oArtLang->set('published', date('Y-m-d H:i:s'));
            $oArtLang->set('publishedby', $author);
        }

        $oArtLang->set('datestart', $datestart);
        $oArtLang->set('dateend', $dateend);
        $oArtLang->set('time_move_cat', $time_move_cat);
        $oArtLang->set('time_target_cat', $time_target_cat);
        $oArtLang->set('time_online_move', $time_online_move);
    }

    $oArtLang->store();

    /*
      $availableTags = conGetAvailableMetaTagTypes();
      foreach ($availableTags as $key => $value) {
      conSetMetaValue($idartlang, $key, $_POST['META' . $value['name']]);
      }
     */
}

/**
 * Save a content element and generate index
 *
 * @param   int  $idartlang  idartlang of the article
 * @param   string  $type  Type of content element
 * @param   int   $typeid  Serial number of the content element
 * @param   string  $value  Content
 * @param   bool  $bForce  Not used: Was a flag to use existing db instance in global scope
 * @return void
 */
function conSaveContentEntry($idartlang, $type, $typeid, $value, $bForce = false) {
    global $auth, $cfgClient, $client, $_cecRegistry;

    $oType = new cApiType();
    if (!$oType->loadByType($type)) {
        // Couldn't load type...
        return;
    }

    $date = date('Y-m-d H:i:s');
    $author = $auth->auth['uname'];
    $value = str_replace($cfgClient[$client]['path']['htmlpath'], '', $value);
    $value = stripslashes($value);

    $iterator = $_cecRegistry->getIterator('Contenido.Content.SaveContentEntry');
    while ($chainEntry = $iterator->next()) {
        $value = $chainEntry->execute($idartlang, $type, $typeid, $value);
    }

    $idtype = $oType->get('idtype');

    $oContent = new cApiContent();
    $oContent->loadByArticleLanguageIdTypeAndTypeId($idartlang, $idtype, $typeid);
    if ($oContent->isLoaded()) {
        // Update existing entry
        $oContent->set('value', $value);
        $oContent->set('author', $author);
        $oContent->set('lastmodified', $date);
        $oContent->store();
    } else {
        // Create new entry
        $oContentColl = new cApiContentCollection();
        $oContent = $oContentColl->create($idartlang, $idtype, $typeid, $value, 0, $author, $date, $date);
    }

    // Touch the article to update last modified date
    $lastmodified = date('Y-m-d H:i:s');
    $oArtLang = new cApiArticleLanguage($idartlang);
    $oArtLang->set('lastmodified', $lastmodified);
    $oArtLang->set('modifiedby', $author);
    $oArtLang->store();
}

/**
 * Generate index of article content.
 *
 * added by stese
 * removed from function conSaveContentEntry  before
 * Touch the article to update last modified date
 *
 * @see conSaveContentEntry
 * @param integer $idart
 */
function conMakeArticleIndex($idartlang, $idart) {
    global $db;

    // indexing an article depends on the complete content with all content types,
    // i.e it can not by differentiated by specific content types.
    // Therefore one must fetch the complete content arrray.
    $aContent = conGetContentFromArticle($idartlang);

    // cms types to be excluded from indexing
    // @todo  Make this configurable!
    $aOptions = array('img', 'link', 'linktarget', 'swf');

    $oIndex = new cSearchIndex($db);
    $oIndex->start($idart, $aContent, 'auto', $aOptions);
}

/**
 * Toggle the online status of an article
 *
 * @param int $idart Article Id
 * @param ing $lang Language Id
 */
function conMakeOnline($idart, $lang) {
    global $auth;

    $oArtLang = new cApiArticleLanguage();
    if (!$oArtLang->loadByArticleAndLanguageId($idart, $lang)) {
        return;
    }

    // Reverse current value
    $online = ($oArtLang->get('online') == 0) ? 1 : 0;

    $oArtLang->set('online', $online);

    if ($online == 1) {
        // Update published date and publisher
        $oArtLang->set('published', date('Y-m-d H:i:s'));
        $oArtLang->set('publishedby', $auth->auth['uname']);
    }

    $oArtLang->store();
}

/**
 * Set the status from articles to online or offline.
 *
 * @todo  Should we not use cApiArticleLanguage, even if it is not performant?
 *
 * @param  array  $idarts  All articles
 * @param  int  $idlang
 * @param  bool  $online
 */
function conMakeOnlineBulkEditing($idarts, $idlang, $online) {
    global $db, $cfg, $auth;

    $where = '1=2';
    if ($online == 1) {
        $publisher_info = "published = '" . date("Y-m-d H:i:s") . "', publishedby='" . $auth->auth["uname"] . "',";
    } else {
        $online = 0;
        $publisher_info = '';
    }

    foreach ($idarts as $idart) {
        $where .= " OR idart='" . cSecurity::toInteger($idart) . "'";
    }

    $sql = "UPDATE " . $cfg["tab"]["art_lang"] . "  SET " . $publisher_info . " online = '" . $online . "' WHERE ($where)
        AND idlang = '" . cSecurity::toInteger($idlang) . "'";
    $db->query($sql);
}

/**
 * Toggle the lock status of an article
 *
 * @param int $idart Article Id
 * @param ing $lang Language Id
 */
function conLock($idart, $lang) {
    $oArtLang = new cApiArticleLanguage();
    if (!$oArtLang->loadByArticleAndLanguageId($idart, $lang)) {
        return;
    }

    $locked = ($oArtLang->get('locked') == 0) ? 1 : 0;

    $oArtLang->set('locked', $locked);
    $oArtLang->store();
}

/**
 * Freeze/Lock more articles.
 *
 * @todo  Should we not use cApiArticleLanguage, even if it is not performant?
 *
 * @param  array  $idarts  All articles
 * @param  int  $idlang
 * @param  bool $lock
 */
function conLockBulkEditing($idarts, $idlang, $lock) {
    global $db, $cfg;

    $where = '1=2';
    if ($lock != 1) {
        $lock = 0;
    }

    foreach ($idarts as $idart) {
        $where .= " OR idart='" . cSecurity::toInteger($idart) . "'";
    }

    $sql = "UPDATE " . $cfg["tab"]["art_lang"] . " SET locked = '" . cSecurity::toInteger($lock) . "' WHERE ($where) AND idlang = '" . cSecurity::toInteger($idlang) . "'";
    $db->query($sql);
}

/**
 * Checks if a article is locked or not
 *
 * @param   int  $idart  Article Id
 * @param   int  $lang   Language Id
 * @return  bool
 */
function conIsLocked($idart, $lang) {
    $oArtLang = new cApiArticleLanguage();
    if (!$oArtLang->loadByArticleAndLanguageId($idart, $lang)) {
        return false;
    }
    return (1 == $oArtLang->get('locked'));
}

/**
 * Toggle the online status of a category
 *
 * @param  int  $idcat  Id of the category
 * @param  int  $lang  Id of the language
 * @param  int  $status  Status of the category
 */
function conMakeCatOnline($idcat, $lang, $status) {
    global $cfg;

    $oCatLang = new cApiCategoryLanguage();
    if (!$oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
        return;
    }

    $status = (1 == $status) ? 1 : 0;

    $oCatLang->set('visible', $status);
    $oCatLang->set('lastmodified', date('Y-m-d H:i:s'));
    $oCatLang->store();

    if ($cfg['pathresolve_heapcache'] == true && !$status = 0) {
        $oPathresolveCacheColl = new cApiPathresolveCacheCollection();
        $oPathresolveCacheColl->deleteByCategoryAndLanguage($idcat, $lang);
    }
}

/**
 * Toggle the public status of a category
 *
 * Almost the same function as strMakePublic in functions.str.php
 * (conDeeperCategoriesArray instead of strDeeperCategoriesArray)
 *
 * @param  int  $idcat  Category Id
 * @param  int  $idcat  Language Id
 * @param  bool  $public  Public status of the Article
 */
function conMakePublic($idcat, $lang, $public) {
    $oCatLang = new cApiCategoryLanguage();
    if (!$oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
        return;
    }

    $public = (1 == $public) ? 1 : 0;

    $oCatLang->set('public', $public);
    $oCatLang->set('lastmodified', date('Y-m-d H:i:s'));
    $oCatLang->store();
}

/**
 * Delete an Article and all other related entries
 *
 * @param int $idart Article Id
 */
function conDeleteart($idart) {
    global $lang, $_cecRegistry, $cfgClient, $client;

    // Get article language
    $oArtLang = new cApiArticleLanguage();
    if (!$oArtLang->loadByArticleAndLanguageId($idart, $lang)) {
        return;
    }

    $idartlang = $oArtLang->get('idartlang');
    $idtplcfg = $oArtLang->get('idtplcfg');

    // Fetch idcat
    $oCatArt = new cApiCategoryArticle();
    $oCatArt->loadBy('idart', (int) $idart);
    $idcat = $oCatArt->get('idcat');

    // Reset startidartlang
    if (isStartArticle($idartlang, $idcat, $lang)) {
        $oCatLang = new cApiCategoryLanguage();
        $oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang);
        $oCatLang->set('startidartlang', 0);
        $oCatLang->store();
    }

    $oContentColl = new cApiContentCollection();
    $oContentColl->deleteBy('idartlang', (int) $idartlang);

    $oArtLangColl = new cApiArticleLanguageCollection();
    $oArtLangColl->delete((int) $idartlang);

    if ($idtplcfg != 0) {
        $oContainerConfColl = new cApiContainerConfigurationCollection();
        $oContainerConfColl->deleteBy('idtplcfg', (int) $idtplcfg);

        $oTplConfColl = new cApiTemplateConfigurationCollection();
        $oTplConfColl->delete('idtplcfg', $idtplcfg);
    }

    // Check if there are remaining languages
    $oArtLangColl = new cApiArticleLanguageCollection();
    $oArtLangColl->select('idart = ' . (int) $idart);
    if (!$oArtLangColl->next()) {
        return;
    }

    $oCatArtColl = new cApiCategoryArticleCollection();
    $oCatArtColl->select('idart = ' . (int) $idart);
    while ($oCatArtItem = $oCatArtColl->next()) {
        // Delete from code cache
        $mask = $cfgClient[$client]['code_path'] . '*.' . $oCatArtItem->get('idcatart') . '.php';
        array_map('unlink', glob($mask));

        // Delete from 'stat'-table
        $oStatColl = new cApiStatCollection();
        $oStatColl->deleteBy('idcatart', (int) $oCatArtItem->get('idcatart'));
    }

    $oArtLangColl = new cApiArticleLanguageCollection();
    $oArtLangColl->select('idart = ' . (int) $idart);
    while ($oArtLangColl = $oCatArtColl->next()) {
        // Reset startidlang value of related entry in category language table
        $oCatLang = new cApiCategoryLanguage();
        if ($oCatLang->loadBy('startidartlang', (int) $oArtLangColl->get('idartlang'))) {
            $oCatLang->set('startidartlang', 0);
            $oCatLang->store();
        }

        // Delete entries from content table
        $oContentColl = new cApiContentCollection();
        $oContentColl->deleteBy('idartlang', (int) $oArtLangColl->get('idartlang'));
    }

    $oCatArtColl = new cApiCategoryArticleCollection();
    $oCatArtColl->deleteBy('idart', (int) $idart);

    $oArtLangColl = new cApiArticleLanguageCollection();
    $oArtLangColl->deleteBy('idart', (int) $idart);

    $oArtColl = new cApiArticleCollection();
    $oArtColl->delete((int) $idart);

    # Contenido Extension Chain
    # @see docs/techref/plugins/Contenido Extension Chainer.pdf
    #
    # Usage:
    # One could define the file data/config/{environment}/config.local.php
    # with following code.
    #
    # global $_cecRegistry;
    # cInclude("plugins", "extension/extension.php");
    # $_cecRegistry->addChainFunction("Contenido.Content.DeleteArticle", "AdditionalFunction1");
    #
    # If function "AdditionalFunction1" is defined in file extension.php, it would be called via
    # $chainEntry->execute($idart);

    $iterator = $_cecRegistry->getIterator("Contenido.Content.DeleteArticle");
    while ($chainEntry = $iterator->next()) {
        $chainEntry->execute($idart);
    }
}

/**
 * Extract a number from a string
 *
 * @param string $string String var by reference
 */
function extractNumber(&$string) {
    $string = preg_replace('/[^0-9]/', '', $string);
}

/**
 * Change the template of a category
 *
 * @param int $idcat Category Id
 * @param int $idtpl Template Id
 */
function conChangeTemplateForCat($idcat, $idtpl) {
    global $lang;

    $oCatLang = new cApiCategoryLanguage();
    if (!$oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
        return;
    }

    if ($oCatLang->get('idtplcfg')) {
        // Delete old container configuration
        $oContainerConfColl = new cApiContainerConfigurationCollection();
        $oContainerConfColl->deleteBy('idtplcfg', (int) $oCatLang->get('idtplcfg'));

        // Delete old template configuration
        $oTplConfColl = new cApiTemplateConfigurationCollection();
        $oTplConfColl->delete('idtplcfg', (int) $oCatLang->get('idtplcfg'));
    }

    // Parameter $idtpl is 0, reset the template
    if (0 == $idtpl) {
        $oCatLang->set('idtplcfg', 0);
        $oCatLang->store();
    } else {
        // Check if a pre-configuration is assigned
        $oTpl = new cApiTemplate();
        $oTpl->loadBy('idtpl', (int) $idtpl);

        if (0 != $oTpl->get('idtplcfg')) {
            // Template is pre-configured, create new configuration
            $oTplConfColl = new cApiTemplateConfigurationCollection();
            $oTplConf = $oTplConfColl->create($idtpl);

            // If there is a preconfiguration of template, copy its settings into templateconfiguration
            $oTplConfColl->copyTemplatePreconfiguration($idtpl, $oTplConf->get('idtplcfg'));

            // Update category language
            $oCatLang->set('idtplcfg', $oTplConf->get('idtplcfg'));
            $oCatLang->store();
        } else {
            // Template is not pre-configured, create a new configuration.
            $oTplConfColl = new cApiTemplateConfigurationCollection();
            $oTplConf = $oTplConfColl->create($idtpl);

            // Update category language
            $oCatLang->set('idtplcfg', $oTplConf->get('idtplcfg'));
            $oCatLang->store();
        }
    }

    conGenerateCodeForAllartsInCategory($idcat);
}

/**
 * Returns category tree structure.
 * @param  int  $client  Uses global set client if not set
 * @param  int  $lang  Uses global set language if not set
 * @return array
 */
function conFetchCategoryTree($client = false, $lang = false) {
    global $db, $cfg;

    if ($client === false) {
        $client = $GLOBALS["client"];
    }

    if ($lang === false) {
        $lang = $GLOBALS["lang"];
    }

    $sql = "SELECT
                *
            FROM
                " . $cfg["tab"]["cat_tree"] . " AS A,
                " . $cfg["tab"]["cat"] . " AS B,
                " . $cfg["tab"]["cat_lang"] . " AS C
            WHERE
                A.idcat  = B.idcat AND
                B.idcat = C.idcat AND
                C.idlang = '" . cSecurity::toInteger($lang) . "' AND
                idclient = '" . cSecurity::toInteger($client) . "'
            ORDER BY
                idtree";

    $catarray = array();

    $db->query($sql);

    while ($db->next_record()) {
        $catarray[$db->f("idtree")] = array(
            "idcat" => $db->f("idcat"),
            "level" => $db->f("level"),
            "idtplcfg" => $db->f("idtplcfg"),
            "visible" => $db->f("visible"),
            "name" => $db->f("name"),
            "public" => $db->f("public"),
            "urlname" => $db->f("urlname"),
            "is_start" => $db->f("is_start")
        );
    }

    return ($catarray);
}

/**
 * Fetch all deeper categories by a given id
 *
 * @param int $idcat Id of category
 * @return array Array with all deeper categories
 */
function conDeeperCategoriesArray($idcat_start) {
    global $db, $client, $cfg;

    $sql = "SELECT
                *
            FROM
                " . $cfg["tab"]["cat_tree"] . " AS A,
                " . $cfg["tab"]["cat"] . " AS B
            WHERE
                A.idcat  = B.idcat AND
                idclient = '" . cSecurity::toInteger($client) . "'
            ORDER BY
                idtree";

    $db->query($sql);

    $found = false;
    $curLevel = 0;
    $catstring = array();

    while ($db->next_record()) {
        if ($found && $db->f("level") <= $curLevel) { // ending part of tree
            $found = false;
        }

        if ($db->f("idcat") == $idcat_start) { // starting part of tree
            $found = true;
            $curLevel = $db->f("level");
        }

        if ($found) {
            $catstring[] = $db->f("idcat");
        }
    }

    return $catstring;
}

/**
 * Recursive function to create an location string
 *
 * @param int $idcat ID of the starting category
 * @param string $seperator Seperation string
 * @param string $cat_str Category location string (by reference)
 * @param boolean $makeLink create location string with links
 * @param string $linkClass stylesheet class for the links
 * @param integer first navigation level location string should be printed out (first level = 0!!)
 * @return string location string
 */
function conCreateLocationString($idcat, $seperator, &$cat_str, $makeLink = false, $linkClass = "",
                                 $firstTreeElementToUse = 0, $uselang = 0, $final = true, $usecache = false) {
    global $cfg, $client, $cfgClient, $lang, $sess, $_locationStringCache;

    if ($idcat == 0) {
        $cat_str = "Lost and Found";
        return;
    }

    if ($uselang == 0) {
        $uselang = $lang;
    }

    if ($final == true && $usecache == true) {
        if (!is_array($_locationStringCache)) {
            if (cFileHandler::exists($cfgClient[$client]['cache_path'] . "locationstring-cache-$uselang.txt")) {
                $_locationStringCache = unserialize(cFileHandler::read($cfgClient[$client]['cache_path'] . "locationstring-cache-$uselang.txt"));
            } else {
                $_locationStringCache = array();
            }
        }

        if (array_key_exists($idcat, $_locationStringCache)) {
            if ($_locationStringCache[$idcat]["expires"] > time()) {
                $cat_str = $_locationStringCache[$idcat]["name"];
                return;
            }
        }
    }

    $db = cRegistry::getDb();

    $sql = "SELECT
                a.name AS name,
                a.idcat AS idcat,
                b.parentid AS parentid,
                c.level as level
            FROM
                " . $cfg["tab"]["cat_lang"] . " AS a,
                " . $cfg["tab"]["cat"] . " AS b,
                " . $cfg["tab"]["cat_tree"] . " AS c
            WHERE
                a.idlang    = '" . cSecurity::toInteger($uselang) . "' AND
                b.idclient  = '" . cSecurity::toInteger($client) . "' AND
                b.idcat     = '" . cSecurity::toInteger($idcat) . "' AND
                a.idcat     = b.idcat AND
                c.idcat = b.idcat";

    $db->query($sql);
    $db->next_record();

    if ($db->f("level") >= $firstTreeElementToUse) {
        $name = $db->f("name");
        $parentid = $db->f("parentid");

        //create link
        if ($makeLink == true) {
            $linkUrl = $sess->url("front_content.php?idcat=$idcat");
            $name = '<a href="' . $linkUrl . '" class="' . $linkClass . '">' . $name . '</a>';
        }

        $tmp_cat_str = $name . $seperator . $cat_str;
        $cat_str = $tmp_cat_str;
    }

    if ($parentid != 0) {
        conCreateLocationString($parentid, $seperator, $cat_str, $makeLink, $linkClass, $firstTreeElementToUse, $uselang, false);
    } else {
        $sep_length = strlen($seperator);
        $str_length = strlen($cat_str);
        $tmp_length = $str_length - $sep_length;
        $cat_str = substr($cat_str, 0, $tmp_length);
    }

    if ($final == true && $usecache == true) {
        $_locationStringCache[$idcat]["name"] = $cat_str;
        $_locationStringCache[$idcat]["expires"] = time() + 3600;

        if (is_writable($cfgClient[$client]['cache_path'])) {
            cFileHandler::write($cfgClient[$client]['cache_path'] . "locationstring-cache-$uselang.txt", serialize($_locationStringCache));
        }
    }
}

/**
 * Set a start-article
 *
 * @fixme  Do we still need the isstart. The old start compatibility has already been removed...
 * @param  int  $idcatart  Idcatart of the article
 * @param  bool  $isstart   Start article flag
 */
function conMakeStart($idcatart, $isstart) {
    global $db, $cfg, $lang;

    $sql = "SELECT idcat, idart FROM " . $cfg["tab"]["cat_art"] . " WHERE idcatart='" . cSecurity::toInteger($idcatart) . "'";
    $db->query($sql);
    $db->next_record();

    $idart = $db->f("idart");
    $idcat = $db->f("idcat");

    $sql = "SELECT idartlang FROM " . $cfg["tab"]["art_lang"] . " WHERE idart='" . cSecurity::toInteger($idart) . "' AND idlang='" . cSecurity::toInteger($lang) . "'";
    $db->query($sql);
    $db->next_record();

    $idartlang = $db->f("idartlang");

    if ($isstart == 1) {
        $sql = "UPDATE " . $cfg["tab"]["cat_lang"] . " SET startidartlang='" . cSecurity::toInteger($idartlang) . "' WHERE idcat='" . cSecurity::toInteger($idcat) . "' AND idlang='" . cSecurity::toInteger($lang) . "'";
        $db->query($sql);
    } else {
        $sql = "UPDATE " . $cfg["tab"]["cat_lang"] . " SET startidartlang='0' WHERE idcat='" . cSecurity::toInteger($idcat) . "' AND idlang='" . cSecurity::toInteger($lang) . "' AND startidartlang='" . cSecurity::toInteger($idartlang) . "'";
        $db->query($sql);
    }

    if ($isstart == 1) {
        // Deactivate time management if article is a start article
        $sql = "SELECT idart FROM " . $cfg["tab"]["cat_art"] . " WHERE idcatart = '" . cSecurity::toInteger($idcatart) . "'";

        $db->query($sql);
        $db->next_record();

        $idart = $db->f("idart");

        $sql = "UPDATE " . $cfg["tab"]["art_lang"] . " SET timemgmt = 0 WHERE idart = '" . cSecurity::toInteger($idart) . "' AND idlang = '" . cSecurity::toInteger($lang) . "'";
        $db->query($sql);
    }
}

/**
 * Create code for one article in all categorys
 *
 * @param int $idart Article ID
 */
function conGenerateCodeForArtInAllCategories($idart) {
    global $lang, $client, $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT idcatart FROM " . $cfg["tab"]["cat_art"] . " WHERE idart = '" . cSecurity::toInteger($idart) . "'";
    $db->query($sql);
    while ($db->next_record()) {
        conSetCodeFlag($db->f("idcatart"));
    }
}

/**
 * Generate code for all articles in a category
 *
 * @param int $idcat Category ID
 */
function conGenerateCodeForAllArtsInCategory($idcat) {
    global $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT idcatart FROM " . $cfg["tab"]["cat_art"] . " WHERE idcat='" . cSecurity::toInteger($idcat) . "'";
    $db->query($sql);
    while ($db->next_record()) {
        conSetCodeFlag($db->f("idcatart"));
    }
}

/**
 * Generate code for the active client
 */
function conGenerateCodeForClient() {
    global $client, $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT A.idcatart
            FROM " . $cfg["tab"]["cat_art"] . " as A, " . $cfg["tab"]["cat"] . " as B
            WHERE B.idclient=''" . cSecurity::toInteger($client) . "' AND B.idcat=A.idcat";
    $db->query($sql);
    while ($db->next_record()) {
        conSetCodeFlag($db->f("idcatart"));
    }
}

/**
 * Create code for all arts using the same layout
 *
 * @param int $idlay Layout-ID
 */
function conGenerateCodeForAllartsUsingLayout($idlay) {
    global $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT idtpl FROM " . $cfg["tab"]["tpl"] . " WHERE idlay='" . cSecurity::toInteger($idlay) . "'";
    $db->query($sql);
    while ($db->next_record()) {
        conGenerateCodeForAllartsUsingTemplate($db->f("idtpl"));
    }
}

/**
 * Create code for all articles using the same module
 *
 * @param int $idmod Module id
 */
function conGenerateCodeForAllartsUsingMod($idmod) {
    global $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT idtpl FROM " . $cfg["tab"]["container"] . " WHERE idmod = '" . cSecurity::toInteger($idmod) . "'";
    $db->query($sql);
    while ($db->next_record()) {
        conGenerateCodeForAllArtsUsingTemplate($db->f("idtpl"));
    }
}

/**
 * Generate code for all articles using one template
 *
 * @param int $idtpl Template-Id
 */
function conGenerateCodeForAllArtsUsingTemplate($idtpl) {
    global $cfg, $lang, $client;

    $db = cRegistry::getDb();
    $db2 = cRegistry::getDb();

    // Search all categories
    $sql = "SELECT
                b.idcat
            FROM
                " . $cfg["tab"]["tpl_conf"] . " AS a,
                " . $cfg["tab"]["cat_lang"] . " AS b,
                " . $cfg["tab"]["cat"] . " AS c
            WHERE
                a.idtpl     = '" . cSecurity::toInteger($idtpl) . "' AND
                b.idtplcfg  = a.idtplcfg AND
                c.idclient  = '" . cSecurity::toInteger($client) . "' AND
                b.idcat     = c.idcat";

    $db->query($sql);

    while ($db->next_record()) {
        $sql = "SELECT idcatart FROM " . $cfg["tab"]["cat_art"] . " WHERE idcat='" . cSecurity::toInteger($db->f("idcat")) . "'";
        $db2->query($sql);
        while ($db2->next_record()) {
            conSetCodeFlag($db2->f("idcatart"));
        }
    }

    // Search all articles
    $sql = "SELECT
                b.idart
            FROM
                " . $cfg["tab"]["tpl_conf"] . " AS a,
                " . $cfg["tab"]["art_lang"] . " AS b,
                " . $cfg["tab"]["art"] . " AS c
            WHERE
                a.idtpl     = '" . cSecurity::toInteger($idtpl) . "' AND
                b.idtplcfg  = a.idtplcfg AND
                c.idclient  = '" . cSecurity::toInteger($client) . "' AND
                b.idart     = c.idart";

    $db->query($sql);

    while ($db->next_record()) {
        $sql = "SELECT idcatart FROM " . $cfg["tab"]["cat_art"] . " WHERE idart='" . cSecurity::toInteger($db->f("idart")) . "'";
        $db2->query($sql);

        while ($db2->next_record()) {
            conSetCodeFlag($db2->f("idcatart"));
        }
    }
}

/**
 * Create code for all articles
 */
function conGenerateCodeForAllArts() {
    global $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT idcatart FROM " . $cfg["tab"]["cat_art"];
    $db->query($sql);
    while ($db->next_record()) {
        conSetCodeFlag($db->f("idcatart"));
    }
}

/**
 * Set code creation flag to true
 *
 * @param int $idcatart Contenido Category-Article-ID
 */
function conSetCodeFlag($idcatart) {
    global $cfg, $client, $cfgClient;

    $db = cRegistry::getDb();

    $sql = "UPDATE " . $cfg["tab"]["cat_art"] . " SET createcode = '1' WHERE idcatart='" . cSecurity::toInteger($idcatart) . "'";
    $db->query($sql);

    /* Setting the createcode flag is not enough due to a bug in the
     * database structure. Remove all con_code entries for a specific
     * idcatart in the con_code table.
     */
    $arr = glob($cfgClient[$client]['code_path'] . "*.*." . $idcatart . ".php");
    foreach ($arr as $file) {
        cFileHandler::remove($file);
    }
}

/**
 * Set articles on/offline for the time management function
 */
function conFlagOnOffline() {
    global $cfg;

    $db = cRegistry::getDb();
    $db2 = cRegistry::getDb();

    // Set all articles which are before our starttime to offline
    $sql = "SELECT idartlang FROM " . $cfg["tab"]["art_lang"] . " WHERE NOW() < datestart AND datestart != '0000-00-00 00:00:00' AND datestart IS NOT NULL AND timemgmt = 1";
    $db->query($sql);
    while ($db->next_record()) {
        $sql = "UPDATE " . $cfg["tab"]["art_lang"] . " SET online = 0 WHERE idartlang = '" . cSecurity::toInteger($db->f("idartlang")) . "'";
        $db2->query($sql);
    }

    // Set all articles which are in between of our start/endtime to online
    $sql = "SELECT idartlang FROM " . $cfg["tab"]["art_lang"] . " WHERE NOW() > datestart AND (NOW() < dateend OR dateend = '0000-00-00 00:00:00') AND " .
            "online = 0 AND timemgmt = 1";
    $db->query($sql);
    while ($db->next_record()) {
        // modified 2007-11-14: Set publish date if article goes online
        $sql = "UPDATE " . $cfg["tab"]["art_lang"] . " SET online = 1, published = datestart " .
                "WHERE idartlang = " . cSecurity::toInteger($db->f("idartlang"));
        $db2->query($sql);
    }

    // Set all articles after our endtime to offline
    $sql = "SELECT idartlang FROM " . $cfg["tab"]["art_lang"] . " WHERE NOW() > dateend AND dateend != '0000-00-00 00:00:00' AND timemgmt = 1";
    $db->query($sql);
    while ($db->next_record()) {
        $sql = "UPDATE " . $cfg["tab"]["art_lang"] . " SET online = 0 WHERE idartlang = '" . cSecurity::toInteger($db->f("idartlang")) . "'";
        $db2->query($sql);
    }
}

/**
 * Move articles for the time management function
 */
function conMoveArticles() {
    global $cfg;

    $db = cRegistry::getDb();
    $db2 = cRegistry::getDb();

    // Perform after-end updates
    $sql = "SELECT idartlang, idart, time_move_cat, time_target_cat, time_online_move FROM " . $cfg["tab"]["art_lang"] . " WHERE NOW() > dateend AND dateend != '0000-00-00 00:00:00' AND timemgmt = 1";

    $db->query($sql);

    while ($db->next_record()) {
        if ($db->f("time_move_cat") == "1") {
            $sql = "UPDATE " . $cfg["tab"]["art_lang"] . " SET timemgmt = 0, online = 0 WHERE idartlang = '" . cSecurity::toInteger($db->f("idartlang")) . "'";
            $db2->query($sql);

            $sql = "UPDATE " . $cfg["tab"]["cat_art"] . " SET idcat = '" . cSecurity::toInteger($db->f("time_target_cat")) . "', createcode = '1' WHERE idart = '" . cSecurity::toInteger($db->f("idart")) . "'";
            $db2->query($sql);

            if ($db->f("time_online_move") == "1") {
                $sql = "UPDATE " . $cfg["tab"]["art_lang"] . " SET online = 1 WHERE idart = '" . cSecurity::toInteger($db->f("idart")) . "'";
            } else {
                $sql = "UPDATE " . $cfg["tab"]["art_lang"] . " SET online = 0 WHERE idart = '" . cSecurity::toInteger($db->f("idart")) . "'";
            }
            $db2->query($sql);

            // execute CEC hook
            cApiCecHook::execute('Contenido.Article.conMoveArticles_Loop', $db->Record);
        }
    }
}

function conCopyTemplateConfiguration($srcidtplcfg) {
    global $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT idtpl FROM " . $cfg["tab"]["tpl_conf"] . " WHERE idtplcfg = '" . cSecurity::toInteger($srcidtplcfg) . "'";
    $db->query($sql);
    if (!$db->next_record()) {
        return false;
    }

    $idtpl = $db->f("idtpl");
    $created = date("Y-m-d H:i:s");

    $sql = "INSERT INTO " . $cfg["tab"]["tpl_conf"] . " (idtpl, created) VALUES ('" . cSecurity::toInteger($idtpl) . "', '" . cSecurity::escapeDB($created, $db) . "')";
    $db->query($sql);

    return $db->getLastInsertedId(($cfg["tab"]["tpl_conf"]));
}

function conCopyContainerConf($srcidtplcfg, $dstidtplcfg) {
    global $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT number, container FROM " . $cfg["tab"]["container_conf"] . " WHERE idtplcfg = '" . cSecurity::toInteger($srcidtplcfg) . "'";
    $db->query($sql);
    $val = array();
    while ($db->next_record()) {
        $val[$db->f("number")] = $db->f("container");
    }

    if (count($val) == 0) {
        return false;
    }

    foreach ($val as $key => $value) {
        //$nextidcontainerc = $db->nextid($cfg["tab"]["container_conf"]);
        $sql = "INSERT INTO " . $cfg["tab"]["container_conf"] . " (idtplcfg, number, container) VALUES ('" . cSecurity::toInteger($dstidtplcfg) . "',
                '" . cSecurity::toInteger($key) . "', '" . cSecurity::escapeDB($value, $db) . "')";
        $db->query($sql);
    }

    return true;
}

function conCopyContent($srcidartlang, $dstidartlang) {
    global $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT idtype, typeid, value, version, author FROM " . $cfg["tab"]["content"] . " WHERE idartlang = '" . cSecurity::toInteger($srcidartlang) . "'";
    $db->query($sql);
    $id = 0;
    $val = array();
    while ($db->next_record()) {
        $id++;
        $val[$id]["idtype"] = $db->f("idtype");
        $val[$id]["typeid"] = $db->f("typeid");
        $val[$id]["value"] = $db->f("value");
        $val[$id]["version"] = $db->f("version");
        $val[$id]["author"] = $db->f("author");
    }

    if (count($val == 0)) {
        return false;
    }

    foreach ($val as $key => $value) {
        //$nextid = $db->nextid($cfg["tab"]["content"]);
        $idtype = $value["idtype"];
        $typeid = $value["typeid"];
        $lvalue = $value["value"];
        $version = $value["version"];
        $author = $value["author"];
        $created = date("Y-m-d H:i:s");

        $sql = "INSERT INTO " . $cfg["tab"]["content"]
                . " ( idartlang, idtype, typeid, value, version, author, created) " .
                "VALUES ('" . cSecurity::toInteger($dstidartlang) . "', '" . cSecurity::toInteger($idtype) . "', '" . cSecurity::toInteger($typeid) . "',
              '" . cSecurity::escapeDB($lvalue, $db) . "', '" . cSecurity::escapeDB($version, $db) . "', '" . cSecurity::escapeDB($author, $db) . "', '" . cSecurity::escapeDB($created, $db) . "')";

        $db->query($sql);
    }
}

function conCopyArtLang($srcidart, $dstidart, $newtitle, $bUseCopyLabel = true) {
    global $cfg, $lang;

    $db = cRegistry::getDb();
    $db2 = cRegistry::getDb();

    $sql = "SELECT idartlang, idlang, idtplcfg, title, pagetitle, summary,
            author, online, redirect, redirect, redirect_url,
            artsort, timemgmt, datestart, dateend, status, free_use_01,
            free_use_02, free_use_03, time_move_cat, time_target_cat,
            time_online_move, external_redirect, locked FROM
            " . $cfg["tab"]["art_lang"] . " WHERE idart = '" . cSecurity::toInteger($srcidart) . "' AND idlang='" . cSecurity::toInteger($lang) . "'";
    $db->query($sql);

    while ($db->next_record()) {
        //$nextid = $db2->nextid($cfg["tab"]["art_lang"]);
        /* Copy the template configuration */
        if ($db->f("idtplcfg") != 0) {
            $newidtplcfg = conCopyTemplateConfiguration($db->f("idtplcfg"));
            conCopyContainerConf($db->f("idtplcfg"), $newidtplcfg);
        }

        $idartlang = $nextid;
        $idart = $dstidart;
        $idlang = $db->f("idlang");
        $idtplcfg = $newidtplcfg;

        if ($newtitle != "") {
            $title = sprintf($newtitle, addslashes($db->f("title")));
        } else {
            if ($bUseCopyLabel == true) {
                $title = sprintf(i18n("%s (Copy)"), addslashes($db->f("title")));
            } else {
                $title = addslashes($db->f("title"));
            }
        }
        $pagetitle = addslashes($db->f("pagetitle"));
        $summary = addslashes($db->f("summary"));
        $created = date("Y-m-d H:i:s");
        $author = $db->f("author");
        $online = 0;
        $redirect = $db->f("redirect");
        $redirecturl = $db->f("redirect_url");
        $artsort = $db->f("artsort");
        $timemgmt = $db->f("timemgmt");
        $datestart = $db->f("datestart");
        $dateend = $db->f("dateend");
        $status = $db->f("status");
        $freeuse01 = $db->f("free_use_01");
        $freeuse02 = $db->f("free_use_02");
        $freeuse03 = $db->f("free_use_03");
        $timemovecat = $db->f("time_move_cat");
        $timetargetcat = $db->f("time_target_cat");
        $timeonlinemove = $db->f("time_online_move");
        $externalredirect = $db->f("external_redirect");
        $locked = $db->f("locked");

        $sql = "INSERT INTO " . $cfg["tab"]["art_lang"] . "
                (idart, idlang, idtplcfg, title,
                pagetitle, summary, created, lastmodified,
                author, online, redirect, redirect_url,
                artsort, timemgmt, datestart, dateend,
                status, free_use_01, free_use_02, free_use_03,
                time_move_cat, time_target_cat, time_online_move,
                external_redirect, locked) VALUES (
                '" . cSecurity::toInteger($idart) . "',
                '" . cSecurity::toInteger($idlang) . "',
                '" . cSecurity::toInteger($idtplcfg) . "',
                '" . cSecurity::escapeDB($title, $db2) . "',
                '" . cSecurity::escapeDB($pagetitle, $db2) . "',
                '" . cSecurity::escapeDB($summary, $db2) . "',
                '" . cSecurity::escapeDB($created, $db2) . "',
                '" . cSecurity::escapeDB($created, $d2b) . "',
                '" . cSecurity::escapeDB($author, $db2) . "',
                '" . cSecurity::toInteger($online) . "',
                '" . cSecurity::escapeDB($redirect, $db2) . "',
                '" . cSecurity::escapeDB($redirecturl, $db2) . "',
                '" . cSecurity::toInteger($artsort) . "',
                '" . cSecurity::toInteger($timemgmt) . "',
                '" . cSecurity::escapeDB($datestart, $db2) . "',
                '" . cSecurity::escapeDB($dateend, $db2) . "',
                '" . cSecurity::toInteger($status) . "',
                '" . cSecurity::toInteger($freeuse01) . "',
                '" . cSecurity::toInteger($freeuse02) . "',
                '" . cSecurity::toInteger($freeuse03) . "',
                '" . cSecurity::toInteger($timemovecat) . "',
                '" . cSecurity::toInteger($timetargetcat) . "',
                '" . cSecurity::toInteger($timeonlinemove) . "',
                '" . cSecurity::escapeDB($externalredirect, $db) . "',
                '" . cSecurity::toInteger($locked) . "')";

        $db2->query($sql);

        conCopyContent($db->f("idartlang"), $db->getLastInsertedId($cfg["tab"]["art_lang"]));

        // execute CEC hook
        cApiCecHook::execute('Contenido.Article.conCopyArtLang_AfterInsert', array(
            'idartlang' => cSecurity::toInteger($idartlang),
            'idart' => cSecurity::toInteger($idart),
            'idlang' => cSecurity::toInteger($idlang),
            'idtplcfg' => cSecurity::toInteger($idtplcfg),
            'title' => cSecurity::escapeDB($title, $db2)
        ));

        // Copy meta tags
        $sql = "SELECT idmetatype, metavalue FROM " . $cfg["tab"]["meta_tag"] . " WHERE idartlang = '" . cSecurity::toInteger($db->f("idartlang")) . "'";
        $db->query($sql);

        while ($db->next_record()) {
            //$nextidmetatag = $db2->nextid($cfg["tab"]["meta_tag"]);
            $metatype = $db->f("idmetatype");
            $metavalue = $db->f("metavalue");
            $sql = "INSERT INTO " . $cfg["tab"]["meta_tag"] . "
                        (idartlang, idmetatype, metavalue)
                        VALUES
                        ('" . cSecurity::toInteger($idartlang) . "', '" . cSecurity::toInteger($metatype) . "', '" . cSecurity::escapeDB($metavalue, $db2) . "')";
            $db2->query($sql);
        }

        // Update keyword list for new article
        conMakeArticleIndex($idartlang, $idart);
    }
}

function conCopyArticle($srcidart, $targetcat = 0, $newtitle = "", $bUseCopyLabel = true) {
    global $cfg, $_cecRegistry;

    $db = cRegistry::getDb();
    $db2 = cRegistry::getDb();

    $sql = "SELECT idclient FROM " . $cfg["tab"]["art"] . " WHERE idart = '" . cSecurity::toInteger($srcidart) . "'";
    $db->query($sql);
    if (!$db->next_record()) {
        return false;
    }

    $idclient = $db->f("idclient");

    $sql = "INSERT INTO " . $cfg["tab"]["art"] . " (idclient) VALUES ('" . cSecurity::toInteger($idclient) . "')";
    $db->query($sql);
    //$dstidart = $db->nextid($cfg["tab"]["art"]);
    $dstidart = $db->getLastInsertedId($cfg["tab"]["art"]);
    conCopyArtLang($srcidart, $dstidart, $newtitle, $bUseCopyLabel);

    // Update category relationship
    $sql = "SELECT idcat, status FROM " . $cfg["tab"]["cat_art"] . " WHERE idart = '" . cSecurity::toInteger($srcidart) . "'";
    $db->query($sql);

    while ($db->next_record()) {
        //$nextid = $db2->nextid($cfg["tab"]["cat_art"]);
        // These are the insert values
        $aFields = array(
            "idcat" => ($targetcat != 0) ? cSecurity::toInteger($targetcat) : cSecurity::toInteger($db->f("idcat")),
            "idart" => cSecurity::toInteger($dstidart),
            "is_start" => 0,
            "status" => ($db->f("status") != '') ? cSecurity::toInteger($db->f("status")) : 0,
            "createcode" => 1
        );

        $sql = "INSERT INTO " . $cfg["tab"]["cat_art"] . " (" . implode(", ", array_keys($aFields)) . ") VALUES (" . implode(", ", array_values($aFields)) . ");";
        $db2->query($sql);

        if ($targetcat != 0) { // If true, exit while routine, only one category entry is needed
            break;
        }
    }

    # Contenido Extension Chain
    # @see docs/techref/plugins/Contenido Extension Chainer.pdf
    #
    # Usage:
    # One could define the file data/config/{environment}/config.local.php
    # with following code.
    #
    # global $_cecRegistry;
    # cInclude("plugins", "extension/extenison.php");
    # $_cecRegistry->addChainFunction("Contenido.Content.CopyArticle", "AdditionalFunction1");
    #
    # If function "AdditionalFunction1" is defined in file extension.php, it would be called via
    # $chainEntry->execute($srcidart, $dstidart);

    $iterator = $_cecRegistry->getIterator("Contenido.Content.CopyArticle");
    while ($chainEntry = $iterator->next()) {
        $chainEntry->execute($srcidart, $dstidart);
    }

    return $dstidart;
}

function conGetTopmostCat($idcat, $minLevel = 0) {
    global $cfg, $client, $lang;

    $db = cRegistry::getDb();

    $sql = "SELECT
                a.name AS name,
                a.idcat AS idcat,
                b.parentid AS parentid,
                c.level AS level
            FROM
                " . $cfg["tab"]["cat_lang"] . " AS a,
                " . $cfg["tab"]["cat"] . " AS b,
                " . $cfg["tab"]["cat_tree"] . " AS c
            WHERE
                a.idlang    = " . (int) $lang . " AND
                b.idclient  = " . (int) $client . " AND
                b.idcat     = " . (int) $idcat . " AND
                c.idcat     = b.idcat AND
                a.idcat     = b.idcat";

    $db->query($sql);
    $db->next_record();

    $name = $db->f("name");
    $parentid = $db->f("parentid");
    $thislevel = $db->f("level");

    if ($parentid != 0 && $thislevel >= $minLevel) {
        return conGetTopmostCat($parentid, $minLevel);
    } else {
        return $idcat;
    }
}

function conSyncArticle($idart, $srclang, $dstlang) {
    global $cfg;

    $db = cRegistry::getDb();
    $db2 = cRegistry::getDb();

    // Check if article has already been synced to target language
    $sql = "SELECT * FROM " . $cfg['tab']['art_lang'] . " WHERE idart = " . (int) $idart . " AND idlang = " . (int) $dstlang;
    $db2->query($sql);

    $sql = "SELECT * FROM " . $cfg["tab"]["art_lang"] . " WHERE idart = " . (int) $idart . " AND idlang = " . (int) $srclang;
    $db->query($sql);

    if ($db->next_record() && ($db2->num_rows() == 0)) {
        $rsSrc = $db->toArray();

        if ($rsSrc["idtplcfg"] != 0) {
            $newidtplcfg = tplcfgDuplicate($rsSrc["idtplcfg"]);
        } else {
            $newidtplcfg = 0;
        }

        // Build fields to insert. NOTE: We don't need to sync the whole record set
        $aFields = array(
            'idart' => $rsSrc['idart'],
            'idlang' => (int) $dstlang,
            'idtplcfg' => (int) $newidtplcfg,
            'title' => $rsSrc['title'],
            'urlname' => $rsSrc['urlname'],
            'pagetitle' => $rsSrc['pagetitle'],
            'summary' => $rsSrc['summary'],
            'created' => $rsSrc['created'],
            'lastmodified' => $rsSrc['lastmodified'],
            'author' => $rsSrc['author'],
            'modifiedby' => $rsSrc['modifiedby'],
            'online' => $rsSrc['online'],
            'redirect' => $rsSrc['redirect'],
            'redirect_url' => $rsSrc['redirect_url'],
            'artsort' => $rsSrc['artsort'],
            'status' => $rsSrc['status'],
            'external_redirect' => $rsSrc['external_redirect'],
        );

        $sql = $db2->buildInsert($cfg["tab"]["art_lang"], $aFields);
        $db2->query($sql);
        $newidartlang = $db2->getLastInsertedId($cfg["tab"]["art_lang"]);

        // execute CEC hook
        $param = array();
        $param['src_art_lang'] = $db->Record;
        $param['dest_art_lang'] = $db2->Record;
        $param['dest_art_lang']['idartlang'] = (int) $newidartlang;
        $param['dest_art_lang']['idlang'] = (int) $dstlang;
        $param['dest_art_lang']['idtplcfg'] = (int) $newidtplcfg;
        cApiCecHook::execute('Contenido.Article.conSyncArticle_AfterInsert', $param);

        // Copy content
        $sql = "SELECT * FROM " . $cfg["tab"]["content"] . " WHERE idartlang = " . (int) $idartlang;
        $db->query($sql);
        while ($db->next_record()) {
            $rs = $db->toArray();
            $oContentColl = new cApiContentCollection();
            $oContentColl->create((int) $newidartlang, $rs['idtype'], $rs['typeid'], $rs['value'], $rs['version'], $rs['author'], $rs['created'], $rs['lastmodified']);
        }

        // Copy meta tags
        $sql = "SELECT idmetatype, metavalue FROM " . $cfg["tab"]["meta_tag"] . " WHERE idartlang = '$idartlang'";
        $db->query($sql);
        while ($db->next_record()) {
            $rs = $db->toArray();
            $oMetaTagColl = new cApiMetaTagCollection();
            $oMetaTagColl->create((int) $newidartlang, $rs['idmetatype'], $rs['metavalue']);
        }
    }
}

/**
 * Checks if an article is a start article of a category.
 * @global  array  $cfg
 * @param  int  $idartlang
 * @param  int  $idcat
 * @param  int  $idlang
 * @param  DB_Contenido|null  $db
 * @return bool
 */
function isStartArticle($idartlang, $idcat, $idlang, $db = null) {
    global $cfg;

    if (!is_object($db)) {
        $db = cRegistry::getDb();
    }

    $sql = "SELECT startidartlang FROM " . $cfg["tab"]["cat_lang"] . "
            WHERE startidartlang=" . (int) $idartlang . " AND idcat=" . (int) $idcat . " AND idlang=" . (int) $idlang;
    $db->query($sql);
    if ($db->next_record()) {
        return true;
    } else {
        return false;
    }
}

/**
 * Returns all categories in which the given article is in.
 *
 * @param   int  $idart  Article ID
 * @param   DB_Contenido|null  $db  If specified, uses the given db object
 * @return  array  Flat array which contains all category id's
 */
function conGetCategoryAssignments($idart, $db = null) {
    global $cfg;

    if (!is_object($db)) {
        $db = cRegistry::getDb();
    }

    $categories = array();

    $sql = "SELECT idcat FROM " . $cfg["tab"]["cat_art"] . " WHERE idart = " . (int) $idart;
    $db->query($sql);

    while ($db->next_record()) {
        $categories[] = $db->f("idcat");
    }

    return $categories;
}

/**
 * Deletes old category article entries and other related entries from other tables.
 * @global  array  $cfgClient
 * @param   int  $idcat
 * @param   int  $idart
 * @param   int  $idartlang
 * @param   int  $client
 * @param   int  $lang
 */
function conRemoveOldCategoryArticle($idcat, $idart, $idartlang, $client, $lang) {
    global $cfgClient;

    // Get category article that will no longer exist
    $oCatArtColl = new cApiCategoryArticleCollection();
    $oCatArt = $oCatArtColl->fetchByCategoryIdAndArticleId($idcat, $idart);
    if (!is_object($oCatArt)) {
        continue;
    }

    $idcatart = $oCatArt->get('idcatart');

    // Delete frome code cache and delete corresponding code
    // @todo: It's better to move this logic to a model class
    $mask = $cfgClient[$client]['code_path'] . '*.' . $idcatart . '.php';
    array_map('unlink', glob($mask));

    // Delete statistics
    $oStatColl = new cApiStatCollection();
    $oStatColl->deleteByCategoryArticleAndLanguage($idcatart, $lang);

    // Delete category article
    $oCatArtColl->delete($idcatart);

    // Remove startidartlang
    if (isStartArticle($idartlang, $idcat, $lang)) {
        $oCatLang = new cApiCategoryLanguage();
        $oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang);
        if ($oCatLang->isLoaded()) {
            $oCatLang->set('startidartlang', 0);
            $oCatLang->store();
        }
    }

    // Delete template configuration
    $oArtLang = new cApiArticleLanguage();
    $oArtLang->loadByArticleAndLanguageId($idart, $lang);
    if ($oArtLang->isLoaded() && $oArtLang->get('idtplcfg') > 0) {
        $oTplCfgColl = new cApiTemplateConfigurationCollection();
        $oTplCfgColl->delete($oArtLang->get('idtplcfg'));
    }
}

?>