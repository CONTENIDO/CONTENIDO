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
 * @extends ItemCollection<cApiContainer>
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
        parent::__construct(cDb::getTableName('container'), 'idcontainer');
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
     * @param int $templateId
     * @param int $number
     * @param int $moduleId
     * @return cApiContainer
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($templateId, $number, $moduleId)
    {
        $item = $this->createNewItem();

        $item->set('idtpl', $templateId);
        $item->set('number', $number);
        $item->set('idmod', $moduleId);
        $item->store();

        return $item;
    }

    /**
     * Returns list of container numbers by passed template id.
     *
     * @return int[]
     * @throws cDbException
     */
    public function getNumbersByTemplate($templateId): array
    {
        $list = [];
        $sql = "SELECT `number` FROM `%s` WHERE `idtpl` = %d";
        $this->db->query($sql, $this->table, $templateId);
        while ($this->db->nextRecord()) {
            $list[] = cSecurity::toInteger($this->db->f('number'));
        }
        return $list;
    }

    /**
     * Deletes all configurations by given template id
     *
     * @param int $templateId
     * @throws cDbException|cInvalidArgumentException
     */
    public function clearAssignments($templateId)
    {
        $this->deleteBy('idtpl', cSecurity::toInteger($templateId));
    }

    /**
     * @param int $templateId
     * @param int $number
     * @param int $moduleId
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function assignModule($templateId, $number, $moduleId)
    {
        $this->select($this->db->prepare(
            '`idtpl` = %d AND `number` = %d',
            $templateId,
            $number
        ));
        if (($item = $this->next()) !== false) {
            $item->set('idmod', $moduleId);
            $item->store();
        } else {
            $this->create($templateId, $number, $moduleId);
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
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('container'), 'idcontainer');
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
