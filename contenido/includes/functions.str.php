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
 * @version    1.3.24
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
    $db_str = cRegistry::getDb();
    $db_str2 = cRegistry::getDb();
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
        $urlname = htmlspecialchars(cApiStrCleanURLCharacters($catalias), ENT_QUOTES);

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
        $urlname = htmlspecialchars(cApiStrCleanURLCharacters($catalias), ENT_QUOTES);

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
    $oCatColl = new cApiCategoryCollection();
    $oCatColl->select('parentid=0 AND preid=' . (int) $idcat . ' AND idcat!=0');
    if ($oCat = $oCatColl->next()) {
        $postIdcat = $oCat->get('idcat');
        $poststring = $poststring . ',' . $postIdcat;
        $poststring = strOrderedPostTreeList($postIdcat, $poststring);
    }

    return $poststring;
}


/**
 * Remakes the category tree structure in category tree table.
 *
 * It still uses manually build sql statements due to performance reasons.
 *
 * @return  void
 */
function strRemakeTreeTable()
{
    global $db, $client, $cfg;

    // Flag to rebuild the category table
    global $remakeCatTable;
    global $remakeStrTable;

    // Get all category ids
    $oCatColl = new cApiCategoryCollection();
    $idcats = $oCatColl->getCategoryIdsByClient($client);
    if (0 === count($idcats)) {
        // There are no categories to build the tree from!
        return;
    }

    $remakeCatTable = true;
    $remakeStrTable = true;

    // Empty category tree table having specific categories
    $sql = 'DELETE FROM ' . $cfg['tab']['cat_tree'] . ' WHERE idcat IN (' . implode(', ', $idcats) . ')';
    $db->query($sql);

    // Delete entries from category table having idcat = 0
    // @todo: Check this, how it is possible to have an invalid entry with primary key = 0
    $sql = 'DELETE FROM ' . $cfg['tab']['cat'] . ' WHERE idcat=0';
    $db->query($sql);

    // Delete entries from category language table having idcat = 0
    // @todo: Check this, how it is possible to have an invalid entry with primary key = 0
    $sql = 'DELETE FROM ' . $cfg['tab']['cat_lang'] . ' WHERE idcat=0';
    $db->query($sql);

    // Get all categories by client
    $sql = "SELECT idcat, parentid, preid, postid FROM " . $cfg['tab']['cat'] . " WHERE idclient = " . (int) $client . " ORDER BY parentid ASC, preid ASC, postid ASC";
    $aCategories = array();
    $db->query($sql);
    while ($db->next_record()) {
        $rs = $db->toArray();
        if (!isset($aCategories[$rs['parentid']])) {
            $aCategories[$rs['parentid']] = array();
        }
        $aCategories[$rs['parentid']][$rs['idcat']] = $rs;
    }

    // Build INSERT statement
    $sInsertQuery = "INSERT INTO " . $cfg['tab']['cat_tree'] . " (idcat, level) VALUES ";
    $sInsertQuery = strBuildSqlValues($aCategories[0], $sInsertQuery, $aCategories);
    $sInsertQuery = rtrim($sInsertQuery, " ,");

    // Lock db table and execute INSERT query
    $db->lock($cfg['tab']['cat_tree']);
    $db->query($sInsertQuery);
    $db->unlock($cfg['tab']['cat_tree']);
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
//            die(i18n("The list of categories is messed up. The order in the list creates an infinite loop. Check you database."));
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
        die(i18n("The list of categories is messed up. The order in the list creates an infinite loop. Check you database."));
    }

    while ($curId != 0) {
        $array[] = $arr[$curId];
        $curId = $arr[$curId]['postid'];
    }

    return $array;
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


/**
 * Returns id of next deeper category.
 * @global int $lang
 * @param int $idcat  Category id to check next deeper item
 * @param bool $ignoreLang Flag to check for existing entry in category language table
 * @return int
 */
function strNextDeeper($idcat, $ignoreLang = false)
{
    global $lang;

    $languageId = (true == $ignoreLang) ? $lang : null;
    $oCatColl = new cApiCategoryCollection();
    return $oCatColl->getFirstChildCategoryId($idcat, $languageId);
}


/**
 * Checks, if passed category contains any articles
 *
 * @param   int  $idcat  ID of category
 * @return  bool
 */
function strHasArticles($idcat)
{
    global $lang;

    $oCatArtColl = new cApiCategoryArticleCollection();
    return $oCatArtColl->getHasArticles($idcat, $lang);
}


/**
 * Returns next post category id
 *
 * @param   int  $idcat  ID of category
 * @return  int
 */
function strNextPost($idcat)
{
    $oCatColl = new cApiCategoryCollection();
    return $oCatColl->getNextPostCategoryId($idcat);
}

/**
 * Returns next backwards category id
 *
 * @param   int  $idcat  ID of category
 * @return  int
 */
function strNextBackwards($idcat)
{
    $oCatColl = new cApiCategoryCollection();
    return $oCatColl->getParentsNextPostCategoryId($idcat);
}


/**
 * Returns list of child categories.
 *
 * @global int $lang
 * @param int $idcat
 * @param bool $ignoreLang
 * @return array
 */
function strNextDeeperAll($idcat, $ignoreLang = false)
{
    global $lang;

    $languageId = (true == $ignoreLang) ? $lang : null;
    $oCatColl = new cApiCategoryCollection();
    return $oCatColl->getAllChildCategoryIds($idcat, $languageId);
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
function strRenameCategory($idcat, $lang, $newCategoryName, $newCategoryAlias)
{
    global $client;

    if (trim($newCategoryName) == '') {
        return;
    }

    $oCatLang = new cApiCategoryLanguage();
    if (!$oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
        // Couldn't load category language
        return;
    }

    $name = $newCategoryName;
    $urlName = (trim($newCategoryAlias) != '') ? trim($newCategoryAlias) : $newCategoryName;

    if (trim($newCategoryAlias) != '') {
        if ($oCatLang->get('urlname') != $newCategoryAlias) {
            $urlName = $newCategoryAlias;
        }
        cInclude('includes', 'functions.pathresolver.php');
        prDeleteCacheFileContent($client, $lang);
    }

    $oCatLang->set('name', $name);
    $oCatLang->set('urlname', $urlName);
    $oCatLang->set('lastmodified', date('Y-m-d H:i:s'));
    $oCatLang->store();
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
    global $client;

    $oCatLang = new cApiCategoryLanguage();
    if (!$oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
        // Couldn't load category language
        return;
    }

    if (trim($newcategoryalias) == '') {
        // Use categoryname as default -> get it escape it save it as urlname
        $newcategoryalias = $oCatLang->get('name');
    }

    $oCatLang->set('urlname', $newcategoryalias);
    $oCatLang->set('lastmodified', date('Y-m-d H:i:s'));
    $oCatLang->store();

    cInclude('includes', 'functions.pathresolver.php');
    prDeleteCacheFileContent($client, $lang);
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
    global $cfg;

    $visible = (int) $visible;
    $lang = (int) $lang;

    $categories = strDeeperCategoriesArray($idcat);
    foreach ($categories as $value) {
        $oCatLang = new cApiCategoryLanguage();
        $oCatLang->loadByCategoryIdAndLanguageId($value, $lang);
        $oCatLang->set('visible', $visible);
        $oCatLang->set('lastmodified', date('Y-m-d H:i:s'));
        $oCatLang->store();
    }

    if ($cfg['pathresolve_heapcache'] == true && $visible = 0) {
        $oPathresolveCacheColl = new cApiPathresolveCacheCollection();
        $oPathresolveCacheColl->deleteByCategoryAndLanguage($idcat, $lang);
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
    $public = (int) $public;
    $lang = (int) $lang;

    $categories = strDeeperCategoriesArray($idcat);
    foreach ($categories as $value) {
        $oCatLang = new cApiCategoryLanguage();
        $oCatLang->loadByCategoryIdAndLanguageId($value, $lang);
        $oCatLang->set('public', $public);
        $oCatLang->set('lastmodified', date('Y-m-d H:i:s'));
        $oCatLang->store();
    }
}

/**
 * Returns all childs and childchilds of passed category
 *
 * @param    int    $startIdcat  The start category
 * @return   array  Contains all childs of $startIdcat and $startIdcat start itself
 */
function strDeeperCategoriesArray($startIdcat)
{
    global $client;

    $oCatColl = new cApiCategoryCollection();
    return $oCatColl->getAllCategoryIdsRecursive($startIdcat, $client);
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

    $db2 = cRegistry::getDb();
    $remakeCatTable = true;
    $remakeStrTable = true;

    $sql = "SELECT idtplcfg FROM ".$cfg['tab']['cat_lang']." WHERE idcat='".cSecurity::toInteger($idcat)."' AND idlang='".cSecurity::toInteger($lang)."'";
    $db->query($sql);

    while ($db->next_record()) {
        // delete entry in 'tpl_conf'-table
        $sql = "DELETE FROM ".$cfg['tab']['tpl_conf']." WHERE idtplcfg='".cSecurity::toInteger($db->f("idtplcfg"))."'";
        $db2->query($sql);

        $sql = "DELETE FROM ".$cfg['tab']['container_conf']." WHERE idtplcfg = '".cSecurity::toInteger($db->f("idtplcfg"))."'";
        $db2->query($sql);
    }

    // Delete language dependend part
    $sql = "DELETE FROM ".$cfg['tab']['cat_lang']." WHERE idcat='".cSecurity::toInteger($idcat)."' AND idlang='".cSecurity::toInteger($lang)."'";
    $db->query($sql);

    // Are there any additional languages?
    $sql = "SELECT idcatlang FROM ".$cfg['tab']['cat_lang']." WHERE idcat='".cSecurity::toInteger($idcat)."'";
    $db->query($sql);

    if ($db->num_rows() > 0) {
        // more languages found, delete rights for element
        cInclude('includes', 'functions.rights.php');
        deleteRightsForElement('str', $idcat, $lang);
        deleteRightsForElement('con', $idcat, $lang);
        return;
    }

    $sql = "SELECT * FROM ".$cfg['tab']['cat']." WHERE idcat='".cSecurity::toInteger($idcat)."'";
    $db->query($sql);
    $db->next_record();
    $tmp_preid  = $db->f("preid");
    $tmp_postid = $db->f("postid");

    // update pre cat set new postid
    if ($tmp_preid != 0) {
        $sql = "UPDATE ".$cfg['tab']['cat']." SET postid='".cSecurity::toInteger($tmp_postid)."' WHERE idcat='".cSecurity::toInteger($tmp_preid)."'";
        $db->query($sql);
    }

    // update post cat set new preid
    if ($tmp_postid != 0) {
        $sql = "UPDATE ".$cfg['tab']['cat']." SET preid='".cSecurity::toInteger($tmp_preid)."' WHERE idcat='".cSecurity::toInteger($tmp_postid)."'";
        $db->query($sql);
    }

    // delete entry in 'cat'-table
    $sql = "DELETE FROM ".$cfg['tab']['cat']." WHERE idcat='".cSecurity::toInteger($idcat)."'";
    $db->query($sql);

    $sql = "SELECT idtplcfg FROM ".$cfg['tab']['cat_lang']." WHERE idcat='".cSecurity::toInteger($idcat)."'";
    $db->query($sql);
    while ($db->next_record()) {
        // delete entry in 'tpl_conf'-table
        $sql = "DELETE FROM ".$cfg['tab']['tpl_conf']." WHERE idtplcfg='".cSecurity::toInteger($db->f("idtplcfg"))."'";
        $db2->query($sql);

        $sql = "DELETE FROM ".$cfg['tab']['container_conf']." WHERE idtplcfg = '".cSecurity::toInteger($db->f("idtplcfg"))."'";
        echo $sql;
        $db2->query($sql);
    }

    ////// delete entry in 'cat_lang'-table
    $sql = "DELETE FROM ".$cfg['tab']['cat_lang']." WHERE idcat='".cSecurity::toInteger($idcat)."'";
    $db->query($sql);

    ////// delete entry in 'cat_tree'-table
    $sql = "DELETE FROM ".$cfg['tab']['cat_tree']." WHERE idcat='".cSecurity::toInteger($idcat)."'";
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

    $db2 = cRegistry::getDb();
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
    $oCatLangColl = new cApiCategoryLanguageCollection();
    return ($oCatLangColl->getStartIdartlangByIdcatAndIdlang($idcat, $idlang) > 0);
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

    $db = cRegistry::getDb();
    $db2 = cRegistry::getDb();

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

    $db = cRegistry::getDb();
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

/**
 * @deprecated 2012-04-26 This function is not longer supported, use strSortPrePost() instead
 */
function sort_pre_post($arr)
{
    cDeprecated("This function is not longer supported, use strSortPrePost() instead");
    return strSortPrePost($arr);
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
 * Renders the category tree a HTML table
 * @deprecated 2012-03-04 This function is not longer supported.
 * @return  void
 */
function strShowTreeTable()
{
    global $db, $sess, $client, $lang, $cfg, $lngStr;

    cDeprecated("This function is not longer supported.");

    echo "<br><table cellpadding=0 cellspacing=0 border=0>";
    $sql = "SELECT * FROM `".$cfg['tab']['cat_tree']."` AS A, `".$cfg['tab']['cat']."` AS B, `".$cfg['tab']['cat_lang']."` AS C "
         . "WHERE A.idcat=B.idcat AND B.idcat=C.idcat AND C.idlang=".(int) $lang." AND B.idclient=".(int)$client." ORDER BY A.idtree";
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

?>