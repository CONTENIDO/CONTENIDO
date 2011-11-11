<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * System property management class.
 *
 * cApiSystemProperty instance contains following class properties:
 * - idsystemprop  (int)
 * - type          (string)
 * - name          (string)
 * - value         (string)
 *
 * If caching is enabled, see $cfg['properties']['system_prop']['enable_cache'],
 * all entries will be loaded at first time.
 * If enabled, each call of cApiSystemPropertyCollection functions to retrieve properties
 * will return the cached entries without stressing the database.  
 *
 * The cApiSystemPropertyCollection class keeps also track of changed and deleted
 * properties and synchronizes them with cached values, as long as you use the 
 * interface of cApiSystemPropertyCollection to manage the properties.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend classes
 * @version    0.2
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2011-11-03
 *   created  2011-11-10, Murat Purc, added caching feature
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cApiSystemPropertyCollection extends ItemCollection
{
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
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['system_prop'], 'idsystemprop');
        $this->_setItemClass('cApiSystemProperty');

        if (!isset(self::$_enableCache)) {
            if (isset($cfg['properties']) && isset($cfg['properties']['system_prop']) 
                && isset($cfg['properties']['system_prop']['enable_cache']))
            {
                self::$_enableCache = (bool) $cfg['properties']['system_prop']['enable_cache'];
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
    public static function reset()
    {
        unset(self::$_enableCache, self::$_entries);
    }
    
    /**
     * Updatess a existing system property entry or creates it.
     * @param  string  $type
     * @param  string  $name
     * @param  string  $value
     * @return cApiSystemProperty
     */
    public function set($type, $name, $value)
    {
        $item = $this->fetchByTypeName($type, $name);
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
     * Creates a system property entry.
     * @param  string  $type
     * @param  string  $name
     * @param  string  $value
     * @return cApiSystemProperty
     */
    public function create($type, $name, $value)
    {
        $item = parent::create();

        $item->set('type', $this->escape($type));
        $item->set('name', $this->escape($name));
        $item->set('value', $this->escape($value));
        $item->store();

        if (self::$_enableCache) {
            $this->_addToCache($item);
        }

        return $item;
    }

    /**
     * Returns all system properties.
     * @param  string  $orderBy  Order by clause like "value ASC"
     * @return cApiSystemProperty[]
     */
    public function fetchAll($orderBy = '')
    {
        if (self::$_enableCache) {
            // no order for cached results
            return $this->_fetchAllFromCache();
        }

        $this->select('', '', $this->escape($orderBy));
        $props = array();
        while ($property = $this->next()) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Returns all system properties by type and name.
     * @param  string  $type
     * @param  string  $name
     * @return cApiSystemProperty|null
     */
    public function fetchByTypeName($type, $name)
    {
        if (self::$_enableCache) {
            return $this->_fetchByTypeNameFromCache($type, $name);
        }

        $this->select("type='" . $this->escape($type) . "' AND name='" . $this->escape($name) . "'");
        if ($property = $this->next()) {
            return $property;
        }
        return null;
    }

    /**
     * Returns all system properties by type.
     * @param  string  $type
     * @return cApiSystemProperty[]
     */
    public function fetchByType($type)
    {
        if (self::$_enableCache) {
            return $this->_fetchByTypeFromCache($type);
        }

        $this->select("type='" . $this->escape($type) . "'");
        $props = array();
        while ($property = $this->next()) {
            $props[] = clone $property;
        }
        return $props;
    }

    /**
     * Deletes system property by type and name.
     * @param  string  $type
     * @param  string  $name
     * @return bool
     */
    public function deleteByTypeName($type, $name)
    {
        $this->select("type='" . $this->escape($type) . "' AND name='" . $this->escape($name) . "'");
        return $this->_deleteSelected();
    }

    /**
     * Deletes system properties by type.
     * @param  string  $type
     * @return bool
     */
    public function deleteByType($type)
    {
        $this->select("type='" . $this->escape($type) . "'");
        return $this->_deleteSelected();
    }

    /**
     * Deletes selected system properties.
     * @return bool
     */
    protected function _deleteSelected()
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
     */
    protected function _loadFromCache()
    {
        self::$_entries = array();
        $this->select();
        while ($property = $this->next()) {
            $data = $property->toArray();
            self::$_entries[$data['idsystemprop']] = $data;
        }
    }

    /**
     * Adds a entry to the cache.
     * @param  cApiSystemProperty  $entry
     */
    protected function _addToCache($entry)
    {
        $data = $entry->toArray();
        self::$_entries[$data['idsystemprop']] = $data;
    }

    /**
     * Fetches all entries from cache.
     * @return  cApiSystemProperty[]
     */
    protected function _fetchAllFromCache()
    {
        $props = array();
        $obj = new cApiSystemProperty();
        foreach (self::$_entries as $entry) {
            $obj->loadByRecordSet($entry);
            $props[] = clone $obj;
        }
        return $props;
    }

    /**
     * Fetches entry by type and name from cache.
     * @param   string  $type
     * @param   string  $name
     * @return  cApiSystemProperty|null
     */
    protected function _fetchByTypeNameFromCache($type, $name)
    {
        $obj = new cApiSystemProperty();
        foreach (self::$_entries as $entry) {
            if ($entry['type'] == $type && $entry['name'] == $name) {
                $obj->loadByRecordSet($entry);
                return $obj;
            }
        }
        return null;
    }

    /**
     * Fetches entries by type from cache.
     * @param   string  $type
     * @return  cApiSystemProperty[]
     */
    protected function _fetchByTypeFromCache($type)
    {
        $props = array();
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
     * @param   int  $id
     */
    protected function _deleteFromCache($id)
    {
        if (isset(self::$_entries[$id])) {
            unset(self::$_entries[$id]);
        }
    }

}


/**
 * Class cApiSystemProperty
 */
class cApiSystemProperty extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['system_prop'], 'idsystemprop');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Updates a system property value.
     * @param   string  $value
     * @return  bool
     */
    public function updateValue($value)
    {
        $this->set('value', $this->escape($value));
        return $this->store();
    }
}

?>