<?php
/**
 * This file contains the area collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @version SVN Revision $Rev:$
 *
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Area collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiAreaCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['area'], 'idarea');
        $this->_setItemClass('cApiArea');
    }

    /**
     * Creates a area item entry
     *
     * @param string $name Name
     * @param string|int $parentid Parent id as astring or number
     * @param int $relevant 0 or 1
     * @param int $online 0 or 1
     * @param int $menuless 0 or 1
     * @return cApiArea
     */
    public function create($name, $parentid = 0, $relevant = 1, $online = 1, $menuless = 0) {
        $parentid = (is_string($parentid)) ? $this->escape($parentid) : (int) $parentid;

        $item = parent::createNewItem();

        $item->set('parent_id', $parentid);
        $item->set('name', $this->escape($name));
        $item->set('relevant', (1 == $relevant) ? 1 : 0);
        $item->set('online', (1 == $online) ? 1 : 0);
        $item->set('menuless', (1 == $menuless) ? 1 : 0);

        $item->store();

        return $item;
    }

    /**
     * Returns the parent id of passed area
     *
     * @param int|string $area Area id or name
     * @return string int name of parent area or passed area
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
     * Returns all area ids having passed area as name or as parent id
     *
     * @param int|string $nameOrId Area name or parent id
     * @return array List of area ids
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
     * Returns all areas available in the system
     *
     * @return array Array with id and name entries
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
     * Returns the name for a given areaid
     *
     * @param string $area
     * @return string String with the name for the area
     */
    public function getAreaName($area) {
        $oItem = new cApiArea($area);
        return $oItem->get('name');
    }

    /**
     * Returns the idarea for a given area name
     *
     * @param string $area
     * @return int Integer with the ID for the area
     */
    public function getAreaID($area) {
        $oItem = new cApiArea();
        $oItem->loadBy('name', $area);

        return $oItem->get('idarea');
    }
}

/**
 * Area item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiArea extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['area'], 'idarea');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}
