<?php
/**
 * This file contains the CONTENIDO structure/category functions.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Olaf Niemann
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.con.php');
cInclude('includes', 'functions.database.php');

/**
 * Creates a new category tree (root category item).
 *
 * @param string $catname The category name
 * @param string $catalias Alias of category
 * @param int $visible Flag about visible status
 * @param int $public Flag about public status
 * @param int $iIdtplcfg Id of template configuration
 * @return (int null) of new generated category or nothing on failure
 */
function strNewTree($catname, $catalias = '', $visible = 0, $public = 1, $iIdtplcfg = 0) {
    global $client, $lang, $perm;

    // Flag to rebuild the category table
    global $remakeCatTable, $remakeStrTable;

    if (trim($catname) == '') {
        return;
    }

    $catname = stripslashes($catname);

    $remakeCatTable = true;
    $remakeStrTable = true;

    $catalias = trim($catalias);
    if ($catalias == '') {
        $catalias = trim($catname);
    }

    $client = (int) $client;
    $lang = (int) $lang;

    $visible = ($visible == 1)? 1 : 0;
    if (!$perm->have_perm_area_action('str', 'str_makevisible')) {
        $visible = 0;
    }

    $public = ($public == 1)? 1 : 0;
    if (!$perm->have_perm_area_action('str', 'str_makepublic')) {
        $public = 1;
    }

    // Get last category tree
    $oCatColl = new cApiCategoryCollection();
    $oLastCatTree = $oCatColl->fetchLastCategoryTree($client);
    $lastCatTreeId = (is_object($oLastCatTree))? $oLastCatTree->get('idcat') : 0;

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
    $aLanguages = array(
        $lang
    );
    foreach ($aLanguages as $curLang) {
        $name = $catname;
        $urlname = conHtmlSpecialChars(cApiStrCleanURLCharacters($catalias), ENT_QUOTES);

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
 * @param int $parentid Id of parent category
 * @param string $catname The category name
 * @param bool $remakeTree Flag to rebuild category tree structure
 * @param string $catalias Alias of category
 * @param int $visible Flag about visible status
 * @param int $public Flag about public status
 * @param int $iIdtplcfg Id of template configuration
 * @return (int null) of new generated category or nothing on failure
 */
function strNewCategory($parentid, $catname, $remakeTree = true, $catalias = '', $visible = 0, $public = 1, $iIdtplcfg = 0) {
    global $client, $lang, $perm;

    // Flag to rebuild the category table
    global $remakeCatTable, $remakeStrTable;

    $parentid = (int) $parentid;

    if (trim($catname) == '') {
        return;
    }

    $catname = stripslashes($catname);

    $remakeCatTable = true;
    $remakeStrTable = true;

    $catalias = trim($catalias);
    if ($catalias == '') {
        $catalias = trim($catname);
    }

    $client = (int) $client;
    $lang = (int) $lang;

    $visible = ($visible == 1)? 1 : 0;
    if (!$perm->have_perm_area_action('str', 'str_makevisible')) {
        $visible = 0;
    }

    $public = ($public == 1)? 1 : 0;
    if (!$perm->have_perm_area_action('str', 'str_makepublic')) {
        $public = 1;
    }

    // Get previous category on same level, if exists
    $oCatColl = new cApiCategoryCollection();
    $oCatColl->select('parentid=' . $parentid . ' AND postid = 0 AND idclient = ' . $client);
    $oPrevCat = $oCatColl->next();
    $preIdcat = (is_object($oPrevCat))? $oPrevCat->get('idcat') : 0;

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
    $aLanguages = array(
        $lang
    );
    foreach ($aLanguages as $curLang) {
        $name = $catname;
        $urlname = conHtmlSpecialChars(cApiStrCleanURLCharacters($catalias), ENT_QUOTES);

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
 * @param int $idcat
 * @param string $poststring
 * @return string
 */
function strOrderedPostTreeList($idcat, $poststring) {
    $oCatColl = new cApiCategoryCollection();
    $oCatColl->select('parentid = 0 AND preid = ' . (int) $idcat . ' AND idcat != 0');
    if (($oCat = $oCatColl->next()) !== false) {
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
 */
function strRemakeTreeTable() {
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
    // @todo: Check this, how it is possible to have an invalid entry with
    // primary key = 0
    $sql = 'DELETE FROM ' . $cfg['tab']['cat'] . ' WHERE idcat = 0';
    $db->query($sql);

    // Delete entries from category language table having idcat = 0
    // @todo: Check this, how it is possible to have an invalid entry with
    // primary key = 0
    $sql = 'DELETE FROM ' . $cfg['tab']['cat_lang'] . ' WHERE idcat = 0';
    $db->query($sql);

    // Get all categories by client
    $sql = "SELECT idcat, parentid, preid, postid FROM " . $cfg['tab']['cat'] . " WHERE idclient = " . (int) $client . " ORDER BY parentid ASC, preid ASC, postid ASC";
    $aCategories = array();
    $db->query($sql);
    while ($db->nextRecord()) {
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
    $db->query($sInsertQuery);
}

/**
 * Sorts passed assoziative categories array.
 *
 * @todo Check logic, move sorting to db layer, if possible!
 * @param array $arr
 * @return array
 */
function strSortPrePost($arr) {
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
            // die(i18n("The list of categories is messed up. The order in the
            // list creates an infinite loop. Check you database."));
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
 * @param array|?? $aCats Assoziative categories array or something else, but
 *            what?
 * @param string $sInsertQuery The insert statement
 * @param array $aAllCats Assoziative categories array holding the complete
 *            category structure
 * @param int $iLevel Category level
 * @return string
 */
function strBuildSqlValues($aCats, $sInsertQuery, &$aAllCats, $iLevel = 0) {
    if (is_array($aCats)) {
        $aCats = strSortPrePost($aCats);
        foreach ($aCats as $aCat) {
            $sInsertQuery .= '(' . (int) $aCat['idcat'] . ', ' . (int) $iLevel . '), ';
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
 *
 * @global int $lang
 * @param int $idcat Category id to check next deeper item
 * @param bool $ignoreLang Flag to check for existing entry in category language
 *            table
 * @return int
 */
function strNextDeeper($idcat, $ignoreLang = false) {
    global $lang;

    $languageId = (true == $ignoreLang)? $lang : null;
    $oCatColl = new cApiCategoryCollection();
    return $oCatColl->getFirstChildCategoryId($idcat, $languageId);
}

/**
 * Checks, if passed category contains any articles
 *
 * @param int $idcat ID of category
 * @return bool
 */
function strHasArticles($idcat) {
    global $lang;

    $oCatArtColl = new cApiCategoryArticleCollection();
    return $oCatArtColl->getHasArticles($idcat, $lang);
}

/**
 * Returns next post category id
 *
 * @param int $idcat ID of category
 * @return int
 */
function strNextPost($idcat) {
    $oCatColl = new cApiCategoryCollection();
    return $oCatColl->getNextPostCategoryId($idcat);
}

/**
 * Returns next backwards category id
 *
 * @param int $idcat ID of category
 * @return int
 */
function strNextBackwards($idcat) {
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
function strNextDeeperAll($idcat, $ignoreLang = false) {
    global $lang;

    $languageId = (true == $ignoreLang)? $lang : null;
    $oCatColl = new cApiCategoryCollection();
    return $oCatColl->getAllChildCategoryIds($idcat, $languageId);
}

/**
 * Renames a category
 *
 * @param int $idcat Category id
 * @param int $lang Language id
 * @param string $newcategoryname New category name
 * @param string $newcategoryalias New category alias
 */
function strRenameCategory($idcat, $lang, $newCategoryName, $newCategoryAlias) {
    if (trim($newCategoryName) == '') {
        return;
    }

    $oCatLang = new cApiCategoryLanguage();
    if (!$oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
        // Couldn't load category language
        return;
    }

    $oldData = array(
        'idcat' => $oCatLang->get('idcat'),
        'name' => $oCatLang->get('name'),
        'urlname' => $oCatLang->get('urlname')
    );

    $name = stripslashes($newCategoryName);
    $urlName = (trim($newCategoryAlias) != '')? trim($newCategoryAlias) : $newCategoryName;

    if (trim($newCategoryAlias) != '') {
        // overfluous assignment
        // if ($oCatLang->get('urlname') != $newCategoryAlias) {
        // $urlName = $newCategoryAlias;
        // }
        cInclude('includes', 'functions.pathresolver.php');
        $client = cRegistry::getClientId();
        prDeleteCacheFileContent($client, $lang);
    }

    $oCatLang->set('name', $name);
    $oCatLang->set('urlname', $urlName);
    $oCatLang->set('lastmodified', date('Y-m-d H:i:s'));
    $oCatLang->store();

    $newData = array(
        'idcat' => $idcat,
        'name' => $name,
        'urlname' => $urlName
    );

    cApiCecHook::execute('Contenido.Category.strRenameCategory', $newData, $oldData);
}

/**
 * Renames a category alias.
 *
 * @param int $idcat Category id
 * @param int $lang Language id
 * @param string $newcategoryalias New category alias
 */
function strRenameCategoryAlias($idcat, $lang, $newcategoryalias) {
    $oCatLang = new cApiCategoryLanguage();
    if (!$oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
        // Couldn't load category language
        return;
    }

    $oldData = array(
        'idcat' => $oCatLang->get('idcat'),
        'urlname' => $oCatLang->get('urlname')
    );

    if (trim($newcategoryalias) == '') {
        // Use categoryname as default -> get it escape it save it as urlname
        $newcategoryalias = $oCatLang->get('name');
    }

    $oCatLang->set('urlname', $newcategoryalias);
    $oCatLang->set('lastmodified', date('Y-m-d H:i:s'));
    $oCatLang->store();

    cInclude('includes', 'functions.pathresolver.php');
    $client = cRegistry::getClientId();
    prDeleteCacheFileContent($client, $lang);

    $newData = array(
        'idcat' => $idcat,
        'urlname' => $newcategoryalias
    );

    cApiCecHook::execute('Contenido.Category.strRenameCategoryAlias', $newData, $oldData);
}

/**
 * Sets the visible status of the category and its childs
 *
 * @param int $idcat Category id
 * @param int $lang Language id
 * @param int $visible Visible status
 */
function strMakeVisible($idcat, $lang, $visible) {
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
 * @param int $idcat Category id
 * @param int $lang Language id
 * @param int $public Public status
 */
function strMakePublic($idcat, $lang, $public) {
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
 * @param int $startIdcat The start category
 * @return array Contains all childs of $startIdcat and $startIdcat start itself
 */
function strDeeperCategoriesArray($startIdcat) {
    global $client;

    $oCatColl = new cApiCategoryCollection();
    return $oCatColl->getAllCategoryIdsRecursive($startIdcat, $client);
}

/**
 * Deletes the category and its template configurations.
 *
 * Only categories having no child categories and having no articles will be
 * deleted!
 *
 * @param int $idcat Id of category to delete
 */
function strDeleteCategory($idcat) {
    global $lang, $lang;

    // Flag to rebuild the category table
    global $remakeCatTable, $remakeStrTable;

    if (strNextDeeper($idcat)) {
        // Category has subcategories
        return '0201';
    } elseif (strHasArticles($idcat)) {
        // Category has articles
        return '0202';
    }

    cInclude('includes', 'functions.rights.php');

    $remakeCatTable = true;
    $remakeStrTable = true;

    // Load category language
    $oCatLang = new cApiCategoryLanguage();
    $oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang);

    if ($oCatLang->isLoaded()) {
        // Delete template configuration (deletes also all container
        // configurations)
        $oTemplateConfigColl = new cApiTemplateConfigurationCollection();
        $oTemplateConfigColl->delete($oCatLang->get('idtplcfg'));

        // Delete category language
        $oCatLangColl = new cApiCategoryLanguageCollection();
        $oCatLangColl->delete($oCatLang->get('idcatlang'));
    }

    // Are there any additional entries for other languages?
    $oCatLangColl = new cApiCategoryLanguageCollection();
    $oCatLangColl->select('idcat = ' . (int) $idcat);
    if (($oCatLang = $oCatLangColl->next()) !== false) {
        // More languages found, delete rights for current category
        deleteRightsForElement('str', $idcat, $lang);
        deleteRightsForElement('con', $idcat, $lang);
        return;
    }

    // Load category
    $oCat = new cApiCategory((int) $idcat);
    $preid = (int) $oCat->get('preid');
    $postid = (int) $oCat->get('postid');

    // Update pre cat, set it to new postid
    if ($preid != 0) {
        $oPreCat = new cApiCategory($preid);
        $oPreCat->set('postid', $postid);
        $oPreCat->store();
    }

    // Update post cat, set it to new preid
    if ($postid != 0) {
        $oPostCat = new cApiCategory($postid);
        $oPostCat->set('preid', $preid);
        $oPostCat->store();
    }

    // Delete category
    $oCatColl = new cApiCategoryCollection();
    $oCatColl->deleteBy('idcat', (int) $idcat);

    $oCatLangColl = new cApiCategoryLanguageCollection();
    $oCatLangColl->select('idcat = ' . (int) $idcat);
    if (($oCatLang = $oCatLangColl->next()) !== false) {
        // Delete template configuration (deletes also all container
        // configurations)
        $oTemplateConfigColl = new cApiTemplateConfigurationCollection();
        $oTemplateConfigColl->delete($oCatLang->get('idtplcfg'));
    }

    // Delete category language entry by category id
    $oCatLangColl->resetQuery();
    $oCatLangColl->deleteBy('idcat', (int) $idcat);

    // Delete category tree entry by category id
    $oCatTreeColl = new cApiCategoryTreeCollection();
    $oCatTreeColl->deleteBy('idcat', (int) $idcat);

    // Delete rights for element
    deleteRightsForElement('str', $idcat);
    deleteRightsForElement('con', $idcat);
}

/**
 * Moves a category upwards.
 *
 * @param int $idcat Id of category to upwards
 */
function strMoveUpCategory($idcat) {
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
    $preIdcat = $oPreCat->get('idcat');

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
        $oPrePreCat->set('postid', $idcat);
        $oPrePreCat->store();
    }

    // Update previous category
    $oPreCat->set('preid', $idcat);
    $oPreCat->set('postid', $postid);
    $oPreCat->store();

    // Update current category
    $oCat->set('preid', $prePreid);
    $oCat->set('postid', $preid);
    $oCat->store();

    // Update post category, if exists!
    $oPostCat->set('preid', $preIdcat);
    $oPostCat->store();
}

/**
 * Moves a category downwards.
 *
 * @param int $idcat Id of category to move downwards
 */
function strMoveDownCategory($idcat) {
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
    $oCat->set('preid', $postid);
    $oCat->set('postid', $postPostid);
    $oCat->store();

    // Update post category
    $oPostCat->set('preid', $preIdcat);
    $oPostCat->set('postid', $idcat);
    $oPostCat->store();

    if ($postPostid != 0) {
        // Update post post category, if exists
        $oPostPostCat = new cApiCategory($postPostid);
        $oPostPostCat->set('preid', $idcat);
        $oPostPostCat->store();
    }
}

/**
 * Moves a subtree to another destination.
 *
 * @param int $idcat Id of category
 * @param int $newParentId Id of destination parent category
 * @param int $newPreId Id of new previous category
 * @param int $newPostId Id of new post category
 */
function strMoveSubtree($idcat, $newParentId, $newPreId = null, $newPostId = null) {
    global $movesubtreeidcat;

    $idlang = cRegistry::getLanguageId();
    $cat = new cApiCategoryCollection();
    $children = $cat->getAllChildCategoryIds($idcat, $idlang);

    foreach ($children as $category) {
        // avoids to move the main tree node in sub node of the same tree
        if ($category == $newParentId) {
            return false;
        }
    }

    if ($idcat == $newParentId) {
        return false;
    }

    if ($newParentId == 0 && $newPreId == 0) {
        return false;
    }
    if (!isset($newPostId)) {
        return false;
    }
    // flag to rebuild the category table
    global $remakeCatTable, $remakeStrTable;

    $remakeCatTable = true;
    $remakeStrTable = true;

    // check the post ID parameter
    if (is_null($newPostId)) {
        $newPostId = 0;
    }

    if ($newParentId == -1) {
        // stop moving the category without actually moving it
        $movesubtreeidcat = 0;
    } else if (is_null($newParentId)) {
        // start moving the category withour moving it yet
        $movesubtreeidcat = $idcat;
    } else {
        // move the category with the ID idcat to the category newParentId
        $category = new cApiCategory($idcat);
        $oldPreId = $category->get('preid');
        $oldPostId = $category->get('postid');

        // update old predecessor (pre) category
        if ($oldPreId != 0) {
            $oldPreCategory = new cApiCategory($oldPreId);
            $oldPreCategory->set('postid', $oldPostId);
            $oldPreCategory->store();
        }

        // update old follower (post) category
        if ($oldPostId != 0) {
            $oldPostCategory = new cApiCategory($oldPostId);
            $oldPostCategory->set('preid', $oldPreId);
            $oldPostCategory->store();
        }

        // update new predecessor (pre) category
        if (is_null($newPreId)) {
            // if no new pre ID has been given, use the last category in the
            // given parent category
            $categoryCollection = new cApiCategoryCollection();
            $categoryCollection->select("parentid = " . $newParentId . " AND postid = 0");
            $newPreCategory = $categoryCollection->next();
        } else {
            $newPreCategory = new cApiCategory($newPreId);
        }
        if ($newPreCategory) {
            $newPreCategory->set('postid', $idcat);
            $newPreCategory->store();
            $newPreId = $newPreCategory->get('idcat');
        } else {
            $newPreId = 0;
        }

        // update new follower (post) category
        if ($newPostId != 0) {
            $newPostCategory = new cApiCategory($newPostId);
            $newPostCategory->set('preid', $idcat);
            $newPostCategory->store();
        }

        // Update current category
        $category->set('parentid', $newParentId);
        $category->set('preid', $newPreId);
        $category->set('postid', $newPostId);
        $category->store();

        $movesubtreeidcat = 0;
    }

    $sess = cRegistry::getSession();
    $sess->register('movesubtreeidcat');
    $sess->freeze();
}

/**
 * Checks if category is movable.
 *
 * @param int $idcat Id of category to move
 * @param int $source Id of source category
 * @return bool
 */
function strMoveCatTargetallowed($idcat, $source) {
    return ($idcat == $source)? 0 : 1;
}

/**
 * Synchronizes a category from one language to another language.
 *
 * @param int $idcatParam Id of category to synchronize
 * @param int $sourcelang Id of source language
 * @param int $targetlang Id of target language
 * @param bool $bMultiple Flag to synchronize child languages
 */
function strSyncCategory($idcatParam, $sourcelang, $targetlang, $bMultiple = false) {
    $bMultiple = (bool) $bMultiple;

    $aCatArray = array();
    if ($bMultiple == true) {
        $aCatArray = strDeeperCategoriesArray($idcatParam);
    } else {
        $aCatArray[] = $idcatParam;
    }

    foreach ($aCatArray as $idcat) {
        // Check if category for target language already exists
        $oCatLang = new cApiCategoryLanguage();
        if ($oCatLang->loadByCategoryIdAndLanguageId($idcat, $targetlang)) {
            return false;
        }

        // Get source category language
        $oCatLang = new cApiCategoryLanguage();
        if ($oCatLang->loadByCategoryIdAndLanguageId($idcat, $sourcelang)) {
            $aRs = $oCatLang->toArray();

            // Copy the template configuration, if exists
            $newidtplcfg = ($aRs['idtplcfg'] != 0)? tplcfgDuplicate($aRs['idtplcfg']) : 0;

            $visible = 0;
            $startidartlang = 0;
            $urlpath = '';

            $oCatLangColl = new cApiCategoryLanguageCollection();
            $oNewCatLang = $oCatLangColl->create($aRs['idcat'], $targetlang, $aRs['name'], $aRs['urlname'], $urlpath, $newidtplcfg, $visible, $aRs['public'], $aRs['status'], $aRs['author'], $startidartlang, $aRs['created'], $aRs['lastmodified']);

            // Execute CEC hook
            $param = $aRs;
            $param['idlang'] = $targetlang;
            $param['idtplcfg'] = (int) $newidtplcfg;
            $param['visible'] = $visible;
            cApiCecHook::execute('Contenido.Category.strSyncCategory_Loop', $param);

            // Set correct rights for element
            cInclude('includes', 'functions.rights.php');
            createRightsForElement('str', $idcat, $targetlang);
            createRightsForElement('con', $idcat, $targetlang);
        }
    }
}

/**
 * Checks if category has a start article
 *
 * @param int $idcat Id of category
 * @param int $idlang The language id
 * @return bool
 */
function strHasStartArticle($idcat, $idlang) {
    $oCatLangColl = new cApiCategoryLanguageCollection();
    return ($oCatLangColl->getStartIdartlangByIdcatAndIdlang($idcat, $idlang) > 0);
}

/**
 * Copies the category and it's existing articles into another category.
 *
 * @param int $idcat Id of category to copy
 * @param int $destidcat Id of destination category
 * @param bool $remakeTree Flag to rebuild category tree
 * @param bool $bUseCopyLabel Flag to add copy label to the new categories
 */
function strCopyCategory($idcat, $destidcat, $remakeTree = true, $bUseCopyLabel = true) {
    global $cfg, $lang;

    $newidcat = (int) strNewCategory($destidcat, 'a', $remakeTree);
    if ($newidcat == 0) {
        return;
    }

    // Load old and new category
    $oOldCatLang = new cApiCategoryLanguage();
    if (!$oOldCatLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
        return;
    }

    $oNewCatLang = new cApiCategoryLanguage();
    if (!$oNewCatLang->loadByCategoryIdAndLanguageId($newidcat, $lang)) {
        return;
    }

    // Worker objects
    $oNewCat = new cApiCategory((int) $newidcat);
    $oOldCat = new cApiCategory((int) $idcat);

    // Copy properties
    if ($bUseCopyLabel == true) {
        $oNewCatLang->set('name', sprintf(i18n('%s (Copy)'), $oOldCatLang->get('name')));
    } else {
        $oNewCatLang->set('name', $oOldCatLang->get('name'));
    }

    $oNewCatLang->set('public', $oOldCatLang->get('public'));
    $oNewCatLang->set('visible', 0);
    $oNewCatLang->store();

    // Execute cec hook
    cApiCecHook::execute('Contenido.Category.strCopyCategory', array(
        'oldcat' => $oOldCat,
        'newcat' => $oNewCat,
        'newcatlang' => $oNewCatLang
    ));

    // Copy template configuration
    if ($oOldCatLang->get('idtplcfg') != 0) {
        // Create new template configuration
        $oNewCatLang->assignTemplate($oOldCatLang->getTemplate());

        // Copy the container configuration
        $oContainerConfColl = new cApiContainerConfigurationCollection();
        $oContainerConfColl->select('idtplcfg = ' . (int) $oOldCatLang->get('idtplcfg'));

        $oNewContainerConfColl = new cApiContainerConfigurationCollection();
        while (($oItem = $oContainerConfColl->next()) !== false) {
            $oNewContainerConfColl->create($oNewCatLang->get('idtplcfg'), $oItem->get('number'), $oItem->get('container'));
        }
    }

    $db = cRegistry::getDb();

    $oCatArtColl = new cApiCategoryArticleCollection();

    // Copy all articles
    $sql = "SELECT A.idart, B.idartlang FROM %s AS A, %s AS B WHERE A.idcat = %d AND B.idart = A.idart AND B.idlang = %s";
    $db->query($sql, $cfg['tab']['cat_art'], $cfg['tab']['art_lang'], $idcat, $lang);

    while ($db->nextRecord()) {
        $newidart = (int) conCopyArticle($db->f('idart'), $newidcat, '', $bUseCopyLabel);
        if ($db->f('idartlang') == $oOldCatLang->get('startidartlang')) {
            $oCatArtColl->resetQuery();
            $idcatart = $oCatArtColl->getIdByCategoryIdAndArticleId($newidcat, $newidart);
            if ($idcatart) {
                conMakeStart($idcatart, 1);
            }
        }
    }

    return $newidcat;
}

/**
 * Copies the categorytree (category and its childs) to an another category.
 *
 * @param int $idcat Id of category to copy
 * @param int $destcat Id of destination category
 * @param bool $remakeTree Flag to rebuild category tree
 * @param bool $bUseCopyLabel Flag to add copy label to the new categories
 */
function strCopyTree($idcat, $destcat, $remakeTree = true, $bUseCopyLabel = true) {
    $newidcat = strCopyCategory($idcat, $destcat, false, $bUseCopyLabel);

    $oCatColl = new cApiCategoryCollection();
    $aIds = $oCatColl->getIdsByWhereClause('parentid = ' . (int) $idcat);
    foreach ($aIds as $id) {
        strCopyTree($id, $newidcat, false, $bUseCopyLabel);
    }

    if ($remakeTree == true) {
        strRemakeTreeTable();
    }
}

/**
 * Assigns a template to passed category.
 *
 * @param int $idcat
 * @param int $client
 * @param int $idTplCfg
 */
function strAssignTemplate($idcat, $client, $idTplCfg) {
    global $perm;

    // Template permission check
    $iIdtplcfg = ($perm->have_perm_area_action('str_tplcfg', 'str_tplcfg'))? (int) $idTplCfg : 0;

    $idtpl = null;
    if ($iIdtplcfg == 0) {
        // Get default template
        $oTemplateColl = new cApiTemplateCollection('defaulttemplate = 1 AND idclient = ' . (int) $client);
        if (($oTemplate = $oTemplateColl->next()) !== false) {
            $idtpl = $oTemplate->get('idtpl');
        }
    } else {
        // Use passed template
        $idtpl = $idTplCfg;
    }

    if ($idtpl) {
        // Assign template
        $oCatLangColl = new cApiCategoryLanguageCollection('idcat = ' . (int) $idcat);
        while (($oCatLang = $oCatLangColl->next()) !== false) {
            $oCatLang->assignTemplate($idtpl);
        }
    }
}
