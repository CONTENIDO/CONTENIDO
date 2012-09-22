<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * User property management class.
 *
 * cApiUserProperty instance contains following properties:
 * - iduserprop    (int)
 * - user_id       (string)
 * - type          (string)
 * - name          (string)
 * - value         (string)
 * - idcatlang     (int)
 *
 * If caching is enabled, see $cfg['properties']['user_prop']['enable_cache'],
 * all entries will be loaded at first time.
 * If enabled, each call of cApiUserPropertyCollection functions to retrieve properties
 * will return the cached entries without stressing the database.
 *
 * The cApiUserPropertyCollection class keeps also track of changed and deleted
 * properties and synchronizes them with cached values, as long as you use the
 * interface of cApiUserPropertyCollection to manage the properties.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    0.2
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2011-11-03
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * User property collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiUserPropertyCollection extends ItemCollection {

    /**
     * User id (usually the current logged in user)
     * @var string
     */
    protected $_userId = '';

    /**
     * List of cached entries
     * @var array
     */
    protected static $_entries;

    /**
     * Flag to enable caching.
     * @var bool
     */
    protected static $_enableCache;

    /**
     * Constructor
     * @param  string  $userId
     */
    public function __construct($userId) {
        global $cfg;
        parent::__construct($cfg['tab']['user_prop'], 'iduserprop');
        $this->_setItemClass('cApiUserProperty');

        if (!isset(self::$_enableCache)) {
            if (isset($cfg['properties']) && isset($cfg['properties']['user_prop'])
                    && isset($cfg['properties']['user_prop']['enable_cache'])) {
                self::$_enableCache = (bool) $cfg['properties']['user_prop']['enable_cache'];
            } else {
                self::$_enableCache = false;
            }
        }

        $this->setUserId($userId);
    }

    /**
     * Resets the states of static properties.
     */
    public static function reset() {
        unset(self::$_enableCache, self::$_entries);
    }

    /**
     * User id setter
     *
     * @param string $userId
     * @throws cInvalidArgumentException If passed user id is empty
     * @return void
     */
    public function setUserId($userId) {
        if (empty($userId)) {
            throw new cInvalidArgumentException("Empty user id");
        }
        $this->_userId = $userId;
        if (self::$_enableCache && !isset(self::$_entries)) {
            $this->_loadFromCache();
        }
    }

    /**
     * Updatess a existing user property entry or creates it.
     * @param  string  $type
     * @param  string  $name
     * @param  string  $value
     * @param  int     $idcatlang
     * @return cApiUserProperty
     */
    public function setValueByTypeName($type, $name, $value, $idcatlang = 0) {
        $item = $this->fetchByUserIdTypeName($type, $name);
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
     * Creates a user property entry.
     * @param  string  $type
     * @param  string  $name
     * @param  string  $value
     * @param  int     $idcatlang
     * @return cApiUserProperty
     */
    public function create($type, $name, $value, $idcatlang = 0) {
        $item = parent::createNewItem();

        $item->set('user_id', $this->escape($this->_userId));
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
     * Returns all user properties by userid.
     * @return cApiUserProperty[]
     */
    public function fetchByUserId() {
        if (self::$_enableCache) {
            return $this->_fetchByUserIdFromCache();
        }

        $this->select("user_id = '" . $this->escape($this->_userId) . "'");
        $props = array();
        while ($property = $this->next()) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Returns all user properties of all users by type and name.
     * NOTE: Enabled caching will be skipped in this case!
     * @param  string  $type
     * @param  string  $name
     * @return cApiUserProperty[]
     */
    public function fetchByTypeName($type, $name) {
        $this->select("type ='" . $this->escape($type) . "' AND name = '" . $this->escape($name) . "'");
        $props = array();
        while ($property = $this->next()) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Returns all user properties by userid, type and name.
     * @param  string  $type
     * @param  string  $name
     * @return cApiUserProperty|null
     */
    public function fetchByUserIdTypeName($type, $name) {
        if (self::$_enableCache) {
            return $this->_fetchByUserIdTypeNameFromCache($type, $name);
        }

        $this->select("user_id = '" . $this->escape($this->_userId) . "' AND type = '" . $this->escape($type) . "' AND name = '" . $this->escape($name) . "'");
        if ($property = $this->next()) {
            return $property;
        }
        return null;
    }

    /**
     * Returns all user properties by userid and type.
     * @param  string  $type
     * @return cApiUserProperty[]
     */
    public function fetchByUserIdType($type) {
        if (self::$_enableCache) {
            return $this->_fetchByUserIdTypeFromCache($type);
        }

        $this->select("user_id = '" . $this->escape($this->_userId) . "' AND type = '" . $this->escape($type) . "'");
        $props = array();
        while ($property = $this->next()) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Deletes user property by userid, type and name.
     * @param  string  $type
     * @param  string  $name
     * @return bool
     */
    public function deleteByUserIdTypeName($type, $name) {
        $this->select("user_id = '" . $this->escape($this->_userId) . "' AND type = '" . $this->escape($type) . "' AND name = '" . $this->escape($name) . "'");
        return $this->_deleteSelected();
    }

    /**
     * Deletes user properties by userid and type.
     * @param  string  $type
     * @return bool
     */
    public function deleteByUserIdType($type) {
        $this->select("user_id = '" . $this->escape($this->_userId) . "' AND type = '" . $this->escape($type) . "'");
        return $this->_deleteSelected();
    }

    /**
     * Deletes all user properties by userid.
     * @return bool
     */
    public function deleteByUserId() {
        $this->select("user_id = '" . $this->escape($this->_userId) . "'");
        return $this->_deleteSelected();
    }

    /**
     * Deletes selected user properties.
     * @return bool
     */
    protected function _deleteSelected() {
        $result = false;
        while ($prop = $this->next()) {
            $id = $prop->get('iduserprop');
            if (self::$_enableCache) {
                $this->_deleteFromCache($id);
            }
            $result = $this->delete($id);
        }
        return $result;
    }

    /**
     * Loads/Caches all user properties.
     */
    protected function _loadFromCache() {
        self::$_entries = array();
        $this->select("user_id='" . $this->escape($this->_userId) . "'");
        while ($property = $this->next()) {
            $data = $property->toArray();
            self::$_entries[$data['iduserprop']] = $data;
        }
    }

    /**
     * Adds a entry to the cache.
     * @param  cApiUserProperty  $entry
     */
    protected function _addToCache($entry) {
        $data = $entry->toArray();
        self::$_entries[$data['iduserprop']] = $data;
    }

    /**
     * Fetches all user properties by userid from cache.
     * @return cApiUserProperty[]
     */
    protected function _fetchByUserIdFromCache() {
        $props = array();
        $obj = new cApiUserProperty();
        foreach (self::$_entries as $entry) {
            $obj->loadByRecordSet($entry);
            $props[] = clone $obj;
        }
        return $props;
    }

    /**
     * Fetches user properties by userid, type and name from cache.
     * @param  string  $type
     * @param  string  $name
     * @return cApiUserProperty|null
     */
    public function _fetchByUserIdTypeNameFromCache($type, $name) {
        $props = array();
        $obj = new cApiUserProperty();
        foreach (self::$_entries as $entry) {
            if ($entry['type'] == $type && $entry['name'] == $name) {
                $obj->loadByRecordSet($entry);
                return $obj;
            }
        }
        return null;
    }

    /**
     * Fetches user properties by userid and type from cache.
     * @param  string  $type
     * @return cApiUserProperty[]
     */
    public function _fetchByUserIdTypeFromCache($type) {
        $props = array();
        $obj = new cApiUserProperty();
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
     * @param   int  $id
     */
    protected function _deleteFromCache($id) {
        if (isset(self::$_entries[$id])) {
            unset(self::$_entries[$id]);
        }
    }

}

/**
 * User property item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiUserProperty extends Item {

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['user_prop'], 'iduserprop');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Updates a user property value.
     * @param   string  $value
     * @return  bool
     */
    public function updateValue($value) {
        $this->set('value', $this->escape($value));
        return $this->store();
    }

}

?>