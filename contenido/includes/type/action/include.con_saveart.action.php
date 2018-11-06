<?php

/**
 * Backend action file con_saveart
 *
 * @package    Core
 * @subpackage Backend
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$idtpl           = isset($idtpl) ? $idtpl : false;
$artspec         = isset($artspec) ? $artspec : '';
$online          = isset($online) || isset($timemgmt) ? $online : false;
$searchable      = isset($searchable) ? $searchable : false;
$publishing_date = isset($publishing_date) ? $publishing_date : false;

// remember old values to be passed to listeners of Contenido.Action.con_saveart.AfterCall
$oldData = array();

if (isset($title) && ($perm->have_perm_area_action($area, "con_edit") || $perm->have_perm_area_action_item($area, "con_edit", $idcat))  && ((int) $locked === 0 || $admin )) {

	// get idartlang
	if (!isset($idartlang) || $idartlang == 0) {
		$db->query("
            SELECT idartlang
            FROM " . $cfg["tab"]["art_lang"] . "
            WHERE idart = '$idart'
                AND idlang = '$lang'");
		$db->nextRecord();
		$idartlang = $db->f("idartlang");
	}

    if (1 == $tmp_firstedit) {
        // insert article
        $idart = conEditFirstTime(
            $idcat, $idcatnew, $idart, $is_start, $idtpl, $idartlang, $lang, $title, $summary, $artspec,
            $created, $lastmodified, $author, $online, $datestart, $dateend, $artsort, 0, $searchable
        );

        // build notification
        $tmp_notification = $notification->returnNotification("ok", i18n("Changes saved"));

        // if article should be related to current category
        if (in_array($idcat, $idcatnew)) {
            // if article should be startarticle
            if ($is_start == 1) {
                // set as startarticle of current category
                conSetStartArticle($idcat, $idart, $lang, $is_start);
            }

            // if article should not be startarticle
            if (!isset($is_start)) {
                // get startidartlang of current category in current language
                $db->query("
                    SELECT *
                    FROM " . $cfg["tab"]["cat_lang"] . "
                    WHERE idcat = '$idcat'
                        AND idlang = '$lang'
                        AND startidartlang != '0'");
                if ($db->nextRecord()) {
                    // category has startarticle
                    if ($idartlang == $db->f('startidartlang')) {
                        // current article is currently startarticle
                        conSetStartArticle($idcat, $idart, $lang, 0);
                    } else {
                        // another article is currently startarticle
                    }
                } else {
                    // category has no startarticle
                    conSetStartArticle($idcat, $idart, $lang, 0);
                }
            }
        }

        // if article should be related to categories
        if (is_array($idcatnew)) {
            // enforce code creation for all categories this article should be related to
            foreach ($idcatnew as $idcat) {
                $db->query("
                    SELECT idcatart
                    FROM " . $cfg["tab"]["cat_art"] . "
                    WHERE idcat = '$idcat'
                        AND idart = '$idart'");
                $db->nextRecord();

                conSetCodeFlag($db->f("idcatart"));
            }
        }

        // if meta-tag 'robots' is available add value 'index, follow'
        $availableTags = conGetAvailableMetaTagTypes();
        if (array_key_exists('robots', $availableTags)) {
            conSetMetaValue($idartlang, 'robots', 'index, follow');
        }
    } else {
        // get old article data that will be passed to
        // Contenido.Action.con_saveart.AfterCall chain handler
        $oArtLang = new cApiArticleLanguage(cSecurity::toInteger($idartlang));
        if ($oArtLang->isLoaded()) {

            // get array of idcats this article was related to
            $oCatArtColl = new cApiCategoryArticleCollection();
            $idcatold = $oCatArtColl->getCategoryIdsByArticleId($oArtLang->get('idart'));

            // get category language objects this article was related to
            $oCatLangColl = new cApiCategoryLanguageCollection();
            $oCatLangColl->setWhere('idlang', cRegistry::getLanguageId());
            $oCatLangColl->setWhere('idcat', implode(',', $idcatold), 'IN');
            $oCatLangColl->query();

            // determine if this article was start article of any related category
            $wasStart = false;
            while (false !== $categoryLanguage = $oCatLangColl->next()) {
                $wasStart |= $oArtLang->get('idartlang') == $categoryLanguage->get('startidartlang');
                if ($wasStart) {
                    break;
                }
            }

            // set old article data
            $oldData['idart']        = $oArtLang->get('idart');
            $oldData['idartlang']    = $oArtLang->get('idartlang');
            $oldData['lang']         = $oArtLang->get('idlang');
            $oldData['title']        = $oArtLang->get('title');
            $oldData['urlname']      = $oArtLang->get('urlname');
            $oldData['summary']      = $oArtLang->get('summary');
            $oldData['artspec']      = $oArtLang->get('artspec');
            $oldData['created']      = $oArtLang->get('created');
            $oldData['lastmodified'] = $oArtLang->get('lastmodified');
            $oldData['author']       = $oArtLang->get('author');
            $oldData['online']       = $oArtLang->get('online');
            $oldData['searchable']   = $oArtLang->get('searchable');
            $oldData['sitemapprio']  = $oArtLang->get('sitemapprio');
            $oldData['changefreq']   = $oArtLang->get('changefreq');
            $oldData['published']    = $oArtLang->get('published');
            $oldData['datestart']    = $oArtLang->get('datestart');
            $oldData['dateend']      = $oArtLang->get('dateend');
            $oldData['artsort']      = $oArtLang->get('artsort');
            $oldData['idtpl']        = $idtpl;
            $oldData['idcat']        = $idcat;
            $oldData['idcatnew']     = $idcatold;
            $oldData['is_start']     = $wasStart;
        }

        // update article
        conEditArt(
            $idcat, $idcatnew, $idart, $is_start, $idtpl, $idartlang, $lang, $title, $summary, $artspec,
            $created, $lastmodified, $author, $online, $datestart, $dateend, $publishing_date, $artsort, 0, $searchable
        );

        // build notification
        $tmp_notification = $notification->returnNotification("ok", i18n("Changes saved"));

        // if article should be related to categories
        if (is_array($idcatnew)) {
            // if article should still be related to current category
            if (in_array($idcat, $idcatnew)) {
                // if article should be startarticle
                if ($is_start == 1) {
                    // set as startarticle of current category
                    conSetStartArticle($idcat, $idart, $lang, $is_start);
                }

                // if article should not be startarticle
                if (!isset($is_start)) {
                    // get startidartlang of current category in current language
                    $db->query("
                        SELECT startidartlang
                        FROM " . $cfg["tab"]["cat_lang"] . "
                        WHERE idcat = '$idcat'
                            AND idlang = '$lang'
                            AND startidartlang != '0'");
                    if ($db->nextRecord()) {
                        // category has startarticle
                        if ($idartlang == $db->f('startidartlang')) {
                            // current article is currently startarticle
                            conSetStartArticle($idcat, $idart, $lang, 0);
                        } else {
                            // another article is currently startarticle
                        }
                    } else {
                        // category has no startarticle
                        conSetStartArticle($idcat, $idart, $lang, 0);
                    }
                }
            }

            // enforce code creation for all categories this article should be related to
            foreach ($idcatnew as $idcat) {
                $db->query("
                    SELECT idcatart
                    FROM " . $cfg["tab"]["cat_art"] . "
                    WHERE idcat = '$idcat'
                        AND idart = '$idart'");
                $db->nextRecord();

                conSetCodeFlag($db->f("idcatart"));
            }
        }
    }
}

// CON-1493
if (isset($_POST['redirect_mode']) && ($_POST['redirect_mode'] === 'permanently' || $_POST['redirect_mode'] === 'temporary')) {
    $article = new cApiArticleLanguage($idartlang);
    $article->set('redirect_mode', ($_POST['redirect_mode']));
    $article->store();
}

$newData = [
    'idcat'        => $idcat,
    'idcatnew'     => $idcatnew,
    'idart'        => $idart,
    'is_start'     => $is_start,
    'idtpl'        => $idtpl,
    'idartlang'    => $idartlang,
    'lang'         => $lang,
    'title'        => $title,
    'urlname'      => $urlname,
    'summary'      => $summary,
    'artspec'      => $artspec,
    'created'      => $created,
    'lastmodified' => $lastmodified,
    'author'       => $author,
    'online'       => $online,
    'searchable'   => $searchable,
    'sitemapprio'  => $sitemapprio,
    'changefreq'   => $changefreq,
    'datestart'    => $datestart,
    'dateend'      => $dateend,
    'published'    => $publishing_date,
    'artsort'      => $artsort,
];
cApiCecHook::execute('Contenido.Action.con_saveart.AfterCall', $newData, $oldData);
