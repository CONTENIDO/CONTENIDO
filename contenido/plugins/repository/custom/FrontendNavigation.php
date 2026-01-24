<?php

/**
 * This file includes the "frontend navigation" sub plugin from the old plugin repository.
 *
 * @package    Plugin
 * @subpackage Repository_FrontendNavigation
 * @author     Willi Man
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * file FrontendNavigation.php
 *
 * @package    Plugin
 * @subpackage Repository_FrontendNavigation
 */
class FrontendNavigation
{

    /**
     * @var cApiCategoryLanguage[] Used to cache loaded category language objects.
     */
    private static $categoryLanguageCache = [];

    /**
     * @var cDb
     */
    protected $_db = null;

    /**
     * @var bool
     */
    protected $_debug = false;

    /**
     * @var int
     */
    protected $_client = 0;

    /**
     * @var array
     */
    protected $_cfgClient = [];

    /**
     * @var array
     */
    protected $_cfg = [];

    /**
     * @var int
     */
    protected $_lang = 0;

    /**
     * FrontendNavigation constructor
     */
    public function __construct()
    {
        $this->_db = cRegistry::getDb();
        $this->_cfgClient = cRegistry::getClientConfig();
        $this->_cfg = cRegistry::getConfig();
        $this->_client = cRegistry::getClientId();
        $this->_lang = cRegistry::getLanguageId();
    }

    /**
     * Get child categories by given parent category
     *
     * @param int $parentCategory
     * @return int[]
     * @throws cDbException|cInvalidArgumentException
     */
    public function getSubCategories($parentCategory): array
    {
        $parentCategory = cSecurity::toInteger($parentCategory);
        if ($parentCategory <= 0) {
            return [];
        }

        $sql = $this->_db->prepare(
            "SELECT
                    A.idcat
                FROM
                    `%s` AS A,
                    `%s` AS B,
                    `%s` AS C
                WHERE
                    A.idcat    = B.idcat AND
                    B.idcat    = C.idcat AND
                    B.idclient = %d AND
                    C.idlang   = %d AND
                    C.visible  = 1 AND
                    C.public   = 1 AND
                    B.parentid = %d
                ORDER BY
                    A.idtree ",
            cDb::getTableName('cat_tree'),
            cDb::getTableName('cat'),
            cDb::getTableName('cat_lang'),
            $this->_client,
            $this->_lang,
            $parentCategory
        );

        if ($this->_debug) {
            cDebug::getDebugger()->add($sql, __FUNCTION__ . ' $sql');
        }

        $this->_db->query($sql);

        $navigation = [];
        while ($this->_db->nextRecord()) {
            $navigation[] = cSecurity::toInteger($this->_db->f('idcat'));
        }

        return $navigation;
    }

    /**
     * Check if child categories of a given parent category exist
     *
     * @param int $parentCategory
     * @throws cDbException|cInvalidArgumentException
     */
    public function hasChildren($parentCategory): bool
    {
        $parentCategory = cSecurity::toInteger($parentCategory);
        if ($parentCategory <= 0) {
            return false;
        }

        $sql = $this->_db->prepare(
            "SELECT
                    B.idcat
                FROM
                    `%s` AS B,
                    `%s` AS C
                WHERE
                    B.idcat    = C.idcat AND
                    B.idclient = %d AND
                    C.idlang   = %d AND
                    C.visible  = 1 AND
                    C.public   = 1 AND
                    B.parentid = %d",
            cDb::getTableName('cat'),
            cDb::getTableName('cat_lang'),
            $this->_client,
            $this->_lang,
            $parentCategory
        );

        if ($this->_debug) {
            cDebug::getDebugger()->add($sql, __FUNCTION__ . ' $sql');
        }

        $this->_db->query($sql);

        return $this->_db->nextRecord();
    }

    /**
     * Get direct successor of a given category
     * Note: does not work if the direct successor (with preid 0) is not visible or not public
     *
     * @param int $categoryId
     * @return int The successor category id or -1 if no successor exists.
     * @throws cDbException|cInvalidArgumentException
     */
    public function getSuccessor($categoryId): int
    {
        $categoryId = cSecurity::toInteger($categoryId);
        if ($categoryId <= 0) {
            return -1;
        }

        $sql = $this->_db->prepare(
            "SELECT
                    B.idcat
                FROM
                    `%s` AS B,
                    `%s` AS C
                WHERE
                    B.idcat    = C.idcat AND
                    B.idclient = %d AND
                    C.idlang   = %d AND
                    C.visible  = 1 AND
                    C.public   = 1 AND
                    B.preid    = 0 AND
                    B.parentid = %d",
            cDb::getTableName('cat'),
            cDb::getTableName('cat_lang'),
            $this->_client,
            $this->_lang,
            $categoryId
        );


        if ($this->_debug) {
            cDebug::getDebugger()->add($sql, __FUNCTION__ . ' $sql');
        }

        $this->_db->query($sql);

        return $this->_db->nextRecord() ? cSecurity::toInteger($this->_db->f('idcat')) : -1;
    }

    /**
     * Check if a given category has a direct successor.
     *
     * @param int $categoryId
     * @throws cDbException|cInvalidArgumentException
     */
    public function hasSuccessor($categoryId): bool
    {
        $categoryId = cSecurity::toInteger($categoryId);
        if ($categoryId <= 0) {
            return false;
        }

        $sql = $this->_db->prepare(
            "SELECT
                    B.idcat
                FROM
                    `%s` AS B,
                    `%s` AS C
                WHERE
                    B.idcat    = C.idcat AND
                    B.idclient = %d AND
                    C.idlang   = %d AND
                    C.visible  = 1 AND
                    C.public   = 1 AND
                    B.preid    = 0 AND
                    B.parentid = %d",
            cDb::getTableName('cat'),
            cDb::getTableName('cat_lang'),
            $this->_client,
            $this->_lang,
            $categoryId
        );

        if ($this->_debug) {
            cDebug::getDebugger()->add($sql, __FUNCTION__ . ' $sql');
        }

        $this->_db->query($sql);

        return $this->_db->nextRecord();
    }

    /**
     * Get category name
     *
     * @param int $categoryId
     * @throws cException
     */
    public function getCategoryName($categoryId): string
    {
        $categoryLanguage = $this->getCategoryLanguage(cSecurity::toInteger($categoryId));

        return $categoryLanguage ? $categoryLanguage->get('name') : '';
    }

    /**
     * Get category urlname
     *
     * @param int $categoryId
     * @throws cException
     */
    public function getCategoryURLName($categoryId): string
    {
        $categoryLanguage = $this->getCategoryLanguage(cSecurity::toInteger($categoryId));

        return $categoryLanguage ? $categoryLanguage->get('urlname') : '';
    }

    /**
     * Check if the category is visible
     *
     * @param int $categoryId
     * @throws cException
     */
    public function isVisible($categoryId): bool
    {
        $categoryLanguage = $this->getCategoryLanguage(cSecurity::toInteger($categoryId));

        return $categoryLanguage && cSecurity::toBoolean($categoryLanguage->get('visible'));
    }

    /**
     * Check if the category is public
     *
     * @param int $categoryId
     * @throws cException
     */
    public function isPublic($categoryId): bool
    {
        $categoryLanguage = $this->getCategoryLanguage(cSecurity::toInteger($categoryId));

        return $categoryLanguage && cSecurity::toBoolean($categoryLanguage->get('public'));
    }

    /**
     * Return true if $parentCategoryId is the parent of $categoryId
     *
     * @param int $parentCategoryId
     * @param int $categoryId
     * @throws cException
     */
    public function isParent($parentCategoryId, $categoryId): bool
    {
        $categoryLanguage = $this->getCategoryLanguage(cSecurity::toInteger($categoryId));

        if ($categoryLanguage) {
            return $categoryLanguage->get('parentid') == $parentCategoryId;
        } else {
            return false;
        }
    }

    /**
     * Get parent id of a category
     *
     * @param int $categoryId
     * @throws cException
     */
    public function getParent($categoryId): int
    {
        $categoryLanguage = $this->getCategoryLanguage(cSecurity::toInteger($categoryId));

        return $categoryLanguage ? cSecurity::toInteger($categoryLanguage->get('parentid')) : -1;
    }

    /**
     * Check if a category has a parent
     *
     * @param int $categoryId
     * @throws cException
     */
    public function hasParent($categoryId): bool
    {
        return $this->getParent(cSecurity::toInteger($categoryId)) > 0;
    }

    /**
     * Get level of a category
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function getLevel($categoryId): int
    {
        $categoryId = cSecurity::toInteger($categoryId);
        if ($categoryId <= 0) {
            return -1;
        }

        $sql = $this->_db->prepare(
            "SELECT `level` FROM `%s` WHERE `idcat` = %d",
            cDb::getTableName('cat_tree'),
            $categoryId
        );
        $this->_db->query($sql);

        if ($this->_debug) {
            cDebug::getDebugger()->add($sql, __FUNCTION__ . ' $sql');
        }

        return $this->_db->nextRecord() ? cSecurity::toInteger($this->_db->f('level')) : -1;
    }

    /**
     * Get URL by the given category in front_content.php style
     *
     * @param int $categoryId
     * @param int $articleId
     * @param bool $absolute return absolute path or not [optional]
     * @return string $url
     */
    public function getFrontContentUrl($categoryId, $articleId, $absolute = true): string
    {
        $categoryId = cSecurity::toInteger($categoryId);
        if ($categoryId <= 0) {
            return '';
        }

        $articleId = cSecurity::toInteger($articleId);
        if ($articleId > 0) {
            $url = "front_content.php?idcat=$categoryId&idart=$articleId";
        } else {
            $url = "front_content.php?idcat=$categoryId";
        }
        if ($absolute === true) {
            $url = cRegistry::getFrontendUrl() . $url;
        }

        return $url;
    }

    /**
     * Get urlpath by given category and/or idart and level.
     * The urlpath looks like /Home/Product/Support/ where the directory-like string equals a category path.
     *
     * @requires functions.pathresolver.php
     * @param int $categoryId
     * @param int $articleId
     * @param bool $absolute return absolute path or not [optional]
     * @param int $level [optional]
     * @param string $urlSuffix [optional]
     * @return string path information or empty string
     * @throws cDbException|cException
     */
    public function getUrlPath($categoryId, $articleId, $absolute = true, $level = 0, $urlSuffix = 'index.html'): string
    {
        $categoryId = cSecurity::toInteger($categoryId);
        if ($categoryId <= 0) {
            return '';
        }

        $categoryPath = '';
        prCreateURLNameLocationString(
            $categoryId,
            '/',
            $categoryPath,
            false,
            '',
            $level,
            $this->_lang
        );

        if (cString::getStringLength($categoryPath) <= 1) {
            // return an empty string if no url location is available
            return '';
        }

        $articleId = cSecurity::toInteger($articleId);
        if ($articleId > 0) {
            $urlPath = "$categoryPath/index-d-$articleId.html";
        } else {
            $urlPath = "$categoryPath/$urlSuffix";
        }
        if ($absolute === true) {
            $urlPath = cRegistry::getFrontendUrl() . $urlPath;
        }

        return $urlPath;
    }

    /**
     * Get urlpath by given category and/or selected param and level.
     *
     * @requires functions.pathresolver.php
     * @param int $categoryId
     * @param int $selectedNumber
     * @param bool $absolute return absolute path or not [optional]
     * @param int $level [optional]
     * @return string path information or empty string
     * @throws cDbException|cException
     */
    public function getUrlPathGenParam($categoryId, $selectedNumber, $absolute = true, $level = 0): string
    {
        $categoryId = cSecurity::toInteger($categoryId);
        if ($categoryId <= 0) {
            return '';
        }

        $categoryPath = '';
        prCreateURLNameLocationString(
            $categoryId,
            '/',
            $categoryPath,
            false,
            '',
            $level,
            $this->_lang,
        );

        if (cString::getStringLength($categoryPath) <= 1) {
            // return an empty string if no url location is available
            return '';
        }

        $selectedNumber = cSecurity::toInteger($selectedNumber);
        if ($selectedNumber > 0) {
            $urlPath = "$categoryPath/index-g-$selectedNumber.html";
            if ($absolute === true) {
                $urlPath = cRegistry::getFrontendUrl() . $urlPath;
            }

            return $urlPath;
        }

        return '';
    }

    /**
     * Get URL by given category id and/or article id
     *
     * @param int $categoryId url name to create for
     * @param int $articleId
     * @param string $type
     * @param bool $absolute return absolute path or not [optional]
     * @param int $level
     * @return string $url or empty
     * @throws cDbException|cException
     */
    public function getURL($categoryId, $articleId, $type = '', $absolute = true, $level = 0): string
    {
        $categoryId = cSecurity::toInteger($categoryId);
        if ($categoryId <= 0) {
            return '';
        }

        $articleId = cSecurity::toInteger($articleId);
        $absolute = cSecurity::toBoolean($absolute);
        $level = cSecurity::toInteger($level);

        switch ($type) {
            case 'urlpath':
                $url = $this->getUrlPath($categoryId, $articleId, $absolute, $level);
                break;
            case 'frontcontent':
                $url = $this->getFrontContentUrl($categoryId, $articleId, $absolute);
                break;
            case 'index-a':
                // not implemented
                $url = '';
                break;
            default:
                $url = $this->getFrontContentUrl($categoryId, $articleId, $absolute);
        }

        return $url;
    }

    /**
     * Get category of article.
     *
     * If an article is assigned to more than one category, take the first category.
     *
     * @param int $articleId
     * @return int category id or negative integer
     * @throws cDbException|cInvalidArgumentException
     */
    public function getCategoryOfArticle($articleId): int
    {
        $articleId = cSecurity::toInteger($articleId);
        if ($articleId <= 0) {
            return '';
        }

        $sql = $this->_db->prepare(
            "SELECT
                c.idcat
            FROM
                `%s` AS a,
                `%s` AS b,
                `%s` AS c
            WHERE
                a.idart = %d AND
                b.idclient = %d AND
                a.idlang = %d AND
                b.idart = c.idart AND
                a.idart = b.idart",
            cDb::getTableName('art_lang'),
            cDb::getTableName('art'),
            cDb::getTableName('cat_art'),
            $articleId,
            $this->_client,
            $this->_lang
        );

        if ($this->_debug) {
            cDebug::getDebugger()->add($sql, __FUNCTION__ . ' $sql');
        }

        $this->_db->query($sql);

        // $this->db->getErrorNumber() returns 0 (zero) if no error occurred.
        if ($this->_db->getErrorNumber() == 0) {
            return $this->_db->nextRecord() ? cSecurity::toInteger($this->_db->f('idcat')) : -1;
        } elseif ($this->_debug) {
            cDebug::getDebugger()->add(
                "Mysql Error:" . $this->_db->getErrorMessage() . "(" . $this->_db->getErrorNumber() . ")",
                __FUNCTION__
            );
        }

        return -1;
    }

    /**
     * Get the path of a given category up to a certain level
     *
     * @param int $categoryId
     * @param int $level [optional]
     * @param bool $reverse
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function getCategoryPath($categoryId, $level = 0, $reverse = true): array
    {
        $categoryId = cSecurity::toInteger($categoryId);
        if ($categoryId <= 0) {
            return [];
        }

        $level = cSecurity::toInteger($level);
        $rootPath = [$categoryId];
        $parentId = $categoryId;

        while ($this->getLevel($parentId) >= 0 && $this->getLevel($parentId) > $level) {
            $parentId = $this->getParent($parentId);
            if ($parentId >= 0) {
                $rootPath[] = $parentId;
            }
        }

        if ($reverse) {
            $rootPath = array_reverse($rootPath);
        }

        return $rootPath;
    }

    /**
     * Get the root category of a given category
     *
     * @param int $categoryId
     * @return int|false
     * @throws cDbException|cException|cInvalidArgumentException
     */
    function getRoot($categoryId)
    {
        $categoryId = cSecurity::toInteger($categoryId);
        if ($categoryId <= 0) {
            return false;
        }

        $rootCategory = false;
        $parentId = $categoryId;

        while ($this->getLevel($parentId) >= 0) {
            $rootCategory = $parentId;
            $parentId = $this->getParent($parentId);
        }

        return $rootCategory;
    }

    /**
     * Get subtree by a given category id
     *
     * @param int $categoryId Id of category
     * @return int[] Array with subtree
     * @throws cDbException|cInvalidArgumentException
     */
    function getSubTree($categoryId): array
    {
        $categoryId = cSecurity::toInteger($categoryId);
        if ($categoryId <= 0) {
            return [];
        }

        $sql = $this->_db->prepare(
            "SELECT
                    B.idcat,
                    A.level
                FROM
                    `%s` AS A,
                    `%s` AS B
                WHERE
                    A.idcat  = B.idcat AND
                    idclient = %d
                ORDER BY
                    idtree",
            cDb::getTableName('cat_tree'),
            cDb::getTableName('cat'),
            $this->_client
        );

        if ($this->_debug) {
            cDebug::getDebugger()->add($sql, __FUNCTION__ . ' $sql');
        }

        $this->_db->query($sql);

        $isEndNotReached = false;
        $curLevel = 0;
        $deeperCats = [];

        while ($this->_db->nextRecord()) {
            if ($this->_db->f('idcat') == $categoryId) {
                $curLevel = $this->_db->f('level');
                $isEndNotReached = true;
            } else {
                if ($curLevel == $this->_db->f('level')) {
                    // Ending part of the tree
                    $isEndNotReached = false;
                }
            }

            if ($isEndNotReached) {
                $deeperCats[] = cSecurity::toInteger($this->_db->f('idcat'));
            }
        }

        return $deeperCats;
    }

    /**
     * @throws cException
     */
    private function getCategoryLanguage(int $categoryId): ?cApiCategoryLanguage
    {
        if ($categoryId <= 0) {
            return null;
        }

        if (!isset(self::$categoryLanguageCache[$categoryId])) {
            $categoryLanguage = new cApiCategoryLanguage();
            $categoryLanguage->loadByCategoryIdAndLanguageId($categoryId, $this->_lang);
            if ($categoryLanguage->isLoaded()) {
                self::$categoryLanguageCache[$categoryId] = $categoryLanguage;
            } else {
                self::$categoryLanguageCache[$categoryId] = false;
            }
        }

        return self::$categoryLanguageCache[$categoryId] instanceof cApiCategoryLanguage
            ? self::$categoryLanguageCache[$categoryId]
            : null;
    }

}
