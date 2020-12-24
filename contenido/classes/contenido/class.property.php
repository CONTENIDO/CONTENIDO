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
 * @package          Core
 * @subpackage       GenericDB_Model
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Property collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiPropertyCollection extends ItemCollection {

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
     * @param int $idclient [optional]
     *                      Client id
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function __construct($idclient = 0) {
        global $cfg, $client, $lang;

        if (0 === $idclient) {
            // @todo Make client id parameter mandatory, otherwise using the global variable
            // may lead to unwanted issues!
            $idclient = $client;
        }

        $this->client = cSecurity::toInteger($idclient);
        parent::__construct($cfg['tab']['properties'], 'idproperty');
        $this->_setItemClass('cApiProperty');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');

        if (!isset(self::$_enableCache)) {
            if (isset($cfg['properties']) && isset($cfg['properties']['properties']) && isset($cfg['properties']['properties']['enable_cache'])) {
                self::$_enableCache = (bool) $cfg['properties']['properties']['enable_cache'];

                if (isset($cfg['properties']['properties']['itemtypes']) && is_array($cfg['properties']['properties']['itemtypes'])) {
                    self::$_cacheItemtypes = $cfg['properties']['properties']['itemtypes'];
                    foreach (self::$_cacheItemtypes as $name => $value) {
                        if ('%client%' == $value) {
                            self::$_cacheItemtypes[$name] = (int) $idclient;
                        } elseif ('%lang%' == $value) {
                            self::$_cacheItemtypes[$name] = (int) $lang;
                        } else {
                            unset(self::$_cacheItemtypes[$name]);
                        }
                    }
                }
            } else {
                self::$_enableCache = false;
            }
        }

        if (self::$_enableCache && !isset(self::$_entries)) {
            $this->_loadFromCache();
        }
    }

    /**
     * Resets the states of static properties.
     */
    public static function reset() {
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
     * @param mixed $itemtype
     *                           Type of the item (example: idcat)
     * @param mixed $itemid
     *                           ID of the item (example: 31)
     * @param mixed $type
     *                           Type of the data to store (arbitary data)
     * @param mixed $name
     *                           Entry name
     * @param mixed $value
     *                           Value
     * @param bool  $bDontEscape [optional; default false]
     *                           on internal call do not escape parameters again
     *                           NOTE: This parameter is deprecated since 2013-11-26
     *
     * @return cApiProperty
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($itemtype, $itemid, $type, $name, $value, $bDontEscape = false) {
        global $auth;

        $item = $this->createNewItem();

        $item->set('idclient', $this->client);
        $item->set('itemtype', $itemtype, false);
        $item->set('itemid', $itemid, false);
        $item->set('type', $type);
        $item->set('name', $name);
        $item->set('value', $value);

        $item->set('created', date('Y-m-d H:i:s'), false);
        $item->set('author', $auth->auth['uid']);
        $item->store();

        if ($this->_useCache($itemtype, $itemid)) {
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
     * @param mixed $itemtype
     *                       Type of the item (example: idcat)
     * @param mixed $itemid
     *                       ID of the item (example: 31)
     * @param mixed $type
     *                       Type of the data to store (arbitary data)
     * @param mixed $name
     *                       Entry name
     * @param mixed $default [optional]
     *                       to be returned if no item was found
     *
     * @return mixed Value
     * @throws cDbException
     * @throws cException
     */
    public function getValue($itemtype, $itemid, $type, $name, $default = false) {
        if ($this->_useCache($itemtype, $itemid)) {
            return $this->_getValueFromCache($itemtype, $itemid, $type, $name, $default);
        }

        if (isset($this->client)) {
            $sql = $this->db->prepare("idclient = %d AND itemtype = '%s' AND itemid = '%s' AND type = '%s' AND name = '%s'", $this->client, $itemtype, $itemid, $type, $name);
        } else {
            // @todo We never get here, since this class will always have a set client property!
            $sql = $this->db->prepare("itemtype = '%s' AND itemid = '%s' AND type = '%s' AND name = '%s'", $itemtype, $itemid, $type, $name);
        }
        $this->select($sql);

        if (false !== $item = $this->next()) {
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
     * @param mixed $itemtype
     *         Type of the item (example: idcat)
     * @param mixed $itemid
     *         ID of the item (example: 31)
     * @param mixed $type
     *         Type of the data to store (arbitrary data)
     *
     * @return array
     *         Value
     *
     * @throws cDbException
     * @throws cException
     */
    public function getValuesByType($itemtype, $itemid, $type) {
        if ($this->_useCache($itemtype, $itemid)) {
            return $this->_getValuesByTypeFromCache($itemtype, $itemid, $type);
        }

        $aResult = [];

        if (isset($this->client)) {
            $sql = $this->db->prepare("idclient = %d AND itemtype = '%s' AND itemid = '%s' AND type = '%s'", $this->client, $itemtype, $itemid, $type);
        } else {
            // @fixme We never get here, since this class will always have a set client property!
            $sql = $this->db->prepare("itemtype = '%s' AND itemid = '%s' AND type = '%s'", $itemtype, $itemid, $type);
        }
        $this->select($sql);

        while (($item = $this->next()) !== false) {
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
     * @param       $type
     * @param mixed $name
     *         Type of the data to store (arbitrary data)
     *
     * @return array
     *         Value
     *
     * @throws cDbException
     * @throws cException
     */
    public function getValuesOnlyByTypeName($type, $name) {
        $aResult = [];

        $sql = $this->db->prepare("type = '%s' AND name = '%s'", $type, $name);
        $this->select($sql);
        while (($item = $this->next()) !== false) {
            $aResult[] = cSecurity::unescapeDB($item->get('value'));
        }

        return $aResult;
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
     * @param mixed $itemtype
     *                      Type of the item (example: idcat)
     * @param mixed $itemid
     *                      ID of the item (example: 31)
     * @param mixed $type
     *                      Type of the data to store (arbitary data)
     * @param mixed $name
     *                      Entry name
     * @param mixed $value
     *                      Value
     * @param int   $idProp [optional]
     *                      Id of database record (if set, update on this basis
     *                      (possibility to update name value and type))
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function setValue($itemtype, $itemid, $type, $name, $value, $idProp = 0) {
        $idProp = (int) $idProp;

        if ($idProp == 0) {
            $sql = $this->db->prepare("idclient = %d AND itemtype = '%s' AND itemid = '%s' AND type = '%s' AND name = '%s'", $this->client, $itemtype, $itemid, $type, $name);
        } else {
            $sql = $this->db->prepare("idclient = %d AND itemtype = '%s' AND itemid = '%s' AND idproperty = %d", $this->client, $itemtype, $itemid, $idProp);
        }
        $this->select($sql);

        if (($item = $this->next()) !== false) {
            $item->set('value', $value);
            $item->set('name', $name);
            $item->set('type', $type);
            $item->store();

            if ($this->_useCache($itemtype, $itemid)) {
                $this->_addToCache($item);
            }
        } else {
            $this->create($itemtype, $itemid, $type, $name, $value, true);
        }
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
     * @param mixed $itemtype
     *         Type of the item (example: idcat)
     * @param mixed $itemid
     *         ID of the item (example: 31)
     * @param mixed $type
     *         Type of the data to store (arbitary data)
     * @param mixed $name
     *         Entry name
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function deleteValue($itemtype, $itemid, $type, $name) {
        if (isset($this->client)) {
            $where = $this->db->prepare("idclient = %d AND itemtype = '%s' AND itemid = '%s' AND type = '%s' AND name = '%s'", $this->client, $itemtype, $itemid, $type, $name);
        } else {
            // @fixme We never get here, since this class will always have a set client property!
            $where = $this->db->prepare("itemtype = '%s' AND itemid = '%s' AND type = '%s' AND name = '%s'", $itemtype, $itemid, $type, $name);
        }

        $idproperties = $this->getIdsByWhereClause($where);

        $this->_deleteMultiple($idproperties);
        if ($this->_useCache()) {
            $this->_deleteFromCacheMultiple($idproperties);
        }
    }

    /**
     * Checks if values for a given item are available.
     *
     * @param mixed $itemtype
     *         Type of the item (example: idcat)
     * @param mixed $itemid
     *         ID of the item (example: 31)
     *
     * @return array
     *         For each given item
     *
     * @throws cDbException
     * @throws cException
     */
    public function getProperties($itemtype, $itemid) {
        if ($this->_useCache($itemtype, $itemid)) {
            return $this->_getPropertiesFromCache($itemtype, $itemid);
        }

        if (isset($this->client)) {
            $sql = $this->db->prepare("idclient = %d AND itemtype = '%s' AND itemid = '%s'", $this->client, $itemtype, $itemid);
        } else {
            // @fixme We never get here, since this class will always have a set client property!
            $sql = $this->db->prepare("itemtype = '%s' AND itemid = '%s'", $itemtype, $itemid);
        }
        $this->select($sql);

        $result[$itemid] = false;

        while (($item = $this->next()) !== false) {
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
     * @param mixed $field
     *                    Field to search in
     * @param mixed $fieldValue
     *                    Value to search for
     * @param cAuth $auth [optional]
     *                    Narrow result down to user in auth object
     * @return array
     *                    For each given item
     * @throws cDbException
     * @throws cException
     */
    public function getAllValues($field, $fieldValue, $auth = NULL) {
        $authString = '';
        if (!is_null($auth) && is_object($auth) && sizeof($auth->auth) > 0) {
            $authString .= " AND author = '" . $this->db->escape($auth->auth["uid"]) . "'";
        }

        $field = $this->db->escape($field);
        $fieldValue = $this->db->escape($fieldValue);

        if (isset($this->client)) {
            $this->select("idclient = " . (int) $this->client . " AND " . $field . " = '" . $fieldValue . "'" . $authString, '', 'itemid');
        } else {
            // @fixme We never get here, since this class will always have a set client property!
            $this->select($field . " = '" . $fieldValue . "'" . $authString);
        }

        $retValue = [];
        while (($item = $this->next()) !== false) {
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
     * @param mixed $itemtype
     *         Type of the item (example: idcat)
     * @param mixed $itemid
     *         ID of the item (example: 31)
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function deleteProperties($itemtype, $itemid) {
        if (isset($this->client)) {
            $where = $this->db->prepare("idclient = %d AND itemtype = '%s' AND itemid = '%s'", $this->client, $itemtype, $itemid);
        } else {
            // @fixme We never get here, since this class will always have a set client property!
            $where = $this->db->prepare("itemtype = '%s' AND itemid = '%s'", $itemtype, $itemid);
        }

        $idproperties = $this->getIdsByWhereClause($where);

        $this->_deletePropertiesByIds($idproperties);
    }

    /**
     * Delete all properties which match itemtype and multiple itemids.
     *
     * @param mixed $itemtype
     *         Type of the item (example: idcat)
     * @param array $itemids
     *         Ids of multiple items (example: [31,12,22])
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function deletePropertiesMultiple($itemtype, array $itemids) {
        $itemtype = $this->db->escape($itemtype);
        $itemids = array_map([$this, 'escape'], $itemids);
        $in = "'" . implode("', '", $itemids) . "'";

        if (isset($this->client)) {
            $where = "idclient = " . (int) $this->client . " AND itemtype = '" . $itemtype . "' AND itemid IN (" . $in . ")";
        } else {
            // @fixme We never get here, since this class will always have a set client property!
            $where = "itemtype = '" . $itemtype . "' AND itemid IN (" . $in . ")";
        }

        $idproperties = $this->getIdsByWhereClause($where);

        $this->_deletePropertiesByIds($idproperties);
    }

    /**
     * Changes the client
     *
     * @param int $idclient
     */
    public function changeClient($idclient) {
        $this->client = (int) $idclient;
    }

    /**
     * Loads/Caches configured properties, but only for current client!
     * NOTE: It loads properties for global set client, not for the client set
     * in this instance!
     *
     * @throws cDbException
     * @throws cException
     */
    protected function _loadFromCache() {
        global $client;
        if (!isset(self::$_entries)) {
            self::$_entries = [];
        }

        $where = [];
        foreach (self::$_cacheItemtypes as $itemtype => $itemid) {
            if (is_numeric($itemid)) {
                $where[] = "(itemtype = '" . $itemtype . "' AND itemid = " . $itemid . ")";
            } else {
                $where[] = "(itemtype = '" . $itemtype . "' AND itemid = '" . $itemid . "')";
            }
        }

        if (count($where) == 0) {
            return;
        }

        $where = "idclient = " . (int) $client . ' AND ' . implode(' OR ', $where);
        $this->select($where);
        /** @var cApiUserProperty $property */
        while (($property = $this->next()) !== false) {
            $this->_addToCache($property);
        }
    }

    /**
     *
     * @param string $itemtype [optional]
     * @param int $itemid [optional]
     * @return bool
     */
    protected function _useCache($itemtype = NULL, $itemid = NULL) {
        global $client;
        $ok = (self::$_enableCache && $this->client == $client);
        if (!$ok) {
            return $ok;
        } elseif ($itemtype == NULL || $itemid == NULL) {
            return $ok;
        }

        foreach (self::$_cacheItemtypes as $name => $value) {
            if ((isset($value['itemtype']) && $itemtype == $value['itemtype'])
                || (isset($value['itemid']) && $itemid == $value['itemid'])) {
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
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    protected function _deletePropertiesByIds(array $ids) {
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
     * @param cApiUserProperty $entry
     */
    protected function _addToCache($entry) {
        $data = $entry->toArray();
        self::$_entries[$data['idproperty']] = $data;
    }

    /**
     * Removes a entry from cache.
     *
     * @param int $id
     */
    protected function _deleteFromCache($id) {
        if (isset(self::$_entries[$id])) {
            unset(self::$_entries[$id]);
        }
    }

    /**
     * Removes multiple entries from cache.
     *
     * @param array $ids
     */
    protected function _deleteFromCacheMultiple(array $ids) {
        foreach ($ids as $id) {
            if (isset(self::$_entries[$id])) {
                unset(self::$_entries[$id]);
            }
        }
    }

    /**
     * Returns the value for a given item from cache.
     *
     * @param mixed $itemtype
     *         Type of the item (example: idcat)
     * @param mixed $itemid
     *         ID of the item (example: 31)
     * @param mixed $type
     *         Type of the data to store (arbitrary data)
     * @param mixed $name
     *         Entry name
     * @param mixed $default [optional]
     *         to be returned if no item was found
     * @return mixed
     *         Value
     */
    protected function _getValueFromCache($itemtype, $itemid, $type, $name, $default = false) {
        foreach (self::$_entries as $id => $entry) {
            if ($entry['itemtype'] == $itemtype && $entry['itemid'] == $itemid && $entry['type'] == $type && $entry['name'] == $name) {
                return cSecurity::unescapeDB($entry['value']);
            }
        }

        return $default;
    }

    /**
     * Returns the values for a given item by its type from cache.
     *
     * @param mixed $itemtype
     *         Type of the item (example: idcat)
     * @param mixed $itemid
     *         ID of the item (example: 31)
     * @param mixed $type
     *         Type of the data to store (arbitrary data)
     * @return array
     *         Value
     *
     */
    protected function _getValuesByTypeFromCache($itemtype, $itemid, $type) {
        $result = [];

        foreach (self::$_entries as $id => $entry) {
            if ($entry['itemtype'] == $itemtype && $entry['itemid'] == $itemid && $entry['type'] == $type) {
                $result[$entry['name']] = cSecurity::unescapeDB($entry['value']);
            }
        }

        return $result;
    }

    /**
     * Returns properties for given item are available.
     *
     * @param mixed $itemtype
     *         Type of the item (example: idcat)
     * @param mixed $itemid
     *         ID of the item (example: 31)
     * @return array
     *         For each given item
     */
    public function _getPropertiesFromCache($itemtype, $itemid) {
        $result = [];
        $result[$itemid] = false;

        foreach (self::$_entries as $id => $entry) {
            if ($entry['itemtype'] == $itemtype && $entry['itemid'] == $itemid) {
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
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiProperty extends Item {

    /**
     * Array which stores the maximum string length of each field
     *
     * @var array
     */
    public $maximumLength;

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $mId [optional]
     *                   Specifies the ID of item to load
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['properties'], 'idproperty');

        // Initialize maximum lengths for each column
        $this->maximumLength = [
            'itemtype' => 64,
            'itemid' => 255,
            'type' => 96,
            'name' => 96
        ];

        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Stores changed cApiProperty
     *
     * @return bool
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function store() {
        global $auth;

        $this->set('modified', date('Y-m-d H:i:s'), false);
        $this->set('modifiedby', $auth->auth['uid']);

        return parent::store();
    }

    /**
     * Sets value of a field
     *
     * @param string $field
     * @param string $value
     * @param bool $safe [optional]
     *         Flag to run filter on passed value
     * @return bool
     * @throws cInvalidArgumentException
     *     if the field is too small for the given value
     */
    public function setField($field, $value, $safe = true) {
        if (array_key_exists($field, $this->maximumLength)) {
            if (cString::getStringLength($value) > $this->maximumLength[$field]) {
                throw new cInvalidArgumentException("Tried to set field $field to value $value, but the field is too small. Truncated.");
            }
        }

        return parent::setField($field, $value, $safe);
    }

}
