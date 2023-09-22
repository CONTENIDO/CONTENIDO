<?php

/**
 * This file performs various searches on articles from backend.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Holger Librenz
 * @author     Andreas Lindner
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

global $idtpl, $properties, $tplconfig;

// CONTENIDO startup process
include_once('./includes/startup.php');

$cfg['debug']['backend_exectime']['fullstart'] = getmicrotime();

cRegistry::bootstrap([
    'sess' => 'cSession',
    'auth' => 'cAuthHandlerBackend',
    'perm' => 'cPermission'
]);

$cfg = cRegistry::getConfig();
$belang = cRegistry::getBackendLanguage();
$lang = cSecurity::toInteger(cRegistry::getLanguageId());
$auth = cRegistry::getAuth();
$perm = cRegistry::getPerm();
$client = cSecurity::toInteger(cRegistry::getClientId());
$area = cRegistry::getArea();
$frame = cRegistry::getFrame();

i18nInit($cfg['path']['contenido_locale'], $belang);

// Initialize variables
$db = cRegistry::getDb();

// Language ID
$iSpeachId = $lang;

// Search - ID
$iSearchId = NULL;

// Search - Text
$sSearchStr = NULL;

// Search - Date type
$sSearchStrDateType = NULL;

// Search - Date from
$sSearchStrDateFrom = '';

// Search - Date to
$sSearchStrDateTo = '';

$bLostAndFound = false;

$iLangId = $lang > 0 ? $lang : 1;

$sDateFormat = getEffectiveSetting('dateformat', 'date', 'Y-m-d');

$sLoadSubnavi = '';
$iIdCat = 0;
$iDisplayMenu = 0;
$iIdTpl = 0;
$aScripts = [];

$sSession = cRegistry::getBackendSessionId() ?? '';

$iSpeachIdTmp = $_POST['speach'] ?? '';
if (is_numeric($iSpeachIdTmp)) {
    $iSpeachId = $iSpeachIdTmp;
}

if (!empty($sSession)) {
    // Backend
    cRegistry::bootstrap([
        'sess' => 'cSession',
        'auth' => 'cAuthHandlerBackend',
        'perm' => 'cPermission'
    ]);
    i18nInit($cfg['path']['contenido_locale'], $belang);
} else {
    // Frontend
    cRegistry::bootstrap([
        'sess' => 'cFrontendSession',
        'auth' => 'cAuthHandlerFrontend',
        'perm' => 'cPermission'
    ]);
}

// Get sorting values - make sure that they only contain valid values!
$sSortByValues = ['title', 'lastmodified', 'published', 'artsort'];
$sSortBy = isset($_POST['sortby']) && in_array($_POST['sortby'], $sSortByValues) ? $_POST['sortby'] : 'lastmodified';
$sSortMode = (isset($_POST['sortmode']) && $_POST['sortmode'] == 'asc') ? 'asc' : 'desc';

/*
 * SAVE SEARCH
 * Some orientation info:
 * 1. User is calling a stored search -> fetch search values from con_properties and put them in PHP variables used for searching
 * 2. User has entered some search values -> standard search in DB
 * 3. User pressed 'save search' -> show 'successfully stored' message & use the stored search id to show the result again
 */

$sSaveSuccessful = '';    // Successfully stored message

// Initialize CONTENIDO_Backend.
// Load all actions from the DB and check if permission is granted.
$oldMemUsage = memory_get_usage();

$cfg['debug']['backend_exectime']['start'] = getmicrotime();

$backendSearchHelper = new cBackendSearchHelper(cRegistry::getDb(), $auth, $perm, $lang, $client);

// Save values
$aSearchFields = [
    'save_title',
    'save_id',
    'save_date_from',
    'save_date_to',
    'save_date_field',
    'save_author',
    'save_name',
];

// Initialize empty search array, we may need it later!
$aSearch = [];
foreach ($aSearchFields as $field) {
    $aSearch[$field] = '';
}

if (sizeof($_GET) == 0 && isset($_POST['save_search'])) {
    // Save current search
    $itemtype = rand(0, 10000);
    $itemid = time();
    $propertyCollection = new cApiPropertyCollection();

    // Getting values from POST and storing them to DB
    // no checking for consistency done here because these values have already been checked when
    // building form sending this POST

    // Save values
    foreach ($aSearchFields as $field) {
        $postValue = trim(strip_tags($_POST[$field] ?? ''));
        $propertyCollection->setValue($itemtype, $itemid, 'savedsearch', $field, $postValue);
    }

    // Call search we just saved to show results
    $aSearch = $backendSearchHelper->getSearchResults($itemid, $itemtype);

    $aScripts[] = $backendSearchHelper->generateJs($aSearch);

    // Reload top left to show new search name
    $aScripts[] = '
        // Refresh top_left frame to show new saved searches
        Con.getFrame("left_top").location.href = Con.getFrame("left_top").location.href + "&save_search=true";
    ';

    // Message for successful saving
    $sSaveSuccessful = i18n("Thank you for saving this search from extinction!");
} elseif (sizeof($_GET) > 0) {
    // Stored search has been called

    $itemtypeReq = cSecurity::toInteger($_GET['itemtype'] ?? '0');
    $itemidReq = cSecurity::toInteger($_GET['itemid'] ?? '0');
    // Do we have the request parameters we need to fetch search values of stored search ?
    if ($itemtypeReq > 0 && $itemidReq > 0) {
        $aSearch = $backendSearchHelper->getSearchResults($itemidReq, $itemtypeReq);

        // Script for refreshing search form with stored search options
        $aScripts[] = $backendSearchHelper->generateJs($aSearch);
    } elseif (isset($_GET['recentedit'])) {
        // Compute current day minus one week
        $actDate = time();
        $weekInSeconds = 60 * 60 * 24 * 7;  // seconds, minutes, hours, days
        $oneWeekEarlier = $actDate - $weekInSeconds;

        $aSearch['save_date_field'] = 'lastmodified';
        $aSearch['save_date_from_day'] = date('d', $oneWeekEarlier);
        $aSearch['save_date_from_month'] = date('m', $oneWeekEarlier);
        $aSearch['save_date_from_year'] = date('Y', $oneWeekEarlier);
        $aSearch['save_date_to_day'] = date('d', $actDate);
        $aSearch['save_date_to_month'] = date('m', $actDate);
        $aSearch['save_date_to_year'] = date('Y', $actDate);
    } elseif (isset($_GET['myarticles'])) {
        $aSearch['save_author'] = $auth->auth['uname'];
    } elseif (isset($_GET['lostfound'])) {
        $bLostAndFound = true;
    }
} elseif (sizeof($_GET) == 0 && isset($_POST)) {
    // Regular search, take over send form data
    $aSearch['save_title'] = trim(strip_tags($_POST['bs_search_text']));
    $aSearch['save_id'] = cSecurity::toInteger($_POST['bs_search_id']);
    $aSearch['save_date_field'] = trim(strip_tags($_POST['bs_search_date_type']));
    $aSearch['save_date_from_day'] = cSecurity::toInteger($_POST['bs_search_date_from_day']);
    $aSearch['save_date_from_month'] = cSecurity::toInteger($_POST['bs_search_date_from_month']);
    $aSearch['save_date_from_year'] = cSecurity::toInteger($_POST['bs_search_date_from_year']);
    $aSearch['save_date_to_day'] = cSecurity::toInteger($_POST['bs_search_date_to_day']);
    $aSearch['save_date_to_month'] = cSecurity::toInteger($_POST['bs_search_date_to_month']);
    $aSearch['save_date_to_year'] = cSecurity::toInteger($_POST['bs_search_date_to_year']);
    $aSearch['save_author'] = trim(strip_tags($_POST['bs_search_author']));
}

// Title / Content
if (!empty($aSearch['save_title'])) {
    $sSearchStr = $aSearch['save_title'];
}
// Article ID
if ($aSearch['save_id'] > 0) {
    $iSearchId = $aSearch['save_id'];
}
// Date
if (!empty($aSearch['save_date_field']) && $aSearch['save_date_field'] != 'n/a') {
    $sSearchStrDateFrom = $backendSearchHelper->composeSaveDateFrom($aSearch);
    $sSearchStrDateTo = $backendSearchHelper->composeSaveDateTo($aSearch);
    $sDateFieldName = $aSearch['save_date_field'];
} else {
    $sDateFieldName = '';
}
// Author
$sSearchStrAuthor = !empty($aSearch['save_author']) ? $aSearch['save_author'] :  'n/a';

// Build the query to search for the article
$sql = "SELECT
          DISTINCT a.idart, a.idartlang, a.title, a.online, a.locked, a.idartlang, a.created, a.published,
          a.artsort, a.lastmodified, b.idcat, b.idcatart, b.idcatart, c.startidartlang,
          c.idcatlang, e.name as 'tplname'
        FROM " . $cfg['tab']['art_lang'] . " as a
          LEFT JOIN " . $cfg['tab']['cat_art'] . " as b ON a.idart = b.idart
          LEFT JOIN " . $cfg['tab']['cat_lang'] . " as c ON a.idartlang = c.startidartlang
          LEFT JOIN " . $cfg['tab']['tpl_conf'] . " as d ON a.idtplcfg = d.idtplcfg
          LEFT JOIN " . $cfg['tab']['tpl'] . " as e ON d.idtpl = e.`idtpl`
          LEFT JOIN " . $cfg['tab']['content'] . " as f ON f.idartlang = a.idartlang
        WHERE
          (a.idlang = " . cSecurity::toInteger($iSpeachId) . ")
        ";

$sWhere = '';

$bNoCriteria = true;

// Article ID
if ($iSearchId > 0) {
    $sWhere .= " AND (a.idart = " . cSecurity::toInteger($iSearchId) . ")";
    $bNoCriteria = false;
}

// Text search
if (!empty($sSearchStr)) {
    $sWhere .= " AND ((a.title LIKE '%" . $backendSearchHelper->mask($sSearchStr) . "%')";
    $sWhere .= " OR (f.value LIKE '%" . $backendSearchHelper->mask($sSearchStr) . "%'))";
    $bNoCriteria = false;
}

if (!empty($sSearchStrDateFrom) && ($sDateFieldName != '')) {
    $sWhere .= " AND (a." . $db->escape($sDateFieldName) . " >= '" . $backendSearchHelper->mask($sSearchStrDateFrom) . "')";
    $bNoCriteria = false;
}

if (!empty($sSearchStrDateTo) && ($sDateFieldName != '')) {
    $sWhere .= " AND (a." . $sDateFieldName . " <= '" . $backendSearchHelper->mask($sSearchStrDateTo) . "')";
    $bNoCriteria = false;
}

if (!empty($sSearchStrAuthor) && ($sSearchStrAuthor != 'n/a')) {
    // Author search
    $sWhere .= " AND ((a.author = '" . $backendSearchHelper->mask($sSearchStrAuthor) . "') OR (a.modifiedby = '" . $backendSearchHelper->mask($sSearchStrAuthor) . "'))";
    $bNoCriteria = false;
}

if (!empty($sWhere)) {
    $sql .= $sWhere;
    $sql .= ' ORDER BY a.' . $sSortBy . ' ' . cString::toUpperCase($sSortMode);
    $db->query($sql);
} elseif ($bLostAndFound) {
    $sql = "SELECT
              DISTINCT a.idart, a.idartlang, a.title, a.online, a.locked, a.idartlang, a.created, a.published,
              a.artsort, a.lastmodified, b.idcat, b.idcatart, b.idcatart, c.startidartlang,
              c.idcatlang, e.name as 'tplname'
            FROM " . $cfg['tab']['art_lang'] . " as a
              LEFT JOIN " . $cfg['tab']['cat_art'] . " as b ON a.idart = b.idart
              LEFT JOIN " . $cfg['tab']['cat_lang'] . " as c ON a.idartlang = c.startidartlang
              LEFT JOIN " . $cfg['tab']['tpl_conf'] . " as d ON a.idtplcfg = d.idtplcfg
              LEFT JOIN " . $cfg['tab']['tpl'] . " as e ON d.idtpl = e.`idtpl`
            WHERE
                (a.idart NOT IN (SELECT " . $cfg['tab']['cat_art'] . ".idart FROM " . $cfg['tab']['cat_art'] . "))
            OR
                (b.idcat NOT IN (SELECT " . $cfg['tab']['cat'] . ".idcat FROM " . $cfg['tab']['cat'] . "));";
    $db->query($sql);
}

$aTableHeaders = [];
foreach ($sSortByValues as $value) {
    $sTableHeader = '<a href="#" class="gray">';
    switch ($value) {
        case 'title':
            $sTableHeader .= i18n('Title');
            break;
        case 'lastmodified':
            $sTableHeader .= i18n('Changed');
            break;
        case 'published':
            $sTableHeader .= i18n('Published');
            break;
        case 'artsort':
            $sTableHeader .= i18n('Sort order');
            break;
        default:
            break;
    }
    $sTableHeader .= '</a>';
    // Add the sorting arrow
    if ($value == $sSortBy) {
        $imageSrc = ($sSortMode == 'asc') ? 'images/sort_up.gif' : 'images/sort_down.gif';
        $sTableHeader .= '<img src="' . $imageSrc . '">';
    }
    $aTableHeaders[$value] = $sTableHeader;
}

$tpl = new cTemplate();

$tpl->setEncoding('iso-8859-1');
$tpl->set('s', 'SCRIPT', implode("\n\n", $aScripts));
$tpl->set('s', 'TITLE', i18n('Search results'));
$tpl->set('s', 'TH_START', i18n("Article"));
$tpl->set('s', 'TH_TITLE', $aTableHeaders['title']);
$tpl->set('s', 'TH_CHANGED', $aTableHeaders['lastmodified']);
$tpl->set('s', 'TH_PUBLISHED', $aTableHeaders['published']);
$tpl->set('s', 'TH_SORTORDER', $aTableHeaders['artsort']);
$tpl->set('s', 'TH_TEMPLATE', i18n("Template"));
$tpl->set('s', 'TH_ACTIONS', i18n("Actions"));
$tpl->set('s', 'CURRENT_SORTBY', $sSortBy);
$tpl->set('s', 'CURRENT_SORTMODE', $sSortMode);

// Successfully stored Message
$tpl->set('s', 'SEARCHSTOREDMESSAGE', $sSaveSuccessful);

$iAffectedRows = $db->affectedRows();

if ($iAffectedRows <= 0 || (empty($sWhere) && !$bLostAndFound)) {
    $sNoArticle = i18n("Missing search value.");
    $sNothingFound = i18n("No article found.");

    if ($bNoCriteria && !$bLostAndFound) {
        $sErrOut = $sNoArticle;
    } else {
        $sErrOut = $sNothingFound;
    }

    $sRow = '<tr><td colspan="7">' . $sErrOut . '</td></tr>';
    $tpl->set('d', 'ROWS', $sRow);
    $sLoadSubnavi = 'Con.getFrame(\'right_top\').location.href = \'main.php?area=con&frame=3&idcat=0&idtpl=' . $iIdTpl . '&contenido=' . $sSession . "';";
    $tpl->next();
} else {
    $bHit = false;

    // First collects base infos about found article like idartlang, idcat, etc.
    $backendSearchHelper->initializeArticleInfos($db);

    $lngFlagAsNormalArticle = i18n('Flag as normal article');
    $lngFlagAsStartArticle = i18n('Flag as start article');
    $lndMakeOffline = i18n('Make offline');
    $lndMakeOnline = i18n('Make online');
    $lngUnfreezeArticle = i18n('Unfreeze article');
    $lngFreezeArticle = i18n('Freeze article');
    $lngReminder = i18n("Reminder");
    $lngSetReminder = i18n("Set reminder / add to todo list");
    $lngDuplicateArticle = i18n("Duplicate article");
    $lngDeleteArticle = i18n("Delete article");
    $lngDeleteArticleQuestion = i18n("Do you really want to delete the following article");
    $lngNone = i18n("None");

    for ($i = 0; $i < $iAffectedRows; $i++) {
        $sRow = '';

        $db->nextRecord();

        $idcat = cSecurity::toInteger($db->f('idcat'));

        $bCheckRights = $backendSearchHelper->hasCommonContentPermission();

        // Check rights for article by cat
        if (!$bCheckRights) {
            $bCheckRights = $backendSearchHelper->hasArticlePermission($idcat);
        }

        if ($bCheckRights) {
            $bHit = true;

            $idart = $db->f('idart');
            $idartlang = $db->f('idartlang');
            $idcatart = $db->f('idcatart');
            $idcatlang = $db->f('idcatlang');
            $title = $db->f('title');
            $idartlang = $db->f('idartlang');
            $artsort = $db->f('artsort');
            $created = date($sDateFormat, strtotime($db->f('created')));
            $lastmodified = date($sDateFormat, strtotime($db->f('lastmodified')));
            $published = date($sDateFormat, strtotime($db->f('published')));
            $online = $db->f('online');
            $locked = $db->f('locked');
            $startidartlang = $db->f('startidartlang');
            $templatename = $db->f('tplname');
            $idtplcfg = $db->f('idtplcfg');

            // Store values of category and template for first found article
            if ($i == 0) {
                $iDisplayMenu = 1;
                $iIdCat = $idcat;
                $iIdTpl = $idtpl;
            }

            // Convert to start article/regular article
            if ($backendSearchHelper->hasArticleMakeStartPermission($idcat) && 0 == 1) {
                if ($startidartlang == $idartlang) {
                    $makeStartarticle = "<td class=\"text_center\"><a class=\"con_img_button\" href=\"main.php?area=con&idcat=$idcat&action=con_makestart&idcatart=$idcatart&frame=4&is_start=0&contenido=$sSession\" title=\"{$lngFlagAsNormalArticle}\"><img src=\"images/isstart1.gif\" title=\"{$lngFlagAsNormalArticle}\" alt=\"{$lngFlagAsNormalArticle}\"></a></td>";
                } else {
                    $makeStartarticle = "<td class=\"text_center\"><a class=\"con_img_button\" href=\"main.php?area=con&idcat=$idcat&action=con_makestart&idcatart=$idcatart&frame=4&is_start=1&contenido=$sSession\" title=\"{$lngFlagAsStartArticle}\"><img src=\"images/isstart0.gif\" title=\"{$lngFlagAsStartArticle}\" alt=\"{$lngFlagAsStartArticle}\"></a></td>";
                }
            } else {
                if ($startidartlang == $idartlang) {
                    $makeStartarticle = "<td class=\"text_center\"><img class=\"con_img_button_off\" src=\"images/isstart1.gif\" title=\"{$lngFlagAsNormalArticle}\" alt=\"{$lngFlagAsNormalArticle}\"></td>";
                } else {
                    $makeStartarticle = "<td class=\"text_center\"><img class=\"con_img_button_off\" src=\"images/isstart0.gif\" title=\"{$lngFlagAsStartArticle}\" alt=\"{$lngFlagAsStartArticle}\"></td>";
                }
            }

            // Set online/offline
            if ($online == 1) {
                $bgColorRow = "background-color: #E2E2E2;";
                $setOnOff = "<a href=\"main.php?area=con&idcat=$idcat&action=con_makeonline&frame=4&idart=$idart&contenido=$sSession\" title=\"{$lndMakeOffline}\"><img src=\"images/online.gif\" title=\"{$lndMakeOffline}\" alt=\"{$lndMakeOffline}\"></a>";
            } else {
                $bgColorRow = "background-color: #E2D9D9;";
                $setOnOff = "<a href=\"main.php?area=con&idcat=$idcat&action=con_makeonline&frame=4&idart=$idart&contenido=$sSession\" title=\"{$lndMakeOnline}\"><img src=\"images/offline.gif\" title=\"{$lndMakeOnline}\" alt=\"{$lndMakeOnline}\"></a>";
            }
            // Lock/unlock article
            if ($locked == 1) {
                $lockArticle = "<a href=\"main.php?area=con&idcat=$idcat&action=con_lock&frame=4&idart=$idart&contenido=$sSession\" title=\"{$lngUnfreezeArticle}\"><img src=\"images/lock_closed.gif\" title=\"{$lngUnfreezeArticle}\" alt=\"{$lngUnfreezeArticle}\"></a>";
            } else {
                $lockArticle = "<a href=\"main.php?area=con&idcat=$idcat&action=con_lock&frame=4&idart=$idart&contenido=$sSession\" title=\"{$lngFreezeArticle}\"><img src=\"images/lock_open.gif\" title=\"{$lngFreezeArticle}\" alt=\"{$lngFreezeArticle}\"></a>";
            }

            // Template name
            if (!empty($templatename)) {
                $sTemplateName = conHtmlentities($templatename);
            } else {
                $templateInfo = $backendSearchHelper->getCategoryTemplateInfos($idcat);
                $sTemplateName = !empty($templateInfo['name']) ? '<i>' . $templateInfo['name'] . '</i>' : "--- " . $lngNone . " ---";
            }

            $sRowId = "$idart-$idartlang-$idcat-0-$idcatart-$iLangId";

            if ($i == 0) {
                $tpl->set('s', 'FIRST_ROWID', $sRowId);
            }

            $categoryBreadcrumb = $backendSearchHelper->getCategoryBreadcrumb($idcat);

            $sTitle = cSecurity::unFilter($title);
            if ($backendSearchHelper->hasArticleEditContentPermission($idcat)) {
                $editart = "<a href=\"main.php?area=con_editcontent&action=con_editart&changeview=edit&idartlang=$idartlang&idart=$idart&idcat=$idcat&frame=4&contenido=$sSession\" title=\"idart: $idart idcatart: $idcatart\"><i><span style='font-size: 80%'>" . $categoryBreadcrumb . "</span></i><br>" . $sTitle . "</a>";
            } else {
                $editart = "<i><span style='font-size: 80%'>" . $categoryBreadcrumb . "</span></i><br>" . $sTitle;
            }

            if ($backendSearchHelper->hasArticleDuplicatePermission($idcat)) {
                $duplicate = "<a href=\"main.php?area=con&idcat=$idcat&action=con_duplicate&duplicate=$idart&frame=4&contenido=$sSession\" title=\"$lngDuplicateArticle\"><img src=\"images/but_copy.gif\" title=\"$lngDuplicateArticle\" alt=\"$lngDuplicateArticle\"></a>";
            } else {
                $duplicate = "";
            }

            if ($backendSearchHelper->hasArticleDeletePermission($idcat)) {
                $sTitle = conHtmlSpecialChars($title);
                if (cString::getStringLength($sTitle) > 30) {
                    $sTitle = cString::getPartOfString($sTitle, 0, 27) . "...";
                }

                $delete = '
                <a
                    href="javascript:void(0)"
                    onclick="Con.showConfirmation(&quot;' . $lngDeleteArticleQuestion . ':<br><br><b>' . conHtmlSpecialChars($sTitle) . '</b>&quot;, function() {deleteArticle(' . $idart . ', ' . $idcat . ');});"
                    title="' . $lngDeleteArticle . '"
                >
                    <img src="images/delete.gif" title="' . $lngDeleteArticle . '" alt="' . $lngDeleteArticle . '">
                </a>';
            } else {
                $delete = "";
            }

            if (!is_numeric($artsort) && empty($artsort)) {
                $artsort = '&nbsp;';
            }
            if (empty($lastmodified)) {
                $lastmodified = '&nbsp;';
            }
            if (empty($published)) {
                $published = '&nbsp;';
            }

            $sRow = '<tr id="' . $sRowId . '" class="row_mark" data-idcat="' . $idcat . '" data-idart="' . $idart . '">' . "\n";
            $sRow .= $makeStartarticle . "\n";
            $sRow .= "<td>$editart</td>
                      <td>$lastmodified</td>
                      <td>$published</td>
                      <td class=\"text_center\">" . $artsort . "</td>
                      <td>$sTemplateName</td>
                      <td>
                          <a id=\"m1\" onclick=\"javascript:window.open('main.php?subject=$lngReminder&amp;area=todo&amp;frame=1&amp;itemtype=idart&amp;itemid=$idart&amp;contenido=$sSession', 'todo', 'scrollbars=yes, height=300, width=625');\" title=\"$lngSetReminder\" href=\"#\"><img id=\"m2\" alt=\"$lngSetReminder\" src=\"images/but_setreminder.gif\"></a>
                          $properties
                          $tplconfig
                          $duplicate
                          $delete
                      </td>
                  </tr>";

            $tpl->set('d', 'ROWS', $sRow);
            $tpl->next();
        }
    }

    if (!$bHit) {
        $sNothingFound = i18n("No article found.");
        $sRow = '<tr><td colspan="7">' . $sNothingFound . '</td></tr>';
        $tpl->set('d', 'ROWS', $sRow);
        $tpl->next();
    }

    if ($bLostAndFound) {
        $iDisplayMenu = 1;
    }
    $sLoadSubnavi = 'Con.getFrame(\'right_top\').location.href = \'main.php?area=con&frame=3&idcat=' . $iIdCat . '&idtpl=' . $iIdTpl . '&display_menu=' . $iDisplayMenu . '&contenido=' . $sSession . "';";
}


###########################
# Save Search Parameters
###########################

if (sizeof($_GET) == 0 && isset($_POST) && !$bNoCriteria) {
    // Build form with hidden fields that contain all search parameters to be stored using generic db
    $searchForm = '
        <form id="save_search" target="right_bottom" method="post" action="backend_search.php">
            <input type="hidden" name="area" value="' . $area . '">
            <input type="hidden" name="frame" value="' . $frame . '">
            <input type="hidden" name="contenido" value="' . $sSession . '">
            <input type="hidden" name="speach" value="' . $lang . '">
            <input type="hidden" name="save_search" id="save_search" value="true">
            <input type="hidden" name="save_title" id="save_title" value="' . $sSearchStr . '">
            <input type="hidden" name="save_id" id="save_id" value="' . $iSearchId . '">
            <input type="hidden" name="save_date_from" id="save_date_from" value="' . $sSearchStrDateFrom . '">
            <input type="hidden" name="save_date_to" id="save_date_to" value="' . $sSearchStrDateTo . '">
            <input type="hidden" name="save_date_field" id="save_date_field" value="' . $sDateFieldName . '">
            <input type="hidden" name="save_author" id="save_author" value="' . $sSearchStrAuthor . '">
            <label for="' . 'save_name' . '">' . i18n("Search name") . ': </label>
            <input type="text" class="text_medium" name="save_name" id="save_name" placeholder="' . i18n("The search") . '" class="align_middle">
            <input type="image" class="con_img_button align_middle" src="./images/but_ok.gif" alt="' . i18n('Store') . '" title="' . i18n('Store') . '" value="' . i18n('Store') . '" name="submit">
        </form>'
    ;

    $tpl->set('s', 'STORESEARCHFORM', $searchForm);

    // Title / Header for 'store the search' form
    $tpl->set('s', 'STORESEARCHINFO', i18n("Save this search"));
} else {
    $tpl->set('s', 'STORESEARCHINFO', '');
    $tpl->set('s', 'STORESEARCHFORM', '');
}

$tpl->set('s', 'SUBNAVI', $sLoadSubnavi);

// Finalize debug of backend rendering
ob_start();
cDebug::out(cBuildBackendRenderDebugInfo($cfg, $oldMemUsage, basename(__FILE__)));
$output = ob_get_contents();
ob_end_clean();
$tpl->set('s', 'DEBUGMESSAGE', $output);

sendEncodingHeader($db, $cfg, $lang);
$tpl->generate($cfg['path']['templates'] . 'template.backend_search_results.html');
