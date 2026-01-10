<?php

/**
 * This file contains the system property collection and item class.
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
 * System property collection
 *
 * The cApiSystemPropertyCollection class keeps also track of changed and deleted
 * properties and synchronizes them with cached values, as long as you use the
 * interface of cApiSystemPropertyCollection to manage the properties.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiSystemProperty>
 */
class cApiSystemPropertyCollection extends ItemCollection
{

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
     * Constructor to create an instance of this class.
     *
     * @throws cDbException|cException
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('system_prop'), 'idsystemprop');
        $this->_setItemClass('cApiSystemProperty');

        if (!isset(self::$_enableCache)) {
            $cfg = cRegistry::getConfig();
            self::$_enableCache = cSecurity::toBoolean($cfg['properties']['system_prop']['enable_cache'] ?? '0');
        }

        if (self::$_enableCache && !isset(self::$_entries)) {
            $this->_loadFromCache();
        }
    }

    /**
     * Resets the states of static properties.
     */
    public static function reset()
    {
        self::$_enableCache = null;
        self::$_entries = null;
    }

    /**
     * Updates an existing system property entry by its id.
     *
     * @param string $type
     * @param string $name
     * @param string $value
     * @param int $id
     * @return ?cApiSystemProperty
     * @throws cDbException|cException
     * @throws cInvalidArgumentException
     */
    public function setTypeNameValueById($type, $name, $value, $id): ?cApiSystemProperty
    {
        $item = $this->fetchById($id);
        if (!$item) {
            return NULL;
        }

        $item->set('type', $type);
        $item->set('name', $name);
        $item->set('value', $value);
        $item->store();

        if (self::$_enableCache) {
            $this->_addToCache($item);
        }

        return $item;
    }

    /**
     * Updates an existing system property entry or creates it.
     *
     * @param string $type
     * @param string $name
     * @param string $value
     * @return cApiSystemProperty
     * @throws cDbException|cException
     * @throws cInvalidArgumentException
     */
    public function setValueByTypeName($type, $name, $value)
    {
        $item = $this->fetchByTypeName($type, $name);
        if ($item) {
            $item->set('value', $value);
            $item->store();
        } else {
            $item = $this->create($type, $name, $value);
        }

        if (self::$_enableCache) {
            $this->_addToCache($item);
        }

        return $item;
    }

    /**
     * Creates a system property entry.
     *
     * @param string $type
     * @param string $name
     * @param string $value
     * @return cApiSystemProperty
     * @throws cDbException|cException
     * @throws cInvalidArgumentException
     */
    public function create($type, $name, $value)
    {
        $item = $this->createNewItem();

        $item->set('type', $type);
        $item->set('name', $name);
        $item->set('value', $value);
        $item->store();

        if (self::$_enableCache) {
            $this->_addToCache($item);
        }

        return $item;
    }

    /**
     * Returns all system properties.
     *
     * @param string $orderBy [optional] Order by clause like "value ASC"
     * @return cApiSystemProperty[]
     * @throws cDbException|cException
     */
    public function fetchAll($orderBy = ''): array
    {
        if (self::$_enableCache) {
            // no order for cached results
            return $this->_fetchAllFromCache();
        }

        $this->select('', '', $this->escape($orderBy));
        $props = [];
        while ($property = $this->next()) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Returns system property by its id.
     *
     * @param int $id
     * @throws cException
     */
    public function fetchById($id): ?cApiSystemProperty
    {
        if (self::$_enableCache) {
            return $this->_fetchByIdFromCache($id);
        }

        /** @var cApiSystemProperty $item */
        $item = parent::fetchById($id);
        return ($item && $item->isLoaded()) ? $item : NULL;
    }

    /**
     * Returns all system properties by type and name.
     *
     * @param string $type
     * @param string $name
     * @throws cDbException|cException
     */
    public function fetchByTypeName($type, $name): ?cApiSystemProperty
    {
        if (self::$_enableCache) {
            return $this->_fetchByTypeNameFromCache($type, $name);
        }

        $sql = $this->db->prepare("type = '%s' AND name = '%s'", $type, $name);
        $this->select($sql);
        if (($property = $this->next()) !== false) {
            return $property;
        }
        return NULL;
    }

    /**
     * Returns all system properties by type.
     *
     * @param string $type
     * @return cApiSystemProperty[]
     * @throws cDbException|cException
     */
    public function fetchByType($type): array
    {
        if (self::$_enableCache) {
            return $this->_fetchByTypeFromCache($type);
        }

        $sql = $this->db->prepare("type = '%s'", $type);
        $this->select($sql);
        $props = [];
        while ($property = $this->next()) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Deletes system property by type and name.
     *
     * @param string $type
     * @param string $name
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function deleteByTypeName($type, $name): bool
    {
        $sql = $this->db->prepare("type = '%s' AND name = '%s'", $type, $name);
        $this->select($sql);
        return $this->_deleteSelected();
    }

    /**
     * Deletes system properties by type.
     *
     * @param string $type
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function deleteByType($type): bool
    {
        $sql = $this->db->prepare("type = '%s'", $type);
        $this->select($sql);
        return $this->_deleteSelected();
    }

    /**
     * Deletes selected system properties.
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    protected function _deleteSelected(): bool
    {
        $result = false;
        while ($system = $this->next()) {
            $id = $system->get('idsystemprop');
            if (self::$_enableCache) {
                $this->_deleteFromCache($id);
            }
            $result = $this->delete($id);
        }
        return $result;
    }

    /**
     * Loads/Caches all system properties.
     *
     * @throws cDbException|cException
     */
    protected function _loadFromCache()
    {
        self::$_entries = [];
        $this->select();
        while ($property = $this->next()) {
            $data = $property->toArray();
            self::$_entries[$data['idsystemprop']] = $data;
        }
    }

    /**
     * Adds an entry to the cache.
     */
    protected function _addToCache(cApiSystemProperty $entry)
    {
        $data = $entry->toArray();
        self::$_entries[$data['idsystemprop']] = $data;
    }

    /**
     * Fetches all entries from cache.
     *
     * @return cApiSystemProperty[]
     */
    protected function _fetchAllFromCache(): array
    {
        $props = [];
        $obj = new cApiSystemProperty();
        foreach (self::$_entries as $entry) {
            $obj->loadByRecordSet($entry);
            $props[] = clone $obj;
        }
        return $props;
    }

    /**
     * Fetches entry by id from cache.
     *
     * @param int $id
     */
    protected function _fetchByIdFromCache($id): ?cApiSystemProperty
    {
        $obj = new cApiSystemProperty();
        foreach (self::$_entries as $_id => $entry) {
            if ($_id == $id) {
                $obj->loadByRecordSet($entry);
                return $obj;
            }
        }
        return NULL;
    }

    /**
     * Fetches entry by type and name from cache.
     *
     * @param string $type
     * @param string $name
     */
    protected function _fetchByTypeNameFromCache($type, $name): ?cApiSystemProperty
    {
        $obj = new cApiSystemProperty();
        foreach (self::$_entries as $entry) {
            if ($entry['type'] == $type && $entry['name'] == $name) {
                $obj->loadByRecordSet($entry);
                return $obj;
            }
        }
        return NULL;
    }

    /**
     * Fetches entries by type from cache.
     *
     * @param string $type
     * @return cApiSystemProperty[]
     */
    protected function _fetchByTypeFromCache($type): array
    {
        $props = [];
        $obj = new cApiSystemProperty();
        foreach (self::$_entries as $entry) {
            if ($entry['type'] == $type) {
                $obj->loadByRecordSet($entry);
                $props[] = clone $obj;
            }
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
        if (isset(self::$_entries[$id])) {
            unset(self::$_entries[$id]);
        }
    }

}

/**
 * System property item
 *
 * cApiSystemProperty instance contains following class properties:
 * - idsystemprop (int)
 * - type (string)
 * - name (string)
 * - value (string)
 *
 * If caching is enabled, see $cfg['properties']['system_prop']['enable_cache'], all entries
 * will be loaded at first time.
 * If enabled, each call of cApiSystemPropertyCollection functions to retrieve properties
 * will return the cached entries without stressing the database.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiSystemProperty extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('system_prop'), 'idsystemprop');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Updates a system property value.
     *
     * @param string $value
     * @throws cDbException|cInvalidArgumentException
     */
    public function updateValue($value): bool
    {
        $this->set('value', $value);
        return $this->store();
    }

}
