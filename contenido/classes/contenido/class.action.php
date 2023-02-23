<?php

/**
 * This file contains the action collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Action collection.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @method cApiAction createNewItem
 * @method cApiAction|bool next
 */
class cApiActionCollection extends ItemCollection {

    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        parent::__construct(cRegistry::getDbTableName('actions'), 'idaction');
        $this->_setItemClass('cApiAction');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiAreaCollection');
    }

    /**
     * Creates an action entry.
     *
     * @param string|int $area
     * @param string|int $name
     * @param string|int $alt_name [optional]
     * @param string     $code     [optional]
     * @param string     $location [optional]
     * @param int        $relevant [optional]
     *
     * @return cApiAction
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($area, $name, $alt_name = '', $code = '', $location = '', $relevant = 1) {
        $item = $this->createNewItem();

        if (is_string($area)) {
            $c = new cApiArea();
            $c->loadBy('name', $area);
            if ($c->isLoaded()) {
                $area = $c->get('idarea');
            } else {
                $area = 0;
                cWarning(__FILE__, __LINE__, "Could not resolve area [$area] passed to method [create], assuming 0");
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
        $item->set('relevant', $relevant);

        $item->store();

        return $item;
    }

    /**
     * Returns all actions available in the system.
     *
     * @return array
     *         Array with id and name entries
     *
     * @throws cDbException
     */
    public function getAvailableActions() {
        $sql = "SELECT action.idaction, action.name, area.name AS areaname
                FROM `%s` AS action LEFT JOIN `%s` AS area
                ON area.idarea = action.idarea
                WHERE action.relevant = 1 ORDER BY action.name;";

        $this->db->query($sql, $this->table, cRegistry::getDbTableName('area'));

        $actions = [];

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
     * @param int $action
     *         Id of action
     *
     * @return string|NULL
     *
     * @throws cDbException
     */
    public function getActionName($action) {
        $this->db->query("SELECT name FROM `%s` WHERE idaction = %d", $this->table, $action);

        return ($this->db->nextRecord()) ? $this->db->f('name') : NULL;
    }

    /**
     * Returns the area for the given action.
     *
     * @param string|int
     *         Name or id of action
     *
     * @return int|NULL
     *         with the area ID for the given action or NULL
     *
     * @throws cDbException
     */
    function getAreaForAction($action) {
        if (!is_numeric($action)) {
            $this->db->query("SELECT idarea FROM `%s` WHERE name = '%s'", $this->table, $action);
        } else {
            $this->db->query("SELECT idarea FROM `%s` WHERE idaction = %d", $this->table, $action);
        }

        return ($this->db->nextRecord()) ? $this->db->f('idarea') : NULL;
    }
}

/**
 * Action item.
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiAction extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $mId [optional]
     *                   Specifies the ID of item to load
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($mId = false) {
        parent::__construct(cRegistry::getDbTableName('actions'), 'idaction');
        $this->setFilters(['addslashes'], ['stripslashes']);

        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }

        // @todo Where is this used???
        $this->_wantParameters = [];
    }

    /**
     * User-defined setter for action fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
             case 'relevant':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

}
