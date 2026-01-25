<?php

/**
 * Defines the 'con' related functions in CONTENIDO
 *
 * @package    Core
 * @subpackage Backend
 * @author     Olaf Niemann
 * @author     Jan Lengowski
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// Compatibility: Include new functions.con2.php
cInclude('includes', 'functions.con2.php');

/**
 * Create a new article.
 *
 * Create article version, if versioning state is simple or advanced.
 *
 * @param int $idcat
 * @param int|array $idcatnew
 * @param int $idart
 * @param int $isStart Start article flag (1 or 0)
 * @param int $idtpl
 * @param int $idartlang
 * @param int $idlang
 * @param string $title
 * @param string $summary
 * @param int $artspec
 * @param string $created
 * @param string $lastmodified
 * @param string $author
 * @param int $online
 * @param string $datestart
 * @param string $dateend
 * @param int $artsort
 * @param int $keyart
 * @param int $searchable
 * @param float $sitemapprio
 * @param string $changefreq
 * @return int Id of the new article
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conEditFirstTime(
    $idcat,
    $idcatnew,
    $idart,
    $isStart,
    $idtpl,
    $idartlang,
    $idlang,
    $title,
    $summary,
    $artspec,
    $created,
    $lastmodified,
    $author,
    $online,
    $datestart,
    $dateend,
    $artsort,
    $keyart = 0,
    $searchable = 1,
    $sitemapprio = 0.5,
    $changefreq = ''
): int {
    // Additional globals from send form ($_POST)
    global $urlname, $page_title;
    global $redirect, $redirect_url, $external_redirect;
    global $time_move_cat; // Used to indicate "move to cat"
    global $time_target_cat; // Used to indicate the target category
    global $time_online_move; // Used to indicate if the moved article should be online
    global $timemgmt;

    $isStart = $isStart ? 1 : 0;

    $client = cRegistry::getClientId();
    $lang = cRegistry::getLanguageId();
    $auth = cRegistry::getAuth();

    // Add slashes because single quotes will crash the db
    $page_title = addslashes($page_title ?? '');
    $title = stripslashes($title);
    $redirect_url = stripslashes($redirect_url ?? '');

    if ($isStart == 1) {
        $timemgmt = 0;
    }

    if (!is_array($idcatnew)) {
        $idcatnew = [0];
    }

    $versioning = new cContentVersioning();
    // Create article entry
    $oArtColl = new cApiArticleCollection();
    $oArt = $oArtColl->create($client);
    $idart = cSecurity::toInteger($oArt->get('idart'));

    $urlname = (trim($urlname) == '') ? trim($title) : trim($urlname);
    $urlname = conGetUniqueArticleUrlname($idart, $idlang, $urlname, $idcatnew);

    $status = 0;

    // Create a category article entry
    $oCatArtColl = new cApiCategoryArticleCollection();
    $oCatArt = $oCatArtColl->create($idcat, $idart, $status);
    $idcatart = $oCatArt->get('idcatart');

    $aLanguages = [
        $lang
    ];

    // Table 'con_art_lang', one entry for every language
    foreach ($aLanguages as $curLang) {
        $lastmodified = ($lang == $curLang) ? $lastmodified : '';
        $modifiedby = '';

        if ($online == 1) {
            $published_value = date('Y-m-d H:i:s');
            $publishedby_value = $auth->getUsername();
        } else {
            $published_value = '';
            $publishedby_value = '';
        }

        // Create a stat entry
        $oStatColl = new cApiStatCollection();
        $oStat = $oStatColl->create($idcatart, $curLang, $client, 0);

        // Create an article language entry
        $oArtLangColl = new cApiArticleLanguageCollection();
        $parameters = [
            'idart' => $idart, 'idlang' => $curLang, 'title' => $title, 'urlname' => $urlname,
            'pagetitle' => $page_title, 'summary' => $summary, 'artspec' => $artspec, 'created' => $created,
            'author' => $auth->getUsername(), 'lastmodified' => $lastmodified, 'modifiedby' => $modifiedby,
            'published' => $published_value, 'publishedby' => $publishedby_value, 'online' => $online,
            'redirect' => $redirect, 'redirect_url' => $redirect_url, 'external_redirect' > $external_redirect,
            'artsort' => $artsort, 'timemgmt' => $timemgmt, 'datestart' => $datestart, 'dateend' => $dateend,
            'status' => $status, 'time_move_cat' => $time_move_cat, 'time_target_cat' => $time_target_cat,
            'time_online_move' => $time_online_move, 'locked' => 0, 'free_use_01' => '', 'free_use_02' => '',
            'free_use_03' => '', 'searchable' => $searchable, 'searchmapprio' => $sitemapprio,
            'changefreq' => $changefreq
        ];
        $oArtLang = $oArtLangColl->create($parameters);
        $lastId = $oArtLang->get('idartlang');
        $availableTags = conGetAvailableMetaTagTypes();
        foreach ($availableTags as $key => $value) {
            $tmpValue = isset($value['name']) && isset($_POST['META' . $value['name']]) ? $_POST['META' . $value['name']] : '';
            conSetMetaValue($lastId, $key, $tmpValue);
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
        $curLastmodified = ($lang == $curLang) ? $lastmodified : '';

        $oArtLang = new cApiArticleLanguage();
        $oArtLang->loadByArticleAndLanguageId($idart, $curLang);
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
        $oArtLang->set('searchable', $searchable);
        $oArtLang->set('sitemapprio', $sitemapprio);
        $oArtLang->set('changefreq', $changefreq);
        $oArtLang->set('redirect', $redirect);
        $oArtLang->set('redirect_url', $redirect_url);
        $oArtLang->set('external_redirect', $external_redirect);
        $oArtLang->set('artsort', $artsort);
        $oArtLang->set('datestart', $datestart);
        $oArtLang->set('dateend', $dateend);
        $oArtLang->store();
    }

    switch ($versioning->getState()) {
        case $versioning::STATE_SIMPLE:
        case $versioning::STATE_ADVANCED:
            // Create new Article Language Version Entry
            $parameters = [
                'published' => $published_value,
                'idcat' => $idcat,
                'idcatnew' => $idcatnew,
                'idart' => $idart,
                'isstart' => $isStart,
                'idtpl' => $idtpl,
                'idartlang' => $lastId,
                'idlang' => $idlang,
                'title' => $title,
                'summary' => $summary,
                'artspec' => $artspec,
                'created' => $created,
                'iscurrentversion' => 1,
                'lastmodified' => $lastmodified,
                'author' => $author,
                'online' => $online,
                'artsort' => $artsort,
                'datestart' => $datestart,
                'dateend' => $dateend,
                'keyart' => $keyart,
                'searchable' => $searchable,
                'sitemapprio' => $sitemapprio,
                'changefreq' => $changefreq
            ];

            $versioning->createArticleLanguageVersion($parameters);
            break;
        case $versioning::STATE_DISABLED:
        default:
            break;
    }

    return $idart;
}

/**
 * Edit an existing article.
 * Create a version if versioning state is simple or advanced.
 *
 * @param int $idcat
 * @param array|mixed $idcatnew
 * @param int $idart
 * @param int $isStart Start article flag (1 or 0)
 * @param int $idtpl
 * @param int $idartlang
 * @param int $idlang
 * @param string $title
 * @param string $summary
 * @param int $artspec
 * @param string $created
 * @param string $lastmodified
 * @param string $author
 * @param int $online
 * @param string $datestart
 * @param string $dateend
 * @param int $published
 * @param int $artsort
 * @param int $keyart
 * @param int $searchable
 * @param int $sitemapprio
 * @param string $changefreq
 * @return int|void
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conEditArt(
    $idcat,
    $idcatnew,
    $idart,
    $isStart,
    $idtpl,
    $idartlang,
    $idlang,
    $title,
    $summary,
    $artspec,
    $created,
    $lastmodified,
    $author,
    $online,
    $datestart,
    $dateend,
    $published,
    $artsort,
    $keyart = 0,
    $searchable = 1,
    $sitemapprio = -1,
    $changefreq = 'nothing'
)
{
    // Additional globals from send form ($_POST)
    global $urlname, $page_title;
    global $redirect, $redirect_url, $external_redirect;
    global $time_move_cat; // Used to indicate "move to cat"
    global $time_target_cat; // Used to indicate the target category
    global $time_online_move; // Used to indicate if the moved article should be online
    global $timemgmt;

    $isStart = $isStart ? 1 : 0;

    $client = cRegistry::getClientId();
    $lang = cRegistry::getLanguageId();
    $perm = cRegistry::getPerm();

    // CON-2134 check admin permission
    $isAdmin = cPermission::checkAdminPermission(cRegistry::getAuth()->getPerms());

    $oArtLang = new cApiArticleLanguage($idartlang);
    $locked = cSecurity::toInteger($oArtLang->get('locked'));

    // abort editing if article is locked and user is no admin
    if ($locked && !$isAdmin) {
        return $idart;
    }

    // Add slashes because single quotes will crash the db
    $page_title = addslashes($page_title ?? '');
    $title = stripslashes($title);
    $redirect_url = stripslashes($redirect_url ?? '');

    $urlname = (trim($urlname) == '') ? trim($title) : trim($urlname);
    $urlname = conGetUniqueArticleUrlname($idart, $idlang, $urlname, $idcatnew);

    $usetimemgmt = $timemgmt ? 1 : 0;
    if (
        $timemgmt == '1'
        && (
            ($datestart == '' && $dateend == '')
            || ($datestart == '0000-00-00 00:00:00' && $dateend == '0000-00-00 00:00:00')
        )
    ) {
        $usetimemgmt = 0;
    }

    if ($isStart == 1) {
        $usetimemgmt = 0;
    }

    if (!is_array($idcatnew)) {
        $idcatnew[0] = 0;
    }

    $artLang = new cApiArticleLanguage((int)$idartlang);
    if (!$artLang->isLoaded()) {
        return;
    }

    // Get idtplcfg
    $idTplCfg = $artLang->get('idtplcfg');

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

            // Copy template configuration
            if ($idTplCfg != 0) {
                $newIdTplCfg = conCopyTemplateConfiguration($idTplCfg);
                conCopyContainerConf($idTplCfg, $newIdTplCfg);
            }

            $aLanguages = getLanguagesByClient($client);

            // New statistics entry for each language
            foreach ($aLanguages as $curLang) {
                $oStatColl = new cApiStatCollection();
                $oStatColl->create($curIdcatart, $curLang, $client, 0);
            }
        }
    }

    foreach ($aCatsForArt as $value) {
        if (!in_array($value, $idcatnew)) {
            // Delete category article and other related entries that will no
            // longer exist
            conRemoveOldCategoryArticle($value, $idart, $idartlang, $client, $lang);
        }
    }

    if ($title == '') {
        $title = '--- ' . i18n('Default title') . ' ---';
    }

    $versioning = new cContentVersioning();

    switch ($versioning->getState()) {
        case $versioning::STATE_SIMPLE:
            // update current article
            $artLang->set('title', $title);
            $artLang->set('urlname', $urlname);
            $artLang->set('summary', $summary);
            $artLang->set('artspec', $artspec);
            $artLang->set('created', $created);
            $artLang->set('lastmodified', $lastmodified);
            $artLang->set('modifiedby', $author);
            $artLang->set('timemgmt', $usetimemgmt);
            $artLang->set('redirect', $redirect);
            $artLang->set('external_redirect', $external_redirect);
            $artLang->set('redirect_url', $redirect_url);
            $artLang->set('artsort', $artsort);
            $artLang->set('searchable', $searchable);
            if ($sitemapprio != -1) {
                $artLang->set('sitemapprio', $sitemapprio);
            }
            if ($changefreq != "nothing") {
                $artLang->set('changefreq', $changefreq);
            }
            $artLang->set('published', date('Y-m-d H:i:s', strtotime($published)));

            // If the user has right for makeonline, update some properties.
            if (
                $perm->have_perm_area_action('con', 'con_makeonline')
                || $perm->have_perm_area_action_item('con', 'con_makeonline', $idcat)
            ) {
                $oldOnline = $artLang->get('online');
                if (isset($online)) {
                    $artLang->set('online', $online);
                }

                // Check if old online value was 0, update published data if value
                // changed from 0 to 1
                if ((int)$online == 1 && $oldOnline == 0) {
                    $artLang->set('published', date('Y-m-d H:i:s'));
                    $artLang->set('publishedby', $author);
                }

                $artLang->set('datestart', $datestart);
                $artLang->set('dateend', $dateend);
                $artLang->set('time_move_cat', $time_move_cat);
                $artLang->set('time_target_cat', $time_target_cat);
                $artLang->set('time_online_move', $time_online_move);
            }

            // Update idtplcfg
            if (!empty($newIdTplCfg) && $idTplCfg != $newIdTplCfg) {
                $artLang->set('idtplcfg', $newIdTplCfg);
            }

            $artLang->store();
        case $versioning::STATE_ADVANCED:
            $oldOnline = $artLang->get('online');
            $publishedby = null;
            // Create new Article Language Version Entry
            if ((int)$online == 1 && $oldOnline == 0) {
                $published = date('Y-m-d H:i:s');
                $publishedby = $author;
            }
            $parameters = [
                'idcat' => $idcat,
                'idcatnew' => $idcatnew,
                'idart' => $idart,
                'isstart' => $isStart,
                'idtpl' => $idtpl,
                'idartlang' => $idartlang,
                'idlang' => $idlang,
                'title' => $title,
                'summary' => $summary,
                'artspec' => $artspec,
                'created' => $created,
                'iscurrentversion' => 1,
                'lastmodified' => $lastmodified,
                'published' => $published,
                'author' => $author,
                'artsort' => $artsort,
                'datestart' => $datestart,
                'dateend' => $dateend,
                'keyart' => $keyart,
                'searchable' => $searchable,
                'sitemapprio' => $sitemapprio,
                'changefreq' => $changefreq
            ];

            if ($publishedby) {
                $parameters['publishedby'] = $publishedby;
            }

            if (isset($online)) {
                $parameters['online'] = $online;
            } else {
                $parameters['online'] = $oldOnline;
            }

            $versioning->createArticleLanguageVersion($parameters);

            break;
        case $versioning::STATE_DISABLED:
            $artLang->set('title', $title);
            $artLang->set('urlname', $urlname);
            $artLang->set('summary', $summary);
            $artLang->set('artspec', $artspec);
            $artLang->set('created', $created);
            $artLang->set('lastmodified', $lastmodified);
            $artLang->set('modifiedby', $author);
            $artLang->set('timemgmt', $usetimemgmt);
            $artLang->set('redirect', $redirect);
            $artLang->set('external_redirect', $external_redirect);
            $artLang->set('redirect_url', $redirect_url);
            $artLang->set('artsort', $artsort);
            $artLang->set('searchable', $searchable);
            if ($sitemapprio != -1) {
                $artLang->set('sitemapprio', $sitemapprio);
            }
            if ($changefreq != "nothing") {
                $artLang->set('changefreq', $changefreq);
            }
            $artLang->set('published', date('Y-m-d H:i:s', strtotime($published)));

            // If the user has right for makeonline, update some properties.
            if (
                $perm->have_perm_area_action('con', 'con_makeonline')
                || $perm->have_perm_area_action_item('con', 'con_makeonline', $idcat)
            ) {
                $oldOnline = $artLang->get('online');
                if (isset($online)) {
                    $artLang->set('online', $online);
                }

                // Check if old online value was 0, update published data if value
                // changed from 0 to 1
                if ((int)$online == 1 && $oldOnline == 0) {
                    $artLang->set('published', date('Y-m-d H:i:s'));
                    $artLang->set('publishedby', $author);
                }

                $artLang->set('datestart', $datestart);
                $artLang->set('dateend', $dateend);
                $artLang->set('time_move_cat', $time_move_cat);
                $artLang->set('time_target_cat', $time_target_cat);
                $artLang->set('time_online_move', $time_online_move);
            }

            // Update idtplcfg
            if (!empty($newIdTplCfg) && $idTplCfg != $newIdTplCfg) {
                $artLang->set('idtplcfg', $newIdTplCfg);
            }

            $artLang->store();
        default:
            break;
    }

    // article has been saved, so clear the article cache
    $purge = new cSystemPurge();
    $purge->clearArticleCache(cSecurity::toInteger($idartlang));
}

/**
 * Save a content element and generate index; create content version if
 * versioning state is simple or advanced
 *
 * @param int $idartlang idartlang of the article
 * @param string $type Type of content element
 * @param int $typeid Serial number of the content element
 * @param string $value Content
 * @param bool $force Not used: Was a flag to use existing db instance in global scope
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conSaveContentEntry($idartlang, $type, $typeid, $value, $force = false)
{
    $oType = new cApiType();
    if (!$oType->loadByType($type)) {
        // Couldn't load type...
        return;
    }

    $value = str_replace(cRegistry::getFrontendUrl(), '', $value);
    $value = stripslashes($value);

    $cecIterator = cApiCecRegistry::getInstance()->getIterator('Contenido.Content.SaveContentEntry');
    while ($chainEntry = $cecIterator->next()) {
        $value = $chainEntry->execute($idartlang, $type, $typeid, $value);
    }

    $idtype = $oType->get('idtype');

    // instantiate content
    $content = new cApiContent();
    $content->loadByArticleLanguageIdTypeAndTypeId($idartlang, $idtype, $typeid);

    if (!$content->isLoaded()) {
        $contentColl = new cApiContentCollection();
        $content = $contentColl->create($idartlang, $idtype, $typeid, NULL, NULL);
    }

    // save content and versions
    $versioning = new cContentVersioning();
    $versioning->prepareContentForSaving($idartlang, $content, $value);

    // content entry has been saved, so clear the article cache
    $purge = new cSystemPurge();
    $purge->clearArticleCache(cSecurity::toInteger($idartlang));
}

/**
 * Generate index of article content.
 *
 * This is done by calling the hook 'Contenido.Content.AfterStore'.
 *
 * @param int $idartlang of article to index
 * @param int $idart of article to index
 * @throws cDbException|cException
 */
function conMakeArticleIndex($idartlang, $idart)
{
    $idartlang = cSecurity::toInteger($idartlang);
    $idart = cSecurity::toInteger($idart);

    // get IDs of given article langauge
    if (cRegistry::getArticleLanguageId() == $idartlang) {
        // quite easy if given article is current article
        $idclient = cRegistry::getClientId();
        $idlang = cRegistry::getLanguageId();
        $idcat = cRegistry::getCategoryId();
        $idart = cRegistry::getArticleId();
        $idcatlang = cRegistry::getCategoryLanguageId();
    } else {
        $idclient = 0;
        $idlang = 0;
        $idcatlang = 0;

        // == for other articles these infos have to be read from DB
        // get idclient by idart
        $article = new cApiArticle($idart);
        if ($article->isLoaded()) {
            $idclient = cSecurity::toInteger($article->get('idclient'));
        }
        // get idlang by idartlang
        $articleLanguage = new cApiArticleLanguage($idartlang);
        if ($articleLanguage->isLoaded()) {
            $idlang = cSecurity::toInteger($articleLanguage->get('idlang'));
        }
        // get first idcat by idart
        $coll = new cApiCategoryArticleCollection();
        $categoryIds = $coll->getCategoryIdsByArticleId($idart);
        $idcat = cSecurity::toInteger(array_shift($categoryIds) ?? 0);
        // get idcatlang by idcat & idlang
        $categoryLanguage = new cApiCategoryLanguage();
        $categoryLanguage->loadByCategoryIdAndLanguageId($idcat, $idlang);
        if ($categoryLanguage->isLoaded()) {
            $idcatlang = cSecurity::toInteger($articleLanguage->get('idlang'));
        }
    }

    // build data structure expected by handlers of Contenido.Content.AfterStore
    $articleIds = [
        'idclient' => $idclient,
        'idlang' => $idlang,
        'idcat' => $idcat,
        'idcatlang' => $idcatlang,
        'idart' => $idart,
        'idartlang' => $idartlang,
    ];

    // iterate chain Contenido.Content.AfterStore
    $cecIterator = cApiCecRegistry::getInstance()->getIterator('Contenido.Content.AfterStore');
    while ($chainEntry = $cecIterator->next()) {
        $chainEntry->execute($articleIds);
    }
}

/**
 * Toggle the online status of an article
 *
 * @param int $idart Article Id
 * @param int $lang Language Id
 * @param int $online [optional] if 0 the article will be offline, if 1 article will be online
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conMakeOnline($idart, $lang, $online = -1)
{
    $auth = cRegistry::getAuth();

    $artLang = new cApiArticleLanguage();
    if (!$artLang->loadByArticleAndLanguageId($idart, $lang)) {
        return;
    }

    // Reverse current value
    if ($online === -1) {
        $online = ($artLang->get('online') == 0) ? 1 : 0;
    }

    $artLang->set('online', $online);

    if ($online == 1) {
        // Update published date and publisher
        $artLang->set('published', date('Y-m-d H:i:s'));
        $artLang->set('publishedby', $auth->getUsername());
    }

    $artLang->store();

    // Execute cec hook
    cApiCecHook::execute('Contenido.Article.ConMakeOnline', [
        'idart' => $idart,
        'idlang' => $lang,
        'state' => $online
    ]);
}

/**
 * Set the status from articles to online or offline.
 *
 * @param array $articleIds All articles
 * @param int $idlang
 * @param bool $online
 * @throws cDbException|cException
 */
function conMakeOnlineBulkEditing(array $articleIds, $idlang, $online)
{
    $auth = cRegistry::getAuth();

    $idlang = cSecurity::toInteger($idlang);
    $online = $online ? 1 : 0;

    $articleIds = array_map('intval', $articleIds);

    // get all articles with the given idart and idlang
    $artLangCollection = new cApiArticleLanguageCollection();
    $artLangCollection->select(sprintf(
        "`idart` IN (%s) AND `idlang` = %d",
        implode(',', $articleIds),
        $idlang
    ));

    // iterate over articles and set online flag
    while ($artLang = $artLangCollection->next()) {
        $artLang->set('online', $online);
        if ($online == 1) {
            // update published date and publisher
            $artLang->set('published', date('Y-m-d H:i:s'));
            $artLang->set('publishedby', $auth->getUsername());
        }
        $artLang->store();
    }
}

/**
 * Toggle the lock status of an article
 *
 * @param int $idart Article Id
 * @param int $lang Language Id
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conLock($idart, $lang)
{
    $idart = cSecurity::toInteger($idart);
    $lang = cSecurity::toInteger($lang);

    $artLang = new cApiArticleLanguage();
    if (!$artLang->loadByArticleAndLanguageId($idart, $lang)) {
        return;
    }

    $locked = $artLang->get('locked') == 0 ? 1 : 0;

    $artLang->set('locked', $locked);
    $artLang->store();
}

/**
 * Freeze/Lock more articles.
 *
 * @param array $articleIds All articles
 * @param int $idlang
 * @param bool $lock
 * @throws cDbException|cException
 */
function conLockBulkEditing(array $articleIds, $idlang, $lock)
{
    $idlang = cSecurity::toInteger($idlang);
    $lock = $lock ? 1 : 0;

    $articleIds = array_map('intval', $articleIds);

    // get all articles with the given idart and idlang
    $artLangCollection = new cApiArticleLanguageCollection();
    $artLangCollection->select(sprintf(
        "`idart` IN (%s) AND `idlang` = %d",
        implode(',', $articleIds),
        $idlang
    ));

    // iterate over articles and set online flag
    while ($artLang = $artLangCollection->next()) {
        $artLang->set('locked', $lock);
        $artLang->store();
    }
}

/**
 * Checks if an article is locked or not
 *
 * @param int $idart Article Id
 * @param int $lang Language Id
 * @throws cDbException|cException
 */
function conIsLocked($idart, $lang): bool
{
    $idart = cSecurity::toInteger($idart);
    $lang = cSecurity::toInteger($lang);

    $artLang = new cApiArticleLanguage();
    if (!$artLang->loadByArticleAndLanguageId($idart, $lang)) {
        return false;
    }
    return $artLang->get('locked') == 1;
}

/**
 * Toggle the online status of a category
 *
 * @param int $idcat Id of the category
 * @param int $lang Id of the language
 * @param int $visible Visible status of the category
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conMakeCatOnline($idcat, $lang, $visible)
{
    $idcat = cSecurity::toInteger($idcat);
    $lang = cSecurity::toInteger($lang);

    $catLang = new cApiCategoryLanguage();
    if (!$catLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
        return;
    }

    $visible = $visible == 1 ? 1 : 0;

    $catLang->set('visible', $visible);
    $catLang->set('lastmodified', date('Y-m-d H:i:s'));
    $catLang->store();

    if (cRegistry::getConfigValue('pathresolve_heapcache') === true && $visible != 0) {
        $oPathresolveCacheColl = new cApiPathresolveCacheCollection();
        $oPathresolveCacheColl->deleteByCategoryAndLanguage($idcat, $lang);
    }

    // Execute cec hook
    cApiCecHook::execute('Contenido.Article.ConMakeCatOnline', [
        'idcat' => $idcat,
        'idlang' => $lang,
    ]);
}

/**
 * Sets the public status of the given category and its children for the given language.
 *
 * This is almost the same function as strMakePublic.
 *
 * @param int $idcat Category id
 * @param int $lang Language id
 * @param bool $public Public status of the article to set
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conMakePublic($idcat, $lang, $public)
{
    $idcat = cSecurity::toInteger($idcat);
    $lang = cSecurity::toInteger($lang);
    $public = $public ? 1 : 0;

    foreach (conDeeperCategoriesArray($idcat) as $tmpIdcat) {
        $oCatLang = new cApiCategoryLanguage();
        $oCatLang->loadByCategoryIdAndLanguageId($tmpIdcat, $lang);
        $oCatLang->set('public', $public);
        $oCatLang->set('lastmodified', date('Y-m-d H:i:s'));
        $oCatLang->store();
    }
}

/**
 * Delete an Article and all other related entries
 *
 * @param int $idart Article Id
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conDeleteart($idart)
{
    $idart = cSecurity::toInteger($idart);

    $lang = cRegistry::getLanguageId();
    $client = cRegistry::getClientId();

    // Get article language
    $artLang = new cApiArticleLanguage();
    if (!$artLang->loadByArticleAndLanguageId($idart, $lang)) {
        return;
    }

    $idartlang = cSecurity::toInteger($artLang->get('idartlang'));
    $idtplcfg = cSecurity::toInteger($artLang->get('idtplcfg'));

    $catArtColl = new cApiCategoryArticleCollection();
    $cats = $catArtColl->getIdsByWhereClause('`idart` = ' . $idart);

    // Fetch idcat
    foreach ($cats as $idcat) {
        // Reset startidartlang
        if (isStartArticle($idartlang, $idcat, $lang)) {
            $catLang = new cApiCategoryLanguage();
            $catLang->loadByCategoryIdAndLanguageId($idcat, $lang);
            $catLang->set('startidartlang', 0);
            $catLang->store();
        }
    }

    $contentColl = new cApiContentCollection();
    $contentColl->deleteBy('idartlang', $idartlang);

    // delete article in language itself
    $artLangColl = new cApiArticleLanguageCollection();
    $artLangColl->delete($idartlang);

    // delete all versioning information for article in language
    $oArtLangVersColl = new cApiArticleLanguageVersionCollection();
    $oArtLangVersColl->deleteBy('idartlang', $idartlang);

    if ($idtplcfg != 0) {
        $containerConfColl = new cApiContainerConfigurationCollection();
        $containerConfColl->deleteBy('idtplcfg', $idtplcfg);

        $tplConfColl = new cApiTemplateConfigurationCollection();
        $tplConfColl->delete($idtplcfg);
    }

    // Check if there are remaining languages
    $artLangColl->resetQuery();
    $artLangColl->select('`idart` = ' . $idart);
    if ($artLangColl->next()) {
        return;
    }

    $catArtColl = new cApiCategoryArticleCollection();
    $catArtColl->select('`idart` = ' . $idart);
    while ($oCatArtItem = $catArtColl->next()) {
        // Delete from code cache
        conClearClientCode($client, cSecurity::toInteger($oCatArtItem->get('idcatart')));

        // Delete from 'stat'-table
        $statColl = new cApiStatCollection();
        $statColl->deleteBy('idcatart', cSecurity::toInteger($oCatArtItem->get('idcatart')));
    }

    // delete values from con_cat_art only in the correct language
    $catLangColl = new cApiCategoryLanguageCollection();
    $catLangColl->select('`idlang` = ' . $lang);
    $idcats = array_map('intval', $catLangColl->getAllIds());
    $catArtColl->resetQuery();
    $catArtColl->deleteByWhereClause(sprintf(
        '`idart` = %d AND `idcat` IN (%s)',
        $idart,
        implode(',', $idcats)
    ));

    // delete entry from con_art
    $oArtColl = new cApiArticleCollection();
    $oArtColl->delete($idart);

    // this will delete all keywords associated with the article
    $search = new cSearchIndex();
    $search->start($idart, []);

    // delete articles meta-tags
    $metaTagColl = new cApiMetaTagCollection();
    $metaTagColl->deleteBy('idartlang', $idartlang);

    // Contenido Extension Chain
    // @see docs/techref/plugins/Contenido Extension Chainer.pdf
    $cecIterator = cApiCecRegistry::getInstance()->getIterator('Contenido.Content.DeleteArticle');
    while ($chainEntry = $cecIterator->next()) {
        $chainEntry->execute($idart);
    }

    // delete meta-tags
    $metaTagColl = new cApiMetaTagCollection();
    $metaTagColl->deleteBy('idartlang', $idartlang);

    // delete article, content and meta-tag versions
    $contentVersionColl = new cApiContentVersionCollection();
    $contentVersionColl->deleteBy('idartlang', $idartlang);
    $artLangVersionColl = new cApiArticleLanguageVersionCollection();
    $artLangVersionColl->deleteBy('idartlang', $idartlang);
    $metaTagVersionColl = new cApiMetaTagVersionCollection();
    $metaTagVersionColl->deleteBy('idartlang', $idartlang);

    // CON-2578 call listeners to Contenido.Action.con_deleteart.AfterCall
    $cecIterator = cApiCecRegistry::getInstance()->getIterator('Contenido.Action.con_deleteart.AfterCall');
    while ($chainEntry = $cecIterator->next()) {
        $chainEntry->execute($idart, $idartlang);
    }
}

/**
 * @deprecated [2015-05-21] Use {@see cString::extractNumber()} instead
 */
function extractNumber(&$string)
{
    return cString::extractNumber($string);
}

/**
 * Change the template of a category
 *
 * @param int $idcat Category Id
 * @param int $idtpl Template Id
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conChangeTemplateForCat($idcat, $idtpl)
{
    $idcat = cSecurity::toInteger($idcat);
    $idtpl = cSecurity::toInteger($idtpl);

    $lang = cRegistry::getLanguageId();

    $oCatLang = new cApiCategoryLanguage();
    if (!$oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
        return;
    }

    if ($oCatLang->get('idtplcfg')) {
        // Delete old container configuration
        $oContainerConfColl = new cApiContainerConfigurationCollection();
        $oContainerConfColl->deleteBy('idtplcfg', cSecurity::toInteger($oCatLang->get('idtplcfg')));

        // Delete old template configuration
        $oTplConfColl = new cApiTemplateConfigurationCollection();
        $oTplConfColl->delete(cSecurity::toInteger($oCatLang->get('idtplcfg')));
    }

    // Parameter $idtpl is 0, reset the template
    if ($idtpl == 0) {
        $oCatLang->set('idtplcfg', 0);
        $oCatLang->store();
    } else {
        // Check if a pre-configuration is assigned
        $oTpl = new cApiTemplate();
        $oTpl->loadBy('idtpl', $idtpl);

        if ($oTpl->get('idtplcfg') != 0) {
            // Template is pre-configured, create new configuration
            $oTplConfColl = new cApiTemplateConfigurationCollection();
            $oTplConf = $oTplConfColl->create($idtpl);

            // If there is a preconfiguration of template, copy its settings
            // into template configuration
            $oTplConfColl->copyTemplatePreconfiguration($idtpl, cSecurity::toInteger($oTplConf->get('idtplcfg')));

            // Update category language
            $oCatLang->set('idtplcfg', cSecurity::toInteger($oTplConf->get('idtplcfg')));
            $oCatLang->store();
        } else {
            // Template is not pre-configured, create a new configuration.
            $oTplConfColl = new cApiTemplateConfigurationCollection();
            $oTplConf = $oTplConfColl->create($idtpl);

            // Update category language
            $oCatLang->set('idtplcfg', cSecurity::toInteger($oTplConf->get('idtplcfg')));
            $oCatLang->store();
        }
    }

    conGenerateCodeForAllArtsInCategory($idcat);
}

/**
 * Returns category tree structure.
 *
 * @param int|false $clientId Uses global set client if not set
 * @param int|false $languageId Uses global set language if not set
 * @throws cDbException
 */
function conFetchCategoryTree($clientId = false, $languageId = false): array
{
    return (new cApiCategoryTreeCollection())->getCategoryTreeStructureByClientIdAndLanguageId(
        $clientId ? cSecurity::toInteger($clientId) : cRegistry::getClientId(),
        $languageId ? cSecurity::toInteger($languageId) : cRegistry::getLanguageId()
    );
}

/**
 * Return a list of idcats of all scions of given category.
 *
 * @param int $idcat Category ID to start at
 * @return array Idcats of all scions
 * @throws cDbException
 */
function conDeeperCategoriesArray($idcat): array
{
    return (new cApiCategoryCollection())->getAllCategoryIdsRecursive(
        cSecurity::toInteger($idcat),
        cRegistry::getClientId()
    );
}

/**
 * Recursive function to create a location string
 *
 * @param int $idcat ID of the starting category
 * @param string $seperator Separation string
 * @param string $categoryString Category location string (by reference)
 * @param bool $makeLink Create location string with links
 * @param string $linkClass Stylesheet class for the links
 * @param int $firstTreeElementToUse First navigation Level location string should be printed out
 *         (first level = 0!!)
 * @param int $languageIdToUse Id of language
 * @param bool $final
 * @param bool $useCache
 * @return void
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conCreateLocationString(
    $idcat,
    $seperator,
    &$categoryString,
    $makeLink = false,
    $linkClass = '',
    $firstTreeElementToUse = 0,
    $languageIdToUse = 0,
    $final = true,
    $useCache = false
)
{
    $idcat = cSecurity::toBoolean($idcat);
    if ($idcat == 0) {
        $categoryString = i18n("Lost and found");
        return;
    }

    $cfgClient = cRegistry::getClientConfig();
    $client = cRegistry::getClientId();
    $lang = cRegistry::getLanguageId();
    $sess = cRegistry::getSession();

    $makeLink = cSecurity::toBoolean($makeLink);
    $final = cSecurity::toBoolean($final);
    $useCache = cSecurity::toBoolean($useCache);
    $languageIdToUse = cSecurity::toInteger($languageIdToUse);

    if ($languageIdToUse == 0) {
        $languageIdToUse = $lang;
    }

    $locationStringCache = cRegistry::getAppVar('locationStringCache');
    $locationStringCacheFile = $cfgClient[$client]['cache']['path'] . "locationstring-cache-$languageIdToUse.txt";

    if ($final && $useCache) {
        if (!is_array($locationStringCache)) {
            if (cFileHandler::exists($locationStringCacheFile)) {
                $locationStringCache = unserialize(cFileHandler::read($locationStringCacheFile));
            } else {
                $locationStringCache = [];
            }
            cRegistry::setAppVar('locationStringCache', $locationStringCache);
        }

        if (array_key_exists($idcat, $locationStringCache)) {
            if ($locationStringCache[$idcat]['expires'] > time()) {
                $categoryString = $locationStringCache[$idcat]['name'];
                return;
            }
        }
    }

    $db = cRegistry::getDb();

    $sql = "SELECT a.name AS name, a.idcat AS idcat, b.parentid AS parentid, c.level as level "
        . "FROM `:cat_lang` AS a, `:cat` AS b, `:cat_tree` AS c "
        . "WHERE a.idlang = :idlang AND b.idclient = :idclient AND b.idcat = :idcat AND a.idcat = b.idcat AND c.idcat = b.idcat";

    $sql = $db->prepare($sql, [
        'cat_lang' => cDb::getTableName('cat_lang'),
        'cat' => cDb::getTableName('cat'),
        'cat_tree' => cDb::getTableName('cat_tree'),
        'idlang' => $languageIdToUse,
        'idclient' => $client,
        'idcat' => $idcat
    ]);
    $db->query($sql);
    $db->nextRecord();

    $parentid = 0;
    if ($db->f('level') >= $firstTreeElementToUse) {
        $name = $db->f('name');
        $parentid = $db->f('parentid');

        // create link
        if ($makeLink) {
            $linkUrl = $sess->url("front_content.php?idcat=$idcat");
            $name = '<a href="' . $linkUrl . '" class="' . $linkClass . '">' . $name . '</a>';
        }

        $tmp_cat_str = $name . $seperator . $categoryString;
        $categoryString = $tmp_cat_str;
    }

    if ($parentid != 0) {
        conCreateLocationString(
            $parentid,
            $seperator,
            $categoryString,
            $makeLink,
            $linkClass,
            $firstTreeElementToUse,
            $languageIdToUse,
            false
        );
    } else {
        $sep_length = cString::getStringLength($seperator);
        $str_length = cString::getStringLength($categoryString);
        $tmp_length = $str_length - $sep_length;
        $categoryString = cString::getPartOfString($categoryString, 0, $tmp_length);
    }

    if ($final && $useCache) {
        $locationStringCache[$idcat]['name'] = $categoryString;
        $locationStringCache[$idcat]['expires'] = time() + 3600;

        if (cFileHandler::writeable($cfgClient[$client]['cache']['path'])) {
            cFileHandler::write($locationStringCacheFile, serialize($locationStringCache));
        }
        cRegistry::setAppVar('locationStringCache', $locationStringCache);
    }
}

/**
 * Set a start-article
 *
 * @fixme Do we still need the isstart. The old start compatibility has already been removed ..
 *
 * @param int $idcatart Idcatart of the article
 * @param int $isStart Start article flag (1 or 0)
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conMakeStart($idcatart, $isStart)
{
    // Load category article
    $categoryArticle = new cApiCategoryArticle(cSecurity::toInteger($idcatart));
    if ($categoryArticle->isLoaded()) {
        conSetStartArticle(
            cSecurity::toInteger($categoryArticle->get('idcat')),
            cSecurity::toInteger($categoryArticle->get('idart')),
            cRegistry::getLanguageId(),
            $isStart ? 1 : 0
        );
    }
}

/**
 * Set start-article property of given article in given category of given language.
 *
 * @param int $idcat
 * @param int $idart
 * @param int $lang
 * @param int $isStart Start article flag (1 or 0)
 * @return bool if action was successful
 *
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conSetStartArticle($idcat, $idart, $lang, $isStart): bool
{
    $idcat = cSecurity::toInteger($idcat);
    $idart = cSecurity::toInteger($idart);
    $lang = cSecurity::toInteger($lang);
    $isStart = $isStart ? 1 : 0;

    // load article language
    $articleLanguage = new cApiArticleLanguage();
    $isLoaded = $articleLanguage->loadByArticleAndLanguageId($idart, $lang);

    // deactivate time management of article language if article should be start article
    if ($isLoaded && $isStart == 1) {
        $timemgmt = $articleLanguage->get('timemgmt');
        if ($timemgmt == 1) {
            $articleLanguage->set('timemgmt', 0);
            $isLoaded = $articleLanguage->store();
        }
    }

    // set startidartlang of category language
    $categoryLanguage = new cApiCategoryLanguage();
    if ($isLoaded && $categoryLanguage->loadByCategoryIdAndLanguageId($idcat, $lang)) {
        $startidartlang = $isStart == 1 ? $articleLanguage->get('idartlang') : 0;

        $categoryLanguage->set('startidartlang', $startidartlang);
        $isLoaded = $categoryLanguage->store();

        // PARANOIA: in case of failure rollback timemgmt change
        if (!$isLoaded && isset($timemgmt)) {
            $articleLanguage->set('timemgmt', $timemgmt);
            $isLoaded = $articleLanguage->store();
        }
    }

    // execute CEC hook
    if ($isLoaded) {
        cApiCecHook::execute('Contenido.Article.ConMakeStart', ['idart' => $idart, 'idlang' => $lang]);
    }

    return $isLoaded;
}

/**
 * Handles the start article logic of a new created or edited article.
 *
 * @param int|array $idcatnew
 * @param int $idcat
 * @param int $isStart Start article flag (1 or 0)
 * @param int $idart
 * @param int $lang
 * @param int $idartlang
 * @return void
 * @throws cDbException|cException|cInvalidArgumentException
 * @since CONTENIDO 4.10.2
 */
function conSetStartArticleHandler(
    $idcatnew, int $idcat, int $isStart, int $idart, int $lang, int $idartlang
)
{
    $isStart = $isStart ? 1 : 0;

    $db = cRegistry::getDb();
    $cfg = cRegistry::getConfig();

    // if article should be assigned to multiple categories
    if (is_array($idcatnew)) {
        // if article should still be related to current category
        if (in_array($idcat, $idcatnew)) {
            // if article should be a start article
            if ($isStart == 1) {
                // set as start article of current category
                conSetStartArticle($idcat, $idart, $lang, $isStart);
            }

            // if article should not be start article
            if (!$isStart) {
                // get startidartlang of current category in current language
                $sql = 'SELECT `startidartlang` FROM `%s` WHERE `idcat` = %d AND `idlang` = %d AND `startidartlang` != 0';
                $db->query($sql, cDb::getTableName('cat_lang'), $idcat, $lang);
                if ($db->nextRecord()) {
                    // category has startarticle
                    if ($idartlang == $db->f('startidartlang')) {
                        // current article is currently start article
                        conSetStartArticle($idcat, $idart, $lang, 0);
                    }
                } else {
                    // category has no start article
                    conSetStartArticle($idcat, $idart, $lang, 0);
                }
            }
        }

        // enforce code creation for all categories this article should be related to
        foreach ($idcatnew as $idcat) {
            $sql = 'SELECT `idcatart` FROM `%s` WHERE `idcat` = %d AND `idart` = %d';
            $db->query($sql, cDb::getTableName('cat_art'), $idcat, $idart);
            $db->nextRecord();

            conSetCodeFlag($db->f('idcatart'));
        }
    }
}

/**
 * Create code for one article in all categories
 *
 * @param int $idart Article ID
 * @throws cDbException|cInvalidArgumentException
 */
function conGenerateCodeForArtInAllCategories($idart)
{
    $categoryArticleIds = (new cApiCategoryArticleCollection())
        ->getIdsByWhereClause('`idart` = ' . cSecurity::toInteger($idart));
    conSetCodeFlagBulkEditing(array_map('intval', $categoryArticleIds));
}

/**
 * Generate code for all articles in a category
 *
 * @param int $idcat Category ID
 * @throws cDbException|cInvalidArgumentException
 */
function conGenerateCodeForAllArtsInCategory($idcat)
{
    $categoryArticleIds = (new cApiCategoryArticleCollection())
        ->getIdsByWhereClause('`idcat` = ' . cSecurity::toInteger($idcat));
    conSetCodeFlagBulkEditing(array_map('intval', $categoryArticleIds));
}

/**
 * Generate code for the active client
 *
 * @throws cDbException|cInvalidArgumentException
 */
function conGenerateCodeForClient()
{
    $categoryArticleIds = (new cApiCategoryArticleCollection())->getAllIdsByClientId(cRegistry::getClientId());
    conSetCodeFlagBulkEditing(array_map('intval', $categoryArticleIds));
}

/**
 * Create code for all articles using the same layout.
 *
 * @param int $idlay Layout Id
 * @throws cDbException|cException
 */
function conGenerateCodeForAllartsUsingLayout($idlay)
{
    $templateIds = (new cApiTemplateCollection())->getIdsByLayoutId(cSecurity::toInteger($idlay));
    foreach ($templateIds as $templateId) {
        conGenerateCodeForAllArtsUsingTemplate($templateId);
    }
}

/**
 * Create code for all articles using the same module
 *
 * @param int|int[] $moduleId Module id or list of module ids
 * @throws cDbException|cInvalidArgumentException
 */
function conGenerateCodeForAllartsUsingMod($moduleId)
{
    $moduleId = is_array($moduleId) ? $moduleId : [$moduleId];
    $moduleId = array_map('intval', $moduleId);
    $moduleId = implode(',', $moduleId);
    if (empty($moduleId)) {
        return;
    }

    $rsList = (new cApiContainerCollection())
        ->getFieldsByWhereClause(['idtpl'], '`idmod` IN (' . $moduleId . ')');

    $templateIds = [];
    foreach ($rsList as $rs) {
        $templateIds[] = cSecurity::toInteger($rs['idtpl']);
    }

    conGenerateCodeForAllArtsUsingTemplate($templateIds);
}

/**
 * Generate code for all articles using one template
 *
 * @param int|array $idtpls Template configuration id or list of template configuration ids
 * @throws cDbException
 */
function conGenerateCodeForAllArtsUsingTemplate($idtpls)
{
    $client = cRegistry::getClientId();

    $idtpls = is_array($idtpls) ? $idtpls : [$idtpls];
    $idtpls = array_map('intval', $idtpls);
    $idtpls = implode(',', $idtpls);
    if (empty($idtpls)) {
        return;
    }

    // Search all categories
    $db = cRegistry::getDb();
    $db->query(
        "SELECT
            b.idcat
        FROM
            " . cDb::getTableName('tpl_conf') . " AS a,
            " . cDb::getTableName('cat_lang') . " AS b,
            " . cDb::getTableName('cat') . " AS c
        WHERE
            a.idtpl     IN (" . $idtpls . ")
            AND b.idtplcfg  = a.idtplcfg
            AND c.idclient  = " . $client . "
            AND b.idcat     = c.idcat"
    );

    $categoryArticleColl = new cApiCategoryArticleCollection();

    $categoryArticleIds = [];
    while ($db->nextRecord()) {
        $categoryArticleColl->resetQuery();
        $ids = $categoryArticleColl->getIdsByWhereClause('`idcat` = ' . cSecurity::toInteger($db->f('idcat')));
        $categoryArticleIds = array_merge($categoryArticleIds, $ids);
    }

    // Search all articles
    $db->query(
        "SELECT
            b.idart
        FROM
            " . cDb::getTableName('tpl_conf') . " AS a,
            " . cDb::getTableName('art_lang') . " AS b,
            " . cDb::getTableName('art') . " AS c
        WHERE
            a.idtpl     IN (" . $idtpls . ")
            AND b.idtplcfg  = a.idtplcfg
            AND c.idclient  = " . $client . "
            AND b.idart     = c.idart"
    );

    while ($db->nextRecord()) {
        $categoryArticleColl->resetQuery();
        $ids = $categoryArticleColl->getIdsByWhereClause('`idart` = ' . cSecurity::toInteger($db->f('idart')));
        $categoryArticleIds = array_merge($categoryArticleIds, $ids);
    }

    // set code flag for unique category article ids
    $categoryArticleIds = array_unique($categoryArticleIds);
    foreach ($categoryArticleIds as $idcatart) {
        conSetCodeFlag($idcatart);
    }
}

/**
 * Create code for all articles
 *
 * @throws cDbException
 */
function conGenerateCodeForAllArts()
{
    $db = cRegistry::getDb();
    try {
        $db->query('SELECT `idcatart` FROM `%s`', cDb::getTableName('cat_art'));
    } catch (cDbException $e) {
    }

    while ($db->nextRecord()) {
        conSetCodeFlag(cSecurity::toInteger($db->f('idcatart')));
    }
}

/**
 * Set code creation flag for one category article id to true
 *
 * @param int $idcatart Category article ID
 * @throws cDbException
 */
function conSetCodeFlag($idcatart)
{
    $idcatart = cSecurity::toInteger($idcatart);

    // Set 'createcode' flag
    $coll = new cApiCategoryArticleCollection();
    $coll->setCreateCodeFlag($idcatart);

    // Delete also generated code files from file system
    conClearClientCode(cRegistry::getClientId(), $idcatart);
}

/**
 * Set code creation flag for several category article ids to true
 *
 * @param array $categoryArticleIds List of category article ids
 * @throws cDbException|cInvalidArgumentException
 */
function conSetCodeFlagBulkEditing(array $categoryArticleIds)
{
    if (count($categoryArticleIds) == 0) {
        return;
    }
    $categoryArticleIds = array_map('intval', $categoryArticleIds);

    // Set 'createcode' flag
    $oCatArtColl = new cApiCategoryArticleCollection();
    $oCatArtColl->setCreateCodeFlag($categoryArticleIds);

    conClearClientCode(cRegistry::getClientId(), $categoryArticleIds);
}

/**
 * Set articles on/offline for the time management function
 *
 * @throws cDbException
 */
function conFlagOnOffline()
{
    $db = cRegistry::getDb();

    $oArtLangColl = new cApiArticleLanguageCollection();

    // Set all articles which are before our starttime to offline
    $ids = $oArtLangColl->getIdsByWhereClause(
        "NOW() < `datestart` AND `datestart` != '0000-00-00 00:00:00' AND `datestart` IS NOT NULL AND `timemgmt` = 1"
    );
    $ids = array_map('intval', $ids);
    if (count($ids) > 0) {
        // Set articles offline
        $db->query(
            "UPDATE `%s` SET `online` = 0 WHERE `idartlang` IN (%s)",
            cDb::getTableName('art_lang'),
            implode(',', $ids)
        );

        // Execute cec hook
        cApiCecHook::execute('Contenido.Article.conFlagOnOffline', $ids);
    }

    // Set all articles which are in between of our start/endtime to online
    $oArtLangColl->resetQuery();
    $ids = $oArtLangColl->getIdsByWhereClause(
        "NOW() > `datestart` AND (NOW() < `dateend` OR `dateend` = '0000-00-00 00:00:00') AND `online` = 0 AND `timemgmt` = 1"
    );
    $ids = array_map('intval', $ids);
    if (count($ids) > 0) {
        // Set articles online
        $db->query(
            "UPDATE `%s` SET `online` = 1, `published` = `datestart` WHERE `idartlang` IN (%s)",
            cDb::getTableName('art_lang'),
            implode(',', $ids)
        );

        // Execute cec hook
        cApiCecHook::execute('Contenido.Article.conFlagOnOffline', $ids);
    }

    // Set all articles after our endtime to offline
    $oArtLangColl->resetQuery();
    $ids = $oArtLangColl->getIdsByWhereClause(
        "NOW() > `dateend` AND `dateend` != '0000-00-00 00:00:00' AND `timemgmt` = 1 AND `online` = 1"
    );
    $ids = array_map('intval', $ids);
    if (count($ids) > 0) {
        // Set articles offline
        $db->query(
            "UPDATE `%s` SET `online` = 0 WHERE `idartlang` IN (%s)",
            cDb::getTableName('art_lang'),
            implode(',', $ids)
        );

        // Execute cec hook
        cApiCecHook::execute('Contenido.Article.conFlagOnOffline', $ids);
    }
}

/**
 * Move articles for the time management function
 *
 * @throws cDbException|cInvalidArgumentException
 */
function conMoveArticles()
{
    $db = cRegistry::getDb();

    // Perform after-end updates
    $oArtLangColl = new cApiArticleLanguageCollection();
    $rsList = $oArtLangColl->getFieldsByWhereClause(
        [
            'idartlang',
            'idart',
            'time_move_cat',
            'time_target_cat',
            'time_online_move'
        ],
        "NOW() > `dateend` AND `dateend` != '0000-00-00 00:00:00' AND `timemgmt` = 1 AND `time_move_cat` = 1"
    );

    foreach ($rsList as $rs) {
        $online = $rs['time_online_move'] == '1' ? 1 : 0;
        $idartlang = cSecurity::toInteger($rs['idartlang']);
        $idart = cSecurity::toInteger($rs['idart']);
        $idcat = cSecurity::toInteger($rs['time_target_cat']);

        $db->query(
            'UPDATE `%s` SET `timemgmt` = 0, `online` = 0 WHERE `idartlang` = %d',
            cDb::getTableName('art_lang'),
            $idartlang
        );

        $db->query(
            'UPDATE `%s` SET `idcat` = %d, `createcode` = 1 WHERE `idart` = %d',
            cDb::getTableName('cat_art'),
            $idcat,
            $idart
        );

        $db->query(
            'UPDATE `%s` SET `online` = %d WHERE `idart` = %d',
            cDb::getTableName('art_lang'),
            $online,
            $idart
        );

        // Execute CEC hook
        cApiCecHook::execute('Contenido.Article.conMoveArticles_Loop', $rs);
    }
}

/**
 * Copies template configuration entry from source template configuration.
 *
 * @param int $srcIdTplCfg
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conCopyTemplateConfiguration($srcIdTplCfg): ?int
{
    $srcIdTplCfg = cSecurity::toInteger($srcIdTplCfg);

    $oTemplateConf = new cApiTemplateConfiguration($srcIdTplCfg);
    if (!$oTemplateConf->isLoaded()) {
        return NULL;
    }

    $oNewTemplateConf = (new cApiTemplateConfigurationCollection())->create($oTemplateConf->get('idtpl'));

    return is_object($oNewTemplateConf) ? cSecurity::toInteger($oNewTemplateConf->get('idtplcfg')) : NULL;
}

/**
 * Copies container configuration entries from source container configuration
 * to destination container configuration.
 *
 * @param int $srcIdTplCfg
 * @param int $dstIdTplCfg
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conCopyContainerConf($srcIdTplCfg, $dstIdTplCfg): bool
{
    $srcIdTplCfg = cSecurity::toInteger($srcIdTplCfg);
    $dstIdTplCfg = cSecurity::toInteger($dstIdTplCfg);

    $counter = 0;
    $oContainerConfColl = new cApiContainerConfigurationCollection();
    $oContainerConfColl->select('`idtplcfg` = ' . $srcIdTplCfg);
    while ($oContainerConf = $oContainerConfColl->next()) {
        $oNewContainerConfColl = new cApiContainerConfigurationCollection();
        $oNewContainerConfColl->copyItem($oContainerConf, [
            'idtplcfg' => $dstIdTplCfg
        ]);
        $counter++;
    }
    return $counter > 0;
}

/**
 * Copies content entries from source article language to destination article language.
 *
 * @param int $srcIdArtLang
 * @param int $dstIdArtLang
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conCopyContent($srcIdArtLang, $dstIdArtLang)
{
    $oContentColl = new cApiContentCollection();
    $oContentColl->select('`idartlang` = ' . cSecurity::toInteger($srcIdArtLang));
    while ($oContent = $oContentColl->next()) {
        $oNewContentColl = new cApiContentCollection();
        $oNewContentColl->copyItem($oContent, [
            'idartlang' => cSecurity::toInteger($dstIdArtLang)
        ]);
    }
}

/**
 * Copies meta-tag entries from source article language to destination article language.
 *
 * @param int $srcIdArtLang
 * @param int $dstIdArtLang
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conCopyMetaTags($srcIdArtLang, $dstIdArtLang)
{
    $oMetaTagColl = new cApiMetaTagCollection();
    $oMetaTagColl->select('`idartlang` = ' . cSecurity::toInteger($srcIdArtLang));
    while ($oMetaTag = $oMetaTagColl->next()) {
        $oNewMetaTagColl = new cApiMetaTagCollection();
        $oNewMetaTagColl->copyItem($oMetaTag, [
            'idartlang' => cSecurity::toInteger($dstIdArtLang)
        ]);
    }
}

/**
 * Copy article language entry.
 *
 * @param int $srcIdArt
 * @param int $dstIdArt
 * @param int $dstIdCat
 * @param string $newTitle
 * @param bool $useCopyLabel
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conCopyArtLang($srcIdArt, $dstIdArt, $dstIdCat, $newTitle, $useCopyLabel = true)
{
    $srcIdArt = cSecurity::toInteger($srcIdArt);
    $dstIdArt = cSecurity::toInteger($dstIdArt);
    $dstIdCat = cSecurity::toInteger($dstIdCat);
    $newTitle = cSecurity::toString($newTitle);
    $useCopyLabel = cSecurity::toBoolean($useCopyLabel);

    $auth = cRegistry::getAuth();
    $lang = cRegistry::getLanguageId();
    $newIdTplCfg = null;

    $oSrcArtLang = new cApiArticleLanguage();
    if (!$oSrcArtLang->loadByArticleAndLanguageId($srcIdArt, $lang)) {
        return;
    }

    // Copy the template configuration
    if ($oSrcArtLang->get('idtplcfg') != 0) {
        $newIdTplCfg = conCopyTemplateConfiguration(cSecurity::toInteger($oSrcArtLang->get('idtplcfg')));
        conCopyContainerConf(
            cSecurity::toInteger($oSrcArtLang->get('idtplcfg')),
            cSecurity::toInteger($newIdTplCfg)
        );
    }

    $idart = $dstIdArt;
    $idlang = $oSrcArtLang->get('idlang');
    $idtplcfg = $newIdTplCfg;

    if ($newTitle != '') {
        $title = sprintf($newTitle, $oSrcArtLang->get('title'));
    } elseif ($useCopyLabel) {
        $title = sprintf(i18n('%s (Copy)'), $oSrcArtLang->get('title'));
    } else {
        $title = $oSrcArtLang->get('title');
    }

    // Initializing Article Language
    $oArtLangColl = new cApiArticleLanguageCollection();

    // Create an article language entry
    $fieldsToOverwrite = [
        'idart' => $idart,
        'idlang' => $idlang,
        'idtplcfg' => cSecurity::toInteger($idtplcfg),
        'online' => 0,
        'title' => $title,
        'created' => date('Y-m-d H:i:s'),
        'lastmodified' => date('Y-m-d H:i:s'),
        'modifiedby' => $auth->getUsername(),
        'published' => '',
        'publishedby' => ''
    ];
    $oNewArtLang = $oArtLangColl->copyItem($oSrcArtLang, $fieldsToOverwrite);

    if (!is_object($oNewArtLang)) {
        return;
    }

    // Copy content
    conCopyContent($oSrcArtLang->get('idartlang'), $oNewArtLang->get('idartlang'));

    // Copy meta-tags
    conCopyMetaTags($oSrcArtLang->get('idartlang'), $oNewArtLang->get('idartlang'));

    $urlname = trim(conHtmlSpecialChars(cString::cleanURLCharacters($title)));
    $urlname = conGetUniqueArticleUrlname($idart, $idlang, $urlname, [$dstIdCat]);

    $oNewArtLang->set('urlname', $urlname);
    $oNewArtLang->store();

    // Execute CEC hook
    cApiCecHook::execute('Contenido.Article.conCopyArtLang_AfterInsert', [
        'oldidartlang' => cSecurity::toInteger($oSrcArtLang->get('idartlang')),
        'idartlang' => cSecurity::toInteger($oNewArtLang->get('idartlang')),
        'idart' => cSecurity::toInteger($idart),
        'idlang' => cSecurity::toInteger($idlang),
        'idtplcfg' => cSecurity::toInteger($idtplcfg),
        'title' => $title
    ]);

    // Update keyword list for new article
    $versioning = new cContentVersioning();
    if ($versioning->getState() != $versioning::STATE_ADVANCED) {
        conMakeArticleIndex($oNewArtLang->get('idartlang'), $idart);
    }
}

/**
 * Copy article entry.
 *
 * @param int $srcIdArt
 * @param int $dstIdCat
 * @param string $newTitle
 * @param bool $useCopyLabel
 * @return int|bool
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conCopyArticle($srcIdArt, $dstIdCat = 0, $newTitle = '', $useCopyLabel = true)
{
    $srcIdArt = cSecurity::toInteger($srcIdArt);
    $dstIdCat = cSecurity::toInteger($dstIdCat);
    $newTitle = cSecurity::toString($newTitle);
    $useCopyLabel = cSecurity::toBoolean($useCopyLabel);

    // Get source article
    $oSrcArt = new cApiArticle((int)$srcIdArt);
    if (!$oSrcArt->isLoaded()) {
        return false;
    }
    $idclient = $oSrcArt->get('idclient');

    // Create destination article
    $oArtCollection = new cApiArticleCollection();
    $oNewArt = $oArtCollection->create($idclient);
    if (!is_object($oNewArt)) {
        return false;
    }
    $dstIdArt = $oNewArt->get('idart');

    conCopyArtLang($srcIdArt, $dstIdArt, $dstIdCat, $newTitle, $useCopyLabel);

    // Get source category article entries
    $oCatArtColl = new cApiCategoryArticleCollection();
    $oCatArtColl->select('`idart` = ' . $srcIdArt);
    while ($oCatArt = $oCatArtColl->next()) {
        // Insert destination category article entry
        $oCatArtColl2 = new cApiCategoryArticleCollection();
        $fieldsToOverwrite = [
            'idcat' => ($dstIdCat != 0) ? $dstIdCat : $oCatArt->get('idcat'),
            'idart' => $dstIdArt,
            'status' => ($oCatArt->get('status') !== '') ? $oCatArt->get('status') : 0,
            'createcode' => 1,
            'is_start' => 0
        ];
        $oCatArtColl2->copyItem($oCatArt, $fieldsToOverwrite);

        // If true, exit while routine, only one category entry is needed
        if ($dstIdCat != 0) {
            break;
        }
    }

    // Contenido Extension Chain
    // @see docs/techref/plugins/Contenido Extension Chainer.pdf
    $cecIterator = cApiCecRegistry::getInstance()->getIterator('Contenido.Content.CopyArticle');
    while ($chainEntry = $cecIterator->next()) {
        $chainEntry->execute($srcIdArt, $dstIdArt);
    }

    return $dstIdArt;
}

/**
 * @param int $idcat
 * @param int $minLevel
 * @throws cDbException
 */
function conGetTopmostCat($idcat, $minLevel = 0): int
{
    $idcat = cSecurity::toInteger($idcat);
    $minLevel = cSecurity::toInteger($minLevel);

    $clientId = cRegistry::getClientId();
    $languageId = cRegistry::getLanguageId();

    $db = cRegistry::getDb();

    $sql = "SELECT a.name AS name, a.idcat AS idcat, b.parentid AS parentid, c.level AS level
            FROM `:cat_lang` AS a, `:cat` AS b, `:cat_tree` AS c
            WHERE a.idlang = :idlang AND b.idclient = :idclient AND b.idcat = :idcat
            AND c.idcat = b.idcat AND a.idcat = b.idcat";

    $sql = $db->prepare($sql, [
        'cat_lang' => cDb::getTableName('cat_lang'),
        'cat' => cDb::getTableName('cat'),
        'cat_tree' => cDb::getTableName('cat_tree'),
        'idlang' => $languageId,
        'idclient' => $clientId,
        'idcat' => $idcat
    ]);
    $db->query($sql);
    $db->nextRecord();

    $parentId = cSecurity::toInteger($db->f('parentid'));
    $thisLevel = cSecurity::toInteger($db->f('level'));

    if ($parentId != 0 && $thisLevel >= $minLevel) {
        return conGetTopmostCat($parentId, $minLevel);
    } else {
        return $idcat;
    }
}

/**
 * Synchronizes an article from source language to destination language.
 *
 * @param int $articleId Article id
 * @param int $srcLanguageId Source language id
 * @param int $dstLanguageId Destination language id
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conSyncArticle($articleId, $srcLanguageId, $dstLanguageId)
{
    $articleId = cSecurity::toInteger($articleId);
    $srcLanguageId = cSecurity::toInteger($srcLanguageId);
    $dstLanguageId = cSecurity::toInteger($dstLanguageId);

    $auth = cRegistry::getAuth();

    // Check if article has already been synced to target language
    $dstArtLang = new cApiArticleLanguage();
    $dstArtLang->loadByArticleAndLanguageId($articleId, $dstLanguageId);
    if ($dstArtLang->isLoaded()) {
        // Article already exists in destination language
        return;
    }

    $srcArtLang = new cApiArticleLanguage();
    $srcArtLang->loadByArticleAndLanguageId($articleId, $srcLanguageId);
    if (!$srcArtLang->isLoaded()) {
        // Couldn't load article in source language
        return;
    }
    $srcIdArtLang = $srcArtLang->get('idartlang');

    if ($srcArtLang->get('idtplcfg') != 0) {
        $newIdTplCfg = tplcfgDuplicate($srcArtLang->get('idtplcfg'));
    } else {
        $newIdTplCfg = 0;
    }

    // Create an article language entry for destination language
    $artLangColl = new cApiArticleLanguageCollection();
    $fieldsToOverwrite = [
        'idart' => $articleId,
        'idlang' => $dstLanguageId,
        'idtplcfg' => $newIdTplCfg,
        'artspec' => 0,
        'online' => 0,
        'created' => date('Y-m-d H:i:s'),
        'lastmodified' => date('Y-m-d H:i:s'),
        'modifiedby' => $auth->getUsername(),
        'published' => '',
        'publishedby' => '',
        'timemgmt' => 0,
        'datestart' => '',
        'dateend' => '',
        'status' => 0,
        'time_move_cat' => 0,
        'time_target_cat' => 0,
        'time_online_move' => 0,
        'free_use_01' => '',
        'free_use_02' => '',
        'free_use_03' => ''
    ];
    $artLang = $artLangColl->copyItem($srcArtLang, $fieldsToOverwrite);
    if (!is_object($artLang)) {
        return;
    }

    $newArticleLanguageId = cSecurity::toInteger($artLang->get('idartlang'));

    // Execute CEC hook
    $param = [];
    $param['src_art_lang'] = $srcArtLang->toArray();
    $param['dest_art_lang'] = $dstArtLang->toArray();
    if (!is_array($param['dest_art_lang'])) {
        // This case can happen when synchronizing from another language.
        $param['dest_art_lang'] = [];
    }
    $param['dest_art_lang']['idartlang'] = $newArticleLanguageId;
    $param['dest_art_lang']['idlang'] = $dstLanguageId;
    $param['dest_art_lang']['idtplcfg'] = $newIdTplCfg;
    cApiCecHook::execute('Contenido.Article.conSyncArticle_AfterInsert', $param);

    // Copy content
    conCopyContent($srcIdArtLang, $newArticleLanguageId);

    // Copy meta-tags
    conCopyMetaTags($srcIdArtLang, $newArticleLanguageId);
}

/**
 * Checks if an article is a start article of a category.
 *
 * @param int $idartlang
 * @param int $idcat
 * @param int $idlang
 * @param cDb $db Not used!
 * @throws cDbException|cInvalidArgumentException
 */
function isStartArticle($idartlang, $idcat, $idlang, $db = NULL): bool
{
    return (new cApiCategoryLanguageCollection())->isStartArticle(
        cSecurity::toInteger($idartlang),
        cSecurity::toInteger($idcat),
        cSecurity::toInteger($idlang)
    );
}

/**
 * Returns all categories in which the given article is in.
 *
 * @param int $idart Article ID
 * @param cDb $db Not used!
 * @return int[] Flat array which contains all category id's
 * @throws cDbException|cInvalidArgumentException
 */
function conGetCategoryAssignments($idart, $db = NULL): array
{
    // Return empty array if idart is null (or empty)
    if (empty($idart)) {
        return [];
    }

    $categories = [];
    $oCatArtColl = new cApiCategoryArticleCollection();
    $entries = $oCatArtColl->getFieldsByWhereClause([
        'idcat'
    ], '`idart` = ' . cSecurity::toInteger($idart));
    foreach ($entries as $entry) {
        $categories[] = cSecurity::toInteger($entry['idcat']);
    }

    return $categories;
}

/**
 * Deletes old category article entries and other related entries from other tables.
 *
 * @param int $idcat
 * @param int $idart
 * @param int $idartlang
 * @param int $client
 * @param int $lang
 * @throws cDbException|cException|cInvalidArgumentException
 */
function conRemoveOldCategoryArticle($idcat, $idart, $idartlang, $client, $lang)
{
    $idcat = cSecurity::toInteger($idcat);
    $idart = cSecurity::toInteger($idart);
    $idartlang = cSecurity::toInteger($idartlang);
    $client = cSecurity::toInteger($client);
    $lang = cSecurity::toInteger($lang);

    // Get category article that will no longer exist
    $oCatArtColl = new cApiCategoryArticleCollection();
    $oCatArt = $oCatArtColl->fetchByCategoryIdAndArticleId($idcat, $idart);
    if (!is_object($oCatArt)) {
        return;
    }

    $cfgClient = cRegistry::getClientConfig();

    $idcatart = $oCatArt->get('idcatart');

    $codePath = $cfgClient[$client]['code']['path'];

    // Delete from code cache and delete corresponding code
    /* @var $file SplFileInfo */
    foreach (new DirectoryIterator($codePath) as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $extension = cString::getPartOfString($file, cString::findLastPos($file->getBasename(), '.') + 1);
        if ($extension != 'php') {
            continue;
        }

        if (preg_match('/[0-9*].[0-9*].' . $idcatart . '/s', $file->getBasename())) {
            cFileHandler::remove($codePath . '/' . $file->getFilename());
        }
    }

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

/**
 * Returns for the given article language a urlname which is unique in given categories.
 *
 * @param int $idart
 * @param int $idlang
 * @param string $urlname
 * @param array $idcats
 * @return string
 * @throws cDbException
 * @see CON-2690 and Mod_Rewrite code
 */
function conGetUniqueArticleUrlname($idart, $idlang, $urlname, array $idcats): string
{
    $idart = cSecurity::toInteger($idart);
    $idlang = cSecurity::toInteger($idlang);
    $urlname = cSecurity::toString($urlname);

    // assume given urlname to be unique
    $uniqueUrlname = $urlname;

    // check for uniqueness
    while (!conIsArticleUrlnameUnique($idart, $idlang, $uniqueUrlname, $idcats)) {
        // append five random chars to original urlname
        $uniqueUrlname = $urlname . ' ' . substr(md5(time()), 0, 5);
    }

    return $uniqueUrlname;
}

/**
 * Checks if the given urlname is unique in the given categories.
 *
 * @param int $idart
 * @param int $idlang
 * @param string $urlname
 * @throws cDbException
 * @internal Count number of other article languages of the given language that have the given urlname
 *      and are related to the given categories. Given urlname is unique if there are no other articles.
 */
function conIsArticleUrlnameUnique($idart, $idlang, $urlname, array $idcats): bool
{
    $idart = cSecurity::toInteger($idart);
    $idlang = cSecurity::toInteger($idlang);
    $urlname = cSecurity::toString($urlname);

    $articleCount = 0;
    if (!empty($idcats)) {
        $idcats = array_map('intval', $idcats);
        $sql = "SELECT
                    COUNT(art_lang.idart) AS art_count
                FROM
                    " . cDb::getTableName('art_lang') . " AS art_lang
                INNER JOIN
                    " . cDb::getTableName('cat_art') . " AS cat_art
                        ON art_lang.idart = cat_art.idart
                        AND cat_art.idcat IN (" . implode(',', $idcats) . ")
                WHERE
                    art_lang.idlang = " . cSecurity::toInteger($idlang) . "
                    AND art_lang.idart <> " . cSecurity::toInteger($idart) . "
                    AND LOWER(art_lang.urlname) = LOWER('" . cSecurity::escapeString($urlname) . "')
                GROUP BY
                    cat_art.idcat";
        $db = new cDb();
        $db->query($sql);
        while ($db->nextRecord()) {
            $articleCount = max($articleCount, $db->f('art_count'));
        }
    }

    return $articleCount === 0;
}


/**
 * Clears client code by category article id(s).
 *
 * @param int|int[] $categoryArticleId
 * @since CONTENIDO 4.10.2
 */
function conClearClientCode(int $clientId, $categoryArticleId)
{
    $cfgClient = cRegistry::getClientConfig();

    $codePath = $cfgClient[$clientId]['code']['path'];

    // Delete also generated code files from file system
    if (cFileHandler::exists($codePath)) {
        if (!is_array($categoryArticleId)) {
            $categoryArticleId = [$categoryArticleId];
        }
        $categoryArticleId = array_map('intval', $categoryArticleId);

        /* @var $file SplFileInfo */
        foreach (new DirectoryIterator($codePath) as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            foreach ($categoryArticleId as $id) {
                if (preg_match('/[0-9*].[0-9*].' . $id . '/s', $file->getBasename())) {
                    try {
                        cFileHandler::remove($codePath . '/' . $file->getFilename());
                    } catch (cInvalidArgumentException $e) {
                        // if file does not exist it does not have to be removed
                        error_log('cannot remove ' . $codePath . '/' . $file->getFilename());
                    }
                }
            }
        }
    }
}
