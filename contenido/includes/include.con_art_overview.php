<?php

/**
 * This file contains the backend page for displaying articles of a category.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $action, $perm, $duplicate, $idart, $sourcelanguage, $_cecRegistry, $cfg, $currentuser, $db;
global $tpl, $sess, $auth, $contenido, $frame, $idtpl, $notification;

cInclude('includes', 'functions.tpl.php');
cInclude('includes', 'functions.str.php');
cInclude('includes', 'functions.pathresolver.php');

$db2 = cRegistry::getDb();
$client = cSecurity::toInteger(cRegistry::getClientId());
$lang = cSecurity::toInteger(cRegistry::getLanguageId());

$idcat = cSecurity::toInteger($_REQUEST['idcat'] ?? '-1');
$next = (isset($_REQUEST['next']) && is_numeric($_REQUEST['next']) && $_REQUEST['next'] > 0) ? $_REQUEST['next'] : 0;

$dateformat = getEffectiveSetting('dateformat', 'date', 'Y-m-d');
$templateDescription = '';

if (!isset($syncfrom)) {
    $syncfrom = -1;
}

// TODO What was the purpose of this variable?
$foreignlang = false;

$syncoptions = $syncfrom;
// CON-1752
// init duplicate counter in session
if (!isset($_SESSION['count_duplicate'])) {
    $_SESSION['count_duplicate'] = 0;
}

// New Article Selected: Unset Selected Article Id
global $selectedArticleId;
$selectedArticleId = NULL;

$articleOverviewHelper = new cArticleOverviewHelper($db, $auth, $perm, [], $idcat, $lang, $client);


if ($action == 'con_duplicate' && $articleOverviewHelper->hasArticleDuplicatePermission()) {
    $count = (int)$_SESSION['count_duplicate'];

    // check if duplicate action was called from click or from back button
    if ($_GET['count_duplicate'] < $count) {
    } else {
        // perfom action only when duplicate action is called from link
        $newidartlang = conCopyArticle($duplicate, $idcat);
        $count++;
        $_SESSION['count_duplicate'] = $count;
    }
}

if ($action == 'con_syncarticle' && $articleOverviewHelper->hasArticleContentSyncPermission()) {
    if (!empty($_POST['idarts'])) {
        $idarts = json_decode($_POST['idarts'], true);
    } else {
        $idarts = [
            $idart
        ];
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
$listColumns = [
    "mark" => i18n("Mark"),
    "start" => i18n("Article"),
    "title" => i18n("Title"),
    "changeddate" => i18n("Changed"),
    "publisheddate" => i18n("Published"),
    "sortorder" => i18n("Sort order"),
    "template" => i18n("Template"),
    "actions" => i18n("Actions")
];

// Which actions to display?
$actionList = [
    "online",
    "duplicate",
    "locked",
    "todo",
    "delete",
    "usetime"
];

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

    if ((($idcat == 0 || $perm->have_perm_area_action('con')) && $perm->have_perm_item('str', $idcat))
        || $perm->have_perm_area_action('con', 'con_makestart') || $perm->have_perm_area_action('con', 'con_makeonline')
        || $perm->have_perm_area_action('con', 'con_deleteart') || $perm->have_perm_area_action('con', 'con_tplcfg_edit')
        || $perm->have_perm_area_action('con', 'con_lock') || $perm->have_perm_area_action('con', 'con_makecatonline')
        || $perm->have_perm_area_action('con', 'con_changetemplate') || $perm->have_perm_area_action('con_editcontent', 'con_editart')
        || $perm->have_perm_area_action('con_editart', 'con_edit') || $perm->have_perm_area_action('con_editart', 'con_newart')
        || $perm->have_perm_area_action('con_editart', 'con_saveart') || $perm->have_perm_area_action('con_tplcfg', 'con_tplcfg_edit')
        || $perm->have_perm_area_action_item('con', 'con_makestart', $idcat) || $perm->have_perm_area_action_item('con', 'con_makeonline', $idcat)
        || $perm->have_perm_area_action_item('con', 'con_deleteart', $idcat) || $perm->have_perm_area_action_item('con', 'con_tplcfg_edit', $idcat)
        || $perm->have_perm_area_action_item('con', 'con_lock', $idcat) || $perm->have_perm_area_action_item('con', 'con_makecatonline', $idcat)
        || $perm->have_perm_area_action_item('con', 'con_changetemplate', $idcat) || $perm->have_perm_area_action_item('con_editcontent', 'con_editart', $idcat)
        || $perm->have_perm_area_action_item('con_editart', 'con_edit', $idcat) || $perm->have_perm_area_action_item('con_editart', 'con_newart', $idcat)
        || $perm->have_perm_area_action_item('con_tplcfg', 'con_tplcfg_edit', $idcat) || $perm->have_perm_area_action_item('con_editart', 'con_saveart', $idcat)) {

        // SQL template to get number of articles in category for current client and language
        $articleCountSql = "SELECT
                        COUNT(*) AS article_count
                     FROM
                        " . cRegistry::getDbTableName('art_lang') . " AS a,
                        " . cRegistry::getDbTableName('art') . " AS b,
                        " . cRegistry::getDbTableName('cat_art') . " AS c
                     WHERE
                        (a.idlang   = " . cSecurity::toInteger($lang) . " {SYNCOPTIONS}) AND
                        a.idart     = b.idart AND
                        b.idclient  = " . cSecurity::toInteger($client) . " AND
                        b.idart     = c.idart AND
                        c.idcat     = " . cSecurity::toInteger($idcat);

        // Get number of articles in category for current client and language
        // Remove the {SYNCOPTIONS} placeholder, we need this below again!
        $sql = str_replace('{SYNCOPTIONS}', '', $articleCountSql);
        $db->query($sql);
        $db->nextRecord();
        $articlesInSelectedLanguage = cSecurity::toInteger($db->f('article_count'));

        // Sortby and sortmode
        $sortby = $currentuser->getUserProperty("system", "sortorder-idlang-$lang-idcat-$idcat");
        $sortmode = $currentuser->getUserProperty("system", "sortmode-idlang-$lang-idcat-$idcat");

        // Main SQL statement template
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
                    " . cRegistry::getDbTableName('art_lang') . " AS a,
                    " . cRegistry::getDbTableName('art') . " AS b,
                    " . cRegistry::getDbTableName('cat_art') . " AS c
                 WHERE
                    (a.idlang   = " . $lang . " {SYNCOPTIONS}) AND
                    a.idart     = b.idart AND
                    b.idclient  = " . $client . " AND
                    b.idart     = c.idart AND
                    c.idcat     = " . $idcat;

        // Simple SQL statement to get the number of articles
        // Only with activated synchonization mode
        if ($syncoptions != -1) {
            $sql = str_replace("{SYNCOPTIONS}", "OR a.idlang = '" . $syncoptions . "'", $sql);

            if ($elemperpage > 1) {
                $sqlCount = str_replace("{SYNCOPTIONS}", "OR a.idlang = '" . $syncoptions . "'", $articleCountSql);
                $db->query($sqlCount);
                $db->nextRecord();
                $iArticleCount = $db->f("article_count");
            }
        } else {
            $sql = str_replace("{SYNCOPTIONS}", '', $sql);
            $iArticleCount = $articlesInSelectedLanguage;
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
                $add = $iArticleCount - $articlesInSelectedLanguage;
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

        $aArticles = [];

        while ($db->nextRecord()) {
            $sItem = "k" . $db->f("idart");

            if ($db->f("idlang") == $lang || !array_key_exists($sItem, $aArticles)) {
                $aArticles[$sItem]["idart"] = cSecurity::toInteger($db->f("idart"));
                $aArticles[$sItem]["idlang"] = cSecurity::toInteger($db->f("idlang"));
                $aArticles[$sItem]["idartlang"] = cSecurity::toInteger($db->f("idartlang"));
                $aArticles[$sItem]["title"] = cSecurity::unFilter($db->f("title"));
                $aArticles[$sItem]["is_start"] = isStartArticle($db->f("idartlang"), $idcat, $lang);
                $aArticles[$sItem]["idcatart"] = cSecurity::toInteger($db->f("idcatart"));
                $aArticles[$sItem]["idtplcfg"] = cSecurity::toInteger($db->f("idtplcfg"));
                $aArticles[$sItem]["published"] = $db->f("published");
                $aArticles[$sItem]["online"] = cSecurity::toInteger($db->f("online"));
                $aArticles[$sItem]["created"] = $db->f("created");
                $aArticles[$sItem]["idcat"] = cSecurity::toInteger($db->f("idcat"));
                $aArticles[$sItem]["lastmodified"] = $db->f("lastmodified");
                $aArticles[$sItem]["timemgmt"] = $db->f("timemgmt");
                $aArticles[$sItem]["datestart"] = $db->f("datestart");
                $aArticles[$sItem]["dateend"] = $db->f("dateend");
                $aArticles[$sItem]["artsort"] = $db->f("artsort");
                $aArticles[$sItem]["locked"] = cSecurity::toInteger($db->f("locked"));
                $aArticles[$sItem]["redirect"] = $db->f("redirect");
            }
        }

        $artlist = [];
        $colitem = [];
        $articlesOnline = 0;
        $articlesOffline = 0;
        $articlesLocked = 0;
        $articlesUnlocked = 0;
        $articlesToSync = 0;
        $articlesToRemove = 0;
        $articlesToEdit = 0;

        // NOTE: Collect redundant function calls, repetitive and computationally
        //       intensive tasks before the article list loop!

        // CON-2137 check admin permission
        $isAdmin = cPermission::checkAdminPermission($auth->getPerms());

        $articleOverviewHelper->setArticles($aArticles);

        $cecIteratorRenderAction = $_cecRegistry->getIterator('Contenido.ArticleList.RenderAction');
        $cecIteratorRenderColumn = $_cecRegistry->getIterator('Contenido.ArticleList.RenderColumn');

        $lngDisplayProperties = i18n("Display properties");
        $lngNotYetPublished = i18n("not yet published");
        $lngDeleteArticle = i18n("Delete article");
        $lngArticleWithTimeControlOnline = i18n("Article with time control online");
        $lngArticleWithTimeControlOffline = i18n("Article with time control offline");
        $lngUnfreezeArticle = i18n("Unfreeze article");
        $lngFreezeArticle = i18n("Freeze article");
        $lngArticleIsFrozen = i18n("Article is frozen");
        $lngArticleIsNotFrozen = i18n("Article is not frozen");
        $lngArticleProperties = i18n("Article properties");
        $lngCopyArticleToTheCurrentLanguage = i18n("Copy article to the current language");
        $lngFlagAsStartArticle = i18n("Flag as start article");
        $lngFlagAsNormalArticle = i18n("Flag as normal article");
        $lngDuplicateArticle = i18n("Duplicate article");
        $lngMakeOffline = i18n("Make offline");
        $lngArticleIsOnline = i18n("Article is online");
        $lngMakeOnline = i18n("Make online");
        $lngArticleIsOffline = i18n("Article is offline");
        $lngNone = i18n("None");
        $lngStartArticle = i18n("Start article");
        $lngNormalArticle = i18n("Normal article");
        $lngReminderForArticleX = i18n("Reminder for article '%s'");
        $lngReminderForArticleXCategoryX = i18n("Reminder for article '%s'\nCategory: %s");
        $lngAreYouSureToDeleteTheFollowingArticleX = i18n("Are you sure to delete the following article:<br><br><b>%s</b>");

        foreach ($aArticles as $sart) {
            $idart = $sart["idart"];
            $idlang = $sart["idlang"];

            $idtplcfg = $sart["idtplcfg"];
            $idartlang = $sart["idartlang"];
            $lidcat = $sart["idcat"];
            $idcatlang = 0;
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

            $published = ($published != '0000-00-00 00:00:00') ? date($dateformat, strtotime($published)) : $lngNotYetPublished;
            $created = date($dateformat, strtotime($created));
            $alttitle = "idart" . '&#58; ' . $idart . ' ' . "idcatart" . '&#58; ' . $idcatart . ' ' . "idartlang" . '&#58; ' . $idartlang;

            $articlesToEdit++;

            if ($idlang != $lang) {
                $articlesToSync++;
            } else {
                if ($online === 1) {
                    $articlesOnline++;
                } else {
                    $articlesOffline++;
                }
                if ($locked === 1) {
                    $articlesLocked++;
                } else {
                    $articlesUnlocked++;
                }
                $articlesToRemove++;
            }

            // Is article in use by other user?
            $inUse = $articleOverviewHelper->isArticleInUse($idartlang);
            if ($inUse) {
                $inUseUserObj = $articleOverviewHelper->getArticleInUseUser($idartlang);
                $inUseUser = $inUseUserObj ? $inUseUserObj->getField("username") : "";
                $inUseUserRealName = $inUseUserObj ? $inUseUserObj->getField("realname") : "";
                $title = $title . " (" . i18n("Article is in use") . ")";
                $alttitle = sprintf(i18n("Article in use by %s (%s)"), $inUseUser, $inUseUserRealName) . " " . $alttitle;
            }

            // Id of the row, stores information about the article and category
            $tmp_rowid = $idart . "-" . $idartlang . "-" . $lidcat . "-" . $idcatlang . "-" . $idcatart . "-" . $idlang;
            $tpl->set('d', 'ROWID', $tmp_rowid);

            if ($idlang != $lang) {
                $colitem[$tmp_rowid] = 'con_sync';
            }

            // Article Title
            if ($articleOverviewHelper->hasArticleEditContentPermission()) {
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
                $starttimestamp = strtotime($datestart);
                $endtimestamp = strtotime($dateend);
                $nowtimestamp = strtotime($articleOverviewHelper->getDatabaseTime());

                if (($nowtimestamp < $endtimestamp) && ($nowtimestamp > $starttimestamp)) {
                    $usetime = cHTMLImage::img('images/but_time_2.gif', $lngArticleWithTimeControlOnline, ['class' => 'con_img_button_off mgl3']);
                } else {
                    $usetime = cHTMLImage::img('images/but_time_1.gif', $lngArticleWithTimeControlOffline, ['class' => 'con_img_button_off mgl3']);
                }
            } else {
                $usetime = '';
            }

            // Article Title
            if ($articleOverviewHelper->hasArticleLockPermission() && $inUse === false) {
                if ($locked === 1) {
                    $lockimg = 'images/article_locked.gif';
                    $lockalt = $lngUnfreezeArticle;
                } else {
                    $lockimg = 'images/article_unlocked.gif';
                    $lockalt = $lngFreezeArticle;
                }
                $tmp_lock = '<a class="con_img_button mgl3" href="' . $sess->url("main.php?area=con&idcat=$idcat&action=con_lock&frame=4&idart=$idart&next=$next") . '" title="' . $lockalt . '"><img src="' . $lockimg . '" title="' . $lockalt . '" alt="' . $lockalt . '"></a>';
            } else {
                if ($locked === 1) {
                    $lockimg = 'images/article_locked.gif';
                    $lockalt = $lngArticleIsFrozen;
                } else {
                    $lockimg = 'images/article_unlocked.gif';
                    $lockalt = $lngArticleIsNotFrozen;
                }
                $tmp_lock = cHTMLImage::img($lockimg, $lockalt, ['class' => 'con_img_button_off mgl3']);
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
            if ($articleOverviewHelper->hasArticleEditPermission()) {
                $tmp_artconf = '<a class="con_img_button mgl3" href="' . $sess->url("main.php?area=con_editart&action=con_edit&frame=4&idart=$idart&idcat=$idcat") . '" title="' . $lngArticleProperties . '"><img src="' . $cfg['path']['images'] . 'but_art_conf2.gif" alt="' . $lngArticleProperties . '" title="' . $lngArticleProperties . '"></a>';
            } else {
                $tmp_artconf = '';
            }

            $tmp_sync = '';
            if ($idlang != $lang) {
                $sql = "SELECT idcatlang FROM " . cRegistry::getDbTableName('cat_lang') . " WHERE idcat='" . cSecurity::toInteger($idcat) . "' AND idlang='" . cSecurity::toInteger($lang) . "'";
                $db->query($sql);

                if ($db->nextRecord()) {
                    $tmp_sync = '<a class="con_img_button mgl3" href="' . $sess->url("main.php?area=con&action=con_syncarticle&idart=$idart&sourcelanguage=$idlang&frame=4&idcat=$idcat&next=$next") . '" title="' . $lngCopyArticleToTheCurrentLanguage . '"><img src="' . $cfg['path']['images'] . 'but_sync_art.gif" alt="' . $lngCopyArticleToTheCurrentLanguage . '" title="' . $lngCopyArticleToTheCurrentLanguage . '"></a>';
                } else {
                    $tmp_sync = '';
                    $articlesToSync--;
                    $articlesToRemove--;
                }
            }

            // Article Template
            $articleTemplateInfo = $articleOverviewHelper->getArticleTemplateInfo($idartlang);
            $a_tplname = $articleTemplateInfo['name'] ?? '';
            $a_idtpl = $articleTemplateInfo['idtpl'] ?? '';
            $templateDescription = $articleTemplateInfo['description'] ?? '';

            // Uses Category Template
            if (0 == $idtplcfg) {
                $categoryTemplateInfo = $articleOverviewHelper->getCategoryTemplateInfos();
                $a_tplname = ($categoryTemplateInfo['name'] ?? '') ? '<i>' . $categoryTemplateInfo['name'] . '</i>' : "--- " . $lngNone . " ---";
            }

            // Make Startarticle button
            $imgsrc = "isstart";

            if ($is_start === false) {
                $imgsrc .= '0';
            } else {
                $imgsrc .= '1';
            }
            if ($articleOverviewHelper->isArticleInMultipleUse($idart)) {
                $imgsrc .= 'm';
            }
            if ((int)$redirect == 1) {
                $imgsrc .= 'r';
            }

            $imgsrc .= '.gif';

            if ($idlang == $lang && ($articleOverviewHelper->hasArticleMakeStartPermission()) && $idcat != 0 && ($locked === 0 || $isAdmin)) {
                if ($is_start === false) {
                    $tmp_link = '<a class="con_img_button mgl3" href="' . $sess->url("main.php?area=con&amp;idcat=$idcat&action=con_makestart&idcatart=$idcatart&frame=4&is_start=1&next=$next") . '" title="' . $lngFlagAsStartArticle . '"><img src="images/' . $imgsrc . '" title="' . $lngFlagAsStartArticle . '" alt="' . $lngFlagAsStartArticle . '"></a>';
                } else {
                    $tmp_link = '<a class="con_img_button mgl3" href="' . $sess->url("main.php?area=con&amp;idcat=$idcat&action=con_makestart&idcatart=$idcatart&frame=4&is_start=0&next=$next") . '" title="' . $lngFlagAsNormalArticle . '"><img src="images/' . $imgsrc . '" title="' . $lngFlagAsNormalArticle . '" alt="' . $lngFlagAsNormalArticle . '"></a>';
                }
            } else {
                if ($is_start === true) {
                    $sTitle = $lngStartArticle;
                } else {
                    $sTitle = $lngNormalArticle;
                }

                $tmp_img = '<img class="con_img_button_off mgl3" src="images/' . $imgsrc . '" title="' . $sTitle . '" alt="' . $sTitle . '">';

                $tmp_link = $tmp_img;
            }

            $tmp_start = $tmp_link;

            // Make copy button
            if ($articleOverviewHelper->hasArticleDuplicatePermission() && $idcat != 0 && ($locked === 0 || $isAdmin)) {
                $imgsrc = "but_copy.gif";
                // add count_duplicate param to identify if the duplicate action
                // is called from click or back button.
                $tmp_link = '<a class="con_img_button mgl3" href="' . $sess->url("main.php?area=con&idcat=$idcat&action=con_duplicate&duplicate=$idart&frame=4&next=$next") . "&count_duplicate=" . $_SESSION['count_duplicate'] . '" title="' . $lngDuplicateArticle . '"><img src="images/' . $imgsrc . '" title="' . $lngDuplicateArticle . '" alt="' . $lngDuplicateArticle . '"></a>';
            } else {
                $tmp_link = '';
            }

            if ($idlang != $lang) {
                $duplicatelink = '';
            } else {
                $duplicatelink = $tmp_link;
            }

            // Article reminder message
            $subject = urlencode(sprintf($lngReminderForArticleX, $title));
            $categoryPath = $articleOverviewHelper->getCategoryBreadcrumb();
            $message = urlencode(sprintf($lngReminderForArticleXCategoryX, $title, $categoryPath));

            // Make todo link
            $todolink = new TODOLink("idart", $idart, $subject, $message);

            // Make On-/Offline button
            if ($online) {
                if ($articleOverviewHelper->hasArticleMakeOnlinePermission() && ($idcat != 0) && ($locked === 0 || $isAdmin)) {
                    $tmp_online = '<a class="con_img_button mgl3" href="' . $sess->url("main.php?area=con&idcat=$idcat&action=con_makeonline&frame=4&idart=$idart&next=$next") . '" title="' . $lngMakeOffline . '"><img src="images/online.gif" title="' . $lngMakeOffline . '" alt="' . $lngMakeOffline . '"></a>';
                } else {
                    $tmp_online = '<img class="con_img_button mgl3" src="images/online.gif" title="' . $lngArticleIsOnline . '" alt="' . $lngArticleIsOnline . '">';
                }
            } else {
                if ($articleOverviewHelper->hasArticleMakeOnlinePermission() && ($idcat != 0) && ($locked === 0 || $isAdmin)) {
                    $tmp_online = '<a class="con_img_button mgl3" href="' . $sess->url("main.php?area=con&idcat=$idcat&action=con_makeonline&frame=4&idart=$idart&next=$next") . '" title="' . $lngMakeOnline . '"><img src="images/offline.gif" title="' . $lngMakeOnline . '" alt="' . $lngMakeOnline . '"></a>';
                } else {
                    $tmp_online = '<img class="con_img_button_off mgl3" src="images/offline.gif" title="' . $lngArticleIsOffline . '" alt="' . $lngArticleIsOffline . '">';
                }
            }

            if ($idlang != $lang) {
                $onlinelink = '';
            } else {
                $onlinelink = $tmp_online;
            }

            // Delete button
            if ($articleOverviewHelper->hasArticleDeletePermission() && $inUse === false && ($locked === 0 || $isAdmin)) {
                $tmp_title = $title;
                if (cString::getStringLength($tmp_title) > 30) {
                    $tmp_title = cString::getPartOfString($tmp_title, 0, 27) . "...";
                }

                $confirmString = sprintf($lngAreYouSureToDeleteTheFollowingArticleX, conHtmlSpecialChars($tmp_title));
                $tmp_del = '<a class="con_img_button mgl3" href="javascript:void(0)" onclick="Con.showConfirmation(&quot;' . $confirmString . '&quot;, function() { deleteArticle(' . $idart . ', ' . $idcat . ', ' . $next . '); });return false;" title="' . $lngDeleteArticle . '"><img src="images/delete.gif" title="' . $lngDeleteArticle . '" alt="' . $lngDeleteArticle . '"></a>';
            } else {
                $tmp_del = '';
            }

            if ($idlang != $lang) {
                $deletelink = '';
            } else {
                $deletelink = $tmp_del;
            }

            // DIRECTION
            $tpl->set('d', 'DIRECTION', 'dir="' . $articleOverviewHelper->getTextDirection() . '"');

            // Next iteration
            // Articles found
            $no_article = false;

            foreach ($listColumns as $listColumn => $ctitle) {
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
                        if ($online === 1) {
                            $value = $published;
                        } else {
                            $value = $lngNotYetPublished;
                        }
                        break;
                    case "sortorder":
                        $value = $sortkey;
                        break;
                    case "template":
                        $value = $a_tplname;
                        break;
                    case "actions":
                        $actions = [];
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
                                    $contents = [];
                                    $cecIteratorRenderAction->reset();
                                    while ($chainEntry = $cecIteratorRenderAction->next()) {
                                        $contents[] = $chainEntry->execute($idcat, $idart, $idartlang, $actionItem);
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
                            $actions[] = '<a class="con_img_button mgl3" href="main.php?area=con_editart&action=con_edit&frame=4&idcat=' . $idcat . '&idart=' . $idart . '&contenido=' . $contenido . '">
                                <img src="images/but_art_conf2.gif" title="' . $lngDisplayProperties . '" alt="' . $lngDisplayProperties . '">
                            </a>';
                        }

                        $value = implode("\n", $actions);
                        break;
                    default:
                        // Call chain to retrieve value
                        $contents = [];
                        $cecIteratorRenderColumn->reset();
                        while ($chainEntry = $_cecIterator->next()) {
                            $contents[] = $chainEntry->execute($idcat, $idart, $idartlang, $listColumn);
                        }
                        $value = implode('', $contents);
                }
                if (!is_numeric($value) && empty($value)) {
                    $value = '&nbsp;';
                }
                $artlist[$tmp_rowid][$listColumn] = $value;
                $artlist[$tmp_rowid]['templateDescription'] = $templateDescription;
            }
        }

        $headers = [];

        // keep old keys so that saved user properties still work
        $sortColumns = [
            'title' => 1,
            'changeddate' => 2,
            'publisheddate' => 3,
            'sortorder' => 4
        ];
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
            $headers[] = '<th width="' . $width . '">' . $col . '</th>';
        }

        $tpl->set('s', 'HEADERS', implode("\n", $headers));

        if ($elemperpage > 0 && $iArticleCount > 0 && $iArticleCount > $elemperpage) {
            $sBrowseLinks = '';
            for ($i = 1; $i <= ceil($iArticleCount / $elemperpage); $i++) {
                $iNext = ($i - 1) * $elemperpage;
                if (!empty($sBrowseLinks)) {
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
        if ($articlesOffline > 0 && $articleOverviewHelper->hasArticleMakeOnlinePermission()) {
            $bulkEditingFunctions .= createBulkEditingFunction('con_makeonline', 'images/online.gif', i18n('Set articles online'));
        }
        if ($articlesOnline > 0 && $articleOverviewHelper->hasArticleMakeOnlinePermission()) {
            $bulkEditingFunctions .= createBulkEditingFunction('con_makeonline invert', 'images/offline.gif', i18n('Set articles offline'));
        }
        if ($articlesUnlocked > 0 && $articleOverviewHelper->hasArticleLockPermission()) {
            $bulkEditingFunctions .= createBulkEditingFunction('con_lock', 'images/article_unlocked.gif', i18n('Freeze articles'));
        }
        if ($articlesLocked > 0 && $articleOverviewHelper->hasArticleLockPermission()) {
            $bulkEditingFunctions .= createBulkEditingFunction('con_lock invert', 'images/article_locked.gif', i18n('Unfreeze articles'));
        }
        if ($articlesToSync > 0 && $articleOverviewHelper->hasArticleContentSyncPermission()) {
            $bulkEditingFunctions .= createBulkEditingFunction('con_syncarticle', 'images/but_sync_art.gif', i18n('Copy article to the current language'));
        }
        if ($articlesToRemove > 0 && $articleOverviewHelper->hasArticleDeletePermission()) {
            $bulkEditingFunctions .= createBulkEditingFunction('con_deleteart', 'images/delete.gif', i18n('Delete articles'), 'Con.showConfirmation("' . i18n('Are you sure to delete the selected articles') . '", deleteArticles)');
        }
        if ($articlesToEdit > 0 && $articleOverviewHelper->hasArticleEditPermission()) {
            $bulkEditingFunctions .= createBulkEditingFunction('con_inlineeditart', 'images/editieren.gif', i18n('Edit articles'));
        }

        if ($bulkEditingFunctions == "") {
            $bulkEditingFunctions = i18n("Your permissions do not allow any actions here");
        }

        $tpl->set('s', 'BULK_EDITING_FUNCTIONS', $bulkEditingFunctions);
        $tpl->set('s', 'SAVE_ARTICLES', i18n('Save articles'));

        if (count($artlist) > 0) {
            foreach ($artlist as $key2 => $artitem) {
                $cells = [];

                foreach ($listColumns as $key => $listColumn) {
                    // Description for hover effect
                    if ($key == 'template') {
                        $templateDescription = $artitem['templateDescription'];
                        $descString = '<b>' . $artitem[$key] . '</b>';

                        $sTemplatename = cString::trimHard($artitem[$key], 20);
                        if (cString::getStringLength($artitem[$key]) > 20) {
                            $cells[] = '<td class="tooltip" title="' . $descString . '">' . $sTemplatename . '</td>';
                        } else {
                            $cells[] = '<td>' . $artitem[$key] . '</td>';
                        }
                    } elseif ($key == 'mark' || $key == 'start' || $key == 'sortorder') {
                        $cells[] = '<td class="text_center">' . $artitem[$key] . '</td>';
                    } else {
                        $cells[] = '<td>' . $artitem[$key] . '</td>';
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
            $emptyCell = '<td colspan="' . count($listColumns) . '">' . i18n("No articles found") . '</td>';
            $tpl->set('d', 'CELLS', $emptyCell);
            $tpl->set('d', 'CSS_CLASS', '');
            $tpl->set('d', 'ROWID', '');
        }

        // Elements per Page select
        $aElemPerPage = [
            0 => i18n("All"),
            10 => "10",
            25 => "25",
            50 => "50",
            75 => "75",
            100 => "100"
        ];

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

        $select = (!$no_article) ? $tpl2->generate($cfg['path']['templates'] . $cfg['templates']['generic_select'], true) : '&nbsp;';
        $caption = (!$no_article) ? i18n("Items per page:") : '&nbsp;';
        $sourcelanguage = $sourcelanguage ?? $syncoptions;

        $tpl->set('s', 'ELEMPERPAGECAPTION', $caption);
        $tpl->set('s', 'ELEMPERPAGE', $select);

        $tpl->set('s', 'IDCAT', $idcat);
        $tpl->set('s', 'SOURCELANGUAGE', $sourcelanguage);

        // Extract Category and Catcfg
        $sql = "SELECT
                    b.name AS name,
                    d.idtpl AS idtpl
                FROM
                    (" . cRegistry::getDbTableName('cat') . " AS a,
                    " . cRegistry::getDbTableName('cat_lang') . " AS b,
                    " . cRegistry::getDbTableName('tpl_conf') . " AS c)
                LEFT JOIN
                    " . cRegistry::getDbTableName('tpl') . " AS d
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
            $cat_idtpl = $db->f("idtpl");
        }

        $cat_name = renderBackendBreadcrumb($syncoptions, false, true);

        // Note if no item was found
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

        // Button to display and configure category
        /*
         * JL 23.06.03 Check right from "Content" instead of "Category" if
         * ($perm->have_perm_area_action("str_tplcfg", "str_tplcfg") ||
         * $perm->have_perm_area_action_item("str_tplcfg", "str_tplcfg",
         * $lidcat))
         */

        if (($perm->have_perm_area_action_item('con', 'con_tplcfg_edit', $idcat)
                || $perm->have_perm_area_action('con', 'con_tplcfg_edit')) && $foreignlang == false) {
            if (0 != $idcat) {
                $tpl->set('s', 'CATEGORY', $cat_name);
                $tpl->set('s', 'CATEGORY_CONF', $tmp_img ?? '');
                $tpl->set('s', 'CATEGORY_LINK', $tmp_link ?? '');
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
                || $perm->have_perm_area_action_item('con_editart', 'con_newart', $idcat)) && $foreignlang == false) {
            // check if category has an assigned template
            if ($idcat != 0 && $cat_idtpl != 0) {
                $link = new cHTMLLink(
                    $sess->url("main.php?area=con_editart&frame=$frame&action=con_newart&idcat=$idcat"),
                    cHTMLImage::img('images/but_art_new.gif', i18n("Create new article")) . ' ' . i18n("Create new article"),
                    'con_func_button',
                    'newArtTxt'
                );
                $link->setAlt(i18n("Create new article"));
                $tpl->set('s', 'NEWARTICLE_LINK', $link);
                $tpl->set('s', 'CATTEMPLATE', $warningBox);
            } else {
                // category is either not in sync or does not exist
                // check if category does not exist for current language if syncoptions is turned on to find out if current category is unsynchronized
                // TODO cApiArticleLanguage($idcat) cannot be correct!
                $oArtLang = new cApiArticleLanguage($idcat);
                if (0 < $syncoptions && false === $oArtLang->isLoaded()) {
                    $notification_text = $notification->returnNotification("error", i18n("Creation of articles is only possible if the category is synchronized."));
                } else {
                    $notification_text = $notification->returnNotification("error", i18n("Creation of articles is only possible if the category has a assigned template."));
                }
                $tpl->set('s', 'CATTEMPLATE', $notification_text);
                $tpl->set('s', 'NEWARTICLE_LINK', '&nbsp;');
            }
        } else {
            $tpl->set('s', 'NEWARTICLE_LINK', '&nbsp;');
            $tpl->set('s', 'CATTEMPLATE', $warningBox);
        }

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
function createBulkEditingFunction($class, $imageSrc, $alt, $onclick = '')
{
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
