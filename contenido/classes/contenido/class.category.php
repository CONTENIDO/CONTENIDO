<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Area management class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.6
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2005-08-30
 *   modified 2011-03-15, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *   modified 2011-10-26, Murat Purc, added functions cApiCategoryCollection->create, cApiCategoryCollection->selectLastCategoryTree
 *                        and cApiCategory->store
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cApiCategoryCollection extends ItemCollection
{
    public function __construct($select = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['cat'], 'idcat');
        $this->_setItemClass('cApiCategory');
        if ($select !== false) {
            $this->select($select);
        }
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
     * @return cApiCategory
     */
    public function create($idclient, $parentid = 0, $preid = 0, $postid = 0, $status = 0, $author = '')
    {
        global $auth;

        if (empty($author)) {
            $author = $auth->auth['uname'];
        }
        $created = date('Y-m-d H:i:s');
        
        $oItem = parent::create();

        $oItem->set('idclient', (int) $idclient);
        $oItem->set('parentid', (int) $parentid);
        $oItem->set('preid', (int) $preid);
        $oItem->set('postid', (int) $postid);
        $oItem->set('status', (int) $status);
        $oItem->set('author', $this->escape($author));
        $oItem->set('created', $created);
        $oItem->set('lastmodified', $created);
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
    public function selectLastCategoryTree($idclient)
    {
        $where = 'parentid=0 AND postid=0 AND idclient=' . (int) $idclient;
        $this->select($where);
        return $this->next();
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiCategoryCollection($select = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct($select);
    }
}


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
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
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

?>