<?php

/**
 * This file contains the container collection and item class.
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
 * Container collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @method cApiContainer createNewItem
 * @method cApiContainer|bool next
 */
class cApiContainerCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @param bool $select [optional]
     *                     where clause to use for selection (see ItemCollection::select())
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function __construct($select = false) {
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
     *
     * @return cApiContainer
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($idtpl, $number, $idmod) {
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
     * @param int $idtpl
     * @return array
     * @throws cDbException
     */
    public function getNumbersByTemplate($idtpl) {
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
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function clearAssignments($idtpl) {
        $this->deleteBy('idtpl', (int) $idtpl);
    }

    /**
     *
     * @param int $idtpl
     * @param int $number
     * @param int $idmod
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function assignModule($idtpl, $number, $idmod) {
        $this->select('idtpl = ' . (int) $idtpl . ' AND number = ' . (int) $number);
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
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiContainer extends Item
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
        parent::__construct(cRegistry::getDbTableName('container'), 'idcontainer');
        $this->setFilters([], []);
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * User-defined setter for container fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
     *
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idtpl':
            case 'number':
            case 'idmod':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

}
