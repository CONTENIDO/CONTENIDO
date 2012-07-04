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
 * @version    1.0.6
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
 * @author Olaf Niemann <Olaf.Niemann@4fb.de>
 * @copyright four for business AG <http://www.4fb.de>
 *
 * @return int Id of the new article
 */
function conEditFirstTime($idcat, $idcatnew, $idart, $is_start, $idtpl,
                          $idartlang, $idlang, $title, $summary, $artspec, $created,
                          $lastmodified, $author, $online, $datestart, $dateend,
                          $artsort, $keyart=0)
{
    global $db, $client, $lang, $cfg, $auth, $urlname, $page_title, $cfgClient;
    //Some stuff for the redirect
    global $redirect, $redirect_url, $external_redirect;
    global $time_move_cat; // Used to indicate "move to cat"
    global $time_target_cat; // Used to indicate the target category
    global $time_online_move; // Used to indicate if the moved article should be online
    global $timemgmt;

    $page_title = addslashes($page_title);

    $urlname            = (trim($urlname) == '') ? trim($title) : trim($urlname);
    $urlname            = htmlspecialchars(cApiStrCleanURLCharacters($urlname), ENT_QUOTES);
    $usetimemgmt        = ($timemgmt == '1')     ? '1' : '0';
    $movetocat          = ($time_move_cat == '1') ? '1' : '0';
    $onlineaftermove    = ($time_online_move == '1') ? '1' : '0';
    $redirect           = ($redirect == '1')     ? '1' : '0';
    $external_redirect  = ($external_redirect == '1')    ? '1' : '0';
    $redirect_url       = ($redirect_url == 'http://' || $redirect_url == '') ? '0' : $redirect_url;

    if ($is_start == 1) {
        $usetimemgmt = "0";
    }

    // Table 'con_art'
    $db->free();
    $sql = "INSERT INTO ".$cfg["tab"]["art"]." (idclient) VALUES (". (int) $client . ")";
    $db->query($sql);
    // $new_idart = $db->nextid($cfg["tab"]["art"]);
    $new_idart = $db->getLastInsertedId($cfg["tab"]["art"]);

    // Set self defined Keywords
    if ($keyart != "") {
        $keycode[1][1] = $keyart;
    }

    // Table 'con_stat'
    $db->free();
    $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE 'idcat' = ". (int) $idcat . " AND 'idart' = ". (int) $new_idart;
    $db->query($sql);
    $db->next_record();
    $idcatart = $db->f("idcatart");

    $a_languages[] = $lang;
    foreach ($a_languages as $tmp_lang) {
        $oStatColl = new cApiStatCollection();
        $oStatColl->create($idcatart, $tmp_lang, $client, 0);
    }

    // Table 'con_art_lang', one entry for every language
    foreach ($a_languages as $tmp_lang) {
        $lastmodified = ($lang == $tmp_lang) ? $lastmodified : 0;

        //$nextidartlang = $db->nextid($cfg["tab"]["art_lang"]);
        if ($online == 1) {
            $published_value = date("Y-m-d H:i:s");
            $publishedby_value = $auth->auth["uname"];
        } else {
            $published_value = '';
            $publishedby_value = '';
        }

        $aFields = array(
            'idart' => (int) $new_idart,
            'idlang' => (int) $tmp_lang,
            'title' => $title,
            'urlname' => $urlname,
            'pagetitle' => $page_title,
            'summary' => $summary,
            'artspec' => $artspec,
            'created' => $created,
            'lastmodified' => $lastmodified,
            'author' => $auth->auth['uname'],
            'published' => $published_value,
            'publishedby' => $publishedby_value,
            'online' => (int) $online,
            'redirect' => (int) $redirect,
            'redirect_url' => $redirect_url,
            'external_redirect' => (int) $external_redirect,
            'artsort' => (int) $artsort,
            'timemgmt' => (int) $usetimemgmt,
            'datestart' => $datestart,
            'dateend' => $dateend,
            'status' => 0,
            'time_move_cat' => (int) $movetocat,
            'time_target_cat' => (int) $time_target_cat,
            'time_online_move' => (int) $onlineaftermove,
        );

        $sql = $db->buildInsert($cfg["tab"]["art_lang"], $aFields);

        $db->query($sql);

        conMakeStart($idcatart, 0);

        $availableTags = conGetAvailableMetaTagTypes();

        $lastId = $db->getLastInsertedId($cfg["tab"]["art_lang"]);
        foreach ($availableTags as $key => $value) {
            conSetMetaValue($lastId, $key, $_POST['META'.$value["name"]]);
        }
    }

    // Set new idart
    $idart = $new_idart;

    // Table 'cat_art'
    $sql = "SELECT idcat FROM ".$cfg["tab"]["cat_art"]." WHERE idart=". (int) $idart; // get all idcats that contain art
    $db->query($sql);

    $tmp_idcat = array();
    while ($db->next_record()) {
        $tmp_idcat[] = $db->f("idcat");
    }

    if (!is_array($idcatnew)) {
        $idcatnew[0] = 0;
    }
    if (count($tmp_idcat) == 0) {
        $tmp_idcat[0] = 0;
    }

    foreach ($idcatnew as $value) {
        if (!in_array($value, $tmp_idcat)) {
            // INSERT -> Table 'cat_art'
            $sql = "INSERT INTO ".$cfg["tab"]["cat_art"]." (idcat, idart) VALUES (" . (int) $value . ", " . (int) $idart . ")";
            $db->query($sql);

            // Entry in 'stat'-table for all languages
            $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat=" . (int) $value . " AND idart=" . (int) $idart;
            $db->query($sql);

            $db->next_record();
            $tmp_idcatart = $db->f("idcatart");

            $a_languages = getLanguagesByClient($client);
            foreach ($a_languages as $tmp_lang) {
                $oStatColl = new cApiStatCollection();
                $oStatColl->create($tmp_idcatart, $tmp_lang, $client, 0);
            }
        }
    }

    foreach ($tmp_idcat as $value) {
        if (!in_array($value, $idcatnew)) {
            $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat=" . (int) $value . " AND idart=" . (int) $idart; // get all idcatarts that will no longer exist
            $db->query($sql);
            $db->next_record();

            //******** delete frome code cache ***************        // and delete corresponding code
            $mask = $cfgClient[$client]['code_path']."*.".$db->f("idcatart").".php";
            array_map("unlink", glob($mask));

            //******* delete from 'stat'-table ****************
            $sql = "SELECT * FROM ".$cfg["tab"]["cat_art"]." WHERE idcat=".(int) $value . " AND idart=". (int) $idart;
            $db->query($sql);

            while ($db->next_record()) {
                $a_idcatart[] = $db->f("idcatart");
            }

            if (is_array($a_idcatart)) {
                foreach ($a_idcatart AS $value2) {
                    //****** delete from 'stat'-table ************
                    $sql = "DELETE FROM ".$cfg["tab"]["stat"]." WHERE idcatart=". (int) $value2;
                    $db->query($sql);
                }
            }

            //******** delete from 'cat_art'-table ***************
            $sql = "DELETE FROM ".$cfg["tab"]["cat_art"]." WHERE idart=". (int) $idart . " AND idcat=". (int) $value;
            $db->query($sql);

            // Remove startidartlang
            if (isStartArticle($idartlang, $idcat, $lang)) {
                $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET startidartlang='0' WHERE idcat=". (int) $value . " AND idlang=" . (int) $lang;
                $db->query($sql);
            }

            //******** delete from 'tpl_conf'-table ***************
            $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["art_lang"]." WHERE idart = ". (int) $idart . " AND idlang = ". (int) $lang;
            $db->query($sql);
            $db->next_record();
            $tmp_idtplcfg = $db->f('idtplcfg');

            $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = ". (int) $tmp_idtplcfg;
            $db->query($sql);
        }
    }

    //********* update into 'art_lang'-table for all languages ******
    if (!$title) {
        $title = "--- " . i18n("Default title"). " ---";
    }

    $a_languages = getLanguagesByClient($client);
    foreach ($a_languages as $tmp_lang) {
        $tmp_online       = ($lang == $tmp_lang) ? $online : 0;
        $tmp_lastmodified = ($lang == $tmp_lang) ? $lastmodified : 0;

        $aFields = array(
            'title' => $title,
            'urlname' => $urlname,
            'pagetitle' => $page_title,
            'summary' => $summary,
            'artspec' => $artspec,
            'created' => $created,
            'lastmodified' => $tmp_lastmodified,
            'modifiedby' => $author,
            'online' => (int) $tmp_online,
            'redirect' => (int) $redirect,
            'redirect_url' => $redirect_url,
            'external_redirect' => (int) $external_redirect,
            'artsort' => (int) $artsort,
            'datestart' => $datestart,
            'dateend' => $dateend,
        );
        $aWhere = array('idart' => (int) $new_idart, idlang => $tmp_lang);

        $sql = $db->buildUpdate($cfg["tab"]["art_lang"], $aFields, $aWhere);

        $db->query($sql);
    }

    return $new_idart;
}


/**
 * Edit an existing article
 *
 * @param mixed many
 * @return void
 *
 * @author Olaf Niemann <olaf.niemann@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function conEditArt($idcat, $idcatnew, $idart, $is_start, $idtpl, $idartlang,
                    $idlang, $title, $summary, $artspec, $created, $lastmodified, $author,
                    $online, $datestart, $dateend, $artsort, $keyart = 0)
{
    $args = func_get_args();

    global $db, $client, $lang, $cfg, $redirect, $redirect_url, $external_redirect, $perm, $cfgClient;
    global $urlname, $page_title;
    global $time_move_cat, $time_target_cat;
    global $time_online_move; // Used to indicate if the moved article should be online
    global $timemgmt;

    // Add slashes because single quotes will crash the db
    $page_title = addslashes($page_title);

    $urlname     = (trim($urlname) == '') ? trim($title) : trim($urlname);
    $urlname     = htmlspecialchars(cApiStrCleanURLCharacters($urlname), ENT_QUOTES);
    $usetimemgmt = ($timemgmt == '1') ? '1': '0';
    if ($timemgmt == '1' && (($datestart == "" && $dateend == "") || ($datestart == "0000-00-00 00:00:00" && $dateend == "0000-00-00 00:00:00"))) {
        $usetimemgmt = 0;
    }
    $onlineaftermove = ($time_online_move == '1') ? '1' : '0';
    $movetocat = ($time_move_cat == '1') ? '1' : '0';
    $redirect     = ('1' == $redirect ) ? 1 : 0;
    $redirect_url = ($redirect_url == 'http://' || $redirect_url == '') ? '0' : $redirect_url;
    $external_redirect = ($external_redirect == '1') ? 1 : 0;

    if ($is_start == 1) {
        $usetimemgmt = "0";
    }

    $sql = "SELECT idcat FROM ".$cfg["tab"]["cat_art"]." WHERE idart=" . (int) $idart; // get all idcats that contain art
    $db->query($sql);
    while ($db->next_record()) {
        $tmp_idcat[] = $db->f("idcat");
    }

    if (!is_array($idcatnew)) {
        $idcatnew[0] = 0;
    }

    if (!is_array($tmp_idcat)) {
        $tmp_idcat[0] = 0;
    }

    // if (is_array($idcatnew)) {
    foreach ($idcatnew as $value) {
        if (!in_array($value, $tmp_idcat) ) {
            // INSERT insert 'cat_art' table
            $sql = "INSERT INTO ".$cfg["tab"]["cat_art"]." (idcat, idart) VALUES (" . (int) $value . ", ". (int) $idart . ")";
            $db->query($sql);

            // entry in 'stat'-table for all languages
            $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat=" . (int) $value . " AND idart=" . (int) $idart;
            $db->query($sql);
            $db->next_record();

            $tmp_idcatart = $db->f("idcatart");

            $a_languages = getLanguagesByClient($client);
            foreach ($a_languages as $tmp_lang) {
                $oStatColl = new cApiStatCollection();
                $oStatColl->create($tmp_idcatart, $tmp_lang, $client, 0);
            }
        }
    }

    foreach ($tmp_idcat as $value) {
        if (!in_array($value, $idcatnew)) {
            $sql = "SELECT * FROM ".$cfg["tab"]["cat_art"]." WHERE idcat=" . (int) $value . " AND idart=" . (int) $idart;  // get all idcatarts that will no longer exist
            $db->query($sql);
            $db->next_record();

            //******** delete from code cache ***************        // and delete corresponding code
            $mask = $cfgClient[$client]['code_path']."*.".$db->f("idcatart").".php";
            array_map("unlink", glob($mask));

            //******* delete from 'stat'-table ****************
            $sql = "SELECT * FROM ".$cfg["tab"]["cat_art"]." WHERE idcat=" . (int) $value . " AND idart=" . (int) $idart;
            $db->query($sql);

            while ($db->next_record()) {
                $a_idcatart[] = $db->f("idcatart");
            }

            if (is_array($a_idcatart)) {
                foreach ($a_idcatart as $value2) {
                    //****** delete from 'stat'-table ************
                    $sql = "DELETE FROM ".$cfg["tab"]["stat"]." WHERE idcatart=" . (int) $value2;
                    $db->query($sql);
                }
            }

            //******** delete from 'cat_art'-table ***************
            $sql = "DELETE FROM ".$cfg["tab"]["cat_art"]." WHERE idart=" . (int) $idart . " AND idcat= " . (int) $value;
            $db->query($sql);

            // Update startidartlang
            if (isStartArticle($idartlang, $idcat, $lang)) {
                $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET startidartlang='0' WHERE idcat=" . (int) $value . " AND idlang=" . (int) $lang;
                $db->query($sql);
            }
        }
    }

    // If the user has no right for makeonline, don't update it.
    if (!$perm->have_perm_area_action("con", "con_makeonline") &&
        !$perm->have_perm_area_action_item("con", "con_makeonline", $idcat))
    {
        $sqlonline = "";
    } else {
        $sqlonline = "online = " . (int) $online . ",";
        if ($online == '1') {
            // Check if online id is currently 0
            $sql = "SELECT online FROM ".$cfg["tab"]["art_lang"]." WHERE idartlang=". (int) $idartlang;
            $db->query($sql);
            if ($db->next_record()) {
                if ($db->f("online") == 0) {
                    // Only update if value changed from 0 to 1
                    $sqlonline .= "published = '".date("Y-m-d H:i:s")."', publishedby='" . $db->escape($author) . "',";
                }
            }
        }
    }

    if ($title == "") {
        $title = "--- ".i18n("Default title")." ---";
    }

    //******** update 'art_lang'-table **********
    // pagetitle = '".cSecurity::escapeDB($page_title, $db)."',
    $sql = "UPDATE
                ".$cfg["tab"]["art_lang"]."
            SET
                title = '".$db->escape($title)."',
                urlname = '".$db->escape($urlname)."',
                summary = '".$db->escape($summary)."',
                artspec = '".$db->escape($artspec)."',
                created = '".$db->escape($created)."',
                lastmodified = '".$db->escape($lastmodified)."',
                modifiedby = '".$db->escape($author)."',
                $sqlonline
                timemgmt = ". (int) $usetimemgmt.",
                redirect = ". (int) $redirect.",
                external_redirect = " . (int) $external_redirect.",
                redirect_url = '".$db->escape($redirect_url)."',
                artsort = ". (int) $artsort;

    if ($perm->have_perm_area_action("con", "con_makeonline") ||
        $perm->have_perm_area_action_item("con","con_makeonline", $idcat))
    {
        $sql .= ", datestart = '".$db->escape($datestart)."',
        dateend = '".$db->escape($dateend)."',
        time_move_cat = " . (int) $movetocat.",
        time_target_cat = " . (int) $time_target_cat.",
        time_online_move = " . (int) $onlineaftermove;
    }

    $sql .= "WHERE idartlang=". (int) $idartlang;
    $db->query($sql);

    /*$availableTags = conGetAvailableMetaTagTypes();

    foreach ($availableTags as $key => $value) {
        conSetMetaValue($idartlang, $key, $_POST['META'.$value["name"]]);
    }
    */
}

/**
 * Save a content element and generate index
 *
 * @param integer $idartlang idartlang of the article
 * @param string $type Type of content element
 * @param integer $typeid Serial number of the content element
 * @param string $value Content
 *
 * @return void
 *
 * @author Olaf Niemann <olaf.niemann@4fb.de>
 *         Jan Lengowski <jan.lengowski@4fb.de>
 *
 * @copyright four for business AG <www.4fb.de>
 */
function conSaveContentEntry($idartlang, $type, $typeid, $value, $bForce = false)
{
    global $auth, $cfg, $cfgClient, $client, $lang, $_cecRegistry;

    if ($bForce == true) {
        $db = cRegistry::getDb();
    } else {
        global $db;
    }

    $date   = date("Y-m-d H:i:s");
    $author = $auth->auth["uname"];

    $cut_path  = $cfgClient[$client]["path"]["htmlpath"];

    $value = str_replace($cut_path, "", $value);
    $value = stripslashes($value);

    $iterator = $_cecRegistry->getIterator("Contenido.Content.SaveContentEntry");

    while ($chainEntry = $iterator->next()) {
        $value = $chainEntry->execute($idartlang, $type, $typeid, $value);
    }
    $value = urlencode($value);

    $sql = "SELECT * FROM ".$cfg["tab"]["type"]." WHERE type = '".$db->escape($type)."'";
    $db->query($sql);
    $db->next_record();
    $idtype=$db->f("idtype");

    $sql = "SELECT * FROM ".$cfg["tab"]["content"]." WHERE idartlang='".cSecurity::toInteger($idartlang)."' AND idtype='".cSecurity::toInteger($idtype)."' AND typeid='".cSecurity::toInteger($typeid)."'";
    $db->query($sql);

    if ($db->next_record()) {
        $sql = "UPDATE ".$cfg["tab"]["content"]." SET value='".cSecurity::escapeDB($value, $db)."', author='".cSecurity::escapeDB($author, $db)."', lastmodified='".cSecurity::escapeDB($date, $db)."'
                WHERE idartlang='".cSecurity::toInteger($idartlang)."' AND idtype='".cSecurity::toInteger($idtype)."' AND typeid='".cSecurity::toInteger($typeid)."'";
        $db->query($sql);
    } else {
        $sql = "INSERT INTO ".$cfg["tab"]["content"]." (idartlang, idtype, typeid, value, author, created, lastmodified) VALUES(
                '".cSecurity::toInteger($idartlang)."', '".cSecurity::toInteger($idtype)."', '".cSecurity::toInteger($typeid)."', '".cSecurity::escapeDB($value, $db)."',
                '".cSecurity::escapeDB($author, $db)."', '".cSecurity::escapeDB($date, $db)."', '".cSecurity::escapeDB($date, $db)."')";
        $db->query($sql);
    }

    // Touch the article to update last modified date
    $lastmodified = date("Y-m-d H:i:s");

    $sql = "UPDATE
                ".$cfg["tab"]["art_lang"]."
            SET
                lastmodified = '".cSecurity::escapeDB($lastmodified, $db)."',
                modifiedby = '".cSecurity::escapeDB($author, $db)."'
            WHERE
                idartlang='".cSecurity::toInteger($idartlang)."'";
    $db->query($sql);
}

/**
 * generate index of article content
 *
 * added by stese
 * removed from function conSaveContentEntry  before
 * Touch the article to update last modified date
 *
 * @see conSaveContentEntry
 * @param integer $idart
 */
function conMakeArticleIndex($idartlang, $idart)
{
    global $db, $auth, $cfg;

    // generate index of article content
    $oIndex = new SearchIndex($db);
    $aOptions = array("img", "link", "linktarget", "swf"); // cms types to be excluded from indexing
    // indexing an article depends on the complete content with all content types, i.e it can not by differentiated by specific content types.
    // Therefore one must fetch the complete content arrray.

    $aContent = conGetContentFromArticle($idartlang);
    $oIndex->start($idart, $aContent, 'auto', $aOptions);
}

/**
 * Toggle the online status of an article
 *
 * @param int $idart Article Id
 * @param ing $lang Language Id
 *
 * @author Olaf Niemann <olaf.niemann@4fb-de>
 *         Jan Lengowski <jan.lengowski@4fb.de>
 *
 * @copyright four for business AG <www.4fb.de>
 */
function conMakeOnline($idart, $lang)
{
    global $db, $cfg, $auth;

    $sql = "SELECT online FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".cSecurity::toInteger($idart)."' AND idlang = '".cSecurity::toInteger($lang)."'";
    $db->query($sql);

    $db->next_record();

    $set = ($db->f("online") == 0) ? 1 : 0;

    if ($set == 1) {
        $publisher_info = "published = '".date("Y-m-d H:i:s")."', publishedby='".$auth->auth["uname"]."',";
    } else {
        $publisher_info = '';
    }
    $sql = "UPDATE ".$cfg["tab"]["art_lang"]."  SET ".$publisher_info." online = '".cSecurity::toInteger($set)."' WHERE idart = '".cSecurity::toInteger($idart)."'
            AND idlang = '".cSecurity::toInteger($lang)."'";
    $db->query($sql);
}



/**
 *
 * Set the status from articles to online or offline.
 *
 * @param array $idarts all articles
 * @param int $idlang
 * @param boolean $online
 */

function conMakeOnlineBulkEditing($idarts, $idlang, $online) {
    global $db, $cfg, $auth;
    $where = '1=2';
    if ($online == 1) {
         $publisher_info = "published = '".date("Y-m-d H:i:s")."', publishedby='".$auth->auth["uname"]."',";
    } else  {
        $online = 0;
        $publisher_info = '';
    }

    foreach ($idarts as $idart) {
        $where .= " OR idart='".cSecurity::toInteger($idart)."'";
    }

    $sql = "UPDATE ".$cfg["tab"]["art_lang"]."  SET ".$publisher_info." online = '".$online."' WHERE ($where)
        AND idlang = '".cSecurity::toInteger($idlang)."'";
    $db->query($sql);
}


/**
 * Toggle the lock status
 * of an article
 *
 * @param int $idart Article Id
 * @param ing $lang Language Id
 *
 */
function conLock($idart, $lang)
{
    global $db, $cfg;

    $sql = "SELECT locked FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".cSecurity::toInteger($idart)."' AND idlang = '".cSecurity::toInteger($lang)."'";
    $db->query($sql);

    $db->next_record();

    $set = ($db->f("locked") == 0) ? 1 : 0;

    $sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET locked = '".cSecurity::toInteger($set)."' WHERE idart = '".cSecurity::toInteger($idart)."' AND idlang = '".cSecurity::toInteger($lang)."'";
    $db->query($sql);
}


/**
 * Freeze/Lock more articles.
 * @param array $idarts all articles
 * @param int $idlang
 * @param boolean $lock
 */
function conLockBulkEditing($idarts, $idlang , $lock) {
    global $db, $cfg;
    $where = '1=2';
    if ($lock != 1) {
        $lock = 0;
    }

    foreach ($idarts as $idart) {
        $where .= " OR idart='".cSecurity::toInteger($idart)."'";
    }

    $sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET locked = '".cSecurity::toInteger($lock)."' WHERE ($where) AND idlang = '".cSecurity::toInteger($idlang)."'";
    $db->query($sql);
}

/**
 * Checks if a article is locked or not
 *
 * @param   int  $idart  Article Id
 * @param   int  $lang   Language Id
 * @return  bool
 */
function conIsLocked($idart, $lang)
{
    global $db, $cfg;

    $sql = 'SELECT locked FROM ' . $cfg['tab']['art_lang'] . ' WHERE idart=' . (int) $idart . ' AND idlang=' . (int) $lang;
    $db->query($sql);
    $db->next_record();
    return (1 == $db->f('locked'));
}

/**
 * Toggle the online status of a category
 *
 * @param int $idcat id of the category
 * @param int $lang id of the language
 * @param int $status status of the category
 *
 * @author Jan Lengowski <jan.lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function conMakeCatOnline($idcat, $lang, $status)
{
    global $cfg, $db;

     $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET visible = '".cSecurity::toInteger($status)."',
                lastmodified = '".cSecurity::escapeDB(date("Y-m-d H:i:s"), $db)."'
                WHERE idcat = '".cSecurity::toInteger($idcat)."' AND idlang = '".cSecurity::toInteger($lang)."'";
     $db->query($sql);

    if ($cfg['pathresolve_heapcache'] == true && !$status = 0) {
        $oPathresolveCacheColl = new cApiPathresolveCacheCollection();
        $oPathresolveCacheColl->deleteByCategoryAndLanguage($idcat, $lang);
    }
}

/**
 * Toggle the public status of a category
 *
 * Almost the same function as strMakePublic in
 * functions.str.php (conDeeperCategoriesArray instead of
 * strDeeperCategoriesArray)
 *
 * @param int $idcat Article Id
 * @param int $idcat Language Id
 * @param bool $is_start Start status of the Article
 *
 * @author Olaf Niemann <olaf.niemann@4fb-de>
 *         Jan Lengowski <jan.lengowski@4fb.de>
 *
 * @copyright four for business AG <www.4fb.de>
 */
function conMakePublic($idcat, $lang, $public)
{
    global $db, $cfg;
    $public = (int) $public;
    if ($public != 1) {
        $public = 0;
    }

    $a_catstring = conDeeperCategoriesArray($idcat);
    foreach ($a_catstring as $value) {
        $sql = "UPDATE ".$cfg["tab"]["cat_lang"].
               " SET public='".cSecurity::toInteger($public)."', lastmodified = '".cSecurity::escapeDB(date("Y-m-d H:i:s"), $db).
               "' WHERE idcat='".cSecurity::toInteger($value)."' AND idlang='".cSecurity::toInteger($lang)."' ";
        $db->query($sql);
    }
}

/**
 * Delete an Article
 *
 * @param int $idart Article Id
 *
 * @author Olaf Niemann <olaf.niemann@4fb-de>
 *         Jan Lengowski <jan.lengowski@4fb.de>
 *
 * @copyright four for business AG <www.4fb.de>
 */
function conDeleteart($idart)
{
    global $db, $cfg, $lang, $_cecRegistry, $cfgClient, $client;

    // Delete current language
    $sql = "SELECT idartlang, idtplcfg FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".cSecurity::toInteger($idart)."' AND idlang='".cSecurity::toInteger($lang)."'";
    $db->query($sql);
    $db->next_record();

    $idartlang = $db->f("idartlang");
    $idtplcfg = $db->f("idtplcfg");

    // Fetch idcat
    $sql = "SELECT idcat FROM ".$cfg["tab"]["cat_art"]." WHERE idart = '".cSecurity::toInteger($idart)."'";
    $db->query($sql);
    $db->next_record();

    $idcat = $db->f("idcat");

    // Remove startidartlang
    if (isStartArticle($idartlang, $idcat, $lang)) {
        $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET startidartlang='0' WHERE idcat='".cSecurity::toInteger($idcat)."' AND idlang='".cSecurity::toInteger($lang)."'";
        $db->query($sql);
    }

    $sql = "DELETE FROM ".$cfg["tab"]["content"]." WHERE idartlang = '".cSecurity::toInteger($idartlang)."'";
    $db->query($sql);

    $sql = "DELETE FROM ".$cfg["tab"]["art_lang"]." WHERE idartlang = '".cSecurity::toInteger($idartlang)."'";
    $db->query($sql);

    if ($idtplcfg != "0") {
        $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".cSecurity::toInteger($idtplcfg)."'";
        $db->query($sql);

        $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '".cSecurity::toInteger($idtplcfg)."'";
        $db->query($sql);
    }

    // Check if there are remaining languages
    $sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".cSecurity::toInteger($idart)."'";
    $db->query($sql);

    if ($db->num_rows() > 0) {
        return;
    }

    $sql = "SELECT * FROM ".$cfg["tab"]["cat_art"]." WHERE idart = '".cSecurity::toInteger($idart)."'";
    $db->query($sql);
    $idcatart = array();
    while ( $db->next_record() ) {
        $idcatart[] = $db->f("idcatart");
    }

    ##################################################
    # set keywords
    $keycode[1][1] = "";

    if (count($idcatart) > 0) {
        foreach ($idcatart as $value) {
            //********* delete from code cache **********
            $mask = $cfgClient[$client]['code_path']."*.".$value.".php";
            array_map("unlink", glob($mask));

            //****** delete from 'stat'-table ************
            $sql = "DELETE FROM ".$cfg["tab"]["stat"]." WHERE idcatart = '".cSecurity::toInteger($value)."'";
            $db->query($sql);
        }
    }

    $sql = "SELECT * FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".cSecurity::toInteger($idart)."'";
    $db->query($sql);
    while ($db->next_record()) {
        $idartlang[] = $db->f("idartlang");
    }

    if (is_array($idartlang)) {
        foreach ($idartlang as $value) {
            $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET startidartlang='0' WHERE startidartlang ='".cSecurity::toInteger($value)."'";
            $db->query($sql);

            //********* delete from content table **********
            $sql = "DELETE FROM ".$cfg["tab"]["content"]." WHERE idartlang = '".cSecurity::toInteger($value)."'";
            $db->query($sql);
        }
    }

    $sql = "DELETE FROM ".$cfg["tab"]["cat_art"]." WHERE idart = '".cSecurity::toInteger($idart)."'";
    $db->query($sql);

    $sql = "DELETE FROM ".$cfg["tab"]["art"]." WHERE idart = '".cSecurity::toInteger($idart)."'";
    $db->query($sql);

    $sql = "DELETE FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".cSecurity::toInteger($idart)."'";
    $db->query($sql);

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
 *
 * @author Olaf Niemann <olaf.niemann@4fb-de>
 *         Jan Lengowski <jan.lengowski@4fb.de>
 *
 * @copyright four for business AG <www.4fb.de>
 */
function extractNumber(&$string)
{
    $string = preg_replace("/[^0-9]/","",$string);
}

/**
 * Change the template of a category
 *
 * @param int $idcat Category Id
 * @param int $idtpl Template Id
 *
 * @return void
 * @author Jan Lengowski <jan.lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function conChangeTemplateForCat($idcat, $idtpl)
{
    global $db, $db2, $cfg, $lang;

    // DELETE old entries
    $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = '".cSecurity::toInteger($idcat)."' AND idlang = '".cSecurity::toInteger($lang)."'";
    $db->query($sql);
    $db->next_record();
    $old_idtplcfg = $db->f("idtplcfg");

    $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '".cSecurity::toInteger($old_idtplcfg)."'";
    $db->query($sql);

    $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".cSecurity::toInteger($old_idtplcfg)."'";
    $db->query($sql);

    // parameter $idtpl is 0, reset the template
    if (0 == $idtpl) {
        // get $idtplcfg
        $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = '".cSecurity::toInteger($idcat)."' AND idlang = '".cSecurity::toInteger($lang)."'";

        $db->query($sql);
        $db->next_record();

        $idtplcfg = $db->f("idtplcfg");

        // DELETE 'template_conf' entry
        $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '".cSecurity::toInteger($idtplcfg)."'";
        $db->query($sql);

        // DELETE 'container_conf entries'
        $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".cSecurity::toInteger($idtplcfg)."'";
        $db->query($sql);

        // UPDATE 'cat_lang' table
        $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET idtplcfg = '0' WHERE idcat = '".cSecurity::toInteger($idcat)."' AND idlang = '".cSecurity::toInteger($lang)."'";
        $db->query($sql);

    } else {

        if (!is_object($db2)) {
            $db2 = cRegistry::getDb();
        }

        // check if a pre-configuration is assigned
        $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["tpl"]." WHERE idtpl = '".cSecurity::toInteger($idtpl)."'";

        $db->query($sql);
        $db->next_record();

        if (0 != $db->f("idtplcfg")) {
            // template is pre-configured, create new configuration and
            // copy data from pre-cfg

            // create new configuration
            $sql = "INSERT INTO ".$cfg["tab"]["tpl_conf"]." (idtpl) VALUES ('".cSecurity::toInteger($idtpl)."')";
            $db->query($sql);

            // get new id
            $new_idtplcfg = $db2->getLastInsertedId($cfg["tab"]["tpl_conf"]);

            // extract pre-configuration data
            $sql = "SELECT * FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".cSecurity::toInteger($db->f("idtplcfg"))."'";
            $db->query($sql);

            while ($db->next_record()) {
                // get data
                //$nextid     = $db2->nextid($cfg["tab"]["container_conf"]);

                $number     = $db->f("number");
                $container  = $db->f("container");

                // write new entry
                $sql = "INSERT INTO
                            ".$cfg["tab"]["container_conf"]."
                            (idtplcfg, number, container)
                        VALUES
                            ('".cSecurity::toInteger($new_idtplcfg)."', '".cSecurity::toInteger($number)."', '".cSecurity::escapeDB($container, $db2)."')";

                $db2->query($sql);
            }

            // extract old idtplcfg
            $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = '".cSecurity::toInteger($idcat)."' AND idlang = '".cSecurity::toInteger($lang)."'";
            $db->query($sql);
            $db->next_record();
            $tmp_idtplcfg = $db->f("idtplcfg");

            if ($tmp_idtplcfg != 0) {
                // DELETE 'template_conf' entry
                $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '".cSecurity::toInteger($tmp_idtplcfg)."'";
                $db->query($sql);

                // DELETE 'container_conf entries'
                $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".cSecurity::toInteger($tmp_idtplcfg)."'";
                $db->query($sql);
            }

            // update 'cat_lang' table
            $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET idtplcfg = '".cSecurity::toInteger($new_idtplcfg)."' WHERE idcat = '".cSecurity::toInteger($idcat)."' AND idlang = '".cSecurity::toInteger($lang)."'";
            $db->query($sql);

        } else {
            // template is not pre-configured, create a new configuration.

            $sql = "INSERT INTO ".$cfg["tab"]["tpl_conf"]."
                    ( idtpl) VALUES
                    ('".cSecurity::toInteger($idtpl)."')";
            $db->query($sql);

            $new_idtplcfg = $db->getLastInsertedId(($cfg["tab"]["tpl_conf"]));

            // update 'cat_lang' table
            $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET idtplcfg = '".cSecurity::toInteger($new_idtplcfg)."' WHERE idcat = '".cSecurity::toInteger($idcat)."' AND idlang = '".cSecurity::toInteger($lang)."'";
            $db->query($sql);
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
function conFetchCategoryTree($client = false, $lang = false)
{
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
                ".$cfg["tab"]["cat_tree"]." AS A,
                ".$cfg["tab"]["cat"]." AS B,
                ".$cfg["tab"]["cat_lang"]." AS C
            WHERE
                A.idcat  = B.idcat AND
                B.idcat = C.idcat AND
                C.idlang = '".cSecurity::toInteger($lang)."' AND
                idclient = '".cSecurity::toInteger($client)."'
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
 *
 * Fetch all deeper categories by a given id
 *
 * @param int $idcat Id of category
 * @return array Array with all deeper categories
 *
 * @author Olaf Niemann <olaf.niemann@4fb-de>
 *         Jan Lengowski <jan.lengowski@4fb.de>
 *
 * @copyright four for business AG <www.4fb.de>
 */
function conDeeperCategoriesArray($idcat_start)
{
    global $db, $client, $cfg;

    $sql = "SELECT
                *
            FROM
                ".$cfg["tab"]["cat_tree"]." AS A,
                ".$cfg["tab"]["cat"]." AS B
            WHERE
                A.idcat  = B.idcat AND
                idclient = '".cSecurity::toInteger($client)."'
            ORDER BY
                idtree";

    $db->query($sql);

    $found    = false;
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
 *
 * @return string location string
 *
 * @author Jan Lengowski <jan.lengowski@4fb.de>
 * @author Marco Jahn <marco.jahn@4fb.de>
 *
 * @copyright four for business AG <www.4fb.de>
 */
function conCreateLocationString($idcat, $seperator, &$cat_str, $makeLink = false, $linkClass = "", $firstTreeElementToUse = 0, $uselang = 0, $final = true, $usecache = false)
{
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
            if (cFileHandler::exists($cfgClient[$client]['cache_path']."locationstring-cache-$uselang.txt")) {
                $_locationStringCache = unserialize(cFileHandler::read($cfgClient[$client]['cache_path']."locationstring-cache-$uselang.txt"));
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
                ".$cfg["tab"]["cat_lang"]." AS a,
                ".$cfg["tab"]["cat"]." AS b,
                ".$cfg["tab"]["cat_tree"]." AS c
            WHERE
                a.idlang    = '".cSecurity::toInteger($uselang)."' AND
                b.idclient  = '".cSecurity::toInteger($client)."' AND
                b.idcat     = '".cSecurity::toInteger($idcat)."' AND
                a.idcat     = b.idcat AND
                c.idcat = b.idcat";

    $db->query($sql);
    $db->next_record();

    if ($db->f("level") >= $firstTreeElementToUse) {
        $name     = $db->f("name");
        $parentid = $db->f("parentid");

        //create link
        if ($makeLink == true) {
            $linkUrl = $sess->url("front_content.php?idcat=$idcat");
            $name = '<a href="'.$linkUrl.'" class="'.$linkClass.'">'.$name.'</a>';
        }

        $tmp_cat_str = $name . $seperator . $cat_str;
        $cat_str = $tmp_cat_str;
    }

    if ($parentid != 0) {
        conCreateLocationString($parentid, $seperator, $cat_str, $makeLink, $linkClass, $firstTreeElementToUse ,$uselang, false);
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
            cFileHandler::write($cfgClient[$client]['cache_path']."locationstring-cache-$uselang.txt", serialize($_locationStringCache));
        }
    }
}

/**
 * Set a start-article
 *
 * @param int $idcatart Idcatart of the article
 *
 * @return void
 *
 * @author Olaf Niemann <olaf.niemann@4fb-de>
 *         Jan Lengowski <jan.lengowski@4fb.de>
 *
 * @copyright four for business AG <www.4fb.de>
 */
function conMakeStart($idcatart, $is_start)
{
    global $db, $cfg, $lang;

    $sql = "SELECT idcat, idart FROM ".$cfg["tab"]["cat_art"]." WHERE idcatart='".cSecurity::toInteger($idcatart)."'";
    $db->query($sql);
    $db->next_record();

    $idart = $db->f("idart");
    $idcat = $db->f("idcat");

    $sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE idart='".cSecurity::toInteger($idart)."' AND idlang='".cSecurity::toInteger($lang)."'";
    $db->query($sql);
    $db->next_record();

    $idartlang = $db->f("idartlang");

    if ($is_start == 1) {
        $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET startidartlang='".cSecurity::toInteger($idartlang)."' WHERE idcat='".cSecurity::toInteger($idcat)."' AND idlang='".cSecurity::toInteger($lang)."'";
        $db->query($sql);
    } else {
        $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET startidartlang='0' WHERE idcat='".cSecurity::toInteger($idcat)."' AND idlang='".cSecurity::toInteger($lang)."' AND startidartlang='".cSecurity::toInteger($idartlang)."'";
        $db->query($sql);
    }

    if ($is_start == 1) {
        // Deactivate time management if article is a start article
        $sql = "SELECT idart FROM ".$cfg["tab"]["cat_art"]." WHERE idcatart = '".cSecurity::toInteger($idcatart)."'";

        $db->query($sql);
        $db->next_record();

        $idart = $db->f("idart");

        $sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET timemgmt = 0 WHERE idart = '".cSecurity::toInteger($idart)."' AND idlang = '".cSecurity::toInteger($lang)."'";
        $db->query($sql);
    }
}

/**
 * Create code for one article in all categorys
 *
 * @param int $idart Article ID
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG 2003
 */
function conGenerateCodeForArtInAllCategories($idart)
{
    global $lang, $client, $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idart = '".cSecurity::toInteger($idart)."'";
    $db->query($sql);
    while ($db->next_record()) {
        conSetCodeFlag($db->f("idcatart"));
    }
}

/**
 * Generate code for all articles in a category
 *
 * @param int $idcat Category ID
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG 2003
 */
function conGenerateCodeForAllArtsInCategory($idcat)
{
    global $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat='".cSecurity::toInteger($idcat)."'";
    $db->query($sql);
    while ($db->next_record()) {
        conSetCodeFlag($db->f("idcatart"));
    }
}

/**
 * Generate code for the active client
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG 2003
 */
function conGenerateCodeForClient()
{
    global $client, $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT A.idcatart
            FROM ".$cfg["tab"]["cat_art"]." as A, ".$cfg["tab"]["cat"]." as B
            WHERE B.idclient=''".cSecurity::toInteger($client)."' AND B.idcat=A.idcat";
    $db->query($sql);
    while ($db->next_record()) {
        conSetCodeFlag($db->f("idcatart"));
    }
}

/**
 * Create code for all arts using the same layout
 *
 * @param int $idlay Layout-ID
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG 2003
 */
function conGenerateCodeForAllartsUsingLayout($idlay)
{
    global $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT idtpl FROM ".$cfg["tab"]["tpl"]." WHERE idlay='".cSecurity::toInteger($idlay)."'";
    $db->query($sql);
    while ($db->next_record()) {
        conGenerateCodeForAllartsUsingTemplate($db->f("idtpl"));
    }
}

/**
 * Create code for all articles using the same module
 *
 * @param int $idmod Module id
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG 2003
 */
function conGenerateCodeForAllartsUsingMod($idmod)
{
    global $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT idtpl FROM ".$cfg["tab"]["container"]." WHERE idmod = '".cSecurity::toInteger($idmod)."'";
    $db->query($sql);
    while ($db->next_record()) {
        conGenerateCodeForAllArtsUsingTemplate($db->f("idtpl"));
    }
}

/**
 * Generate code for all articles using one template
 *
 * @param int $idtpl Template-Id
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG 2003
 */
function conGenerateCodeForAllArtsUsingTemplate($idtpl)
{
    global $cfg, $lang, $client;

    $db = cRegistry::getDb();
    $db2 = cRegistry::getDb();

    // Search all categories
    $sql = "SELECT
                b.idcat
            FROM
                ".$cfg["tab"]["tpl_conf"]." AS a,
                ".$cfg["tab"]["cat_lang"]." AS b,
                ".$cfg["tab"]["cat"]." AS c
            WHERE
                a.idtpl     = '".cSecurity::toInteger($idtpl)."' AND
                b.idtplcfg  = a.idtplcfg AND
                c.idclient  = '".cSecurity::toInteger($client)."' AND
                b.idcat     = c.idcat";

    $db->query($sql);

    while ($db->next_record()) {
        $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat='".cSecurity::toInteger($db->f("idcat"))."'";
        $db2->query($sql);
        while ($db2->next_record()) {
            conSetCodeFlag($db2->f("idcatart"));
        }
    }

    // Search all articles
    $sql = "SELECT
                b.idart
            FROM
                ".$cfg["tab"]["tpl_conf"]." AS a,
                ".$cfg["tab"]["art_lang"]." AS b,
                ".$cfg["tab"]["art"]." AS c
            WHERE
                a.idtpl     = '".cSecurity::toInteger($idtpl)."' AND
                b.idtplcfg  = a.idtplcfg AND
                c.idclient  = '".cSecurity::toInteger($client)."' AND
                b.idart     = c.idart";

    $db->query($sql);

    while ($db->next_record()) {
        $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idart='".cSecurity::toInteger($db->f("idart"))."'";
        $db2->query($sql);

        while ($db2->next_record()) {
            conSetCodeFlag($db2->f("idcatart"));
        }
    }
}


/**
 * Create code for all articles
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG 2003
 */
function conGenerateCodeForAllArts()
{
    global $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"];
    $db->query($sql);
    while ($db->next_record()) {
        conSetCodeFlag($db->f("idcatart"));
    }
}

/**
 * Set code creation flag to true
 *
 * @param int $idcatart Contenido Category-Article-ID
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG 2003
 */
function conSetCodeFlag($idcatart)
{
    global $cfg;

    $db = cRegistry::getDb();

    $sql = "UPDATE ".$cfg["tab"]["cat_art"]." SET createcode = '1' WHERE idcatart='".cSecurity::toInteger($idcatart)."'";
    $db->query($sql);

    /* Setting the createcode flag is not enough due to a bug in the
     * database structure. Remove all con_code entries for a specific
     * idcatart in the con_code table.
     */
     $sql = "DELETE FROM ".$cfg["tab"]["code"] ." WHERE idcatart='".cSecurity::toInteger($idcatart)."'";
     $db->query($sql);
}


/**
 * Set articles on/offline for the time management function
 *
 * @param none
 *
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG 2003
 */
function conFlagOnOffline()
{
    global $cfg;

    $db = cRegistry::getDb();
    $db2 = cRegistry::getDb();

    // Set all articles which are before our starttime to offline
    $sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE NOW() < datestart AND datestart != '0000-00-00 00:00:00' AND datestart IS NOT NULL AND timemgmt = 1";
    $db->query($sql);
    while ($db->next_record()) {
        $sql = "UPDATE ".$cfg["tab"]["art_lang"] ." SET online = 0 WHERE idartlang = '".cSecurity::toInteger($db->f("idartlang"))."'";
        $db2->query($sql);
    }

    // Set all articles which are in between of our start/endtime to online
    $sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE NOW() > datestart AND (NOW() < dateend OR dateend = '0000-00-00 00:00:00') AND " .
            "online = 0 AND timemgmt = 1";
    $db->query($sql);
    while ($db->next_record()) {
        // modified 2007-11-14: Set publish date if article goes online
        $sql = "UPDATE " . $cfg["tab"]["art_lang"] . " SET online = 1, published = datestart " .
                "WHERE idartlang = " . cSecurity::toInteger($db->f("idartlang"));
        $db2->query($sql);
    }

    // Set all articles after our endtime to offline
    $sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE NOW() > dateend AND dateend != '0000-00-00 00:00:00' AND timemgmt = 1";
    $db->query($sql);
    while ($db->next_record()) {
        $sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET online = 0 WHERE idartlang = '" . cSecurity::toInteger($db->f("idartlang")) . "'";
        $db2->query($sql);
    }
}

/**
 * Move articles for the time management function
 * @param none
 *
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG 2003
 */
function conMoveArticles()
{
    global $cfg;

    $db = cRegistry::getDb();
    $db2 = cRegistry::getDb();

    // Perform after-end updates
    $sql = "SELECT idartlang, idart, time_move_cat, time_target_cat, time_online_move FROM ".$cfg["tab"]["art_lang"]." WHERE NOW() > dateend AND dateend != '0000-00-00 00:00:00' AND timemgmt = 1";

    $db->query($sql);

    while ($db->next_record()) {
        if ($db->f("time_move_cat") == "1") {
            $sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET timemgmt = 0, online = 0 WHERE idartlang = '".cSecurity::toInteger($db->f("idartlang"))."'";
            $db2->query($sql);

            $sql = "UPDATE ".$cfg["tab"]["cat_art"]." SET idcat = '" . cSecurity::toInteger($db->f("time_target_cat")) . "', createcode = '1' WHERE idart = '" . cSecurity::toInteger($db->f("idart")) . "'";
            $db2->query($sql);

            if ($db->f("time_online_move") == "1") {
                $sql = "UPDATE ".$cfg["tab"]["art_lang"] ." SET online = 1 WHERE idart = '".cSecurity::toInteger($db->f("idart"))."'";
            } else {
                $sql = "UPDATE ".$cfg["tab"]["art_lang"] ." SET online = 0 WHERE idart = '".cSecurity::toInteger($db->f("idart"))."'";
            }
            $db2->query($sql);

            // execute CEC hook
            CEC_Hook::execute('Contenido.Article.conMoveArticles_Loop', $db->Record);
        }
    }
}


function conCopyTemplateConfiguration($srcidtplcfg)
{
    global $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT idtpl FROM ".$cfg["tab"]["tpl_conf"] ." WHERE idtplcfg = '".cSecurity::toInteger($srcidtplcfg)."'";
    $db->query($sql);
    if (!$db->next_record()) {
        return false;
    }

    $idtpl = $db->f("idtpl");
    $created = date("Y-m-d H:i:s");

    $sql = "INSERT INTO ".$cfg["tab"]["tpl_conf"] . " (idtpl, created) VALUES ('".cSecurity::toInteger($idtpl)."', '".cSecurity::escapeDB($created, $db)."')";
    $db->query($sql);

    return $db->getLastInsertedId(($cfg["tab"]["tpl_conf"]));
}

function conCopyContainerConf($srcidtplcfg, $dstidtplcfg)
{
    global $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT number, container FROM ".$cfg["tab"]["container_conf"] . " WHERE idtplcfg = '".cSecurity::toInteger($srcidtplcfg)."'";
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
        $sql = "INSERT INTO ".$cfg["tab"]["container_conf"]." (idtplcfg, number, container) VALUES ('".cSecurity::toInteger($dstidtplcfg)."',
                '".cSecurity::toInteger($key)."', '".cSecurity::escapeDB($value, $db)."')";
        $db->query($sql);
    }

    return true;
}

function conCopyContent($srcidartlang, $dstidartlang)
{
    global $cfg;

    $db = cRegistry::getDb();

    $sql = "SELECT idtype, typeid, value, version, author FROM ".$cfg["tab"]["content"]." WHERE idartlang = '".cSecurity::toInteger($srcidartlang)."'";
    $db->query($sql);
    $id = 0;
    $val = array();
    while ($db->next_record()) {
        $id++;
        $val[$id]["idtype"] = $db->f("idtype");
        $val[$id]["typeid"] = $db->f("typeid");
        $val[$id]["value"] = $db->f("value");
        $val[$id]["version"]  = $db->f("version");
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

        $sql = "INSERT INTO ".$cfg["tab"]["content"]
              ." ( idartlang, idtype, typeid, value, version, author, created) ".
              "VALUES ('".cSecurity::toInteger($dstidartlang)."', '".cSecurity::toInteger($idtype)."', '".cSecurity::toInteger($typeid)."',
              '".cSecurity::escapeDB($lvalue, $db)."', '".cSecurity::escapeDB($version, $db)."', '".cSecurity::escapeDB($author, $db)."', '".cSecurity::escapeDB($created, $db)."')";

        $db->query($sql);
    }
}

function conCopyArtLang($srcidart, $dstidart, $newtitle, $bUseCopyLabel = true)
{
    global $cfg, $lang;

    $db = cRegistry::getDb();
    $db2 = cRegistry::getDb();

    $sql = "SELECT idartlang, idlang, idtplcfg, title, pagetitle, summary,
            author, online, redirect, redirect, redirect_url,
            artsort, timemgmt, datestart, dateend, status, free_use_01,
            free_use_02, free_use_03, time_move_cat, time_target_cat,
            time_online_move, external_redirect, locked FROM
            ".$cfg["tab"]["art_lang"]." WHERE idart = '".cSecurity::toInteger($srcidart)."' AND idlang='".cSecurity::toInteger($lang)."'";
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

        $sql = "INSERT INTO ".$cfg["tab"]["art_lang"]."
                (idart, idlang, idtplcfg, title,
                pagetitle, summary, created, lastmodified,
                author, online, redirect, redirect_url,
                artsort, timemgmt, datestart, dateend,
                status, free_use_01, free_use_02, free_use_03,
                time_move_cat, time_target_cat, time_online_move,
                external_redirect, locked) VALUES (
                '".cSecurity::toInteger($idart)."',
                '".cSecurity::toInteger($idlang)."',
                '".cSecurity::toInteger($idtplcfg)."',
                '".cSecurity::escapeDB($title, $db2)."',
                '".cSecurity::escapeDB($pagetitle, $db2)."',
                '".cSecurity::escapeDB($summary, $db2)."',
                '".cSecurity::escapeDB($created, $db2)."',
                '".cSecurity::escapeDB($created, $d2b)."',
                '".cSecurity::escapeDB($author, $db2)."',
                '".cSecurity::toInteger($online)."',
                '".cSecurity::escapeDB($redirect, $db2)."',
                '".cSecurity::escapeDB($redirecturl, $db2)."',
                '".cSecurity::toInteger($artsort)."',
                '".cSecurity::toInteger($timemgmt)."',
                '".cSecurity::escapeDB($datestart, $db2)."',
                '".cSecurity::escapeDB($dateend, $db2)."',
                '".cSecurity::toInteger($status)."',
                '".cSecurity::toInteger($freeuse01)."',
                '".cSecurity::toInteger($freeuse02)."',
                '".cSecurity::toInteger($freeuse03)."',
                '".cSecurity::toInteger($timemovecat)."',
                '".cSecurity::toInteger($timetargetcat)."',
                '".cSecurity::toInteger($timeonlinemove)."',
                '".cSecurity::escapeDB($externalredirect, $db)."',
                '".cSecurity::toInteger($locked)."')";

        $db2->query($sql);

        conCopyContent($db->f("idartlang"), $db->getLastInsertedId($cfg["tab"]["art_lang"]));

        // execute CEC hook
        CEC_Hook::execute('Contenido.Article.conCopyArtLang_AfterInsert', array(
            'idartlang' => cSecurity::toInteger($idartlang),
            'idart'     => cSecurity::toInteger($idart),
            'idlang'    => cSecurity::toInteger($idlang),
            'idtplcfg'  => cSecurity::toInteger($idtplcfg),
            'title'     => cSecurity::escapeDB($title, $db2)
        ));

        // Copy meta tags
        $sql = "SELECT idmetatype, metavalue FROM ".$cfg["tab"]["meta_tag"]." WHERE idartlang = '".cSecurity::toInteger($db->f("idartlang"))."'";
        $db->query($sql);

        while ($db->next_record()) {
            //$nextidmetatag = $db2->nextid($cfg["tab"]["meta_tag"]);
            $metatype = $db->f("idmetatype");
            $metavalue = $db->f("metavalue");
            $sql = "INSERT INTO ".$cfg["tab"]["meta_tag"]."
                        (idartlang, idmetatype, metavalue)
                        VALUES
                        ('".cSecurity::toInteger($idartlang)."', '".cSecurity::toInteger($metatype)."', '".cSecurity::escapeDB($metavalue, $db2)."')";
            $db2->query($sql);
        }

        // Update keyword list for new article
        conMakeArticleIndex ($idartlang, $idart);
    }
}

function conCopyArticle($srcidart, $targetcat = 0, $newtitle = "", $bUseCopyLabel = true)
{
    global $cfg, $_cecRegistry;

    $db = cRegistry::getDb();
    $db2 = cRegistry::getDb();

    $sql = "SELECT idclient FROM ".$cfg["tab"]["art"] ." WHERE idart = '".cSecurity::toInteger($srcidart)."'";
    $db->query($sql);
    if (!$db->next_record()) {
        return false;
    }

    $idclient = $db->f("idclient");

    $sql = "INSERT INTO ".$cfg["tab"]["art"]." (idclient) VALUES ('".cSecurity::toInteger($idclient)."')";
    $db->query($sql);
    //$dstidart = $db->nextid($cfg["tab"]["art"]);
    $dstidart = $db->getLastInsertedId($cfg["tab"]["art"]);
    conCopyArtLang($srcidart, $dstidart, $newtitle, $bUseCopyLabel);

    // Update category relationship
    $sql = "SELECT idcat, status FROM ".$cfg["tab"]["cat_art"]." WHERE idart = '".cSecurity::toInteger($srcidart)."'";
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

        $sql = "INSERT INTO ".$cfg["tab"]["cat_art"]." (".implode(", ", array_keys($aFields)).") VALUES (".implode(", ", array_values($aFields)).");";
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

function conGetTopmostCat($idcat, $minLevel = 0)
{
    global $cfg, $client, $lang;

    $db = cRegistry::getDb();

    $sql = "SELECT
                a.name AS name,
                a.idcat AS idcat,
                b.parentid AS parentid,
                c.level AS level
            FROM
                ".$cfg["tab"]["cat_lang"]." AS a,
                ".$cfg["tab"]["cat"]." AS b,
                ".$cfg["tab"]["cat_tree"]." AS c
            WHERE
                a.idlang    = " . (int) $lang . " AND
                b.idclient  = " . (int) $client . " AND
                b.idcat     = " . (int) $idcat . " AND
                c.idcat     = b.idcat AND
                a.idcat     = b.idcat";

    $db->query($sql);
    $db->next_record();

    $name      = $db->f("name");
    $parentid  = $db->f("parentid");
    $thislevel = $db->f("level");

    if ($parentid != 0 && $thislevel >= $minLevel) {
        return conGetTopmostCat($parentid, $minLevel);
    } else {
        return $idcat;
    }
}

function conSyncArticle($idart, $srclang, $dstlang)
{
    global $cfg;

    $db = cRegistry::getDb();
    $db2 = cRegistry::getDb();

    // Check if article has already been synced to target language
    $sql = "SELECT * FROM ".$cfg['tab']['art_lang']." WHERE idart = " . (int) $idart . " AND idlang = " . (int) $dstlang;
    $db2->query($sql);

    $sql = "SELECT * FROM ".$cfg["tab"]["art_lang"]." WHERE idart = " . (int) $idart . " AND idlang = " . (int) $srclang;
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
        $param['src_art_lang']  = $db->Record;
        $param['dest_art_lang'] = $db2->Record;
        $param['dest_art_lang']['idartlang'] = (int) $newidartlang;
        $param['dest_art_lang']['idlang']    = (int) $dstlang;
        $param['dest_art_lang']['idtplcfg']  = (int) $newidtplcfg;
        CEC_Hook::execute('Contenido.Article.conSyncArticle_AfterInsert', $param);

        // Copy content
        $sql = "SELECT * FROM " . $cfg["tab"]["content"] . " WHERE idartlang = ". (int) $idartlang;
        $db->query($sql);
        while ($db->next_record()) {
            $rs = $db->toArray();
            $oContentColl = new cApiContentCollection();
            $oContentColl->create((int) $newidartlang, $rs['idtype'], $rs['typeid'], $rs['value'], $rs['version'], $rs['author'], $rs['created'], $rs['lastmodified']);
        }

        // Copy meta tags
        $sql = "SELECT idmetatype, metavalue FROM ".$cfg["tab"]["meta_tag"]." WHERE idartlang = '$idartlang'";
        $db->query($sql);
        while ($db->next_record()) {
            $rs = $db->toArray();
            $oMetaTagColl = new cApiMetaTagCollection();
            $oMetaTagColl->create((int) $newidartlang, $rs['idmetatype'], $rs['metavalue']);
        }
    }
}

function isStartArticle($idartlang, $idcat, $idlang, $db = null)
{
    global $cfg;

    if (!is_object($db)) {
        $db = cRegistry::getDb();
    }

    $sql = "SELECT startidartlang FROM ".$cfg["tab"]["cat_lang"]."
            WHERE startidartlang=". (int) $idartlang . " AND idcat=" . (int) $idcat . " AND idlang=" . (int) $idlang;
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
function conGetCategoryAssignments($idart, $db = null)
{
    global $cfg;

    if (!is_object($db)) {
        $db = cRegistry::getDb();
    }

    $categories = array();

    $sql = "SELECT idcat FROM ".$cfg["tab"]["cat_art"]." WHERE idart = ". (int) $idart;
    $db->query($sql);

    while ($db->next_record()) {
        $categories[] = $db->f("idcat");
    }

    return $categories;
}

?>