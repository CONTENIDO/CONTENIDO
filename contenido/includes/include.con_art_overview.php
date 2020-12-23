<?php

/**
 * This file contains the backend page for displaying articles of a category.
 *
 * @package Core
 * @subpackage Backend
 * @author Jan Lengowski
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.tpl.php');
cInclude('includes', 'functions.str.php');
cInclude('includes', 'functions.pathresolver.php');

$db2 = cRegistry::getDb();

$idcat = (isset($_REQUEST['idcat']) && is_numeric($_REQUEST['idcat'])) ? $_REQUEST['idcat'] : -1;
$next = (isset($_REQUEST['next']) && is_numeric($_REQUEST['next']) && $_REQUEST['next'] > 0) ? $_REQUEST['next'] : 0;

$dateformat = getEffectiveSetting('dateformat', 'date', 'Y-m-d');
$templateDescription = '';

if (!isset($syncfrom)) {
    $syncfrom = -1;
}

$syncoptions = $syncfrom;
// CON-1752
// init duplicate counter in session
if (!isset($_SESSION['count_duplicate'])) {
    $_SESSION['count_duplicate'] = 0;
}

// New Article Selected: Unset Selected Article Id
global $selectedArticleId;
$selectedArticleId = NULL;

if ($action == 'con_duplicate' && ($perm->have_perm_area_action("con", "con_duplicate") || $perm->have_perm_area_action_item("con", "con_duplicate", $idcat))) {

    $count = (int) $_SESSION['count_duplicate'];

    // check if duplicate action was called from click or from back button
    if ($_GET['count_duplicate'] < $count) {
    } else {
        // perfom action only when duplicate action is called from link
        $newidartlang = conCopyArticle($duplicate, $idcat);
        $count++;
        $_SESSION['count_duplicate'] = $count;
    }
}

if ($action == 'con_syncarticle' && ($perm->have_perm_area_action("con", "con_syncarticle") || $perm->have_perm_area_action_item("con", "con_syncarticle", $idcat))) {
    if ($_POST['idarts']) {
        $idarts = json_decode($_POST['idarts'], true);
    } else {
        $idarts = array(
            $idart
        );
    }

    // Verify that the category is available in this language
    $catLang = new cApiCategoryLanguage();
    foreach ($idarts as $idart) {
        if (!$catLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
            strSyncCategory($idcat, $sourcelanguage, $lang);
        }
        conSyncArticle($idart, $sourcelanguage, $lang);
    }
}

// Which columns to display?
$listColumns = array(
    "mark" => i18n("Mark"),
    "start" => i18n("Article"),
    "title" => i18n("Title"),
    "changeddate" => i18n("Changed"),
    "publisheddate" => i18n("Published"),
    "sortorder" => i18n("Sort order"),
    "template" => i18n("Template"),
    "actions" => i18n("Actions")
);

// Which actions to display?
$actionList = array(
    "online",
    "duplicate",
    "locked",
    "todo",
    "delete",
    "usetime"
);

// Call chains to process the columns and the action list
$_cecIterator = $_cecRegistry->getIterator("Contenido.ArticleList.Columns");
if ($_cecIterator->count() > 0) {
    while ($chainEntry = $_cecIterator->next()) {
        $newColumnList = $chainEntry->execute($listColumns);
        if (is_array($newColumnList)) {
            $listColumns = $newColumnList;
        }
    }
}

$_cecIterator = $_cecRegistry->getIterator("Contenido.ArticleList.Actions");
if ($_cecIterator->count() > 0) {
    while ($chainEntry = $_cecIterator->next()) {
        $newActionList = $chainEntry->execute($actionList);
        if (is_array($newActionList)) {
            $actionList = $newActionList;
        }
    }
}

$cat_idtpl = 0;

if (is_numeric($idcat) && ($idcat >= 0)) {
    // Saving sort and elements per page user settings (if specified)
    // Should be changed to User->setProperty... someday
    if (isset($sortby)) {
        $currentuser->setUserProperty("system", "sortorder-idlang-$lang-idcat-$idcat", $sortby);
    }
    if (isset($sortmode)) {
        $currentuser->setUserProperty("system", "sortmode-idlang-$lang-idcat-$idcat", $sortmode);
    }

    if (isset($elemperpage) && is_numeric($elemperpage)) {
        $currentuser->setUserProperty("system", "elemperpage-idlang-$lang-idcat-$idcat", $elemperpage);
    } else {
        $elemperpage = $currentuser->getUserProperty("system", "elemperpage-idlang-$lang-idcat-$idcat");
        if (!is_numeric($elemperpage)) {
            $elemperpage = 10;
        }
    }

    $col = new cApiInUseCollection();

    if ((($idcat == 0 || $perm->have_perm_area_action('con')) && $perm->have_perm_item('str', $idcat)) || $perm->have_perm_area_action('con', 'con_makestart') || $perm->have_perm_area_action('con', 'con_makeonline') || $perm->have_perm_area_action('con', 'con_deleteart') || $perm->have_perm_area_action('con', 'con_tplcfg_edit') || $perm->have_perm_area_action('con', 'con_lock') || $perm->have_perm_area_action('con', 'con_makecatonline') || $perm->have_perm_area_action('con', 'con_changetemplate') || $perm->have_perm_area_action('con_editcontent', 'con_editart') || $perm->have_perm_area_action('con_editart', 'con_edit') || $perm->have_perm_area_action('con_editart', 'con_newart') || $perm->have_perm_area_action('con_editart', 'con_saveart') || $perm->have_perm_area_action('con_tplcfg', 'con_tplcfg_edit') || $perm->have_perm_area_action_item('con', 'con_makestart', $idcat) || $perm->have_perm_area_action_item('con', 'con_makeonline', $idcat) || $perm->have_perm_area_action_item('con', 'con_deleteart', $idcat) || $perm->have_perm_area_action_item('con', 'con_tplcfg_edit', $idcat) || $perm->have_perm_area_action_item('con', 'con_lock', $idcat) || $perm->have_perm_area_action_item('con', 'con_makecatonline', $idcat) || $perm->have_perm_area_action_item('con', 'con_changetemplate', $idcat) || $perm->have_perm_area_action_item('con_editcontent', 'con_editart', $idcat) || $perm->have_perm_area_action_item('con_editart', 'con_edit', $idcat) || $perm->have_perm_area_action_item('con_editart', 'con_newart', $idcat) || $perm->have_perm_area_action_item('con_tplcfg', 'con_tplcfg_edit', $idcat) || $perm->have_perm_area_action_item('con_editart', 'con_saveart', $idcat)) {

        // Simple SQL statement to get the number of articles in selected language
        $sql_count_in_selected_language = "SELECT
                    COUNT(*) AS article_count
                 FROM
                    " . $cfg["tab"]["art_lang"] . " AS a,
                    " . $cfg["tab"]["art"] . " AS b,
                    " . $cfg["tab"]["cat_art"] . " AS c
                 WHERE
                    a.idlang   = " . cSecurity::toInteger($lang) . "  AND
                    a.idart     = b.idart AND
                    b.idclient  = " . cSecurity::toInteger($client) . " AND
                    b.idart     = c.idart AND
                    c.idcat     = " . cSecurity::toInteger($idcat);

        $db->query($sql_count_in_selected_language);
        $db->nextRecord();
        $articles_in_selected_language = $db->f("article_count");

        // Sortby and sortmode
        $sortby = $currentuser->getUserProperty("system", "sortorder-idlang-$lang-idcat-$idcat");
        $sortmode = $currentuser->getUserProperty("system", "sortmode-idlang-$lang-idcat-$idcat");

        // Main SQL statement
        $sql = "SELECT
                    a.idart AS idart,
                    a.idlang AS idlang,
                    a.idartlang AS idartlang,
                    a.title AS title,
                    c.idcat AS idcat,
                    c.idcatart AS idcatart,
                    a.idtplcfg AS idtplcfg,
                    a.published AS published,
                    a.online AS online,
                    a.created AS created,
                    a.lastmodified AS lastmodified,
                    a.timemgmt AS timemgmt,
                    a.datestart AS datestart,
                    a.dateend AS dateend,
                    a.artsort AS artsort,
                    a.redirect AS redirect,
                    a.locked AS locked
                 FROM
                    " . $cfg["tab"]["art_lang"] . " AS a,
                    " . $cfg["tab"]["art"] . " AS b,
                    " . $cfg["tab"]["cat_art"] . " AS c
                 WHERE
                    (a.idlang   = " . $lang . " {SYNCOPTIONS}) AND
                    a.idart     = b.idart AND
                    b.idclient  = " . $client . " AND
                    b.idart     = c.idart AND
                    c.idcat     = " . $idcat;

        // Simple SQL statement to get the number of articles
        // Only with activated synchonization mode
        if ($syncoptions != -1) {
            $sql_count = "SELECT
                        COUNT(*) AS article_count
                     FROM
                        " . $cfg["tab"]["art_lang"] . " AS a,
                        " . $cfg["tab"]["art"] . " AS b,
                        " . $cfg["tab"]["cat_art"] . " AS c
                     WHERE
                        (a.idlang   = " . cSecurity::toInteger($lang) . " {SYNCOPTIONS}) AND
                        a.idart     = b.idart AND
                        b.idclient  = " . cSecurity::toInteger($client) . " AND
                        b.idart     = c.idart AND
                        c.idcat     = " . cSecurity::toInteger($idcat);

            $sql = str_replace("{SYNCOPTIONS}", "OR a.idlang = '" . $syncoptions . "'", $sql);
            $sql_count = str_replace("{SYNCOPTIONS}", "OR a.idlang = '" . $syncoptions . "'", $sql_count);

            if ($elemperpage > 1) {
                $db->query($sql_count);
                $db->nextRecord();
                $iArticleCount = $db->f("article_count");
            }
        } else {
            $sql = str_replace("{SYNCOPTIONS}", '', $sql);
            $iArticleCount = $articles_in_selected_language;
        }

        // Article sort
        if ($sortmode !== 'asc' && $sortmode !== 'desc') {
            $sortmode = 'asc';
        }
        switch ($sortby) {
            case 2:
                $sql .= ' ORDER BY a.lastmodified ' . cString::toUpperCase($sortmode);
                break;
            case 3:
                $sql .= ' ORDER BY a.published ' . cString::toUpperCase($sortmode) . ', a.lastmodified ' . cString::toUpperCase($sortmode);
                break;
            case 4:
                $sql .= ' ORDER BY a.artsort ' . cString::toUpperCase($sortmode);
                break;
            default:
                // Default sort order
                $sql .= ' ORDER BY a.title ' . cString::toUpperCase($sortmode);
                $sortby = 1;
        }

        // Getting article count, if necessary
        if ($elemperpage > 0) {

            // If the synchronized mode is on, perhaps we must increase the limit
            if ($elemperpage < $iArticleCount) {
                $add = $iArticleCount - $articles_in_selected_language;
                $elemperpage = $elemperpage + $add;
            }

            // If not beyond scope, limit
            if ($iArticleCount == 0) {
                $next = 0;
            } elseif ($next >= $iArticleCount) {
                $next = (ceil($iArticleCount / $elemperpage) - 1) * $elemperpage;
            }
            $sql .= " LIMIT $next, $elemperpage";
        } else {
            $iArticleCount = 0; // Will be used to "hide" the browsing area
        }

        // Getting data
        $db->query($sql);

        // Reset Template
        $tpl->reset();

        // No article
        $no_article = true;

        $aArticles = array();

        while ($db->nextRecord()) {
            $sItem = "k" . $db->f("idart");

            if ($db->f("idlang") == $lang || !array_key_exists($sItem, $aArticles)) {
                $aArticles[$sItem]["idart"] = $db->f("idart");
                $aArticles[$sItem]["idlang"] = $db->f("idlang");
                $aArticles[$sItem]["idartlang"] = $db->f("idartlang");
                $aArticles[$sItem]["title"] = cSecurity::unFilter($db->f("title"));
                $aArticles[$sItem]["is_start"] = isStartArticle($db->f("idartlang"), $idcat, $lang);
                $aArticles[$sItem]["idcatart"] = $db->f("idcatart");
                $aArticles[$sItem]["idtplcfg"] = $db->f("idtplcfg");
                $aArticles[$sItem]["published"] = $db->f("published");
                $aArticles[$sItem]["online"] = $db->f("online");
                $aArticles[$sItem]["created"] = $db->f("created");
                $aArticles[$sItem]["idcat"] = $db->f("idcat");
                $aArticles[$sItem]["lastmodified"] = $db->f("lastmodified");
                $aArticles[$sItem]["timemgmt"] = $db->f("timemgmt");
                $aArticles[$sItem]["datestart"] = $db->f("datestart");
                $aArticles[$sItem]["dateend"] = $db->f("dateend");
                $aArticles[$sItem]["artsort"] = $db->f("artsort");
                $aArticles[$sItem]["locked"] = $db->f("locked");
                $aArticles[$sItem]["redirect"] = $db->f("redirect");
            }
        }

        $artlist = array();
        $colitem = array();
        $articlesOnline = 0;
        $articlesOffline = 0;
        $articlesLocked = 0;
        $articlesUnlocked = 0;
        $articlesToSync = 0;
        $articlesToRemove = 0;
        $articlesToEdit = 0;

        foreach ($aArticles as $sart) {
            $idart = $sart["idart"];
            $idlang = $sart["idlang"];

            $idtplcfg = $sart["idtplcfg"];
            $idartlang = $sart["idartlang"];
            $lidcat = $sart["idcat"];
            $idcatlang = 0;
            $idart = $sart["idart"];
            $published = $sart["published"];
            $online = $sart["online"];

            $is_start = $sart["is_start"];

            $idcatart = $sart["idcatart"];
            $created = $sart["created"];
            $modified = $sart["lastmodified"];

            if ($modified === '0000-00-00 00:00:00') {
                $modified = i18n("not modified yet");
            } else {
                $modified = date($dateformat, strtotime($modified));
            }
            $title = conHtmlSpecialChars($sart["title"]);
            $timemgmt = $sart["timemgmt"];
            $datestart = $sart["datestart"];
            $dateend = $sart["dateend"];
            $sortkey = $sart["artsort"];
            $locked = $sart["locked"];
            $redirect = $sart["redirect"];

            $published = ($published != '0000-00-00 00:00:00') ? date($dateformat, strtotime($published)) : i18n("not yet published");
            $created = date($dateformat, strtotime($created));
            $alttitle = "idart" . '&#58; ' . $idart . ' ' . "idcatart" . '&#58; ' . $idcatart . ' ' . "idartlang" . '&#58; ' . $idartlang;

            $articlesToEdit++;

            if ($idlang != $lang) {
                $articlesToSync++;
            } else {
                if ($online == 1) {
                    $articlesOnline++;
                } else {
                    $articlesOffline++;
                }
                if ($locked == 1) {
                    $articlesLocked++;
                } else {
                    $articlesUnlocked++;
                }
                $articlesToRemove++;
            }

            if ((($obj = $col->checkMark("article", $idartlang)) === false) || $obj->get("userid") == $auth->auth['uid']) {
                $inUse = false;
            } else {
                $vuser = new cApiUser($obj->get("userid"));
                $inUseUser = $vuser->getField("username");
                $inUseUserRealName = $vuser->getField("realname");

                $inUse = true;
                $title = $title . " (" . i18n("Article is in use") . ")";
                $alttitle = sprintf(i18n("Article in use by %s (%s)"), $inUseUser, $inUseUserRealName) . " " . $alttitle;
            }

            // Id of the row, stores informations about the article and category
            $tmp_rowid = $idart . "-" . $idartlang . "-" . $lidcat . "-" . $idcatlang . "-" . $idcatart . "-" . $idlang;
            $tpl->set('d', 'ROWID', $tmp_rowid);

            if ($idlang != $lang) {
                $colitem[$tmp_rowid] = 'con_sync';
            }

            // Article Title
            if ($perm->have_perm_area_action('con_editcontent', 'con_editart') || $perm->have_perm_area_action_item('con_editcontent', 'con_editart', $idcat)) {
                if ($idlang != $lang) {
                    $tmp_alink = $sess->url("main.php?area=con_editcontent&action=con_editart&changeview=prev&idartlang=$idartlang&idart=$idart&idcat=$idcat&frame=$frame&tmpchangelang=$idlang");
                    $titlelink = '<a href="' . $tmp_alink . '" title="' . $alttitle . '">' . $title . '</a>';
                } else {
                    $tmp_alink = $sess->url("main.php?area=con_editcontent&action=con_editart&changeview=edit&idartlang=$idartlang&idart=$idart&idcat=$idcat&frame=$frame");
                    $titlelink = '<a href="' . $tmp_alink . '" title="' . $alttitle . '">' . $title . '</a>';
                }
            } else {
                $tmp_alink = '';
                $titlelink = $title;
            }

            if ($timemgmt == "1") {
                $sql = "SELECT NOW() AS TIME";

                $db3 = cRegistry::getDb();

                $db3->query($sql);
                $db3->nextRecord();

                $starttimestamp = strtotime($datestart);
                $endtimestamp = strtotime($dateend);
                $nowtimestamp = strtotime($db3->f("TIME"));

                if (($nowtimestamp < $endtimestamp) && ($nowtimestamp > $starttimestamp)) {
                    $usetime = '<img class="vAlignMiddle tableElement" src="images/but_time_2.gif" alt="' . i18n("Article with time control online") . '" title="' . i18n("Article with time control online") . '">';
                } else {
                    $usetime = '<img class="vAlignMiddle tableElement" src="images/but_time_1.gif" alt="' . i18n("Article with time control offline") . '" title="' . i18n("Article with time control offline") . '">';
                }
            } else {
                $usetime = '';
            }

            // Article Title
            if (($perm->have_perm_area_action('con', 'con_lock') || $perm->have_perm_area_action_item('con', 'con_lock', $idcat)) && $inUse == false) {
                if ($locked == 1) {
                    $lockimg = 'images/article_locked.gif';
                    $lockalt = i18n("Unfreeze article");
                } else {
                    $lockimg = 'images/article_unlocked.gif';
                    $lockalt = i18n("Freeze article");
                }
                $tmp_lock = '<a href="' . $sess->url("main.php?area=con&idcat=$idcat&action=con_lock&frame=4&idart=$idart&next=$next") . '" title="' . $lockalt . '"><img class="vAlignMiddle tableElement" src="' . $lockimg . '" title="' . $lockalt . '" alt="' . $lockalt . '" border="0"></a>';
            } else {
                if ($locked == 1) {
                    $lockimg = 'images/article_locked.gif';
                    $lockalt = i18n("Article is frozen");
                } else {
                    $lockimg = 'images/article_unlocked.gif';
                    $lockalt = i18n("Article is not frozen");
                }
                $tmp_lock = '<img class="vAlignMiddle tableElement" src="' . $lockimg . '" title="' . $lockalt . '" alt="' . $lockalt . '" border="0">';
            }

            if ($idlang != $lang) {
                $lockedlink = '';
            } else {
                $lockedlink = $tmp_lock;
            }

            if ($sortkey == '') {
                $sortkey = '&nbsp;';
            }

            $tmp_articletitle = $titlelink;

            // Article conf button
            if ($perm->have_perm_area_action('con_editart', 'con_edit') || $perm->have_perm_area_action_item('con_editart', 'con_edit', $idcat)) {
                $tmp_artconf = '<a href="' . $sess->url("main.php?area=con_editart&action=con_edit&frame=4&idart=$idart&idcat=$idcat") . '" title="' . i18n("Article properties") . '"><img class="vAlignMiddle tableElement" src="' . $cfg["path"]["images"] . 'but_art_conf2.gif" alt="' . i18n("Article properties") . '" title="' . i18n("Article properties") . '" border="0"></a>';
            } else {
                $tmp_artconf = '';
            }

            $tmp_sync = '';
            if ($idlang != $lang) {
                $sql = "SELECT idcatlang FROM " . $cfg["tab"]["cat_lang"] . " WHERE idcat='" . cSecurity::toInteger($idcat) . "' AND idlang='" . cSecurity::toInteger($lang) . "'";
                $db->query($sql);

                if ($db->nextRecord()) {
                    $tmp_sync = '<a href="' . $sess->url("main.php?area=con&action=con_syncarticle&idart=$idart&sourcelanguage=$idlang&frame=4&idcat=$idcat&next=$next") . '" title="' . i18n("Copy article to the current language") . '"><img class="vAlignMiddle tableElement" src="' . $cfg["path"]["images"] . 'but_sync_art.gif" alt="' . i18n("Copy article to the current language") . '" title="' . i18n("Copy article to the current language") . '" border="0"></a>';
                } else {
                    $tmp_sync = '';
                    $articlesToSync--;
                    $articlesToRemove--;
                }
            }

            // Article Template
            if (!is_object($db2)) {
                $db2 = cRegistry::getDb();
            }

            $sql2 = "SELECT
                        b.name AS tplname,
                        b.idtpl AS idtpl,
                        b.description AS description
                     FROM
                        " . $cfg["tab"]["tpl_conf"] . " AS a,
                        " . $cfg["tab"]["tpl"] . " AS b
                     WHERE
                        a.idtplcfg = " . cSecurity::toInteger($idtplcfg) . " AND
                        a.idtpl = b.idtpl";

            $db2->query($sql2);
            $db2->nextRecord();

            $a_tplname = $db2->f("tplname");
            $a_idtpl = $db2->f("idtpl");
            $templateDescription = $db2->f("description");

            // Uses Category Template
            if (0 == $idtplcfg) {
                $sql2 = "SELECT
                    c.idtpl AS idtpl,
                    c.name AS name,
                    c.description,
                    b.idtplcfg AS idtplcfg
                FROM
                    " . $cfg['tab']['tpl_conf'] . " AS a,
                    " . $cfg['tab']['cat_lang'] . " AS b,
                    " . $cfg['tab']['tpl'] . " AS c
                WHERE
                    b.idcat     = " . cSecurity::toInteger($idcat) . " AND
                    b.idlang    = " . cSecurity::toInteger($lang) . " AND
                    b.idtplcfg  = a.idtplcfg AND
                    c.idtpl     = a.idtpl AND
                    c.idclient  = " . cSecurity::toInteger($client);
                $db2->query($sql2);
                $db2->nextRecord();
                $a_tplname = $db2->f("name") ? '<i>' . $db2->f("name") . '</i>' : "--- " . i18n("None") . " ---";
            }

            // CON-2137 check admin permission
            $aAuthPerms = explode(',', $auth->auth['perm']);

            $admin = false;
            if (count(preg_grep("/admin.*/", $aAuthPerms)) > 0) {
                $admin = true;
            }

            // Make Startarticle button
            $imgsrc = "isstart";

            if ($is_start == false) {
                $imgsrc .= '0';
            } else {
                $imgsrc .= '1';
            }
            if (isArtInMultipleUse($idart)) {
                $imgsrc .= 'm';
            }
            if ((int) $redirect == 1) {
                $imgsrc .= 'r';
            }

            $imgsrc .= '.gif';

            if ($idlang == $lang && ($perm->have_perm_area_action('con', 'con_makestart') || $perm->have_perm_area_action_item('con', 'con_makestart', $idcat)) && $idcat != 0 && ((int) $locked === 0 || $admin)) {
                if ($is_start == false) {
                    $tmp_link = '<a href="' . $sess->url("main.php?area=con&amp;idcat=$idcat&amp;action=con_makestart&amp;idcatart=$idcatart&amp;frame=4&is_start=1&amp;next=$next") . '" title="' . i18n("Flag as start article") . '"><img class="vAlignMiddle tableElement" src="images/' . $imgsrc . '" border="0" title="' . i18n("Flag as start article") . '" alt="' . i18n("Flag as start article") . '"></a>';
                } else {
                    $tmp_link = '<a href="' . $sess->url("main.php?area=con&amp;idcat=$idcat&amp;action=con_makestart&amp;idcatart=$idcatart&amp;frame=4&amp;is_start=0&amp;next=$next") . '" title="' . i18n("Flag as normal article") . '"><img class="vAlignMiddle tableElement" src="images/' . $imgsrc . '" border="0" title="' . i18n("Flag as normal article") . '" alt="' . i18n("Flag as normal article") . '"></a>';
                }
            } else {
                if ($is_start == true) {
                    $sTitle = i18n("Start article");
                } else {
                    $sTitle = i18n("Normal article");
                }

                $tmp_img = '<img class="vAlignMiddle tableElement" src="images/' . $imgsrc . '" border="0" title="' . $sTitle . '" alt="' . $sTitle . '">';

                $tmp_link = $tmp_img;
            }

            $tmp_start = $tmp_link;

            // Make copy button
            if (($perm->have_perm_area_action('con', 'con_duplicate') || $perm->have_perm_area_action_item('con', 'con_duplicate', $idcat)) && $idcat != 0 && ((int) $locked === 0 || $admin )) {
                $imgsrc = "but_copy.gif";
                // add count_duplicate param to identify if the duplicate action
                // is called from click or back button.
                $tmp_link = '<a href="' . $sess->url("main.php?area=con&idcat=$idcat&action=con_duplicate&duplicate=$idart&frame=4&next=$next") . "&count_duplicate=" . $_SESSION['count_duplicate'] . '" title="' . i18n("Duplicate article") . '"><img class="vAlignMiddle tableElement" src="images/' . $imgsrc . '" border="0" title="' . i18n("Duplicate article") . '" alt="' . i18n("Duplicate article") . '"></a>';
            } else {
                $tmp_link = '';
            }

            if ($idlang != $lang) {
                $duplicatelink = '';
            } else {
                $duplicatelink = $tmp_link;
            }

            // Make todo link
            $todolink = '';

            $subject = urlencode(sprintf(i18n("Reminder for article '%s'"), $title));
            $mycatname = '';
            conCreateLocationString($idcat, "&nbsp;/&nbsp;", $mycatname);
            $message = urlencode(sprintf(i18n("Reminder for article '%s'\nCategory: %s"), $title, $mycatname));

            $todolink = new TODOLink("idart", $idart, $subject, $message);

            // Make On-/Offline button
            if ($online) {
                if (($perm->have_perm_area_action('con', 'con_makeonline') || $perm->have_perm_area_action_item('con', 'con_makeonline', $idcat)) && ($idcat != 0) && ((int) $locked === 0 || $admin)) {
                    $tmp_online = '<a href="' . $sess->url("main.php?area=con&idcat=$idcat&action=con_makeonline&frame=4&idart=$idart&next=$next") . '" title="' . i18n("Make offline") . '"><img class="vAlignMiddle tableElement" src="images/online.gif" title="' . i18n("Make offline") . '" alt="' . i18n("Make offline") . '" border="0"></a>';
                } else {
                    $tmp_online = '<img class="vAlignMiddle tableElement" src="images/online.gif" title="' . i18n("Article is online") . '" alt="' . i18n("Article is online") . '" border="0">';
                }
            } else {
                if (($perm->have_perm_area_action('con', 'con_makeonline') || $perm->have_perm_area_action_item('con', 'con_makeonline', $idcat)) && ($idcat != 0) && ((int) $locked === 0 || $admin)) {
                    $tmp_online = '<a href="' . $sess->url("main.php?area=con&idcat=$idcat&action=con_makeonline&frame=4&idart=$idart&next=$next") . '" title="' . i18n("Make online") . '"><img class="vAlignMiddle tableElement" src="images/offline.gif" title="' . i18n("Make online") . '" alt="' . i18n("Make online") . '" border="0"></a>';
                } else {
                    $tmp_online = '<img class="vAlignMiddle tableElement" src="images/offline.gif" title="' . i18n("Article is offline") . '" alt="' . i18n("Article is offline") . '" border="0">';
                }
            }

            if ($idlang != $lang) {
                $onlinelink = '';
            } else {
                $onlinelink = $tmp_online;
            }

            // Delete button
            if (($perm->have_perm_area_action('con', 'con_deleteart') || $perm->have_perm_area_action_item('con', 'con_deleteart', $idcat)) && $inUse == false && ((int) $locked === 0  || $admin)) {
                $tmp_title = $title;
                if (cString::getStringLength($tmp_title) > 30) {
                    $tmp_title = cString::getPartOfString($tmp_title, 0, 27) . "...";
                }

                $confirmString = sprintf(i18n("Are you sure to delete the following article:<br><br><b>%s</b>"), conHtmlSpecialChars($tmp_title));
                $tmp_del = '<a href="javascript:void(0)" onclick="Con.showConfirmation(&quot;' . $confirmString . '&quot;, function() { deleteArticle(' . $idart . ', ' . $idcat . ', ' . $next . '); });return false;" title="' . i18n("Delete article") . '"><img class="vAlignMiddle tableElement" src="images/delete.gif" title="' . i18n("Delete article") . '" alt="' . i18n("Delete article") . '"></a>';
            } else {
                $tmp_del = '';
            }

            if ($idlang != $lang) {
                $deletelink = '';
            } else {
                $deletelink = $tmp_del;
            }

            // DIRECTION
            cInclude('includes', 'functions.lang.php');
            $tpl->set('d', 'DIRECTION', 'dir="' . langGetTextDirection($lang) . '"');

            // Next iteration
            // Articles found
            $no_article = false;
            $oArtLang = new cApiArticleLanguage();
            foreach ($listColumns as $listColumn => $ctitle) {
                $oArtLang->loadBy($oArtLang->getPrimaryKeyName(), $idartlang);

                switch ($listColumn) {
                    case "mark":
                        $value = '<input type="checkbox" name="mark" value="' . $idart . '" class="mark_articles">';
                        break;
                    case "start":
                        $value = $tmp_start . $usetime;
                        break;
                    case "title":
                        $value = $tmp_articletitle;
                        break;
                    case "changeddate":
                        $value = $modified;
                        break;
                    case "publisheddate":
                        if ('1' === $oArtLang->get('online')) {
                            $value = $published;
                        } else {
                            $value = i18n("not yet published");
                        }
                        break;
                    case "sortorder":
                        $value = $sortkey;
                        break;
                    case "template":
                        $value = $a_tplname;
                        break;
                    case "actions":
                        $actions = array();
                        foreach ($actionList as $actionItem) {
                            switch ($actionItem) {
                                case "todo":
                                    $actionValue = $todolink;
                                    break;
                                case "artconf":
                                    $actionValue = $tmp_artconf;
                                    break;
                                case "online":
                                    $actionValue = $onlinelink;
                                    break;
                                case "locked":
                                    $actionValue = $lockedlink;
                                    break;
                                case "duplicate":
                                    $actionValue = $duplicatelink;
                                    break;
                                case "delete":
                                    $actionValue = $deletelink;
                                    break;
                                case "usetime":
                                    $actionValue = '';
                                    break;
                                default:
                                    // Ask chain about the entry
                                    $_cecIterator = $_cecRegistry->getIterator("Contenido.ArticleList.RenderAction");
                                    $contents = array();
                                    if ($_cecIterator->count() > 0) {
                                        while ($chainEntry = $_cecIterator->next()) {
                                            $contents[] = $chainEntry->execute($idcat, $idart, $idartlang, $actionItem);
                                        }
                                    }
                                    $actionValue = implode('', $contents);
                                    break;
                            }

                            $actions[] = $actionValue;
                        }

                        if ($tmp_sync != '') {
                            $actions[] = $tmp_sync;
                        }

                        // add properties button
                        if ($tmp_sync != '') {
                            $actions[] = '<a id="properties" href="main.php?area=con_editart&action=con_edit&frame=4&idcat=' . $idcat . '&idart=' . $idart . '&contenido=' . $contenido . '">
                                <img class="vAlignMiddle tableElement" onmouseover="this.style.cursor=\'pointer\'" src="images/but_art_conf2.gif" title="' . i18n("Display properties") . '" alt="' . i18n("Display properties") . '" style="cursor: pointer;">
                            </a>';
                        }

                        $value = implode("\n", $actions);
                        break;
                    default:
                        $contents = array();
                        // Call chain to retrieve value
                        $_cecIterator = $_cecRegistry->getIterator("Contenido.ArticleList.RenderColumn");
                        if ($_cecIterator->count() > 0) {
                            $contents = array();
                            while ($chainEntry = $_cecIterator->next()) {
                                $contents[] = $chainEntry->execute($idcat, $idart, $idartlang, $listColumn);
                            }
                        }
                        $value = implode('', $contents);
                }
                $artlist[$tmp_rowid][$listColumn] = $value;
                $artlist[$tmp_rowid]['templateDescription'] = $templateDescription;
            }
            unset($oArtLang);
        }

        $headers = array();

        // keep old keys so that saved user properties still work
        $sortColumns = array(
            'title' => 1,
            'changeddate' => 2,
            'publisheddate' => 3,
            'sortorder' => 4
        );
        foreach ($listColumns as $key => $listColumn) {
            // Dirty hack to force column widths
            $width = ($key == 'title' || $listColumn == i18n('Title')) ? '100%' : '1%';
            // if it should be possible to sort by this column, add a link
            if (in_array($key, array_keys($sortColumns))) {
                $newSortmode = 'asc';
                // revert the sorting if it already has been sorted by this
                // column
                if ($sortby == $sortColumns[$key] && $sortmode == 'asc') {
                    $newSortmode = 'desc';
                }
                // add the appropriate sorting image if necessary
                if ($sortby == $sortColumns[$key]) {
                    $imageSrc = ($sortmode == 'asc') ? 'images/sort_up.gif' : 'images/sort_down.gif';
                    $sortImage = '<img src="' . $imageSrc . '">';
                } else {
                    $sortImage = '';
                }
                $sortLink = $sess->url("main.php?area=con&frame=4&idcat=$idcat&sortby=$sortColumns[$key]&sortmode=$newSortmode");
                $col = '<a href="' . $sortLink . '" class="gray">' . $listColumn . $sortImage . '</a>';
            } else {
                $col = $listColumn;
            }
            $headers[] = '<th width="' . $width . '" nowrap="nowrap">' . $col . '</th>';
        }

        $tpl->set('s', 'HEADERS', implode("\n", $headers));

        if ($elemperpage > 0 && $iArticleCount > 0 && $iArticleCount > $elemperpage) {
            for ($i = 1; $i <= ceil($iArticleCount / $elemperpage); $i++) {
                $iNext = ($i - 1) * $elemperpage;
                if ($sBrowseLinks !== '') {
                    $sBrowseLinks .= '&nbsp;';
                }
                if ($next == $iNext) {
                    $sBrowseLinks .= $i . "\n"; // I'm on the current page, no
                                                    // link
                } else {
                    $tmp_alink = $sess->url("main.php?area=con&frame=$frame&idcat=$idcat&next=$iNext");
                    $sBrowseLinks .= '<a href="' . $tmp_alink . '">' . $i . '</a>' . "\n";
                }
            }
            $tpl->set('s', 'NEXT', $next);
            $tpl->set('s', 'BROWSE', sprintf(i18n("Go to page: %s"), $sBrowseLinks));
        } else {
            $tpl->set('s', 'NEXT', "0");
            $tpl->set('s', 'BROWSE', '&nbsp;');
        }
        $tpl->set('s', 'CLICK_ROW_NOTIFICATION', i18n("Click on a row to select an article for editing"));

        // construct the bulk editing functions
        $bulkEditingFunctions = '';
        if ($articlesOffline > 0 && ($perm->have_perm_area_action("con", "con_makeonline") || $perm->have_perm_area_action_item("con", "con_makeonline", $idcat))) {
            $bulkEditingFunctions .= createBulkEditingFunction('con_makeonline', 'images/online.gif', i18n('Set articles online'));
        }
        if ($articlesOnline > 0 && ($perm->have_perm_area_action("con", "con_makeonline") || $perm->have_perm_area_action_item("con", "con_makeonline", $idcat))) {
            $bulkEditingFunctions .= createBulkEditingFunction('con_makeonline invert', 'images/offline.gif', i18n('Set articles offline'));
        }
        if ($articlesUnlocked > 0 && ($perm->have_perm_area_action("con", "con_lock") || $perm->have_perm_area_action_item("con", "con_lock", $idcat))) {
            $bulkEditingFunctions .= createBulkEditingFunction('con_lock', 'images/article_unlocked.gif', i18n('Freeze articles'));
        }
        if ($articlesLocked > 0 && ($perm->have_perm_area_action("con", "con_lock") || $perm->have_perm_area_action_item("con", "con_lock", $idcat))) {
            $bulkEditingFunctions .= createBulkEditingFunction('con_lock invert', 'images/article_locked.gif', i18n('Unfreeze articles'));
        }
        if ($articlesToSync > 0 && ($perm->have_perm_area_action("con", "con_syncarticle") || $perm->have_perm_area_action_item("con", "con_syncarticle", $idcat))) {
            $bulkEditingFunctions .= createBulkEditingFunction('con_syncarticle', 'images/but_sync_art.gif', i18n('Copy article to the current language'));
        }
        if ($articlesToRemove > 0 && ($perm->have_perm_area_action("con", "con_deleteart") || $perm->have_perm_area_action_item("con", "con_deleteart", $idcat))) {
            $bulkEditingFunctions .= createBulkEditingFunction('con_deleteart', 'images/delete.gif', i18n('Delete articles'), 'Con.showConfirmation("' . i18n('Are you sure to delete the selected articles') . '", deleteArticles)');
        }
        if ($articlesToEdit > 0 && ($perm->have_perm_area_action("con_editart", "con_edit") || $perm->have_perm_area_action_item("con_editart", "con_edit", $idcat))) {
        	$bulkEditingFunctions .= createBulkEditingFunction('con_inlineeditart', 'images/editieren.gif', i18n('Edit articles'));
        }

        if ($bulkEditingFunctions == "") {
            $bulkEditingFunctions = i18n("Your permissions do not allow any actions here");
        }

        $tpl->set('s', 'BULK_EDITING_FUNCTIONS', $bulkEditingFunctions);
		$tpl->set('s', 'SAVE_ARTICLES', i18n('Save articles'));

        if (count($artlist) > 0) {
            foreach ($artlist as $key2 => $artitem) {

                $cells = array();

                foreach ($listColumns as $key => $listColumn) {
                    // Description for hover effect
                    if ($key == 'template') {
                        $templateDescription = $artitem['templateDescription'];
                        $descString = '<b>' . $artitem[$key] . '</b>';

                        $sTemplatename = cString::trimHard($artitem[$key], 20);
                        if (cString::getStringLength($artitem[$key]) > 20) {
                            $cells[] = '<td nowrap="nowrap" class="bordercell tooltip" title="' . $descString . '">' . $sTemplatename . '</td>';
                        } else {
                            $cells[] = '<td nowrap="nowrap" class="bordercell">' . $artitem[$key] . '</td>';
                        }
                    } else {
                        $cells[] = '<td nowrap="nowrap" class="bordercell">' . $artitem[$key] . '</td>';
                    }
                }
                $tpl->set('d', 'CELLS', implode("\n", $cells));

                if (isset($colitem[$key2]) && $colitem[$key2] == 'con_sync') {
                    $tpl->set('d', 'CSS_CLASS', 'class="con_sync row_mark"');
                } else {
                    $tpl->set('d', 'CSS_CLASS', 'class="row_mark"');
                }

                $tpl->set('d', 'ROWID', $key2);
                $tpl->next();
            }
        } else {
            $emptyCell = '<td nowrap="nowrap" class="bordercell" colspan="' . count($listColumns) . '">' . i18n("No articles found") . '</td>';
            $tpl->set('d', 'CELLS', $emptyCell);
            $tpl->set('d', 'CSS_CLASS', '');
            $tpl->set('d', 'ROWID', '');
        }

        // Elements per Page select
        $aElemPerPage = array(
            0 => i18n("All"),
            10 => "10",
            25 => "25",
            50 => "50",
            75 => "75",
            100 => "100"
        );

        $tpl2 = new cTemplate();
        $tpl2->set('s', 'NAME', 'sort');
        $tpl2->set('s', 'CLASS', 'text_medium');
        $tpl2->set('s', 'OPTIONS', 'onchange="changeElemPerPage(this)"');

        foreach ($aElemPerPage as $key => $value) {
            $selected = ($elemperpage == $key) ? 'selected="selected"' : '';
            $tpl2->set('d', 'VALUE', $key);
            $tpl2->set('d', 'CAPTION', $value);
            $tpl2->set('d', 'SELECTED', $selected);
            $tpl2->next();
        }

        $select = (!$no_article) ? $tpl2->generate($cfg["path"]["templates"] . $cfg['templates']['generic_select'], true) : '&nbsp;';
        $caption = (!$no_article) ? i18n("Items per page:") : '&nbsp;';

        $tpl->set('s', 'ELEMPERPAGECAPTION', $caption);
        $tpl->set('s', 'ELEMPERPAGE', $select);

        $tpl->set('s', 'IDCAT', $idcat);
        $tpl->set('s', 'SOURCELANGUAGE', $idlang);

        // Extract Category and Catcfg
        $sql = "SELECT
                    b.name AS name,
                    d.idtpl AS idtpl
                FROM
                    (" . $cfg["tab"]["cat"] . " AS a,
                    " . $cfg["tab"]["cat_lang"] . " AS b,
                    " . $cfg["tab"]["tpl_conf"] . " AS c)
                LEFT JOIN
                    " . $cfg["tab"]["tpl"] . " AS d
                ON
                    d.idtpl = c.idtpl
                WHERE
                    a.idclient = " . cSecurity::toInteger($client) . " AND
                    a.idcat    = " . cSecurity::toInteger($idcat) . " AND
                    b.idlang   = " . cSecurity::toInteger($lang) . " AND
                    b.idcat    = a.idcat AND
                    c.idtplcfg = b.idtplcfg";

        $db->query($sql);

        if ($db->nextRecord()) {
            // $foreignlang = false;
            // conCreateLocationString($idcat, "&nbsp;/&nbsp;", $cat_name);
        }

        $cat_idtpl = $db->f("idtpl");

        $cat_name = renderBackendBreadcrumb($syncoptions, false, true);

        // Hinweis wenn kein Artikel gefunden wurde
        if ($no_article) {
            $tpl->set('d', "START", '&nbsp;');
            $tpl->set('d', "ARTICLE", i18n("No articles found"));
            $tpl->set('d', "PUBLISHED", '&nbsp;');
            $tpl->set('d', "LASTMODIFIED", '&nbsp;');
            $tpl->set('d', "ARTCONF", '&nbsp;');
            $tpl->set('d', "TPLNAME", '&nbsp;');
            $tpl->set('d', "LOCKED", '&nbsp;');
            $tpl->set('d', "DUPLICATE", '&nbsp;');
            $tpl->set('d', "TPLCONF", '&nbsp;');
            $tpl->set('d', "ONLINE", '&nbsp;');
            $tpl->set('d', "DELETE", '&nbsp;');
            $tpl->set('d', "USETIME", '&nbsp;');
            $tpl->set('d', "TODO", '&nbsp;');
            $tpl->set('d', "SORTKEY", '&nbsp;');

            $tpl->next();
        }

        // Kategorie anzeigen und Konfigurieren button
        /*
         * JL 23.06.03 Check right from "Content" instead of "Category" if
         * ($perm->have_perm_area_action("str_tplcfg", "str_tplcfg") ||
         * $perm->have_perm_area_action_item("str_tplcfg", "str_tplcfg",
         * $lidcat))
         */

        if (($perm->have_perm_area_action_item('con', 'con_tplcfg_edit', $idcat)
            || $perm->have_perm_area_action('con', 'con_tplcfg_edit'))
            && (isset($foreignlang) && $foreignlang == false))
        {
            if (0 != $idcat) {
                $tpl->set('s', 'CATEGORY', $cat_name);
                $tpl->set('s', 'CATEGORY_CONF', isset($tmp_img) ? $tmp_img : '');
                $tpl->set('s', 'CATEGORY_LINK', $tmp_link);
            } else {
                $tpl->set('s', 'CATEGORY', $cat_name);
                $tpl->set('s', 'CATEGORY_CONF', '&nbsp;');
                $tpl->set('s', 'CATEGORY_LINK', '&nbsp;');
            }
        } else {
            $tpl->set('s', 'CATEGORY', $cat_name);
            $tpl->set('s', 'CATEGORY_CONF', '&nbsp;');
            $tpl->set('s', 'CATEGORY_LINK', '&nbsp;');
        }

        // SELF_URL (Variable f�r das javascript);
        $tpl->set('s', 'SELF_URL', $sess->url("main.php?area=con&frame=4&idcat=$idcat"));

        // Categories without start article
        $warningBox = '';
        if (strHasStartArticle($idcat, $lang) === false) {
            $warningBox = $notification->returnNotification('warning', i18n('This category does not have a configured start article.'));
        }

        // New Article link
        if (($perm->have_perm_area_action('con_editart', 'con_newart')
            || $perm->have_perm_area_action_item('con_editart', 'con_newart', $idcat))
            && (isset($foreignlang) && $foreignlang == false))
        {
            // check if category has an assigned template
            if ($idcat != 0 && $cat_idtpl != 0) {
                $tpl->set('s', 'NEWARTICLE_TEXT', '<a id="newArtTxt" href="' . $sess->url("main.php?area=con_editart&frame=$frame&action=con_newart&idcat=$idcat") . '">' . i18n("Create new article") . '</a>');
                $tpl->set('s', 'NEWARTICLE_IMG', '<a id="newArtImg" href="' . $sess->url("main.php?area=con_editart&frame=$frame&action=con_newart&idcat=$idcat") . '" title="' . i18n("Create new article") . '"><img src="images/but_art_new.gif" border="0" alt="' . i18n("Create new article") . '"></a>');
                $tpl->set('s', 'CATTEMPLATE', $warningBox);
            } else {
                // category is either not in sync or does not exist
                // check if category does not exist for current language if syncoptions is turned on to find out if current category is unsynchronized
                // TODO cApiArticleLanguage($idcat) cannot be correct!
                $oArtLang = new cApiArticleLanguage($idcat);
                if (0 < $syncoptions && false === $oArtLang->isLoaded()) {
                    $notification_text = $notification->returnNotification("error", i18n("Creation of articles is only possible if the category has is synchronized."));
                } else {
                    $notification_text = $notification->returnNotification("error", i18n("Creation of articles is only possible if the category has a assigned template."));
                }
                $tpl->set('s', 'CATTEMPLATE', $notification_text);
                $tpl->set('s', 'NEWARTICLE_TEXT', '&nbsp;');
                $tpl->set('s', 'NEWARTICLE_IMG', '&nbsp;');
            }
        } else {
            $tpl->set('s', 'NEWARTICLE_TEXT', '&nbsp;');
            $tpl->set('s', 'NEWARTICLE_IMG', '&nbsp;');
            $tpl->set('s', 'CATTEMPLATE', $warningBox);
        }

        $str = '';

        $tpl->set('s', 'NOTIFICATION', $str);
        // modified by fulai.zhang 17.07.2012
        // display if there are articles
        if ($no_article) {
            $tpl->set('s', 'NOARTICLE_CSS', "display: none;");
            $tpl->set('s', 'NOARTICLE_JS', 'true');
        } else {
            $tpl->set('s', 'NOARTICLE_CSS', "");
            $tpl->set('s', 'NOARTICLE_JS', 'false');
        }

        // breadcrumb onclick
        $tpl->set('s', 'IDTPL', $idtpl ? $idtpl : $cat_idtpl);
        $tpl->set('s', 'SYNCOPTIONS', $syncoptions);
        $tpl->set('s', 'DISPLAY_MENU', 1);

        // Generate template
        $tpl->generate($cfg['path']['templates'] . $cfg['templates']['con_art_overview']);
    } else {
        $notification->displayNotification("error", i18n("Permission denied"));
    }
} else {
    $tpl->reset();
    $tpl->set('s', 'CONTENTS', '');
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['blank']);
}

/**
 * Creates HTML code for the bulk editing functions in the article overview.
 *
 * @param string $class
 *         the class for the link
 * @param string $imageSrc
 *         the path to the image
 * @param string $alt
 *         the alt tag for the image
 * @param string $onclick [optional]
 *         the onlick attribute for the link
 * @return string
 *         rendered HTML code
 */
function createBulkEditingFunction($class, $imageSrc, $alt, $onclick = '') {
    $function = new cHTMLLink();
    $function->setClass($class);
    if ($onclick !== '') {
        $function->setEvent('click', $onclick);
    }
    $image = new cHTMLImage($imageSrc);
    $image->setAlt($alt);
    $function->setContent($image);

    return $function->render();
}
