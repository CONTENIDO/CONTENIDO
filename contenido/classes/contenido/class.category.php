<?php

/**
 * This file contains the category collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Category collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiCategory>
 */
class cApiCategoryCollection extends ItemCollection
{

    use cItemCollectionIdsByClientIdTrait;

    /**
     * @var string Client id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkClientIdName = 'idclient';

    /**
     * Constructor to create an instance of this class.
     *
     * @param string|false $select [optional] Where clause to use for selection {@see ItemCollection::select()}
     * @throws cDbException|cInvalidArgumentException
     */
    public function __construct($select = false)
    {
        parent::__construct(cDb::getTableName('cat'), 'idcat');
        $this->_setItemClass('cApiCategory');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');

        if ($select !== false) {
            $this->select($select);
        }
    }

    /**
     * Creates a category entry.
     *
     * @param int $clientId
     * @param int $parentid [optional]
     * @param int $preid [optional]
     * @param int $postid [optional]
     * @param int $status [optional]
     * @param string $author [optional]
     * @param string $created [optional]
     * @param string $lastmodified [optional]
     * @return cApiCategory
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create(
        $clientId,
        $parentid = 0,
        $preid = 0,
        $postid = 0,
        $status = 0,
        $author = '',
        $created = '',
        $lastmodified = ''
    ) {
        if (empty($author)) {
            $auth = cRegistry::getAuth();
            $author = $auth->getUsername();
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $oItem = $this->createNewItem();

        $oItem->set('idclient', $clientId);
        $oItem->set('parentid', $parentid);
        $oItem->set('preid', $preid);
        $oItem->set('postid', $postid);
        $oItem->set('status', $status);
        $oItem->set('author', $author);
        $oItem->set('created', $created);
        $oItem->set('lastmodified', $lastmodified);
        $oItem->store();

        return $oItem;
    }

    /**
     * Returns the last category tree entry from the category table for a specific client.
     * Last entry has no parentid and no postid.
     *
     * @param int $clientId
     * @throws cDbException|cException
     */
    public function fetchLastCategoryTree($clientId): ?cApiCategory
    {
        $this->select(sprintf('`parentid` = 0 AND `postid` = 0 AND `idclient` = %d', $clientId));
        return $this->next();
    }

    /**
     * Returns list of categories (category ids) by passed client.
     *
     * @return int[]
     * @throws cDbException
     */
    public function getCategoryIdsByClient($clientId): array
    {
        $list = [];
        $this->db->query('SELECT `idcat` FROM `%s` WHERE `idclient` = %d', $this->table, $clientId);
        while ($this->db->nextRecord()) {
            $list[] = cSecurity::toInteger($this->db->f('idcat'));
        }
        return $list;
    }

    /**
     * Returns the id of category which is located after passed category id.
     *
     * Example:
     * <pre>
     * ...
     * parent_category
     * this_category
     * post_category (*)
     * ...
     * (*) Returned category id
     * </pre>
     *
     * @param int $categoryId
     * @throws cDbException
     */
    public function getNextPostCategoryId($categoryId): int
    {
        $categoryId = cSecurity::toInteger($categoryId);

        $this->db->query("SELECT `idcat` FROM `%s` WHERE `preid` = %d", $this->table, $categoryId);
        if (!$this->db->nextRecord()) {
            // Post element does not exist
            return 0;
        }

        // Post element exists
        $categoryId = cSecurity::toInteger($this->db->f('idcat'));
        $this->db->query("SELECT `parentid` FROM `%s` WHERE `idcat` = %d", $this->table, $categoryId);
        if ($this->db->nextRecord()) {
            // Parent from post can't be 0
            $parentId = cSecurity::toInteger($this->db->f('parentid'));
            return $parentId != 0 ? $categoryId : 0;
        } else {
            return 99;
        }
    }

    /**
     * Returns the id of category which is located after passed category ids parent category.
     *
     * Example:
     * <pre>
     * ...
     * root_category
     * parent_category
     * previous_category
     * this_category
     * post_category
     * parents_post_category (*)
     * ...
     * (*) Returned category id
     * </pre>
     *
     * @param int $categoryId Category id
     * @throws cDbException
     */
    public function getParentsNextPostCategoryId($categoryId): int
    {
        $categoryId = cSecurity::toInteger($categoryId);

        $this->db->query("SELECT `parentid` FROM `%s` WHERE `idcat` = %d", $this->table, $categoryId);
        if (!$this->db->nextRecord()) {
            // No parent
            return 0;
        }

        // Parent exists
        $categoryId = cSecurity::toInteger($this->db->f('parentid'));
        if ($categoryId === 0) {
            return 0;
        }

        $this->db->query("SELECT `idcat` FROM `%s` WHERE `preid` = %d", $this->table, $categoryId);
        if (!$this->db->nextRecord()) {
            // Parent has no post
            // TODO Function `getNextBackwardsCategoryId` doesn't exist!
            //return $this->getNextBackwardsCategoryId($categoryId);
            return 0;
        }

        // Parent has post
        $categoryId = cSecurity::toInteger($this->db->f('idcat'));
        $this->db->query("SELECT `parentid` FROM `%s` WHERE `idcat` = %d", $this->table, $categoryId);
        if ($this->db->nextRecord()) {
            // Parent from post must not be 0
            $parentId = cSecurity::toInteger($this->db->f('parentid'));
            return $parentId != 0 ? $categoryId : 0;
        } else {
            return 99;
        }
    }

    /**
     * Returns id of first child category, where parent id is the same as passed id and the previous id is 0.
     *
     * Example:
     * <pre>
     * ...
     * this_category
     * child_category (*)
     * child_category2
     * child_category3
     * ...
     * (*) Returned category id
     * </pre>
     *
     * @param int $categoryId
     * @param ?int $languageId If defined, it checks also if there is a next deeper category in this language.
     * @throws cDbException
     */
    public function getFirstChildCategoryId($categoryId, $languageId = NULL): int
    {
        $sql = $this->db->prepare(
            "SELECT c.idcat
                FROM `%s` AS c
                LEFT JOIN `%s` AS l ON (l.idcat = c.idcat)
                WHERE c.parentid = %d AND l.idlang = %d",
            $this->table,
            cDb::getTableName('cat_lang'),
            $categoryId,
            $languageId
        );
        $this->db->query($sql);

        return $this->db->nextRecord() ? cSecurity::toInteger($this->db->f('idcat')) : 0;
    }

    /**
     * Returns list of all child category ids, only them on next deeper level (not recursive!)
     * The returned array contains already the order of the categories.
     * Example:
     * <pre>
     * ...
     * this_category
     * child_category (*)
     * child_category2 (*)
     * child_of_child_category2
     * child_category3 (*)
     * ...
     * (*) Returned category ids
     * </pre>
     *
     * @param int $categoryId
     * @param ?int $languageId
     * @return int[]
     * @throws cDbException
     */
    public function getAllChildCategoryIds($categoryId, $languageId = NULL): array
    {
        $categoryIds = [];
        $doLoop = true;
        $db2 = $this->_getSecondDBInstance();

        $sql = "SELECT `idcat` FROM `%s` WHERE `parentid` = %d AND `preid` = 0";
        $this->db->query($sql, $this->table, $categoryId);
        if ($this->db->nextRecord()) {
            while ($doLoop) {
                $tmpCategoryId = cSecurity::toInteger($this->db->f('idcat'));
                if (NULL == $languageId) {
                    $categoryIds[] = $tmpCategoryId;
                } else {
                    // Deeper element exists, check for language dependent part
                    $sql = "SELECT `idcatlang` FROM `%s` WHERE `idcat` = %d AND `idlang` = %d";
                    $db2->query($sql, cDb::getTableName('cat_lang'), $tmpCategoryId, $languageId);
                    if ($db2->nextRecord()) {
                        $categoryIds[] = $tmpCategoryId;
                    }
                }

                $sql = "SELECT `idcat` FROM `%s` WHERE `parentid` = %d AND `preid` = %d";
                $this->db->query($sql, $this->table, $categoryId, $tmpCategoryId);
                if (!$this->db->nextRecord()) {
                    $doLoop = false;
                }
            }
        }
        return $categoryIds;
    }

    /**
     * Returns list of all child category ids and their child category ids of passed category id.
     * The list also contains the id of passed category.
     *
     * The return value of this function could be used to perform bulk actions on a specific category
     * and all of its child categories.
     *
     * NOTE: The returned array is not sorted!
     * Return value is similar to getAllCategoryIdsRecursive2, only the sorting differs.
     *
     * Example:
     * <pre>
     * ...
     * this_category (*)
     * child_category (*)
     * child_category2 (*)
     * child_of_child_category2 (*)
     * child_category3 (*)
     * child_of_child_category3 (*)
     * ...
     * (*) Returned category ids
     * </pre>
     *
     * @param int $categoryId
     * @param int $clientId
     * @return int[]
     * @throws cDbException
     */
    public function getAllCategoryIdsRecursive($categoryId, $clientId): array
    {
        $categoryId = cSecurity::toInteger($categoryId);
        $clientId = cSecurity::toInteger($clientId);

        $categoryIds = [];
        $openList = [$categoryId];

        while (($actId = array_pop($openList)) != NULL) {
            if (in_array($actId, $categoryIds)) {
                continue;
            }

            $categoryIds[] = $actId;

            $sql = $this->db->prepare(
                "SELECT * FROM `:cat_tree` AS A, `:cat` AS B
                WHERE A.idcat = B.idcat AND B.parentid = :parentid AND idclient = :idclient
                ORDER BY idtree",
                [
                    'cat_tree' => cDb::getTableName('cat_tree'),
                    'cat' => $this->table,
                    'parentid' => $actId,
                    'idclient' => cSecurity::toInteger($clientId),
                ]
            );
            $this->db->query($sql);

            while ($this->db->nextRecord()) {
                $openList[] = cSecurity::toInteger($this->db->f('idcat'));
            }
        }

        return $categoryIds;
    }

    /**
     * Returns list of all child category ids and their child category ids of passed category id.
     * The list also contains the id of passed category.
     *
     * The return value of this function could be used to perform bulk actions on a specific category and
     * all of its child categories.
     *
     * NOTE: Return value is similar to getAllCategoryIdsRecursive, only the sorting differs
     *
     * Example:
     * <pre>
     * ...
     * this_category (*)
     * child_category (*)
     * child_category2 (*)
     * child_of_child_category2 (*)
     * child_category3 (*)
     * child_of_child_category3 (*)
     * ...
     * (*) Returned category ids
     * </pre>
     *
     * @param int $categoryId
     * @param int $clientId
     * @return int[] Sorted by category id
     * @throws cDbException
     */
    public function getAllCategoryIdsRecursive2($categoryId, $clientId): array
    {
        $categoryId = cSecurity::toInteger($categoryId);
        $clientId = cSecurity::toInteger($clientId);

        $categoryIds = [];
        $found = false;
        $curLevel = 0;

        $sql = $this->db->prepare(
            "SELECT * FROM `%s` AS a, `%s` AS b WHERE a.idcat = b.idcat AND idclient = %d ORDER BY `idtree`",
            cDb::getTableName('cat_tree'),
            cDb::getTableName('cat'),
            $clientId
        );
        $this->db->query($sql);

        while ($this->db->nextRecord()) {
            // ending part of tree
            if ($found && $this->db->f('level') <= $curLevel) {
                $found = false;
            }

            // starting part of tree
            if ($this->db->f('idcat') == $categoryId) {
                $found = true;
                $curLevel = cSecurity::toInteger($this->db->f('level'));
            }

            if ($found) {
                $categoryIds[] = cSecurity::toInteger($this->db->f('idcat'));
            }
        }

        return $categoryIds;
    }
}

/**
 * Category item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiCategory extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('cat'), 'idcat');
        $this->setFilters();

        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Updates lastmodified field and calls parents store method
     *
     * @inheritDoc
     */
    public function store()
    {
        $this->set('lastmodified', date('Y-m-d H:i:s'));
        return parent::store();
    }

    /**
     * User-defined setter for category fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idcat':
            case 'idclient':
            case 'parentid':
            case 'preid':
            case 'postid':
            case 'status':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

    /**
     * Returns the link to the current object.
     *
     * @param int $changeLanguageId Change language id for URL (optional)
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function getLink($changeLanguageId = 0): string
    {
        if ($this->isLoaded() === false) {
            return '';
        }

        $options = [];
        $options['idcat'] = $this->get('idcat');
        $options['lang'] = $changeLanguageId == 0 ? cRegistry::getLanguageId() : $changeLanguageId;
        if ($changeLanguageId > 0) {
            $options['changelang'] = $changeLanguageId;
        }

        return cUri::getInstance()->build($options);
    }
}
