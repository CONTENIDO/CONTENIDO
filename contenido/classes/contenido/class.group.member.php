<?php

/**
 * This file contains the group member collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
 * @author           Dominik Ziegler
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Group member collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiGroupMemberCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['groupmembers'], 'idgroupuser');
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
     *
     * @return cApiGroupMember
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($userId, $groupId) {
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
     *
     * @return bool
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function deleteByUserId($userId) {
        $result = $this->deleteBy('user_id', $userId);
        return ($result > 0) ? true : false;
    }

    /**
     * Fetches entry from table by user id and group id
     *
     * @param string $userId
     * @param string $groupId
     * 
     * @return cApiGroupMember|NULL
     * 
     * @throws cDbException
     * @throws cException
     */
    public function fetchByUserIdAndGroupId($userId, $groupId) {
        $where = "user_id = '" . $this->escape($userId) . "' AND group_id = '" . $this->escape($groupId) . "'";
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
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiGroupMember extends Item
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
        parent::__construct($cfg['tab']['groupmembers'], 'idgroupuser');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

}
