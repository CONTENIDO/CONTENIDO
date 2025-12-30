<?php

/**
 * This file contains the online user collection and item class.
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
 * Online user collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @method cApiOnlineUser createNewItem($data)
 * @method cApiOnlineUser|bool next
 */
class cApiOnlineUserCollection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param string|false $select [optional] Where clause to use for selection {@see ItemCollection::select()}
     * @throws cDbException|cInvalidArgumentException
     */
    public function __construct($select = false)
    {
        parent::__construct(cRegistry::getDbTableName('online_user'), 'user_id');
        $this->_setItemClass('cApiOnlineUser');
        if ($select !== false) {
            $this->select($select);
        }
    }

    /**
     * Start the User Tracking:
     * 1) First delete all inactive users with time-limit is off
     * 2) If you find user in the table, do update
     * 3) Else there is no current user do insert new user
     *
     * @param string $userId [optional] Id of user
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function startUsersTracking($userId = NULL)
    {
        $userId = cSecurity::toString($userId);

        if (empty($userId)) {
            $auth = cRegistry::getAuth();
            $userId = $auth->auth['uid'];
        }

        // Delete all entries being older than defined timeout
        $this->deleteInactiveUser();

        $bResult = $this->findUser($userId);
        if ($bResult) {
            // Update the current user
            $this->updateUser($userId);
        } else {
            // User not found, we can insert the new user
            $this->insertOnlineUser($userId);
        }
    }

    /**
     * Insert this user in online_user table
     *
     * @param string $userId Id of user
     * @return bool Returns true if successful else false
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function insertOnlineUser($userId): bool
    {
        $oItem = $this->createNewItem(cSecurity::toString($userId));
        if ($oItem) {
            $created = date('Y-m-d H:i:s');
            $oItem->set('lastaccessed', $created);
            $oItem->store();
        }
        return (bool)$oItem;
    }

    /**
     * Find the user in the table 'online_user'
     *
     * @param string $userId Is the User-Id (get from auth object)
     * @return bool Returns true if this User is found, else false
     * @throws cDbException|cException
     */
    public function findUser($userId): bool
    {
        $oUser = new cApiOnlineUser(cSecurity::toString($userId));
        return $oUser->isLoaded();
    }

    /**
     * Find all user_ids in the table 'online_user' for get rest information from table 'con_user'
     *
     * @return array Returns array of user-information
     * @throws cDbException|cException
     */
    public function findAllUser(): array
    {
        // todo use $perm
        $aAllUser = [];
        $aUser = [];
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
            $aPerms = $oItem->getPermsArray();

            if (in_array('sysadmin', $aPerms)) {
                $aAllUser[$userId]['perms'] = 'Systemadministrator';
            } else {
                $bIsAdmin = false;
                $iCounter = 0;
                foreach ($aPerms as $sPerm) {
                    $aResults = [];
                    if (preg_match('/^admin\[(\d+)\]$/', $sPerm, $aResults)) {
                        $iClientId = $aResults[1];
                        $bIsAdmin = true;
                        $sClientName = $oClientColl->getClientname((int)$iClientId);
                        if ($iCounter == 0 && $sClientName != '') {
                            $sClientNames .= $sClientName;
                        } elseif ($sClientName != '') {
                            $sClientNames .= ', ' . $sClientName;
                        }

                        $aAllUser[$userId]['perms'] = 'Administrator (' . $sClientNames . ')';
                        $iCounter++;
                    } elseif (preg_match('/^client\[(\d+)\]$/', $sPerm, $aResults) && !$bIsAdmin) {
                        $iClientId = $aResults[1];
                        $sClientName = $oClientColl->getClientname((int)$iClientId);
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
     * @param string $userId Is the User-Id (get from auth object)
     * @return bool Returns true if successful, else false
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function updateUser($userId): bool
    {
        $oUser = new cApiOnlineUser(cSecurity::toString($userId));
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
     * @return bool Returns true if successful else false
     * @throws cDbException|cInvalidArgumentException
     */
    public function deleteInactiveUser(): bool
    {
        $cfg = cRegistry::getConfig();
        include_once($cfg['path']['contenido_config'] . 'config.misc.php');
        $iSetTimeOut = cSecurity::toInteger($cfg['backend']['timeout']);
        if ($iSetTimeOut <= 0) {
            $iSetTimeOut = 10;
        }

        // NOTE: We could delete outdated entries with one query, but deleting
        // one by one gives us the possibility to hook (CEC) into each deleted entry.
        $where = "DATE_SUB(NOW(), INTERVAL '$iSetTimeOut' Minute) >= `lastaccessed`";

        return $this->deleteByWhereClause($where) > 0;
    }

    /**
     * Get the number of users from the table 'online_user'
     *
     * @return int Returns if exists a number of users
     * @throws cDbException
     */
    public function getNumberOfUsers(): int
    {
        $sql = 'SELECT COUNT(*) AS cnt FROM `%s`';
        $result = $this->db->query($sql, $this->table);
        $this->_lastSQL = $sql;
        if ($result) {
            $this->db->nextRecord();
            return (int)$this->db->f('cnt');
        }
        return 0;
    }

    /**
     * Delete this user from 'online user' table
     *
     * @param string $userId Is the User-Id (get from auth object)
     * @return bool Returns true if successful, else false
     * @throws cDbException|cInvalidArgumentException
     */
    public function deleteUser($userId): bool
    {
        return $this->delete(cSecurity::toString($userId));
    }
}

/**
 * Online user item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiOnlineUser extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id Specifies the ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cRegistry::getDbTableName('online_user'), 'user_id');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }
}
