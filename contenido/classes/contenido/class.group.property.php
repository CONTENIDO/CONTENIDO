<?php

/**
 * This file contains the group property collection and item class.
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
 * Group property collection.
 *
 * The cApiGroupPropertyCollection class keeps also track of changed and deleted
 * properties and synchronizes them with cached values, as long as you use the
 * interface of cApiGroupPropertyCollection to manage the properties.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @method cApiGroupProperty createNewItem
 * @method cApiGroupProperty|bool next
 */
class cApiGroupPropertyCollection extends ItemCollection
{

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
     * Number of max groups to cache properties from.
     *
     * @var int
     */
    protected static $_maxGroups = 3;

    /**
     * Constructor to create an instance of this class.
     *
     * @param string $groupId
     *
     * @throws cDbException|cException
     * @throws cInvalidArgumentException
     */
    public function __construct($groupId)
    {
        parent::__construct(cRegistry::getDbTableName('group_prop'), 'idgroupprop');
        $this->_setItemClass('cApiGroupProperty');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiGroupCollection');

        if (!isset(self::$_enableCache)) {
            $cfg = cRegistry::getConfig();
            self::$_enableCache = cSecurity::toBoolean($cfg['properties']['group_prop']['enable_cache'] ?? '0');
            if (self::$_enableCache) {
                self::$_maxGroups = cSecurity::toInteger($cfg['properties']['group_prop']['max_groups'] ?? '0');
                // If caching is enabled, there is no need to set max cache value to lower than 1
                if (self::$_maxGroups < 1) {
                    self::$_maxGroups = 1;
                }
            }
        }

        $this->setGroupId($groupId);
    }

    /**
     * Resets the states of static properties.
     */
    public static function reset()
    {
        self::$_enableCache = null;
        self::$_entries = null;
        self::$_maxGroups = 3;
    }

    /**
     * Group id setter
     *
     * @param string $groupId
     * @throws cDbException|cException
     * @throws cInvalidArgumentException If passed group id is empty
     */
    public function setGroupId($groupId)
    {
        if (empty($groupId)) {
            throw new cInvalidArgumentException("Empty group id");
        }
        $this->_groupId = $groupId;
        if (self::$_enableCache) {
            $this->_loadFromCache();
        }
    }

    /**
     * Updates an existing group property entry or creates it.
     *
     * @param string $type
     * @param string $name
     * @param string $value
     * @param int $idcatlang [optional]
     * @return cApiGroupProperty
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function setValueByTypeName($type, $name, $value, $idcatlang = 0)
    {
        $item = $this->fetchByGroupIdTypeName($type, $name);
        if ($item) {
            $item->set('value', $value);
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
     * @param int $idcatlang [optional]
     * @return cApiGroupProperty
     * @throws cDbException|cException
     * @throws cInvalidArgumentException
     */
    public function create($type, $name, $value, $idcatlang = 0)
    {
        $item = $this->createNewItem();

        $item->set('group_id', $this->_groupId);
        $item->set('type', $type);
        $item->set('name', $name);
        $item->set('value', $value);
        $item->set('idcatlang', $idcatlang);
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
     * @return ?cApiGroupProperty
     * @throws cDbException|cException
     */
    public function fetchByGroupIdTypeName($type, $name)
    {
        if (self::$_enableCache) {
            return $this->_fetchByGroupIdTypeNameFromCache($type, $name);
        }

        $sql = $this->db->prepare("group_id = '%s' AND type = '%s' AND name = '%s'", $this->_groupId, $type, $name);
        $this->select($sql);
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
     * @throws cDbException|cException
     */
    public function fetchByGroupIdType($type): array
    {
        if (self::$_enableCache) {
            return $this->_fetchByGroupIdTypeFromCache($type);
        }

        $sql = $this->db->prepare("group_id = '%s' AND type = '%s'", $this->_groupId, $type);
        $this->select($sql);
        $props = [];
        while (($property = $this->next()) !== false) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Returns all group properties by groupid.
     * @return cApiGroupProperty[]
     * @throws cDbException|cException
     */
    public function fetchByGroupId(): array
    {
        if (self::$_enableCache) {
            return $this->_fetchByGroupIdFromCache();
        }

        $sql = $this->db->prepare("group_id = '%s'", $this->_groupId);
        $this->select($sql);
        $props = [];
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
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function deleteByGroupIdTypeName($type, $name): bool
    {
        $sql = $this->db->prepare("`group_id` = '%s' AND `type` = '%s' AND `name` = '%s'", $this->_groupId, $type, $name);
        $this->select($sql);

        return $this->_deleteSelected();
    }

    /**
     * Deletes group properties by groupid and type.
     *
     * @param string $type
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function deleteByGroupIdType($type): bool
    {
        $sql = $this->db->prepare("`group_id` = '%s' AND `type` = '%s'", $this->_groupId, $type);
        $this->select($sql);

        return $this->_deleteSelected();
    }

    /**
     * Deletes all group properties by groupid.
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function deleteByGroupId(): bool
    {
        $sql = $this->db->prepare("`group_id` = '%s'", $this->_groupId);
        $this->select($sql);
        return $this->_deleteSelected();
    }

    /**
     * Deletes selected group properties.
     *
     * @throws cDbException|cException
     * @throws cInvalidArgumentException
     */
    protected function _deleteSelected(): bool
    {
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
     *
     * @throws cDbException|cException
     */
    protected function _loadFromCache()
    {
        if (!isset(self::$_entries)) {
            self::$_entries = [];
        }

        if (isset(self::$_entries[$this->_groupId])) {
            // group is already cached, nothing to do
            return;
        }

        self::$_entries[$this->_groupId] = [];

        // remove entry from beginning, if we achieved the number of max
        // cachable groups
        if (count(self::$_entries) > self::$_maxGroups) {
            array_shift(self::$_entries);
        }

        $sql = $this->db->prepare("group_id = '%s'", $this->_groupId);
        $this->select($sql);
        while (($property = $this->next()) !== false) {
            $data = $property->toArray();
            self::$_entries[$this->_groupId][$data['idgroupprop']] = $data;
        }
    }

    /**
     * Adds a entry to the cache.
     *
     * @param cApiGroupProperty $item
     */
    protected function _addToCache($item)
    {
        $data = $item->toArray();
        self::$_entries[$this->_groupId][$data['idgroupprop']] = $data;
    }

    /**
     * Fetches group property by groupid, type and name from cache.
     *
     * @param string $type
     * @param string $name
     * @return ?cApiGroupProperty
     */
    protected function _fetchByGroupIdTypeNameFromCache($type, $name)
    {
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
    protected function _fetchByGroupIdTypeFromCache($type): array
    {
        $props = [];
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
    protected function _fetchByGroupIdFromCache(): array
    {
        $props = [];
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
    protected function _deleteFromCache($id)
    {
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
 * If enabled, each call of cApiGroupPropertyCollection functions to retrieve properties
 * will return the cached entries without stressing the database.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiGroupProperty extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id Specifies the ID of item to load
     * @throws cDbException|cException
     * @throws cInvalidArgumentException
     */
    public function __construct($id = false)
    {
        parent::__construct(cRegistry::getDbTableName('group_prop'), 'idgroupprop');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Updates a group property value.
     *
     * @param string $value
     * @throws cDbException|cInvalidArgumentException
     */
    public function updateValue($value): bool
    {
        $this->set('value', $value);
        return $this->store();
    }

    /**
     * User-defined setter for group property fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idcatlang':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

}
