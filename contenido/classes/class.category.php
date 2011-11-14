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
 * @con_notice Status: Test. Not for production use
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2011-03-14, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class CategoryCollection extends ItemCollection
{
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg["tab"]["cat"], "idcat");
        $this->_setItemClass("CategoryItem");
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function CategoryCollection()
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }
}


class CategoryItem extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["cat"], "idcat");
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function CategoryItem($mId = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }

    public function loadByPrimaryKey($key)
    {
        if (parent::loadByPrimaryKey($key)) {
            // Load all child language items
            $catlangs = new CategoryLanguageCollection();
            $catlangs->select("idcat = '$key'");

            while ($item = $catlangs->next()) {
                $this->lang[$item->get("idlang")] = $item;
            }
            return true;
        }
        return false;
    }
}


class CategoryLanguageCollection extends ItemCollection
{
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg["tab"]["cat_lang"], "idcatlang");
        $this->_setItemClass("CategoryLanguageItem");
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function CategoryLanguageCollection()
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }
}


class CategoryLanguageItem extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["cat_lang"], "idcatlang");
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function CategoryLanguageItem($mId = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct($mId);
    }
}

?>