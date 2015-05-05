<?php
/**
 * This file contains the category helper class.
 *
 * @package Core
 * @subpackage Frontend_Util
 * @version SVN Revision $Rev:$
 *
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions for the category helper class in CONTENIDO.
 *
 * @package Core
 * @subpackage Frontend_Util
 */
class cCategoryHelper {

    /**
     * Instance of the helper class.
     *
     * @var cCategoryHelper
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
    protected $_levelCache = array();

    /**
     * Auth object to use.
     *
     * @var cAuth
     */
    protected $_auth = NULL;

    /**
     * Array with current frontend user groups.
     *
     * @var array
     */
    protected $_feGroups = array();

    /**
     * Object for frontend permission collection.
     *
     * @var cApiFrontendPermissionCollection
     */
    protected $_fePermColl = NULL;

    /**
     * Returns the instance of this class.
     *
     * @return cCategoryHelper
     */
    public static function getInstance() {
        if (self::$_instance === NULL) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Constructor of the class.
     */
    protected function __construct() {
    }

    /**
     * Sets an auth object to use on category access check.
     *
     * @param cAuth $auth auth object
     */
    public function setAuth($auth) {
        $this->_auth = $auth;

        $feUser = new cApiFrontendUser($auth->auth['uid']);
        if ($feUser->isLoaded() === true) {
            $this->_feGroups = $feUser->getGroupsForUser();
        }

        $this->_fePermColl = new cApiFrontendPermissionCollection();
    }

    /**
     * Returns the local stored client ID
     *
     * @throws cInvalidArgumentException if no active client ID specified or
     *         found
     * @return int
     *         client ID
     */
    public function getClientId() {
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
     *
     * @param int $clientId client ID
     */
    public function setClientId($clientId = 0) {
        $this->_clientId = (int) $clientId;
    }

    /**
     * Returns the local stored language ID
     *
     * @throws cInvalidArgumentException if no active language ID specified or
     *         found
     * @return int
     *         language ID
     */
    public function getLanguageId() {
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
     *
     * @param int $languageId language ID
     */
    public function setLanguageId($languageId = 0) {
        $this->_languageId = (int) $languageId;
    }

    /**
     * Return the ID of the top most category based on a given category ID.
     *
     * @param int $categoryId Base category ID to search on
     * @return int
     *         Top most category ID
     */
    public function getTopMostCategoryId($categoryId) {
        $category = new cApiCategory($categoryId);

        if ($category->get('parentid') == 0) {
            $topMostCategoryId = $categoryId;
        } else {
            $topMostCategoryId = $this->getTopMostCategoryId($category->get('parentid'));
        }

        return $topMostCategoryId;
    }

    /**
     * Returns an array with ordered cApiCategoryLanguage objects e.g.
     * for a breadcrumb.
     *
     * @param int $categoryId Last category ID in list.
     * @param int $startingLevel Define here, at which level the list should
     *        start. (optional, default: 1)
     * @param int $maxDepth Amount of the max depth of categories. (optional,
     *        default: 20)
     * @return array
     *         Array with cApiCategoryLanguage objects
     */
    public function getCategoryPath($categoryId, $startingLevel = 1, $maxDepth = 20) {
        $clientId = $this->getClientId();
        $languageId = $this->getLanguageId();

        $categories = array();

        $categoryLanguage = new cApiCategoryLanguage();
        $categoryLanguage->loadByCategoryIdAndLanguageId($categoryId, $languageId);

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
     * @param int $maxDepth Amount of the max depth of categories. (optional,
     *        default: 20)
     * @return array
     *         Array with parent category IDs.
     */
    public function getParentCategoryIds($categoryId, $maxDepth = 20) {
        $categoryIds = array();

        $nextCategoryId = $categoryId;

        $categoryCount = 1;
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
     * Fetchs the level of a category by a given category ID.
     *
     * @param int $categoryId Category ID to fetch the level of.
     * @return int
     *         category level
     */
    public function getCategoryLevel($categoryId) {
        if (isset($this->_levelCache[$categoryId]) === false) {
            $categoryTree = new cApiCategoryTree();
            $categoryTree->loadBy("idcat", $categoryId);

            if ($categoryTree->isLoaded() === false) {
                return -1;
            }

            $level = $categoryTree->get('level');

            $this->_levelCache[$categoryId] = $level;
        }

        return $this->_levelCache[$categoryId];
    }

    /**
     * Return the subcategories of the given category ID.
     * TODO: Use Generic DB instead of SQL queries
     *
     * @param int $categoryId ID of the category to load
     * @param int $depth the maximum depth
     * @return array
     *         array with subcategories
     */
    public function getSubCategories($categoryId, $depth) {
        if ((int) $categoryId <= 0 || (int) $depth < 0) {
            return array();
        }
        $depth = (int) $depth;

        $cfg = cRegistry::getConfig();

        $categories = array();

        $clientId = $this->getClientId();
        $languageId = $this->getLanguageId();

        $selectFields = "cat_tree.idcat, cat_tree.level";

        $useAuthorization = ($this->_auth !== NULL);

        if ($useAuthorization == true) {
            $selectFields .= ", cat_lang.public, cat_lang.idcatlang";
        }

        $sqlSnippetPublic = "cat_lang.public = 1 AND";
        if ($useAuthorization == true) {
            $sqlSnippetPublic = "";
        }

        $sql = 'SELECT
                    ' . $selectFields . '
                FROM
                    ' . $cfg['tab']['cat_tree'] . ' AS cat_tree,
                    ' . $cfg['tab']['cat'] . ' AS cat,
                    ' . $cfg['tab']['cat_lang'] . ' AS cat_lang
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
            $catId = (int) $db->f('idcat');
            $catLevel = (int) $db->f('level');

            if ($depth > 0 && ($depth > ($catLevel))) {
                $subCategories = $this->getSubCategories($catId, $depth);
            } else {
                $subCategories = array();
            }
            $categoryLanguage = new cApiCategoryLanguage();
            $categoryLanguage->loadByCategoryIdAndLanguageId($catId, $languageId);

            $category = array();
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
     * @param cApiCategoryLanguage $categoryLanguage category language object
     * @return bool
     *         result of access check
     */
    public function hasCategoryAccess(cApiCategoryLanguage $categoryLanguage) {
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

        if ($perm->have_perm_client_lang($clientId, $languageId) == true) {
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