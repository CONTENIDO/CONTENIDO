<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Group class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    1.2
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Group collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiGroupCollection extends ItemCollection
{
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['groups'], 'group_id');
        $this->_setItemClass('cApiGroup');
    }

    /**
     * Creates a group entry.
     *
     * @param  string  $groupname
     * @param  string  $perms
     * @param  string  $description
     * @return cApiGroup|null
     */
    public function create($groupname, $perms, $description)
    {
        $primaryKeyValue = md5($groupname);

        $item = parent::create($primaryKeyValue);
        if (!is_object($item)) {
            return null;
        }

        if (substr($groupname, 0, strlen(cApiGroup::PREFIX)) != cApiGroup::PREFIX) {
            $groupname = cApiGroup::PREFIX . $groupname;
        }

        $item->set('groupname', $this->escape($groupname));
        $item->set('perms', $this->escape($perms));
        $item->set('description', $this->escape($description));
        $item->store();

        return $item;
    }

    /**
     * Returns the groups a user is in
     * @param   string    $userid
     * @return  cApiGroup[]  List of groups
     */
    public function fetchByUserID($userid)
    {
        global $cfg;

        $aIds = array();
        $aGroups = array();

        $sql = "SELECT a.group_id FROM `%s` AS a, `%s` AS b "
             . "WHERE (a.group_id  = b.group_id) AND (b.user_id = '%s')";

        $this->db->query($sql, $this->table, $cfg['tab']['groupmembers'], $userid);
        $this->_lastSQL = $sql;

        while ($this->db->next_record()) {
            $aIds[] = $this->db->f('group_id');
        }

        if (0 === count($aIds)) {
            return $aGroups;
        }

        $where = "group_id IN ('" . implode("', '", $aIds) .  "')";
        $this->select($where);
        while ($oItem = $this->next()) {
            $aGroups[] = clone $oItem;
        }

        return $aGroups;
    }

    /**
     * Removes the specified group from the database
     *
     * @param   string  $groupid  Specifies the group ID
     * @return  bool    True if the delete was successful
     * @deprecated  [2012-03-27]  Use cApiGroupCollection->delete() instead
     */
    public function deleteGroupByID($groupid)
    {
        cDeprecated("Use cApiGroupCollection->delete() instead");
        return $this->delete($userid);
    }

    /**
     * Removes the specified group from the database.
     *
     * @param   string  $groupname  Specifies the groupname
     * @return  bool    True if the delete was successful
     */
    public function deleteGroupByGroupname($groupname)
    {
        $result = $this->deleteBy('groupname', $groupname);
        return ($result > 0) ? true : false;
    }

    /**
     * Returns all groups which are accessible by the current group.
     *
     * @param   array  $perms
     * @return  cApiGroup  Array of group objects
     */
    public function fetchAccessibleGroups($perms)
    {
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
     * @param   array  $perms
     * @return  array  Array of user like $arr[user_id][groupname], $arr[user_id][description]
     *                 Note: Value of $arr[user_id][groupname] is cleaned from prefix "grp_"
     */
    public function getAccessibleGroups($perms)
    {
        $groups = array();
        $oGroups = $this->fetchAccessibleGroups($perms);
        foreach ($oGroups as $oItem) {
            $groups[$oItem->get('group_id')] = array(
                'groupname' => $oItem->getGroupName(true),
                'description' => $oItem->get('description'),
            );
        }
        return $groups;
    }

}


/**
 * Group item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiGroup extends Item
{
    const PREFIX = 'grp_';

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
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
     * @param   string  $groupId  Specifies the groupId
     * @return  bool    True if the load was successful
     */
    public function loadGroupByGroupID($groupId)
    {
        return $this->loadByPrimaryKey($groupId);
    }

    /**
     * Loads a group entry by its groupname.
     *
     * @param   string  $groupname  Specifies the groupname
     * @return  bool    True if the load was successful
     */
    public function loadGroupByGroupname($groupname)
    {
        return $this->loadBy('groupname', $groupname);
    }

    /**
     * User defined field value setter.
     *
     * @param  string  $sField  Field name
     * @param  string  $mValue  Value to set
     * @param  bool    $bSafe   Flag to run defined inFilter on passed value
     */
    public function setField($sField, $mValue, $bSafe = true)
    {
        if ('perms' === $sField) {
            if (is_array($mValue)) {
                $mValue = implode(',', $mValue);
            }
        }

        return parent::setField($sField, $mValue, $bSafe);
    }

    /**
     * Returns list of group permissions.
     * @return array
     */
    public function getPermsArray()
    {
        return explode(',', $this->get('perms'));
    }

    /**
     * Returns name of group.
     * @return  bool  $removePrefix Flag to remove "grp_" prefix from group name
     */
    public function getGroupName($removePrefix = false)
    {
        $groupname = $this->get('groupname');
        return (false === $removePrefix) ? $groupname : self::getUnprefixedGroupName($groupname);
    }

    /**
     * Returns name of a group cleaned from prefix "grp_".
     * @param  string  $groupname
     * @return  string
     */
    public static function getUnprefixedGroupName($groupname)
    {
        return substr($groupname, strlen(self::PREFIX));
    }

    /**
     * Returns group property by its type and name
     * @param  string  $type
     * @param  string  $name
     * @return string|bool  Property value or false
     */
    public function getGroupProperty($type, $name)
    {
        $groupPropColl = new cApiGroupPropertyCollection($this->values['group_id']);
        $groupProp = $groupPropColl->fetchByGroupIdTypeName($type, $name);
        return ($groupProp) ? $groupProp->get('value') : false;
    }

    /**
     * Retrieves all available properties of the group.
     *
     * @return  array  Returns assoziative properties array as follows:
     *                 - $arr[idgroupprop][name]
     *                 - $arr[idgroupprop][type]
     *                 - $arr[idgroupprop][value]
     */
    public function getGroupProperties()
    {
        $props = array();

        $groupPropColl = new cApiGroupPropertyCollection($this->values['group_id']);
        $groupProps = $groupPropColl->fetchByGroupId();
        foreach($groupProps as $groupProp) {
            $props[$groupProp->get('idgroupprop')] = array(
                'name'  => $groupProp->get('name'),
                'type'  => $groupProp->get('type'),
                'value' => $groupProp->get('value'),
            );
        }

        return $props;
    }

    /**
     * Stores a property to the database.
     *
     * @param  string  $type   Type (class, category etc) for the property to retrieve
     * @param  string  $name   Name of the property to retrieve
     * @param  string  $value  Value to insert
     * @return cApiGroupProperty
     */
    public function setGroupProperty($type, $name, $value)
    {
        $groupPropColl = new cApiGroupPropertyCollection($this->values['group_id']);
        return $groupPropColl->setValueByTypeName($type, $name, $value);
    }

    /**
     * Deletes a group property from the table.
     *
     * @param  string  $type  Type (class, category etc) for the property to delete
     * @param  string  $name  Name of the property to delete
     */
    public function deleteGroupProperty($type, $name)
    {
        $groupPropColl = new cApiGroupPropertyCollection($this->values['group_id']);
        return $groupPropColl->deleteByGroupIdTypeName($type, $name);
    }

}


################################################################################
# Old versions of group item collection and group item classes
#
# NOTE: Class implemetations below are deprecated and the will be removed in
#       future versions of contenido.
#       Don't use them, they are still available due to downwards compatibility.


/**
 * Group item collection
 * @deprecated  [[2012-03-27] Use cApiGroupCollection instead of this class.
 */
class Groups
{
    var $table;
    var $db;
    /** @deprecated  [2012-03-27]  Use cApiGroupCollection() instead */
    function Groups($table = '')
    {
        cDeprecated("Use cApiGroupCollection() instead");
        global $cfg;
        $this->table = ($table == '') ? $cfg['tab']['groups'] : $table;
        $this->db = new DB_Contenido();
    }
    /** @deprecated  [2012-03-27]  Use cApiGroupCollection->delete() instead */
    function deleteGroupByID($groupid)
    {
        cDeprecated("Use cApiGroupCollection->delete() instead");
        $oGroupCol = new cApiGroupCollection();
        return $oGroupCol->delete($userid);
    }
    /** @deprecated  [2012-03-27]  Use cApiGroupCollection->deleteGroupByGroupname() instead */
    function deleteGroupByGroupname($groupname)
    {
        cDeprecated("Use cApiGroupCollection->deleteGroupByGroupname() instead");
        $oGroupCol = new cApiGroupCollection();
        return $oGroupCol->deleteGroupByGroupname($groupname);
    }
    /** @deprecated  [2012-03-27]  Use cApiGroupCollection->getAccessibleGroups() instead */
    function getAccessibleGroups($perms)
    {
        cDeprecated("Use cApiGroupCollection->getAccessibleGroups() instead");
        $oGroupCol = new cApiGroupCollection();
        return $oGroupCol->getAccessibleGroups($perms);
    }
}

/**
 * Group item
 * @deprecated  [[2012-03-27] Use cApiGroup instead of this class.
 */
class Group
{
    var $table;
    var $db;
    var $values;
    var $modifiedValues;
    /** @deprecated  [2012-03-27]  Use cApiGroup() instead */
    function Group($table = '')
    {
        cDeprecated("Use cApiGroup() instead");
        global $cfg;
        $this->table = ($table == '') ? $cfg['tab']['groups'] : $table;
        $this->db = new DB_Contenido();
    }
    /** @deprecated  [2012-03-27]  Use cApiGroup->loadGroupByGroupname() instead */
    function loadGroupByGroupname($groupname)
    {
        cDeprecated("Use cApiGroup->loadGroupByGroupname() instead");
        $oGroup = new cApiGroup();
        if (!$oGroup->loadGroupByGroupname($groupname)) {
            return false;
        }
        $this->values = $oGroup->toArray();
        return true;
    }
    /** @deprecated  [2012-03-27]  Use cApiGroup->loadByPrimaryKey() instead */
    function loadGroupByGroupID($groupID)
    {
        cDeprecated("Use cApiGroup->loadByPrimaryKey() instead");
        $oGroup = new cApiGroup();
        if (!$oGroup->loadByPrimaryKey($groupID)) {
            return false;
        }
        $this->values = $oGroup->toArray();
        return true;
    }
    /** @deprecated  [2012-03-27]  Use cApiGroup() instead */
    function getField($field)
    {
        cDeprecated("Use cApiGroup() instead");
        return ($this->values[$field]);
    }
    /** @deprecated  [2012-03-27]  Use cApiGroup() instead */
    function setField($field, $value)
    {
        cDeprecated("Use cApiGroup() instead");
        $this->modifiedValues[$field] = true;
        $this->values[$field] = $value;
    }
    /** @deprecated  [2012-03-27]  Use cApiGroup() instead */
    function store()
    {
        cDeprecated("Use cApiGroup() instead");
        $oGroup = new cApiGroup();
        $oGroup->loadByRecordSet($this->values);
        foreach ($this->modifiedValues as $key => $value) {
            $oGroup->set($key, $value);
        }
        return $oGroup->store();
    }
    /** @deprecated  [2012-03-27]  Use cApiGroupPropertyCollection->create() instead */
    function insert()
    {
        cDeprecated("Use cApiGroupPropertyCollection->create() instead");
        $sql = "INSERT INTO " . $this->table ." SET ";
        $first = true;
        foreach ($this->modifiedValues as $key => $value) {
            if ($first == true) {
                $sql .= "$key = '" . $this->values[$key] ."'";
            } else {
                $sql .= ", $key = '" . $this->values[$key] ."'";
            }
            $first = false;
        }
        return ($this->db->query($sql));
    }
    /** @deprecated  [2012-03-27]  Use cApiGroup->getGroupProperty() instead */
    function getGroupProperty($type, $name)
    {
        cDeprecated("Use cApiGroup->getGroupProperty() instead");
        $oGroup = new cApiGroup($this->values['group_id']);
        return $oGroup->getGroupProperty($type, $name);
    }
    /** @deprecated  [2012-03-27]  Use cApiGroup->getGroupProperty() instead */
    function getGroupProperties()
    {
        cDeprecated("Use cApiGroup->getGroupProperties() instead");
        $oGroup = new cApiGroup($this->values['group_id']);
        return $oGroup->getGroupProperties();
    }
    /** @deprecated  [2012-03-27]  Use cApiGroup->setGroupProperty() instead */
    function setGroupProperty($type, $name, $value)
    {
        cDeprecated("Use cApiGroup->setGroupProperty() instead");
        $oGroup = new cApiGroup($this->values['group_id']);
        return $oGroup->setGroupProperty($type, $name, $value);
    }
    /** @deprecated  [2012-03-27]  Use cApiGroup->deleteGroupProperty() instead */
    function deleteGroupProperty($type, $name)
    {
        cDeprecated("Use cApiGroup->deleteGroupProperty() instead");
        $oGroup = new cApiGroup($this->values['group_id']);
        return $oGroup->deleteGroupProperty($type, $name);
    }
}


?>