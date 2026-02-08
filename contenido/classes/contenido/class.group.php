<?php

/**
 * This file contains the group collection and item class.
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
 * Group collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiGroup>
 */
class cApiGroupCollection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('groups'), 'group_id');
        $this->_setItemClass('cApiGroup');
    }

    /**
     * Creates a group entry.
     *
     * @param string $groupName
     * @param string $perms
     * @param string $description
     * @return cApiGroup|false
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($groupName, $perms, $description)
    {
        $primaryKeyValue = md5($groupName . time());

        $item = $this->createNewItem($primaryKeyValue);
        if (!is_object($item)) {
            return false;
        }

        $groupName = cApiGroup::prefixedGroupName($groupName);

        $item->set('groupname', $groupName);
        $item->set('perms', $perms);
        $item->set('description', $description);
        $item->store();

        return $item;
    }

    /**
     * Returns the groups a user is in
     *
     * @param string $userId
     * @return cApiGroup[] List of groups
     * @throws cDbException|cException
     */
    public function fetchByUserID($userId): array
    {
        $ids = [];
        $sql = "SELECT a.group_id FROM `%s` AS a, `%s` AS b WHERE (a.group_id = b.group_id) AND (b.user_id = '%s')";
        $this->db->query($sql, $this->table, cDb::getTableName('groupmembers'), $userId);
        $this->_lastSQL = $sql;
        while ($this->db->nextRecord()) {
            $ids[] = cSecurity::toInteger($this->db->f('group_id'));
        }
        if (!count($ids)) {
            return [];
        }

        $groups = [];
        $this->select(sprintf("`group_id` IN (%s)", implode(',', $ids)));
        while ($oItem = $this->next()) {
            $groups[] = clone $oItem;
        }

        return $groups;
    }

    /**
     * Removes the specified group from the database.
     *
     * @param string $groupName Specifies the group name
     * @return bool True if the deletion was successful
     * @throws cDbException|cInvalidArgumentException
     */
    public function deleteGroupByGroupname(string $groupName): bool
    {
        $groupName = cApiGroup::prefixedGroupName($groupName);

        return $this->deleteBy('groupname', $groupName) > 0;
    }

    /**
     * Returns all groups which are accessible by the current group.
     *
     * @param string[] $perms
     * @return cApiGroup[] Array of group objects
     * @throws cDbException|cException
     */
    public function fetchAccessibleGroups(array $perms): array
    {
        $groups = [];
        $limit = [];
        $where = '';

        if (!in_array('sysadmin', $perms)) {
            // not sysadmin, compose where rules
            $oClientColl = new cApiClientCollection();
            $allClients = $oClientColl->getAvailableClients();
            foreach ($allClients as $key => $value) {
                if (in_array('client[' . $key . ']', $perms) || in_array('admin[' . $key . ']', $perms)) {
                    $limit[] = "perms LIKE '%client[" . $this->escape($key) . "]%'";
                }
                if (in_array('admin[' . $key . ']', $perms)) {
                    $limit[] = "perms LIKE '%admin[" . $this->escape($key) . "]%'";
                }
            }

            if (count($limit) > 0) {
                $where = '1 AND ' . implode(' OR ', $limit);
            }
        }

        $this->select($where);
        while ($oItem = $this->next()) {
            $groups[] = clone $oItem;
        }

        return $groups;
    }

    /**
     * Returns all groups which are accessible by the current group.
     * Is a wrapper of fetchAccessibleGroups() and returns contrary to that function
     * a multidimensional array instead of a list of objects.
     *
     * @param string[] $perms
     * @return array Array of user like:
     *      <pre>
     *      $arr[user_id][groupname],
     *      $arr[user_id][description]
     *      </pre>
     *      Note: Value of $arr[user_id][groupname] is cleaned from prefix "grp_"
     * @throws cDbException|cException
     */
    public function getAccessibleGroups(array $perms): array
    {
        $groups = [];
        $oGroups = $this->fetchAccessibleGroups($perms);
        foreach ($oGroups as $oItem) {
            $groups[$oItem->get('group_id')] = [
                'groupname' => $oItem->getGroupName(true),
                'description' => $oItem->get('description') ?? '',
            ];
        }
        return $groups;
    }

    /**
     * Returns all group permissions of a user.
     * @throws cDbException|cException
     * @since CONTENIDO 4.10.2
     */
    public function getPermissionsByUserId(string $userId): array
    {
        $groupPerm = [];
        $groups = $this->fetchByUserID($userId);
        foreach ($groups as $group) {
            $groupPerm[] = $group->get('perms');
        }

        return $groupPerm;
    }

}

/**
 * Group item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiGroup extends Item
{
    use cUserGroupPermissionsTrait;

    /**
     * Prefix to be used for group names.
     *
     * @var string
     */
    public const PREFIX = 'grp_';

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('groups'), 'group_id');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * @inheritDoc
     */
    protected function _getPermissionsArray(): array
    {
        return cPermission::permissionToArray($this->get('perms'));
    }

    /**
     * Loads a group from the database by its groupId.
     *
     * @param string $groupId Specifies the groupId
     * @return bool True if the load was successful
     * @throws cDbException|cException
     */
    public function loadGroupByGroupID($groupId): bool
    {
        return $this->loadByPrimaryKey($groupId);
    }

    /**
     * Loads a group entry by its group name.
     *
     * @param string $groupName Specifies the group name
     * @return bool True if the load was successful
     * @throws cDbException|cException
     */
    public function loadGroupByGroupname(string $groupName)
    {
        return $this->loadBy('groupname', cApiGroup::prefixedGroupName($groupName));
    }

    /**
     * User defined field value setter.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        if ('perms' === $name) {
            if (is_array($value)) {
                $value = cPermission::permissionToString($value);
            }
        }

        return parent::setField($name, $value, $safe);
    }

    /**
     * Returns group id, currently set.
     *
     * @return string
     */
    public function getGroupId()
    {
        return $this->get('group_id');
    }

    /**
     * Returns name of group.
     *
     * @param bool $removePrefix Flag to remove "grp_" prefix from group name
     */
    public function getGroupName(bool $removePrefix = false): string
    {
        $groupName = $this->get('groupname');

        return $removePrefix ? self::getUnprefixedGroupName($groupName) : $groupName;
    }

    /**
     * Returns name of a group cleaned from prefix "grp_".
     */
    public static function getUnprefixedGroupName(string $groupName): string
    {
        return cString::getPartOfString($groupName, cString::getStringLength(self::PREFIX));
    }

    /**
     * Returns the provided group name prefixed with "grp_", if not exists.
     */
    public static function prefixedGroupName(string $groupName): string
    {
        if (cString::getPartOfString($groupName, 0, cString::getStringLength(cApiGroup::PREFIX)) != cApiGroup::PREFIX) {
            return cApiGroup::PREFIX . $groupName;
        }
        return $groupName;
    }

    /**
     * Returns group property by its type and name
     *
     * @param string $type
     * @param string $name
     * @return string|bool value or false
     * @throws cDbException|cException
     */
    public function getGroupProperty($type, $name)
    {
        $groupPropColl = new cApiGroupPropertyCollection($this->values['group_id']);
        $groupProp = $groupPropColl->fetchByGroupIdTypeName($type, $name);

        return $groupProp ? $groupProp->get('value') : false;
    }

    /**
     * Retrieves all available properties of the group.
     *
     * @return array Returns associative properties array as follows:
     *      <pre>
     *      - $arr[idgroupprop][name]
     *      - $arr[idgroupprop][type]
     *      - $arr[idgroupprop][value]
     *      </pre>
     * @throws cDbException|cException
     */
    public function getGroupProperties(): array
    {
        $groupPropColl = new cApiGroupPropertyCollection($this->values['group_id']);
        $groupProps = $groupPropColl->fetchByGroupId();

        $props = [];
        foreach ($groupProps as $groupProp) {
            $props[cSecurity::toInteger($groupProp->get('idgroupprop'))] = [
                'name' => $groupProp->get('name'),
                'type' => $groupProp->get('type'),
                'value' => $groupProp->get('value'),
            ];
        }

        return $props;
    }

    /**
     * Stores a property to the database.
     *
     * @param string $type Type (class, category etc.) for the property to retrieve
     * @param string $name Name of the property to retrieve
     * @param string $value Value to insert
     * @return cApiGroupProperty
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function setGroupProperty($type, $name, $value)
    {
        $groupPropColl = new cApiGroupPropertyCollection($this->values['group_id']);
        return $groupPropColl->setValueByTypeName($type, $name, $value);
    }

    /**
     * Deletes a group property from the table.
     *
     * @param string $type Type (class, category etc.) for the property to delete
     * @param string $name Name of the property to delete
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function deleteGroupProperty($type, $name): bool
    {
        $groupPropColl = new cApiGroupPropertyCollection($this->values['group_id']);
        return $groupPropColl->deleteByGroupIdTypeName($type, $name);
    }
}
