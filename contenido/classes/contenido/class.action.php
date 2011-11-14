<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Action management class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.5
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2006-06-09
 *   modified 2011-03-14, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cApiActionCollection extends ItemCollection
{
    /**
     * Constructor
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['actions'], 'idaction');
        $this->_setItemClass("cApiAction");
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiActionCollection()
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct();
    }

    public function create($area, $name, $code = "", $location = "", $relevant = 1)
    {
        $item = parent::create();

        if (is_string($area)) {
            $c = new cApiArea();
            $c->loadBy("name", $area);

            if ($c->virgin) {
                $area = 0;
                cWarning(__FILE__, __LINE__, "Could not resolve area [$area] passed to method [create], assuming 0");
            } else {
                $area = $c->get("idarea");
            }
        }

        $item->set("idarea", $area);
        $item->set("name", $name);
        $item->set("code", $code);
        $item->set("location", $location);
        $item->set("relevant", $relevant);

        $item->store();

        return ($item);
    }
}


class cApiAction extends Item
{
    protected $_objectInvalid;

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        $this->_objectInvalid = false;

        parent::__construct($cfg['tab']['actions'], 'idaction');
        $this->setFilters(array('addslashes'), array('stripslashes'));

        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }

        // @todo  Where is this used???
        $this->_wantParameters = array();
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiAction($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct($mId);
    }
}

?>