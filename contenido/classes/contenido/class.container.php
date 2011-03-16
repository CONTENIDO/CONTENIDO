<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Template access class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend classes
 * @version    1.4
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2004-08-04
 *   modified 2011-03-15, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cApiContainerCollection extends ItemCollection
{
    public function __construct($select = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["container"], "idcontainer");
        $this->_setItemClass("cApiContainer");
        if ($select !== false) {
            $this->select($select);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiContainerCollection($select = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct($select = false);
    }

    public function clearAssignments($idtpl)
    {
        $this->select("idtpl = '$idtpl'");
        while ($item = $this->next()) {
            $this->delete($item->get("idcontainer"));
        }
    }

    public function assignModul($idtpl, $number, $module)
    {
        $this->select("idtpl = '$idtpl' AND number = '$number'");
        if ($item = $this->next()) {
            $item->set("module", $module);
            $item->store();
        } else {
            $this->create($idtpl, $number, $module);
        }
    }

    public function create($idtpl, $number, $module)
    {
        $item = parent::create();
        $item->set("idtpl", $idtpl);
        $item->set("number", $number);
        $item->set("module", $module);
        $item->store();
    }
}


class cApiContainer extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["container"], "idcontainer");
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiContainer($mId = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct($mId);
    }
}

?>