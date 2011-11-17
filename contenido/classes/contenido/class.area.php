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
 * @package    CONTENIDO API
 * @version    1.3.1
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2004-08-04
 *   modified 2011-03-14, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *   modified 2011-10-25, Murat Purc, Fixed creation of a cApiItem entry
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Area collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiAreaCollection extends ItemCollection
{
    /**
     * Constructor
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['area'], 'idarea');
        $this->_setItemClass('cApiArea');
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiAreaCollection()
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct();
    }

    /**
     * Creates a area item entry
     *
     * @param  string  $name Name
     * @param  string|int  $parentid  Parent id as astring or number
     * @param  int  $relevant  0 or 1
     * @param  int  $online  0 or 1
     * @param  int  $menuless  0 or 1
     * @return cApiArea
     */
    public function create($name, $parentid = 0, $relevant = 1, $online = 1, $menuless = 0)
    {
        $parentid = (is_string($parentid)) ? $this->escape($parentid) : (int) $parentid;

        $item = parent::create();

        $item->set('parent_id', $parentid);
        $item->set('name', $this->escape($name));
        $item->set('relevant', (1== $relevant) ? 1 : 0);
        $item->set('online', (1== $online) ? 1 : 0);
        $item->set('menuless', (1== $menuless) ? 1 : 0);

        $item->store();

        return $item;
    }

}


/**
 * Area item
 * @package    CONTENIDO API
 * @subpackage Model
 */
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
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiArea($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct($mId);
    }

    /** @deprecated  [2011-10-25] Use cApiAreaCollection->create() */
    public function create($name, $parentid = 0, $relevant = 1, $online = 1, $menuless = 0)
    {
        $oAreaColl = new cApiAreaCollection();
        return $oAreaColl->create($name, $parentid, $relevant, $online, $menuless);
    }

    /** @todo  Why is area item responsible to create a action item ? */
    public function createAction($area, $name, $code, $location, $relevant)
    {
        $ac = new cApiActionCollection();
        $a = $ac->create($area, $name, $code, $location, $relevant);
    }
}

?>