<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Action management class
 *
 * @package CONTENIDO API
 * @version 1.5
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Action collection
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiActionCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['actions'], 'idaction');
        $this->_setItemClass('cApiAction');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiAreaCollection');
    }

    /**
     *
     * @deprecated [2011-03-15] Old constructor function for downwards
     *             compatibility
     */
    public function cApiActionCollection() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    /**
     * Creates an action entry
     *
     * @param string|int $area
     * @param string|int $name
     * @param string|int $alt_name
     * @param string $code
     * @param string $location
     * @param int $relevant
     * @return cApiAction
     */
    public function create($area, $name, $alt_name = '', $code = '', $location = '', $relevant = 1) {
        $item = parent::createNewItem();

        if (is_string($area)) {
            $c = new cApiArea();
            $c->loadBy('name', $area);
            if ($c->virgin) {
                $area = 0;
                cWarning(__FILE__, __LINE__, "Could not resolve area [$area] passed to method [create], assuming 0");
            } else {
                $area = $c->get('idarea');
            }
        }

        if (is_string($area)) {
            $area = $this->escape($area);
        }
        if (is_string($name)) {
            $name = $this->escape($name);
        }
        if (is_string($alt_name)) {
            $alt_name = $this->escape($alt_name);
        }

        $item->set('idarea', $area);
        $item->set('name', $name);
        $item->set('alt_name', $alt_name);
        $item->set('code', $code);
        $item->set('location', $location);
        $item->set('relevant', (int) $relevant);

        $item->store();

        return $item;
    }

    /**
     * Returns all actions available in the system
     *
     * @return array Array with id and name entries
     */
    public function getAvailableActions() {
        global $cfg;

        $sql = "SELECT action.idaction, action.name, area.name AS areaname
                FROM `%s` AS action LEFT JOIN `%s` AS area
                ON area.idarea = action.idarea
                WHERE action.relevant = 1 ORDER BY action.name;";

        $this->db->query($sql, $this->table, $cfg['tab']['area']);

        $actions = array();

        while ($this->db->nextRecord()) {
            $newentry['name'] = $this->db->f('name');
            $newentry['areaname'] = $this->db->f('areaname');
            $actions[$this->db->f('idaction')] = $newentry;
        }

        return $actions;
    }

    /**
     * Return name of passed action.
     *
     * @param int Id of action
     * @return string null
     */
    public function getActionName($action) {
        $this->db->query("SELECT name FROM `%s` WHERE idaction = %d", $this->table, $action);
        return ($this->db->nextRecord())? $this->db->f('name') : null;
    }

    /**
     * Returns the area for the given action.
     *
     * @param string|int Name or id of action
     * @return int null with the area ID for the given action or null
     */
    function getAreaForAction($action) {
        if (!is_numeric($action)) {
            $this->db->query("SELECT idarea FROM `%s` WHERE name = '%s'", $this->table, $action);
        } else {
            $this->db->query("SELECT idarea FROM `%s` WHERE idaction = %d", $this->table, $action);
        }

        return ($this->db->nextRecord())? $this->db->f('idarea') : null;
    }

}

/**
 * Action item
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiAction extends Item {

    /**
     *
     * @var bool
     */
    protected $_objectInvalid;

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        $this->_objectInvalid = false;

        parent::__construct($cfg['tab']['actions'], 'idaction');
        $this->setFilters(array(
            'addslashes'
        ), array(
            'stripslashes'
        ));

        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }

        // @todo Where is this used???
        $this->_wantParameters = array();
    }

    /**
     *
     * @deprecated [2011-03-15] Old constructor function for downwards
     *             compatibility
     */
    public function cApiAction($mId = false) {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }

}

// ##############################################################################
// Old version of action class
//
// NOTE: Class implemetation below is deprecated and the will be removed in
// future versions of contenido.
// Don't use it, it's still available due to downwards compatibility.

/**
 * Action
 *
 * @deprecated [2012-03-01] Use cApiActionCollection instead of this class.
 */
class Action extends cApiActionCollection {

    public function __construct() {
        cDeprecated("Use class cApiActionCollection instead");
        parent::__construct();
    }

    public function Action() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

}
