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
 *   modified 2011-03-15, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *   modified 2011-10-26, Murat Purc, added functions cApiCategoryCollection->create, cApiCategoryCollection->fetchLastCategoryTree
 *                        and cApiCategory->store
 *
 *   $Id$:
 * }}
 *
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

        $oItem = parent::create();

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