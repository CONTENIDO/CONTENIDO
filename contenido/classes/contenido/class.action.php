<?php

/**
 * This file contains the action collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Action collection.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiAction>
 */
class cApiActionCollection extends ItemCollection
{

    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('actions'), 'idaction');
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
     * @param string $code [optional]
     * @param string $location [optional]
     * @param int $relevant [optional]
     * @return cApiAction
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($area, $name, $alt_name = '', $code = '', $location = '', $relevant = 1)
    {
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
     * @return array Array with id and name entries
     * @throws cDbException|cInvalidArgumentException
     */
    public function getAvailableActions(): array
    {
        $sql = "SELECT action.idaction, action.name, area.name AS areaname
                FROM `%s` AS action LEFT JOIN `%s` AS area
                ON area.idarea = action.idarea
                WHERE action.relevant = 1 ORDER BY action.name;";

        $this->db->query($sql, $this->table, cDb::getTableName('area'));

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
     * @param int $action Id of action
     * @throws cDbException|cInvalidArgumentException
     */
    public function getActionName($action): ?string
    {
        $this->db->query("SELECT name FROM `%s` WHERE idaction = %d", $this->table, $action);

        return $this->db->nextRecord() ? $this->db->f('name') : null;
    }

    /**
     * Returns the area for the given action.
     *
     * @param string|int $action Name or id of action
     * @return ?int The area ID for the given action or NULL
     * @throws cDbException|cInvalidArgumentException
     */
    public function getAreaForAction($action): ?int
    {
        if (!is_numeric($action)) {
            $this->db->query("SELECT idarea FROM `%s` WHERE name = '%s'", $this->table, $action);
        } else {
            $this->db->query("SELECT idarea FROM `%s` WHERE idaction = %d", $this->table, $action);
        }

        return $this->db->nextRecord() ? $this->db->f('idarea') : null;
    }
}

/**
 * Action item.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiAction extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('actions'), 'idaction');
        $this->setFilters(['addslashes'], ['stripslashes']);

        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * User-defined setter for action fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'relevant':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

}
