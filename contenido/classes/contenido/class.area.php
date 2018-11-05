<?php
/**
 * This file contains the area collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Area collection.
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiAreaCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['area'], 'idarea');
        $this->_setItemClass('cApiArea');
    }

    /**
     * Creates an area item entry.
     *
     * @param string     $name
     *                             Name
     * @param string|int $parentid [optional]
     *                             Parent id as astring or number
     * @param int        $relevant [optional]
     *                             0 or 1
     * @param int        $online   [optional]
     *                             0 or 1
     * @param int        $menuless [optional]
     *                             0 or 1
     *
     * @return cApiArea
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($name, $parentid = 0, $relevant = 1, $online = 1, $menuless = 0) {
        $parentid = (is_string($parentid)) ? $this->escape($parentid) : (int) $parentid;

        $item = $this->createNewItem();

        $item->set('parent_id', $parentid);
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
     * @param int|string $area
     *         Area id or name
     * @return string|int
     *         name of parent area or passed area
     * @throws cDbException
     */
    public function getParentAreaID($area) {
        if (is_numeric($area)) {
            $sql = "SELECT b.name FROM `%s` AS a, `%s` AS b WHERE a.idarea = %d AND b.name = a.parent_id";
        } else {
            $sql = "SELECT b.name FROM `%s` AS a, `%s` AS b WHERE a.name = '%s' AND b.name = a.parent_id";
        }
        $this->db->query($sql, $this->table, $this->table, $area);
        return ($this->db->nextRecord()) ? $this->db->f('name') : $area;
    }

    /**
     * Returns all area ids having passed area as name or as parent id.
     *
     * @param int|string $nameOrId
     *         Area name or parent id
     * @return array
     *         List of area ids
     * @throws cDbException
     */
    public function getIdareasByAreaNameOrParentId($nameOrId) {
        $sql = "SELECT idarea FROM `%s` AS a WHERE a.name = '%s' OR a.parent_id = '%s' ORDER BY idarea";
        $this->db->query($sql, $this->table, $nameOrId, $nameOrId);

        $ids = array();
        while ($this->db->nextRecord()) {
            $ids[] = $this->db->f('idarea');
        }

        return $ids;
    }

    /**
     * Returns all areas available in the system.
     *
     * @return array
     *         Array with id and name entries
     * @throws cDbException
     * @throws cException
     */
    public function getAvailableAreas() {
        $aClients = array();

        $this->select();

        while (($oItem = $this->next()) !== false) {
            $aAreas[$oItem->get('idarea')] = array(
                'name' => $oItem->get('name')
            );
        }

        return ($aAreas);
    }

    /**
     * Returns the name for a given area id.
     *
     * @param string $area
     * @return string
     *         String with the name for the area
     */
    public function getAreaName($area) {
        $oItem = new cApiArea($area);
        return $oItem->get('name');
    }

    /**
     * Returns the idarea for a given area name.
     *
     * @param string $area
     *
     * @return int
     *         Integer with the ID for the area
     *
     * @throws cDbException
     * @throws cException
     */
    public function getAreaID($area) {
        // if area name is numeric (legacy areas)
        if (is_numeric($area)) {
            return $area;
        }

        $oItem = new cApiArea();
        $oItem->loadBy('name', $area);

        if ($oItem->isLoaded() === false) {
            return $area;
        }

        return $oItem->get('idarea');
    }
}

/**
 * Area item.
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiArea extends Item
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
        global $cfg;
        parent::__construct($cfg['tab']['area'], 'idarea');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Userdefined setter for area fields.
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
                $value = ($value == 1) ? 1 : 0;
                break;
            case 'online':
                $value = ($value == 1) ? 1 : 0;
                break;
            case 'menuless':
                $value = ($value == 1) ? 1 : 0;
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

}
