<?php

/**
 * This file contains the frontend group collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Frontend group collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiFrontendGroup>
 */
class cApiFrontendGroupCollection extends ItemCollection
{

    use cItemCollectionIdsByClientIdTrait;

    /**
     * @var string Client id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkClientIdName = 'idclient';

    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('frontendgroups'), 'idfrontendgroup');
        $this->_setItemClass('cApiFrontendGroup');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');
    }

    /**
     * Creates a new group
     *
     * @param string $groupName Specifies the group name
     * @return cApiFrontendGroup
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($groupName, ?int $clientId = null)
    {
        $clientId = $clientId ?? cRegistry::getClientId();

        $group = new cApiFrontendGroup();

        $mangledGroupName = $group->inFilter($groupName);
        $this->select(sprintf("`idclient` = %d AND `groupname` = '%s'", $clientId, $mangledGroupName));
        if ($this->next()) {
            // Groupname exists, append random hash
            $groupName .= md5(rand());
        }

        $item = $this->createNewItem();
        $item->set('idclient', $clientId);
        $item->set('groupname', $groupName);
        $item->store();

        return $item;
    }

    /**
     * Overridden delete method to remove groups from group member table before deleting group
     *
     * @inheritDoc
     * @param int $id The frontend user group id.
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function delete($id)
    {
        $associations = new cApiFrontendGroupMemberCollection();
        $associations->select(sprintf('`idfrontendgroup` = %d', $id));

        while ($item = $associations->next()) {
            $associations->delete($item->get('idfrontendgroupmember'));
        }

        return parent::delete($id);
    }
}

/**
 * Frontend group item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiFrontendGroup extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('frontendgroups'), 'idfrontendgroup');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }
}
