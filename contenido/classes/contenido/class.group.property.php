<?php
/**
 * This file contains the group property collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
 * @version          SVN Revision $Rev:$
 *
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Group property collection.
 *
 * The cApiGroupPropertyCollection class keeps also track of changed and deleted
 * properties and synchronizes them with cached values, as long as you use the
 * interface of cApiGroupPropertyCollection to manage the properties.
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiGroupPropertyCollection extends ItemCollection {

    /**
     * Groups id (usually the current logged in users group)
     *
     * @var string
     */
    protected $_groupId = '';

    /**
     * List of cached entries
     *
     * @var array
     */
    protected static $_entries;

    /**
     * Flag to enable caching.
     *
     * @var bool
     */
    protected static $_enableCache;

    /**
     * Number of max groups to cache proerties from.
     *
     * @var int
     */
    protected static $_maxGroups = 3;

    /**
     * Constructor
     *
     * @param string $groupId
     */
    public function __construct($groupId) {
        global $cfg;
        parent::__construct($cfg['tab']['group_prop'], 'idgroupprop');
        $this->_setItemClass('cApiGroupProperty');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiGroupCollection');

        if (!isset(self::$_enableCache)) {
            if (isset($cfg['properties']) && isset($cfg['properties']['group_prop']) && isset($cfg['properties']['group_prop']['enable_cache'])) {
                self::$_enableCache = (bool) $cfg['properties']['group_prop']['enable_cache'];

                if (isset($cfg['properties']['group_prop']['max_groups'])) {
                    self::$_maxGroups = (int) $cfg['properties']['group_prop']['max_groups'];
                    // if caching is enabled, there is no need to set max cache
                    // value to lower than 1
                    if (self::$_maxGroups < 1) {
                        self::$_maxGroups = 1;
                    }
                }
            } else {
                self::$_enableCache = false;
            }
        }

        $this->setGroupId($groupId);
    }

    /**
     * Resets the states of static properties.
     */
    public static function reset() {
        unset(self::$_enableCache, self::$_entries, self::$_maxGroups);
    }

    /**
     * Group id setter
     *
     * @param string $groupId
     * @throws cInvalidArgumentException If passed group id is empty
     */
    public function setGroupId($groupId) {
        if (empty($groupId)) {
            throw new cInvalidArgumentException("Empty group id");
        }
        $this->_groupId = $groupId;
        if (self::$_enableCache) {
            $this->_loadFromCache();
        }
    }

    /**
     * Updatess a existing group property entry or creates it.
     *
     * @param string $type
     * @param string $name
     * @param string $value
     * @param int $idcatlang
     * @return cApiGroupProperty
     */
    public function setValueByTypeName($type, $name, $value, $idcatlang = 0) {
        $item = $this->fetchByGroupIdTypeName($type, $name);
        if ($item) {
            $item->set('value', $this->escape($value));
            $item->store();
        } else {
            $item = $this->create($type, $name, $value, $idcatlang);
        }

        if (self::$_enableCache) {
            $this->_addToCache($item);
        }

        return $item;
    }

    /**
     * Creates a group property entry.
     *
     * @param string $type
     * @param string $name
     * @param string $value
     * @param int $idcatlang
     * @return cApiGroupProperty
     */
    public function create($type, $name, $value, $idcatlang = 0) {
        $item = parent::createNewItem();

        $item->set('group_id', $this->escape($this->_groupId));
        $item->set('type', $this->escape($type));
        $item->set('name', $this->escape($name));
        $item->set('value', $this->escape($value));
        $item->set('idcatlang', (int) $idcatlang);
        $item->store();

        if (self::$_enableCache) {
            $this->_addToCache($item);
        }

        return $item;
    }

    /**
     * Returns group property by groupid, type and name.
     *
     * @param string $type
     * @param string $name
     * @return cApiGroupProperty null
     */
    public function fetchByGroupIdTypeName($type, $name) {
        if (self::$_enableCache) {
            return $this->_fetchByGroupIdTypeNameFromCache($type, $name);
        }

        $this->select("group_id='" . $this->escape($this->_groupId) . "' AND type='" . $this->escape($type) . "' AND name='" . $this->escape($name) . "'");
        if (($property = $this->next()) !== false) {
            return $property;
        }
        return NULL;
    }

    /**
     * Returns all group properties by groupid and type.
     *
     * @param string $type
     * @return cApiGroupProperty[]
     */
    public function fetchByGroupIdType($type) {
        if (self::$_enableCache) {
            return $this->_fetchByGroupIdTypeFromCache($type);
        }

        $this->select("group_id='" . $this->escape($this->_groupId) . "' AND type='" . $this->escape($type) . "'");
        $props = array();
        while (($property = $this->next()) !== false) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Returns all group properties by groupid.
     *
     * @return cApiGroupProperty[]
     */
    public function fetchByGroupId() {
        if (self::$_enableCache) {
            return $this->_fetchByGroupIdFromCache();
        }

        $this->select("group_id='" . $this->escape($this->_groupId) . "'");
        $props = array();
        while (($property = $this->next()) !== false) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Deletes group property by groupid, type and name.
     *
     * @param string $type
     * @param string $name
     * @return bool
     */
    public function deleteByGroupIdTypeName($type, $name) {
        $this->select("group_id='" . $this->escape($this->_groupId) . "' AND type='" . $this->escape($type) . "' AND name='" . $this->escape($name) . "'");
        return $this->_deleteSelected();
    }

    /**
     * Deletes group properties by groupid and type.
     *
     * @param string $type
     * @return bool
     */
    public function deleteByGroupIdType($type) {
        $this->select("group_id='" . $this->escape($this->_groupId) . "' AND type='" . $this->escape($type) . "'");
        return $this->_deleteSelected();
    }

    /**
     * Deletes all group properties by groupid.
     *
     * @return bool
     */
    public function deleteByGroupId() {
        $this->select("group_id='" . $this->escape($this->_groupId) . "'");
        return $this->_deleteSelected();
    }

    /**
     * Deletes selected group properties.
     *
     * @return bool
     */
    protected function _deleteSelected() {
        $result = false;
        while (($prop = $this->next()) !== false) {
            $id = $prop->get('idgroupprop');
            if (self::$_enableCache) {
                $this->_deleteFromCache($id);
            }
            $result = $this->delete($id);
        }
        return $result;
    }

    /**
     * Loads/Caches all group properties.
     */
    protected function _loadFromCache() {
        if (!isset(self::$_entries)) {
            self::$_entries = array();
        }

        if (isset(self::$_entries[$this->_groupId])) {
            // group is already cached, nothing to do
            return;
        }

        self::$_entries[$this->_groupId] = array();

        // remove entry from beginning, if we achieved the number of max
        // cachable groups
        if (count(self::$_entries) > self::$_maxGroups) {
            array_shift(self::$_entries);
        }

        $this->select("group_id='" . $this->escape($this->_groupId) . "'");
        while (($property = $this->next()) !== false) {
            $data = $property->toArray();
            self::$_entries[$this->_groupId][$data['idgroupprop']] = $data;
        }
    }

    /**
     * Adds a entry to the cache.
     *
     * @param cApiGroupProperty $entry
     */
    protected function _addToCache($item) {
        $data = $item->toArray();
        self::$_entries[$this->_groupId][$data['idgroupprop']] = $data;
    }

    /**
     * Fetches group property by groupid, type and name from cache.
     *
     * @param string $type
     * @param string $name
     * @return cApiGroupProperty null
     */
    protected function _fetchByGroupIdTypeNameFromCache($type, $name) {
        $props = array();
        $obj = new cApiGroupProperty();
        foreach (self::$_entries[$this->_groupId] as $entry) {
            if ($entry['type'] == $type && $entry['name'] == $name) {
                $obj->loadByRecordSet($entry);
                return $obj;
            }
        }
        return NULL;
    }

    /**
     * Fetches all group properties by groupid and type from cache.
     *
     * @param string $type
     * @return cApiGroupProperty[]
     */
    protected function _fetchByGroupIdTypeFromCache($type) {
        $props = array();
        $obj = new cApiGroupProperty();
        foreach (self::$_entries[$this->_groupId] as $entry) {
            if ($entry['type'] == $type) {
                $obj->loadByRecordSet($entry);
                $props[] = clone $obj;
            }
        }
        return $props;
    }

    /**
     * Fetches all group properties by groupid from cache.
     *
     * @return cApiGroupProperty[]
     */
    protected function _fetchByGroupIdFromCache() {
        $props = array();
        $obj = new cApiGroupProperty();
        foreach (self::$_entries[$this->_groupId] as $entry) {
            $obj->loadByRecordSet($entry);
            $props[] = clone $obj;
        }
        return $props;
    }

    /**
     * Removes a entry from cache.
     *
     * @param int $id
     */
    protected function _deleteFromCache($id) {
        if (isset(self::$_entries[$this->_groupId][$id])) {
            unset(self::$_entries[$this->_groupId][$id]);
        }
    }

}

/**
 * Group property item
 *
 * cApiGroupProperty instance contains following class properties:
 * - idgroupprop (int)
 * - group_id (string)
 * - type (string)
 * - name (string)
 * - value (string)
 * - idcatlang (int)
 *
 * If caching is enabled, see $cfg['properties']['group_prop']['enable_cache'],
 * all entries will be loaded at first time.
 * If enabled, each call of cApiGroupPropertyCollection functions to retrieve
 * properties
 * will return the cached entries without stressing the database.
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiGroupProperty extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['group_prop'], 'idgroupprop');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Updates a group property value.
     *
     * @param string $value
     * @return bool
     */
    public function updateValue($value) {
        $this->set('value', $this->escape($value));
        return $this->store();
    }

}
