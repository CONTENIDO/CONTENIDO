<?php
/**
 * This file contains the user property collection and item class.
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
 * User property collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiUserPropertyCollection extends ItemCollection {

    /**
     * User id (usually the current logged in user)
     *
     * @var string
     */
    protected $_userId = '';

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
     * Constructor
     *
     * @param string $userId
     */
    public function __construct($userId) {
        $cfg = cRegistry::getConfig();
        parent::__construct($cfg['tab']['user_prop'], 'iduserprop');
        $this->_setItemClass('cApiUserProperty');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiUserCollection');

        if (!isset(self::$_enableCache)) {
            if (isset($cfg['properties']) && isset($cfg['properties']['user_prop']) && isset($cfg['properties']['user_prop']['enable_cache'])) {
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
     */
    public function setUserId($userId) {
        if (empty($userId)) {
            throw new cInvalidArgumentException("Empty user id");
        }
        $this->_userId = $userId;
        if (self::$_enableCache) {
            $this->_loadFromCache();
        }
    }

    /**
     * Updatess a existing user property entry or creates it.
     *
     * @param string $type
     * @param string $name
     * @param string $value
     * @param int $idcatlang
     * @return cApiUserProperty
     */
    public function setValueByTypeName($type, $name, $value, $idcatlang = 0) {
        $item = $this->fetchByUserIdTypeName($type, $name);
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
     * Creates a user property entry.
     *
     * @param string $type
     * @param string $name
     * @param string $value
     * @param int $idcatlang
     * @return cApiUserProperty
     */
    public function create($type, $name, $value, $idcatlang = 0) {
        $item = $this->createNewItem();

        $item->set('user_id', $this->_userId);
        $item->set('type', $type);
        $item->set('name', $name);
        $item->set('value', $value);
        $item->set('idcatlang', (int) $idcatlang);
        $item->store();

        if (self::$_enableCache) {
            $this->_addToCache($item);
        }

        return $item;
    }

    /**
     * Returns all user properties by userid.
     *
     * @return cApiUserProperty[]
     */
    public function fetchByUserId() {
        if (self::$_enableCache) {
            return $this->_fetchByUserIdFromCache();
        }

        $sql = $this->db->prepare("user_id = '%s'", $this->_userId);
        $this->select($sql);
        $props = array();
        while (($property = $this->next()) !== false) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Returns all user properties of all users by type and name.
     * NOTE: Enabled caching will be skipped in this case, since it will return
     * settings for all usery!
     *
     * @param string $type
     * @param string $name
     * @return cApiUserProperty[]
     */
    public function fetchByTypeName($type, $name) {
        $sql = $this->db->prepare("type = '%s' AND name = '%s'", $type, $name);
        $this->select($sql);
        $props = array();
        while (($property = $this->next()) !== false) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Returns all user properties by userid, type and name.
     *
     * @param string $type
     * @param string $name
     * @return cApiUserProperty|NULL
     */
    public function fetchByUserIdTypeName($type, $name) {
        if (self::$_enableCache) {
            return $this->_fetchByUserIdTypeNameFromCache($type, $name);
        }

        $sql = $this->db->prepare("user_id = '%s' AND type = '%s' AND name = '%s'", $this->_userId, $type, $name);
        $this->select($sql);
        if (($property = $this->next()) !== false) {
            return $property;
        }
        return NULL;
    }

    /**
     * Returns all user properties by userid and type.
     *
     * @param string $type
     * @return cApiUserProperty[]
     */
    public function fetchByUserIdType($type) {
        if (self::$_enableCache) {
            return $this->_fetchByUserIdTypeFromCache($type);
        }

        $sql = $this->db->prepare("user_id = '%s' AND type = '%s'", $this->_userId, $type);
        $this->select($sql);
        $props = array();
        while (($property = $this->next()) !== false) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Deletes user property by userid, type and name.
     *
     * @param string $type
     * @param string $name
     * @return bool
     */
    public function deleteByUserIdTypeName($type, $name) {
        $sql = $this->db->prepare("user_id = '%s' AND type = '%s' AND name = '%s'", $this->_userId, $type, $name);
        $this->select($sql);
        return $this->_deleteSelected();
    }

    /**
     * Deletes user properties by userid and type.
     *
     * @param string $type
     * @return bool
     */
    public function deleteByUserIdType($type) {
        $sql = $this->db->prepare("user_id = '%s' AND type = '%s'", $this->_userId, $type);
        $this->select($sql);
        return $this->_deleteSelected();
    }

    /**
     * Deletes all user properties by userid.
     *
     * @return bool
     */
    public function deleteByUserId() {
        $sql = $this->db->prepare("user_id = '%s'", $this->_userId);
        $this->select($sql);
        return $this->_deleteSelected();
    }

    /**
     * Deletes selected user properties.
     *
     * @return bool
     */
    protected function _deleteSelected() {
        $result = false;
        while (($prop = $this->next()) !== false) {
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
        $sql = $this->db->prepare("user_id = '%s'", $this->_userId);
        $this->select($sql);
        while (($property = $this->next()) !== false) {
            $data = $property->toArray();
            self::$_entries[$data['iduserprop']] = $data;
        }
    }

    /**
     * Adds a entry to the cache.
     *
     * @param cApiUserProperty $entry
     */
    protected function _addToCache($entry) {
        $data = $entry->toArray();
        self::$_entries[$data['iduserprop']] = $data;
    }

    /**
     * Fetches all user properties by userid from cache.
     *
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
     *
     * @param string $type
     * @param string $name
     * @return cApiUserProperty|NULL
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
        return NULL;
    }

    /**
     * Fetches user properties by userid and type from cache.
     *
     * @param string $type
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
     *
     * @param int $id
     */
    protected function _deleteFromCache($id) {
        if (isset(self::$_entries[$id])) {
            unset(self::$_entries[$id]);
        }
    }

}

/**
 * User property item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiUserProperty extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        $cfg = cRegistry::getConfig();
        parent::__construct($cfg['tab']['user_prop'], 'iduserprop');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Updates a user property value.
     *
     * @param string $value
     * @return bool
     */
    public function updateValue($value) {
        $this->set('value', $value);
        return $this->store();
    }

}
