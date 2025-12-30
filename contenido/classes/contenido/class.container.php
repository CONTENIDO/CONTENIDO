<?php

/**
 * This file contains the container collection and item class.
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
 * Container collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @method cApiContainer createNewItem
 * @method cApiContainer|bool next
 */
class cApiContainerCollection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param string|false $select [optional] Where clause to use for selection {@see ItemCollection::select()}
     * @throws cDbException|cInvalidArgumentException
     */
    public function __construct($select = false)
    {
        parent::__construct(cRegistry::getDbTableName('container'), 'idcontainer');
        $this->_setItemClass('cApiContainer');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiTemplateCollection');

        if ($select !== false) {
            $this->select($select);
        }
    }

    /**
     * Creates a container item entry
     *
     * @param int $idtpl
     * @param int $number
     * @param int $idmod
     * @return cApiContainer
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($idtpl, $number, $idmod)
    {
        $item = $this->createNewItem();

        $item->set('idtpl', $idtpl);
        $item->set('number', $number);
        $item->set('idmod', $idmod);
        $item->store();

        return $item;
    }

    /**
     * Returns list of container numbers by passed template id.
     *
     * @throws cDbException
     */
    public function getNumbersByTemplate($idtpl): array
    {
        $list = [];
        $sql = "SELECT number FROM `%s` WHERE idtpl = %d";
        $this->db->query($sql, $this->table, $idtpl);
        while ($this->db->nextRecord()) {
            $list[] = $this->db->f('number');
        }
        return $list;
    }

    /**
     * Deletes all configurations by given template id
     *
     * @param int $idtpl
     * @throws cDbException|cInvalidArgumentException
     */
    public function clearAssignments($idtpl)
    {
        $this->deleteBy('idtpl', (int)$idtpl);
    }

    /**
     * @param int $idtpl
     * @param int $number
     * @param int $idmod
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function assignModule($idtpl, $number, $idmod)
    {
        $this->select('idtpl = ' . (int)$idtpl . ' AND number = ' . (int)$number);
        if (($item = $this->next()) !== false) {
            $item->set('idmod', $idmod);
            $item->store();
        } else {
            $this->create($idtpl, $number, $idmod);
        }
    }
}

/**
 * Container item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiContainer extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id Specifies the ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cRegistry::getDbTableName('container'), 'idcontainer');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * User-defined setter for container fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idtpl':
            case 'number':
            case 'idmod':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

}
