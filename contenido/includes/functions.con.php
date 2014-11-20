<?php
/**
 * Defines the 'con' related functions in CONTENIDO
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Olaf Niemann, Jan Lengowski
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// Compatibility: Include new functions.con2.php
cInclude('includes', 'functions.con2.php');

/**
 * Create a new article
 *
 * @param int $idcat
 * @param int $idcatnew
 * @param int $idart
 * @param unknown_type $isstart
 * @param int $idtpl
 * @param int $idartlang
 * @param int $idlang
 * @param unknown_type $title
 * @param unknown_type $summary
 * @param unknown_type $artspec
 * @param unknown_type $created
 * @param unknown_type $lastmodified
 * @param unknown_type $author
 * @param unknown_type $online
 * @param unknown_type $datestart
 * @param unknown_type $dateend
 * @param unknown_type $artsort
 * @param unknown_type $keyart
 * @param unknown_type $searchable
 * @param unknown_type $sitemapprio
 * @param unknown_type $changefreq
 * @return int Id of the new article
 */
function conEditFirstTime($idcat, $idcatnew, $idart, $isstart, $idtpl, $idartlang, $idlang, $title, $summary, $artspec, $created, $lastmodified, $author, $online, $datestart, $dateend, $artsort, $keyart = 0, $searchable = 1, $sitemapprio = 0.5, $changefreq = '') {
    global $client, $lang, $auth, $urlname, $page_title;
    // Some stuff for the redirect
    global $redirect, $redirect_url, $external_redirect;
    global $time_move_cat; // Used to indicate "move to cat"
    global $time_target_cat; // Used to indicate the target category
    global $time_online_move; // Used to indicate if the moved article should be
                              // online
    global $timemgmt;

    $page_title = addslashes($page_title);
    $title = stripslashes($title);
    $redirect_url = stripslashes($redirect_url);
    $urlname = (trim($urlname) == '')? trim($title) : trim($urlname);

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

    $aLanguages = array(
        $lang
    );
    

    // Table 'con_art_lang', one entry for every language
    foreach ($aLanguages as $curLang) {
        $lastmodified = ($lang == $curLang)? $lastmodified : '';
        $modifiedby = '';

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
        $oArtLang = $oArtLangColl->create($idart, $curLang, $title, $urlname, $page_title, $summary, $artspec, $created, $auth->auth['uname'], $lastmodified, $modifiedby, $published_value, $publishedby_value, $online, $redirect, $redirect_url, $external_redirect, $artsort, $timemgmt, $datestart, $dateend, $status, $time_move_cat, $time_target_cat, $time_online_move, 0, '', '', '', $searchable, $sitemapprio, $changefreq);
        $lastId = $oArtLang->get('idartlang');
        $availableTags = conGetAvailableMetaTagTypes();
        foreach ($availableTags as $key => $value) {
            conSetMetaValue($lastId, $key, $_POST['META' . $value['name']], $child);
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
            // Delete category article and other related entries that will no
            // longer exist
            conRemoveOldCategoryArticle($value, $idart, $idartlang, $client, $lang);
        }
    }

    if (!$title) {
        $title = '--- ' . i18n("Default title") . ' ---';
    }

    // Update article language for all languages
    foreach ($aLanguages as $curLang) {
        $curOnline = ($lang == $curLang)? $online : 0;
        $curLastmodified = ($lang == $curLang)? $lastmodified : '';

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
	
	$versioning = new cContentVersioning();
	$versioningState = $versioning->getState();
	
	switch ($versioningState) {
            case 'simple':
            case 'advanced':
                // Create new Article Language Version Entry
                $parameters = array(
                        'idcat' => $idcat,
                        'idcatnew' => $idcatnew,
                        'idart' => $idart,
                        'isstart' => $isstart,
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
                );
                
                $versioning->createArticleLanguageVersion($parameters);			
                break;
            case 'false': 
            default:
                break;
	}
	   
	return $idart;
}

/**
 * Edit an existing article
 *
 * @param int $idcat
 * @param int $idcatnew
 * @param int $idart
 * @param int $isstart
 * @param int $idtpl
 * @param int $idartlang
 * @param int $idlang
 * @param string $title
 * @param string $summary
 * @param unknown_type $artspec
 * @param unknown_type $created
 * @param unknown_type $lastmodified
 * @param unknown_type $author
 * @param unknown_type $online
 * @param unknown_type $datestart
 * @param unknown_type $dateend
 * @param unknown_type $published
 * @param unknown_type $artsort
 * @param unknown_type $keyart
 * @param unknown_type $searchable
 * @param unknown_type $sitemapprio
 * @param unknown_type $changefreq
 */
function conEditArt($idcat, $idcatnew, $idart, $isstart, $idtpl, $idartlang, $idlang, $title, $summary, $artspec, $created, $lastmodified, $author, $online, $datestart, $dateend, $published, $artsort, $keyart = 0, $searchable = 1, $sitemapprio = -1, $changefreq = 'nothing') {
    global $client, $lang, $redirect, $redirect_url, $external_redirect, $perm;
    global $urlname, $page_title;
    global $time_move_cat, $time_target_cat;
    // Used to indicate if the moved article should be online
    global $time_online_move;
    global $timemgmt;
    // Add slashes because single quotes will crash the db
    $page_title = addslashes($page_title);
    $title = stripslashes($title);
    $redirect_url = stripslashes($redirect_url);

    $urlname = (trim($urlname) == '')? trim($title) : trim($urlname);
    $usetimemgmt = ((int) $timemgmt == 1)? 1 : 0;
    if ($timemgmt == '1' && (($datestart == '' && $dateend == '') || ($datestart == '0000-00-00 00:00:00' && $dateend == '0000-00-00 00:00:00'))) {
        $usetimemgmt = 0;
    }

    if ($isstart == 1) {
        $usetimemgmt = 0;
    }

    if (!is_array($idcatnew)) {
        $idcatnew[0] = 0;
    }

    $artLang = new cApiArticleLanguage((int) $idartlang);
    if (!$artLang->isLoaded()) {
        return;
    }
	
	// Get idtplcfg
    $idTplCfg = $artLang->get('idtplcfg'); 

    // Get all idcats that contain art     //TODOJ: ???
    $oCatArtColl = new cApiCategoryArticleCollection();
    $aCatsForArt = $oCatArtColl->getCategoryIdsByArticleId($idart);
    if (count($aCatsForArt) == 0) {
        $aCatsForArt[0] = 0;
    }

    foreach ($idcatnew as $value) { //TODOJ: ???
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

    foreach ($aCatsForArt as $value) { //TODOJ: ???
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
    $versioningState = $versioning->getState();

    switch ($versioningState) {
        case 'simple':
        case 'advanced':
            // Create new Article Language Version Entry
            $parameters = array(
                'idcat' => $idcat,
                'idcatnew' => $idcatnew,
                'idart' => $idart,
                'isstart' => $isstart,
                'idtpl' => $idtpl,
                'idartlang' => $idartlang,
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
            );
            $versioning->createArticleLanguageVersion($parameters);
            
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
            $artLang->set('published', date("Y-m-d H:i:s", strtotime($published)));

            // If the user has right for makeonline, update some properties.
            if ($perm->have_perm_area_action('con', 'con_makeonline') || $perm->have_perm_area_action_item('con', 'con_makeonline', $idcat)) {
                $oldOnline = $artLang->get('online');
                $artLang->set('online', $online);

                // Check if old online value was 0, update published data if value
                // changed from 0 to 1
                if ((int) $online == 1 && $oldOnline == 0) {
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
            break;
        case 'false':
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
            $artLang->set('published', date("Y-m-d H:i:s", strtotime($published)));

            // If the user has right for makeonline, update some properties.
            if ($perm->have_perm_area_action('con', 'con_makeonline') || $perm->have_perm_area_action_item('con', 'con_makeonline', $idcat)) {
                $oldOnline = $artLang->get('online');
                $artLang->set('online', $online);

                // Check if old online value was 0, update published data if value
                // changed from 0 to 1
                if ((int) $online == 1 && $oldOnline == 0) {
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
    $purge->clearArticleCache($idartlang);
}

/**
 * Save a content element and generate index
 *
 * @param int $idartlang idartlang of the article
 * @param string $type Type of content element
 * @param int $typeid Serial number of the content element
 * @param string $value Content
 * @param bool $bForce Not used: Was a flag to use existing db instance in
 *        global scope
 */
function conSaveContentEntry($idartlang, $type, $typeid, $value, $bForce = false) {
    global $auth, $cfgClient, $client, $_cecRegistry, $lang;
	echo "save";
    $oType = new cApiType();
    if (!$oType->loadByType($type)) {
        // Couldn't load type...
        return;
    }

    $date = date('Y-m-d H:i:s');
    $author = $auth->auth['uname'];
    $value = str_replace(cRegistry::getFrontendUrl(), '', $value);
    $value = stripslashes($value);

    $iterator = $_cecRegistry->getIterator('Contenido.Content.SaveContentEntry');
    while (($chainEntry = $iterator->next()) !== false) {
        $value = $chainEntry->execute($idartlang, $type, $typeid, $value);
    }

    $idtype = $oType->get('idtype');
    
    // Create new entry
    $content = new cApiContent();
    $content->loadByArticleLanguageIdTypeAndTypeId($idartlang, $idtype, $typeid);	
	
    $versioning = new cContentVersioning();
    $versioningState = $versioning->getState();

    switch ($versioningState) {		
        case 'simple':
            // Create Content Version
            $idContent = NULL;
            if ($content->isLoaded()) {
                $idContent = $content->getField('idcontent');
            }
            
            if ($idContent == NULL) {
                $idContent = $versioning->getMaxIdContent() + 1;
            }

            $parameters = array(
                'idcontent' => $idContent,
                'idartlang' => $idartlang,
                'idtype' => $idtype,
                'typeid' => $typeid,
                'value' => $value,
                'author' => $author,
                'created' => $date,
                'lastmodified' => $date
            );

            $versioning = new cContentVersioning();
            $versioning->createContentVersion($parameters);
        case 'false':
            if ($content->isLoaded()) {
                // Update existing entry
                $content->set('value', $value);
                $content->set('author', $author);
                $content->set('lastmodified', date('Y-m-d H:i:s'));
                $content->store();
            } else {
                // Create new entry
                $contentColl = new cApiContentCollection();
                $content = $contentColl->create($idartlang, $idtype, $typeid, $value, 0, $author, $date, $date);
            }    

            // Touch the article to update last modified date
            $lastmodified = date('Y-m-d H:i:s');
            $artLang = new cApiArticleLanguage($idartlang);
            $artLang->set('lastmodified', $lastmodified);
            $artLang->set('modifiedby', $author);
            $artLang->store();

            break;
        case 'advanced':
            // Create Content Version	
            $idContent = NULL;
            if ($content->isLoaded()) {                
                $idContent = $content->getField('idcontent');
            }

            if ($idContent == NULL) {
                $idContent = $versioning->getMaxIdContent() + 1;
            }
            
            $parameters = array(
                'idcontent' => $idContent,
                'idartlang' => $idartlang,
                'idtype' => $idtype,
                'typeid' => $typeid,
                'value' => $value,
                'author' => $author,
                'created' => $date,
                'lastmodified' => $date
            );
            
            $versioning = new cContentVersioning();
            $versioning->createContentVersion($parameters);
        default:
            break;
    }
		
    // content entry has been saved, so clear the article cache
    $purge = new cSystemPurge();
    $purge->clearArticleCache($idartlang);
}

/**
 * Generate index of article content.
 *
 * This is done by calling the hook 'Contenido.Content.AfterStore'.
 *
 * @param int $idartlang of article to index
 * @param int $idart of article to index
 */
function conMakeArticleIndex($idartlang, $idart) {

    // get IDs of given article langauge
    if (cRegistry::getArticleLanguageId() == $idartlang) {
        // quite easy if given article is current article
        $idclient = cRegistry::getClientId();
        $idlang = cRegistry::getLanguageId();
        $idcat = cRegistry::getCategoryId();
        $idart = cRegistry::getArticleId();
        $idcatlang = cRegistry::getCategoryLanguageId();
        $idartlang = cRegistry::getArticleLanguageId();
    } else {
        // == for other articles these infos have to be read from DB
        // get idclient by idart
        $article = new cApiArticle($idart);
        if ($article->isLoaded()) {
            $idclient = $article->get('idclient');
        }
        // get idlang by idartlang
        $articleLanguage = new cApiArticleLanguage($idartlang);
        if ($articleLanguage->isLoaded()) {
            $idlang = $articleLanguage->get('idlang');
        }
        // get first idcat by idart
        $coll = new cApiCategoryArticleCollection();
        $idcat = array_shift($coll->getCategoryIdsByArticleId($idart));
        // get idcatlang by idcat & idlang
        $categoryLanguage = new cApiCategoryLanguage();
        $categoryLanguage->loadByCategoryIdAndLanguageId($idcat, $idlang);
        if ($categoryLanguage->isLoaded()) {
            $idcatlang = $articleLanguage->get('idlang');
        }
    }

    // build data structure expected by handlers of Contenido.Content.AfterStore
    $articleIds = array(
        'idclient' => $idclient,
        'idlang' => $idlang,
        'idcat' => $idcat,
        'idcatlang' => $idcatlang,
        'idart' => $idart,
        'idartlang' => $idartlang
    );

    // iterate chain Contenido.Content.AfterStore
    $iterator = cRegistry::getCecRegistry()->getIterator('Contenido.Content.AfterStore');
    while (false !== $chainEntry = $iterator->next()) {
        $chainEntry->execute($articleIds);
    }
    
}

/**
 * Toggle the online status of an article
 *
 * @param int $idart Article Id
 * @param int $lang Language Id
 * @param int $online optional, if 0 the article will be offline, if 1 article will be online
 */
function conMakeOnline($idart, $lang, $online = -1) {
    $auth = cRegistry::getAuth();

    $artLang = new cApiArticleLanguage();
    if (!$artLang->loadByArticleAndLanguageId($idart, $lang)) {
        return;
    }

    // Reverse current value
    if($online === -1) {
        $online = ($artLang->get('online') == 0)? 1 : 0;
    }

    $artLang->set('online', $online);

    if ($online == 1) {
        // Update published date and publisher
        $artLang->set('published', date('Y-m-d H:i:s'));
        $artLang->set('publishedby', $auth->auth['uname']);
    }

    $artLang->store();
}

/**
 * Set the status from articles to online or offline.
 *
 * @param array $idarts All articles
 * @param int $idlang
 * @param bool $online
 */
function conMakeOnlineBulkEditing($idarts, $idlang, $online) {
    $auth = cRegistry::getAuth();

    // get all articles with the given idart and idlang
    $idartString = implode("','", $idarts);
    $artLangCollection = new cApiArticleLanguageCollection();
    $artLangCollection->select("`idart` IN ('" . $idartString . "') AND `idlang`='" . cSecurity::toInteger($idlang) . "'");

    // iterate over articles and set online flag
    while (($artLang = $artLangCollection->next()) !== false) {
        $artLang->set('online', $online);
        if ($online == 1) {
            // update published date and publisher
            $artLang->set('published', date('Y-m-d H:i:s'));
            $artLang->set('publishedby', $auth->auth['uname']);
        }
        $artLang->store();
    }
}

/**
 * Toggle the lock status of an article
 *
 * @param int $idart Article Id
 * @param int $lang Language Id
 */
function conLock($idart, $lang) {
    $artLang = new cApiArticleLanguage();
    if (!$artLang->loadByArticleAndLanguageId($idart, $lang)) {
        return;
    }

    $locked = ($artLang->get('locked') == 0)? 1 : 0;

    $artLang->set('locked', $locked);
    $artLang->store();
}

/**
 * Freeze/Lock more articles.
 *
 * @param array $idarts All articles
 * @param int $idlang
 * @param bool $lock
 */
function conLockBulkEditing($idarts, $idlang, $lock) {
    // get all articles with the given idart and idlang
    $idartString = implode("','", $idarts);
    $artLangCollection = new cApiArticleLanguageCollection();
    $artLangCollection->select("`idart` IN ('" . $idartString . "') AND `idlang`='" . cSecurity::toInteger($idlang) . "'");

    // iterate over articles and set online flag
    while (($artLang = $artLangCollection->next()) !== false) {
        $artLang->set('locked', $lock);
        $artLang->store();
    }
}

/**
 * Checks if a article is locked or not
 *
 * @param int $idart Article Id
 * @param int $lang Language Id
 * @return bool
 */
function conIsLocked($idart, $lang) {
    $artLang = new cApiArticleLanguage();
    if (!$artLang->loadByArticleAndLanguageId($idart, $lang)) {
        return false;
    }
    return (1 == $artLang->get('locked'));
}

/**
 * Toggle the online status of a category
 *
 * @param int $idcat Id of the category
 * @param int $lang Id of the language
 * @param int $status Status of the category
 */
function conMakeCatOnline($idcat, $lang, $status) {
    global $cfg;

    $catLang = new cApiCategoryLanguage();
    if (!$catLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
        return;
    }

    $status = (1 == $status)? 1 : 0;

    $catLang->set('visible', $status);
    $catLang->set('lastmodified', date('Y-m-d H:i:s'));
    $catLang->store();

    if ($cfg['pathresolve_heapcache'] == true && !$status = 0) {
        $oPathresolveCacheColl = new cApiPathresolveCacheCollection();
        $oPathresolveCacheColl->deleteByCategoryAndLanguage($idcat, $lang);
    }
}

/**
 * Toggle the public status of a category Almost the same function as
 * strMakePublic in functions.str.php (conDeeperCategoriesArray instead of
 * strDeeperCategoriesArray)
 *
 * @param int $idcat Category Id
 * @param int $lang Language Id
 * @param bool $public Public status of the Article
 */
function conMakePublic($idcat, $lang, $public) {
    // $catLang = new cApiCategoryLanguage();
    // if (!$catLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
    // return;
    // }

    // $public = (1 == $public) ? 1 : 0;

    // $catLang->set('public', $public);
    // $catLang->set('lastmodified', date('Y-m-d H:i:s'));
    // $catLang->store();
    $categories = conDeeperCategoriesArray($idcat);
    foreach ($categories as $value) {
        $oCatLang = new cApiCategoryLanguage();
        $oCatLang->loadByCategoryIdAndLanguageId($value, $lang);
        $oCatLang->set('public', $public);
        $oCatLang->set('lastmodified', date('Y-m-d H:i:s'));
        $oCatLang->store();
    }
}

/**
 * Delete an Article and all other related entries
 *
 * @param int $idart Article Id
 */
function conDeleteart($idart) {
    global $lang, $_cecRegistry, $cfgClient, $client;
    
    // Get article language
    $artLang = new cApiArticleLanguage();
    if (!$artLang->loadByArticleAndLanguageId($idart, $lang)) {
        return;
    }

    $idartlang = $artLang->get('idartlang');
    $idtplcfg = $artLang->get('idtplcfg');

    $catArtColl = new cApiCategoryArticleCollection();
    $cats = $catArtColl->getIdsByWhereClause("idart = " . (int) $idart);

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
    $contentColl->deleteBy('idartlang', (int) $idartlang);

    $artLangColl = new cApiArticleLanguageCollection();
    $artLangColl->delete((int) $idartlang);

    if ($idtplcfg != 0) {
        $containerConfColl = new cApiContainerConfigurationCollection();
        $containerConfColl->deleteBy('idtplcfg', (int) $idtplcfg);

        $tplConfColl = new cApiTemplateConfigurationCollection();
        $tplConfColl->delete('idtplcfg', $idtplcfg);
    }

    // Check if there are remaining languages
    $artLangColl->resetQuery();
    $artLangColl->select('idart = ' . (int) $idart);
    if ($artLangColl->next()) {
        return;
    }

    $catArtColl = new cApiCategoryArticleCollection();
    $catArtColl->select('idart = ' . (int) $idart);
    while (($oCatArtItem = $catArtColl->next()) !== false) {
        // Delete from code cache
        if (cFileHandler::exists($cfgClient[$client]['code']['path'])) {
            /**
             * @var $file SplFileInfo
             */
            foreach (new DirectoryIterator($cfgClient[$client]['code']['path']) as $file) {
                if ($file->isFile() === false) {
                    continue;
                }

                $extension = substr($file, strrpos($file->getBasename(), '.') + 1);
                if ($extension != 'php') {
                    continue;
                }

                if (preg_match('/[0-9*].[0-9*].' . $oCatArtItem->get('idcatart') . '/s', $file->getBasename())) {
                    cFileHandler::remove($cfgClient[$client]['code']['path'] . '/' . $file->getFilename());
                }
            }
        }

        // Delete from 'stat'-table
        $statColl = new cApiStatCollection();
        $statColl->deleteBy('idcatart', (int) $oCatArtItem->get('idcatart'));
    }

    // delete values from con_cat_art only in the correct language
    $catLangColl = new cApiCategoryLanguageCollection();
    $catLangColl->select('`idlang`=' . cSecurity::toInteger($lang));
    $idcats = $catLangColl->getAllIds();
    $idcatsString = "('" . implode('\',\'', $idcats) . "')";
    $catArtColl->resetQuery();
    $catArtColl->deleteByWhereClause('`idart`=' . $idart . ' AND `idcat` IN ' . $idcatsString);

    // delete entry from con_art
    $oArtColl = new cApiArticleCollection();
    $oArtColl->delete((int) $idart);

    // this will delete all keywords associated with the article
    $search = new cSearchIndex();
    $search->start($idart, array());

    // Contenido Extension Chain
    // @see docs/techref/plugins/Contenido Extension Chainer.pdf
    $iterator = $_cecRegistry->getIterator("Contenido.Content.DeleteArticle");
    while (($chainEntry = $iterator->next()) !== false) {
        $chainEntry->execute($idart);
    }
    
    // delete article and content versions
    $contentVersionColl = new cApiContentVersionCollection();
    $contentVersionColl->deleteBy('idartlang', (int) $idartlang);
    $artLangVersionColl = new cApiArticleLanguageVersionCollection();
    $artLangVersionColl->deleteBy('idartlang', (int) $idartlang);
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

            // If there is a preconfiguration of template, copy its settings
            // into templateconfiguration
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
 *
 * @param int $client Uses global set client if not set
 * @param int $lang Uses global set language if not set
 * @return array
 */
function conFetchCategoryTree($client = false, $lang = false) {
    if ($client === false) {
        $client = $GLOBALS['client'];
    }
    if ($lang === false) {
        $lang = $GLOBALS['lang'];
    }

    $oCatTreeColl = new cApiCategoryTreeCollection();
    $aCatTree = $oCatTreeColl->getCategoryTreeStructureByClientIdAndLanguageId($client, $lang);

    return $aCatTree;
}

/**
 * Fetch all deeper categories by a given id
 *
 * @param int $idcat Id of category
 * @return array Array with all deeper categories
 */
function conDeeperCategoriesArray($idcat) {
    global $client;

    $oCatColl = new cApiCategoryCollection();
    $aCatIds = $oCatColl->getAllCategoryIdsRecursive($idcat, $client);

    return $aCatIds;
}

/**
 * Recursive function to create an location string
 *
 * @param int $idcat ID of the starting category
 * @param string $seperator Seperation string
 * @param string $catStr Category location string (by reference)
 * @param bool $makeLink Create location string with links
 * @param string $linkClass Stylesheet class for the links
 * @param int $firstTreeElementToUse First navigation Level location string
 *        should be printed out (first level = 0!!)
 * @param int $uselang Id of language
 * @param bool $final
 * @param bool $usecache
 * @return string Location string
 */
function conCreateLocationString($idcat, $seperator, &$catStr, $makeLink = false, $linkClass = '', $firstTreeElementToUse = 0, $uselang = 0, $final = true, $usecache = false) {
    global $cfg, $client, $cfgClient, $lang, $sess;

    if ($idcat == 0) {
        $catStr = i18n("Lost and found");
        return;
    }

    if ($uselang == 0) {
        $uselang = $lang;
    }

    $locationStringCache = cRegistry::getAppVar('locationStringCache');
    $locationStringCacheFile = $cfgClient[$client]['cache']['path'] . "locationstring-cache-$uselang.txt";

    if ($final == true && $usecache == true) {
        if (!is_array($locationStringCache)) {
            if (cFileHandler::exists($locationStringCacheFile)) {
                $locationStringCache = unserialize(cFileHandler::read($locationStringCacheFile));
            } else {
                $locationStringCache = array();
            }
            cRegistry::setAppVar('locationStringCache', $locationStringCache);
        }

        if (array_key_exists($idcat, $locationStringCache)) {
            if ($locationStringCache[$idcat]['expires'] > time()) {
                $catStr = $locationStringCache[$idcat]['name'];
                return;
            }
        }
    }

    $db = cRegistry::getDb();

    $sql = "SELECT a.name AS name, a.idcat AS idcat, b.parentid AS parentid, c.level as level " . "FROM `:cat_lang` AS a, `:cat` AS b, `:cat_tree` AS c " . "WHERE a.idlang = :idlang AND b.idclient = :idclient AND b.idcat = :idcat AND a.idcat = b.idcat AND c.idcat = b.idcat";

    $sql = $db->prepare($sql, array(
        'cat_lang' => $cfg['tab']['cat_lang'],
        'cat' => $cfg['tab']['cat'],
        'cat_tree' => $cfg['tab']['cat_tree'],
        'idlang' => (int) $uselang,
        'idclient' => (int) $client,
        'idcat' => (int) $idcat
    ));
    $db->query($sql);
    $db->nextRecord();

    if ($db->f('level') >= $firstTreeElementToUse) {
        $name = $db->f('name');
        $parentid = $db->f('parentid');

        // create link
        if ($makeLink == true) {
            $linkUrl = $sess->url("front_content.php?idcat=$idcat");
            $name = '<a href="' . $linkUrl . '" class="' . $linkClass . '">' . $name . '</a>';
        }

        $tmp_cat_str = $name . $seperator . $catStr;
        $catStr = $tmp_cat_str;
    }

    if ($parentid != 0) {
        conCreateLocationString($parentid, $seperator, $catStr, $makeLink, $linkClass, $firstTreeElementToUse, $uselang, false);
    } else {
        $sep_length = strlen($seperator);
        $str_length = strlen($catStr);
        $tmp_length = $str_length - $sep_length;
        $catStr = substr($catStr, 0, $tmp_length);
    }

    if ($final == true && $usecache == true) {
        $locationStringCache[$idcat]['name'] = $catStr;
        $locationStringCache[$idcat]['expires'] = time() + 3600;

        if (is_writable($cfgClient[$client]['cache']['path'])) {
            cFileHandler::write($locationStringCacheFile, serialize($locationStringCache));
        }
        cRegistry::setAppVar('locationStringCache', $locationStringCache);
    }
}

/**
 * Set a start-article @fixme Do we still need the isstart.
 * The old start
 * compatibility has already been removed...
 *
 * @param int $idcatart Idcatart of the article
 * @param bool $isstart Start article flag
 */
function conMakeStart($idcatart, $isstart) {
    global $lang;

    // Load category article
    $oCatArt = new cApiCategoryArticle((int) $idcatart);
    if (!$oCatArt->isLoaded()) {
        return;
    }
    $idart = $oCatArt->get('idart');
    $idcat = $oCatArt->get('idcat');

    // Load article language
    $oArtLang = new cApiArticleLanguage();
    if (!$oArtLang->loadByArticleAndLanguageId($idart, $lang)) {
        return;
    }
    $idartlang = $oArtLang->get('idartlang');

    // Update startidartlang for category language
    $oCatLang = new cApiCategoryLanguage();
    if ($oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
        if ($isstart == 1) {
            $oCatLang->set('startidartlang', $idartlang);
        } else {
            $oCatLang->set('startidartlang', 0);
        }
        $oCatLang->store();
    }

    if ($isstart == 1) {
        // Deactivate time management if article is a start article
        $oArtLang->set('timemgmt', 0);
        $oArtLang->store();
    }
}

/**
 * Create code for one article in all categorys
 *
 * @param int $idart Article ID
 */
function conGenerateCodeForArtInAllCategories($idart) {
    $oCatArtColl = new cApiCategoryArticleCollection();
    $ids = $oCatArtColl->getIdsByWhereClause('idart = ' . (int) $idart);
    conSetCodeFlagBulkEditing($ids);
}

/**
 * Generate code for all articles in a category
 *
 * @param int $idcat Category ID
 */
function conGenerateCodeForAllArtsInCategory($idcat) {
    $oCatArtColl = new cApiCategoryArticleCollection();
    $ids = $oCatArtColl->getIdsByWhereClause('idcat = ' . (int) $idcat);
    conSetCodeFlagBulkEditing($ids);
}

/**
 * Generate code for the active client
 */
function conGenerateCodeForClient() {
    global $client;
    $oCatArtColl = new cApiCategoryArticleCollection();
    $ids = $oCatArtColl->getAllIdsByClientId($client);
    conSetCodeFlagBulkEditing($ids);
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
    while ($db->nextRecord()) {
        conGenerateCodeForAllartsUsingTemplate($db->f("idtpl"));
    }
}

/**
 * Create code for all articles using the same module
 *
 * @param int $idmod Module id
 */
function conGenerateCodeForAllartsUsingMod($idmod) {
    $oContainerColl = new cApiContainerCollection();
    $rsList = $oContainerColl->getFieldsByWhereClause(array(
        'idtpl'
    ), 'idmod = ' . (int) $idmod);
    foreach ($rsList as $rs) {
        conGenerateCodeForAllArtsUsingTemplate($rs['idtpl']);
    }
}

/**
 * Generate code for all articles using one template
 *
 * @param int $idtpl Template-Id
 */
function conGenerateCodeForAllArtsUsingTemplate($idtpl) {
    global $cfg, $client;

    $db = cRegistry::getDb();

    $oCatArtColl = new cApiCategoryArticleCollection();

    // Search all categories
    $sql = "SELECT
                b.idcat
            FROM
                " . $cfg['tab']['tpl_conf'] . " AS a,
                " . $cfg['tab']['cat_lang'] . " AS b,
                " . $cfg['tab']['cat'] . " AS c
            WHERE
                a.idtpl     = " . cSecurity::toInteger($idtpl) . " AND
                b.idtplcfg  = a.idtplcfg AND
                c.idclient  = " . cSecurity::toInteger($client) . " AND
                b.idcat     = c.idcat";

    $db->query($sql);

    while ($db->nextRecord()) {
        $oCatArtColl->resetQuery();
        $ids = $oCatArtColl->getIdsByWhereClause('idcat = ' . cSecurity::toInteger($db->f('idcat')));
        foreach ($ids as $id) {
            conSetCodeFlag($id);
        }
    }

    // Search all articles
    $sql = "SELECT
                b.idart
            FROM
                " . $cfg['tab']['tpl_conf'] . " AS a,
                " . $cfg['tab']['art_lang'] . " AS b,
                " . $cfg['tab']['art'] . " AS c
            WHERE
                a.idtpl     = " . cSecurity::toInteger($idtpl) . " AND
                b.idtplcfg  = a.idtplcfg AND
                c.idclient  = " . cSecurity::toInteger($client) . " AND
                b.idart     = c.idart";

    $db->query($sql);

    while ($db->nextRecord()) {
        $oCatArtColl->resetQuery();
        $ids = $oCatArtColl->getIdsByWhereClause('idart = ' . (int) $db->f('idart'));
        foreach ($ids as $id) {
            conSetCodeFlag($id);
        }
    }
}

/**
 * Create code for all articles
 */
function conGenerateCodeForAllArts() {
    global $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT idcatart FROM " . $cfg['tab']['cat_art'];
    $db->query($sql);
    while ($db->nextRecord()) {
        conSetCodeFlag($db->f("idcatart"));
    }
}

/**
 * Set code creation flag for one category article id to true
 *
 * @param int $idcatart Category article id
 */
function conSetCodeFlag($idcatart) {
    global $client, $cfgClient;

    // Set 'createcode' flag
    $oCatArtColl = new cApiCategoryArticleCollection();
    $oCatArtColl->setCreateCodeFlag($idcatart);

    // Delete also generated code files from file system
    if (cFileHandler::exists($cfgClient[$client]['code']['path'])) {
        /**
         * @var $file SplFileInfo
         */
        foreach (new DirectoryIterator($cfgClient[$client]['code']['path']) as $file) {
            if ($file->isFile() === false) {
                continue;
            }

            $extension = substr($file, strrpos($file->getBasename(), '.') + 1);
            if ($extension != 'php') {
                continue;
            }

            if (preg_match('/[0-9*].[0-9*].' . $idcatart . '/s', $file->getBasename())) {
                cFileHandler::remove($cfgClient[$client]['code']['path'] . '/' . $file->getFilename());
            }
        }
    }
}

/**
 * Set code creation flag for several category article ids to true
 *
 * @param array $idcatarts List of category article ids
 */
function conSetCodeFlagBulkEditing(array $idcatarts) {
    global $client, $cfgClient;

    if (count($idcatarts) == 0) {
        return;
    }

    // Set 'createcode' flag
    $oCatArtColl = new cApiCategoryArticleCollection();
    $oCatArtColl->setCreateCodeFlag($idcatarts);

    if (cFileHandler::exists($cfgClient[$client]['code']['path']) === false) {
        return;
    }

    // Delete also generated code files from file system
    foreach ($idcatarts as $id) {
        /**
         * @var $file SplFileInfo
         */
        foreach (new DirectoryIterator($cfgClient[$client]['code']['path']) as $file) {
            if ($file->isFile() === false) {
                continue;
            }

            $extension = substr($file, strrpos($file->getBasename(), '.') + 1);
            if ($extension != 'php') {
                continue;
            }

            if (preg_match('/[0-9*].[0-9*].' . $id . '/s', $file->getBasename())) {
                cFileHandler::remove($cfgClient[$client]['code']['path'] . '/' . $file->getFilename());
            }
        }
    }
}

/**
 * Set articles on/offline for the time management function
 */
function conFlagOnOffline() {
    global $cfg;

    $db = cRegistry::getDb();

    $oArtLangColl = new cApiArticleLanguageCollection();

    // Set all articles which are before our starttime to offline
    $where = "NOW() < datestart AND datestart != '0000-00-00 00:00:00' AND datestart IS NOT NULL AND timemgmt = 1";
    $ids = $oArtLangColl->getIdsByWhereClause($where);
    foreach ($ids as $id) {
        $sql = "UPDATE " . $cfg['tab']['art_lang'] . " SET online = 0 WHERE idartlang = " . (int) $id;
        $db->query($sql);
    }

    // Set all articles which are in between of our start/endtime to online
    $where = "NOW() > datestart AND (NOW() < dateend OR dateend = '0000-00-00 00:00:00') AND " . "online = 0 AND timemgmt = 1";
    $oArtLangColl->resetQuery();
    $ids = $oArtLangColl->getIdsByWhereClause($where);
    foreach ($ids as $id) {
        $sql = "UPDATE " . $cfg['tab']['art_lang'] . " SET online = 1, published = datestart WHERE idartlang = " . (int) $id;
        $db->query($sql);
    }

    // Set all articles after our endtime to offline
    $where = "NOW() > dateend AND dateend != '0000-00-00 00:00:00' AND timemgmt = 1";
    $oArtLangColl->resetQuery();
    $ids = $oArtLangColl->getIdsByWhereClause($where);
    foreach ($ids as $id) {
        $sql = "UPDATE " . $cfg['tab']['art_lang'] . " SET online = 0 WHERE idartlang = " . (int) $id;
        $db->query($sql);
    }
}

/**
 * Move articles for the time management function
 */
function conMoveArticles() {
    global $cfg;

    $db = cRegistry::getDb();

    // Perform after-end updates
    $fields = array(
        'idartlang',
        'idart',
        'time_move_cat',
        'time_target_cat',
        'time_online_move'
    );
    $where = "NOW() > dateend AND dateend != '0000-00-00 00:00:00' AND timemgmt = 1 AND time_move_cat = 1";
    $oArtLangColl = new cApiArticleLanguageCollection();
    $rsList = $oArtLangColl->getFieldsByWhereClause($fields, $where);

    foreach ($rsList as $rs) {
        $online = ($rs['time_online_move'] == '1')? 1 : 0;
        $sql = array();
        $sql[] = 'UPDATE ' . $cfg['tab']['art_lang'] . ' SET timemgmt = 0, online = 0 WHERE idartlang = ' . (int) $rs['idartlang'] . ';';
        $sql[] = 'UPDATE ' . $cfg['tab']['cat_art'] . ' SET idcat = ' . (int) $rs['time_target_cat'] . ', createcode = 1 WHERE idart = ' . (int) $rs['idart'] . ';';
        $sql[] = 'UPDATE ' . $cfg['tab']['art_lang'] . ' SET online = ' . (int) $online . ' WHERE idart = ' . (int) $rs['idart'] . ';';

        // $sql = implode("\n", $sql);
        // cDebug::out($sql);
        $db->query($sql[0]);
        $db->query($sql[1]);
        $db->query($sql[2]);

        // Execute CEC hook
        cApiCecHook::execute('Contenido.Article.conMoveArticles_Loop', $rs);
    }
}

/**
 * Copies template configuration entry from source template configuration.
 *
 * @param int $srcidtplcfg
 */
function conCopyTemplateConfiguration($srcidtplcfg) {
    $oTemplateConf = new cApiTemplateConfiguration((int) $srcidtplcfg);
    if (!$oTemplateConf->isLoaded()) {
        return NULL;
    }

    $oTemplateConfColl = new cApiTemplateConfigurationCollection();
    $oNewTemplateConf = $oTemplateConfColl->create($oTemplateConf->get('idtpl'));
    return (is_object($oNewTemplateConf))? $oNewTemplateConf->get('idtplcfg') : NULL;
}

/**
 * Copies container configuration entries from source container configuration to
 * destination container configuration.
 *
 * @param int $srcidtplcfg
 * @param int $dstidtplcfg
 */
function conCopyContainerConf($srcidtplcfg, $dstidtplcfg) {
    $counter = 0;
    $oContainerConfColl = new cApiContainerConfigurationCollection();
    $oContainerConfColl->select('idtplcfg = ' . cSecurity::toInteger($srcidtplcfg));
    while (($oContainerConf = $oContainerConfColl->next()) !== false) {
        $oNewContainerConfColl = new cApiContainerConfigurationCollection();
        $oNewContainerConfColl->copyItem($oContainerConf, array(
            'idtplcfg' => cSecurity::toInteger($dstidtplcfg)
        ));
        $counter++;
    }
    return ($counter > 0)? true : false;
}

/**
 * Copies content entries from source article language to destination article
 * language.
 *
 * @param int $srcidartlang
 * @param int $dstidartlang
 */
function conCopyContent($srcidartlang, $dstidartlang) {
    $oContentColl = new cApiContentCollection();
    $oContentColl->select('idartlang = ' . cSecurity::toInteger($srcidartlang));
    while (($oContent = $oContentColl->next()) !== false) {
        $oNewContentColl = new cApiContentCollection();
        $oNewContentColl->copyItem($oContent, array(
            'idartlang' => cSecurity::toInteger($dstidartlang)
        ));
    }
}

/**
 * Copies meta tag entries from source article language to destination article
 * language.
 *
 * @param int $srcidartlang
 * @param int $dstidartlang
 */
function conCopyMetaTags($srcidartlang, $dstidartlang) {
    $oMetaTagColl = new cApiMetaTagCollection();
    $oMetaTagColl->select('idartlang = ' . cSecurity::toInteger($srcidartlang));
    while (($oMetaTag = $oMetaTagColl->next()) !== false) {
        $oNewMetaTagColl = new cApiMetaTagCollection();
        $oNewMetaTagColl->copyItem($oMetaTag, array(
            'idartlang' => cSecurity::toInteger($dstidartlang)
        ));
    }
}

/**
 * Copy article language entry.
 *
 * @global array $cfg
 * @global int $lang
 * @param int $srcidart
 * @param int $dstidart
 * @param int $newtitle
 * @param int $useCopyLabel
 */
function conCopyArtLang($srcidart, $dstidart, $newtitle, $useCopyLabel = true) {
    global $auth, $lang;

    $oSrcArtLang = new cApiArticleLanguage();
    if (!$oSrcArtLang->loadByArticleAndLanguageId($srcidart, $lang)) {
        return;
    }

    // Copy the template configuration
    if ($oSrcArtLang->get('idtplcfg') != 0) {
        $newidtplcfg = conCopyTemplateConfiguration($oSrcArtLang->get('idtplcfg'));
        conCopyContainerConf($oSrcArtLang->get('idtplcfg'), $newidtplcfg);
    }

    $idart = $dstidart;
    $idlang = $oSrcArtLang->get('idlang');
    $idtplcfg = $newidtplcfg;

    if ($newtitle != '') {
        $title = sprintf($newtitle, $oSrcArtLang->get('title'));
    } else if ($useCopyLabel == true) {
        $title = sprintf(i18n('%s (Copy)'), $oSrcArtLang->get('title'));
    } else {
        $title = $oSrcArtLang->get('title');
    }

    // Create an article language entry
    $oArtLangColl = new cApiArticleLanguageCollection();
    $fieldsToOverwrite = array(
        'idart' => $idart,
        'idlang' => $idlang,
        'idtplcfg' => cSecurity::toInteger($idtplcfg),
        'online' => 0,
        'title' => $title,
        'created' => date('Y-m-d H:i:s'),
        'lastmodified' => date('Y-m-d H:i:s'),
        'modifiedby' => $auth->auth['uname'],
        'published' => '',
        'publishedby' => ''
    );
    $oNewArtLang = $oArtLangColl->copyItem($oSrcArtLang, $fieldsToOverwrite);

    if (!is_object($oNewArtLang)) {
        return;
    }

    // Copy content
    conCopyContent($oSrcArtLang->get('idartlang'), $oNewArtLang->get('idartlang'));

    // Copy meta tags
    conCopyMetaTags($oSrcArtLang->get('idartlang'), $oNewArtLang->get('idartlang'));

    // Execute CEC hook
    cApiCecHook::execute('Contenido.Article.conCopyArtLang_AfterInsert', array(
        'idartlang' => cSecurity::toInteger($oNewArtLang->get('idartlang')),
        'idart' => cSecurity::toInteger($idart),
        'idlang' => cSecurity::toInteger($idlang),
        'idtplcfg' => cSecurity::toInteger($idtplcfg),
        'title' => $title
    ));

    // Update keyword list for new article
    $versioning = new cContentVersioning();
    if ($versioning->getState() != 'advanced') {
        conMakeArticleIndex($oNewArtLang->get('idartlang'), $idart);
    }
}

/**
 * Copy article entry.
 *
 * @global object $auth
 * @param int $srcidart
 * @param int $targetcat
 * @param string $newtitle
 * @param bool $useCopyLabel
 * @return bool
 */
function conCopyArticle($srcidart, $targetcat = 0, $newtitle = '', $useCopyLabel = true) {
    // Get source article
    $oSrcArt = new cApiArticle((int) $srcidart);
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
    $dstidart = $oNewArt->get('idart');

    conCopyArtLang($srcidart, $dstidart, $newtitle, $useCopyLabel);

    // Get source category article entries
    $oCatArtColl = new cApiCategoryArticleCollection();
    $oCatArtColl->select('idart = ' . (int) $srcidart);
    while (($oCatArt = $oCatArtColl->next()) !== false) {
        // Insert destination category article entry
        $oCatArtColl2 = new cApiCategoryArticleCollection();
        $fieldsToOverwrite = array(
            'idcat' => ($targetcat != 0)? $targetcat : $oCatArt->get('idcat'),
            'idart' => $dstidart,
            'status' => ($oCatArt->get('status') !== '')? $oCatArt->get('status') : 0,
            'createcode' => 1,
            'is_start' => 0
        );
        $oCatArtColl2->copyItem($oCatArt, $fieldsToOverwrite);

        // If true, exit while routine, only one category entry is needed
        if ($targetcat != 0) {
            break;
        }
    }

    // Contenido Extension Chain
    // @see docs/techref/plugins/Contenido Extension Chainer.pdf
    $_cecRegistry = cApiCecRegistry::getInstance();
    $iterator = $_cecRegistry->getIterator('Contenido.Content.CopyArticle');
    while (($chainEntry = $iterator->next()) !== false) {
        $chainEntry->execute($srcidart, $dstidart);
    }

    return $dstidart;
}

/**
 *
 * @todo Returns something....
 * @global array $cfg
 * @global int $client
 * @global int $lang
 * @param int $idcat
 * @param int $minLevel
 * @return int
 */
function conGetTopmostCat($idcat, $minLevel = 0) {
    global $cfg, $client, $lang;

    $db = cRegistry::getDb();

    $sql = "SELECT a.name AS name, a.idcat AS idcat, b.parentid AS parentid, c.level AS level
            FROM `:cat_lang` AS a, `:cat` AS b, `:cat_tree` AS c
            WHERE a.idlang = :idlang AND b.idclient = :idclient AND b.idcat = :idcat
            AND c.idcat = b.idcat AND a.idcat = b.idcat";

    $sql = $db->prepare($sql, array(
        'cat_lang' => $cfg['tab']['cat_lang'],
        'cat' => $cfg['tab']['cat'],
        'cat_tree' => $cfg['tab']['cat_tree'],
        'idlang' => (int) $lang,
        'idclient' => (int) $client,
        'idcat' => (int) $idcat
    ));
    $db->query($sql);
    $db->nextRecord();

    $name = $db->f('name');
    $parentid = $db->f('parentid');
    $thislevel = $db->f('level');

    if ($parentid != 0 && $thislevel >= $minLevel) {
        return conGetTopmostCat($parentid, $minLevel);
    } else {
        return $idcat;
    }
}

/**
 * Synchronizes an article from source language to destination language.
 *
 * @param int $idart Article id
 * @param int $srclang Source language id
 * @param int $dstlang Destination language id
 */
function conSyncArticle($idart, $srclang, $dstlang) {
    $auth = cRegistry::getAuth();

    // Check if article has already been synced to target language
    $dstArtLang = new cApiArticleLanguage();
    $dstArtLang->loadByArticleAndLanguageId($idart, $dstlang);
    if ($dstArtLang->isLoaded()) {
        // Article already exists in detination language
        return;
    }

    $srcArtLang = new cApiArticleLanguage();
    $srcArtLang->loadByArticleAndLanguageId($idart, $srclang);
    if (!$srcArtLang->isLoaded()) {
        // Couldn't load article in source language
        return;
    }
    $srcidartlang = $srcArtLang->get('idartlang');

    if ($srcArtLang->get('idtplcfg') != 0) {
        $newidtplcfg = tplcfgDuplicate($srcArtLang->get('idtplcfg'));
    } else {
        $newidtplcfg = 0;
    }

    // Create an article language entry for destination language
    $artLangColl = new cApiArticleLanguageCollection();
    $fieldsToOverwrite = array(
        'idart' => $idart,
        'idlang' => $dstlang,
        'artspec' => 0,
        'online' => 0,
        'created' => date('Y-m-d H:i:s'),
        'lastmodified' => date('Y-m-d H:i:s'),
        'modifiedby' => $auth->auth['uname'],
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
    );
    $artLang = $artLangColl->copyItem($srcArtLang, $fieldsToOverwrite);
    if (!is_object($artLang)) {
        return;
    }

    $newidartlang = $artLang->get('idartlang');

    // Execute CEC hook
    $param = array();
    $param['src_art_lang'] = $srcArtLang->toArray();
    $param['dest_art_lang'] = $dstArtLang->toArray();
    $param['dest_art_lang']['idartlang'] = cSecurity::toInteger($newidartlang);
    $param['dest_art_lang']['idlang'] = cSecurity::toInteger($dstlang);
    $param['dest_art_lang']['idtplcfg'] = cSecurity::toInteger($newidtplcfg);
    cApiCecHook::execute('Contenido.Article.conSyncArticle_AfterInsert', $param);

    // Copy content
    conCopyContent($srcidartlang, $newidartlang);

    // Copy meta tags
    conCopyMetaTags($srcidartlang, $newidartlang);
}

/**
 * Checks if an article is a start article of a category.
 *
 * @param int $idartlang
 * @param int $idcat
 * @param int $idlang
 * @param cDb|NULL $db (NOT used)
 * @return bool
 */
function isStartArticle($idartlang, $idcat, $idlang, $db = NULL) {
    $oCatLangColl = new cApiCategoryLanguageCollection();
    return $oCatLangColl->isStartArticle($idartlang, $idcat, $idlang);
}

/**
 * Returns all categories in which the given article is in.
 *
 * @param int $idart Article ID
 * @param cDb|NULL $db If specified, uses the given db object (NOT used)
 * @return array Flat array which contains all category id's
 */
function conGetCategoryAssignments($idart, $db = NULL) {

	// Return empty array if idart is null (or empty)
	if (empty($idart)) {
		return array();
	}

    $categories = array();
    $oCatArtColl = new cApiCategoryArticleCollection();
    $entries = $oCatArtColl->getFieldsByWhereClause(array(
        'idcat'
    ), 'idart = ' . (int) $idart);
    foreach ($entries as $entry) {
        $categories[] = $entry['idcat'];
    }
    return $categories;
}

/**
 * Deletes old category article entries and other related entries from other
 * tables.
 *
 * @global array $cfgClient
 * @param int $idcat
 * @param int $idart
 * @param int $idartlang
 * @param int $client
 * @param int $lang
 */
function conRemoveOldCategoryArticle($idcat, $idart, $idartlang, $client, $lang) {
    global $cfgClient;

    // Get category article that will no longer exist
    $oCatArtColl = new cApiCategoryArticleCollection();
    $oCatArt = $oCatArtColl->fetchByCategoryIdAndArticleId($idcat, $idart);
    if (!is_object($oCatArt)) {
        return;
    }

    $idcatart = $oCatArt->get('idcatart');

    // Delete frome code cache and delete corresponding code
    /**
     * @var $file SplFileInfo
     */
    foreach (new DirectoryIterator($cfgClient[$client]['code']['path']) as $file) {
        if ($file->isFile() === false) {
            continue;
        }

        $extension = substr($file, strrpos($file->getBasename(), '.') + 1);
        if ($extension != 'php') {
            continue;
        }

        if (preg_match('/[0-9*].[0-9*].' . $idcatart . '/s', $file->getBasename())) {
            cFileHandler::remove($cfgClient[$client]['code']['path'] . '/' . $file->getFilename());
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

?>