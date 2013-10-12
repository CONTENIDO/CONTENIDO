<?php
/**
 * This file contains the frontend permission collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @version SVN Revision $Rev:$
 *
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Frontend permission collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiFrontendPermissionCollection extends ItemCollection {

    /**
     * instance of cApiFrontendPermission to access defined filters
     *
     * @var cApiFrontendPermission
     */
    protected $_frontendPermission;

    /**
     * Constructor Function
     */
    public function __construct() {
        global $cfg;
        $this->_frontendPermission = new cApiFrontendPermission();

        parent::__construct($cfg['tab']['frontendpermissions'], 'idfrontendpermission');
        $this->_setItemClass('cApiFrontendPermission');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiFrontendGroupCollection');
        $this->_setJoinPartner('cApiLanguageCollection');
    }

    /**
     * Creates a new permission entry.
     *
     * @param int $group Specifies the frontend group
     * @param string $plugin Specifies the plugin
     * @param string $action Specifies the action
     * @param string $item Specifies the item
     * @return cApiFrontendPermission NULL
     */
    public function create($group, $plugin, $action, $item) {
        global $lang;

        $perm = NULL;
        if (!$this->checkPerm($group, $plugin, $action, $item)) {
            $perm = parent::createNewItem();
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
     * @param int $group Specifies the frontend group
     * @param string $plugin Specifies the plugin
     * @param string $action Specifies the action
     * @param string $item Specifies the item
     */
    public function setPerm($group, $plugin, $action, $item) {
        $this->create($group, $plugin, $action, $item);
    }

    /**
     * Cheks, if an entry exists.
     *
     * 1.) Checks for global permission
     * 2.) Checks for specific item permission
     *
     * @param int $group Specifies the frontend group
     * @param string $plugin Specifies the plugin
     * @param string $action Specifies the action
     * @param string $item Specifies the item
     * @param bool $useLang Flag to use language (Not used!)
     * @return bool
     */
    public function checkPerm($group, $plugin, $action, $item, $useLang = false) {
        global $lang;

        // checklang = ($useLang !== false) ? $useLang : $lang;

        $group = (int) $group;
        $plugin = $this->_frontendPermission->_inFilter($plugin);
        $action = $this->_frontendPermission->_inFilter($action);
        $item = $this->_frontendPermission->_inFilter($item);

        // Check for global permisson
        $this->select("idlang = " . $lang . " AND idfrontendgroup = " . $group . " AND plugin = '" . $plugin . "' AND action = '" . $action . "' AND item = '__GLOBAL__'");
        if ($this->next()) {
            return true;
        }

        // Check for item permisson
        $this->select("idlang = " . $lang . " AND idfrontendgroup = " . $group . " AND plugin = '" . $plugin . "' AND action = '" . $action . "' AND item = '" . $item . "'");
        return ($this->next()) ? true : false;
    }

    /**
     * Removes the permission.
     *
     * @param int $group Specifies the frontend group
     * @param string $plugin Specifies the plugin
     * @param string $action Specifies the action
     * @param string $item Specifies the item
     * @param bool $useLang Flag to use language (Not used!)
     * @return bool
     */
    public function removePerm($group, $plugin, $action, $item, $useLang = false) {
        global $lang;

        // checklang = ($useLang !== false) ? $useLang : $lang;

        $group = (int) $group;
        $plugin = $this->_frontendPermission->_inFilter($plugin);
        $action = $this->_frontendPermission->_inFilter($action);
        $item = $this->_frontendPermission->_inFilter($item);

        $this->select("idlang = " . $lang . " AND idfrontendgroup = " . $group . " AND plugin = '" . $plugin . "' AND action = '" . $action . "' AND item = '" . $item . "'");
        if (($myitem = $this->next()) !== false) {
            return $this->delete($myitem->get('idfrontendpermission'));
        }
        return false;
    }
}

/**
 * Frontend permission item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiFrontendPermission extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['frontendpermissions'], 'idfrontendpermission');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}
