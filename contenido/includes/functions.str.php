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
 * @param string $catname
 *         The category name
 * @param string $catalias
 *         Alias of category
 * @param int $visible
 *         Flag about visible status
 * @param int $public
 *         Flag about public status
 * @param int $iIdtplcfg
 *         Id of template configuration
 * @return int|NULL
 *         of new generated category or nothing on failure
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
    $oldPostId = -1;

    // Update last category tree
    if (is_object($oLastCatTree)) {
        $oldPostId = $oLastCatTree->get('postid');
        $oLastCatTree->set('postid', $newIdcat);
        $oLastCatTree->store();
    }

    $error = strCheckTreeForErrors();
    if (!($error === false)) {
        if ($oldPostId != -1) {
            $oLastCatTree->set('postid', $oldPostId);
            $oLastCatTree->store();
        }
        $oCatColl->delete($oNewCat->get('idcat'));
        return;
    }

    cInclude('includes', 'functions.rights.php');

    // Loop through languages
    $aLanguages = array(
        $lang
    );
    foreach ($aLanguages as $curLang) {
        $name = $catname;
        $urlname = conHtmlSpecialChars(cString::cleanURLCharacters($catalias), ENT_QUOTES);

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
 * @param int $parentid
 *         Id of parent category
 * @param string $catname
 *         The category name
 * @param bool $remakeTree
 *         Flag to rebuild category tree structure
 * @param string $catalias
 *         Alias of category
 * @param int $visible
 *         Flag about visible status
 * @param int $public
 *         Flag about public status
 * @param int $iIdtplcfg
 *         Id of template configuration
 * @return int|NULL
 *         of new generated category or nothing on failure
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
    $oCatColl->select('parentid=' . $parentid . ' AND postid = 0 AND idclient = ' . $client);
    $oPrevCat = $oCatColl->next();
    $preIdcat = (is_object($oPrevCat)) ? $oPrevCat->get('idcat') : 0;

    // Insert new category tree
    $oCatColl2 = new cApiCategoryCollection();
    $oNewCat = $oCatColl2->create($client, $parentid, $preIdcat, 0);
    $newIdcat = $oNewCat->get('idcat');
    $oldPostId = -1;

    // Update previous category, if exists
    if (is_object($oPrevCat)) {
        $oldPostId = $oPrevCat->get('postid');
        $oPrevCat->set('postid', $newIdcat);
        $oPrevCat->set('lastmodified', date('Y-m-d H:i:s'));
        $oPrevCat->store();
    }

    $error = strCheckTreeForErrors();

    if (!($error === false)) {
        if ($oldPostId != -1) {
            $oPrevCat->set('postid', $oldPostId);
            $oPrevCat->store();
        }
        $oCatColl2->delete($oNewCat->get('idcat'));
        return;
    }

    cInclude('includes', 'functions.rights.php');

    // Loop through languages
    $aLanguages = array(
        $lang
    );
    foreach ($aLanguages as $curLang) {
        $name = $catname;
        $urlname = conHtmlSpecialChars(cString::cleanURLCharacters($catalias), ENT_QUOTES);

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

    $errors = strCheckTreeForErrors();
    if (!($errors === false)) {
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
    $firstElement = NULL;
    foreach ($arr as $row) {
        if ($row['preid'] == 0) {
            $firstElement = $row['idcat'];
        }
    }

    $curId = $firstElement;
    $array = array();

    // Test for a last element in the category list
    $fine = false;
    foreach ($arr as $row) {
        if ($row['postid'] == 0) {
            $fine = true;
            break;
        }
    }
    if (!$fine) {
        die(); // we already displayed an error message through strCheckTree
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
 * @param array $aCats
 *         Assoziative categories array or something else, but what?
 * @param string $sInsertQuery
 *         The insert statement
 * @param array $aAllCats
 *         Assoziative categories array holding the complete category structure
 * @param int $iLevel
 *         Category level
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
 * @param int $idcat
 *         Category id to check next deeper item
 * @param bool $ignoreLang
 *         Flag to check for existing entry in category language table
 * @return int
 */
function strNextDeeper($idcat, $ignoreLang = false) {
	$lang = cRegistry::getLanguageId();

    $languageId = (true == $ignoreLang) ? NULL : $lang;
    $oCatColl = new cApiCategoryCollection();
    return $oCatColl->getFirstChildCategoryId($idcat, $languageId);
}

/**
 * Checks, if passed category contains any articles
 *
 * @param int $idcat
 *         ID of category
 * @return bool
 */
function strHasArticles($idcat) {
    $lang = cRegistry::getLanguageId();

    $oCatArtColl = new cApiCategoryArticleCollection();
    return $oCatArtColl->getHasArticles($idcat, $lang);
}

/**
 * Returns next post category id
 *
 * @param int $idcat
 *         ID of category
 * @return int
 */
function strNextPost($idcat) {
    $oCatColl = new cApiCategoryCollection();
    return $oCatColl->getNextPostCategoryId($idcat);
}

/**
 * Returns next backwards category id
 *
 * @param int $idcat
 *         ID of category
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

    $languageId = (true == $ignoreLang) ? NULL : $lang;
    $oCatColl = new cApiCategoryCollection();
    return $oCatColl->getAllChildCategoryIds($idcat, $languageId);
}

/**
 * Renames a category
 *
 * @param int $idcat
 *         Category id
 * @param int $lang
 *         Language id
 * @param string $newcategoryname
 *         New category name
 * @param string $newcategoryalias
 *         New category alias
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
    $urlName = (trim($newCategoryAlias) != '') ? trim($newCategoryAlias) : $newCategoryName;

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
 * @param int $idcat
 *         Category id
 * @param int $lang
 *         Language id
 * @param string $newcategoryalias
 *         New category alias
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
 * @param int $idcat
 *         Category id
 * @param int $lang
 *         Language id
 * @param int $visible
 *         Visible status
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
 * Sets the public status of the given category and its children
 * for the given language.
 *
 * This is almost the same function as conMakePublic.
 *
 * @param int $idcat
 *         category id
 * @param int $lang
 *         language id
 * @param int $public
 *         public status of the article to set
 */
function strMakePublic($idcat, $lang, $public) {

    foreach (strDeeperCategoriesArray($idcat) as $tmpIdcat) {
        $oCatLang = new cApiCategoryLanguage();
        $oCatLang->loadByCategoryIdAndLanguageId($tmpIdcat, $lang);
        $oCatLang->set('public', $public);
        $oCatLang->set('lastmodified', date('Y-m-d H:i:s'));
        $oCatLang->store();
    }

}

/**
 * Return a list of idcats of all scions of given category.
 *
 * @param int $idcat
 *         category ID to start at
 * @return array
 *         idcats of all scions
 */
function strDeeperCategoriesArray($idcat) {
    global $client;

    $coll = new cApiCategoryCollection();
    $idcats = $coll->getAllCategoryIdsRecursive($idcat, $client);

    return $idcats;
}

/**
 * Deletes the category and its template configurations.
 *
 * Only categories having no child categories and having no articles will be
 * deleted!
 *
 * @param int $idcat
 *         Id of category to delete
 * @return void|string
 */
function strDeleteCategory($idcat) {
    $lang = cRegistry::getLanguageId();

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

    $error = strCheckTreeForErrors(array(), array(
        $idcat
    ));
    if (!($error === false)) {
        if ($preid != 0) {
            $oPreCat = new cApiCategory($preid);
            $oPreCat->set('postid', $idcat);
            $oPreCat->store();
        }
        if ($postid != 0) {
            $oPostCat = new cApiCategory($postid);
            $oPostCat->set('preid', $idcat);
            $oPostCat->store();
        }
        return '0600';
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
 * @param int $idcat
 *         Id of category to move upwards
 */
function strMoveUpCategory($idcat) {
    // Flag to rebuild the category table and initializing notification variable
    global $remakeCatTable, $remakeStrTable, $notification;

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

    $updateCats = array();

    // Update category before previous, if exists
    if ($oPrePreCat->isLoaded()) {
        $oPrePreCat->set('postid', $idcat);
        $updateCats[$prePreid] = $oPrePreCat;
    }

    // Update previous category
    $oPreCat->set('preid', $idcat);
    $oPreCat->set('postid', $postid);
    $updateCats[$preid] = $oPreCat;

    // Update current category
    $oCat->set('preid', $prePreid);
    $oCat->set('postid', $preid);
    $updateCats[$idcat] = $oCat;

    // Update post category, if exists!
    $oPostCat->set('preid', $preIdcat);
    $updateCats[$postid] = $oPostCat;

    $error = strCheckTreeForErrors($updateCats);
    if ($error === false) {
        foreach ($updateCats as $cat) {
            $cat->store();
        }
    } else {
        $string = '';
        foreach ($error as $msg) {
            $string .= $msg . '<br>';
        }
        $notification->displayNotification(cGuiNotification::LEVEL_WARNING, $msg . '<br><br>' . i18n('Something went wrong while trying to perform this operation. Please try again.'));
        return;
    }
}

/**
 * Moves a category downwards.
 *
 * @param int $idcat
 *         Id of category to move downwards
 */
function strMoveDownCategory($idcat) {
    // Flag to rebuild the category table and initializing notification variable
    global $remakeCatTable, $remakeStrTable, $notification;

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

    $updateCats = array();

    if ($preIdcat != 0) {
        // Update previous category, if exists
        $oPreCat->set('postid', (int) $postIdcat);
        $updateCats[$preIdcat] = $oPreCat;
    }

    // Update current category
    $oCat->set('preid', $postid);
    $oCat->set('postid', $postPostid);
    $updateCats[$idcat] = $oCat;

    // Update post category
    $oPostCat->set('preid', $preIdcat);
    $oPostCat->set('postid', $idcat);
    $updateCats[$postid] = $oPostCat;

    if ($postPostid != 0) {
        // Update post post category, if exists
        $oPostPostCat = new cApiCategory($postPostid);
        $oPostPostCat->set('preid', $idcat);
        $updateCats[$postPostid] = $oPostPostCat;
    }

    $error = strCheckTreeForErrors($updateCats);
    if ($error === false) {
        foreach ($updateCats as $cat) {
            $cat->store();
        }
    } else {
        $string = '';
        foreach ($error as $msg) {
            $string .= $msg . '<br>';
        }
        $notification->displayNotification(cGuiNotification::LEVEL_WARNING, $msg . '<br><br>' . i18n('Something went wrong while trying to perform this operation. Please try again.'));
        return;
    }
}

/**
 * Moves a subtree to another destination.
 *
 * @param int $idcat
 *         Id of category
 * @param int $newParentId
 *         Id of destination parent category
 * @param int $newPreId
 *         Id of new previous category
 * @param int $newPostId
 *         Id of new post category
 * @return bool
 */
function strMoveSubtree($idcat, $newParentId, $newPreId = NULL, $newPostId = NULL) {
    global $movesubtreeidcat, $notification;

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
        // return false;
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
        $oldParentId = $category->get('parentid');

        $updateCats = array();

        // update old predecessor (pre) category
        if ($oldPreId != 0) {
            $oldPreCategory = new cApiCategory($oldPreId);
            $oldPreCategory->set('postid', $oldPostId);
            $updateCats[$oldPreId] = $oldPreCategory;
        }

        // update old follower (post) category
        if ($oldPostId != 0) {
            if (isset($updateCats[$oldPostId])) {
                $updateCats[$oldPostId]->set('preid', $oldPreId);
            } else {
                $oldPostCategory = new cApiCategory($oldPostId);
                $oldPostCategory->set('preid', $oldPreId);
                $updateCats[$oldPostId] = $oldPostCategory;
            }
        }

        // update new predecessor (pre) category
        if (is_null($newPreId)) {
            // if no new pre ID has been given, use the last category in the
            // given parent category
            $categoryCollection = new cApiCategoryCollection();
            $categoryCollection->select("parentid = " . $newParentId . " AND postid = 0");
            $newPreCategory = $categoryCollection->next();
            if($newPreCategory != null) {
                $newPreId = $newPreCategory->get('idcat');
                $newPreCategory->set('postid', $idcat);
                $updateCats[$newPreId] = $newPreCategory;
            }
        } else {
            if (isset($updateCats[$newPreId])) {
                $updateCats[$newPreId]->set('postid', $idcat);
            } else {
                $newPreCategory = new cApiCategory($newPreId);
                $newPreCategory->set('postid', $idcat);
                $updateCats[$newPreId] = $newPreCategory;
                $newPreId = $newPreCategory->get('idcat');
            }
        }

        // update new follower (post) category
        if ($newPostId != 0) {
            if (isset($updateCats[$newPostId])) {
                $updateCats[$newPostId]->set('preid', $idcat);
            } else {
                $newPostCategory = new cApiCategory($newPostId);
                $newPostCategory->set('preid', $idcat);
                $updateCats[$newPostId] = $newPostCategory;
            }
        }

        // Update current category
        $category->set('parentid', $newParentId);
        $category->set('preid', $newPreId);
        $category->set('postid', $newPostId);
        $updateCats[$idcat] = $category;

        $error = strCheckTreeForErrors($updateCats);
        if ($error === false) {
            foreach ($updateCats as $cat) {
                $cat->store();
            }
        } else {
            $string = '';
            foreach ($error as $msg) {
                $string .= $msg . '<br>';
            }
            $notification->displayNotification(cGuiNotification::LEVEL_WARNING, $msg . '<br><br>' . i18n('Something went wrong while trying to perform this operation. Please try again.'));
            return false;
        }

        $movesubtreeidcat = 0;
    }

    $sess = cRegistry::getSession();
    $sess->register('movesubtreeidcat');
    $sess->freeze();
}

/**
 * Checks if category is movable.
 *
 * @param int $idcat
 *         Id of category to move
 * @param int $source
 *         Id of source category
 * @return bool
 */
function strMoveCatTargetallowed($idcat, $source) {
    return ($idcat == $source) ? 0 : 1;
}

/**
 * Synchronizes a category from one language to another language.
 *
 * @param int $idcatParam
 *         Id of category to synchronize
 * @param int $sourcelang
 *         Id of source language
 * @param int $targetlang
 *         Id of target language
 * @param bool $bMultiple
 *         Flag to synchronize child languages
 * @return boolean
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
            $newidtplcfg = ($aRs['idtplcfg'] != 0) ? tplcfgDuplicate($aRs['idtplcfg']) : 0;

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
 * @param int $idcat
 *         Id of category
 * @param int $idlang
 *         The language id
 * @return bool
 */
function strHasStartArticle($idcat, $idlang) {
    $oCatLangColl = new cApiCategoryLanguageCollection();
    return ($oCatLangColl->getStartIdartlangByIdcatAndIdlang($idcat, $idlang) > 0);
}

/**
 * Copies the category and it's existing articles into another category.
 *
 * @param int $idcat
 *         Id of category to copy
 * @param int $destidcat
 *         Id of destination category
 * @param bool $remakeTree
 *         Flag to rebuild category tree
 * @param bool $bUseCopyLabel
 *         Flag to add copy label to the new categories
 * @return void|int
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
 * @param int $idcat
 *         Id of category to copy
 * @param int $destcat
 *         Id of destination category
 * @param bool $remakeTree
 *         Flag to rebuild category tree
 * @param bool $bUseCopyLabel
 *         Flag to add copy label to the new categories
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
    $iIdtplcfg = ($perm->have_perm_area_action('str_tplcfg', 'str_tplcfg')) ? (int) $idTplCfg : 0;

    $idtpl = NULL;
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

/**
 * Checks the category tree for errors
 * Returns FALSE if there are NO errors.
 * If there are errors, an array with error messages will be returned
 *
 * @param array $addCats
 *         An array of cApiCategory objects which overwrite categories from the database
 * @param array $ignoreCats
 *         An array of idcat's which will be treated like they don't exist in the database
 * @return array|bool
 *         An array of error messages if something is wrong.
 *         If nothing is wrong false will be returned.
 */
function strCheckTreeForErrors($addCats = array(), $ignoreCats = array()) {
    $errorMessages = array();

    // Get all categories into memory
    $cats = new cApiCategoryCollection();
    $cats->select("idclient = '" . cSecurity::toInteger(cRegistry::getClientId()) . "'");

    $catArray = array();
    // first add the ones from the parameters
    foreach ($addCats as $addCat) {
        if ($addCat->get('idcat') == 0) {
            continue;
        }
        $catArray[$addCat->get('idcat')] = $addCat;
    }

    // add every category from the database
    while ($cat = $cats->next()) {
        if (in_array($cat->get('idcat'), $ignoreCats)) {
            continue;
        }
        if (isset($catArray[$cat->get('idcat')])) {
            continue;
        }
        $catArray[$cat->get('idcat')] = $cat;
    }

    ksort($catArray);

    // build an array with the parentids at the top level and every child
    // category as member
    // aka
    // $parents[parentId][catIdOfChildToParentId] =
    // cApiCategory(catIdOfChildToParent)
    // check if every parent that is mentioned in the database actually exists
    $fine = true;
    $parents = array();
    foreach ($catArray as $idcat => $cat) {
        if (!array_key_exists($cat->get('parentid'), $catArray) && $cat->get('parentid') != 0) {
            $fine = false;
            $errorMessages[] = sprintf(i18n('Category %s has a parent id (%s) which does not exist!'), $idcat, $cat->get('parentid'));
        }
        $parents[$cat->get('parentid')][$idcat] = $cat;
    }

    // check for consistency in every parent
    foreach ($parents as $parentId => $parent) {
        // first, check for multiple preids and postids
        // the category tree will miss some categories if multiple categories
        // share preids and/or postids
        $preIds = array();
        $postIds = array();
        foreach ($parent as $idcat => $cat) {
            $preId = $cat->get('preid');
            $postId = $cat->get('postid');
            if (in_array($preId, $preIds)) {
                $fine = false;
                $errorMessages[] = sprintf(i18n('There are multiple categories in %s that share the same pre-id (%s - second occurence at %s). Sorting will fail and not all categories will be shown.'), $parentId, $preId, $idcat);
            }
            if (in_array($postId, $postIds)) {
                $fine = false;
                $errorMessages[] = sprintf(i18n('There are multiple categories in %s that share the same post-id (%s - second occurence at %s). Sorting will fail and not all categories will be shown.'), $parentId, $postId, $idcat);
            }
            $preIds[] = $preId;
            $postIds[] = $postId;
        }

        // check the consistency of the postids
        // find the start
        $startCat = null;
        foreach ($parent as $cat) {
            if ($cat->get('preid') == 0) {
                $startCat = $cat;
                break;
            }
        }
        // if not start was found then something is wrong
        if ($startCat == null) {
            $fine = false;
            $errorMessages[] = sprintf(i18n('There is no defined start (a category with preid == 0) in %s. Sorting impossible.'), $parentId);
            continue;
        }
        // loop through the categories using the postid
        $actCat = $startCat;
        $checkedCats = array();
        $checkedCats[] = $startCat->get('idcat');
        while ($actCat != null) {
            $catId = $actCat->get('idcat');
            $postId = $actCat->get('postid');
            if ($postId == 0) {
                break;
            }
            // check if the postid is actually a child of the parent
            if (!array_key_exists($postId, $parent)) {
                $fine = false;
                $errorMessages[] = sprintf(i18n('%s has an invalid post-id (%s). The category does not exist in this parent! Sorting impossible.'), $catId, $postId);
                break;
            }
            $actCat = $catArray[$postId];
            // check if the postid was seen before. if yes that would mean
            // there's a loop in the tree
            if (in_array($actCat->get('idcat'), $checkedCats)) {
                $fine = false;
                $errorMessages[] = sprintf(i18n('The sorting in category %s creates an infinite loop (postid = %s). Sorting the category is impossible! (Cause of failure is near category %s)'), $parentId, $postId, $catId);
                break;
            }
            $checkedCats[] = $actCat->get('idcat');

            // check that all categories in this parent belong to the same
            // client
            if (isset($catArray[$parentId])) {
                $parentClientId = $catArray[$parentId]->get('idclient');
                if ($actCat->get('idclient') != $parentClientId) {
                    $fine = false;
                    $errorMessages[] = sprintf(i18n('The category %s has a sub category (%s) that belongs to another client!'), $parentId, $catId);
                    break;
                }
            }
        }

        // check the consistency of the preids
        // find the last element (which is the start of the preids)
        $startCat = null;
        foreach ($parent as $cat) {
            if ($cat->get('postid') == 0) {
                $startCat = $cat;
                break;
            }
        }
        // if no end was found => error (this most likely means there's some
        // kind of loop too)
        if ($startCat == null) {
            $fine = false;
            $errorMessages[] = sprintf(i18n('There is no defined end (a category with postid == 0) in %s. Sorting impossible.'), $parentId);
            continue;
        }
        // loop through the categories using the preid
        $actCat = $startCat;
        $checkedCats = array();
        $checkedCats[] = $startCat->get('idcat');
        while ($actCat != null) {
            $catId = $actCat->get('idcat');
            $preId = $actCat->get('preid');
            if ($preId == 0) {
                break;
            }
            // if the preid isn't a child of the parent => error
            if (!array_key_exists($preId, $parent)) {
                $fine = false;
                $errorMessages[] = sprintf(i18n('%s has an invalid pre-id (%s). The category does not exist in this parent! Sorting impossible.'), $catId, $preId);
                break;
            }
            $actCat = $catArray[$preId];
            // if we've seen this preid before, that means there is some kind of
            // loop => error
            if (in_array($actCat->get('idcat'), $checkedCats)) {
                $fine = false;
                $errorMessages[] = sprintf(i18n('The sorting in category %s creates an infinite loop (preid = %s). Sorting the category is impossible! (Cause of failure is near category %s)'), $parentId, $preId, $catId);
                break;
            }
            $checkedCats[] = $actCat->get('idcat');
        }
    }
    // if everything is fine, return false
    // otherwise return the collected error messages
    if (!$fine) {
        $messages = array();
        foreach ($errorMessages as $errorMessage) {
            if (in_array($errorMessage, $messages)) {
                continue;
            }
            $messages[] = $errorMessage;
        }
        return $messages;
    } else {
        return false;
    }
}
