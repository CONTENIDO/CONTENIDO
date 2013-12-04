<?php
/**
 * Backend action file con_saveart
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (!isset($idtpl)) {
    $idtpl = false;
}

if (!isset($artspec)) {
    $artspec = '';
}

if (!isset($online)) {
    $online = false;
}

if (!isset($searchable)) {
    $searchable = false;
}

$oldData = array();

if (isset($title) && ($perm->have_perm_area_action($area, "con_edit") || $perm->have_perm_area_action_item($area, "con_edit", $idcat))) {
    if (1 == $tmp_firstedit) {
        $idart = conEditFirstTime($idcat, $idcatnew, $idart, $is_start, $idtpl, $idartlang, $lang, $title, $summary, $artspec, $created, $lastmodified, $author, $online, $datestart, $dateend, $artsort, 0, $searchable);
        $tmp_notification = $notification->returnNotification("info", i18n("Changes saved"));

        if (!isset($idartlang) || $idartlang == 0) {
            $sql = "SELECT idartlang FROM " . $cfg["tab"]["art_lang"] . " WHERE idart = $idart AND idlang = $lang";
            $db->query($sql);
            $db->nextRecord();
            $idartlang = $db->f("idartlang");
        }

        if (in_array($idcat, $idcatnew)) {
            $sql = "SELECT idcatart FROM " . $cfg["tab"]["cat_art"] . " WHERE idcat = '" . $idcat . "' AND idart = '" . $idart . "'";

            $db->query($sql);
            $db->nextRecord();

            $tmp_idcatart = $db->f("idcatart");

            if ($is_start == 1) {
                conMakeStart($tmp_idcatart, $is_start);
            }

            if (!isset($is_start)) {
                $sql = "SELECT * FROM " . $cfg["tab"]["cat_lang"] . " WHERE idcat = '$idcat' AND idlang = '$lang' AND startidartlang != '0' ";
                $db->query($sql);
                if ($db->nextRecord()) {
                    $tmp_startidartlang = $db->f('startidartlang');
                    if ($idartlang == $tmp_startidartlang) {
                        conMakeStart($tmp_idcatart, 0);
                    }
                } else {
                    conMakeStart($tmp_idcatart, 0);
                }
            }
        }

        if (is_array($idcatnew)) {
            foreach ($idcatnew as $idcat) {
                $sql = "SELECT idcatart FROM " . $cfg["tab"]["cat_art"] . " WHERE idcat = $idcat AND idart = $idart";

                $db->query($sql);
                $db->nextRecord();

                conSetCodeFlag($db->f("idcatart"));
            }
        }

        $availableTags = conGetAvailableMetaTagTypes();
        foreach ($availableTags as $key => $value) {
            if ($value["metatype"] == "robots") {
                conSetMetaValue($idartlang, $key, "index, follow");
                break;
            }
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

            // determine if this article was start article of any related
            // category
            $wasStart = false;
            while (false !== $categoryLanguage = $oCatLangColl->next()) {
                $wasStart |= $oArtLang->get('idartlang') == $categoryLanguage->get('startidartlang');
                if ($wasStart) {
                    break;
                }
            }

            // set old article data
            $oldData['idart'] = $oArtLang->get('idart');
            $oldData['idartlang'] = $oArtLang->get('idartlang');
            $oldData['lang'] = $oArtLang->get('idlang');
            $oldData['title'] = $oArtLang->get('title');
            $oldData['urlname'] = $oArtLang->get('urlname');
            $oldData['summary'] = $oArtLang->get('summary');
            $oldData['artspec'] = $oArtLang->get('artspec');
            $oldData['created'] = $oArtLang->get('created');
            $oldData['lastmodified'] = $oArtLang->get('lastmodified');
            $oldData['author'] = $oArtLang->get('author');
            $oldData['online'] = $oArtLang->get('online');
            $oldData['searchable'] = $oArtLang->get('searchable');
            $oldData['sitemapprio'] = $oArtLang->get('sitemapprio');
            $oldData['changefreq'] = $oArtLang->get('changefreq');
            $oldData['published'] = $oArtLang->get('published');
            $oldData['datestart'] = $oArtLang->get('datestart');
            $oldData['dateend'] = $oArtLang->get('dateend');
            $oldData['artsort'] = $oArtLang->get('artsort');
            $oldData['idtpl'] = $idtpl;
            $oldData['idcat'] = $idcat;
            $oldData['idcatnew'] = $idcatold;
            $oldData['is_start'] = $wasStart;
        }

        conEditArt($idcat, $idcatnew, $idart, $is_start, $idtpl, $idartlang, $lang, $title, $summary, $artspec, $created, $lastmodified, $author, $online, $datestart, $dateend, $publishing_date, $artsort, 0, $searchable);

        $tmp_notification = $notification->returnNotification("info", i18n("Changes saved"));

        if (!isset($idartlang)) {
            $sql = "SELECT idartlang FROM " . $cfg["tab"]["art_lang"] . " WHERE idart = $idart AND idlang = $lang";
            $db->query($sql);
            $db->nextRecord();
            $idartlang = $db->f("idartlang");
        }

        if (is_array($idcatnew)) {
            if (in_array($idcat, $idcatnew)) {
                $sql = "SELECT idcatart FROM " . $cfg["tab"]["cat_art"] . " WHERE idcat = '" . $idcat . "' AND idart = '" . $idart . "'";

                $db->query($sql);
                $db->nextRecord();

                $tmp_idcatart = $db->f("idcatart");

                if ($is_start == 1) {
                    conMakeStart($tmp_idcatart, $is_start);
                }

                if (!isset($is_start)) {
                    $sql = "SELECT * FROM " . $cfg["tab"]["cat_lang"] . " WHERE idcat = '$idcat' AND idlang = '$lang' AND startidartlang != '0' ";
                    $db->query($sql);
                    if ($db->nextRecord()) {
                        $tmp_startidartlang = $db->f('startidartlang');
                        if ($idartlang == $tmp_startidartlang) {
                            conMakeStart($tmp_idcatart, 0);
                        }
                    } else {
                        conMakeStart($tmp_idcatart, 0);
                    }
                }
            }
        }

        if (is_array($idcatnew)) {
            foreach ($idcatnew as $idcat) {
                $sql = "SELECT idcatart FROM " . $cfg["tab"]["cat_art"] . " WHERE idcat = $idcat AND idart = $idart";

                $db->query($sql);
                $db->nextRecord();

                conSetCodeFlag($db->f("idcatart"));
            }
        }
    }
}

$newData = array(
    'idcat' => $idcat,
    'idcatnew' => $idcatnew,
    'idart' => $idart,
    'is_start' => $is_start,
    'idtpl' => $idtpl,
    'idartlang' => $idartlang,
    'lang' => $lang,
    'title' => $title,
    'urlname' => $urlname,
    'summary' => $summary,
    'artspec' => $artspec,
    'created' => $created,
    'lastmodified' => $lastmodified,
    'author' => $author,
    'online' => $online,
    'searchable' => $searchable,
    'sitemapprio' => $sitemapprio,
    'changefreq' => $changefreq,
    'datestart' => $datestart,
    'dateend' => $dateend,
    'published' => $publishing_date,
    'artsort' => $artsort
);

if (isset($_POST['redirect_mode']) && ($_POST['redirect_mode'] === 'permanently' || $_POST['redirect_mode'] === 'temporary')) {
    $article = new cApiArticleLanguage($idartlang);
    $article->set('redirect_mode', ($_POST['redirect_mode']));
    $article->store();
}

cApiCecHook::execute("Contenido.Action.con_saveart.AfterCall", $newData, $oldData);
