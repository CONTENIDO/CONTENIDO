<?php

/**
 * This file contains the group member collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Group member collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiGroupMember>
 */
class cApiGroupMemberCollection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('groupmembers'), 'idgroupuser');
        $this->_setItemClass('cApiGroupMember');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiGroupCollection');
        $this->_setJoinPartner('cApiUserCollection');
    }

    /**
     * Creates a group member entry.
     *
     * @param string $userId
     * @param string $groupId
     * @return cApiGroupMember
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($userId, $groupId)
    {
        $oItem = $this->createNewItem();

        $oItem->set('user_id', $userId);
        $oItem->set('group_id', $groupId);

        $oItem->store();

        return $oItem;
    }

    /**
     * Deletes group member entries by user id.
     *
     * @param string $userId
     * @throws cDbException|cInvalidArgumentException
     */
    public function deleteByUserId($userId): bool
    {
        return $this->deleteBy('user_id', $userId) > 0;
    }

    /**
     * Fetches entry from table by user id and group id
     *
     * @param string $userId
     * @param string $groupId
     * @return ?cApiGroupMember
     * @throws cDbException|cException
     */
    public function fetchByUserIdAndGroupId($userId, $groupId)
    {
        $where =sprintf( "`user_id` = '%s' AND `group_id` = '%s'", $this->escape($userId), $this->escape($groupId));
        if ($this->select($where)) {
            return $this->next();
        } else {
            return NULL;
        }
    }

}

/**
 * Group member item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiGroupMember extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id Specifies the ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('groupmembers'), 'idgroupuser');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

}
