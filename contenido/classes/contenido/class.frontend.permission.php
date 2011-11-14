<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Frontend permission classes
 *
 * Code is taken over from file contenido/classes/class.frontend.permissions.php in favor of
 * normalizing API.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 *
 * {@internal
 *   created  2011-10-06
 *
 *   $Id: $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Frontend user management class
 */
class cApiFrontendPermissionCollection extends ItemCollection
{
    /**
     * @var cApiFrontendPermission
     */
    protected $_frontendPermission;

    /**
     * Constructor Function
     */
    public function __construct()
    {
        global $cfg;
        $this->_frontendPermission = new cApiFrontendPermission();

        parent::__construct($cfg['tab']['frontendpermissions'], 'idfrontendpermission');
        $this->_setItemClass('cApiFrontendPermission');
    }

    /**
     * Creates a new permission entry.
     *
     * @param  int     $group   Specifies the frontend group
     * @param  string  $plugin  Specifies the plugin
     * @param  string  $action  Specifies the action
     * @param  string  $item    Specifies the item
     * @return cApiFrontendPermission|null
     */
    public function create($group, $plugin, $action, $item)
    {
        global $lang;

        $perm = null;
        if (!$this->checkPerm($group, $plugin, $action, $item)) {
            $perm = parent::create();
            $perm->set('idlang', $lang);
            $perm->set('idfrontendgroup', $group);
            $perm->set('plugin', $plugin);
            $perm->set('action', $action);
            $perm->set('item', $item);

            $perm->store();
        }

        return $perm;
    }

    /**
     * Sets a permission entry, is a wrapper for create() function
     *
     * @param  int     $group   Specifies the frontend group
     * @param  string  $plugin  Specifies the plugin
     * @param  string  $action  Specifies the action
     * @param  string  $item    Specifies the item
     */
    public function setPerm($group, $plugin, $action, $item)
    {
        $this->create($group, $plugin, $action, $item);
    }

    /**
     * Cheks, if an entry exists.
     *
     * 1.) Checks for global permission
     * 2.) Checks for specific item permission
     *
     * @param  int     $group    Specifies the frontend group
     * @param  string  $plugin   Specifies the plugin
     * @param  string  $action   Specifies the action
     * @param  string  $item     Specifies the item
     * @param  bool    $useLang  Flag to use language (Not used!)
     * @return bool
     */
    public function checkPerm($group, $plugin, $action, $item, $useLang = false)
    {
        global $lang;

        #$checklang = ($useLang !== false) ? $useLang : $lang;

        $group  = (int) $group;
        $plugin = $this->_frontendPermission->_inFilter($plugin);
        $action = $this->_frontendPermission->_inFilter($action);
        $item   = $this->_frontendPermission->_inFilter($item);

        // Check for global permisson
        $this->select("idlang=" . $lang . " AND idfrontendgroup=" . $group . " AND plugin='" . $plugin. "' AND action='" . $action . "' AND item='__GLOBAL__'");
        if ($this->next()) {
            return true;
        }

        // Check for item permisson
        $this->select("idlang=" . $lang . " AND idfrontendgroup=" . $group . " AND plugin='" . $plugin. "' AND action='" . $action . "' AND item='" . $item . "'");
        return ($this->next()) ? true : false;
    }

    /**
     * Removes the permission.
     *
     * @param  int     $group    Specifies the frontend group
     * @param  string  $plugin   Specifies the plugin
     * @param  string  $action   Specifies the action
     * @param  string  $item     Specifies the item
     * @param  bool    $useLang  Flag to use language (Not used!)
     * @return bool
     */
    public function removePerm($group, $plugin, $action, $item, $useLang = false)
    {
        global $lang;

        #$checklang = ($useLang !== false) ? $useLang : $lang;

        $group  = (int) $group;
        $plugin = $this->_frontendPermission->_inFilter($plugin);
        $action = $this->_frontendPermission->_inFilter($action);
        $item   = $this->_frontendPermission->_inFilter($item);

        $this->select("idlang=" . $lang . " AND idfrontendgroup=" . $group . " AND plugin='" . $plugin. "' AND action='" . $action . "' AND item='" . $item . "'");
        if ($myitem = $this->next()) {
            return $this->delete($myitem->get('idfrontendpermission'));
        }
        return false;
    }
}


/**
 * Single cApiFrontendPermission Item
 */
class cApiFrontendPermission extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['frontendpermissions'], 'idfrontendpermission');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
   }
}

################################################################################
# Old versions of frontend permission item collection and frontend permission 
# item classes
#
# NOTE: Class implemetations below are deprecated and the will be removed in
#       future versions of contenido.
#       Don't use them, they are still available due to downwards compatibility.


/**
 * Frontend permission collection
 * @deprecated  [2011-10-06] Use cApiFrontendPermissionCollection instead of this class.
 */
class FrontendPermissionCollection extends cApiFrontendPermissionCollection
{
    public function __construct()
    {
        cWarning(__FILE__, __LINE__, 'Deprecated class ' . __CLASS__ . ' use ' . get_parent_class($this));
        parent::__construct();
    }
    public function FrontendPermissionCollection()
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct();
    }
}


/**
 * Single frontend permission item
 * @deprecated  [2011-10-06] Use cApiFrontendPermission instead of this class.
 */
class FrontendPermission extends cApiFrontendPermission
{
    public function __construct($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated class ' . __CLASS__ . ' use ' . get_parent_class($this));
        parent::__construct($mId);
    }
    public function FrontendPermission($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct($mId);
    }
}

?>