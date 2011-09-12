<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Frontend permissions class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend classes
 * @version    1.6
 * @author     unknowm
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  unknown
 *   modified 2008-06-30, Frederic Schneider, add security fix
 *   modified 2011-03-14, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Frontend user management class
 */
class FrontendPermissionCollection extends ItemCollection
{

    protected $_FrontendPermission;

    /**
     * Constructor Function
     * @param none
     */
    public function __construct()
    {
        global $cfg;
        $this->_FrontendPermission = new FrontendPermission();

        parent::__construct($cfg["tab"]["frontendpermissions"], "idfrontendpermission");
        $this->_setItemClass("FrontendPermission");
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function FrontendPermissionCollection()
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }

    /**
     * Creates a new permission entry
     * @param $group string Specifies the frontend group
     * @param $plugin string Specifies the plugin
     * @param $action string Specifies the action
     * @param $item   string Specifies the item
     */
    public function create($group, $plugin, $action, $mitem)
    {
        global $lang;

        $item = null;
        if (!$this->checkPerm($group, $plugin, $action, $mitem)) {
            $item = parent::create();
            $item->set("idlang", $lang);
            $item->set("idfrontendgroup", $group);
            $item->set("plugin", $plugin);
            $item->set("action", $action);
            $item->set("item", $mitem);

            $item->store();
        }

        return $item;
    }

    public function setPerm($group, $plugin, $action, $item)
    {
        $this->create($group, $plugin, $action, $item);
    }

    public function checkPerm($group, $plugin, $action, $item, $uselang = false)
    {
        global $lang;

        #$checklang = ($uselang !== false) ? $uselang : $lang;

        $group  = $this->_FrontendPermission->_inFilter($group);
        $plugin = $this->_FrontendPermission->_inFilter($plugin);
        $action = $this->_FrontendPermission->_inFilter($action);
        $item   = $this->_FrontendPermission->_inFilter($item);

        // Check for global permisson
        $this->select("idlang = '$lang' AND idfrontendgroup = '$group' AND plugin = '$plugin' AND action = '$action' AND item = '__GLOBAL__'");

        if ($this->next()) {
            return true;
        }

        // Check for item permisson
        $this->select("idlang = '$lang' AND idfrontendgroup = '$group' AND plugin = '$plugin' AND action = '$action' AND item = '$item'");

        if ($this->next()) {
            return true;
        } else {
            return false;
        }
    }

    public function removePerm($group, $plugin, $action, $item, $uselang = false)
    {
        global $lang;

        #$checklang = ($uselang !== false) ? $uselang : $lang;

        $group  = $this->_FrontendPermission->_inFilter($group);
        $plugin = $this->_FrontendPermission->_inFilter($plugin);
        $action = $this->_FrontendPermission->_inFilter($action);
        $item   = $this->_FrontendPermission->_inFilter($item);

        $this->select("idlang = '$lang' AND idfrontendgroup = '$group' AND plugin = '$plugin' AND action = '$action' AND item = '$item'");

        if ($myitem = $this->next()) {
            $this->delete($myitem->get("idfrontendpermission"));
        }
    }
}


/**
 * Single FrontendPermission Item
 */
class FrontendPermission extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["frontendpermissions"], "idfrontendpermission");
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function FrontendPermission($mId = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct($mId);
    }
}

?>