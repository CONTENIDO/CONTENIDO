<?php

/**
 * This file contains the property collection and item class.
 *
 * Custom properties
 * -----------------
 * Custom properties are properties which can be assigned to virtually any element
 * in CONTENIDO and underlaying websites.
 *
 * Table structure
 * ---------------
 *
 * Field       Size         Description
 * -----       ----         -----------
 * idproperty  int(10)      idproperty (automatically handled by this class)
 * idclient    int(10)      Id of client
 * itemtype    varchar(32)  Custom item type (e.g. idcat, idart, idartlang, custom)
 * itemid      varchar(32)  ID of the item
 * type        varchar(32)  Property type
 * name        varchar(32)  Property name value text Property value
 * author      varchar(32)  Author (md5-hash of the username)
 * created     datetime     Created date and time
 * modified    datetime     Modified date and time
 * modifiedby  varchar(32)  Modified by (md5-hash of the username)
 *
 * Example:
 * --------
 * A module needs to store custom properties for categories. Modifying the database
 * would be a bad thing, since the changes might get lost during an upgrade or
 * reinstall. If the custom property for a category would be the path to a category
 * image, we would fill a row as follows:
 *
 * itemtype: idcat
 * itemid:   <number of your category>
 * type:     category
 * name:     image
 * value:    images/category01.gif
 *
 * idproperty, author, created, modified and modifiedby are automatically handled
 * by the class. If caching is enabled, see $cfg['properties']['properties']['enable_cache'],
 * configured entries will be loaded at first time. If enabled, each call of
 * cApiPropertyCollection functions to retrieve cacheable properties will return
 * the cached entries without stressing the database. The cApiPropertyCollection
 * class keeps also track of changed and deleted properties and synchronizes
 * them with cached values, as long as you use the interface of
 * cApiPropertyCollection to manage the properties.
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
 * Property collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiProperty>
 */
class cApiPropertyCollection extends ItemCollection
{

    use cItemCollectionIdsByClientIdTrait;

    /**
     * @var string Client id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkClientIdName = 'idclient';

    /**
     * Client id
     *
     * @var int
     */
    public $client;

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
     * Itemtypes and itemids array
     *
     * @var array
     */
    protected static $_cacheItemtypes;

    /**
     * Constructor to create an instance of this class.
     *
     * @param int $clientId [optional] Client id
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function __construct($clientId = 0)
    {
        if ($clientId) {
            // @todo Make client id parameter mandatory, otherwise using the global variable
            // may lead to unwanted issues!
            $clientId = cRegistry::getClientId();
        }

        $this->client = cSecurity::toInteger($clientId);
        parent::__construct(cDb::getTableName('properties'), 'idproperty');
        $this->_setItemClass('cApiProperty');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');

        if (!isset(self::$_enableCache)) {
            $cfg = cRegistry::getConfig();
            self::$_enableCache = cSecurity::toBoolean(
                $cfg['properties']['properties']['enable_cache'] ?? '0'
            );
            if (self::$_enableCache) {
                if (isset(
                    $cfg['properties']['properties']['itemtypes'])
                    && is_array($cfg['properties']['properties']['itemtypes'])
                ) {
                    self::$_cacheItemtypes = $cfg['properties']['properties']['itemtypes'];
                    foreach (self::$_cacheItemtypes as $name => $value) {
                        if ('%client%' == $value) {
                            self::$_cacheItemtypes[$name] = (int)$clientId;
                        } elseif ('%lang%' == $value) {
                            self::$_cacheItemtypes[$name] = cRegistry::getLanguageId();
                        } else {
                            unset(self::$_cacheItemtypes[$name]);
                        }
                    }
                }
            }
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
        self::$_enableCache = false;
        self::$_entries = [];
        self::$_cacheItemtypes = [];
    }

    /**
     * Creates a new property item.
     *
     * Example:
     * <pre>
     * $properties = new cApiPropertyCollection($clientid);
     * $property = $properties->create('idcat', 27, 'visual', 'image', 'images/tool.gif');
     * </pre>
     *
     * @param mixed $itemType Type of the item (example: idcat)
     * @param mixed $itemId ID of the item (example: 31)
     * @param mixed $type Type of the data to store (arbitrary data)
     * @param mixed $name Entry name
     * @param mixed $value Value
     * @param bool $dontEscape [optional; default false] on internal call do not escape parameters again
     *      NOTE: This parameter is deprecated since 2013-11-26
     * @return cApiProperty
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($itemType, $itemId, $type, $name, $value, $dontEscape = false)
    {
        $auth = cRegistry::getAuth();
        $item = $this->createNewItem();

        $item->set('idclient', $this->client);
        $item->set('itemtype', $itemType, false);
        $item->set('itemid', $itemId, false);
        $item->set('type', $type);
        $item->set('name', $name);
        $item->set('value', $value);

        $item->set('created', date('Y-m-d H:i:s'), false);
        $item->set('author', $auth->getUserId());
        $item->store();

        if ($this->_useCache($itemType, $itemId)) {
            $this->_addToCache($item);
        }

        return $item;
    }

    /**
     * Returns the value for a given item.
     *
     * Example:
     * <pre>
     * $properties = new cApiPropertyCollection($clientid);
     * $value = $properties->getValue('idcat', 27, 'visual', 'image');
     * </pre>
     *
     * @param mixed $itemType Type of the item (example: idcat)
     * @param mixed $itemId  ID of the item (example: 31)
     * @param mixed $type Type of the data to store (arbitrary data)
     * @param mixed $name Entry name
     * @param mixed $default [optional] to be returned if no item was found
     * @return mixed Value
     * @throws cDbException|cException
     */
    public function getValue($itemType, $itemId, $type, $name, $default = false)
    {
        if ($this->_useCache($itemType, $itemId)) {
            return $this->_getValueFromCache($itemType, $itemId, $type, $name, $default);
        }

        if (isset($this->client)) {
            $sql = $this->db->prepare(
                "`idclient` = %d AND `itemtype` = '%s' AND `itemid` = '%s' AND `type` = '%s' AND `name` = '%s'",
                $this->client,
                $itemType,
                $itemId,
                $type,
                $name
            );
        } else {
            // @todo We never get here, since this class will always have a set client property!
            $sql = $this->db->prepare(
                "`itemtype` = '%s' AND `itemid` = '%s' AND `type` = '%s' AND `name` = '%s'",
                $itemType,
                $itemId,
                $type,
                $name
            );
        }
        $this->select($sql);

        if (($item = $this->next()) !== false) {
            return cSecurity::unescapeDB($item->get('value'));
        }

        return $default;
    }

    /**
     * Returns the value for a given item.
     *
     * Example:
     * <pre>
     * $properties = new cApiPropertyCollection($clientid);
     * $values = $properties->getValuesByType('idcat', 27, 'visual');
     * </pre>
     *
     * @param mixed $itemType Type of the item (example: idcat)
     * @param mixed $itemId ID of the item (example: 31)
     * @param mixed $type Type of the data to store (arbitrary data)
     * @return array Value
     * @throws cDbException|cException
     */
    public function getValuesByType($itemType, $itemId, $type): array
    {
        if ($this->_useCache($itemType, $itemId)) {
            return $this->_getValuesByTypeFromCache($itemType, $itemId, $type);
        }

        $aResult = [];

        if (isset($this->client)) {
            $sql = $this->db->prepare(
                "`idclient` = %d AND `itemtype` = '%s' AND `itemid` = '%s' AND `type` = '%s'",
                $this->client,
                $itemType,
                $itemId,
                $type
            );
        } else {
            // @fixme We never get here, since this class will always have a set client property!
            $sql = $this->db->prepare(
                "`itemtype` = '%s' AND `itemid` = '%s' AND `type` = '%s'",
                $itemType,
                $itemId,
                $type
            );
        }
        $this->select($sql);

        while ($item = $this->next()) {
            $aResult[$item->get('name')] = cSecurity::unescapeDB($item->get('value'));
        }

        return $aResult;
    }

    /**
     * Returns the values only by type and name.
     *
     * Example:
     * <pre>
     * $properties = new cApiPropertyCollection($clientid);
     * $values = $properties->getValuesOnlyByTypeName('note', 'category');
     * </pre>
     *
     * @param string $type
     * @param mixed $name
     * @return array Value
     * @throws cDbException|cException
     */
    public function getValuesOnlyByTypeName($type, $name): array
    {
        $result = [];

        $this->select($this->db->prepare("`type` = '%s' AND `name` = '%s'", $type, $name));
        while ($item = $this->next()) {
            $result[] = cSecurity::unescapeDB($item->get('value'));
        }

        return $result;
    }

    /**
     * Sets a property item.
     * Handles creation and updating.
     * Existing item will be updated, not existing item will be created.
     *
     * Example:
     * <pre>
     * $properties = new cApiPropertyCollection($clientid);
     * $properties->setValue('idcat', 27, 'visual', 'image', 'images/tool.gif');
     * </pre>
     *
     * @param mixed $itemType Type of the item (example: idcat)
     * @param mixed $itemId ID of the item (example: 31)
     * @param mixed $type Type of the data to store (arbitrary data)
     * @param mixed $name  Entry name
     * @param mixed $value  Value
     * @param int $idProp [optional] Id of database record (if set, update on this basis
     *      possibility to update name value and type)
     * @return bool
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function setValue($itemType, $itemId, $type, $name, $value, $idProp = 0)
    {
        $idProp = cSecurity::toInteger($idProp);

        if ($idProp == 0) {
            $where = $this->db->prepare(
                "`idclient` = %d AND `itemtype` = '%s' AND `itemid` = '%s' AND `type` = '%s' AND `name` = '%s'",
                $this->client, $itemType, $itemId, $type, $name
            );
        } else {
            $where = $this->db->prepare(
                "`idclient` = %d AND `itemtype` = '%s' AND `itemid` = '%s' AND `idproperty` = %d",
                $this->client, $itemType, $itemId, $idProp
            );
        }
        $this->select($where);

        if (($item = $this->next()) !== false) {
            $item->set('value', $value);
            $item->set('name', $name);
            $item->set('type', $type);
            $result = $item->store();

            if ($this->_useCache($itemType, $itemId)) {
                $this->_addToCache($item);
            }
        } else {
            $item = $this->create($itemType, $itemId, $type, $name, $value, true);
            $result = is_object($item);
        }

        return $result;
    }

    /**
     * Delete a property item.
     *
     * Example:
     * <pre>
     * $properties = new cApiPropertyCollection($clientid);
     * $properties->deleteValue('idcat', 27, 'visual', 'image');
     * </pre>
     *
     * @param mixed $itemType Type of the item (example: idcat)
     * @param mixed $itemId ID of the item (example: 31)
     * @param mixed $type Type of the data to store (arbitrary data)
     * @param mixed $name Entry name
     * @return int the number of deleted entries (rows)
     * @throws cDbException|cInvalidArgumentException
     */
    public function deleteValue($itemType, $itemId, $type, $name): int
    {
        if (isset($this->client)) {
            $where = $this->db->prepare(
                "`idclient` = %d AND `itemtype` = '%s' AND `itemid` = '%s' AND `type` = '%s' AND `name` = '%s'",
                $this->client, $itemType, $itemId, $type, $name
            );
        } else {
            // @fixme We never get here, since this class will always have a set client property!
            $where = $this->db->prepare(
                "`itemtype` = '%s' AND `itemid` = '%s' AND `type` = '%s' AND `name` = '%s'",
                $itemType, $itemId, $type, $name
            );
        }

        $idProperties = $this->getIdsByWhereClause($where);

        $numDeleted = $this->_deleteMultiple($idProperties);
        if ($this->_useCache()) {
            $this->_deleteFromCacheMultiple($idProperties);
        }

        return $numDeleted;
    }

    /**
     * Checks if values for a given item are available.
     *
     * @param mixed $itemType Type of the item (example: idcat)
     * @param mixed $itemId ID of the item (example: 31)
     * @return array For each given item
     * @throws cDbException|cException
     */
    public function getProperties($itemType, $itemId)
    {
        if ($this->_useCache($itemType, $itemId)) {
            return $this->_getPropertiesFromCache($itemType, $itemId);
        }

        if (isset($this->client)) {
            $sql = $this->db->prepare(
                "`idclient` = %d AND `itemtype` = '%s' AND `itemid` = '%s'",
                $this->client, $itemType, $itemId
            );
        } else {
            // @fixme We never get here, since this class will always have a set client property!
            $sql = $this->db->prepare(
                "`itemtype` = '%s' AND `itemid` = '%s'",
                $itemType, $itemId
            );
        }
        $this->select($sql);

        // @TODO The initial value of $result[$itemId] should be an empty array, but this breaks the compatibility
        $result[$itemId] = false;

        while ($item = $this->next()) {
            // Fix automatic conversion of false to array warning, see initial value above!
            if ($result[$itemId] === false) {
                $result[$itemId] = [];
            }
            // enable accessing property values per number and field name
            $result[$item->get('itemid')][$item->get('idproperty')] = [
                0 => $item->get('type'),
                'type' => $item->get('type'),
                1 => $item->get('name'),
                'name' => $item->get('name'),
                2 => $item->get('value'),
                'value' => $item->get('value')
            ];
        }
        return $result;
    }

    /**
     * Returns all datasets selected by given field and value combination
     *
     * @param mixed $field Field to search in
     * @param mixed $fieldValue Value to search for
     * @param cAuth $auth [optional] Narrow result down to user in auth object
     * @return array For each given item
     * @throws cDbException|cException
     */
    public function getAllValues($field, $fieldValue, $auth = NULL): array
    {
        $authString = '';
        if (!is_null($auth) && is_object($auth) && count($auth->getAuthInfo()) > 0) {
            $authString .= " AND `author` = '" . $this->db->escape($auth->getUserId()) . "'";
        }

        $field = $this->db->escape($field);
        $fieldValue = $this->db->escape($fieldValue);

        if (isset($this->client)) {
            $this->select(sprintf(
                "`idclient` = %d AND `%s` = '%s'%s",
                $this->client,
                $field,
                $fieldValue,
                $authString
            ), '', 'itemid');
        } else {
            // @fixme We never get here, since this class will always have a set client property!
            $this->select("`" . $field . "` = '" . $fieldValue . "'" . $authString);
        }

        $retValue = [];
        while ($item = $this->next()) {
            $dbLine = [
                'idproperty' => $item->get('idproperty'),
                'idclient' => $item->get('idclient'),
                'itemtype' => $item->get('itemtype'),
                'itemid' => $item->get('itemid'),
                'type' => $item->get('type'),
                'name' => $item->get('name'),
                'value' => $item->get('value'),
                'author' => $item->get('author'),
                'created' => $item->get('created'),
                'modified' => $item->get('modified'),
                'modifiedby' => $item->get('modifiedby')
            ];
            $retValue[] = $dbLine;
        }
        return $retValue;
    }

    /**
     * Delete all properties which match itemtype and itemid
     *
     * @param mixed $itemType Type of the item (example: idcat)
     * @param mixed $itemId ID of the item (example: 31)
     * @throws cDbException|cInvalidArgumentException
     */
    public function deleteProperties($itemType, $itemId)
    {
        if (isset($this->client)) {
            $where = $this->db->prepare(
                "`idclient` = %d AND `itemtype` = '%s' AND `itemid` = '%s'",
                $this->client, $itemType, $itemId
            );
        } else {
            // @fixme We never get here, since this class will always have a set client property!
            $where = $this->db->prepare(
                "`itemtype` = '%s' AND `itemid` = '%s'",
                $itemType, $itemId
            );
        }

        $idProperties = $this->getIdsByWhereClause($where);

        $this->_deletePropertiesByIds($idProperties);
    }

    /**
     * Delete all properties which match itemtype and multiple itemids.
     *
     * @param mixed $itemType Type of the item (example: idcat)
     * @param array $itemids Ids of multiple items (example: [31,12,22])
     * @throws cDbException|cInvalidArgumentException
     */
    public function deletePropertiesMultiple($itemType, array $itemIds)
    {
        $itemType = $this->db->escape($itemType);
        $itemIds = array_map([$this, 'escape'], $itemIds);
        $in = "'" . implode("', '", $itemIds) . "'";

        if (isset($this->client)) {
            $where = "`idclient` = " . $this->client . " AND `itemtype` = '" . $itemType . "' AND `itemid` IN (" . $in . ")";
        } else {
            // @fixme We never get here, since this class will always have a set client property!
            $where = "`itemtype` = '" . $itemType . "' AND `itemid` IN (" . $in . ")";
        }

        $idProperties = $this->getIdsByWhereClause($where);

        $this->_deletePropertiesByIds($idProperties);
    }

    /**
     * Changes the client
     *
     * @param int $clientId
     */
    public function changeClient($clientId)
    {
        $this->client = cSecurity::toInteger($clientId);
    }

    /**
     * Loads/Caches configured properties, but only for current client!
     * NOTE: It loads properties for global set client, not for the client set in this instance!
     *
     * @throws cDbException|cException
     */
    protected function _loadFromCache()
    {
        if (!isset(self::$_entries)) {
            self::$_entries = [];
        }

        $where = [];
        foreach (self::$_cacheItemtypes as $itemType => $itemId) {
            if (is_numeric($itemId)) {
                $where[] = "(`itemtype` = '" . $itemType . "' AND `itemid` = " . $itemId . ")";
            } else {
                $where[] = "(`itemtype` = '" . $itemType . "' AND `itemid` = '" . $itemId . "')";
            }
        }

        if (count($where) == 0) {
            return;
        }

        $where = "`idclient` = " . $this->client . ' AND ' . implode(' OR ', $where);
        $this->select($where);
        /** @var cApiUserProperty $property */
        while ($property = $this->next()) {
            $this->_addToCache($property);
        }
    }

    /**
     *
     * @param string $itemType [optional]
     * @param int $itemId [optional]
     */
    protected function _useCache($itemType = NULL, $itemId = NULL): bool
    {
        $client = cRegistry::getClientId();
        $ok = self::$_enableCache && $this->client == $client;
        if (!$ok) {
            return $ok;
        } elseif ($itemType == NULL || $itemId == NULL) {
            return $ok;
        }

        foreach (self::$_cacheItemtypes as $name => $value) {
            if ((isset($value['itemtype']) && $itemType == $value['itemtype'])
                || (isset($value['itemid']) && $itemId == $value['itemid'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deletes multiple property entries by their ids.
     * Deletes them also from internal cache.
     *
     * @param array $ids
     * @throws cDbException|cInvalidArgumentException
     */
    protected function _deletePropertiesByIds(array $ids)
    {
        if (count($ids) > 0) {
            $this->_deleteMultiple($ids);
            if ($this->_useCache()) {
                $this->_deleteFromCacheMultiple($ids);
            }
        }
    }

    /**
     * Adds a entry to the cache.
     *
     * @param cApiProperty|cApiUserProperty $entry
     */
    protected function _addToCache($entry)
    {
        $data = $entry->toArray();
        self::$_entries[$data['idproperty']] = $data;
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

    /**
     * Removes multiple entries from cache.
     *
     * @param array $ids
     */
    protected function _deleteFromCacheMultiple(array $ids)
    {
        foreach ($ids as $id) {
            if (isset(self::$_entries[$id])) {
                unset(self::$_entries[$id]);
            }
        }
    }

    /**
     * Returns the value for a given item from cache.
     *
     * @param mixed $itemType Type of the item (example: idcat)
     * @param mixed $itemId ID of the item (example: 31)
     * @param mixed $type Type of the data to store (arbitrary data)
     * @param mixed $name Entry name
     * @param mixed $default [optional] to be returned if no item was found
     * @return mixed Value
     */
    protected function _getValueFromCache($itemType, $itemId, $type, $name, $default = false)
    {
        foreach (self::$_entries as $id => $entry) {
            if ($entry['itemtype'] == $itemType && $entry['itemid'] == $itemId && $entry['type'] == $type && $entry['name'] == $name) {
                return cSecurity::unescapeDB($entry['value']);
            }
        }

        return $default;
    }

    /**
     * Returns the values for a given item by its type from cache.
     *
     * @param mixed $itemType Type of the item (example: idcat)
     * @param mixed $itemId ID of the item (example: 31)
     * @param mixed $type Type of the data to store (arbitrary data)
     * @return array Value
     */
    protected function _getValuesByTypeFromCache($itemType, $itemId, $type): array
    {
        $result = [];

        foreach (self::$_entries as $id => $entry) {
            if ($entry['itemtype'] == $itemType && $entry['itemid'] == $itemId && $entry['type'] == $type) {
                $result[$entry['name']] = cSecurity::unescapeDB($entry['value']);
            }
        }

        return $result;
    }

    /**
     * Returns properties for given item are available.
     *
     * @param mixed $itemType Type of the item (example: idcat)
     * @param mixed $itemId ID of the item (example: 31)
     * @return array For each given item
     */
    public function _getPropertiesFromCache($itemType, $itemId): array
    {
        $result = [];
        $result[$itemId] = false;

        foreach (self::$_entries as $id => $entry) {
            if ($entry['itemtype'] == $itemType && $entry['itemid'] == $itemId) {
                // enable accessing property values per number and field name
                $result[$entry['itemid']][$entry['idproperty']] = [
                    0 => $entry['type'],
                    'type' => $entry['type'],
                    1 => $entry['name'],
                    'name' => $entry['name'],
                    2 => $entry['value'],
                    'value' => $entry['value']
                ];
            }
        }

        return $result;
    }

}

/**
 * Property item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiProperty extends Item
{

    /**
     * Array which stores the maximum string length of each field
     *
     * @var array
     */
    public $maximumLength;

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('properties'), 'idproperty');

        // Initialize maximum lengths for each column
        $this->maximumLength = [
            'itemtype' => 64,
            'itemid' => 255,
            'type' => 96,
            'name' => 96
        ];

        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Stores changed cApiProperty
     *
     * @inheritDoc
     */
    public function store()
    {
        $auth = cRegistry::getAuth();

        $this->set('modified', date('Y-m-d H:i:s'), false);
        $this->set('modifiedby', $auth->getUserId());

        return parent::store();
    }

    /**
     * Sets value of a field
     *
     * @inheritDoc
     * @throws cInvalidArgumentException if the field is too small for the given value
     */
    public function setField($name, $value, $safe = true)
    {
        if (array_key_exists($name, $this->maximumLength)) {
            if (cString::getStringLength($value) > $this->maximumLength[$name]) {
                throw new cInvalidArgumentException("Tried to set field $name to value $value, but the field is too small. Truncated.");
            }
        }

        return parent::setField($name, $value, $safe);
    }

}
