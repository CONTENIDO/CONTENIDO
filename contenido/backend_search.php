<?php
/**
 * This file performs various searches on articles from backend.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Holger Librenz, Andreas Lindner
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO startup process
include_once('./includes/startup.php');

cRegistry::bootstrap(array(
    'sess' => 'cSession',
    'auth' => 'Contenido_Challenge_Crypt_Auth',
    'perm' => 'cPermission'
));

i18nInit($cfg['path']['contenido_locale'], $belang);

// Initialize variables
$db = cRegistry::getDb();
$db2 = cRegistry::getDb();

// Session
$sSession = '';
$sSessionTmp = '';

// Language ID
$iSpeachId = $lang;
$iSpeachIdTmp = NULL;

// Search - ID
$iSearchId = NULL;
$iSearchIdTmp = 0;

// Search - Text
$sSearchStr = NULL;
$sSearchStrTmp = '';

// Search - Date type
$sSearchStrDateType = NULL;
$sSearchStrDateTypeTmp = '';

// Search - Date from
$sSearchStrDateFrom = NULL;
$sSearchStrDateFromTmp = '';

// Search - Date to
$sSearchStrDateTo = NULL;
$sSearchStrDateToTmp = '';

$bLostAndFound = false;

$sWhere = '';

$iLangId = ((int) $lang > 0 ? (int) $lang : 1);

$sDateFormat = getEffectiveSetting('dateformat', 'date', 'Y-m-d');

$sLoadSubnavi = '';
$iIdCat = 0;
$iDisplayMenu = 0;
$iIdTpl = 0;
$sScript = '';


if (isset($_POST[$sess->name])) {
    $sSessionTmp = trim(strip_tags($_POST[$sess->name]));
} elseif (isset($_GET[$sess->name])) {
    $sSessionTmp = trim(strip_tags($_GET[$sess->name]));
}
if (strlen($sSessionTmp) > 0) {
    $sSession = $sSessionTmp;
}

if (isset($_POST['speach'])) {
    $iSpeachIdTmp = (int) $_POST['speach'];
    if ((string) $iSpeachIdTmp === $_POST['speach']) {
        $iSpeachId = $iSpeachIdTmp;
    }
}
if (!empty($sSession)) {
    // Backend
    cRegistry::bootstrap(array(
        'sess' => 'cSession',
        'auth' => 'cAuthHandlerBackend',
        'perm' => 'cPermission'
    ));
    i18nInit($cfg['path']['contenido_locale'], $belang);
} else {
    // Frontend
    cRegistry::bootstrap(array(
        'sess' => 'cFrontendSession',
        'auth' => 'cAuthHandlerFrontend',
        'perm' => 'cPermission'
    ));
}

// Get sorting values - make sure that they only contain valid values!
$sSortByValues = array('title', 'lastmodified', 'published', 'artsort');
$sSortBy = in_array($_POST['sortby'], $sSortByValues) ? $_POST['sortby'] : 'lastmodified';
$sSortMode = ($_POST['sortmode'] == 'asc') ? 'asc' : 'desc';

/*
 * SAVE SEARCH
 * Some orientation info:
 * 1. User is calling a stored search -> fetch search values from con_properties and put them in PHP variables used for searching
 * 2. User has entered some search values -> standard search in DB
 * 3. User pressed 'save search' -> show 'successfully stored' message & use the stored search id to show the result again
 */

$sSaveTitle = 'save_title';
$sSaveId = 'save_id';
$sSaveDateFrom = 'save_date_from';
$sSaveDateFromYear = 'save_date_from_year';
$sSaveDateFromMonth = 'save_date_from_month';
$sSaveDateFromDay = 'save_date_from_day';
$sSaveDateTo = 'save_date_to';
$sSaveDateToYear = 'save_date_to_year';
$sSaveDateToMonth = 'save_date_to_month';
$sSaveDateToDay = 'save_date_to_day';
$sSaveDateField = 'save_date_field';
$sSaveAuthor = 'save_author';
$sSaveName = 'save_name';
$sType = 'savedsearch';  // section for saved searches in con_properties
$sRefreshScript = '';        // refresh top left frame
$sSaveSuccessfull = '';    // Sucessfully stored message


/**
 * Generating refresh JavaScript for form in left_top
 * @global string $sSaveTitle
 * @global string $sSaveId
 * @global string $sSaveDateFromYear
 * @global string $sSaveDateFromMonth
 * @global string $sSaveDateFromDay
 * @global string $sSaveDateToYear
 * @global string $sSaveDateToMonth
 * @global string $sSaveDateToDay
 * @global string $sSaveDateField
 * @global string $sSaveAuthor
 * @global string $sSaveName
 * @param array $aValues
 * @return string
 */
function generateJs($aValues) {
    if (is_array($aValues)) {
        global $sSaveTitle;
        global $sSaveId;
        global $sSaveDateFromYear;
        global $sSaveDateFromMonth;
        global $sSaveDateFromDay;
        global $sSaveDateToYear;
        global $sSaveDateToMonth;
        global $sSaveDateToDay;
        global $sSaveDateField;
        global $sSaveAuthor;
        global $sSaveName;

        return 'function refreshArticleSearchForm(refresh) {
                    var oFrame = Con.getFrame("left_top");
                    if (oFrame) {
                        oForm = oFrame.document.backend_search;

                        oForm.bs_search_text.value = "' . $aValues[$sSaveTitle] . '";
                        oForm.bs_search_id.value = "' . $aValues[$sSaveId] . '";
                        oForm.bs_search_date_type.value = "' . $aValues[$sSaveDateField] . '";

                        oFrame.toggle_tr_visibility("tr_date_from");
                        oFrame.toggle_tr_visibility("tr_date_to");

                        oForm.bs_search_date_from_day.value = "' . $aValues[$sSaveDateFromDay] . '";
                        oForm.bs_search_date_from_month.value = "' . $aValues[$sSaveDateToMonth] . '";
                        oForm.bs_search_date_from_year.value = "' . $aValues[$sSaveDateFromYear] . '";

                        oForm.bs_search_date_to_day.value = "' . $aValues[$sSaveDateToDay] . '";
                        oForm.bs_search_date_to_month.value = "' . $aValues[$sSaveDateToMonth] . '";
                        oForm.bs_search_date_to_year.value = "' . $aValues[$sSaveDateToYear] . '";

                        oForm.bs_search_author.value = "' . $aValues[$sSaveAuthor] . '";
                    }
                }
                refreshArticleSearchForm();
                ';
    } else {
        return false;
    }
}

/**
 * Masks string for inserting into SQL statement
 * @param string $sString
 * @return string
 */
function mask($sString) {
    $sString = str_replace('\\', '\\\\', $sString);
    $sString = str_replace('\'', '\\\'', $sString);
    $sString = str_replace('"', '\\"', $sString);
    return $sString;
}

/**
 * Searches in properties
 * @param mixed  $itemidReq Property item id
 * @param string $itemtypeReq Property item type
 * @return array
 */
function getSearchResults($itemidReq, $itemtypeReq) {
    global $sSaveTitle;
    global $sSaveId;
    global $sSaveDateFrom;
    global $sSaveDateFromYear;
    global $sSaveDateFromMonth;
    global $sSaveDateFromDay;
    global $sSaveDateTo;
    global $sSaveDateToYear;
    global $sSaveDateToMonth;
    global $sSaveDateToDay;
    global $sSaveDateField;
    global $sSaveAuthor;
    global $sSaveName;
    global $sType;

    $retValue = array();
    // Request from DB
    $propertyCollection = new cApiPropertyCollection();
    $results = $propertyCollection->getValuesByType($itemtypeReq, $itemidReq, $sType);

    // Put results in returning Array
    $retValue[$sSaveTitle] = $results[$sSaveTitle];
    $retValue[$sSaveId] = $results[$sSaveId];
    $retValue[$sSaveDateField] = $results[$sSaveDateField];
    $retValue[$sSaveAuthor] = $results[$sSaveAuthor];

    // Date from
    $sSearchStrDateFromDayTmp = 0;
    $sSearchStrDateFromMonthTmp = 0;
    $sSearchStrDateFromYearTmp = 0;
    $saveDateFrom = $results[$sSaveDateFrom];
    if (isset($saveDateFrom) && sizeof($saveDateFrom) > 0) {
        $saveDateFrom = str_replace(' 00:00:00', '', $saveDateFrom);
        $saveDateFromParts = explode('-', $saveDateFrom);
        if (sizeof($saveDateFromParts) == 3) {
            $retValue[$sSaveDateFromYear] = $saveDateFromParts[0];
            $retValue[$sSaveDateFromMonth] = $saveDateFromParts[1];
            $retValue[$sSaveDateFromDay] = $saveDateFromParts[2];
        }
    }
    // Date to
    $sSearchStrDateToDayTmp = 0;
    $sSearchStrDateToMonthTmp = 0;
    $sSearchStrDateToYearTmp = 0;
    $saveDateTo = $results[$sSaveDateTo];
    if (isset($saveDateTo) && sizeof($saveDateTo) > 0) {
        $saveDateTo = str_replace(' 23:59:59', '', $saveDateTo);
        $saveDateToParts = explode('-', $saveDateTo);
        if (sizeof($saveDateToParts) == 3) {
            $retValue[$sSaveDateToYear] = $saveDateToParts[0];
            $retValue[$sSaveDateToMonth] = $saveDateToParts[1];
            $retValue[$sSaveDateToDay] = $saveDateToParts[2];
        }
    }
    return $retValue;
}

// Save current search
if (sizeof($_GET) == 0 && isset($_POST['save_search'])) {
    $itemtype = rand(0, 10000);
    $itemid = time();
    $propertyCollection = new cApiPropertyCollection();

    // Getting values from POST and storing them to DB
    // no checking for consistency done here because these values have already been checked when
    // building form sending this POST

    // Title / Content
    $propertyCollection->setValue($itemtype, $itemid, $sType, $sSaveTitle, $_POST[$sSaveTitle]);
    // ID
    $propertyCollection->setValue($itemtype, $itemid, $sType, $sSaveId, $_POST[$sSaveId]);
    // Date from
    $propertyCollection->setValue($itemtype, $itemid, $sType, $sSaveDateFrom, $_POST[$sSaveDateFrom]);
    // Date to
    $propertyCollection->setValue($itemtype, $itemid, $sType, $sSaveDateTo, $_POST[$sSaveDateTo]);
    // Date type
    $propertyCollection->setValue($itemtype, $itemid, $sType, $sSaveDateField, $_POST[$sSaveDateField]);
    // Author
    $propertyCollection->setValue($itemtype, $itemid, $sType, $sSaveAuthor, $_POST[$sSaveAuthor]);
    // Name of search (displayed to user)
    $propertyCollection->setValue($itemtype, $itemid, $sType, $sSaveName, $_POST[$sSaveName]);

    // Call search we justed saved to show results
    $aSearchResults = getSearchResults($itemid, $itemtype);
    $sSearchStrTmp = $aSearchResults[$sSaveTitle];
    $iSearchIdTmp = $aSearchResults[$sSaveId];
    $sSearchStrDateTypeTmp = $aSearchResults[$sSaveDateField];
    $sSearchStrDateFromDayTmp = $aSearchResults[$sSaveDateFromDay];
    $sSearchStrDateFromMonthTmp = $aSearchResults[$sSaveDateFromMonth];
    $sSearchStrDateFromYearTmp = $aSearchResults[$sSaveDateFromYear];
    $sSearchStrDateToDayTmp = $aSearchResults[$sSaveDateToDay];
    $sSearchStrDateToMonthTmp = $aSearchResults[$sSaveDateToMonth];
    $sSearchStrDateToYearTmp = $aSearchResults[$sSaveDateToYear];
    $sSearchStrAuthorTmp = $aSearchResults[$sSaveAuthor];

    $sScript = generateJs($aSearchResults);

    // Reload top left to show new search name
    $sRefreshScript .= 'Con.getFrame("left_top").location.href = Con.getFrame("left_top").location.href + "&save_search=true";';

    // Message for successful saving
    $sSaveSuccessfull = i18n("Thank you for saving this search from extinction!");
} elseif (sizeof($_GET) > 0) {
    // STORED SEARCH HAS BEEN CALLED

    $itemtypeReq = $_GET['itemtype'];
    $itemidReq = $_GET['itemid'];
    // Do we have the request parameters we need to fetch search values of stored search ?
    if ((isset($itemtypeReq) && strlen($itemtypeReq) > 0) && (isset($itemidReq) && strlen($itemidReq) > 0)) {
        $aSearchResults = getSearchResults($itemidReq, $itemtypeReq);
        $sSearchStrTmp = $aSearchResults[$sSaveTitle];
        $iSearchIdTmp = $aSearchResults[$sSaveId];
        $sSearchStrDateTypeTmp = $aSearchResults[$sSaveDateField];
        $sSearchStrDateFromDayTmp = $aSearchResults[$sSaveDateFromDay];
        $sSearchStrDateFromMonthTmp = $aSearchResults[$sSaveDateFromMonth];
        $sSearchStrDateFromYearTmp = $aSearchResults[$sSaveDateFromYear];
        $sSearchStrDateToDayTmp = $aSearchResults[$sSaveDateToDay];
        $sSearchStrDateToMonthTmp = $aSearchResults[$sSaveDateToMonth];
        $sSearchStrDateToYearTmp = $aSearchResults[$sSaveDateToYear];
        $sSearchStrAuthorTmp = $aSearchResults[$sSaveAuthor];
        $sSearchStrDateFromTmp = $aSearchResults[$sSaveDateFrom];
        $sSearchStrDateToTmp = $aSearchResults[$sSaveDateTo];

        // Script for refreshing search form with stored search options
        $sScript = generateJs($aSearchResults);
    } elseif (isset($_GET['recentedit'])) {
        // Compute current day minus one week
        $actDate = time();
        $weekInSeconds = 60 * 60 * 24 * 7;  // seconds, minutes, hours, days
        $oneWeekEarlier = $actDate - $weekInSeconds;

        $sSearchStrDateTypeTmp = 'lastmodified';
        $sSearchStrDateFromDayTmp = date('d', $oneWeekEarlier);
        $sSearchStrDateFromMonthTmp = date('m', $oneWeekEarlier);
        $sSearchStrDateFromYearTmp = date('Y', $oneWeekEarlier);
        $sSearchStrDateToDayTmp = date('d', $actDate);
        $sSearchStrDateToMonthTmp = date('m', $actDate);
        $sSearchStrDateToYearTmp = date('Y', $actDate);
    } elseif (isset($_GET['myarticles'])) {
        $sSearchStrAuthorTmp = $auth->auth['uname'];
    } elseif (isset($_GET['lostfound'])) {
        $bLostAndFound = true;
    }
} elseif (sizeof($_GET) == 0 && isset($_POST)) {
    // STANDARD SEARCH

    $sSearchStrTmp = trim(strip_tags($_POST['bs_search_text']));
    $iSearchIdTmp = (int) $_POST['bs_search_id'];
    $sSearchStrDateTypeTmp = trim(strip_tags($_POST['bs_search_date_type']));
    $sSearchStrDateFromDayTmp = (int) trim(strip_tags($_POST['bs_search_date_from_day']));
    $sSearchStrDateFromMonthTmp = (int) trim(strip_tags($_POST['bs_search_date_from_month']));
    $sSearchStrDateFromYearTmp = (int) trim(strip_tags($_POST['bs_search_date_from_year']));
    $sSearchStrDateToDayTmp = (int) trim(strip_tags($_POST['bs_search_date_to_day']));
    $sSearchStrDateToMonthTmp = (int) trim(strip_tags($_POST['bs_search_date_to_month']));
    $sSearchStrDateToYearTmp = (int) trim(strip_tags($_POST['bs_search_date_to_year']));
    $sSearchStrAuthorTmp = trim(strip_tags($_POST['bs_search_author']));
}
// else ERROR
// No code here, empty results caught later in code

// Title / Content
if (!empty($sSearchStrTmp)) {
    $sSearchStr = $sSearchStrTmp;
}
// Article ID
if ($iSearchIdTmp > 0) {
    $iSearchId = $iSearchIdTmp;
}
// Date
if ($sSearchStrDateTypeTmp != 'n/a') {
    if (($sSearchStrDateFromDayTmp > 0) && ($sSearchStrDateFromMonthTmp > 0) && ($sSearchStrDateFromYearTmp > 0)) {
        $sSearchStrDateFrom = $sSearchStrDateFromYearTmp . '-' . $sSearchStrDateFromMonthTmp . '-' . $sSearchStrDateFromDayTmp . ' 00:00:00';
    } else {
        $sSearchStrDateFrom = '';
    }

    if (($sSearchStrDateToDayTmp > 0) && ($sSearchStrDateToMonthTmp > 0) && ($sSearchStrDateToYearTmp > 0)) {
        $sSearchStrDateTo = $sSearchStrDateToYearTmp . '-' . $sSearchStrDateToMonthTmp . '-' . $sSearchStrDateToDayTmp . ' 23:59:59';
    } else {
        $sSearchStrDateTo = '';
    }

    $sDateFieldName = $sSearchStrDateTypeTmp;
} else {
    $sDateFieldName = '';
}
// Author
if (!empty($sSearchStrAuthorTmp)) {
    $sSearchStrAuthor = $sSearchStrAuthorTmp;
}

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
    $sWhere .= " AND ((a.title LIKE '%" . mask($db->escape($sSearchStr)) . "%')";
    $sWhere .= " OR (f.value LIKE '%" . mask($db->escape($sSearchStr)) . "%'))";
    $bNoCriteria = false;
}

if (!empty($sSearchStrDateFrom) && ($sDateFieldName != '')) {
    $sWhere .= " AND (a." . $db->escape($sDateFieldName) . " >= '" . mask($db->escape($sSearchStrDateFrom)) . "')";
    $bNoCriteria = false;
}

if (!empty($sSearchStrDateTo) && ($sDateFieldName != '')) {
    $sWhere .= " AND (a." . $sDateFieldName . " <= '" . mask($db->escape($sSearchStrDateTo)) . "')";
    $bNoCriteria = false;
}

if (!empty($sSearchStrAuthor) && ($sSearchStrAuthor != 'n/a')) {
    // Author seach
    $sWhere .= " AND ((a.author = '" . mask($db->escape($sSearchStrAuthor)) . "') OR (a.modifiedby = '" . mask($db->escape($sSearchStrAuthor)) . "'))";
    $bNoCriteria = false;
}

if (!empty($sWhere)) {
    $sql .= $sWhere;
    $sql .= ' ORDER BY a.' . $sSortBy . ' ' . strtoupper($sSortMode);
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

$aTableHeaders = array();
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
$tpl->set('s', 'SCRIPT', $sScript);
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

// Refresh top left frame
$tpl->set('s', 'REFRESH', $sRefreshScript);

// Successfully stored Message
$tpl->set('s', 'SEARCHSTOREDMESSAGE', $sSaveSuccessfull);

$iAffectedRows = $db->affectedRows();

if ($iAffectedRows <= 0 || (empty($sWhere) && !$bLostAndFound)) {
    $sNoArticle = i18n("Missing search value.");
    $sNothingFound = i18n("No article found.");

    if ($bNoCriteria && !$bLostAndFound) {
        $sErrOut = $sNoArticle;
    } else {
        $sErrOut = $sNothingFound;
    }

    $sRow = '<tr><td colspan="7" class="bordercell">' . $sErrOut . '</td></tr>';
    $tpl->set('d', 'ROWS', $sRow);
    $sLoadSubnavi = 'Con.getFrame(\'right_top\').location.href = \'main.php?area=con&frame=3&idcat=0&idtpl=' . $iIdTpl . '&contenido=' . $sSession . "';";
    $tpl->next();
} else {
    $bHit = false;

    for ($i = 0; $i < $iAffectedRows; $i++) {
        $sRow = '';

        $db->nextRecord();

        $idcat = $db->f("idcat");

        $bCheckRights = $perm->have_perm_area_action("con", "con_makestart");

        if (!$bCheckRights) {
            $bCheckRights = $perm->have_perm_area_action("con", "con_makeonline");
        }
        if (!$bCheckRights) {
            $bCheckRights = $perm->have_perm_area_action("con", "con_deleteart");
        }
        if (!$bCheckRights) {
            $bCheckRights = $perm->have_perm_area_action("con", "con_tplcfg_edit");
        }
        if (!$bCheckRights) {
            $bCheckRights = $perm->have_perm_area_action("con", "con_makecatonline");
        }
        if (!$bCheckRights) {
            $bCheckRights = $perm->have_perm_area_action("con", "con_changetemplate");
        }
        if (!$bCheckRights) {
            $bCheckRights = $perm->have_perm_area_action("con_editcontent", "con_editart");
        }
        if (!$bCheckRights) {
            $bCheckRights = $perm->have_perm_area_action("con_editart", "con_edit");
        }
        if (!$bCheckRights) {
            $bCheckRights = $perm->have_perm_area_action("con_editart", "con_newart");
        }
        if (!$bCheckRights) {
            $bCheckRights = $perm->have_perm_area_action("con_editart", "con_saveart");
        }

        // Check rights per cat
        if (!$bCheckRights) {
            // hotfix timo trautmann 2008-12-10 also check rights in associated groups
            $aGroupsForUser = $perm->getGroupsForUser($auth->auth['uid']);
            $aGroupsForUser[] = $auth->auth['uid'];
            $sTmpUserString = implode("','", $aGroupsForUser);

            // Check if any rights are applied to current user or his groups
            $sql = "SELECT *
                    FROM " . $cfg["tab"]["rights"] . "
                    WHERE user_id IN ('" . $sTmpUserString . "') AND idclient = " . cSecurity::toInteger($client) . "
                        AND idlang = " . cSecurity::toInteger($lang) . " AND idcat = " . cSecurity::toInteger($idcat);
            $db2->query($sql);

            if ($db2->numRows() != 0) {

                if (!$bCheckRights) {
                    $bCheckRights = $perm->have_perm_area_action_item("con", "con_makestart", $idcat);
                }
                if (!$bCheckRights) {
                    $bCheckRights = $perm->have_perm_area_action_item("con", "con_makeonline", $idcat);
                }
                if (!$bCheckRights) {
                    $bCheckRights = $perm->have_perm_area_action_item("con", "con_deleteart", $idcat);
                }
                if (!$bCheckRights) {
                    $bCheckRights = $perm->have_perm_area_action_item("con", "con_tplcfg_edit", $idcat);
                }
                if (!$bCheckRights) {
                    $bCheckRights = $perm->have_perm_area_action_item("con", "con_makecatonline", $idcat);
                }
                if (!$bCheckRights) {
                    $bCheckRights = $perm->have_perm_area_action_item("con", "con_changetemplate", $idcat);
                }
                if (!$bCheckRights) {
                    $bCheckRights = $perm->have_perm_area_action_item("con_editcontent", "con_editart", $idcat);
                }
                if (!$bCheckRights) {
                    $bCheckRights = $perm->have_perm_area_action_item("con_editart", "con_edit", $idcat);
                }
                if (!$bCheckRights) {
                    $bCheckRights = $perm->have_perm_area_action_item("con_editart", "con_newart", $idcat);
                }
                if (!$bCheckRights) {
                    $bCheckRights = $perm->have_perm_area_action_item("con_editart", "con_saveart", $idcat);
                }
            }
        }

        if ($bCheckRights) {
            $bHit = true;

            $idart = $db->f("idart");
            $idartlang = $db->f("idartlang");
            $idcatart = $db->f("idcatart");
            $idcatlang = $db->f("idcatlang");
            $title = $db->f("title");
            $idartlang = $db->f("idartlang");
            $created = date($sDateFormat, strtotime($db->f("created")));
            $lastmodified = date($sDateFormat, strtotime($db->f("lastmodified")));
            $published = date($sDateFormat, strtotime($db->f("published")));
            $online = $db->f("online");
            $locked = $db->f("locked");
            $startidartlang = $db->f("startidartlang");
            $templatename = $db->f("tplname");
            $idtplcfg = $db->f("idtplcfg");

            // Store values of category and template for first found article
            if ($i == 0) {
                $iDisplayMenu = 1;
                $iIdCat = $idcat;
                $iIdTpl = $idtpl;
            }

            // Convert to start article/regular article
            if ($perm->have_perm_area_action_item("con", "con_makestart", $idcat) && 0 == 1) {
                if ($startidartlang == $idartlang) {
                    $sFlagTitle = i18n('Flag as normal article');
                    $makeStartarticle = "<td nowrap=\"nowrap\" class=\"bordercell\"><a href=\"main.php?area=con&idcat=$idcat&action=con_makestart&idcatart=$idcatart&frame=4&is_start=0&contenido=$sSession\" title=\"{$sFlagTitle}\"><img src=\"images/isstart1.gif\" border=\"0\" title=\"{$sFlagTitle}\" alt=\"{$sFlagTitle}\"></a></td>";
                } else {
                    $sFlagTitle = i18n('Flag as start article');
                    $makeStartarticle = "<td nowrap=\"nowrap\" class=\"bordercell\"><a href=\"main.php?area=con&idcat=$idcat&action=con_makestart&idcatart=$idcatart&frame=4&is_start=1&contenido=$sSession\" title=\"{$sFlagTitle}\"><img src=\"images/isstart0.gif\" border=\"0\" title=\"{$sFlagTitle}\" alt=\"{$sFlagTitle}\"></a></td>";
                }
            } else {
                if ($startidartlang == $idartlang) {
                    $makeStartarticle = "<td nowrap=\"nowrap\" class=\"bordercell\"><img src=\"images/isstart1.gif\" border=\"0\" title=\"{$sFlagTitle}\" alt=\"{$sFlagTitle}\"></td>";
                } else {
                    $makeStartarticle = "<td nowrap=\"nowrap\" class=\"bordercell\"><img src=\"images/isstart0.gif\" border=\"0\" title=\"{$sFlagTitle}\" alt=\"{$sFlagTitle}\"></td>";
                }
            }

            // Set online/offline
            if ($online == 1) {
                $sOnlineStatus = i18n('Make offline');
                $bgColorRow = "background-color: #E2E2E2;";
                $setOnOff = "<a href=\"main.php?area=con&idcat=$idcat&action=con_makeonline&frame=4&idart=$idart&contenido=$sSession\" title=\"{$sOnlineStatus}\"><img src=\"images/online.gif\" title=\"{$sOnlineStatus}\" alt=\"{$sOnlineStatus}\" border=\"0\"></a>";
            } else {
                $sOnlineStatus = i18n('Make online');
                $bgColorRow = "background-color: #E2D9D9;";
                $setOnOff = "<a href=\"main.php?area=con&idcat=$idcat&action=con_makeonline&frame=4&idart=$idart&contenido=$sSession\" title=\"{$sOnlineStatus}\"><img src=\"images/offline.gif\" title=\"{$sOnlineStatus}\" alt=\"{$sOnlineStatus}\" border=\"0\"></a>";
            }
            // Lock/unlock article
            if ($locked == 1) {
                $sLockStatus = i18n('Unfreeze article');
                $lockArticle = "<a href=\"main.php?area=con&idcat=$idcat&action=con_lock&frame=4&idart=$idart&contenido=$sSession\" title=\"{$sLockStatus}\"><img src=\"images/lock_closed.gif\" title=\"{$sLockStatus}\" alt=\"{$sLockStatus}\" border=\"0\"></a>";
            } else {
                $sLockStatus = i18n('Freeze article');
                $lockArticle = "<a href=\"main.php?area=con&idcat=$idcat&action=con_lock&frame=4&idart=$idart&contenido=$sSession\" title=\"{$sLockStatus}\"><img src=\"images/lock_open.gif\" title=\"{$sLockStatus}\" alt=\"{$sLockStatus}\" border=\"0\"></a>";
            }

            // Templatename
            if (!empty($templatename)) {
                $sTemplateName = conHtmlentities($templatename);
            } else {
                $db2 = cRegistry::getDb();
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
                $sTemplateName = $db2->f("name") ? '<i>' . $db2->f("name") . '</i>' : "--- " . i18n("None") . " ---";
            }

            $sTodoListSubject = i18n("Reminder");
            $sReminder = i18n("Set reminder / add to todo list");
            $sDuplicateArticle = i18n("Duplicate article");
            $sArticleProperty = i18n("Article properties");
            $sConfigureTpl = i18n("Configure template");
            $sDeleteArticle = i18n("Delete article");
            $sDeleteArticleQuestion = i18n("Do you really want to delete the following article");
            $sRowId = "$idart-$idartlang-$idcat-0-$idcatart-$iLangId";

            if ($i == 0) {
                $tpl->set('s', 'FIRST_ROWID', $sRowId);
            }

            $categoryHelper = cCategoryHelper::getInstance();
            $catArt = new cApiCategoryArticle($idcatart);
            $catArray = $categoryHelper->getCategoryPath($catArt->get("idcat"));
            $catstring = "";
            foreach ($catArray as $cat) {
                $catstring .= $cat->get("name") . "-> ";
            }
            if (strlen($catstring) > 0) {
                $catstring = substr($catstring, 0, strlen($catstring) - 3);
            }

            $strTitle = cSecurity::unFilter($db->f("title"));
            
            if ($idcat == '') {
            	$idcat = 0;
            }

            if ($perm->have_perm_area_action_item("con_editcontent", "con_editart", $idcat)) {
                $editart = "<a href=\"main.php?area=con_editcontent&action=con_editart&changeview=edit&idartlang=$idartlang&idart=$idart&idcat=$idcat&frame=4&contenido=$sSession\" title=\"idart: $idart idcatart: $idcatart\" alt=\"idart: $idart idcatart: $idcatart\"><i><span style='font-size: 80%'>" . $catstring . "</span></i><br>" . $strTitle . "</a>";
            } else {
                $editart = "<i><span style='font-size: 80%'>" . $catstring . "</span></i><br>" . $strTitle;
            }

            if ($perm->have_perm_area_action_item("con", "con_duplicate", $idcat)) {
                $duplicate = "<a href=\"main.php?area=con&idcat=$idcat&action=con_duplicate&duplicate=$idart&frame=4&contenido=$sSession\" title=\"$sDuplicateArticle\"><img src=\"images/but_copy.gif\" border=\"0\" title=\"$sDuplicateArticle\" alt=\"$sDuplicateArticle\"></a>";
            } else {
                $duplicate = "";
            }

            if ($perm->have_perm_area_action_item("con", "con_deleteart", $idcat)) {
                $tmp_title = conHtmlSpecialChars($db->f("title"));
                if (strlen($tmp_title) > 30) {
                    $tmp_title = substr($tmp_title, 0, 27) . "...";
                }

                $delete = '
                <a
                    href="javascript:void(0)"
                    onclick="Con.showConfirmation(&quot;' . $sDeleteArticleQuestion . ':<br><br><b>' . conHtmlSpecialChars($tmp_title) . '</b>&quot;, function() {deleteArticle(' . $idart . ', ' . $idcat . ');});"
                    title="' . $sDeleteArticle . '"
                >
                    <img src="images/delete.gif" title="' . $sDeleteArticle . '" alt="' . $sDeleteArticle . '">
                </a>';
            } else {
                $delete = "";
            }

            $sRow = '<tr id="' . $sRowId . '" class="text_medium" onmouseover="artRow.over(this)" onmouseout="artRow.out(this)" onclick="artRow.click(this)">' . "\n";
            $sRow .= $makeStartarticle . "\n";
            $sRow .= "<td nowrap=\"nowrap\" class=\"bordercell\">$editart</td>
                      <td nowrap=\"nowrap\" class=\"bordercell\">$lastmodified</td>
                      <td nowrap=\"nowrap\" class=\"bordercell\">$published</td>
                      <td nowrap=\"nowrap\" class=\"bordercell\">" . $db->f("artsort") . "</td>
                      <td nowrap=\"nowrap\" class=\"bordercell\">$sTemplateName</td>
                      <td nowrap=\"nowrap\" class=\"bordercell\">
                          <a id=\"m1\" onclick=\"javascript:window.open('main.php?subject=$sTodoListSubject&amp;area=todo&amp;frame=1&amp;itemtype=idart&amp;itemid=$idart&amp;contenido=$sSession', 'todo', 'scrollbars=yes, height=300, width=625');\" alt=\"$sReminder\" title=\"$sReminder\" href=\"#\"><img id=\"m2\" alt=\"$sReminder\" src=\"images/but_setreminder.gif\" border=\"0\"></a>
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
        $sRow = '<tr><td colspan="7" class="bordercell">' . $sNothingFound . '</td></tr>';
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
    $searchForm = '<form id="save_search" target="right_bottom" method="post" action="backend_search.php">';
    // Meta for CONTENIDO
    $searchForm .= '<input type="hidden" name="area" value="' . $area . '">';
    $searchForm .= '<input type="hidden" name="frame" value="' . $frame . '">';
    $searchForm .= '<input type="hidden" name="contenido" value="' . $sess->id . '">';
    $searchForm .= '<input type="hidden" name="speach" value="' . $lang . '">';
    // Form data for saving current search
    $searchForm .= '<input type="hidden" name="save_search" id="save_search" value="true">';
    $searchForm .= '<input type="hidden" name="' . $sSaveTitle . '" id="' . $sSaveTitle . '" value="' . $sSearchStr . '">';
    $searchForm .= '<input type="hidden" name="' . $sSaveId . '" id="' . $sSaveId . '" value="' . $iSearchId . '">';
    $searchForm .= '<input type="hidden" name="' . $sSaveDateFrom . '" id="' . $sSaveDateFrom . '" value="' . $sSearchStrDateFrom . '">';
    $searchForm .= '<input type="hidden" name="' . $sSaveDateTo . '" id="' . $sSaveDateTo . '" value="' . $sSearchStrDateTo . '">';
    $searchForm .= '<input type="hidden" name="' . $sSaveDateField . '" id="' . $sSaveDateField . '" value="' . $sDateFieldName . '">';
    $searchForm .= '<input type="hidden" name="' . $sSaveAuthor . '" id="' . $sSaveAuthor . '" value="' . $sSearchStrAuthor . '">';
    $searchForm .= '<label for="' . $sSaveName . '">' . i18n("Search name") . ': </label>';
    $searchForm .= '<input type="text" class="text_medium" name="' . $sSaveName . '" id="' . $sSaveName . '" placeholder="' . i18n("The search") . '" class="vAlignMiddle">';
    $searchForm .= '<input type="image" class="vAlignMiddle tableElement" src="./images/but_ok.gif" alt="' . i18n('Store') . '" title="' . i18n('Store') . '" value="' . i18n('Store') . '" name="submit">';
    $searchForm .= '</form>';
    $tpl->set('s', 'STORESEARCHFORM', $searchForm);

    // Title / Header for 'store the search' form
    $tpl->set('s', 'STORESEARCHINFO', i18n("Save this search"));
} else {
    $tpl->set('s', 'STORESEARCHINFO', '');
    $tpl->set('s', 'STORESEARCHFORM', '');
}

$tpl->set('s', 'SUBNAVI', $sLoadSubnavi);
sendEncodingHeader($db, $cfg, $lang);
$tpl->generate($cfg['path']['templates'] . 'template.backend_search_results.html');
