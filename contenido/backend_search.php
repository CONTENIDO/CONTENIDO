<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Contenido main file
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend
 * @version    1.0.5
 * @author     Holger Librenz, Andreas Lindner
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created  2007-04-20
 *   modified 2008-06-15, Rudi Bieller, Bugfix CON-149
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2008-06-27, Timo.Trautmann, Encoding Header added
 *   modified 2008-07-02, Frederic Schneider, querys escaped and include security class
 *   modified 2008-09-08, Oliver Lohkemper, Fixed: "Fatal error: Class 'PropertyCollection' not found"
 *   modified 2010-05-20, Murat Purc, standardized Contenido startup and security check invocations, see [#CON-307]
 *
 *   $Id: backend_search.php 1157 2010-05-20 14:10:43Z xmurrix $:
 * }}
 *
 */

/*
 * 16.01.2008
 * Thorsten Granz
 * Added 'store search' function
 * Added showinng values of search in searchform (Timo Trautmann)
 */

if (!defined("CON_FRAMEWORK")) {
    define("CON_FRAMEWORK", true);
}

// Contenido startup process
include_once (dirname(__FILE__) . '/includes/startup.php');

page_open(array('sess' => 'Contenido_Session',
	'auth' => 'Contenido_Challenge_Crypt_Auth',
	'perm' => 'Contenido_Perm'));

i18nInit($cfg["path"]["contenido"].$cfg["path"]["locale"], $belang);

# Variablen initialisieren
$db = new DB_Contenido;
$db2 = new DB_Contenido;

// Session
$sSession = '';
$sSession_tmp = '';
// Session 'Anhang' fuer URLs
$sSessionAppend = '';

// SprachID
$iSpeachID = $lang;
$iSpeachID_tmp = NULL;

// Suche - ID
$iSearchID = NULL;
$iSearchID_tmp = 0;

// Suche - Text
$sSearchStr = NULL;
$sSearchStr_tmp = '';

// Suche - Date type
$sSearchStrDateType = NULL;
$sSearchStrDateType_tmp = '';

// Suche - Date from
$sSearchStrDateFrom = NULL;
$sSearchStrDateFrom_tmp = '';

// Suche - Date to
$sSearchStrDateTo = NULL;
$sSearchStrDateTo_tmp = '';

$where = '';

// SprachID ermitteln
$iLangID = ((int) $lang > 0?(int) $lang:1);

// effektive Einstellung fuer Zeitdarstellung holen
$dateformat = getEffectiveSetting("backend", "timeformat_date", "Y-m-d");

// fuer das Initialiseren der Sub-Navi benoetigte Werte
$sLoadSubnavi = '';
$iIDCat = 0;
$iIDTpl = 0;

// Session- und Sprachdaten aus Formularanfrage sichern
if (isset($_POST[$sess->name])) {
    $sSession_tmp = trim (strip_tags ($_POST[$sess->name]));
} elseif (isset($_GET[$sess->name])) {
    $sSession_tmp = trim (strip_tags ($_GET[$sess->name]));
}

if (strlen($sSession_tmp) > 0) {
    $sSession = $sSession_tmp;
}

if (isset($_POST['speach'])) {
    $iSpeachID_tmp = (int) $_POST['speach'];
    if ((string) $iSpeachID_tmp === $_POST['speach']) {
        $iSpeachID = $iSpeachID_tmp;
    }
}
if( !empty($sSession) ) {
    //Backend
    page_open(array (
        'sess' => 'Contenido_Session',
        'auth' => 'Contenido_Challenge_Crypt_Auth',
        'perm' => 'Contenido_Perm'
	));
    i18nInit($cfg["path"]["contenido"].$cfg["path"]["locale"], $belang);
} else {
    //Frontend
    page_open(array ('sess' => 'Contenido_Frontend_Session', 'auth' => 'Contenido_Frontend_Challenge_Crypt_Auth', 'perm' => 'Contenido_Perm'));
}

/*
 * SAVE SEARCH
 * Some orientation info:
 * 1. User is calling a stored search -> fetch search values from con_properties and put them in PHP variables used for searching
 * 2. User has entered some search values -> standard search in DB
 * 3. User pressed 'save search' -> show 'successfully stored' message & use the stored search id to show the result again
 */

$save_title = 'save_title';
$save_id = 'save_id';
$save_date_from = 'save_date_from';
$save_date_from_year = 'save_date_from_year';
$save_date_from_month = 'save_date_from_month';
$save_date_from_day = 'save_date_from_day';
$save_date_to = 'save_date_to';
$save_date_to_year = 'save_date_to_year';
$save_date_to_month = 'save_date_to_month';
$save_date_to_day = 'save_date_to_day';
$save_date_field = 'save_date_field';
$save_author = 'save_author';
$save_name = 'save_name';
$type = 'savedsearch';  // section for saved searches in con_properties
$refreshScript = '';		// refresh top left frame
$saveSuccessfull = '';	// Sucessfully stored message

/* Function for generating refresh JavaScript for form in left_top */
function generateJs ($aValues) {
    if (is_array($aValues)) {
        global $save_title;
        global $save_id;
        global $save_date_from_year;
        global $save_date_from_month;
        global $save_date_from_day;
        global $save_date_to_year;
        global $save_date_to_month;
        global $save_date_to_day;
        global $save_date_field;
        global $save_author;
        global $save_name;

        return 'function refresh_article_search_form (refresh) {
                    var oFrame = top.content.left.left_top;
                    if (oFrame) {
                        oForm = oFrame.document.backend_search;

                        oForm.bs_search_text.value = "'.$aValues[$save_title].'";
                        oForm.bs_search_id.value = "'.$aValues[$save_id].'";
                        oForm.bs_search_date_type.value = "'.$aValues[$save_date_field].'";

                        oFrame.toggle_tr_visibility("tr_date_from");
                        oFrame.toggle_tr_visibility("tr_date_to");

                        oForm.bs_search_date_from_day.value = "'.$aValues[$save_date_from_day].'";
                        oForm.bs_search_date_from_month.value = "'.$aValues[$save_date_to_month].'";
                        oForm.bs_search_date_from_year.value = "'.$aValues[$save_date_from_year].'";

                        oForm.bs_search_date_to_day.value = "'.$aValues[$save_date_to_day].'";
                        oForm.bs_search_date_to_month.value = "'.$aValues[$save_date_to_month].'";
                        oForm.bs_search_date_to_year.value = "'.$aValues[$save_date_to_year].'";

                        oForm.bs_search_author.value = "'.$aValues[$save_author].'";
                    }
                }
                refresh_article_search_form ();
                ';
    } else {
        return false;
    }
}

/** Function masks string for inserting into SQL statement
 *
 * @param string $sString
 * @return string
 */
function mask ($sString) {
    $sString = str_replace('\\', '\\\\', $sString);
    $sString = str_replace('\'', '\\\'', $sString);
    $sString = str_replace('"', '\\"', $sString);
    return $sString;
}

$sScript = '';

/* Searches in generic db for a - Search */
function getSearchResults($itemidReq, $itemtypeReq)
{
	global $save_title;
	global $save_id;
	global $save_date_from;
	global $save_date_from_year;
	global $save_date_from_month;
	global $save_date_from_day;
	global $save_date_to;
	global $save_date_to_year;
	global $save_date_to_month;
	global $save_date_to_day;
	global $save_date_field;
	global $save_author;
	global $save_name;
	global $type;

	$retValue = array();
	// Request from DB
	$propertyCollection = new PropertyCollection;
	$results = $propertyCollection->getValuesByType($itemtypeReq, $itemidReq, $type);

	// Put results in returning Array
	$retValue[$save_title] = $results[$save_title];
	$retValue[$save_id] = $results[$save_id];
	$retValue[$save_date_field] = $results[$save_date_field];
	$retValue[$save_author] = $results[$save_author];

	// Date from
	$sSearchStrDateFromDay_tmp = 0;
	$sSearchStrDateFromMonth_tmp = 0;
	$sSearchStrDateFromYear_tmp = 0;
	$saveDateFrom = $results[$save_date_from];
	if( isset($saveDateFrom) && sizeof($saveDateFrom)>0 )
	{
        $saveDateFrom = str_replace(' 00:00:00', '', $saveDateFrom);
		$saveDateFromParts = explode('-', $saveDateFrom);
		if(sizeof($saveDateFromParts) == 3)
		{
			$retValue[$save_date_from_year] = $saveDateFromParts[0];
			$retValue[$save_date_from_month] = $saveDateFromParts[1];
			$retValue[$save_date_from_day] = $saveDateFromParts[2];
		}
	}
	// Date to
	$sSearchStrDateToDay_tmp = 0;
	$sSearchStrDateToMonth_tmp = 0;
	$sSearchStrDateToYear_tmp = 0;
	$saveDateTo = $results[$save_date_to];
	if( isset($saveDateTo) && sizeof($saveDateTo)>0 )
	{
        $saveDateTo = str_replace(' 23:59:59', '', $saveDateTo);
		$saveDateToParts = explode('-', $saveDateTo);
		if(sizeof($saveDateToParts) == 3)
		{
			$retValue[$save_date_to_year] = $saveDateToParts[0];
			$retValue[$save_date_to_month] = $saveDateToParts[1];
			$retValue[$save_date_to_day] = $saveDateToParts[2];
		}
	}
	return $retValue;
}

// SAVE CURRENT SEARCH
if( sizeof($_GET) == 0 && isset($_POST['save_search']) )
{
	$itemtype = rand(0,10000);
	$itemid = time();
	$propertyCollection = new PropertyCollection;

	/**
	 * Getting values from POST and storing them to DB
	 * no checking for consistency done here because these values have already been checked when
	 * building form sending this POST
	 */

	// Title / Content
	$propertyCollection->setValue($itemtype, $itemid, $type, $save_title, $_POST[$save_title]);
	// ID
	$propertyCollection->setValue($itemtype, $itemid, $type, $save_id, $_POST[$save_id]);
	// Date from
	$propertyCollection->setValue($itemtype, $itemid, $type, $save_date_from, $_POST[$save_date_from]);
	// Date to
	$propertyCollection->setValue($itemtype, $itemid, $type, $save_date_to, $_POST[$save_date_to]);
	// Date type
	$propertyCollection->setValue($itemtype, $itemid, $type, $save_date_field, $_POST[$save_date_field]);
	// Author
	$propertyCollection->setValue($itemtype, $itemid, $type, $save_author, $_POST[$save_author]);
	// Name of search (displayed to user)
	$propertyCollection->setValue($itemtype, $itemid, $type, $save_name, $_POST[$save_name]);

	// Call search we justed saved to show results
	$searchResults = getSearchResults($itemid, $itemtype);
	$sSearchStr_tmp = $searchResults[$save_title];
	$iSearchID_tmp = $searchResults[$save_id];
	$sSearchStrDateType_tmp = $searchResults[$save_date_field];
	$sSearchStrDateFromDay_tmp = $searchResults[$save_date_from_day];
	$sSearchStrDateFromMonth_tmp = $searchResults[$save_date_from_month];
	$sSearchStrDateFromYear_tmp = $searchResults[$save_date_from_year];
	$sSearchStrDateToDay_tmp = $searchResults[$save_date_to_day];
	$sSearchStrDateToMonth_tmp = $searchResults[$save_date_to_month];
	$sSearchStrDateToYear_tmp = $searchResults[$save_date_to_year];
	$sSearchStrAuthor_tmp = $searchResults[$save_author];

    $sScript = generateJs($searchResults);

	// Reload top left to show new search name
	$refreshScript .= 'top.content.left.left_top.location.href = top.content.left.left_top.location.href+"&save_search=true";';

	// Message for successfull saving
	$saveSuccessfull = i18n("Thank you for saving this search from extinction!");
}
// STORED SEARCH HAS BEEN CALLED
elseif( sizeof($_GET) > 0)
{
	$itemtypeReq = $_GET['itemtype'];
	$itemidReq = $_GET['itemid'];
	// Do we have the request parameters we need to fetch search values of stored search ?
	if( (isset($itemtypeReq) && strlen($itemtypeReq)>0) &&
			(isset($itemidReq) && strlen($itemidReq)>0)
		)
	{
		$searchResults = getSearchResults($itemidReq, $itemtypeReq);
		$sSearchStr_tmp = $searchResults[$save_title];
		$iSearchID_tmp = $searchResults[$save_id];
		$sSearchStrDateType_tmp = $searchResults[$save_date_field];
		$sSearchStrDateFromDay_tmp = $searchResults[$save_date_from_day];
		$sSearchStrDateFromMonth_tmp = $searchResults[$save_date_from_month];
		$sSearchStrDateFromYear_tmp = $searchResults[$save_date_from_year];
		$sSearchStrDateToDay_tmp = $searchResults[$save_date_to_day];
		$sSearchStrDateToMonth_tmp = $searchResults[$save_date_to_month];
		$sSearchStrDateToYear_tmp = $searchResults[$save_date_to_year];
		$sSearchStrAuthor_tmp = $searchResults[$save_author];

        $sSearchStrDateFrom_tmp = $searchResults[$save_date_from];
        $sSearchStrDateTo_tmp = $searchResults[$save_date_to];

        #script for refreshing search form with stored search options
        $sScript = generateJs($searchResults);

	}
	elseif( isset($_GET['recentedit']) )
	{
		// compute current day minus one week
		$actDate = time();
		$weekInSeconds = 60 * 60 * 24 * 7;  // seconds, minutes, hours, days
		$oneWeekEarlier = $actDate - $weekInSeconds;

		$sSearchStrDateType_tmp = 'lastmodified';
		$sSearchStrDateFromDay_tmp = date('d', $oneWeekEarlier);
		$sSearchStrDateFromMonth_tmp = date('m', $oneWeekEarlier);
		$sSearchStrDateFromYear_tmp = date('Y', $oneWeekEarlier);
		$sSearchStrDateToDay_tmp = date('d', $actDate);
		$sSearchStrDateToMonth_tmp = date('m', $actDate);
		$sSearchStrDateToYear_tmp = date('Y', $actDate);
	}
	elseif( isset($_GET['myarticles']) )
	{
		$sSearchStrAuthor_tmp = $auth->auth['uname'];
	}
}
// STANDARD SEARCH
elseif( sizeof($_GET) == 0 && isset($_POST) )
{
	$sSearchStr_tmp = trim (strip_tags ($_POST['bs_search_text']));
	$iSearchID_tmp = (int) $_POST['bs_search_id'];
	$sSearchStrDateType_tmp = trim (strip_tags ($_POST['bs_search_date_type']));
	$sSearchStrDateFromDay_tmp = (int) trim (strip_tags ($_POST['bs_search_date_from_day']));
	$sSearchStrDateFromMonth_tmp = (int) trim (strip_tags ($_POST['bs_search_date_from_month']));
	$sSearchStrDateFromYear_tmp = (int) trim (strip_tags ($_POST['bs_search_date_from_year']));
	$sSearchStrDateToDay_tmp = (int) trim (strip_tags ($_POST['bs_search_date_to_day']));
	$sSearchStrDateToMonth_tmp = (int) trim (strip_tags ($_POST['bs_search_date_to_month']));
	$sSearchStrDateToYear_tmp = (int) trim (strip_tags ($_POST['bs_search_date_to_year']));
	$sSearchStrAuthor_tmp = trim (strip_tags ($_POST['bs_search_author']));
}
// else ERROR
// No code here, empty results caught later in code

// Title / Content
if (!empty($sSearchStr_tmp)) {
    $sSearchStr = $sSearchStr_tmp;
}
// Article ID
if ($iSearchID_tmp > 0) {
    $iSearchID = $iSearchID_tmp;
}
// Date
if ($sSearchStrDateType_tmp != 'n/a') {
	if (($sSearchStrDateFromDay_tmp > 0) && ($sSearchStrDateFromMonth_tmp > 0) && ($sSearchStrDateFromYear_tmp > 0)) {
		$sSearchStrDateFrom = $sSearchStrDateFromYear_tmp.'-'.$sSearchStrDateFromMonth_tmp.'-'.$sSearchStrDateFromDay_tmp.' 00:00:00';
	} else {
		$sSearchStrDateFrom = '';
	}

	if (($sSearchStrDateToDay_tmp > 0) && ($sSearchStrDateToMonth_tmp > 0) && ($sSearchStrDateToYear_tmp > 0)) {
		$sSearchStrDateTo = $sSearchStrDateToYear_tmp.'-'.$sSearchStrDateToMonth_tmp.'-'.$sSearchStrDateToDay_tmp.' 23:59:59';
	} else {
		$sSearchStrDateTo = '';
	}

	$sDateFieldName = $sSearchStrDateType_tmp;
} else {
	$sDateFieldName = '';
}
// Author
if (!empty($sSearchStrAuthor_tmp)) {
    $sSearchStrAuthor = $sSearchStrAuthor_tmp;
}

# liest den gesuchten Artikel aus der Datenbank
$sql_1 = "SELECT
		  DISTINCT a.idart, a.idartlang, a.title, a.online, a.locked, a.idartlang, a.created, a.published,
		  a.artsort, a.lastmodified, b.idcat, b.idcatart, b.idcatart, c.startidartlang,
		  c.idcatlang, e.name as 'tplname'
		FROM ".$cfg['tab']['art_lang']." as a
		  LEFT JOIN ".$cfg['tab']['cat_art']." as b ON a.idart = b.idart
		  LEFT JOIN ".$cfg['tab']['cat_lang']." as c ON a.idartlang = c.startidartlang
		  LEFT JOIN ".$cfg['tab']['tpl_conf']." as d ON a.idtplcfg = d.idtplcfg
		  LEFT JOIN ".$cfg['tab']['tpl']." as e ON d.idtpl = e.`idtpl`
		  LEFT JOIN ".$cfg['tab']['content']." as f ON f.idartlang = a.idartlang
		WHERE
		  (a.idlang = ".Contenido_Security::toInteger($iSpeachID).")
		";

$where = "";

$bNoCriteria = true;

// Article ID
if (!empty($iSearchID)) {
    $where.= " AND (a.idart = " . Contenido_Security::toInteger($iSearchID) . ")";
	$bNoCriteria = false;
}

// es soll nach Text gesucht werden
if (!empty($sSearchStr)) {
    $where.= " AND ((a.title LIKE '%" . mask(Contenido_Security::escapeDB($sSearchStr, $db)) .  "%')";
    $where.= " OR (f.value LIKE '%" . mask(Contenido_Security::escapeDB($sSearchStr, $db)) .  "%'))";
	$bNoCriteria = false;
}

if (!empty($sSearchStrDateFrom) && ($sDateFieldName != '')) {
    $where.= " AND (a.".Contenido_Security::escapeDB($sDateFieldName, $db)." >= '".mask(Contenido_Security::escapeDB($sSearchStrDateFrom, $db))."')";
	$bNoCriteria = false;
}

if (!empty($sSearchStrDateTo) && ($sDateFieldName != '')) {
    $where.= " AND (a.".$sDateFieldName." <= '".mask(Contenido_Security::escapeDB($sSearchStrDateTo, $db))."')";
	$bNoCriteria = false;
}

if (!empty($sSearchStrAuthor) && ($sSearchStrAuthor != 'n/a')) {
    // es soll nach Autor gesucht werden
    $where.= " AND ((a.author = '" . mask(Contenido_Security::escapeDB($sSearchStrAuthor, $db)) .  "') OR (a.modifiedby = '" . mask(Contenido_Security::escapeDB($sSearchStrAuthor, $db))."'))";
	$bNoCriteria = false;
}

if (!empty($where)) {
    $sql_1 .= $where;
	$db->query($sql_1);
}

if (!empty($sSession)) {
    $sSessionAppend = '?contenido=' . $sSession;
}

/* Include Template Class */
include_once($cfg["path"]["contenido"] . 'classes/class.template.php');

$tpl = new Template();

$tpl->setEncoding('iso-8859-1');
$tpl->set('s', 'SESSID', $sSession);
$tpl->set('s', 'SCRIPT', $sScript);
$tpl->set('s', 'SESSNAME', $sess->name);
$tpl->set('s', 'TITLE', i18n("Search results"));
$tpl->set('s', 'TH_START', i18n("Article"));
$tpl->set('s', 'TH_TITLE', i18n("Title"));
$tpl->set('s', 'TH_CHANGED', i18n("Changed"));
$tpl->set('s', 'TH_PUBLISHED', i18n("Published"));
$tpl->set('s', 'TH_SORTORDER', i18n("Sort order"));
$tpl->set('s', 'TH_TEMPLATE', i18n("Template"));
$tpl->set('s', 'TH_ACTIONS', i18n("Actions"));

// Refresh top left frame
$tpl->set('s', 'REFRESH', $refreshScript);

// Successfully stored Message
$tpl->set('s', 'SEARCHSTOREDMESSAGE', $saveSuccessfull);

$iAffectedRows = $db->affected_rows();

if ($iAffectedRows <= 0 || empty($where)) {
    $sNoArticle = i18n("Missing search value.");
    $sNothingFound = i18n("No article found.");

    if( $bNoCriteria ) {
        $sErrOut = $sNoArticle;
    } else {
        $sErrOut = $sNothingFound;
    }

    $sRow = '<tr><td colspan="7" class="bordercell">' . $sErrOut . '</td></tr>';
    $tpl->set('d', 'ROWS', $sRow);
    $tpl->next();
} else {
	$bHit = false;

    for ($i = 0; $i < $iAffectedRows; $i++) {

        // reinitialisiere Hilfs-String
        $sRow = '';

	    $db->next_record();

		$idcat = $db->f("idcat");

		$check_rights = $perm->have_perm_area_action("con", "con_makestart");

		if (!$check_rights) {
			$check_rights = $perm->have_perm_area_action("con", "con_makeonline");
		}
		if (!$check_rights) {
			$check_rights = $perm->have_perm_area_action("con", "con_deleteart");
		}
		if (!$check_rights) {
			$check_rights = $perm->have_perm_area_action("con", "con_tplcfg_edit");
		}
		if (!$check_rights) {
			$check_rights = $perm->have_perm_area_action("con", "con_makecatonline");
		}
		if (!$check_rights) {
			$check_rights = $perm->have_perm_area_action("con", "con_changetemplate");
		}
		if (!$check_rights) {
			$check_rights = $perm->have_perm_area_action("con_editcontent", "con_editart");
		}
		if (!$check_rights) {
			$check_rights = $perm->have_perm_area_action("con_editart", "con_edit");
		}
		if (!$check_rights) {
			$check_rights = $perm->have_perm_area_action("con_editart", "con_newart");
		}
		if (!$check_rights) {
			$check_rights = $perm->have_perm_area_action("con_editart", "con_saveart");
		}

		#Check rights per cat
		if (!$check_rights) {
			//hotfix timo trautmann 2008-12-10 also check rights in associated groups
			$aGroupsForUser = $perm->getGroupsForUser($auth->auth[uid]);
			$aGroupsForUser[] = $auth->auth[uid];
			$sTmpUserString = implode("','", $aGroupsForUser);

			#Check if any rights are applied to current user or his groups
			$sql = "SELECT *
					FROM ".$cfg["tab"]["rights"]."
					WHERE user_id IN ('".$sTmpUserString."') AND idclient = '".Contenido_Security::toInteger($client)."' AND idlang = '".Contenido_Security::toInteger($lang)."' AND idcat = '".Contenido_Security::toInteger($idcat)."'";
			$db2->query($sql);

			if ($db2->num_rows() != 0) {

				if (!$check_rights) {
					$check_rights = $perm->have_perm_area_action_item("con", "con_makestart",$idcat);
				}
				if (!$check_rights) {
					$check_rights = $perm->have_perm_area_action_item("con", "con_makeonline",$idcat);
				}
				if (!$check_rights) {
					$check_rights = $perm->have_perm_area_action_item("con", "con_deleteart",$idcat);
				}
				if (!$check_rights) {
					$check_rights = $perm->have_perm_area_action_item("con", "con_tplcfg_edit",$idcat);
				}
				if (!$check_rights) {
					$check_rights = $perm->have_perm_area_action_item("con", "con_makecatonline",$idcat);
				}
				if (!$check_rights) {
					$check_rights = $perm->have_perm_area_action_item("con", "con_changetemplate",$idcat);
				}
				if (!$check_rights) {
					$check_rights = $perm->have_perm_area_action_item("con_editcontent", "con_editart",$idcat);
				}
				if (!$check_rights) {
					$check_rights = $perm->have_perm_area_action_item("con_editart", "con_edit",$idcat);
				}
				if (!$check_rights) {
					$check_rights = $perm->have_perm_area_action_item("con_editart", "con_newart",$idcat);
				}
				if (!$check_rights) {
					$check_rights = $perm->have_perm_area_action_item("con_editart", "con_saveart",$idcat);
				}
			}
		}

	    if ($check_rights) {
	    	$bHit = true;

		    $idart             = $db->f("idart");
		    $idartlang         = $db->f("idartlang");
		    $idcatart          = $db->f("idcatart");
		    $idcatlang         = $db->f("idcatlang");
		    $title             = $db->f("title");
		    $idartlang         = $db->f("idartlang");
		    $created           = date($dateformat, strtotime($db->f("created")));
		    $lastmodified      = date($dateformat, strtotime($db->f("lastmodified")));
		    $published         = date($dateformat, strtotime($db->f("published")));
		    $online            = $db->f("online");
		    $locked            = $db->f("locked");
		    $startidartlang    = $db->f("startidartlang");
		    $templatename      = $db->f("tplname");

		    // fuer den ersten gefundenen Artikel die Werte fuer CategoryID und TemplateID merken
	        if ($i == 0) {
	            $iIDCat = $idcat;
	            $iIDTpl = $idtpl;
	        }

		    /* Funktion zum umwandeln in Startartikel/normale Artikel*/
			if ($perm->have_perm_area_action_item("con", "con_makestart",$idcat) && 0 == 1) {
			    if( $startidartlang == $idartlang ) {
			        $sFlagTitle = i18n("Flag as normal article");
			        $makeStartarticle = "<td nowrap=\"nowrap\" class=\"bordercell\"><a href=\"main.php?area=con&idcat=$idcat&action=con_makestart&idcatart=$idcatart&frame=4&is_start=0&contenido=$sSession\" title=\"{$sFlagTitle}\"><img src=\"images/isstart1.gif\" border=\"0\" title=\"{$sFlagTitle}\" alt=\"{$sFlagTitle}\"></a></td>";
			    } else {
			        $sFlagTitle = i18n("Flag as start article");
			        $makeStartarticle = "<td nowrap=\"nowrap\" class=\"bordercell\"><a href=\"main.php?area=con&idcat=$idcat&action=con_makestart&idcatart=$idcatart&frame=4&is_start=1&contenido=$sSession\" title=\"{$sFlagTitle}\"><img src=\"images/isstart0.gif\" border=\"0\" title=\"{$sFlagTitle}\" alt=\"{$sFlagTitle}\"></a></td>";
			    }
			} else {
			    if( $startidartlang == $idartlang ) {
			        $makeStartarticle = "<td nowrap=\"nowrap\" class=\"bordercell\"><img src=\"images/isstart1.gif\" border=\"0\" title=\"{$sFlagTitle}\" alt=\"{$sFlagTitle}\"></td>";
				} else {
			    	$makeStartarticle = "<td nowrap=\"nowrap\" class=\"bordercell\"><img src=\"images/isstart0.gif\" border=\"0\" title=\"{$sFlagTitle}\" alt=\"{$sFlagTitle}\"></td>";
				}
			}

		    /* Funktion zum online/offline stellen */
		    if( $online==1 ) {
		        $sOnlineStatus = i18n("Make offline");
		        $bgColorRow = "background-color: #E2E2E2;";
		        $setOnOff = "<a href=\"main.php?area=con&idcat=$idcat&action=con_makeonline&frame=4&idart=$idart&contenido=$sSession\" title=\"{$sOnlineStatus}\"><img src=\"images/online.gif\" title=\"{$sOnlineStatus}\" alt=\"{$sOnlineStatus}\" border=\"0\"></a>";
		    } else {
		        $sOnlineStatus = i18n("Make online");
		        $bgColorRow = "background-color: #E2D9D9;";
		        $setOnOff = "<a href=\"main.php?area=con&idcat=$idcat&action=con_makeonline&frame=4&idart=$idart&contenido=$sSession\" title=\"{$sOnlineStatus}\"><img src=\"images/offline.gif\" title=\"{$sOnlineStatus}\" alt=\"{$sOnlineStatus}\" border=\"0\"></a>";
		    }
		    /* Funktion zum Artikel sperren/entsperren */
		    if( $locked==1 ) {
		        $sLockStatus = i18n("Unfreeze article");
		        $lockArticle = "<a href=\"main.php?area=con&idcat=$idcat&action=con_lock&frame=4&idart=$idart&contenido=$sSession\" title=\"{$sLockStatus}\"><img src=\"images/lock_closed.gif\" title=\"{$sLockStatus}\" alt=\"{$sLockStatus}\" border=\"0\"></a>";
		    } else {
		        $sLockStatus = i18n("Freeze article");
		        $lockArticle = "<a href=\"main.php?area=con&idcat=$idcat&action=con_lock&frame=4&idart=$idart&contenido=$sSession\" title=\"{$sLockStatus}\"><img src=\"images/lock_open.gif\" title=\"{$sLockStatus}\" alt=\"{$sLockStatus}\" border=\"0\"></a>";
		    }

		    /* Templatename */
		    if (!empty($templatename)) {
		        $sTemplateName = conHtmlentities($templatename);
		    } else {
		        $sTemplateName = '--- ' . i18n("None") . ' ---';
		    }

		    $todoListeSubject = i18n("Reminder");

		    $sReminder = i18n("Set reminder / add to todo list");
		    $sDuplicateArticle = i18n("Duplicate article");
		    $sArticleProperty = i18n("Article properties");
		    $sConfigureTpl = i18n("Configure template");
		    $sDeleteArticle = i18n("Delete article");
		    $sDeleteArticleQuestion = i18n("Do you really want to delete following article");

		    $sRowId = "$idart-$idartlang-$idcat-0-$idcatart-$iLangID";

            if ($i == 0) {
                $tpl->set('s', 'FIRST_ROWID', $sRowId);
            }

		    if ($online == 1 OR ($i % 2 == 1)) {
		        $bgColorRow = '#E2E2E2';
		    } else {
		        $bgColorRow = '#E2E2E2';
		    }

			if ($perm->have_perm_area_action_item("con_editcontent", "con_editart",$idcat)) {
				$editart = "<a href=\"main.php?area=con_editcontent&action=con_editart&changeview=edit&idartlang=$idartlang&idart=$idart&idcat=$idcat&frame=4&contenido=$sSession\" title=\"idart: $idart idcatart: $idcatart\" alt=\"idart: $idart idcatart: $idcatart\">".$db->f("title")."</a>";
			} else {
				$editart = $db->f("title");
			}

			if ($perm->have_perm_area_action_item("con", "con_duplicate",$idcat)) {
				$duplicate = "<a href=\"main.php?area=con&idcat=$idcat&action=con_duplicate&duplicate=$idart&frame=4&contenido=$sSession\" title=\"$sDuplicateArticle\"><img src=\"images/but_copy.gif\" border=\"0\" title=\"$sDuplicateArticle\" alt=\"$sDuplicateArticle\"></a>";
			} else {
				$duplicate = "";
			}

		    if ($perm->have_perm_area_action_item("con", "con_deleteart",$idcat)) {
				$delete = "<a href=\"javascript://\" onclick=\"box.confirm(&quot;$sDeleteArticle&quot;, &quot;$sDeleteArticleQuestion:<br><br><b>$db->f('title')</b>&quot;, &quot;deleteArticle($idart,$idcat)&quot;)\" title=\"$sDeleteArticle\"><img src=\"images/delete.gif\" title=\"$sDeleteArticle\" alt=\"$sDeleteArticle\" border=\"0\"></a>";
		    }else {
		    	$delete = "";
		    }

			$sRow = '<tr id="' . $sRowId . '" class="text_medium" style="' . $bgColorRow . '" onmouseover="artRow.over(this)" onmouseout="artRow.out(this)" onclick="artRow.click(this)">' . "\n";
		    $sRow .= $makeStartarticle . "\n";
		    $sRow .= 	"<td nowrap=\"nowrap\" class=\"bordercell\">$editart</td>
						<td nowrap=\"nowrap\" class=\"bordercell\">$lastmodified</td>
						<td nowrap=\"nowrap\" class=\"bordercell\">$published</td>
						<td nowrap=\"nowrap\" class=\"bordercell\">".$db->f("artsort")."</td>
						<td nowrap=\"nowrap\" class=\"bordercell\">$sTemplateName</td>
						<td nowrap=\"nowrap\" class=\"bordercell\">
							<a id=\"m1\" onclick=\"javascript:window.open('main.php?subject=$todoListeSubject&amp;area=todo&amp;frame=1&amp;itemtype=idart&amp;itemid=$idart&amp;contenido=$sSession', 'todo', 'scrollbars=yes, height=300, width=550');\" alt=\"$sReminder\" title=\"$sReminder\" href=\"#\"><img id=\"m2\" style=\"padding-left: 2px; padding-right: 2px;\" alt=\"$sReminder\" src=\"images/but_setreminder.gif\" border=\"0\"></a>
							$properties
							$tplconfig
							$duplicate
							$delete
						</td>
					</tr>";

	        $tpl->set('d', 'ROWS', $sRow);
	        $tpl->next();
	    } #if
	} #for

    if (!$bHit) {

	    $sNothingFound = i18n("No article found.");

	    $sRow = '<tr><td colspan="7" class="bordercell">' . $sNothingFound . '</td></tr>';
	    $tpl->set('d', 'ROWS', $sRow);
	    $tpl->next();
    }

	$sLoadSubnavi = 'parent.parent.frames["right"].frames["right_top"].location.href = \'main.php?area=con&frame=3&idcat=' . $iIDCat . '&idtpl=' . $iIDTpl . '&contenido=' . $sSession . "';";
} #if


###########################
# Save Search Parameters
###########################

if( sizeof($_GET) == 0 && isset($_POST) ) {
    // Build form with hidden fields that contain all search parameters to be stored using generic db
    $searchForm = '<form id="save_search" target="right_bottom" method="post" action="backend_search.php">';
    // Meta for Contenido
    $searchForm .= '<input type="hidden" name="area" value="'.$area.'">';
    $searchForm .= '<input type="hidden" name="frame" value="'.$frame.'">';
    $searchForm .= '<input type="hidden" name="contenido" value="'.$sess->id.'">';
    $searchForm .= '<input type="hidden" name="speach" value="'.$lang.'">';
    // Form data for saving current search
    $searchForm .= '<input type="hidden" name="save_search" id="save_search" value="true">';
    $searchForm .= '<input type="hidden" name="'.$save_title.'" id="'.$save_title.'" value="'.$sSearchStr.'">';
    $searchForm .= '<input type="hidden" name="'.$save_id.'" id="'.$save_id.'" value="'.$iSearchID.'">';
    $searchForm .= '<input type="hidden" name="'.$save_date_from.'" id="'.$save_date_from.'" value="'.$sSearchStrDateFrom.'">';
    $searchForm .= '<input type="hidden" name="'.$save_date_to.'" id="'.$save_date_to.'" value="'.$sSearchStrDateTo.'">';
    $searchForm .= '<input type="hidden" name="'.$save_date_field.'" id="'.$save_date_field.'" value="'.$sDateFieldName.'">';
    $searchForm .= '<input type="hidden" name="'.$save_author.'" id="'.$save_author.'" value="'.$sSearchStrAuthor.'">';
    $searchForm .= '<label for="save_searchname">'.i18n("Search Name").': </label>';
    $searchForm .= '<input type="text" class="text_medium" name="'.$save_name.'" id="'.$save_name.'" value="Die Suche" style="vertical-align:middle;">';
    $searchForm .= '<input type="image" style="margin-left: 5px; vertical-align: middle;" src="./images/but_ok.gif" alt="'.i18n("Store").'" title="'.i18n("Store").'" value="'.i18n("Store").'" name="submit">';
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
?>