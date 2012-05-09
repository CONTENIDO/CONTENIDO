<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Defines the "str" related functions
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.3.21
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2002-03-02
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.database.php');

global $db_str;
global $db_str2;

if (class_exists('DB_Contenido')) {
    $db_str = new DB_Contenido();
    $db_str2 = new DB_Contenido();
}


/**
 * Creates a new category tree (root category item).
 *
 * @param   string  $catname     The category name
 * @param   string  $catalias    Alias of category
 * @param   int     $visible     Flag about visible status
 * @param   int     $public      Flag about public status
 * @param   int     $iIdtplcfg   Id of template configuration
 * @return  (int|void)  Id of new generated category or nothing on failure
 */
function strNewTree($catname, $catalias = '', $visible = 0, $public = 1, $iIdtplcfg = 0)
{
    global $client, $lang, $perm;

    // Flag to rebuild the category table
    global $remakeCatTable, $remakeStrTable;

    if (trim($catname) == '') {
        return;
    }

    $remakeCatTable = true;
    $remakeStrTable = true;

    $catalias = trim($catalias);
    if ($catalias == '') {
        $catalias = trim($catname);
    }

    $client = (int) $client;
    $lang = (int) $lang;

    $visible = ($visible == 1) ? 1 : 0;
    if (!$perm->have_perm_area_action('str', 'str_makevisible')) {
        $visible = 0;
    }

    $public = ($public == 1) ? 1 : 0;
    if (!$perm->have_perm_area_action('str', 'str_makepublic')) {
        $public = 1;
    }

    // Get last category tree
    $oCatColl = new cApiCategoryCollection();
    $oLastCatTree = $oCatColl->fetchLastCategoryTree($client);
    $lastCatTreeId = (is_object($oLastCatTree)) ? $oLastCatTree->get('idcat') : 0;

    // Insert new category tree
    $oCatColl2 = new cApiCategoryCollection();
    $oNewCat = $oCatColl2->create($client, 0, $lastCatTreeId, 0);
    $newIdcat = $oNewCat->get('idcat');

    // Update last category tree
    if (is_object($oLastCatTree)) {
        $oLastCatTree->set('postid', $newIdcat);
        $oLastCatTree->store();
    }

    cInclude('includes', 'functions.rights.php');

    // Loop through languages
    $aLanguages = array($lang);
    foreach ($aLanguages as $curLang) {
        $name = htmlspecialchars($catname, ENT_QUOTES);
        $urlname = htmlspecialchars(capiStrCleanURLCharacters($catalias), ENT_QUOTES);

        // Insert new category language entry
        $oCatLangColl = new cApiCategoryLanguageCollection();
        $oCatLangColl->create($newIdcat, $curLang, $name, $urlname, '', 0, $visible, $public, 0, '', 0);

        // Set correct rights for element
        createRightsForElement('str', $newIdcat, $curLang);
        createRightsForElement('con', $newIdcat, $curLang);
    }

    // Assign template
    strAssignTemplate($newIdcat, $client, $iIdtplcfg);

    return $newIdcat;
}


/**
 * Creates a new category.
 *
 * @param   int     $parentid    Id of parent category
 * @param   string  $catname     The category name
 * @param   bool    $remakeTree  Flag to rebuild category tree structure
 * @param   string  $catalias    Alias of category
 * @param   int     $visible     Flag about visible status
 * @param   int     $public      Flag about public status
 * @param   int     $iIdtplcfg   Id of template configuration
 * @return  (int|void)  Id of new generated category or nothing on failure
 */
function strNewCategory($parentid, $catname, $remakeTree = true, $catalias = '', $visible = 0, $public = 1, $iIdtplcfg = 0)
{
    global $client, $lang, $perm;

    // Flag to rebuild the category table
    global $remakeCatTable, $remakeStrTable;

    if (trim($catname) == '') {
        return;
    }

    $remakeCatTable = true;
    $remakeStrTable = true;

    $catalias = trim($catalias);
    if ($catalias == '') {
        $catalias = trim($catname);
    }

    $client = (int) $client;
    $lang = (int) $lang;

    $visible = ($visible == 1) ? 1 : 0;
    if (!$perm->have_perm_area_action('str', 'str_makevisible')) {
        $visible = 0;
    }

    $public = ($public == 1) ? 1 : 0;
    if (!$perm->have_perm_area_action('str', 'str_makepublic')) {
        $public = 1;
    }

    // Get previous category on same level, if exists
    $oCatColl = new cApiCategoryCollection();
    $oCatColl->select('parentid=' . (int) $parentid . ' AND postid=0');
    $oPrevCat = $oCatColl->next();
    $preIdcat = (is_object($oPrevCat)) ? $oPrevCat->get('idcat') : 0;

    // Insert new category tree
    $oCatColl2 = new cApiCategoryCollection();
    $oNewCat = $oCatColl2->create($client, $parentid, $preIdcat, 0);
    $newIdcat = $oNewCat->get('idcat');

    // Update previous category, if exists
    if (is_object($oPrevCat)) {
        $oPrevCat->set('postid', $newIdcat);
        $oPrevCat->set('lastmodified', date('Y-m-d H:i:s'));
        $oPrevCat->store();
    }

    cInclude('includes', 'functions.rights.php');

    // Loop through languages
    $aLanguages = array($lang);
    foreach ($aLanguages as $curLang) {
        $name = htmlspecialchars($catname, ENT_QUOTES);
        $urlname = htmlspecialchars(capiStrCleanURLCharacters($catalias), ENT_QUOTES);

        // Insert new category language entry
        $oCatLangColl = new cApiCategoryLanguageCollection();
        $oCatLangColl->create($newIdcat, $curLang, $name, $urlname, '', 0, $visible, $public, 0, '', 0);

        // Set correct rights for element
        copyRightsForElement('str', $parentid, $newIdcat, $curLang);
        copyRightsForElement('con', $parentid, $newIdcat, $curLang);
    }

    if ($remakeTree == true) {
        strRemakeTreeTable();
    }

    // Assign template
    strAssignTemplate($newIdcat, $client, $iIdtplcfg);

    return $newIdcat;
}


/**
 * Builds ordered post string for a passed category
 *
 * @param int  $idcat
 * @param string  $poststring
 * @return  string
 */
function strOrderedPostTreeList($idcat, $poststring)
{
    global $db, $cfg;

    $sql = "SELECT idcat FROM ".$cfg['tab']['cat']." WHERE parentid=0 AND preid='".Contenido_Security::toInteger($idcat)."' AND idcat!=0";

    $db->query($sql);
    if ($db->next_record()) {
        $tmp_idcat = $db->f("idcat");
        $poststring = $poststring . "," . $tmp_idcat;
        $poststring = strOrderedPostTreeList($tmp_idcat, $poststring);
    }

    return $poststring;
}


/**
 * Remakes the category tree structure in category tree table.
 *
 * @return  void
 */
function strRemakeTreeTable()
{
    global $db, $client, $lang, $cfg;

    // Flag to rebuild the category table
    global $remakeCatTable;
    global $remakeStrTable;

    $sql = "SELECT idcat FROM ".$cfg['tab']['cat']." WHERE idclient = ". (int) $client;
    $db->query($sql);
    $idcats = array();
    while ($db->next_record()) {
        $idcats[] = $db->f("idcat");
    }

    if (0 === count($idcats)) {
        // There are no categories to build the tree from!
        return;
    }

    $remakeCatTable = true;
    $remakeStrTable = true;

    $sql = "DELETE FROM ".$cfg['tab']['cat_tree']." WHERE idcat IN ('" . implode("', '", $idcats) . "')"; // empty 'cat_tree'-table
    $db->query($sql);

    $sql = "DELETE FROM ".$cfg['tab']['cat']." WHERE idcat=0";
    $db->query($sql);

    $sql = "DELETE FROM ".$cfg['tab']['cat_lang']." WHERE idcat=0";
    $db->query($sql);

    $sql = "SELECT idcat, parentid, preid, postid FROM ".$cfg['tab']['cat']." WHERE idclient = " . (int) $client . " ORDER BY parentid ASC, preid ASC, postid ASC";

    $db->query($sql);

    // build cat_tree
    $aCategories = array();
    while ($db->next_record()) {
        if ($db->f('parentid') == 0) {
            $aCategories[0][$db->f('idcat')] = array(
                'idcat' => $db->f('idcat'),
                'parentid' => $db->f('parentid'),
                'preid' => $db->f('preid'),
                'postid' => $db->f('postid')
            );
        } else {
            $aCategories[$db->f('parentid')][$db->f('idcat')] = array(
                'idcat' => $db->f('idcat'),
                'parentid' => $db->f('parentid'),
                'preid' => $db->f('preid'),
                'postid' => $db->f('postid')
            );
        }
    }

    // build INSERT statement
    $sInsertQuery = "INSERT INTO " . $cfg['tab']['cat_tree'] . " (idcat, level) VALUES ";
    $sInsertQuery = strBuildSqlValues($aCategories[0], $sInsertQuery, $aCategories);
    $sInsertQuery = rtrim($sInsertQuery, " ,");

    // lock db table and execute INSERT query
    $db->lock($cfg['tab']['cat_tree']);
    $db->query($sInsertQuery);
    $db->unlock($cfg['tab']['cat_tree']);
}

/**
 * @deprecated 2012-04-26 This function is not longer supported, use strSortPrePost() instead
 */
function sort_pre_post($arr)
{
    cDeprecated("This function is not longer supported, use strSortPrePost() instead");
    return strSortPrePost($arr);
}

/**
 * Sorts passed assoziative categories array.
 * @todo  Check logic, move sorting to db layer, if possible!
 * @param  array  $arr
 * @return array
 */
function strSortPrePost($arr)
{
    $firstElement = null;
    foreach ($arr as $row) {
        if ($row['preid'] == 0) {
            $firstElement = $row['idcat'];
        }
    }

    $curId = $firstElement;
    $array = array();

    // Test for inifinite loops in the category list (1=>2; 2=>1 || 1=>1)
    $checkedIds = array();
    foreach ($arr as $row) {
        if (in_array($row['postid'], $checkedIds) || $row['idcat'] == $row['postid']) {
//            die(i18n("A The list of categories is messed up. The order in the list creates an infinite loop. Check you database."));
            continue;
        }
        $checkedIds[] = $row['idcat'];
    }

    // Test for a last element in the category list
    $fine = false;
    foreach ($arr as $row) {
        if ($row['postid'] == 0) {
            $fine = true;
            break;
        }
    }
    if (!$fine) {
        die(i18n("B The list of categories is messed up. The order in the list creates an infinite loop. Check you database."));
    }

    while ($curId != 0) {
        $array[] = $arr[$curId];
        $curId = $arr[$curId]['postid'];
    }

    return $array;
}


/**
 * @deprecated 2012-04-26 This function is not longer supported, use strBuildSqlValues() instead
 */
function recCats($aCats, $sInsertQuery, &$aAllCats, $iLevel = 0)
{
    cDeprecated("This function is not longer supported, use strBuildSqlValues() instead");
    return strBuildSqlValues($aCats, $sInsertQuery, $aAllCats, $iLevel = 0);
}

/**
 * Builds values part of the SQL used to recreate the category tree table
 *
 * @param  array|??  $aCats  Assoziative categories array or something else, but what?
 * @param  string  $sInsertQuery  The insert statement
 * @param  array  $aAllCats  Assoziative categories array holding the complete category structure
 * @param  int  $iLevel  Category level
 * @return  string
 */
function strBuildSqlValues($aCats, $sInsertQuery, &$aAllCats, $iLevel = 0)
{
    if (is_array($aCats)) {
        $aCats = strSortPrePost($aCats);
        foreach ($aCats as $aCat) {
            $sInsertQuery .= '(' .(int) $aCat['idcat'] . ', ' . (int) $iLevel . '), ';
            if (is_array($aAllCats[$aCat['idcat']])) {
                $iSubLevel = $iLevel + 1;
                $sInsertQuery = strBuildSqlValues($aAllCats[$aCat['idcat']], $sInsertQuery, $aAllCats, $iSubLevel);
            }
        }
    }
    return $sInsertQuery;
}


function strNextDeeper($tmp_idcat, $ignore_lang = false)
{
    global $cfg, $db_str, $lang;

    $sql = "SELECT idcat FROM ".$cfg['tab']['cat']." WHERE parentid=" . (int) $tmp_idcat  . " AND preid=0";
    $db_str->query($sql);
    if ($db_str->next_record()) {
        $midcat = (int) $db_str->f("idcat");
        if ($ignore_lang == true) {
            return $midcat;
        }

        // Deeper element exists, check for language dependent part
        $sql = "SELECT idcatlang FROM ".$cfg['tab']['cat_lang']." WHERE idcat=" . $midcat . " AND idlang=" . (int) $lang;
        $db_str->query($sql);
        return ($db_str->next_record()) ? $midcat : 0;
    } else {
        // Deeper element does not exist
        return 0;
    }
}


/**
 * Checks, if passed category cotains any articles
 *
 * @param   int  $tmp_idcat  ID of category
 * @return  bool
 */
function strHasArticles($tmp_idcat)
{
    global $cfg, $db_str, $lang;

    $sql = "SELECT b.idartlang AS idartlang FROM
            ".$cfg['tab']['cat_art']." AS a,
            ".$cfg['tab']['art_lang']." AS b
            WHERE a.idcat='".Contenido_Security::toInteger($tmp_idcat)."' AND
            a.idart = b.idart AND b.idlang = '".Contenido_Security::toInteger($lang)."'";

    $db_str->query($sql);

    return ($db_str->next_record()) ? true : false;
}


function strNextPost($tmp_idcat)
{
    global $db, $cfg;

    $sql = "SELECT idcat FROM ".$cfg['tab']['cat']." WHERE preid='".Contenido_Security::toInteger($tmp_idcat)."'";
    $db->query($sql);
    if ($db->next_record()) {
        // Post element exists
        $tmp_idcat = $db->f("idcat");
        $sql = "SELECT parentid FROM ".$cfg['tab']['cat']." WHERE idcat='".Contenido_Security::toInteger($tmp_idcat)."'";
        $db->query($sql);
        if ($db->next_record()) {
            // Parent from post must not be 0
            $tmp_parentid = (int) $db->f("parentid");
            return ($tmp_parentid != 0) ? $tmp_idcat : 0;
        } else {
            return 99;
        }
    } else {
        // Post element does not exist
        return 0;
    }
}

function strNextBackwards($tmp_idcat)
{
    global $db, $cfg;

    $tmp_idcat = (int) $tmp_idcat;

    $sql = "SELECT parentid FROM ".$cfg['tab']['cat']." WHERE idcat=" . $tmp_idcat;
    $db->query($sql);
    if ($db->next_record()) {
        // Parent exists
        $tmp_idcat = $db->f("parentid");
        if ($tmp_idcat != 0) {
            $sql = "SELECT idcat FROM ".$cfg['tab']['cat']." WHERE preid=" . $tmp_idcat;
            $db->query($sql);
            if ($db->next_record()) {
                // Parent has post
                $tmp_idcat = $db->f("idcat");
                $sql = "SELECT parentid FROM ".$cfg['tab']['cat']." WHERE idcat=" . $tmp_idcat;
                $db->query($sql);
                if ($db->next_record()) {
                    // Parent from post must not be 0
                    $tmp_parentid = (int) $db->f("parentid");
                    return ($tmp_parentid != 0) ? $tmp_idcat : 0;
                } else {
                    return 99;
                }
            } else {
                // Parent has no post
                return strNextBackwards($tmp_idcat);
            }
        } else {
            return 0;
        }
    } else {
        // No parent
        return 0;
    }
}

/**
    Hotfix recursive call more than 200 times exit script on hosteurope Timo.Trautmann
**/
function strNextDeeperAll($tmp_idcat, $ignore_lang = false)
{
    global $cfg, $db_str, $db_str2, $lang;

    $aCats = array();
    $bLoop = true;
    $sql = "SELECT idcat FROM ".$cfg['tab']['cat']." WHERE parentid='".Contenido_Security::toInteger($tmp_idcat)."' AND preid = 0";

    #echo $sql.'<br>';
    $db_str->query($sql);
    if ($db_str->next_record()) {
        while ($bLoop) {
            $midcat = $db_str->f("idcat");

            if ($ignore_lang == true) {
                array_push($aCats, $midcat);
            } else {
                // Deeper element exists, check for language dependent part
                $sql = "SELECT idcatlang FROM ".$cfg['tab']['cat_lang']." WHERE idcat='".Contenido_Security::toInteger($midcat)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
                $db_str2->query($sql);

                if ($db_str2->next_record()) {
                    array_push($aCats, $midcat);
                }
            }

            $sql = "SELECT preid, postid, idcat FROM ".$cfg['tab']['cat']." WHERE parentid='".Contenido_Security::toInteger($tmp_idcat)."' AND preid = ".Contenido_Security::toInteger($midcat)."";
            $db_str->query($sql);
            if (!$db_str->next_record()) {
                $bLoop = false;
            }
        }
    }
    return $aCats;
}

/**
 * Renders the category tree a HTML table
 * @deprecated 2012-03-04 This function is not longer supported.
 * @return  void
 */
function strShowTreeTable()
{
    global $db, $sess, $client, $lang, $cfg, $lngStr;

    cDeprecated("This function is not longer supported.");

    echo "<br><table cellpadding=0 cellspacing=0 border=0>";
    $sql = "SELECT * FROM ".$cfg['tab']['cat_tree']." AS A, ".$cfg['tab']['cat']." AS B, ".$cfg['tab']['cat_lang']." AS C WHERE A.idcat=B.idcat AND B.idcat=C.idcat AND C.idlang='".Contenido_Security::toInteger($lang)."' AND B.idclient='".Contenido_Security::toInteger($client)."' ORDER BY A.idtree";
    $db->query($sql);
    while ($db->next_record()) {
        $tmp_id    = $db->f("idcat");
        $tmp_name  = $db->f("name");
        $tmp_level = $db->f("level");
        echo "<tr><td>".$tmp_id." | ".$tmp_name." | ".$tmp_level."</td>";
        echo "<td><a class=action href=\"".$sess->url("main.php?action=20&idcat=$tmp_id")."\">".$lngStr["actions"]["20"]."</a></td>";
        echo "<td><a class=action href=\"".$sess->url("main.php?action=30&idcat=$tmp_id")."\">".$lngStr["actions"]["30"]."</a></td>";
        echo "</td></tr>";
    }
    echo "</table>";
}


/**
 * Renames a category
 *
 * @param   int     $idcat             Category id
 * @param   int     $lang              Language id
 * @param   string  $newcategoryname   New category name
 * @param   string  $newcategoryalias  New category alias
 * @return  void
 */
function strRenameCategory($idcat, $lang, $newcategoryname, $newcategoryalias)
{
    global $db, $cfg, $cfgClient, $client;

    // Flag to rebuild the category table
    global $remakeCatTable, $remakeStrTable;

    if (trim($newcategoryname) == '') {
        return;
    }

    // @todo: Do we really need to rebuild category tree after renaming???
    $remakeCatTable = true;
    $remakeStrTable = true;

    $sUrlname = htmlspecialchars(capiStrCleanURLCharacters($newcategoryname), ENT_QUOTES);
    $sName = htmlspecialchars($newcategoryname, ENT_QUOTES);

    if (trim($newcategoryalias) != '') {
        $sql = "SELECT urlname, name FROM ".$cfg['tab']['cat_lang']." WHERE idcat='".Contenido_Security::toInteger($idcat)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
        $db->query($sql);
        $sUrlnameNew = htmlspecialchars(capiStrCleanURLCharacters($newcategoryalias), ENT_QUOTES);
        if ($db->next_record()) {
            $sOldAlias = $db->f('urlname');
            $sOldName = $db->f('name');
        }
        if ($sOldAlias != $sUrlnameNew) {
            $sUrlname = $sUrlnameNew;
        }

        @unlink($cfgClient[$client]["path"]["frontend"]."cache/locationstring-url-cache-$lang.txt");
    }

    $sql = "UPDATE ".$cfg['tab']['cat_lang']." SET urlname='".Contenido_Security::escapeDB($sUrlname, $db)."', name='".Contenido_Security::escapeDB($sName, $db)."', lastmodified = '".date("Y-m-d H:i:s")."' WHERE idcat='".Contenido_Security::toInteger($idcat)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
    $db->query($sql);
}

/**
 * Renames a category alias.
 *
 * @param   int     $idcat             Category id
 * @param   int     $lang              Language id
 * @param   string  $newcategoryalias  New category alias
 * @return  void
 */
function strRenameCategoryAlias($idcat, $lang, $newcategoryalias)
{
    global $db, $cfg, $cfgClient, $client;

    if (trim($newcategoryalias) != '') {
        $sUrlName = capiStrCleanURLCharacters($newcategoryalias);
        $sql = "UPDATE {$cfg['tab']['cat_lang']} SET urlname = '". $db->escape($sUrlName) ."' WHERE idcat = " . (int) $idcat . " AND idlang = " . (int) $lang;
        $db->query($sql);
    } else {
        // Use categoryname as default -> get it escape it save it as urlname
        $sql = "SELECT name from {$cfg['tab']['cat_lang']} WHERE idcat = " . (int) $idcat . " AND idlang = " . (int) $lang;
        $db->query($sql);
        if ($db->next_record()) {
            $sUrlName = capiStrCleanURLCharacters($db->f('name'));
            $sql = "UPDATE {$cfg['tab']['cat_lang']} SET urlname = '" . $sUrlName . "' WHERE idcat = " . (int) $idcat . " AND idlang = " . (int) $lang;
            $db->query($sql);
            @unlink($cfgClient[$client]['path']['frontend'] . "cache/locationstring-url-cache-$lang.txt");
        }
    }
}

/**
 * Sets the visible status of the category and its childs
 *
 * @param   int  $idcat    Category id
 * @param   int  $lang     Language id
 * @param   int  $visible  Visible status
 * @return  void
 */
function strMakeVisible($idcat, $lang, $visible)
{
    global $db, $cfg;

    // Flag to rebuild the category table
    global $remakeCatTable, $remakeStrTable;

    // @todo: Do we really need to rebuild category tree after changing visibility???
    $remakeCatTable = true;
    $remakeStrTable = true;

    $visible = (int) $visible;
    $lang = (int) $lang;

    $a_catstring = strDeeperCategoriesArray($idcat);
    foreach ($a_catstring as $value) {
        $sql = "UPDATE ".$cfg['tab']['cat_lang']." SET visible='" . $visible . "', lastmodified='" . date("Y-m-d H:i:s") . "'
                WHERE idcat=" . (int) $value . " AND idlang=".$lang;
        $db->query($sql);
    }

    if ($cfg["pathresolve_heapcache"] == true && $visible = 0) {
        $pathresolve_tablename = $cfg["sql"]["sqlprefix"]."_pathresolve_cache";
        $sql = "DELETE FROM %s WHERE idlang = '%s' AND idcat = '%s'";
        $db->query(sprintf($sql, Contenido_Security::escapeDB($pathresolve_tablename, $db), Contenido_Security::toInteger($lang), $idcat));
    }
}


/**
 * Sets the public status of the category and its childs
 *
 * @param   int  $idcat   Category id
 * @param   int  $lang    Language id
 * @param   int  $public  Public status
 * @return  void
 */
function strMakePublic($idcat, $lang, $public)
{
    global $db, $cfg;

    // Flag to rebuild the category table
    global $remakeCatTable, $remakeStrTable;

    // @todo: Do we really need to rebuild category tree after changing public state???
    $remakeCatTable = true;
    $remakeStrTable = true;

    $a_catstring = strDeeperCategoriesArray($idcat);
    foreach ($a_catstring as $value) {
        $sql = "UPDATE ".$cfg['tab']['cat_lang']." SET public='$public', lastmodified = '".date("Y-m-d H:i:s")."'
                WHERE idcat='".Contenido_Security::toInteger($value)."' AND idlang='".Contenido_Security::toInteger($lang)."' ";
        $db->query($sql);
    }
}

/**
 * Returns all childs and childchidls of $idcat_start
 *
 * @param    int    $idcat_start the start category
 * @return   array  contains all childs of $idcat_start and $id_cat start itself
 */
function strDeeperCategoriesArray($idcat_start)
{
    global $db, $client, $cfg;

    $catstring = array();
    $openlist = array();

    array_push($openlist, $idcat_start);

    while (($actid = array_pop($openlist)) != null) {
        if (in_array($actid, $catstring)) {
            continue;
        }

        array_push($catstring, $actid);

        $sql = "SELECT * FROM ".$cfg['tab']['cat_tree']." AS A, ".$cfg['tab']['cat']." AS B WHERE A.idcat=B.idcat AND B.parentid='".$actid."' AND idclient='".Contenido_Security::toInteger($client)."' ORDER BY idtree";
        $db->query($sql);

        while ($db->next_record()) {
            $id_cat = $db->f("idcat");
            array_push($openlist, $id_cat);
        }
    }

    return $catstring;
}


/**
 * Deletes the category and its template configurations.
 *
 * Only categories having no child categories and having no articles will be deleted!
 *
 * @param   int   $idcat  Id of category to delete
 * @return  void
 */
function strDeleteCategory($idcat)
{
    global $db, $lang, $client, $lang, $cfg;

    // Flag to rebuild the category table
    global $remakeCatTable, $remakeStrTable;

    if (strNextDeeper($idcat)) {
        // category has subcategories
        return "0201";
    } elseif (strHasArticles($idcat)) {
        // category has arts
        return "0202";
    }

    $db2 = new DB_Contenido();
    $remakeCatTable = true;
    $remakeStrTable = true;

    $sql = "SELECT idtplcfg FROM ".$cfg['tab']['cat_lang']." WHERE idcat='".Contenido_Security::toInteger($idcat)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
    $db->query($sql);

    while ($db->next_record()) {
        // delete entry in 'tpl_conf'-table
        $sql = "DELETE FROM ".$cfg['tab']['tpl_conf']." WHERE idtplcfg='".Contenido_Security::toInteger($db->f("idtplcfg"))."'";
        $db2->query($sql);

        $sql = "DELETE FROM ".$cfg['tab']['container_conf']." WHERE idtplcfg = '".Contenido_Security::toInteger($db->f("idtplcfg"))."'";
        $db2->query($sql);
    }

    // Delete language dependend part
    $sql = "DELETE FROM ".$cfg['tab']['cat_lang']." WHERE idcat='".Contenido_Security::toInteger($idcat)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
    $db->query($sql);

    // Are there any additional languages?
    $sql = "SELECT idcatlang FROM ".$cfg['tab']['cat_lang']." WHERE idcat='".Contenido_Security::toInteger($idcat)."'";
    $db->query($sql);

    if ($db->num_rows() > 0) {
        // more languages found, delete rights for element
        cInclude('includes', 'functions.rights.php');
        deleteRightsForElement('str', $idcat, $lang);
        deleteRightsForElement('con', $idcat, $lang);
        return;
    }

    $sql = "SELECT * FROM ".$cfg['tab']['cat']." WHERE idcat='".Contenido_Security::toInteger($idcat)."'";
    $db->query($sql);
    $db->next_record();
    $tmp_preid  = $db->f("preid");
    $tmp_postid = $db->f("postid");

    // update pre cat set new postid
    if ($tmp_preid != 0) {
        $sql = "UPDATE ".$cfg['tab']['cat']." SET postid='".Contenido_Security::toInteger($tmp_postid)."' WHERE idcat='".Contenido_Security::toInteger($tmp_preid)."'";
        $db->query($sql);
    }

    // update post cat set new preid
    if ($tmp_postid != 0) {
        $sql = "UPDATE ".$cfg['tab']['cat']." SET preid='".Contenido_Security::toInteger($tmp_preid)."' WHERE idcat='".Contenido_Security::toInteger($tmp_postid)."'";
        $db->query($sql);
    }

    // delete entry in 'cat'-table
    $sql = "DELETE FROM ".$cfg['tab']['cat']." WHERE idcat='".Contenido_Security::toInteger($idcat)."'";
    $db->query($sql);

    $sql = "SELECT idtplcfg FROM ".$cfg['tab']['cat_lang']." WHERE idcat='".Contenido_Security::toInteger($idcat)."'";
    $db->query($sql);
    while ($db->next_record()) {
        // delete entry in 'tpl_conf'-table
        $sql = "DELETE FROM ".$cfg['tab']['tpl_conf']." WHERE idtplcfg='".Contenido_Security::toInteger($db->f("idtplcfg"))."'";
        $db2->query($sql);

        $sql = "DELETE FROM ".$cfg['tab']['container_conf']." WHERE idtplcfg = '".Contenido_Security::toInteger($db->f("idtplcfg"))."'";
        echo $sql;
        $db2->query($sql);
    }

    ////// delete entry in 'cat_lang'-table
    $sql = "DELETE FROM ".$cfg['tab']['cat_lang']." WHERE idcat='".Contenido_Security::toInteger($idcat)."'";
    $db->query($sql);

    ////// delete entry in 'cat_tree'-table
    $sql = "DELETE FROM ".$cfg['tab']['cat_tree']." WHERE idcat='".Contenido_Security::toInteger($idcat)."'";
    $db->query($sql);

    // delete rights for element
    cInclude('includes', 'functions.rights.php');
    deleteRightsForElement('str', $idcat);
    deleteRightsForElement('con', $idcat);
}


/**
 * Moves a category upwards.
 *
 * @param   int  $idcat  Id of category to move upwards
 * @return  void
 */
function strMoveUpCategory($idcat)
{
    // Flag to rebuild the category table
    global $remakeCatTable, $remakeStrTable;

    // Load current category
    $oCat = new cApiCategory();
    $oCat->loadByPrimaryKey((int) $idcat);
    $preid = $oCat->get('preid');
    $postid = $oCat->get('postid');

    if (0 == $preid) {
        // No preid, no way to move up
        return;
    }

    $remakeCatTable = true;
    $remakeStrTable = true;

    // Load previous category
    $oPreCat = new cApiCategory();
    $oPreCat->loadByPrimaryKey((int) $preid);
    $prePreid = $oPreCat->get('preid');
    $preIdcat  = $oPreCat->get('idcat');

    // Load category before previous category
    $oPrePreCat = new cApiCategory();
    if ((int) $prePreid > 0) {
        $oPrePreCat->loadByPrimaryKey((int) $prePreid);
    }

    // Load post category
    $oPostCat = new cApiCategory();
    if ((int) $postid > 0) {
        $oPostCat->loadByPrimaryKey((int) $postid);
    }

    // Update category before previous, if exists
    if ($oPrePreCat->isLoaded()) {
        $oPrePreCat->set('postid', (int) $idcat);
        $oPrePreCat->store();
    }

    // Update previous category
    $oPreCat->set('preid', (int) $idcat);
    $oPreCat->set('postid', (int) $postid);
    $oPreCat->store();

    // Update current category
    $oCat->set('preid', (int) $prePreid);
    $oCat->set('postid', (int) $preid);
    $oCat->store();

    // Update post category, if exists!
    $oPostCat->set('preid', (int) $preIdcat);
    $oPostCat->store();
}


/**
 * Moves a category downwards.
 *
 * @param   int  $idcat  Id of category to move downwards
 * @return  void
 */
function strMoveDownCategory($idcat)
{
    // Flag to rebuild the category table
    global $remakeCatTable, $remakeStrTable;

    // Load current category
    $oCat = new cApiCategory();
    $oCat->loadByPrimaryKey((int) $idcat);
    $preid = $oCat->get('preid');
    $postid = $oCat->get('postid');

    if (0 == $postid) {
        // No post, no way to move down
        return;
    }

    $remakeCatTable = true;
    $remakeStrTable = true;

    // Load previous category
    $oPreCat = new cApiCategory();
    if ((int) $preid > 0) {
        $oPreCat->loadByPrimaryKey((int) $preid);
        $preIdcat = (int) $oPreCat->get('idcat');
    } else {
        $preIdcat = 0;
    }

    // Load post category
    $oPostCat = new cApiCategory();
    $oPostCat->loadByPrimaryKey((int) $postid);
    $postIdcat = $oPostCat->get('idcat');
    $postPostid = $oPostCat->get('postid');

    if ($preIdcat != 0) {
        // Update previous category, if exists
        $oPreCat->set('postid', (int) $postIdcat);
        $oPreCat->store();
    }

    // Update current category
    $oCat->set('preid', (int) $postid);
    $oCat->set('postid', (int) $postPostid);
    $oCat->store();

    // Update post category
    $oPostCat->set('preid', (int) $preIdcat);
    $oPostCat->set('postid', (int) $idcat);
    $oPostCat->store();

    if ($postPostid != 0) {
        // Update post post category, if exists
        $oPostPostCat = new cApiCategory($postPostid);
        $oPostPostCat->set('preid', (int) $idcat);
        $oPostPostCat->store();
    }
}


/**
 * Moves a subtree to another destination.
 *
 * @param   int  $idcat  Id of category
 * @param   int  $parentid_new  Id of destination parent category
 * @return  void
 */
function strMoveSubtree($idcat, $parentid_new)
{
    global $db, $cfg, $movesubtreeidcat, $sess;

    // Flag to rebuild the category table
    global $remakeCatTable, $remakeStrTable;

    $remakeCatTable = true;
    $remakeStrTable = true;

    $idcat        = (int) $idcat;
    $iNewParentId = (int) $parentid_new;

    // Check if iNewParentId is 0 and the unescaped value is not null
    if ($iNewParentId == 0 && !is_null($parentid_new)) {
        $movesubtreeidcat = 0;
    } else if ($iNewParentId != 0) {
        $sql = "SELECT idcat, preid, postid FROM ".$cfg['tab']['cat']." WHERE idcat='" . $idcat . "'";
        $db->query($sql);
        $db->next_record();
        $tmp_idcat  = $db->f("idcat");
        $tmp_preid  = $db->f("preid");
        $tmp_postid = $db->f("postid");

        // update predecessor (pre)
        if ($tmp_preid != 0) {
            $sql = "UPDATE ".$cfg['tab']['cat']." SET postid='" . $tmp_postid . "' WHERE idcat='" . $tmp_preid . "'";
            $db->query($sql);
        }

        // update follower (post)
        if ($tmp_postid != 0) {
            $sql = "UPDATE ".$cfg['tab']['cat']." SET preid='" . $tmp_preid . "' WHERE idcat='" . $tmp_postid . "'";
            $db->query($sql);
        }

        // find new pre
        $sql = "SELECT idcat, preid FROM ".$cfg['tab']['cat']." WHERE parentid='" . $iNewParentId . "' AND postid='0'";
        $db->query($sql);
        if ($db->next_record()) {
            $tmp_new_preid = $db->f("idcat");
            $tmp_preid_2   = $db->f("preid");
            if ($tmp_new_preid != $idcat) {
                // update new pre: set post
                $sql = "UPDATE ".$cfg['tab']['cat']." SET postid='" . $idcat . "' WHERE idcat='" . $tmp_new_preid . "'";
                $db->query($sql);
            } else {
                $sql = "SELECT idcat FROM ".$cfg['tab']['cat']." WHERE idcat='" . $tmp_preid_2 . "'";
                $db->query($sql);
                if ($db->next_record()) {
                    $tmp_new_preid = $db->f("idcat");
                    // update new pre: set post
                    $sql = "UPDATE ".$cfg['tab']['cat']." SET postid='" . $idcat . "' WHERE idcat='" . $tmp_new_preid . "'";
                    $db->query($sql);
                } else {
                    $tmp_new_preid = 0;
                }
            }
        } else {
            $tmp_new_preid = 0;
        }

        // update idcat
        $sql = "UPDATE ".$cfg['tab']['cat']." SET parentid='" . $iNewParentId . "', preid='" . $tmp_new_preid . "', postid='0' WHERE idcat='" . $idcat . "'";
        $db->query($sql);

        $movesubtreeidcat = 0;
    } else {
        // We recoded this function to prevent crashing the cat tree
        // when a user copies a tree and forget to set the target category

        // Copy transaction now is only performed by setting the target
        $movesubtreeidcat = $idcat;
    }

    $sess->register('movesubtreeidcat');
    $sess->freeze();
}


/**
 * Checks if category is movable.
 *
 * @param   int  $idcat   Id of category to move
 * @param   int  $source  Id of source category
 * @return  bool
 */
function strMoveCatTargetallowed($idcat, $source)
{
    return ($idcat == $source) ? 0 : 1;
}


/**
 * Synchronizes a category from one language to another language.
 *
 * @param   int   $idcatParam  Id of category to synchronize
 * @param   int   $sourcelang  Id of source language
 * @param   int   $targetlang  Id of target language
 * @param   bool  $bMultiple   Flag to synchronize child languages
 */
function strSyncCategory($idcatParam, $sourcelang, $targetlang, $bMultiple = false)
{
    global $cfg;

    $db2 = new DB_Contenido();
    $bMultiple = (bool) $bMultiple;

    $aCatArray = array();
    if ($bMultiple == true) {
        $aCatArray = strDeeperCategoriesArray($idcatParam);
    } else {
        array_push($aCatArray, $idcatParam);
    }

    foreach ($aCatArray as $idcat) {
        // Check if category already exists
        $sql = "SELECT * FROM " . $cfg['tab']['cat_lang'] . " WHERE idcat = " . (int) $idcat . " AND idlang = " . (int) $targetlang;
        $db2->query($sql);
        if ($db2->next_record()) {
            return false;
        }

        $sql = "SELECT * FROM " . $cfg['tab']['cat_lang'] . " WHERE idcat = " . (int) $idcat . " AND idlang = " . (int) $sourcelang;
        $db2->query($sql);

        if ($db2->next_record()) {
            if ($db2->f("idtplcfg") != 0) {
                // Copy the template configuration
                $newidtplcfg = tplcfgDuplicate($db2->f("idtplcfg"));
            } else {
                $newidtplcfg = 0;
            }
            //$newidcatlang = $db2->nextid($cfg['tab']['cat_lang']);

            $idcat = $db2->f("idcat");
            $visible = 0;

            $aRs = $db2->toArray();

            $sql = $db2->buildInsert($cfg['tab']['cat_lang'], array(
                'idcat' => (int) $aRs['idcat'],
                'idlang' => (int) $targetlang,
                'idtplcfg' => (int) $newidtplcfg,
                'name' => $aRs['name'],
                'visible' => $visible,
                'public' => $aRs['public'],
                'status' => (int) $aRs['status'],
                'urlname' => $aRs['urlname'],
                'author' => $aRs['author'],
                'created' => $aRs['created'],
                'lastmodified' => $aRs['lastmodified'],
            ));

            $db2->query($sql);

            // execute CEC hook
            $param = $aRs;
            $param['idlang']   = $targetlang;
            $param['idtplcfg'] = (int) $newidtplcfg;
            $param['visible']  = $visible;
            CEC_Hook::execute('Contenido.Category.strSyncCategory_Loop', $param);

            // set correct rights for element
            cInclude('includes', 'functions.rights.php');
            createRightsForElement('str', $idcat, $targetlang);
            createRightsForElement('con', $idcat, $targetlang);
        }
    }
}


/**
 * Checks if category has a start article
 *
 * @param   int   $idcat   Id of category
 * @param   int   $idlang  The language id
 * @return  bool
 */
function strHasStartArticle($idcat, $idlang)
{
    global $cfg, $db_str;

    $sql = "SELECT startidartlang FROM " . $cfg['tab']['cat_lang'] . " WHERE idcat = " . (int) $idcat . " AND idlang = " . (int) $idlang . " AND startidartlang != 0";

    $db_str->query($sql);
    return ($db_str->next_record()) ? true : false;
}


/**
 * Copies the category and it's existing articles into another category.
 *
 * @param   int   $idcat          Id of category to copy
 * @param   int   $destidcat      Id of destination category
 * @param   bool  $remakeTree     Flag to rebuild category tree
 * @param   bool  $bUseCopyLabel  Flag to add copy label to the new categories
 * @return  void
 */
function strCopyCategory($idcat, $destidcat, $remakeTree = true, $bUseCopyLabel = true)
{
    global $cfg, $client, $lang;

    $lang = (int) $lang;
    $idcat = (int) $idcat;

    $newidcat = (int) strNewCategory($destidcat, 'a', $remakeTree);
    if ($newidcat == 0) {
        return;
    }

    // Selectors
    $_oldcatlang = new cApiCategoryLanguageCollection();
    $_newcatlang = new cApiCategoryLanguageCollection();

    $_oldcatlang->select("idcat = $idcat AND idlang = $lang");
    $oldcatlang = $_oldcatlang->next();
    if (!is_object($oldcatlang)) {
        return;
    }

    $_newcatlang->select("idcat = $newidcat AND idlang = $lang");
    $newcatlang = $_newcatlang->next();
    if (!is_object($newcatlang)) {
        return;
    }

    // Worker objects
    $newcat = new cApiCategory($newidcat);
    $oldcat = new cApiCategory($idcat);

    // Copy properties
    if ($bUseCopyLabel == true) {
        $newcatlang->set("name", sprintf(i18n("%s (Copy)"), $oldcatlang->get("name")));
    } else {
        $newcatlang->set("name", $oldcatlang->get("name"));
    }

    $newcatlang->set("public", $oldcatlang->get("public"));
    $newcatlang->set("visible", 0);
    $newcatlang->store();

    // execute cec hook
    CEC_Hook::execute('Contenido.Category.strCopyCategory', array(
        'oldcat'     => $oldcat,
        'newcat'     => $newcat,
        'newcatlang' => $newcatlang
    ));

    // Copy template configuration
    if ($oldcatlang->get("idtplcfg") != 0) {
        // Create new template configuration
        $newcatlang->assignTemplate($oldcatlang->getTemplate());

        // Copy the container configuration
        $c_cconf = new cApiContainerConfigurationCollection;
        $m_cconf = new cApiContainerConfigurationCollection;
        $c_cconf->select("idtplcfg = '".$oldcatlang->get("idtplcfg")."'");

        while ($i_cconf = $c_cconf->next()) {
            $m_cconf->create($newcatlang->get("idtplcfg"), $i_cconf->get("number"), $i_cconf->get("container"));
        }
    }

    $db = new DB_Contenido();
    $db2 = new DB_Contenido();

    // Copy all articles
    $sql = "SELECT A.idart, B.idartlang FROM " . $cfg['tab']['cat_art'] . " AS A, " . $cfg['tab']['art_lang'] . " AS B WHERE A.idcat = " . $idcat . " AND B.idart = A.idart AND B.idlang = " . $lang;
    $db->query($sql);

    while ($db->next_record()) {
        $newidart = (int) conCopyArticle($db->f("idart"), $newidcat, "", $bUseCopyLabel);
        if ($db->f("idartlang") == $oldcatlang->get("startidartlang")) {
            $sql = "SELECT idcatart FROM " . $cfg['tab']['cat_art'] . " WHERE idcat = " . $newidcat . " AND idart = " . $newidart;
            $db2->query($sql);
            if ($db2->next_record()) {
                conMakeStart($db2->f("idcatart"), 1);
            }
        }
    }

    return $newidcat;
}


/**
 * Copies the categorytree (category and its childs) to an another category.
 *
 * @param   int   $idcat          Id of category to copy
 * @param   int   $destcat        Id of destination category
 * @param   bool  $remakeTree     Flag to rebuild category tree
 * @param   bool  $bUseCopyLabel  Flag to add copy label to the new categories
 */
function strCopyTree($idcat, $destcat, $remakeTree = true, $bUseCopyLabel = true)
{
    global $cfg;

    $newidcat = strCopyCategory($idcat, $destcat, false, $bUseCopyLabel);

    $db = new DB_Contenido();
    $db->query("SELECT idcat FROM " . $cfg['tab']['cat'] . " WHERE parentid = " . (int) $idcat);
    while ($db->next_record()) {
        strCopyTree($db->f("idcat"), $newidcat, false, $bUseCopyLabel);
    }

    if ($remakeTree == true) {
        strRemakeTreeTable();
    }
}

/**
 * Assigns a template to passed category.
 * @param  int  $idcat
 * @param  int  $client
 * @param  int  $idTplCfg
 */
function strAssignTemplate($idcat, $client, $idTplCfg)
{
    global $perm;

    // Template permissition check
    $iIdtplcfg = ($perm->have_perm_area_action('str_tplcfg', 'str_tplcfg')) ? (int) $iIdtplcfg : 0;

    $idtpl = null;

    if ($iIdtplcfg == 0) {
        // Get default template
        $templateColl = new cApiTemplateCollection('defaulttemplate = 1 AND idclient = ' . (int) $client);
        if ($template = $templateColl->next()) {
            $idtpl = $template->get('idtpl');
        }
    } else {
        // Use passed template
        $idtpl = $idTplCfg;
    }

    if ($idtpl) {
        // Assign template
        $catColl = new cApiCategoryLanguageCollection('idcat = ' . (int) $idcat);
        while ($cat = $catColl->next()) {
            $cat->assignTemplate($idtpl);
        }
    }
}

?>