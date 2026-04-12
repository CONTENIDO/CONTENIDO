<?php

/**
 * This file contains the CONTENIDO structure/category functions.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Olaf Niemann
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
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
 * @return ?int Id of new generated category or null on failure
 * @throws cDbException|cException|cInvalidArgumentException
 */
function strNewTree($catname, $catalias = '', $visible = 0, $public = 1, $iIdtplcfg = 0): ?int
{
    // Flag to rebuild the category table
    global $remakeCatTable, $remakeStrTable;

    $iIdtplcfg = cSecurity::toInteger($iIdtplcfg);
    $visible = $visible == 1 ? 1 : 0;
    $public = $public == 1 ? 1 : 0;

    $client = cRegistry::getClientId();
    $lang = cRegistry::getLanguageId();
    $perm = cRegistry::getPerm();


    if (trim($catname) == '') {
        return null;
    }
    $catname = stripslashes($catname);

    $remakeCatTable = true;
    $remakeStrTable = true;

    $catalias = trim($catalias);
    if ($catalias == '') {
        $catalias = trim($catname);
    }

    if (!$perm->have_perm_area_action('str', 'str_makevisible')) {
        $visible = 0;
    }

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
    $newIdcat = cSecurity::toInteger($oNewCat->get('idcat'));
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
        return null;
    }

    // Loop through languages
    $aLanguages = [$lang];
    foreach ($aLanguages as $curLang) {
        $name = $catname;
        $urlname = conHtmlSpecialChars(cString::cleanURLCharacters($catalias), ENT_QUOTES);

        // Insert new category language entry
        $oCatLangColl = new cApiCategoryLanguageCollection();
        $oCatLangColl->create($newIdcat, $curLang, $name, $urlname, '', 0, $visible, $public, 0, '', 0);

        // Set correct rights for element
        cRights::createRightsForElement('str', $newIdcat, $curLang);
        cRights::createRightsForElement('con', $newIdcat, $curLang);
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
 * @return ?int Id of new generated category or nothing on failure
 * @throws cDbException|cException|cInvalidArgumentException
 */
function strNewCategory(
    $parentid,
    $catname,
    $remakeTree = true,
    $catalias = '',
    $visible = 0,
    $public = 1,
    $iIdtplcfg = 0
): ?int {
    // Flag to rebuild the category table
    global $remakeCatTable, $remakeStrTable;

    $parentid = cSecurity::toInteger($parentid);
    $remakeTree = cSecurity::toBoolean($remakeTree);
    $iIdtplcfg = cSecurity::toInteger($iIdtplcfg);
    $visible = $visible == 1 ? 1 : 0;
    $public = $public == 1 ? 1 : 0;

    $client = cRegistry::getClientId();
    $lang = cRegistry::getLanguageId();
    $perm = cRegistry::getPerm();

    if (trim($catname) == '') {
        return null;
    }
    $catname = stripslashes($catname);

    $remakeCatTable = true;
    $remakeStrTable = true;

    $catalias = trim($catalias);
    if ($catalias == '') {
        $catalias = trim($catname);
    }

    if (!$perm->have_perm_area_action('str', 'str_makevisible')) {
        $visible = 0;
    }

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
    $newIdcat = cSecurity::toInteger($oNewCat->get('idcat'));
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
        return null;
    }

    // Loop through languages
    $aLanguages = [$lang];

    $catalias = conHtmlSpecialChars(cString::cleanURLCharacters($catalias), ENT_QUOTES);

    // Check alias name
    $checkAlias = strCheckAlias($catalias);
    if ($checkAlias === true) {
        // $notification = new cGuiNotification();
        // $notification->displayNotification(cGuiNotification::LEVEL_ERROR, i18n('This alias already exists in a other category. Please try with another alias name again.'));
        // return;
        $catalias = $catalias . $newIdcat;
    }

    foreach ($aLanguages as $curLang) {
        $name = $catname;
        $urlname = $catalias;

        // Insert new category language entry
        $oCatLangColl = new cApiCategoryLanguageCollection();
        $oCatLangColl->create($newIdcat, $curLang, $name, $urlname, '', 0, $visible, $public, 0, '', 0);

        // Set correct rights for element
        cRights::copyRightsForElement('str', $parentid, $newIdcat, $curLang);
        cRights::copyRightsForElement('con', $parentid, $newIdcat, $curLang);
    }

    if ($remakeTree == true) {
        strRemakeTreeTable();
    }

    // Assign template
    strAssignTemplate($newIdcat, $client, $iIdtplcfg);

    return $newIdcat;
}

/**
 * This function check if the alias exists in this language in a other category
 *
 * @param string $catalias
 * @throws cDbException
 */
function strCheckAlias($catalias): bool
{
    $catLangColl = new cApiCategoryLanguageCollection();
    return $catLangColl->select(sprintf(
        "`idlang` = %d AND `urlname` = '%s'",
        cRegistry::getLanguageId(),
        cSecurity::escapeString($catalias)
    ));
}

/**
 * Builds ordered post string for a passed category
 *
 * @param int $idcat
 * @param string $poststring
 * @throws cDbException|cException
 */
function strOrderedPostTreeList($idcat, $poststring): string
{
    $idcat = cSecurity::toInteger($idcat);

    $oCatColl = new cApiCategoryCollection();
    $oCatColl->select('parentid = 0 AND preid = ' . $idcat . ' AND idcat != 0');
    if (($oCat = $oCatColl->next()) !== false) {
        $postIdcat = cSecurity::toInteger($oCat->get('idcat'));
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
 * @throws cDbException|cException
 */
function strRemakeTreeTable()
{
    global $db;

    // Flag to rebuild the category table
    global $remakeCatTable;
    global $remakeStrTable;

    $client = cRegistry::getClientId();

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
    $sql = 'DELETE FROM ' . cDb::getTableName('cat_tree') . ' WHERE idcat IN (' . implode(', ', $idcats) . ')';
    $db->query($sql);

    // Delete entries from category table having idcat = 0
    // @todo: Check this, how it is possible to have an invalid entry with
    // primary key = 0
    $sql = 'DELETE FROM ' . cDb::getTableName('cat') . ' WHERE idcat = 0';
    $db->query($sql);

    // Delete entries from category language table having idcat = 0
    // @todo: Check this, how it is possible to have an invalid entry with
    // primary key = 0
    $sql = 'DELETE FROM ' . cDb::getTableName('cat_lang') . ' WHERE idcat = 0';
    $db->query($sql);

    // Get all categories by client
    $sql = "SELECT idcat, parentid, preid, postid FROM " . cDb::getTableName('cat') . " WHERE idclient = " . (int)$client . " ORDER BY parentid ASC, preid ASC, postid ASC";
    $db->query($sql);

    $aCategories = [];
    while ($db->nextRecord()) {
        $rs = $db->toArray();
        if (!isset($aCategories[$rs['parentid']])) {
            $aCategories[$rs['parentid']] = [];
        }
        $aCategories[$rs['parentid']][$rs['idcat']] = $rs;
    }

    // Build INSERT statement
    $sInsertQuery = "INSERT INTO " . cDb::getTableName('cat_tree') . " (idcat, level) VALUES ";
    $sInsertQuery = strBuildSqlValues($aCategories[0], $sInsertQuery, $aCategories);
    $sInsertQuery = rtrim($sInsertQuery, " ,");

    // Lock db table and execute INSERT query
    $db->query($sInsertQuery);
}

/**
 * Sorts passed associative categories array.
 *
 * @todo Check logic, move sorting to db layer, if possible!
 */
function strSortPrePost(array $arr): array
{
    $firstElement = NULL;
    foreach ($arr as $row) {
        if ($row['preid'] == 0) {
            $firstElement = $row['idcat'];
        }
    }

    $curId = $firstElement;
    $array = [];

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
 * @param array $aCats Associative categories array or something else, but what?
 * @param string $sInsertQuery The insert statement
 * @param array $aAllCats Associative categories array holding the complete category structure
 * @param int $iLevel Category level
 */
function strBuildSqlValues($aCats, $sInsertQuery, &$aAllCats, $iLevel = 0): string
{
    $iLevel = cSecurity::toInteger($iLevel);

    if (is_array($aCats)) {
        $aCats = strSortPrePost($aCats);
        foreach ($aCats as $aCat) {
            $_categoryId = cSecurity::toInteger($aCat['idcat']);
            $sInsertQuery .= '(' . $_categoryId . ', ' . $iLevel . '), ';
            if (isset($aAllCats[$_categoryId]) && is_array($aAllCats[$_categoryId])) {
                $iSubLevel = $iLevel + 1;
                $sInsertQuery = strBuildSqlValues($aAllCats[$_categoryId], $sInsertQuery, $aAllCats, $iSubLevel);
            }
        }
    }
    return $sInsertQuery;
}

/**
 * Returns id of next deeper category.
 *
 * @param int $idcat Category id to check next deeper item
 * @param bool $ignoreLang Flag to check for existing entry in category language table
 * @throws cDbException
 */
function strNextDeeper($idcat, $ignoreLang = false): int
{
    $languageId = $ignoreLang ? NULL : cRegistry::getLanguageId();

    return (new cApiCategoryCollection())
        ->getFirstChildCategoryId(cSecurity::toInteger($idcat), $languageId);
}

/**
 * Checks if the passed category contains any articles
 *
 * @param int $idcat ID of category
 * @throws cDbException|cInvalidArgumentException
 */
function strHasArticles($idcat): bool
{
    return (new cApiCategoryArticleCollection())
        ->getHasArticles(cSecurity::toInteger($idcat), cRegistry::getLanguageId());
}

/**
 * Returns next post category id
 *
 * @param int $idcat ID of category
 * @throws cDbException
 */
function strNextPost($idcat): int
{
    return (new cApiCategoryCollection())
        ->getNextPostCategoryId(cSecurity::toInteger($idcat));
}

/**
 * Returns next backwards category id
 *
 * @param int $idcat ID of category
 * @throws cDbException
 */
function strNextBackwards($idcat): int
{
    return (new cApiCategoryCollection())
        ->getParentsNextPostCategoryId(cSecurity::toInteger($idcat));
}

/**
 * Returns list of child categories.
 *
 * @param int $idcat
 * @param bool $ignoreLang
 * @throws cDbException
 */
function strNextDeeperAll($idcat, $ignoreLang = false): array
{
    $languageId = $ignoreLang ? NULL : cRegistry::getLanguageId();

    return (new cApiCategoryCollection())
        ->getAllChildCategoryIds(cSecurity::toInteger($idcat), $languageId);
}

/**
 * Renames a category
 *
 * @param int $idcat Category id
 * @param int $lang Language id
 * @param string $newCategoryName New category name
 * @param string $newCategoryAlias New category alias
 * @throws cDbException|cException|cInvalidArgumentException
 */
function strRenameCategory($idcat, $lang, $newCategoryName, $newCategoryAlias)
{
    if (trim($newCategoryName) == '') {
        return;
    }

    $idcat = cSecurity::toInteger($idcat);
    $lang = cSecurity::toInteger($lang);

    $oCatLang = new cApiCategoryLanguage();
    if (!$oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
        // Couldn't load category language
        return;
    }

    $oldData = [
        'idcat' => $oCatLang->get('idcat'),
        'name' => $oCatLang->get('name'),
        'urlname' => $oCatLang->get('urlname'),
    ];

    $name = stripslashes($newCategoryName);
    $urlName = (trim($newCategoryAlias) != '') ? trim($newCategoryAlias) : $newCategoryName;

    if (trim($newCategoryAlias) != '') {
        // overfluous assignment
        // if ($oCatLang->get('urlname') != $newCategoryAlias) {
        // $urlName = $newCategoryAlias;
        // }
        cInclude('includes', 'functions.pathresolver.php');
        prDeleteCacheFileContent(cRegistry::getClientId(), $lang);
    }

    $oCatLang->set('name', $name);
    $oCatLang->set('urlname', $urlName);
    $oCatLang->set('lastmodified', date('Y-m-d H:i:s'));
    $oCatLang->store();

    $newData = [
        'idcat' => $idcat,
        'name' => $name,
        'urlname' => $urlName,
    ];

    cApiCecHook::execute('Contenido.Category.strRenameCategory', $newData, $oldData);
}

/**
 * Renames a category alias.
 *
 * @param int $idcat Category id
 * @param int $lang Language id
 * @param string $newcategoryalias New category alias
 * @throws cDbException|cException|cInvalidArgumentException
 */
function strRenameCategoryAlias($idcat, $lang, $newcategoryalias)
{
    $idcat = cSecurity::toInteger($idcat);
    $lang = cSecurity::toInteger($lang);

    $oCatLang = new cApiCategoryLanguage();
    if (!$oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
        // Couldn't load category language
        return;
    }

    $oldData = [
        'idcat' => $oCatLang->get('idcat'),
        'urlname' => $oCatLang->get('urlname'),
    ];

    if (trim($newcategoryalias) == '') {
        // Use categoryname as default -> get it escape it save it as urlname
        $newcategoryalias = $oCatLang->get('name');
    }

    $oCatLang->set('urlname', $newcategoryalias);
    $oCatLang->set('lastmodified', date('Y-m-d H:i:s'));
    $oCatLang->store();

    cInclude('includes', 'functions.pathresolver.php');
    prDeleteCacheFileContent(cRegistry::getClientId(), $lang);

    $newData = [
        'idcat' => $idcat,
        'urlname' => $newcategoryalias,
    ];

    cApiCecHook::execute('Contenido.Category.strRenameCategoryAlias', $newData, $oldData);
}

/**
 * Sets the visible status of the category and its children
 *
 * @param int $idcat Category id
 * @param int $lang Language id
 * @param int $visible Visible status (1 or 0)
 * @throws cDbException|cException|cInvalidArgumentException
 */
function strMakeVisible($idcat, $lang, $visible)
{
    $idcat = cSecurity::toInteger($idcat);
    $lang = cSecurity::toInteger($lang);
    $visible = $visible == 1 ? 1 : 0;

    $categories = strDeeperCategoriesArray($idcat);
    foreach ($categories as $value) {
        $oCatLang = new cApiCategoryLanguage();
        $oCatLang->loadByCategoryIdAndLanguageId($value, $lang);
        $oCatLang->set('visible', $visible);
        $oCatLang->set('lastmodified', date('Y-m-d H:i:s'));
        $oCatLang->store();
    }

    if (cRegistry::getConfigValue('pathresolve_heapcache') && $visible != 0) {
        $oPathresolveCacheColl = new cApiPathresolveCacheCollection();
        $oPathresolveCacheColl->deleteByCategoryAndLanguage($idcat, $lang);
    }
}

/**
 * Sets the public status of the given category and its children for the given language.
 *
 * This is almost the same function as conMakePublic.
 *
 * @param int $idcat Category id
 * @param int $lang Language id
 * @param int $public Public status of the article to set (1 or 0)
 * @throws cDbException|cException|cInvalidArgumentException
 */
function strMakePublic($idcat, $lang, $public)
{
    $idcat = cSecurity::toInteger($idcat);
    $lang = cSecurity::toInteger($lang);
    $public = $public == 1 ? 1 : 0;

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
 * @param int $idcat Category ID to start at
 * @return array idcats of all scions
 * @throws cDbException
 */
function strDeeperCategoriesArray($idcat): array
{
    return (new cApiCategoryCollection())
        ->getAllCategoryIdsRecursive(cSecurity::toInteger($idcat), cRegistry::getClientId());
}

/**
 * Deletes the category and its template configurations.
 *
 * Only categories having no child categories and having no articles will be deleted!
 *
 * @param int $idcat Id of category to delete
 * @return ?string Following error codes or null on success:
 *      - '0201': Category has subcategories
 *      - '0202': Category has articles
 *      - '0600': Category has tree errors
 * @throws cDbException|cException|cInvalidArgumentException
 */
function strDeleteCategory($idcat): ?string
{
    $idcat = cSecurity::toInteger($idcat);

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

    $remakeCatTable = true;
    $remakeStrTable = true;

    // Load category language
    $oCatLang = new cApiCategoryLanguage();
    $oCatLang->loadByCategoryIdAndLanguageId($idcat, $lang);

    if ($oCatLang->isLoaded()) {
        // Delete template configuration (deletes also all container configurations)
        $oTemplateConfigColl = new cApiTemplateConfigurationCollection();
        $oTemplateConfigColl->delete($oCatLang->get('idtplcfg'));

        // Delete category language
        $oCatLangColl = new cApiCategoryLanguageCollection();
        $oCatLangColl->delete($oCatLang->get('idcatlang'));
    }

    // Are there any additional entries for other languages?
    $oCatLangColl = new cApiCategoryLanguageCollection();
    $oCatLangColl->select('idcat = ' . $idcat);
    if (($oCatLang = $oCatLangColl->next()) !== false) {
        // More languages found, delete rights for current category
        cRights::deleteRightsForElement('str', $idcat, $lang);
        cRights::deleteRightsForElement('con', $idcat, $lang);
        return null;
    }

    // Load category
    $oCat = new cApiCategory($idcat);
    $preid = (int)$oCat->get('preid');
    $postid = (int)$oCat->get('postid');

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

    $error = strCheckTreeForErrors([], [$idcat]);
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
    $oCatColl->deleteBy('idcat', $idcat);

    $oCatLangColl = new cApiCategoryLanguageCollection();
    $oCatLangColl->select('idcat = ' . $idcat);
    if (($oCatLang = $oCatLangColl->next()) !== false) {
        // Delete template configuration (deletes also all container configurations)
        $oTemplateConfigColl = new cApiTemplateConfigurationCollection();
        $oTemplateConfigColl->delete($oCatLang->get('idtplcfg'));
    }

    // Delete category language entry by category id
    $oCatLangColl->resetQuery();
    $oCatLangColl->deleteBy('idcat', $idcat);

    // Delete category tree entry by category id
    $oCatTreeColl = new cApiCategoryTreeCollection();
    $oCatTreeColl->deleteBy('idcat', $idcat);

    // Delete rights for element
    cRights::deleteRightsForElement('str', $idcat);
    cRights::deleteRightsForElement('con', $idcat);

    return null;
}

/**
 * Moves a category upwards.
 *
 * @param int $idcat Id of category to move upwards
 * @throws cDbException|cException
 */
function strMoveUpCategory($idcat)
{
    // Flag to rebuild the category table and initializing notification variable
    global $remakeCatTable, $remakeStrTable, $notification;

    $idcat = cSecurity::toInteger($idcat);

    // Load current category
    $oCat = new cApiCategory();
    $oCat->loadByPrimaryKey($idcat);
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
    $oPreCat->loadByPrimaryKey((int)$preid);
    $prePreid = $oPreCat->get('preid');
    $preIdcat = $oPreCat->get('idcat');

    // Load category before previous category
    $oPrePreCat = new cApiCategory();
    if ((int)$prePreid > 0) {
        $oPrePreCat->loadByPrimaryKey((int)$prePreid);
    }

    // Load post category
    $oPostCat = new cApiCategory();
    if ((int)$postid > 0) {
        $oPostCat->loadByPrimaryKey((int)$postid);
    }

    $updateCats = [];

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
        $notification->displayNotification(
            cGuiNotification::LEVEL_WARNING, $string . '<br><br>'
                . i18n('Something went wrong while trying to perform this operation. Please try again.')
        );
    }
}

/**
 * Moves a category downwards.
 *
 * @param int $idcat Id of category to move downwards
 * @throws cDbException|cException
 */
function strMoveDownCategory($idcat)
{
    // Flag to rebuild the category table and initializing notification variable
    global $remakeCatTable, $remakeStrTable, $notification;

    $idcat = cSecurity::toInteger($idcat);

    // Load current category
    $oCat = new cApiCategory();
    $oCat->loadByPrimaryKey($idcat);
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
    if ((int)$preid > 0) {
        $oPreCat->loadByPrimaryKey((int)$preid);
        $preIdcat = (int)$oPreCat->get('idcat');
    } else {
        $preIdcat = 0;
    }

    // Load post category
    $oPostCat = new cApiCategory();
    $oPostCat->loadByPrimaryKey((int)$postid);
    $postIdcat = $oPostCat->get('idcat');
    $postPostid = $oPostCat->get('postid');

    $updateCats = [];

    if ($preIdcat != 0) {
        // Update previous category, if exists
        $oPreCat->set('postid', (int)$postIdcat);
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
        $notification->displayNotification(
            cGuiNotification::LEVEL_WARNING, $string . '<br><br>'
                . i18n('Something went wrong while trying to perform this operation. Please try again.')
        );
    }
}

/**
 * Moves a subtree to another destination.
 *
 * @param int $idcat Id of category
 * @param int $newParentId Id of destination parent category
 * @param int $newPreId Id of new previous category
 * @param int $newPostId Id of new post category
 * @throws cDbException|cException
 */
function strMoveSubtree($idcat, $newParentId, $newPreId = NULL, $newPostId = NULL): bool
{
    global $movesubtreeidcat, $notification;

    $idcat = cSecurity::toInteger($idcat);
    $newParentId = cSecurity::toInteger($newParentId);

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
    // if (!isset($newPostId)) {
    //     return false;
    // }
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
    } elseif (is_null($newParentId)) {
        // start moving the category withour moving it yet
        $movesubtreeidcat = $idcat;
    } else {
        // move the category with the ID idcat to the category newParentId
        $category = new cApiCategory($idcat);
        $oldPreId = $category->get('preid');
        $oldPostId = $category->get('postid');
        $oldParentId = $category->get('parentid');

        $updateCats = [];

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
            if ($newPreCategory != null) {
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
            $notification->displayNotification(
                cGuiNotification::LEVEL_WARNING,
                $string . '<br><br>'
                    . i18n('Something went wrong while trying to perform this operation. Please try again.')
            );
            return false;
        }

        $movesubtreeidcat = 0;
    }

    $sess = cRegistry::getSession();
    $sess->register('movesubtreeidcat');
    $sess->freeze();

    return true;
}

/**
 * Checks if category is movable.
 *
 * @param int $idcat Id of category to move
 * @param int $source Id of source category
 * @return int 1 or 0
 */
function strMoveCatTargetAllowed($idcat, $source): int
{
    return $idcat == $source ? 0 : 1;
}

/**
 * Synchronizes a category from one language to another language.
 *
 * @param int $idcatParam Id of category to synchronize
 * @param int $sourcelang Id of source language
 * @param int $targetlang Id of target language
 * @param bool $bMultiple Flag to synchronize child languages
 * @throws cDbException|cException|cInvalidArgumentException
 */
function strSyncCategory($idcatParam, $sourcelang, $targetlang, $bMultiple = false): bool
{
    $idcatParam = cSecurity::toInteger($idcatParam);
    $sourcelang = cSecurity::toInteger($sourcelang);
    $targetlang = cSecurity::toInteger($targetlang);
    $bMultiple = cSecurity::toBoolean($bMultiple);

    $aCatArray = [];
    if ($bMultiple) {
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
            $oNewCatLang = $oCatLangColl->create(
                $aRs['idcat'],
                $targetlang,
                $aRs['name'],
                $aRs['urlname'],
                $urlpath,
                $newidtplcfg,
                $visible,
                $aRs['public'],
                $aRs['status'],
                $aRs['author'],
                $startidartlang,
                $aRs['created'],
                $aRs['lastmodified']
            );

            // Execute CEC hook
            $param = $aRs;
            $param['idlang'] = $targetlang;
            $param['idtplcfg'] = (int)$newidtplcfg;
            $param['visible'] = $visible;
            cApiCecHook::execute('Contenido.Category.strSyncCategory_Loop', $param);

            // Set correct rights for element
            cRights::createRightsForElement('str', $idcat, $targetlang);
            cRights::createRightsForElement('con', $idcat, $targetlang);
        }
    }

    return true;
}

/**
 * Checks if category has a start article
 *
 * @param int $idcat Id of category
 * @param int $idlang The language id
 * @throws cDbException|cInvalidArgumentException
 */
function strHasStartArticle($idcat, $idlang): bool
{
    $idcat = cSecurity::toInteger($idcat);
    $idlang = cSecurity::toInteger($idlang);
    $oCatLangColl = new cApiCategoryLanguageCollection();

    return ($oCatLangColl->getStartIdartlangByIdcatAndIdlang($idcat, $idlang) > 0);
}

/**
 * Copies the category, and its existing articles into another category.
 *
 * @param int $idcat Id of category to copy
 * @param int $destidcat Id of destination category
 * @param bool $remakeTree Flag to rebuild category tree
 * @param bool $useCopyLabel Flag to add copy label to the new categories
 * @throws cDbException|cException|cInvalidArgumentException
 */
function strCopyCategory($idcat, $destidcat, $remakeTree = true, $useCopyLabel = true): ?int
{
    $idcat = cSecurity::toInteger($idcat);
    $destidcat = cSecurity::toInteger($destidcat);
    $remakeTree = cSecurity::toBoolean($remakeTree);
    $useCopyLabel = cSecurity::toBoolean($useCopyLabel);

    $lang = cRegistry::getLanguageId();

    $newidcat = cSecurity::toInteger(strNewCategory($destidcat, 'a', $remakeTree));
    if ($newidcat == 0) {
        return null;
    }

    // Load old and new category
    $oOldCatLang = new cApiCategoryLanguage();
    if (!$oOldCatLang->loadByCategoryIdAndLanguageId($idcat, $lang)) {
        return null;
    }

    $oNewCatLang = new cApiCategoryLanguage();
    if (!$oNewCatLang->loadByCategoryIdAndLanguageId($newidcat, $lang)) {
        return null;
    }

    // Worker objects
    $oNewCat = new cApiCategory($newidcat);
    $oOldCat = new cApiCategory($idcat);

    // Copy properties
    if ($useCopyLabel) {
        $oNewCatLang->set('name', sprintf(i18n('%s (Copy)'), $oOldCatLang->get('name')));
    } else {
        $oNewCatLang->set('name', $oOldCatLang->get('name'));
    }

    $oNewCatLang->set('public', $oOldCatLang->get('public'));
    $oNewCatLang->set('visible', 0);
    $oNewCatLang->store();

    // Execute cec hook
    cApiCecHook::execute(
        'Contenido.Category.strCopyCategory',
        [
            'oldcat' => $oOldCat,
            'newcat' => $oNewCat,
            'newcatlang' => $oNewCatLang,
        ]
    );

    // Copy template configuration
    if ($oOldCatLang->get('idtplcfg') != 0) {
        // Create new template configuration
        $oNewCatLang->assignTemplate($oOldCatLang->getTemplate());

        // Copy the container configuration
        $oContainerConfColl = new cApiContainerConfigurationCollection();
        $oContainerConfColl->select('idtplcfg = ' . (int)$oOldCatLang->get('idtplcfg'));

        $oNewContainerConfColl = new cApiContainerConfigurationCollection();
        while ($oItem = $oContainerConfColl->next()) {
            $oNewContainerConfColl->create($oNewCatLang->get('idtplcfg'), $oItem->get('number'), $oItem->get('container'));
        }
    }

    $db = cRegistry::getDb();

    $oCatArtColl = new cApiCategoryArticleCollection();

    // Copy all articles
    $sql = "SELECT A.idart, B.idartlang FROM %s AS A, %s AS B WHERE A.idcat = %d AND B.idart = A.idart AND B.idlang = %s";
    $db->query($sql, cDb::getTableName('cat_art'), cDb::getTableName('art_lang'), $idcat, $lang);

    while ($db->nextRecord()) {
        $newidart = (int)conCopyArticle($db->f('idart'), $newidcat, '', $useCopyLabel);
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
 * Copies the categorytree (category and its children) to another category.
 *
 * @param int $idcat Id of category to copy
 * @param int $destidcat Id of destination category
 * @param bool $remakeTree Flag to rebuild category tree
 * @param bool $useCopyLabel Flag to add copy label to the new categories
 * @throws cDbException|cException|cInvalidArgumentException
 */
function strCopyTree($idcat, $destidcat, $remakeTree = true, $useCopyLabel = true)
{
    $idcat = cSecurity::toInteger($idcat);
    $destidcat = cSecurity::toInteger($destidcat);
    $remakeTree = cSecurity::toBoolean($remakeTree);
    $useCopyLabel = cSecurity::toBoolean($useCopyLabel);

    $newidcat = strCopyCategory($idcat, $destidcat, false, $useCopyLabel);

    $oCatColl = new cApiCategoryCollection();
    $aIds = $oCatColl->getIdsByWhereClause('parentid = ' . $idcat);
    foreach ($aIds as $id) {
        strCopyTree($id, $newidcat, false, $useCopyLabel);
    }

    if ($remakeTree) {
        strRemakeTreeTable();
    }
}

/**
 * Assigns a template to passed category.
 *
 * @param int $idcat
 * @param int $client
 * @param int $idTplCfg
 * @throws cDbException|cException
 */
function strAssignTemplate($idcat, $client, $idTplCfg)
{
    $idcat = cSecurity::toInteger($idcat);
    $client = cSecurity::toInteger($client);
    $idTplCfg = cSecurity::toInteger($idTplCfg);

    $perm = cRegistry::getPerm();

    // Template permission check
    $iIdtplcfg = ($perm->have_perm_area_action('str_tplcfg', 'str_tplcfg')) ? $idTplCfg : 0;

    $idtpl = NULL;
    if ($iIdtplcfg == 0) {
        // Get default template
        $oTemplateColl = new cApiTemplateCollection('defaulttemplate = 1 AND idclient = ' . $client);
        if (($oTemplate = $oTemplateColl->next()) !== false) {
            $idtpl = $oTemplate->get('idtpl');
        }
    } else {
        // Use passed template
        $idtpl = $idTplCfg;
    }

    if ($idtpl) {
        // Assign template
        $oCatLangColl = new cApiCategoryLanguageCollection('idcat = ' . $idcat);
        while ($oCatLang = $oCatLangColl->next()) {
            $oCatLang->assignTemplate($idtpl);
        }
    }
}

/**
 * Checks the category tree for errors
 * Returns FALSE if there are NO errors.
 * If there are errors, an array with error messages will be returned
 *
 * @param array $addCats An array of cApiCategory objects which overwrite categories from the database
 * @param array $ignoreCats An array of idcat's which will be treated like they don't exist in the database
 * @return array|bool An array of error messages if something is wrong.
 *      If nothing is wrong false will be returned.
 * @throws cDbException|cException
 */
function strCheckTreeForErrors(array $addCats = [], array $ignoreCats = []): bool|array
{
    $errorMessages = [];

    // Get all categories into memory
    $cats = new cApiCategoryCollection();
    $cats->select("idclient = '" . cRegistry::getClientId() . "'");

    $catArray = [];
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
    $parents = [];
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
        $preIds = [];
        $postIds = [];
        foreach ($parent as $idcat => $cat) {
            $preId = $cat->get('preid');
            $postId = $cat->get('postid');
            if (in_array($preId, $preIds)) {
                $fine = false;
                $errorMessages[] = sprintf(i18n('There are multiple categories in %s that share the same pre-id (%s - second occurrence at %s). Sorting will fail and not all categories will be shown.'), $parentId, $preId, $idcat);
            }
            if (in_array($postId, $postIds)) {
                $fine = false;
                $errorMessages[] = sprintf(i18n('There are multiple categories in %s that share the same post-id (%s - second occurrence at %s). Sorting will fail and not all categories will be shown.'), $parentId, $postId, $idcat);
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
        $checkedCats = [];
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
        $checkedCats = [];
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
        $messages = [];
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
