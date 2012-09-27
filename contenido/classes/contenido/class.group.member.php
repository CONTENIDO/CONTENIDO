<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Group member class
 *
 * @package CONTENIDO API
 * @version 1.1
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Group member collection
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiGroupMemberCollection extends ItemCollection {

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
     * @return cApiGroupMember
     */
    public function create($userId, $groupId) {
        $oItem = parent::createNewItem();

        $oItem->set('user_id', $this->escape($userId));
        $oItem->set('group_id', $this->escape($groupId));

        $oItem->store();

        return $oItem;
    }

    /**
     * Deletes group member entries by user id.
     *
     * @param string $userId
     * @return bool
     */
    public function deleteByUserId($userId) {
        $result = $this->deleteBy('user_id', $userId);
        return ($result > 0)? true : false;
    }

    /**
     * Fetches entry from table by user id and group id
     *
     * @param string $userId
     * @param string $groupId
     * @return cApiGroupMember null
     */
    public function fetchByUserIdAndGroupId($userId, $groupId) {
        $where = "user_id = '" . $this->escape($userId) . "' AND group_id = '" . $this->escape($groupId) . "'";
        if ($this->select($where)) {
            return $this->next();
        } else {
            return null;
        }
    }

}

/**
 * Group member item
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiGroupMember extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
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
