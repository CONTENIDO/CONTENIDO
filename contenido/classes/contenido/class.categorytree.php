<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend classes
 * @version    1.4
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2005-08-30
 *   modified 2011-03-15, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cApiCategoryTreeCollection extends ItemCollection
{
    public function __construct($select = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["cat_tree"], "idtree");
        $this->_setJoinPartner("cApiCategoryCollection");
        $this->_setItemClass("cApiTree");
        if ($select !== false) {
            $this->select($select);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiCategoryTreeCollection($select = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct($select);
    }
}


class cApiTree extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["cat_tree"], "idtree");
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiTree($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct($mId);
    }
}

?>