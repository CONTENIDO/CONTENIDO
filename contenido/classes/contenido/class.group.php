<?php
/**
 * This file contains the group collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @version SVN Revision $Rev:$
 *
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Group collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiGroupCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['groups'], 'group_id');
        $this->_setItemClass('cApiGroup');
    }

    /**
     * Creates a group entry.
     *
     * @param string $groupname
     * @param string $perms
     * @param string $description
     * @return cApiGroup|false
     */
    public function create($groupname, $perms, $description) {
        $primaryKeyValue = md5($groupname);

        $item = $this->createNewItem($primaryKeyValue);
        if (!is_object($item)) {
            return false;
        }

        $groupname = cApiGroup::prefixedGroupName($groupname);

        $item->set('groupname', $groupname);
        $item->set('perms', $perms);
        $item->set('description', $description);
        $item->store();

        return $item;
    }

    /**
     * Returns the groups a user is in
     *
     * @param string $userid
     * @return cApiGroup[] List of groups
     */
    public function fetchByUserID($userid) {
        global $cfg;

        $aIds = array();
        $aGroups = array();

        $sql = "SELECT a.group_id FROM `%s` AS a, `%s` AS b " . "WHERE (a.group_id  = b.group_id) AND (b.user_id = '%s')";

        $this->db->query($sql, $this->table, $cfg['tab']['groupmembers'], $userid);
        $this->_lastSQL = $sql;

        while ($this->db->nextRecord()) {
            $aIds[] = $this->db->f('group_id');
        }

        if (0 === count($aIds)) {
            return $aGroups;
        }

        $where = "group_id IN ('" . implode("', '", $aIds) . "')";
        $this->select($where);
        while (($oItem = $this->next()) !== false) {
            $aGroups[] = clone $oItem;
        }

        return $aGroups;
    }

    /**
     * Removes the specified group from the database.
     *
     * @param string $groupname Specifies the groupname
     * @return bool True if the delete was successful
     */
    public function deleteGroupByGroupname($groupname) {
        $groupname = cApiGroup::prefixedGroupName($groupname);
        $result = $this->deleteBy('groupname', $groupname);
        return ($result > 0) ? true : false;
    }

    /**
     * Returns all groups which are accessible by the current group.
     *
     * @param array $perms
     * @return cApiGroup Array of group objects
     */
    public function fetchAccessibleGroups($perms) {
        $groups = array();
        $limit = array();
        $where = '';

        if (!in_array('sysadmin', $perms)) {
            // not sysadmin, compose where rules
            $oClientColl = new cApiClientCollection();
            $allClients = $oClientColl->getAvailableClients();
            foreach ($allClients as $key => $value) {
                if (in_array('client[' . $key . ']', $perms) || in_array('admin[' . $key . ']', $perms)) {
                    $limit[] = 'perms LIKE "%client[' . $this->escape($key) . ']%"';
                }
                if (in_array('admin[' . $key . ']', $perms)) {
                    $limit[] = 'perms LIKE "%admin[' . $this->escape($key) . ']%"';
                }
            }

            if (count($limit) > 0) {
                $where = '1 AND ' . implode(' OR ', $limit);
            }
        }

        $this->select($where);
        while (($oItem = $this->next()) !== false) {
            $groups[] = clone $oItem;
        }

        return $groups;
    }

    /**
     * Returns all groups which are accessible by the current group.
     * Is a wrapper of fetchAccessibleGroups() and returns contrary to that
     * function
     * a multidimensional array instead of a list of objects.
     *
     * @param array $perms
     * @return array Array of user like $arr[user_id][groupname],
     *         $arr[user_id][description]
     *         Note: Value of $arr[user_id][groupname] is cleaned from prefix
     *         "grp_"
     */
    public function getAccessibleGroups($perms) {
        $groups = array();
        $oGroups = $this->fetchAccessibleGroups($perms);
        foreach ($oGroups as $oItem) {
            $groups[$oItem->get('group_id')] = array(
                'groupname' => $oItem->getGroupName(true),
                'description' => $oItem->get('description')
            );
        }
        return $groups;
    }
}

/**
 * Group item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiGroup extends Item {

    /**
     * Prefix to be used for group names.
     *
     * @var string
     */
    const PREFIX = 'grp_';

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['groups'], 'group_id');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Loads a group from the database by its groupId.
     *
     * @param string $groupId Specifies the groupId
     * @return bool True if the load was successful
     */
    public function loadGroupByGroupID($groupId) {
        return $this->loadByPrimaryKey($groupId);
    }

    /**
     * Loads a group entry by its groupname.
     *
     * @param string $groupname Specifies the groupname
     * @return bool True if the load was successful
     */
    public function loadGroupByGroupname($groupname) {
        $groupname = cApiGroup::prefixedGroupName($groupname);
        return $this->loadBy('groupname', $groupname);
    }

    /**
     * User defined field value setter.
     *
     * @param string $sField Field name
     * @param string $mValue Value to set
     * @param bool $bSafe Flag to run defined inFilter on passed value
     * @return bool
     * @see Item::setField()
     */
    public function setField($sField, $mValue, $bSafe = true) {
        if ('perms' === $sField) {
            if (is_array($mValue)) {
                $mValue = implode(',', $mValue);
            }
        }

        return parent::setField($sField, $mValue, $bSafe);
    }

    /**
     * Returns list of group permissions.
     *
     * @return array
     */
    public function getPermsArray() {
        return explode(',', $this->get('perms'));
    }

    /**
     * Returns name of group.
     *
     * @param bool $removePrefix Flag to remove "grp_" prefix from group name
     * @return Ambigous <string, mixed, bool>
     */
    public function getGroupName($removePrefix = false) {
        $groupname = $this->get('groupname');
        return (false === $removePrefix) ? $groupname : self::getUnprefixedGroupName($groupname);
    }

    /**
     * Returns name of a group cleaned from prefix "grp_".
     *
     * @param string $groupname
     * @return string
     */
    public static function getUnprefixedGroupName($groupname) {
        return substr($groupname, strlen(self::PREFIX));
    }

    /**
     * Returns the passed groupname prefixed with "grp_", if not exists.
     *
     * @param string $groupname
     * @return string
     */
    public static function prefixedGroupName($groupname) {
        if (substr($groupname, 0, strlen(cApiGroup::PREFIX)) != cApiGroup::PREFIX) {
            return cApiGroup::PREFIX . $groupname;
        }
        return $groupname;
    }

    /**
     * Returns group property by its type and name
     *
     * @param string $type
     * @param string $name
     * @return string bool value or false
     */
    public function getGroupProperty($type, $name) {
        $groupPropColl = new cApiGroupPropertyCollection($this->values['group_id']);
        $groupProp = $groupPropColl->fetchByGroupIdTypeName($type, $name);
        return ($groupProp) ? $groupProp->get('value') : false;
    }

    /**
     * Retrieves all available properties of the group.
     *
     * @return array Returns assoziative properties array as follows:
     *         - $arr[idgroupprop][name]
     *         - $arr[idgroupprop][type]
     *         - $arr[idgroupprop][value]
     */
    public function getGroupProperties() {
        $props = array();

        $groupPropColl = new cApiGroupPropertyCollection($this->values['group_id']);
        $groupProps = $groupPropColl->fetchByGroupId();
        foreach ($groupProps as $groupProp) {
            $props[$groupProp->get('idgroupprop')] = array(
                'name' => $groupProp->get('name'),
                'type' => $groupProp->get('type'),
                'value' => $groupProp->get('value')
            );
        }

        return $props;
    }

    /**
     * Stores a property to the database.
     *
     * @param string $type Type (class, category etc) for the property to
     *        retrieve
     * @param string $name Name of the property to retrieve
     * @param string $value Value to insert
     * @return cApiGroupProperty
     */
    public function setGroupProperty($type, $name, $value) {
        $groupPropColl = new cApiGroupPropertyCollection($this->values['group_id']);
        return $groupPropColl->setValueByTypeName($type, $name, $value);
    }

    /**
     * Deletes a group property from the table.
     *
     * @param string $type Type (class, category etc) for the property to delete
     * @param string $name Name of the property to delete
     * @return bool
     */
    public function deleteGroupProperty($type, $name) {
        $groupPropColl = new cApiGroupPropertyCollection($this->values['group_id']);
        return $groupPropColl->deleteByGroupIdTypeName($type, $name);
    }
}
