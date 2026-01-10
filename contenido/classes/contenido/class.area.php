<?php

/**
 * This file contains the area collection and item class.
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
 * Area collection.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiArea>
 */
class cApiAreaCollection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('area'), 'idarea');
        $this->_setItemClass('cApiArea');
    }

    /**
     * Creates an area item entry.
     *
     * @param string $name Name
     * @param string|int $parentId [optional] Parent id as a string or number
     * @param int $relevant [optional] 0 or 1
     * @param int $online [optional] 0 or 1
     * @param int $menuless [optional] 0 or 1
     * @return cApiArea
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($name, $parentId = 0, $relevant = 1, $online = 1, $menuless = 0)
    {
        $parentId = (is_string($parentId)) ? $this->escape($parentId) : (int)$parentId;

        $item = $this->createNewItem();

        $item->set('parent_id', $parentId);
        $item->set('name', $name);
        $item->set('relevant', $relevant);
        $item->set('online', $online);
        $item->set('menuless', $menuless);

        $item->store();

        return $item;
    }

    /**
     * Returns the parent id of passed area.
     *
     * @param int|string $area Area id or name
     * @return string|int Name of parent area or passed area
     * @throws cDbException
     */
    public function getParentAreaId($area)
    {
        if (is_numeric($area)) {
            $sql = "SELECT b.name FROM `%s` AS a, `%s` AS b WHERE a.idarea = %d AND b.name = a.parent_id";
        } else {
            $sql = "SELECT b.name FROM `%s` AS a, `%s` AS b WHERE a.name = '%s' AND b.name = a.parent_id";
        }
        $this->db->query($sql, $this->table, $this->table, $area);
        return $this->db->nextRecord() ? $this->db->f('name') : $area;
    }

    /**
     * Returns all area ids having passed area as name or as parent id.
     *
     * @param int|string $nameOrId Area name or parent id
     * @return array List of area ids
     * @throws cDbException
     */
    public function getIdareasByAreaNameOrParentId($nameOrId)
    {
        $sql = "SELECT idarea FROM `%s` AS a WHERE a.name = '%s' OR a.parent_id = '%s' ORDER BY idarea";
        $this->db->query($sql, $this->table, $nameOrId, $nameOrId);

        $ids = [];
        while ($this->db->nextRecord()) {
            $ids[] = $this->db->f('idarea');
        }

        return $ids;
    }

    /**
     * Returns the area name by area id.
     *
     * This function is similar to {@see cApiAreaCollection::getAreaName()},
     * but it uses direct SQL instead a cApiArea instance.
     *
     * @param int $areaId The area id
     * @throws cDbException
     * @since CONTENIDO 4.10.2
     */
    public function getNameByAreaId(int $areaId): string
    {
        $sql = "SELECT `name` FROM `%s` WHERE `idarea` = %d";
        $this->db->query($sql, $this->table, $areaId);
        return $this->db->nextRecord() ? $this->db->f('name') : '';
    }

    /**
     * Returns area ids of areas by parent id and area id.
     *
     * @param string|int $parentId Parent id as a string or number
     * @param int $areaId The area id
     * @throws cDbException
     * @since CONTENIDO 4.10.2
     */
    public function getAreaIdsByParentIdOrAreaId($parentId, int $areaId): array
    {
        $sql = "SELECT `idarea` FROM `%s` WHERE `parent_id` = '%s' OR `idarea` = %d";
        $this->db->query($sql, $this->table, $parentId, $areaId);

        $areaIds = [];
        while ($this->db->nextRecord()) {
            $areaIds[] = cSecurity::toInteger($this->db->f('idarea'));
        }

        return $areaIds;
    }

    /**
     * Returns all areas available in the system.
     *
     * @return array Array with id and name entries
     * @throws cDbException|cException
     */
    public function getAvailableAreas(): array
    {
        $this->select();

        $aAreas = [];
        while ($oItem = $this->next()) {
            $aAreas[$oItem->get('idarea')] = [
                'name' => $oItem->get('name')
            ];
        }

        return $aAreas;
    }

    /**
     * Returns the name for a given area id.
     *
     * @param string $area
     * @return string String with the name for the area
     */
    public function getAreaName($area)
    {
        $oItem = new cApiArea($area);
        return $oItem->get('name');
    }

    /**
     * Returns the idarea for a given area name.
     *
     * @param int|string $area
     * @return int Id for the area
     * @throws cDbException|cException
     */
    public function getAreaId($area): int
    {
        // if area name is numeric (legacy areas)
        if (is_numeric($area)) {
            return (int) $area;
        }

        $oItem = new cApiArea();
        $oItem->loadBy('name', $area);

        if (!$oItem->isLoaded()) {
            return 0;
        }

        return (int) $oItem->get('idarea');
    }
}

/**
 * Area item.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiArea extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('area'), 'idarea');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * User-defined setter for area fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'online':
            case 'menuless':
            case 'relevant':
                $value = ($value == 1) ? 1 : 0;
                break;
        }

        return parent::setField($name, $value, $safe);
    }

}
