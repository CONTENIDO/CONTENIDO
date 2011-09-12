<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Layout class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend classes
 * @version    1.1
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2004-08-07
 *   modified 2011-03-15, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cApiLayoutCollection extends ItemCollection
{
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg["tab"]["lay"], "idlay");
        $this->_setItemClass("cApiLayout");
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiLayoutCollection()
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }

    public function create($title)
    {
        global $client;
        $item = parent::create();
        $item->set("name", $title);
        $item->set("idclient", $client);
        $item->store();
        return ($item);
    }
}


class cApiLayout extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["lay"], "idlay");
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiLayout($mId = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct($mId);
    }
}

?>