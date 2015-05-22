<?php

/**
 * This file contains the online user collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Online user collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiOnlineUserCollection extends ItemCollection {

    /**
     * Create a new collection of items.
     *
     * @param string $select [optional]
     *         where clause to use for selection (see ItemCollection::select())
     */
    public function __construct($select = false) {
        global $cfg;
        parent::__construct($cfg['tab']['online_user'], 'user_id');
        $this->_setItemClass('cApiOnlineUser');
        if ($select !== false) {
            $this->select($select);
        }
    }

    /**
     * Start the User Tracking:
     * 1) First delete all inactive users with timelimit is off
     * 2) If find user in the table, do update
     * 3) Else there is no current user do insert new user
     *
     * @param string $userId [optional]
     *         Id of user
     */
    public function startUsersTracking($userId = NULL) {
        global $auth;

        $userId = (string) $userId;
        if (empty($userId)) {
            $userId = $auth->auth['uid'];
        }

        // Delete all entries being older than defined timeout
        $this->deleteInactiveUser();

        $bResult = $this->findUser($userId);
        if ($bResult) {
            // Update the curent user
            $this->updateUser($userId);
        } else {
            // User not found, we can insert the new user
            $this->insertOnlineUser($userId);
        }
    }

    /**
     * Insert this user in online_user table
     *
     * @param string $userId
     *         Id of user
     * @return bool
     *         Returns true if successful else false
     */
    public function insertOnlineUser($userId) {
        $oItem = $this->createNewItem((string) $userId);
        if ($oItem) {
            $created = date('Y-m-d H:i:s');
            $oItem->set('lastaccessed', $created);
            $oItem->store();
        }
        return ($oItem) ? true : false;
    }

    /**
     * Find the this user if exists in the table 'online_user'
     *
     * @param string $userId
     *         Is the User-Id (get from auth object)
     * @return bool
     *         Returns true if this User is found, else false
     */
    public function findUser($userId) {
        $oUser = new cApiOnlineUser((string) $userId);
        return ($oUser->isLoaded());
    }

    /**
     * Find all user_ids in the table 'online_user' for get rest information
     * from table 'con_user'
     *
     * @return array
     *         Returns array of user-information
     */
    public function findAllUser() {
        // todo use $perm
        $aAllUser = array();
        $aUser = array();
        $sClientName = '';

        // get all user_ids
        $this->select();
        while (($oItem = $this->next()) !== false) {
            $aUser[] = $oItem->get('user_id');
        }

        $oClientColl = new cApiClientCollection();

        // get data of those users
        $where = "user_id IN ('" . implode("', '", $aUser) . "')";
        $oUserColl = new cApiUserCollection();
        $oUserColl->select($where);
        while (($oItem = $oUserColl->next()) !== false) {
            $sClientNames = '';
            $userId = $oItem->get('user_id');
            $aAllUser[$userId]['realname'] = $oItem->get('realname');
            $aAllUser[$userId]['username'] = $oItem->get('username');
            $aPerms = explode(',', $oItem->get('perms'));

            if (in_array('sysadmin', $aPerms)) {
                $aAllUser[$userId]['perms'] = 'Systemadministrator';
            } else {
                $bIsAdmin = false;
                $iCounter = 0;
                foreach ($aPerms as $sPerm) {
                    $aResults = array();
                    if (preg_match('/^admin\[(\d+)\]$/', $sPerm, $aResults)) {
                        $iClientId = $aResults[1];
                        $bIsAdmin = true;
                        $sClientName = $oClientColl->getClientname((int) $iClientId);
                        if ($iCounter == 0 && $sClientName != '') {
                            $sClientNames .= $sClientName;
                        } elseif ($sClientName != '') {
                            $sClientNames .= ', ' . $sClientName;
                        }

                        $aAllUser[$userId]['perms'] = 'Administrator (' . $sClientNames . ')';
                        $iCounter++;
                    } elseif (preg_match('/^client\[(\d+)\]$/', $sPerm, $aResults) && !$bIsAdmin) {
                        $iClientId = $aResults[1];
                        $sClientName = $oClientColl->getClientname((int) $iClientId);
                        if ($iCounter == 0 && $sClientName != '') {
                            $sClientNames .= $sClientName;
                        } elseif ($sClientName != '') {
                            $sClientNames .= ', ' . $sClientName;
                        }

                        $aAllUser[$userId]['perms'] = '(' . $sClientNames . ')';
                        $iCounter++;
                    }
                }
            }
        }

        return $aAllUser;
    }

    /**
     * This function do an update of current timestamp in 'online_user'
     *
     * @param string $userId
     *         Is the User-Id (get from auth object)
     * @return bool
     *         Returns true if successful, else false
     */
    public function updateUser($userId) {
        $oUser = new cApiOnlineUser((string) $userId);
        if ($oUser->isLoaded()) {
            $now = date('Y-m-d H:i:s');
            $oUser->set('lastaccessed', $now);
            return $oUser->store();
        }
        return false;
    }

    /**
     * Delete all Contains in the table 'online_user' that is older as
     * Backend timeout(currently is $cfg['backend']['timeout'] = 60)
     *
     * @return bool
     *         Returns true if successful else false
     */
    public function deleteInactiveUser() {
        global $cfg;
        include_once($cfg['path']['contenido_config'] . 'config.misc.php');
        $iSetTimeOut = (int) $cfg['backend']['timeout'];
        if ($iSetTimeOut <= 0) {
            $iSetTimeOut = 10;
        }

        // NOTE: We could delete outdated entries with one query, but deleteing
        // one by one
        // gives us the possibility to hook (CEC) into each deleted entry.
        $where = "DATE_SUB(NOW(), INTERVAL '$iSetTimeOut' Minute) >= `lastaccessed`";
        $result = $this->deleteByWhereClause($where);
        return ($result > 0) ? true : false;
    }

    /**
     * Get the number of users from the table 'online_user'
     *
     * @return int
     *         Returns if exists a number of users
     */
    public function getNumberOfUsers() {
        $sql = 'SELECT COUNT(*) AS cnt FROM `%s`';
        $result = $this->db->query($sql, $this->table);
        $this->_lastSQL = $sql;
        if ($result) {
            $this->db->nextRecord();
            return (int) $this->db->f('cnt');
        }
        return 0;
    }

    /**
     * Delete this user from 'online user' table
     *
     * @param string $userId
     *         Is the User-Id (get from auth object)
     * @return bool
     *         Returns true if successful, else false
     */
    public function deleteUser($userId) {
        return $this->delete((string) $userId);
    }
}

/**
 * Online user item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiOnlineUser extends Item {

    /**
     * Constructor function
     *
     * @param mixed $mId [optional]
     *         Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['online_user'], 'user_id');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}
