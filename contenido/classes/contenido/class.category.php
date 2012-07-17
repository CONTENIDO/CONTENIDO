<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Category management class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    1.7
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2005-08-30
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Category collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiCategoryCollection extends ItemCollection
{
    /**
     * Constructor function.
     *
     * @param  string  $select  Select statement (see ItemCollection::select())
     */
    public function __construct($select = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['cat'], 'idcat');
        $this->_setItemClass('cApiCategory');
        if ($select !== false) {
            $this->select($select);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiCategoryCollection($select = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($select);
    }

    /**
     * Creates a category entry.
     *
     * @param  int  $idclient
     * @param  int  $parentid
     * @param  int  $preid
     * @param  int  $postid
     * @param  int  $status
     * @param  string  $author
     * @param  string  $created
     * @param  string  $lastmodified
     * @return cApiCategory
     */
    public function create($idclient, $parentid = 0, $preid = 0, $postid = 0, $status = 0, $author = '', $created = '', $lastmodified = '')
    {
        global $auth;

        if (empty($author)) {
            $author = $auth->auth['uname'];
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $oItem = parent::createNewItem();

        $oItem->set('idclient', (int) $idclient);
        $oItem->set('parentid', (int) $parentid);
        $oItem->set('preid', (int) $preid);
        $oItem->set('postid', (int) $postid);
        $oItem->set('status', (int) $status);
        $oItem->set('author', $this->escape($author));
        $oItem->set('created', $this->escape($created));
        $oItem->set('lastmodified', $this->escape($lastmodified));
        $oItem->store();

        return $oItem;
    }

    /**
     * Returns the last category tree entry from the category table for a specific client.
     * Last entry has no parentid and no postid.
     *
     * @param  int  $idclient
     * @return  cApiCategory|null
     */
    public function fetchLastCategoryTree($idclient)
    {
        $where = 'parentid=0 AND postid=0 AND idclient=' . (int) $idclient;
        $this->select($where);
        return $this->next();
    }

    /**
     * Returns list of categories (category ids) by passed client.
     * @param  int  $idclient
     * @return  array
     */
    public function getCategoryIdsByClient($idclient)
    {
        $list = array();
        $sql = 'SELECT idcat FROM `%s` WHERE idclient=%d';
        $this->db->query($sql, $this->table, $idclient);
        while ($this->db->next_record()) {
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
     *     this_category
     *     post_category (*)
     * ...
     * (*) Returned category id
     * </pre>
     *
     * @param  int  $idcat
     * @return int
     */
    public function getNextPostCategoryId($idcat)
    {
        $sql = "SELECT idcat FROM `%s` WHERE preid = %d";
        $this->db->query($sql, $this->table, $idcat);
        if ($this->db->next_record()) {
            // Post element exists
            $idcat = $this->db->f('idcat');
            $sql = "SELECT parentid FROM `%s` WHERE idcat = %d";
            $this->db->query($sql, $this->table, $idcat);
            if ($this->db->next_record()) {
                // Parent from post can't be 0
                $parentid = (int) $this->db->f('parentid');
                return ($parentid != 0) ? $idcat : 0;
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
     *     parent_category
     *         previous_cateory
     *         this_category
     *         post_category
     *     parents_post_category (*)
     * ...
     * (*) Returned category id
     * </pre>
     *
     * @param   int  $idcat  Category id
     * @return  int
     */
    public function getParentsNextPostCategoryId($idcat)
    {
        $sql = "SELECT parentid FROM `%s` WHERE idcat = %d";
        $this->db->query($sql, $this->table, $idcat);
        if ($this->db->next_record()) {
            // Parent exists
            $idcat = $this->db->f('parentid');
            if ($idcat != 0) {
                $sql = "SELECT idcat FROM `%s` WHERE preid = %d";
                $this->db->query($sql, $this->table, $idcat);
                if ($this->db->next_record()) {
                    // Parent has post
                    $idcat = (int) $this->db->f('idcat');
                    $sql = "SELECT parentid FROM `%s` WHERE idcat = %d";
                    $this->db->query($sql, $this->table, $idcat);
                    if ($this->db->next_record()) {
                        // Parent from post must not be 0
                        $parentid = (int) $this->db->f('parentid');
                        return ($parentid != 0) ? $idcat : 0;
                    } else {
                        return 99;
                    }
                } else {
                    // Parent has no post
                    return $this->getNextBackwardsCategoryId($idcat);
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
     * Returns id of first child category, where parent id is the same as passed
     * id and the previous id is 0.
     *
     * Example:
     * <pre>
     * ...
     * this_category
     *     child_category (*)
     *     child_category2
     *     child_category3
     * ...
     * (*) Returned category id
     * </pre>
     *
     * @global  array  $cfg
     * @param  int  $idcat
     * @param  int|null  $idlang  If defined, it checks also if there is a next deeper category in this language.
     * @return int
     */
    public function getFirstChildCategoryId($idcat, $idlang = null) {
        global $cfg;

        $sql = "SELECT idcat FROM `%s` WHERE parentid = %d AND preid = 0";
        $sql = $this->db->prepare($sql, $this->table, $idcat);
        $this->db->query($sql);
        if ($this->db->next_record()) {
            $midcat = (int) $this->db->f('idcat');
            if (null == $idlang) {
                return $midcat;
            }

            // Deeper element exists, check for language dependent part
            $sql = "SELECT idcatlang FROM `%s` WHERE idcat = %d AND idlang = %d";
            $sql = $this->db->prepare($sql, $cfg['tab']['cat_lang'], $idcat, $idlang);
            $this->db->query($sql);
            return ($this->db->next_record()) ? $midcat : 0;
        } else {
            // Deeper element does not exist
            return 0;
        }
    }

    /**
     * Returns list of all child category ids, only them on next deeper level (not recursive!)
     * The returned array contains already the order of the categories.
     * Example:
     * <pre>
     * ...
     * this_category
     *     child_category (*)
     *     child_category2 (*)
     *         child_of_child_category2
     *     child_category3 (*)
     * ...
     * (*) Returned category ids
     * </pre>
     *
     * @global  array  $cfg
     * @param  int  $idcat
     * @param  int|null  $idlang
     * @return array
     */
    public function getAllChildCategoryIds($idcat, $idlang = null) {
        global $cfg;

        $aCats = array();
        $bLoop = true;
        $db2 = $this->_getSecondDBInstance();

        $sql = "SELECT idcat FROM `%s` WHERE parentid = %d AND preid = 0";
        $this->db->query($sql, $this->table, $idcat);
        if ($this->db->next_record()) {
            while ($bLoop) {
                $midcat = $this->db->f('idcat');
                if (null == $idlang) {
                    $aCats[] = $midcat;
                } else {
                    // Deeper element exists, check for language dependent part
                    $sql = "SELECT idcatlang FROM `%s` WHERE idcat = %d AND idlang = %d";
                    $db2->query($sql, $cfg['tab']['cat_lang'], $midcat, $idlang);
                    $db2->query($sql);
                    if ($db2->next_record()) {
                        $aCats[] = $midcat;
                    }
                }

                $sql = "SELECT idcat FROM `%s` WHERE parentid = %d AND preid = %d";
                $this->db->query($sql, $this->table, $idcat, $midcat);
                if (!$this->db->next_record()) {
                    $bLoop = false;
                }
            }
        }
        return $aCats;
    }

    /**
     * Returns list of all child category ids and their child category ids of
     * passed category id. The list also contains the id of passed category.
     *
     * The return value of this function could be used to perform bulk actions
     * on a specific category an all of its childcategories.
     *
     * NOTE: The returned array is not sorted!
     *
     * Example:
     * <pre>
     * ...
     * this_category (*)
     *     child_category (*)
     *     child_category2 (*)
     *         child_of_child_category2 (*)
     *     child_category3 (*)
     *         child_of_child_category3 (*)
     * ...
     * (*) Returned category ids
     * </pre>
     *
     * @global  array  $cfg
     * @param  int  $idcat
     * @param  int|null  $idlang
     * @return array
     */
    public function getAllCategoryIdsRecursive($idcat, $client) {
        global $cfg;

        $catList = array();
        $openList = array();

        $openList[] = $idcat;

        while (($actId = array_pop($openList)) != null) {
            if (in_array($actId, $catList)) {
                continue;
            }

            $catList[] = $actId;

            $sql = "SELECT * FROM `:cat_tree` AS A, `:cat` AS B WHERE A.idcat=B.idcat AND B.parentid=:parentid AND idclient=:idclient ORDER BY idtree";
            $sql = $this->db->prepare($sql, array(
                'cat_tree' => $cfg['tab']['cat_tree'],
                'cat' => $this->table,
                'parentid' => (int) $actId,
                'idclient' => (int) $client,
            ));
            $this->db->query($sql);

            while ($this->db->next_record()) {
                $openList[] = $this->db->f('idcat');
            }
        }

        return $catList;
    }
}


/**
 * Category item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiCategory extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['cat'], 'idcat');
        $this->setFilters(array(), array());

        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiCategory($mId = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }

    /**
     * Updates lastmodified field and calls parents store method
     *
     * @return  bool
     */
    public function store()
    {
        $this->set('lastmodified', date('Y-m-d H:i:s'));
        return parent::store();
    }
}


################################################################################
# Old versions of category item collection and category item classes
#
# NOTE: Class implemetations below are deprecated and the will be removed in
#       future versions of contenido.
#       Don't use them, they are still available due to downwards compatibility.

/**
 * Category collection
 * @deprecated  [2011-11-15] Use cApiCategoryCollection instead of this class.
 */
class CategoryCollection extends cApiCategoryCollection
{
    public function __construct()
    {
        cDeprecated("Use class cApiCategoryCollection instead");
        parent::__construct();
    }
    public function CategoryCollection()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }
}


/**
 * Single category item
 * @deprecated  [2011-11-15] Use cApiCategory instead of this class.
 */
class CategoryItem extends cApiCategory
{
    public function __construct($mId = false)
    {
        cDeprecated("Use class cApiCategory instead");
        parent::__construct($mId);
    }
    public function CategoryItem($mId = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }
    public function loadByPrimaryKey($key)
    {
        if (parent::loadByPrimaryKey($key)) {
            // Load all child language items
            $catlangs = new cApiCategoryLanguageCollection();
            $catlangs->select("idcat = " . (int) $key);
            while ($item = $catlangs->next()) {
                $this->lang[$item->get("idlang")] = $item;
            }
            return true;
        }
        return false;
    }
}

?>