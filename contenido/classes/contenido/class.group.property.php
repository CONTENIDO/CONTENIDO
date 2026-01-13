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
 * @extends ItemCollection<cApiGroupProperty>
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
        parent::__construct(cDb::getTableName('group_prop'), 'idgroupprop');
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
     * @param int $categoryLanguageId [optional]
     * @return cApiGroupProperty
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function setValueByTypeName($type, $name, $value, $categoryLanguageId = 0)
    {
        $item = $this->fetchByGroupIdTypeName($type, $name);
        if ($item) {
            $item->set('value', $value);
            $item->store();
        } else {
            $item = $this->create($type, $name, $value, $categoryLanguageId);
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
     * @param int $categoryLanguageId [optional]
     * @return cApiGroupProperty
     * @throws cDbException|cException
     * @throws cInvalidArgumentException
     */
    public function create($type, $name, $value, $categoryLanguageId = 0)
    {
        $item = $this->createNewItem();

        $item->set('group_id', $this->_groupId);
        $item->set('type', $type);
        $item->set('name', $name);
        $item->set('value', $value);
        $item->set('idcatlang', $categoryLanguageId);
        $item->store();

        if (self::$_enableCache) {
            $this->_addToCache($item);
        }

        return $item;
    }

    /**
     * Returns group property by group id, type and name.
     *
     * @param string $type
     * @param string $name
     * @return ?cApiGroupProperty
     * @throws cDbException|cException
     */
    public function fetchByGroupIdTypeName($type, $name)
    {
        $type = cSecurity::toString($type);
        $name = cSecurity::toString($name);

        if (self::$_enableCache) {
            return $this->_fetchByGroupIdTypeNameFromCache($type, $name);
        }

        $this->select($this->db->prepare(
            "`group_id` = '%s' AND `type` = '%s' AND `name` = '%s'",
            $this->_groupId,
            $type,
            $name
        ));
        if (($property = $this->next()) !== false) {
            return $property;
        }
        return NULL;
    }

    /**
     * Returns all group properties by group id and type.
     *
     * @param string $type
     * @return cApiGroupProperty[]
     * @throws cDbException|cException
     */
    public function fetchByGroupIdType($type): array
    {
        $type = cSecurity::toString($type);

        if (self::$_enableCache) {
            return $this->_fetchByGroupIdTypeFromCache($type);
        }

        $this->select($this->db->prepare("`group_id` = '%s' AND `type` = '%s'", $this->_groupId, $type));
        $props = [];
        while ($property = $this->next()) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Returns all group properties by group id.
     *
     * @return cApiGroupProperty[]
     * @throws cDbException|cException
     */
    public function fetchByGroupId(): array
    {
        if (self::$_enableCache) {
            return $this->_fetchByGroupIdFromCache();
        }

        $this->select($this->db->prepare("`group_id` = '%s'", $this->_groupId));
        $props = [];
        while ($property = $this->next()) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Deletes group property by group id, type and name.
     *
     * @param string $type
     * @param string $name
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function deleteByGroupIdTypeName($type, $name): bool
    {
        $this->select($this->db->prepare(
            "`group_id` = '%s' AND `type` = '%s' AND `name` = '%s'",
            $this->_groupId,
            $type,
            $name
        ));

        return $this->_deleteSelected();
    }

    /**
     * Deletes group properties by group id and type.
     *
     * @param string $type
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function deleteByGroupIdType($type): bool
    {
        $this->select($this->db->prepare(
            "`group_id` = '%s' AND `type` = '%s'",
            $this->_groupId,
            $type
        ));

        return $this->_deleteSelected();
    }

    /**
     * Deletes all group properties by group id.
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function deleteByGroupId(): bool
    {
        $this->select($this->db->prepare("`group_id` = '%s'", $this->_groupId));
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
        while ($prop = $this->next()) {
            $id = cSecurity::toInteger($prop->get('idgroupprop'));
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
        // cacheable groups
        if (count(self::$_entries) > self::$_maxGroups) {
            array_shift(self::$_entries);
        }

        $sql = $this->db->prepare("`group_id` = '%s'", $this->_groupId);
        $this->select($sql);
        while ($property = $this->next()) {
            $data = $property->toArray();
            self::$_entries[$this->_groupId][$data['idgroupprop']] = $data;
        }
    }

    /**
     * Adds a entry to the cache.
     */
    protected function _addToCache(cApiGroupProperty $item)
    {
        $data = $item->toArray();
        self::$_entries[$this->_groupId][$data['idgroupprop']] = $data;
    }

    /**
     * Fetches group property by group id, type and name from cache.
     */
    protected function _fetchByGroupIdTypeNameFromCache(string $type, string $name): ?cApiGroupProperty
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
     * Fetches all group properties by group id and type from cache.
     *
     * @return cApiGroupProperty[]
     */
    protected function _fetchByGroupIdTypeFromCache(string $type): array
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
     * Fetches all group properties by group id from cache.
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
     * Removes an entry from cache.
     */
    protected function _deleteFromCache(int $id)
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
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     * @throws cInvalidArgumentException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('group_prop'), 'idgroupprop');
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
