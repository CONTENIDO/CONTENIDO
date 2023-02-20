<?php
/**
 * This file contains the permission class.
 *
 * @package Core
 * @subpackage Backend
 * @author Boris Erdmann
 * @author Kristian Koehntopp
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class handles the permission management
 *
 * @package Core
 * @subpackage Backend
 */
class cPermission
{

    /**
     * Permission class name
     *
     * @var string
     */
    public $classname = 'cPermission';

    /**
     * Area cache
     *
     * @var array
     */
    public $areacache = [];

    /**
     * Actions cache
     *
     * @var array
     */
    public $actioncache = [];

    /**
     * CONTENIDO database instance
     *
     * @var cDb
     */
    public $db;

    /**
     * Returns all groups of a user
     *
     * @param string $userId
     *
     * @return string[]
     *         List of group ids
     *
     * @throws cDbException
     * @throws cException
     */
    public function getGroupsForUser($userId)
    {
        $oGroupMemberColl = new cApiGroupMemberCollection();
        $result = $oGroupMemberColl->getFieldsWhere(['group_id'], 'user_id', $userId);
        return array_map(function($item) {
            return $item['group_id'];
        }, $result);
    }

    /**
     * Returns the id of an area.
     * If passed area is numeric, it will be returned directly.
     *
     * @deprecated [2015-05-21]
     *         This method is no longer supported (no replacement)
     * @param string|int $area
     *
     * @return int
     *
     * @throws cDbException
     * @throws cException
     */
    public function getIdForArea($area)
    {
        if (is_numeric($area)) {
            return $area;
        } elseif (isset($this->areacache[$area])) {
            return $this->areacache[$area];
        }

        $oAreaColl = new cApiAreaCollection();
        $oAreaColl->select("name='" . $oAreaColl->escape($area) . "'");
        if (false !== $oItem = $oAreaColl->next()) {
            $this->areacache[$area] = $oItem->get('idarea');
            $area = $oItem->get('idarea');
        }

        return $area;
    }

    /**
     * Returns the id of an action.
     * If passed action is numeric, it will be returned directly.
     *
     * @param string|int $action
     *
     * @return int
     *
     * @throws cDbException
     * @throws cException
     */
    public function getIdForAction($action)
    {
        if (is_numeric($action)) {
            return $action;
        } elseif (isset($this->actioncache[$action])) {
            return $this->actioncache[$action];
        }

        $oActionColl = new cApiActionCollection();
        $ids = $oActionColl->getIdsWhere('name', $action);
        return !empty($ids) ? cSecurity::toInteger($ids[0]) : 0;
    }

    /**
     * Loads all permissions of groups where current logged-in user is a member
     * and saves them in session.
     *
     * @param bool $force [optional]
     *                    Flag to force loading, event if they were cached before
     *
     * @return string
     *         Returns different values, depending on state:
     *         '1' (string) if permissions couldn't be loaded
     *         '3' (string) if permissions were loaded successfully
     *
     * @throws cDbException
     * @throws cException
     */
    public function load_permissions($force = false)
    {
        global $area_rights, $item_rights, $changelang, $changeclient;

        $auth = cRegistry::getAuth();
        $sess = cRegistry::getSession();

        $return = '1';

        // if not admin or sysadmin
        if (!$this->have_perm()) {
            $return = isset($area_rights);

            if (!isset($area_rights) || !isset($item_rights) || isset($changeclient) || isset($changelang) || $force) {
                $return = '3';
                // register variables
                $sess->register('area_rights');
                $sess->register('item_rights');
                $item_rights = [];
                $groups = $this->getGroupsForUser($auth->auth['uid']);

                if (is_array($groups)) {
                    foreach ($groups as $group) {
                        $this->load_permissions_for_user($group);
                    }
                }

                $this->load_permissions_for_user($auth->auth['uid']);
            }
        }

        return $return;
    }

    /**
     * Loads all permissions for a specific user or group.
     * Stores area rights in global variable $area_rights.
     * Stores item rights in global variable $item_rights.
     *
     * @param string $user
     *         User Id hash
     *
     * @throws cDbException
     * @throws cException
     */
    public function load_permissions_for_user($user)
    {
        global $area_rights, $item_rights;

        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();

        $oRightColl = new cApiRightCollection();
        $sWhere = "`user_id` = '%s' AND `idcat` = 0 AND `idclient` = %d AND `idlang` = %d";
        $sWhere = $oRightColl->prepare($sWhere, $user, $client, $lang);
        $oRightColl->select($sWhere);

        // define $area_rights if not already done so
        if (!is_array($area_rights)) {
            $area_rights = [];
        }
        while (false !== $oItem = $oRightColl->next()) {
            $idarea = $oItem->get('idarea');
            $idaction = $oItem->get('idaction');
            $area_rights[$idarea][$idaction] = true;
        }

        // Select Rights for Article and structure (Attention Hard code Areas)
        $oAreaColl = new cApiAreaCollection();
        $allAreaIds = $oAreaColl->getAllIds();
        asort($allAreaIds);

        $tmp_area_string = implode("','", array_values($allAreaIds));
        $sWhere = "`user_id` = '%s' AND `idclient` = %d AND `idlang` = %d AND `idarea` IN ('$tmp_area_string') AND `idcat` != 0";
        $sWhere = $oRightColl->prepare($sWhere, $user, $client, $lang);
        $oRightColl->select($sWhere);
        while (false !== $oItem = $oRightColl->next()) {
            $idarea = $oItem->get('idarea');
            $idaction = $oItem->get('idaction');
            $idcat = $oItem->get('idcat');
            $item_rights[$idarea][$idaction][$idcat] = $idcat;
        }
    }

    /**
     *
     * @param string $area
     * @param int    $action [optional]
     *
     * @return bool
     *
     * @throws cDbException
     * @throws cException
     */
    public function have_perm_area_action_anyitem($area, $action = 0)
    {
        global $item_rights;

        if ($this->have_perm_area_action($area, $action)) {
            return true;
        }

        $oAreaColl = new cApiAreaCollection();
        $area = $oAreaColl->getAreaId($area);

        $action = $this->getIdForAction($action);

        return isset($item_rights[$area][$action]);
    }

    /**
     *
     * @param string $area
     * @param string $action
     * @param mixed  $itemid
     *
     * @return bool
     *
     * @throws cDbException
     * @throws cException
     */
    public function have_perm_area_action_item($area, $action, $itemid)
    {
        global $item_rights;

        if ($this->have_perm()) {
            return true;
        }

        $oAreaColl = new cApiAreaCollection();
        $area = $oAreaColl->getAreaId($area);
        $action = $this->getIdForAction($action);

        // If the user has a right on this action in this area check for the
        // items
        if ($this->have_perm_area_action($area, $action)) {
            return true;
        }

        // Check rights for the action in this area at this item
        if (isset($item_rights[$area][$action][$itemid])) {
            // If we have action for area + action +item check right
            // for client and lang
            return true;
        }

        $auth = cRegistry::getAuth();
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();

        $item_rights[$area] = $item_rights[$area] ?? '';
        if ($item_rights[$area] != 'noright') {
            $groupsForUser = $this->getGroupsForUser($auth->auth['uid']);
            $groupsForUser[] = $auth->auth['uid'];

            $userIdIn = implode("','", $groupsForUser);

            $oRightsColl = new cApiRightCollection();
            $where = "`user_id` IN ('" . $userIdIn . "') AND `idclient` = %d AND `idlang` = %d AND `idarea` = %d AND `idcat` != 0";
            $where = $oRightsColl->prepare($where, $client, $lang, $area);
            if (!$oRightsColl->select($where)) {
                $item_rights[$area] = 'noright';
                return false;
            }

            while (false !== $oItem = $oRightsColl->next()) {
                $item_rights[$oItem->get('idarea')][$oItem->get('idaction')][$oItem->get('idcat')] = $oItem->get('idcat');
            }

            // Check
            if (isset($item_rights[$area][$action][$itemid])) {
                // If we have action for area + action +item check right
                // for client and lang
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the parent id of passed area
     *
     * @deprecated [2015-05-21]
     *         This method is no longer supported (no replacement)
     * @param int|string $area
     *         Area id or name
     *
     * @return string|int
     *         name of parent area or passed area
     *
     * @throws cDbException
     */
    public function getParentAreaId($area)
    {
        $oAreaColl = new cApiAreaCollection();
        return $oAreaColl->getParentAreaId($area);
    }

    /**
     *
     * @param string $area
     * @param int|string    $action [optional]
     *
     * @return bool
     *
     * @throws cDbException
     * @throws cException
     */
    public function have_perm_area_action($area, $action = 0)
    {
        global $area_rights;

        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();

        $oAreaColl = new cApiAreaCollection();
        $area = $oAreaColl->getAreaId($area);
        $action = $this->getIdForAction($action);

        if ($action == 0) {
            $area = $oAreaColl->getParentAreaId($area);
        }

        $area = $oAreaColl->getAreaId($area);

        if (!$this->have_perm()) {
            if ($action == 0 && isset($area_rights[$area])) {
                // If we have action for area + action check right for client and lang
                return $this->have_perm_client_lang($client, $lang);
            }

            // check rights for the action in this area
            if (isset($area_rights[$area][$action])) {
                // If we have action for area + action check right for client and lang
                return $this->have_perm_client_lang($client, $lang);
            }

            return false;
        }

        return true;
    }

    /**
     *
     * @param int $client
     * @param int $lang
     * @return bool
     */
    public function have_perm_client_lang($client, $lang)
    {
        // Changed back to a full-featured function, as have_perm needs
        // $client as global variable - not provided by this function
        // return $this->have_perm("client[$client],lang[$lang]");

        $auth = cRegistry::getAuth();
        $client = cSecurity::toInteger($client);
        $lang = cSecurity::toInteger($lang);

        if (self::checkSysadminPermission($auth->getPerms())) {
            // User is sysadmin
            return true;
        } elseif (self::checkClientAdminPermission($client, $auth->getPerms())) {
            // User is client admin
            return true;
        } else {
            // Check rights for the client and the language
            return self::checkClientAndLanguagePermission($client, $lang, $auth->getPerms());
        }
    }

    /**
     * Checks if a user has access rights for a specific client.
     *
     * @param bool $iClient [optional]
     *                      idclient to check, or false for the current client
     * @param cApiUser|bool $oUser   [optional]
     *                      User object to check against, or false for the current user
     *
     * @return bool
     *
     * @throws cInvalidArgumentException
     */
    public function hasClientPermission($iClient = false, $oUser = false)
    {
        if ($iClient === false) {
            $iClient = cRegistry::getClientId();
        }

        $oUser = $this->_checkUserObject($oUser);

        if ($this->isSysadmin($oUser)) {
            return true;
        } elseif ($this->isClientAdmin($iClient, $oUser)) {
            return true;
        } elseif ($this->isClientUser($iClient, $oUser)) {
            return true;
        } else {
            return false;
        }

        // Commented out by Timo Trautmann, because here only client access is checked,
        // possibility for admin or sysadmin access was ignored.
        // functions isSysadmin isClientAdmin isClientUser also handles permission for groups

        // Check clients' rights of users' group(s)
        // global $auth;
        // $aGroups = $this->getGroupsForUser($auth->auth["uid"]);
        // if (is_array($aGroups)) {
        //     foreach ($aGroups as $group) {
        //         $oGroup = new cApiGroup($group);
        //         if ($this->isClientGroup($iClient, $oGroup)) {
        //             return true;
        //         }
        //     }
        // }
        //
        // return false;
    }

    /**
     * Checks if the given user has access permission for a client
     *
     * @param int    $iClient
     *         idclient to check
     * @param cApiUser|bool $oUser
     *         User object to check against, or false for the current user
     *
     * @return bool
     *
     * @throws cInvalidArgumentException
     */
    public function isClientUser($iClient, $oUser = false)
    {
        $oUser = $this->_checkUserObject($oUser);
        return self::checkClientPermission($iClient, $oUser->getEffectiveUserPerms());
    }

    /**
     * Checks if the given group has access permission for a client
     *
     * @param int $iClient
     *         idclient to check
     * @param cApiGroup $oGroup
     *         Group object to check against
     * @return bool
     */
    public function isClientGroup($iClient, $oGroup)
    {
        return self::checkClientPermission($iClient, $oGroup->getField('perms'));
    }

    /**
     * Checks if the given user has an admin permission for a specific client.
     *
     * @param int    $iClient
     *         idclient to check
     * @param cApiUser|bool $oUser
     *         User object to check against, or false for the current user
     *
     * @return bool
     *
     * @throws cInvalidArgumentException
     */
    public function isClientAdmin($iClient, $oUser = false)
    {
        $iClient = cSecurity::toInteger($iClient);
        $oUser = $this->_checkUserObject($oUser);
        return self::checkClientAdminPermission($iClient, $oUser->getEffectiveUserPerms());
    }

    /**
     * Checks if the given user has an admin permission
     *
     * @since CONTENIDO 4.10.2
     * @param cApiUser|bool $oUser
     *         User object to check against, or false for the current user
     * @param bool $strict
     *         Flag to run a strict check.
     *         If true, then the check is only for admin value.
     *         If false, then the check is only for admin or sysadmin value.
     * @return bool
     *
     * @throws cInvalidArgumentException
     */
    public function isAdmin($oUser = false, bool $strict = false): bool
    {
        $oUser = $this->_checkUserObject($oUser);
        return self::checkAdminPermission($oUser->getEffectiveUserPerms(), $strict);
    }

    /**
     * Checks if the given user has sysadmin permission
     *
     * @param cApiUser|bool $oUser
     *         User object to check against, or false for the current user
     *
     * @return bool
     *
     * @throws cInvalidArgumentException
     */
    public function isSysadmin($oUser = false)
    {
        $oUser = $this->_checkUserObject($oUser);
        return self::checkSysadminPermission($oUser->getEffectiveUserPerms());
    }

    /**
     * Checks if the given object is a user object.
     *
     * If oUser is false, initialize the object from the currently logged-in
     * user. If oUser is not an object of the class cApiUser, throw an
     * exception.
     *
     * @param cApiUser|bool $oUser
     *         User object to check against, or false for the current user
     *
     * @return cApiUser
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException if the given or constructed user is not a cApiUser object
     */
    private function _checkUserObject($oUser)
    {
        if ($oUser === false) {
            global $currentuser;
            $oUser = $currentuser;
        }

        if (!is_object($oUser)) {
            global $auth;
            $oUser = new cApiUser($auth->auth['uid']);
        }

        if (!$oUser instanceof cApiUser) {
            throw new cInvalidArgumentException('oUser parameter is not of type User');
        }

        return $oUser;
    }

    /**
     * Does a client check but also any other check depending on passed $perm value.
     *
     * TODO The function name suggest a check for client permission but if you call this with $perm values other
     *      than client related, then the check has nothing to do with a client check. Rename function or find
     *      another solution for this.
     *
     * @param string $perm [optional]
     *
     * @return bool
     */
    public function have_perm_client($perm = 'x')
    {
        $auth = cRegistry::getAuth();

        // If User is sysadmin or admin at this client return true
        if (self::checkSysadminPermission($auth->getPerms())) {
            return true;
        }

        // If there are more permissions to ask, check them too
        return self::checkPermission($auth->getPerms(), $perm);
    }

    /**
     * Checks if user has permissions to passed perm.
     * - Sysadmin has always permission
     * - Client admin has always permission
     *
     * @param string $perm [optional]
     *         Permissions (comma separated list of perms) to check
     * @return bool
     */
    public function have_perm($perm = 'x')
    {
        $auth = cRegistry::getAuth();
        $client = cSecurity::toInteger(cRegistry::getClientId());

        // If user is sysadmin or admin of current client return true
        if (self::checkSysadminPermission($auth->getPerms())) {
            return true;
        } elseif (self::checkClientAdminPermission($client, $auth->getPerms())) {
            return true;
        }

        // If there are more permissions to ask, check them too
        return self::checkPermission($auth->getPerms(), $perm);
    }

    /**
     * Checks if an item have any perms
     *
     * @param string|int $mainArea
     * @param int        $itemid
     *
     * @return bool
     *
     * @throws cDbException
     * @throws cException
     */
    public function have_perm_item($mainArea, $itemid)
    {
        global $item_rights, $area_tree;

        $oAreaColl = new cApiAreaCollection();
        $mainArea = $oAreaColl->getAreaId($mainArea);

        // If is admin or sysadmin
        if ($this->have_perm()) {
            return true;
        }

        // If is not admin or sysadmin

        $auth = cRegistry::getAuth();
        $client = cRegistry::getClientId();
        $lang = cRegistry::getLanguageId();
        $cfg = cRegistry::getConfig();


        if (!is_object($this->db)) {
            $this->db = cRegistry::getDb();
        }

        $this->showareas($mainArea);

        $flg = false;
        // Check if there are any rights for this areas
        foreach ($area_tree[$mainArea] as $value) {
            $item_rights[$value] = $item_rights[$value] ?? '';
            // If the flag noright is set there are no rights in this area
            if ($item_rights[$value] == 'noright') {
                continue;
            } elseif (is_array($item_rights[$value])) {
                // If there are any rights
                foreach ($item_rights[$value] as $value2) {
                    if (in_array($itemid, $value2)) {
                        return true;
                    }
                }
            } elseif ($item_rights[$value] != 'noright') {
                $groupsForUser = $this->getGroupsForUser($auth->auth['uid']);
                $groupsForUser[] = $auth->auth['uid'];
                $userIdIn = implode("','", $groupsForUser);

                // else search for rights for this user in this area
                $sql = "SELECT * FROM `%s` WHERE `user_id` IN ('" . $userIdIn . "') "
                    . "AND `idclient` = %d AND `idlang` = %d AND `idarea` = %d AND `idcat` != 0";
                $this->db->query($sql, $cfg['tab']['rights'], $client, $lang, $value);

                // If there are no rights for this area set the flag norights
                if ($this->db->affectedRows() == 0) {
                    $item_rights[$value] = 'noright';
                }

                // Set the rights
                while ($this->db->nextRecord()) {
                    if ($this->db->f('idcat') == $itemid) {
                        $flg = true;
                    }
                    $item_rights[$this->db->f('idarea')][$this->db->f('idaction')][$this->db->f('idcat')] = $this->db->f('idcat');
                }
            }
        }
        return $flg;
    }

    /**
     * Loads all areas related to passed main area into the global $area_tree variable.
     *
     * @param string|int $mainArea
     *
     * @return int
     *
     * @throws cDbException
     * @throws cException
     */
    public function showareas($mainArea)
    {
        global $area_tree;

        $sess = cRegistry::getSession();

        $oAreaColl = new cApiAreaCollection();
        $mainArea = $oAreaColl->getAreaId($mainArea);

        // If $area_tree for this area is not register
        if (!isset($area_tree[$mainArea])) {
            $sess->register('area_tree');

            // parent_id uses the name not the idarea
            $name = $oAreaColl->getNameByAreaId(cSecurity::toInteger($mainArea));

            // Check which subareas are there and write them in the array
            $area_tree[$mainArea] = $oAreaColl->getAreaIdsByParentIdOrAreaId(
                $name, cSecurity::toInteger($mainArea)
            );
        }
        return $mainArea;
    }

    /**
     * Splits passed permission string and returns it as an array. If the passed permission is already an array,
     * then it will be returned without any further ado.
     *
     * @since CONTENIDO 4.10.2
     * @param string|string[] $permission Comma separated permission string or list of permissions.
     * @return string[]
     */
    public static function permissionToArray($permission): array
    {
        return is_array($permission) ? $permission
            : (is_string($permission) && !empty($permission) ? explode(',', $permission) : []);
    }

    /**
     * Checks for language permissions.
     *
     * @since CONTENIDO 4.10.2
     * @param int $languageId
     * @param string|string[] $permission Comma separated permission string or list of permissions.
     * @return bool
     */
    public static function checkLanguagePermission($languageId, $permission): bool
    {
        $languageId = cSecurity::toInteger($languageId);
        $permissions = self::permissionToArray($permission);
        return in_array("lang[$languageId]", $permissions);
    }

    /**
     * Checks for client permissions.
     *
     * @since CONTENIDO 4.10.2
     * @param int $clientId
     * @param string|string[] $permission Comma separated permission string or list of permissions.
     * @return bool
     */
    public static function checkClientPermission($clientId, $permission): bool
    {
        $clientId = cSecurity::toInteger($clientId);
        $permissions = self::permissionToArray($permission);
        return in_array("client[$clientId]", $permissions);
    }

    /**
     * Checks for client and language permissions.
     *
     * @since CONTENIDO 4.10.2
     * @param int $clientId
     * @param int $languageId
     * @param string|string[] $permission Comma separated permission string or list of permissions.
     * @return bool
     */
    public static function checkClientAndLanguagePermission(int $clientId, int $languageId, $permission): bool
    {
        return self::checkClientPermission($clientId, $permission)
            && self::checkLanguagePermission($languageId, $permission);
    }

    /**
     * Checks for client admin permissions.
     *
     * @since CONTENIDO 4.10.2
     * @param int $clientId
     * @param string|string[] $permission Comma separated permission string or list of permissions.
     * @return bool
     */
    public static function checkClientAdminPermission(int $clientId, $permission): bool
    {
        $permissions = self::permissionToArray($permission);
        return in_array("admin[$clientId]", $permissions);
    }

    /**
     * Checks for admin permissions.
     *
     * @since CONTENIDO 4.10.2
     * @param string|string[] $permission Comma separated permission string or list of permissions.
     * @param bool $strict Flag to run a strict check.
     *      If true, then the check is only for admin value.
     *      If false, then the check is only for admin or sysadmin value.
     *
     * @return bool
     */
    public static function checkAdminPermission($permission, bool $strict = false): bool
    {
        $permissions = self::permissionToArray($permission);
        $pattern = $strict ? '/^admin.*/' : '/admin.*/';
        return (count(preg_grep($pattern, $permissions)) > 0);
    }

    /**
     * Checks for sysadmin permissions.
     *
     * @since CONTENIDO 4.10.2
     * @param string|string[] $permission Comma separated permission string or list of permissions.
     * @return bool
     */
    public static function checkSysadminPermission($permission): bool
    {
        $permissions = self::permissionToArray($permission);
        return (in_array('sysadmin', $permissions));
    }

    /**
     * Check if permissions (needle) are all available in other permissions (haystack).
     *
     * @since CONTENIDO 4.10.2
     * @param string|string[] $haystackPerm The permissions to search in.
     *      Comma separated permission string or list of permissions.
     * @param string|string[] $needlePerm The permissions to search for, all of them must be found in haystackPerm.
     *      Comma separated permission string or list of permissions.
     * @return bool
     */
    public static function checkPermission($haystackPerm, $needlePerm): bool
    {
        $haystackPerms = self::permissionToArray($haystackPerm);
        $needlePerms = self::permissionToArray($needlePerm);

        // Get values available in both arrays, the result count should be the same as the needlePerms count
        $result = array_intersect($haystackPerms, $needlePerms);
        return count($result) === count($needlePerms);
    }

}
