<?php

/**
 * This file contains the permission class.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Boris Erdmann
 * @author     Kristian Koehntopp
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class handles the permission management
 *
 * @package    Core
 * @subpackage Backend
 */
class cPermission
{
    public const NO_RIGHT = 'noright';

    /**
     * @var string Permission class name
     */
    public $classname = 'cPermission';

    /**
     * @var array Area cache
     */
    public $areacache = [];

    /**
     * @var array Actions cache
     */
    public $actioncache = [];

    /**
     * @var cDb Database instance
     */
    public $db;

    /**
     * Returns all groups of a user
     *
     * @param string $userId
     * @return string[] List of group ids
     * @throws cDbException|cException
     */
    public function getGroupsForUser($userId): array
    {
        $oGroupMemberColl = new cApiGroupMemberCollection();
        $result = $oGroupMemberColl->getFieldsWhere(['group_id'], 'user_id', $userId);
        return array_map(function ($item) {
            return $item['group_id'];
        }, $result);
    }

    /**
     * @deprecated [2015-05-21] This method is no longer supported (no replacement)
     */
    public function getIdForArea($area)
    {
        if (is_numeric($area)) {
            return $area;
        } elseif (isset($this->areacache[$area])) {
            return $this->areacache[$area];
        }

        $areaColl = new cApiAreaCollection();
        $areaColl->select("name = '" . $areaColl->escape($area) . "'");
        if ($oItem = $areaColl->next()) {
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
     * @throws cDbException|cException
     */
    public function getIdForAction($action): int
    {
        if (is_numeric($action)) {
            return cSecurity::toInteger($action);
        }

        if (!isset($this->actioncache[$action])) {
            $actionColl = new cApiActionCollection();
            $ids = $actionColl->getIdsWhere('name', $action);
            $this->actioncache[$action] = !empty($ids) ? cSecurity::toInteger($ids[0]) : 0;
        }

        return $this->actioncache[$action] ?? 0;
    }

    /**
     * Loads all permissions of groups where current logged-in user is a member and saves them in session.
     *
     * @param bool $force Flag to force loading, event if they were cached before
     * @return string Returns different values, depending on state:
     *         '1' if permissions couldn't be loaded
     *         '2' if permissions was already loaded before
     *         '3' if permissions were loaded successfully
     * @throws cDbException|cException
     * @since CONTENIDO 4.10.2
     */
    public function loadPermissions(bool $force = false): string
    {
        global $area_rights, $item_rights, $changelang, $changeclient;

        $auth = cRegistry::getAuth();
        $sess = cRegistry::getSession();

        $return = '1';

        // if not admin or sysadmin
        if (!$this->hasPermission()) {
            $return = isset($area_rights) ? '2' : '1';

            if (!isset($area_rights) || !isset($item_rights) || isset($changeclient) || isset($changelang) || $force) {
                $return = '3';
                // register variables
                $sess->register('area_rights');
                $sess->register('item_rights');
                $item_rights = [];
                $groups = $this->getGroupsForUser($auth->getUserId());
                foreach ($groups as $group) {
                    $this->loadPermissionsForUser($group);
                }

                $this->loadPermissionsForUser($auth->getUserId());
            }
        }

        return $return;
    }

    /**
     * @see cPermission::loadPermissions()
     * @throws cDbException|cException
     * @todo Mark this as deprecated!
     */
    public function load_permissions(bool $force = false): string
    {
        return $this->loadPermissions($force);
    }

    /**
     * Loads all permissions for a specific user or group.
     * Stores area rights in global variable $area_rights.
     * Stores item rights in global variable $item_rights.
     *
     * @param string $userId User Id hash
     * @throws cDbException|cException
     * @since CONTENIDO 4.10.2
     */
    public function loadPermissionsForUser(string $userId)
    {
        global $area_rights, $item_rights;

        $clientId = cRegistry::getClientId();
        $languageId = cRegistry::getLanguageId();

        $rightColl = new cApiRightCollection();
        $where = $rightColl->prepare(
            "`user_id` = '%s' AND `idcat` = 0 AND `idclient` = %d AND `idlang` = %d",
            $userId,
            $clientId,
            $languageId
        );
        $rightColl->select($where);

        // define $area_rights if not already done so
        if (!is_array($area_rights)) {
            $area_rights = [];
        }
        while ($oItem = $rightColl->next()) {
            $idarea = $oItem->get('idarea');
            $idaction = $oItem->get('idaction');
            $area_rights[$idarea][$idaction] = true;
        }

        // Select Rights for Article and structure (Attention Hard code Areas)
        $areaColl = new cApiAreaCollection();
        $allAreaIds = $areaColl->getAllIds();
        asort($allAreaIds);

        $tmp_area_string = implode("','", array_values($allAreaIds));
        $where = $rightColl->prepare(
            "`user_id` = '%s' AND `idclient` = %d AND `idlang` = %d AND `idarea` IN ('$tmp_area_string') AND `idcat` != 0",
            $userId,
            $clientId,
            $languageId
        );
        $rightColl->select($where);
        while ($oItem = $rightColl->next()) {
            $idarea = $oItem->get('idarea');
            $idaction = $oItem->get('idaction');
            $idcat = $oItem->get('idcat');
            $item_rights[$idarea][$idaction][$idcat] = $idcat;
        }
    }

    /**
     * @see cPermission::loadPermissionsForUser()
     * @throws cDbException|cException
     * @todo Mark this as deprecated!
     */
    public function load_permissions_for_user($userId)
    {
        $this->loadPermissionsForUser(cSecurity::toString($userId));
    }

    /**
     * Checks if the user has the permission for any item in an area action.
     *
     * @param int|string $action [optional]
     * @since CONTENIDO 4.10.2
     */
    public function hasAreaActionAnyItemPermission(string $area, $action = 0): bool
    {
        global $item_rights;

        try {
            if ($this->hasAreaActionPermission($area, $action)) {
                return true;
            }

            $areaColl = new cApiAreaCollection();
            $area = $areaColl->getAreaId($area);

            $action = $this->getIdForAction($action);

            return isset($item_rights[$area][$action]);
        } catch (cDbException|cException $e) {
            cLogError('Could not check for permission. Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @see cPermission::hasAreaActionPermission()
     * @todo Mark this as deprecated!
     */
    public function have_perm_area_action_anyitem($area, $action = 0): bool
    {
        return $this->hasAreaActionAnyItemPermission(cSecurity::toString($area), $action);
    }

    /**
     * Checks if the user has the permission for a specific item in an area action.
     *
     * @param int|string $action
     * @param mixed $itemId
     * @since CONTENIDO 4.10.2
     */
    public function hasAreaActionItemPermission(string $area, $action, $itemId): bool
    {
        global $item_rights;

        try {
            if ($this->hasPermission()) {
                return true;
            }

            $areaColl = new cApiAreaCollection();
            $area = $areaColl->getAreaId($area);
            $action = $this->getIdForAction($action);

            // If the user has a right on this action in this area check for the items
            if ($this->hasAreaActionPermission($area, $action)) {
                return true;
            }

            // Check rights for the action in this area at this item
            if (isset($item_rights[$area][$action][$itemId])) {
                // If we have action for area + action +item check right for client and lang
                return true;
            }

            $auth = cRegistry::getAuth();
            $clientId = cRegistry::getClientId();
            $languageId = cRegistry::getLanguageId();

            $item_rights[$area] = $item_rights[$area] ?? '';
            if ($item_rights[$area] != self::NO_RIGHT) {
                $groupsForUser = $this->getGroupsForUser($auth->getUserId());
                $groupsForUser[] = $auth->getUserId();

                $userIdIn = implode("','", $groupsForUser);

                $oRightsColl = new cApiRightCollection();
                $where = "`user_id` IN ('" . $userIdIn . "') AND `idclient` = %d AND `idlang` = %d AND `idarea` = %d AND `idcat` != 0";
                $where = $oRightsColl->prepare($where, $clientId, $languageId, $area);
                if (!$oRightsColl->select($where)) {
                    $item_rights[$area] = self::NO_RIGHT;
                    return false;
                }

                while ($oItem = $oRightsColl->next()) {
                    $_catId = $oItem->get('idcat');
                    $item_rights[$oItem->get('idarea')][$oItem->get('idaction')][$_catId] = $_catId;
                }

                // Check
                if (isset($item_rights[$area][$action][$itemId])) {
                    // If we have action for area + action +item check right for client and lang
                    return true;
                }
            }
            return false;
        } catch (cDbException|cException $e) {
            cLogError('Could not check for permission. Error: ' . $e->getMessage());
            return false;
        }
    }


    /**
     * @see cPermission::hasAreaActionItemPermission()
     * @todo Mark this as deprecated!
     */
    public function have_perm_area_action_item($area, $action, $itemId): bool
    {
        return $this->hasAreaActionItemPermission(cSecurity::toString($area), $action, $itemId);
    }

    /**
     * @deprecated [2015-05-21] This method is no longer supported (no replacement)
     */
    public function getParentAreaId($area)
    {
        return (new cApiAreaCollection())->getParentAreaId($area);
    }

    /**
     * Checks if the user has the permission for an area action.
     *
     * @param int|string $action [optional]
     * @since CONTENIDO 4.10.2
     */
    public function hasAreaActionPermission(string $area, $action = 0): bool
    {
        global $area_rights;

        $clientId = cRegistry::getClientId();
        $languageId = cRegistry::getLanguageId();

        try {
            $areaColl = new cApiAreaCollection();
            $area = $areaColl->getAreaId($area);
            $action = $this->getIdForAction($action);

            if ($action == 0) {
                $area = $areaColl->getParentAreaId($area);
            }

            $area = $areaColl->getAreaId($area);

            if (!$this->hasPermission()) {
                if ($action == 0 && isset($area_rights[$area])) {
                    // If we have action for area + action check right for client and lang
                    return $this->have_perm_client_lang($clientId, $languageId);
                }

                // check rights for the action in this area
                if (isset($area_rights[$area][$action])) {
                    // If we have action for area + action check right for client and lang
                    return $this->have_perm_client_lang($clientId, $languageId);
                }

                return false;
            }

            return true;
        } catch (cDbException|cException $e) {
            cLogError('Could not check for permission. Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @see cPermission::hasAreaActionPermission()
     * @todo Mark this as deprecated!
     */
    public function have_perm_area_action($area, $action = 0): bool
    {
        return $this->hasAreaActionPermission(cSecurity::toString($area), $action);
    }

    /**
     * Checks if the user has the permission for an area action or for a specific item in an area action.
     *
     * @param int|string $action
     * @param mixed $itemId
     * @since CONTENIDO 4.10.2
     */
    public function hasAreaActionOrItemPermission(string $area, $action, $itemId): bool
    {
        return $this->hasAreaActionPermission($area, $action)
            || $this->hasAreaActionItemPermission($area, $action, $itemId);
    }

    /**
     * Checks if the user has the permission for a client and its language.
     *
     * @since CONTENIDO 4.10.2
     */
    public function hasClientAndLanguagePermission(int $clientId, int $languageId): bool
    {
        $auth = cRegistry::getAuth();

        if (self::checkSysadminPermission($auth->getPerms())) {
            // User is sysadmin
            return true;
        } elseif (self::checkClientAdminPermission($clientId, $auth->getPerms())) {
            // User is client admin
            return true;
        } else {
            // Check rights for the client and the language
            return self::checkClientAndLanguagePermission($clientId, $languageId, $auth->getPerms());
        }
    }

    /**
     * @see cPermission::hasClientAndLanguagePermission()
     * @todo Mark this as deprecated!
     */
    public function have_perm_client_lang($clientId, $languageId): bool
    {
        return $this->hasClientAndLanguagePermission(
            cSecurity::toInteger($clientId),
            cSecurity::toInteger($languageId)
        );
    }

    /**
     * Checks if a user has access rights for a specific client.
     *
     * @param int|bool $clientId Id of client to check, or false for the current client
     * @param cApiUser|bool $oUser User object to check against, or false for the current user
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function hasClientPermission($clientId = false, $oUser = false): bool
    {
        if ($clientId === false) {
            $clientId = cRegistry::getClientId();
        }

        $oUser = $this->_checkUserObject($oUser);

        if ($this->isSysadmin($oUser)) {
            return true;
        } elseif ($this->isClientAdmin($clientId, $oUser)) {
            return true;
        } elseif ($this->isClientUser($clientId, $oUser)) {
            return true;
        } else {
            return false;
        }

        // Commented out by Timo Trautmann, because here only client access is checked,
        // possibility for admin or sysadmin access was ignored.
        // functions isSysadmin isClientAdmin isClientUser also handles permission for groups

        // Check clients' rights of users' group(s)
        // global $auth;
        // $aGroups = $this->getGroupsForUser($auth->getUserId());
        // if (is_array($aGroups)) {
        //     foreach ($aGroups as $group) {
        //         $oGroup = new cApiGroup($group);
        //         if ($this->isClientGroup($clientId, $oGroup)) {
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
     * @param int $clientId Id of client to check
     * @param cApiUser|bool $oUser User object to check against, or false for the current user
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function isClientUser($clientId, $oUser = false): bool
    {
        $oUser = $this->_checkUserObject($oUser);
        return self::checkClientPermission($clientId, $oUser->getEffectiveUserPerms());
    }

    /**
     * Checks if the given group has access permission for a client
     *
     * @param int $clientId Id of client to check
     * @param cApiGroup $oGroup Group object to check against
     */
    public function isClientGroup($clientId, $oGroup): bool
    {
        return self::checkClientPermission($clientId, $oGroup->getField('perms'));
    }

    /**
     * Checks if the given user has an admin permission for a specific client.
     *
     * @param int $clientId Id of client to check
     * @param cApiUser|bool $oUser User object to check against, or false for the current user
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function isClientAdmin($clientId, $oUser = false): bool
    {
        $clientId = cSecurity::toInteger($clientId);
        $oUser = $this->_checkUserObject($oUser);
        return self::checkClientAdminPermission($clientId, $oUser->getEffectiveUserPerms());
    }

    /**
     * Checks if the given user has an admin permission
     *
     * @param cApiUser|bool $oUser User object to check against, or false for the current user
     * @param bool $strict Flag to run a strict check.
     *         If true, then the check is only for admin value.
     *         If false, then the check is only for admin or sysadmin value.
     * @throws cDbException|cException|cInvalidArgumentException
     * @since CONTENIDO 4.10.2
     */
    public function isAdmin($oUser = false, bool $strict = false): bool
    {
        $oUser = $this->_checkUserObject($oUser);
        return self::checkAdminPermission($oUser->getEffectiveUserPerms(), $strict);
    }

    /**
     * Checks if the given user has sysadmin permission
     *
     * @param cApiUser|bool $oUser User object to check against, or false for the current user
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function isSysadmin($oUser = false): bool
    {
        $oUser = $this->_checkUserObject($oUser);
        return self::checkSysadminPermission($oUser->getEffectiveUserPerms());
    }

    /**
     * Checks if the given object is a user object.
     *
     * If oUser is false, initialize the object from the currently logged-in user.
     * If oUser is not an object of the class cApiUser, throw an exception.
     *
     * @param cApiUser|bool $oUser User object to check against, or false for the current user
     * @throws cDbException|cException|cInvalidArgumentException
     */
    private function _checkUserObject($oUser): cApiUser
    {
        if ($oUser === false) {
            global $currentuser;
            $oUser = $currentuser;
        }

        if (!is_object($oUser)) {
            $oUser = new cApiUser(cRegistry::getAuth()->getUserId());
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
     */
    public function have_perm_client($perm = 'x'): bool
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
     * Checks if the user has the permissions passed in the perm parameter.
     * - Sysadmin has always permission
     * - Client admin has always permission
     *
     * @param string $permission [optional] Permissions (comma separated list of permission) to check.
     * @since CONTENIDO 4.10.2
     */
    public function hasPermission(string $permission = 'x'): bool
    {
        $auth = cRegistry::getAuth();
        $clientId = cRegistry::getClientId();

        // If user is sysadmin or admin of current client return true
        if (self::checkSysadminPermission($auth->getPerms())) {
            return true;
        } elseif (self::checkClientAdminPermission($clientId, $auth->getPerms())) {
            return true;
        }

        // If there are more permissions to ask, check them too
        return self::checkPermission($auth->getPerms(), $permission);
    }

    /**
     * @see cPermission::hasPermission()
     * @todo Mark this as deprecated!
     */
    public function have_perm($perm = 'x'): bool
    {
        return $this->hasPermission(cSecurity::toString($perm));
    }

    /**
     * Checks if the user has any permissions on an item.
     *
     * @param string|int $mainArea
     * @param mixed $itemId
     * @since CONTENIDO 4.10.2
     */
    public function hasItemPermission($mainArea, $itemId): bool
    {
        global $item_rights, $area_tree;

        try {
            $areaColl = new cApiAreaCollection();
            $mainArea = $areaColl->getAreaId($mainArea);

            // If is admin or sysadmin
            if ($this->hasPermission()) {
                return true;
            }

            // If is not admin or sysadmin

            $auth = cRegistry::getAuth();
            $clientId = cRegistry::getClientId();
            $languageId = cRegistry::getLanguageId();

            if (!is_object($this->db)) {
                $this->db = cRegistry::getDb();
            }

            $this->showAreas($mainArea);

            $hasPermission = false;
            // Check if there are any rights for this areas
            foreach ($area_tree[$mainArea] as $value) {
                $item_rights[$value] = $item_rights[$value] ?? '';

                // If the flag noright is set there are no rights in this area
                if ($item_rights[$value] === self::NO_RIGHT) {
                    continue;
                }

                if (is_array($item_rights[$value])) {
                    // If there are any rights
                    foreach ($item_rights[$value] as $value2) {
                        if (in_array($itemId, $value2)) {
                            return true;
                        }
                    }
                } elseif ($item_rights[$value] != self::NO_RIGHT) {
                    $groupsForUser = $this->getGroupsForUser($auth->getUserId());
                    $groupsForUser[] = $auth->getUserId();
                    $userIdIn = implode("','", $groupsForUser);

                    // else search for rights for this user in this area
                    $sql = "SELECT * FROM `%s` WHERE `user_id` IN ('" . $userIdIn . "') "
                        . "AND `idclient` = %d AND `idlang` = %d AND `idarea` = %d AND `idcat` != 0";
                    $this->db->query($sql, cDb::getTableName('rights'), $clientId, $languageId, $value);

                    // If there are no rights for this area set the flag noright
                    if ($this->db->affectedRows() == 0) {
                        $item_rights[$value] = self::NO_RIGHT;
                    }

                    // Set the rights
                    while ($this->db->nextRecord()) {
                        $rs = $this->db->toObject();
                        if ($rs->idcat == $itemId) {
                            $hasPermission = true;
                        }
                        $item_rights[$rs->idarea][$rs->idaction][$rs->idcat] = $rs->idcat;
                    }
                }
            }

            return $hasPermission;
        } catch (cDbException|cException $e) {
            cLogError('Could not check for permission. Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @see cPermission::hasItemPermission()
     * @todo Mark this as deprecated!
     */
    public function have_perm_item($mainArea, $itemId): bool
    {
        return $this->hasItemPermission($mainArea, $itemId);
    }

    /**
     * Loads all areas related to passed main area into the global $area_tree variable.
     *
     * @param string|int $mainArea
     * @throws cDbException|cException
     */
    public function showAreas($mainArea): int
    {
        global $area_tree;

        $sess = cRegistry::getSession();

        $areaColl = new cApiAreaCollection();
        $mainArea = $areaColl->getAreaId($mainArea);

        // If $area_tree for this area is not register
        if (!isset($area_tree[$mainArea])) {
            $sess->register('area_tree');

            // parent_id uses the name not the idarea
            $name = $areaColl->getNameByAreaId(cSecurity::toInteger($mainArea));

            // Check which subareas are there and write them in the array
            $area_tree[$mainArea] = $areaColl->getAreaIdsByParentIdOrAreaId(
                $name, cSecurity::toInteger($mainArea)
            );
        }
        return $mainArea;
    }

    /**
     * Splits passed permission string and returns it as an array. If the provided permission is
     * already an array, then it will be returned without any further ado.
     *
     * @param string|string[] $permission Comma separated permission string or list of permissions.
     * @return string[]
     * @since CONTENIDO 4.10.2
     */
    public static function permissionToArray($permission): array
    {
        $array = is_array($permission) ? $permission
            : (is_string($permission) && !empty($permission) ? explode(',', $permission) : []);

        return array_filter(array_unique($array), function ($value) {
            return strval($value) === '0' || !empty($value);
        });
    }

    /**
     * Joins passed permission array to a string by using a comma as separator.
     *
     * @since CONTENIDO 4.10.2
     */
    public static function permissionToString(array $permission): string
    {
        return implode(',', array_filter(array_unique($permission), function ($value) {
            return strval($value) === '0' || !empty($value);
        }));
    }

    /**
     * Checks for language permissions.
     *
     * @param string|string[] $permission Comma separated permission string or list of permissions.
     * @since CONTENIDO 4.10.2
     */
    public static function checkLanguagePermission(int $languageId, $permission): bool
    {
        $permissions = self::permissionToArray($permission);
        return in_array("lang[$languageId]", $permissions);
    }

    /**
     * Checks for client permissions.
     *
     * @param string|string[] $permission Comma separated permission string or list of permissions.
     * @since CONTENIDO 4.10.2
     */
    public static function checkClientPermission(int $clientId, $permission): bool
    {
        $permissions = self::permissionToArray($permission);
        return in_array("client[$clientId]", $permissions);
    }

    /**
     * Checks for client and language permissions.
     *
     * @param string|string[] $permission Comma separated permission string or list of permissions.
     * @since CONTENIDO 4.10.2
     */
    public static function checkClientAndLanguagePermission(int $clientId, int $languageId, $permission): bool
    {
        return self::checkClientPermission($clientId, $permission)
            && self::checkLanguagePermission($languageId, $permission);
    }

    /**
     * Checks for client admin permissions.
     *
     * @param string|string[] $permission Comma separated permission string or list of permissions.
     * @since CONTENIDO 4.10.2
     */
    public static function checkClientAdminPermission(int $clientId, $permission): bool
    {
        $permissions = self::permissionToArray($permission);
        return in_array("admin[$clientId]", $permissions);
    }

    /**
     * Checks for admin permissions.
     *
     * @param string|string[] $permission Comma separated permission string or list of permissions.
     * @param bool $strict Flag to run a strict check.
     *      If true, then the check is only for admin value.
     *      If false, then the check is only for admin or sysadmin value.
     * @since CONTENIDO 4.10.2
     */
    public static function checkAdminPermission($permission, bool $strict = false): bool
    {
        $permissions = self::permissionToArray($permission);
        $pattern = $strict ? '/^admin.*/' : '/admin.*/';
        return count(preg_grep($pattern, $permissions)) > 0;
    }

    /**
     * Checks for sysadmin permissions.
     *
     * @param string|string[] $permission Comma separated permission string or list of permissions.
     * @since CONTENIDO 4.10.2
     */
    public static function checkSysadminPermission($permission): bool
    {
        $permissions = self::permissionToArray($permission);
        return in_array('sysadmin', $permissions);
    }

    /**
     * Check if permissions (needle) are all available in other permissions (haystack).
     *
     * @param string|string[] $haystackPerm The permissions to search in.
     *      Comma separated permission string or list of permissions.
     * @param string|string[] $needlePerm The permissions to search for, all of them must be found in haystackPerm.
     *      Comma separated permission string or list of permissions.
     * @since CONTENIDO 4.10.2
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
