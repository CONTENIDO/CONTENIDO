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
     * @param int $idclient
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
    public function create($idclient, $parentid = 0, $preid = 0, $postid = 0, $status = 0, $author = '', $created = '', $lastmodified = '')
    {
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

        $oItem->set('idclient', $idclient);
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
     * @param int $idclient
     * @return ?cApiCategory
     * @throws cDbException|cException
     */
    public function fetchLastCategoryTree($idclient)
    {
        $where = 'parentid=0 AND postid=0 AND idclient=' . (int)$idclient;
        $this->select($where);
        return $this->next();
    }

    /**
     * Returns list of categories (category ids) by passed client.
     *
     * @throws cDbException
     */
    public function getCategoryIdsByClient($idclient): array
    {
        $list = [];
        $sql = 'SELECT idcat FROM `%s` WHERE idclient=%d';
        $this->db->query($sql, $this->table, $idclient);
        while ($this->db->nextRecord()) {
            $list[] = $this->db->f('idcat');
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
     * @param int $idcat
     * @return int
     * @throws cDbException
     */
    public function getNextPostCategoryId($idcat)
    {
        $idcat = cSecurity::toInteger($idcat);

        $sql = "SELECT idcat FROM `%s` WHERE preid = %d";
        $this->db->query($sql, $this->table, $idcat);
        if ($this->db->nextRecord()) {
            // Post element exists
            $idcat = $this->db->f('idcat');
            $sql = "SELECT parentid FROM `%s` WHERE idcat = %d";
            $this->db->query($sql, $this->table, $idcat);
            if ($this->db->nextRecord()) {
                // Parent from post can't be 0
                $parentId = cSecurity::toInteger($this->db->f('parentid'));
                return $parentId != 0 ? $idcat : 0;
            } else {
                return 99;
            }
        } else {
            // Post element does not exist
            return 0;
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
     * @param int $idcat Category id
     * @throws cDbException
     */
    public function getParentsNextPostCategoryId($idcat): int
    {
        $idcat = cSecurity::toInteger($idcat);

        $sql = "SELECT parentid FROM `%s` WHERE idcat = %d";
        $this->db->query($sql, $this->table, $idcat);
        if ($this->db->nextRecord()) {
            // Parent exists
            $idcat = cSecurity::toInteger($this->db->f('parentid'));
            if ($idcat != 0) {
                $sql = "SELECT idcat FROM `%s` WHERE preid = %d";
                $this->db->query($sql, $this->table, $idcat);
                if ($this->db->nextRecord()) {
                    // Parent has post
                    $idcat = cSecurity::toInteger($this->db->f('idcat'));
                    $sql = "SELECT parentid FROM `%s` WHERE idcat = %d";
                    $this->db->query($sql, $this->table, $idcat);
                    if ($this->db->nextRecord()) {
                        // Parent from post must not be 0
                        $parentid = cSecurity::toInteger($this->db->f('parentid'));
                        return $parentid != 0 ? $idcat : 0;
                    } else {
                        return 99;
                    }
                } else {
                    // Parent has no post
                    // TODO Function `getNextBackwardsCategoryId` doesn't exist!
                    //return $this->getNextBackwardsCategoryId($idcat);
                    return 0;
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
     * @param int $idcat
     * @param ?int $idlang If defined, it checks also if there is a next deeper category in this language.
     * @throws cDbException
     */
    public function getFirstChildCategoryId($idcat, $idlang = NULL): int
    {
        $sql = "SELECT c.idcat
                FROM `%s` AS c
                LEFT JOIN `%s` AS l ON (l.idcat = c.idcat)
                WHERE c.parentid = %d AND l.idlang = %d";
        $sql = $this->db->prepare($sql, $this->table, cDb::getTableName('cat_lang'), $idcat, $idlang);
        $this->db->query($sql);

        if ($this->db->nextRecord()) {
            return (int) $this->db->f('idcat');
        }

        return 0;
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
     * @param int $idcat
     * @param ?int $idlang
     * @return int[]
     * @throws cDbException
     */
    public function getAllChildCategoryIds($idcat, $idlang = NULL): array
    {
        $aCats = [];
        $bLoop = true;
        $db2 = $this->_getSecondDBInstance();

        $sql = "SELECT idcat FROM `%s` WHERE parentid = %d AND preid = 0";
        $this->db->query($sql, $this->table, $idcat);
        if ($this->db->nextRecord()) {
            while ($bLoop) {
                $midcat = $this->db->f('idcat');
                if (NULL == $idlang) {
                    $aCats[] = (int) $midcat;
                } else {
                    // Deeper element exists, check for language dependent part
                    $sql = "SELECT idcatlang FROM `%s` WHERE idcat = %d AND idlang = %d";
                    $db2->query($sql, cDb::getTableName('cat_lang'), $midcat, $idlang);
                    if ($db2->nextRecord()) {
                        $aCats[] = (int) $midcat;
                    }
                }

                $sql = "SELECT idcat FROM `%s` WHERE parentid = %d AND preid = %d";
                $this->db->query($sql, $this->table, $idcat, $midcat);
                if (!$this->db->nextRecord()) {
                    $bLoop = false;
                }
            }
        }
        return $aCats;
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
     * @param int $idcat
     * @param int $idclient
     * @return int[]
     * @throws cDbException
     */
    public function getAllCategoryIdsRecursive($idcat, $idclient): array
    {
        $catList = [];
        $openList = [];

        $openList[] = $idcat;

        while (($actId = array_pop($openList)) != NULL) {
            if (in_array($actId, $catList)) {
                continue;
            }

            $catList[] = $actId;

            $sql = "SELECT * FROM `:cat_tree` AS A, `:cat` AS B WHERE A.idcat=B.idcat AND B.parentid=:parentid AND idclient=:idclient ORDER BY idtree";
            $sql = $this->db->prepare(
                $sql,
                [
                    'cat_tree' => cDb::getTableName('cat_tree'),
                    'cat' => $this->table,
                    'parentid' => (int)$actId,
                    'idclient' => (int)$idclient,
                ]
            );
            $this->db->query($sql);

            while ($this->db->nextRecord()) {
                $openList[] = (int) $this->db->f('idcat');
            }
        }

        return $catList;
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
     * @param int $idcat
     * @param int $idclient
     * @return int[] Sorted by category id
     * @throws cDbException
     */
    public function getAllCategoryIdsRecursive2($idcat, $idclient): array
    {
        $aCats = [];
        $found = false;
        $curLevel = 0;

        $sql = "SELECT * FROM `%s` AS a, `%s` AS b WHERE a.idcat = b.idcat AND idclient = %d ORDER BY idtree";
        $sql = $this->db->prepare($sql, cDb::getTableName('cat_tree'), cDb::getTableName('cat'), $idclient);
        $this->db->query($sql);

        while ($this->db->nextRecord()) {
            // ending part of tree
            if ($found && $this->db->f('level') <= $curLevel) {
                $found = false;
            }

            // starting part of tree
            if ($this->db->f('idcat') == $idcat) {
                $found = true;
                $curLevel = (int) $this->db->f('level');
            }

            if ($found) {
                $aCats[] = (int) $this->db->f('idcat');
            }
        }

        return $aCats;
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
     * @param mixed $id Specifies the ID of item to load
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
     * @param int $changeLangId Change language id for URL (optional)
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function getLink($changeLangId = 0): string
    {
        if ($this->isLoaded() === false) {
            return '';
        }

        $options = [];
        $options['idcat'] = $this->get('idcat');
        $options['lang'] = ($changeLangId == 0) ? cRegistry::getLanguageId() : $changeLangId;
        if ($changeLangId > 0) {
            $options['changelang'] = $changeLangId;
        }

        return cUri::getInstance()->build($options);
    }
}
