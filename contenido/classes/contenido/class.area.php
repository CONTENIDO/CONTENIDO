<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Area management class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend classes
 * @version    1.3
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2004-08-04
 *   modified 2011-03-14, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cApiAreaCollection extends ItemCollection
{
    /**
     * Constructor
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['area'], 'idarea');
        $this->_setItemClass("cApiArea");
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiAreaCollection()
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }
}


class cApiArea extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['area'], 'idarea');
        $this->setFilters(array("addslashes"), array("stripslashes"));
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiArea($mId = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct($mId);
    }

    public function create($name, $parentid = 0, $relevant = 1, $online = 1)
    {
        $item = parent::create();

        $item->set("name", $name);
        $item->set("relevant", $relevant);
        $item->set("online", $online);
        $item->set("parent_id", $parentid);

        $item->store();

        return ($item);
    }

    public function createAction($area, $name, $code, $location, $relevant)
    {
        $ac = new cApiActionCollection();
        $a = $ac->create($area, $name, $code, $location, $relevant);
    }
}

?>