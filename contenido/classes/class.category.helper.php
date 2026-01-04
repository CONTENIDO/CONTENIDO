<?php

/**
 * This file contains the category helper class.
 *
 * @package    Core
 * @subpackage Frontend_Util
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions for the category helper class in CONTENIDO.
 *
 * @package    Core
 * @subpackage Frontend_Util
 */
class cCategoryHelper
{

    /**
     * Instance of the helper class.
     *
     * @var ?cCategoryHelper
     */
    private static $_instance = NULL;

    /**
     * Local stored language ID
     *
     * @var int language ID
     */
    protected $_languageId = 0;

    /**
     * Local stored client ID
     *
     * @var int client ID
     */
    protected $_clientId = 0;

    /**
     * Local cache of category levels.
     *
     * @var array
     */
    protected $_levelCache = [];

    /**
     * Auth object to use.
     *
     * @var ?cAuth
     */
    protected $_auth = NULL;

    /**
     * Array with current frontend user groups.
     *
     * @var array
     */
    protected $_feGroups = [];

    /**
     * Object for frontend permission collection.
     *
     * @var ?cApiFrontendPermissionCollection
     */
    protected $_fePermColl = NULL;

    /**
     * Returns the instance of this class.
     */
    public static function getInstance(): self
    {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Constructor to create an instance of this class.
     */
    protected function __construct()
    {
    }

    /**
     * Sets an auth object to use on category access check.
     *
     * @throws cException
     */
    public function setAuth(cAuth $auth)
    {
        $this->_auth = $auth;

        $feUser = new cApiFrontendUser($auth->getUserId());
        if ($feUser->isLoaded() === true) {
            $this->_feGroups = $feUser->getGroupsForUser();
        }

        $this->_fePermColl = new cApiFrontendPermissionCollection();
    }

    /**
     * Returns the local stored client ID
     *
     * @throws cInvalidArgumentException if no active client ID specified or found
     */
    public function getClientId(): int
    {
        if ($this->_clientId == 0) {
            $clientId = cRegistry::getClientId();
            if ($clientId == 0) {
                throw new cInvalidArgumentException('No active client ID specified or found.');
            }

            return $clientId;
        }

        return $this->_clientId;
    }

    /**
     * Sets the client ID to store it locally in the class.
     */
    public function setClientId(int $clientId = 0)
    {
        $this->_clientId = $clientId;
    }

    /**
     * Returns the local stored language ID
     *
     * @throws cInvalidArgumentException If no active language ID specified or found
     */
    public function getLanguageId(): int
    {
        if ($this->_languageId == 0) {
            $languageId = cRegistry::getLanguageId();
            if ($languageId == 0) {
                throw new cInvalidArgumentException('No active language ID specified or found.');
            }

            return $languageId;
        }

        return $this->_languageId;
    }

    /**
     * Sets the language ID to store it locally in the class.
     */
    public function setLanguageId(int $languageId = 0)
    {
        $this->_languageId = $languageId;
    }

    /**
     * Return the ID of the top most category based on a given category ID.
     *
     * @param int $categoryId Base category ID to search on
     * @return int Top most category ID
     * @throws cDbException|cException
     */
    public function getTopMostCategoryId(int $categoryId): int
    {
        $category = new cApiCategory($categoryId);

        if ($category->get('parentid') == 0) {
            $topMostCategoryId = $categoryId;
        } else {
            $topMostCategoryId = $this->getTopMostCategoryId((int) $category->get('parentid'));
        }

        return $topMostCategoryId;
    }

    /**
     * Returns an array with ordered cApiCategoryLanguage objects e.g. for a breadcrumb.
     *
     * @param int $categoryId Last category ID in list.
     * @param int $startingLevel Define here, at which level the list should start.
     * @param int $maxDepth Amount of the max depth of categories.
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function getCategoryPath($categoryId, $startingLevel = 1, $maxDepth = 20): array
    {
        $languageId = $this->getLanguageId();

        $categoryLanguage = new cApiCategoryLanguage();
        $categoryLanguage->loadByCategoryIdAndLanguageId($categoryId, $languageId);

        $categories = [];
        if ($this->hasCategoryAccess($categoryLanguage) === true) {
            $categories[] = $categoryLanguage;
        }

        $parentCategoryIds = $this->getParentCategoryIds($categoryId, $maxDepth);
        foreach ($parentCategoryIds as $parentCategoryId) {
            $categoryLanguage = new cApiCategoryLanguage();
            $categoryLanguage->loadByCategoryIdAndLanguageId($parentCategoryId, $languageId);

            if ($this->hasCategoryAccess($categoryLanguage) === true) {
                $categories[] = $categoryLanguage;
            }
        }

        for ($removeCount = 2; $removeCount <= $startingLevel; $removeCount++) {
            array_pop($categories);
        }

        return array_reverse($categories);
    }

    /**
     * Fetch all parent category IDs of a given category.
     *
     * @param int $categoryId Base category to search on.
     * @param int $maxDepth Amount of the max depth of categories.
     * @return array
     * @throws cDbException|cException
     */
    public function getParentCategoryIds($categoryId, $maxDepth = 20): array
    {
        $nextCategoryId = $categoryId;
        $categoryCount = 1;

        $categoryIds = [];
        while ($nextCategoryId != 0 && $categoryCount < $maxDepth) {
            $category = new cApiCategory($nextCategoryId);
            $nextCategoryId = $category->get('parentid');
            if ($nextCategoryId != 0) {
                $categoryIds[] = $nextCategoryId;
            }

            $categoryCount++;
        }

        return $categoryIds;
    }

    /**
     * Fetches the level of a category by a given category ID.
     *
     * @param int $categoryId Category ID to fetch the level of.
     * @throws cDbException|cException
     */
    public function getCategoryLevel($categoryId): int
    {
        if (!isset($this->_levelCache[$categoryId])) {
            $categoryTree = new cApiCategoryTree();
            $categoryTree->loadBy('idcat', $categoryId);

            if (!$categoryTree->isLoaded()) {
                return -1;
            }

            $this->_levelCache[$categoryId] = (int) $categoryTree->get('level');
        }

        return $this->_levelCache[$categoryId];
    }

    /**
     * Return the subcategories of the given category ID.
     * TODO: Use Generic DB instead of SQL queries
     *
     * @param int $categoryId ID of the category to load
     * @param int $depth The maximum depth
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function getSubCategories($categoryId, $depth): array
    {
        $categoryId = cSecurity::toInteger($categoryId);
        $depth = cSecurity::toInteger($depth);

        if ($categoryId <= 0 || $depth < 0) {
            return [];
        }

        $categories = [];

        $clientId = $this->getClientId();
        $languageId = $this->getLanguageId();

        $selectFields = 'cat_tree.idcat, cat_tree.level';

        $useAuthorization = $this->_auth !== null;

        if ($useAuthorization) {
            $selectFields .= ', cat_lang.public, cat_lang.idcatlang';
        }

        $sqlSnippetPublic = 'cat_lang.public = 1 AND';
        if ($useAuthorization) {
            $sqlSnippetPublic = '';
        }

        $sql = 'SELECT
                    ' . $selectFields . '
                FROM
                    ' . cDb::getTableName('cat_tree') . ' AS cat_tree,
                    ' . cDb::getTableName('cat') . ' AS cat,
                    ' . cDb::getTableName('cat_lang') . ' AS cat_lang
                WHERE
                    cat_tree.idcat    = cat.idcat AND
                    cat.idcat    = cat_lang.idcat AND
                    cat.idclient = ' . $clientId . ' AND
                    cat_lang.idlang   = ' . $languageId . ' AND
                    cat_lang.visible  = 1 AND ' . $sqlSnippetPublic . '
                    cat.parentid = ' . $categoryId . '
                ORDER BY
                    cat_tree.idtree';

        $db = cRegistry::getDb();
        $db->query($sql);

        while ($db->nextRecord()) {
            $catId = cSecurity::toInteger($db->f('idcat'));
            $catLevel = cSecurity::toInteger($db->f('level'));

            if ($depth > 0 && $depth > $catLevel) {
                $subCategories = $this->getSubCategories($catId, $depth);
            } else {
                $subCategories = [];
            }
            $categoryLanguage = new cApiCategoryLanguage();
            $categoryLanguage->loadByCategoryIdAndLanguageId($catId, $languageId);

            $category = [];
            $category['item'] = $categoryLanguage;
            $category['idcat'] = $catId;
            $category['level'] = $catLevel;
            $category['subcats'] = $subCategories;

            $this->_levelCache[$catId] = $catLevel;

            if ($this->hasCategoryAccess($categoryLanguage) === true) {
                $categories[] = $category;
            }
        }

        return $categories;
    }

    /**
     * Checks if set auth object has access to the specific category.
     *
     * @param cApiCategoryLanguage $categoryLanguage Category language object
     * @return bool Result of access check
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function hasCategoryAccess(cApiCategoryLanguage $categoryLanguage): bool
    {
        $useAuthorization = ($this->_auth !== NULL && $this->_fePermColl !== NULL);

        if ($useAuthorization === false) {
            return true;
        }

        $perm = cRegistry::getPerm();

        if (intval($categoryLanguage->getField('public')) == 1) {
            return true;
        }

        $clientId = $this->getClientId();
        $languageId = $this->getLanguageId();

        if ($perm->have_perm_client_lang($clientId, $languageId)) {
            return true;
        }

        foreach ($this->_feGroups as $feGroup) {
            if ($this->_fePermColl->checkPerm($feGroup, 'category', 'access', $categoryLanguage->getField('idcatlang'), true)) {
                return true;
            }
        }

        return false;
    }
}
