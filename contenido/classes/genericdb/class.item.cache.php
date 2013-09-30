<?php
/**
 * This file contains the generic db item cache class.
 *
 * @package Core
 * @subpackage GenericDB
 * @version SVN Revision $Rev:$
 *
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class cItemCache.
 *
 * Implements features to cache entries, usually result sets of Item classes.
 * Contains a list of self instances, where each instance contains cached Items
 * fore one specific table.
 *
 * @package Core
 * @subpackage GenericDB
 */
class cItemCache {

    /**
     * List of self instances (cItemCache)
     *
     * @var array
     */
    protected static $_oInstances = array();

    /**
     * Assoziative cache array
     *
     * @var array
     */
    protected $_aItemsCache = array();

    /**
     * Table name for current instance
     *
     * @var string
     */
    protected $_sTable = '';

    /**
     * Max number of items to cache
     *
     * @var int
     */
    protected $_iMaxItemsToCache = 10;

    /**
     * Enable caching
     *
     * @var bool
     */
    protected $_bEnable = false;

    /**
     * Contructor of cItemCache
     *
     * @param string $sTable Table name
     * @param array $aOptions Options array as follows:
     *        - $aOptions['max_items_to_cache'] = (int) Number of items to cache
     *        - $aOptions['enable'] = (bool) Flag to enable caching
     */
    protected function __construct($sTable, array $aOptions = array()) {
        $this->_sTable = $sTable;
        if (isset($aOptions['max_items_to_cache']) && (int) $aOptions['max_items_to_cache'] > 0) {
            $this->_iMaxItemsToCache = (int) $aOptions['max_items_to_cache'];
        }
        if (isset($aOptions['enable']) && is_bool($aOptions['enable'])) {
            $this->_bEnable = (bool) $aOptions['enable'];
        }
    }

    /**
     * Prevent cloning
     */
    protected function __clone() {
    }

    /**
     * Returns item cache instance, creates it, if not done before.
     * Works as a singleton for one specific table.
     *
     * @param string $sTable Table name
     * @param array $aOptions Options array as follows:
     *        - $aOptions['max_items_to_cache'] = (int) Number of items to cache
     *        - $aOptions['enable'] = (bool) Flag to enable caching
     */
    public static function getInstance($sTable, array $aOptions = array()) {
        if (!isset(self::$_oInstances[$sTable])) {
            self::$_oInstances[$sTable] = new self($sTable, $aOptions);
        }
        return self::$_oInstances[$sTable];
    }

    /**
     * Returns items cache list.
     *
     * @return array
     */
    public function getItemsCache() {
        return $this->_aItemsCache;
    }

    /**
     * Returns existing entry from cache by it's id.
     *
     * @param mixed $mId
     * @return array NULL
     */
    public function getItem($mId) {
        if (!$this->_bEnable) {
            return NULL;
        }

        if (isset($this->_aItemsCache[$mId])) {
            return $this->_aItemsCache[$mId];
        } else {
            return NULL;
        }
    }

    /**
     * Returns existing entry from cache by matching propery value.
     *
     * @param mixed $mProperty
     * @param mixed $mValue
     * @return array NULL
     */
    public function getItemByProperty($mProperty, $mValue) {
        if (!$this->_bEnable) {
            return NULL;
        }

        // loop thru all cached entries and try to find a entry by it's property
        foreach ($this->_aItemsCache as $id => $aEntry) {
            if (isset($aEntry[$mProperty]) && $aEntry[$mProperty] == $mValue) {
                return $aEntry;
            }
        }
        return NULL;
    }

    /**
     * Returns existing entry from cache by matching properties and their
     * values.
     *
     * @param array $aProperties Assoziative key value pairs
     * @return array NULL
     */
    public function getItemByProperties(array $aProperties) {
        if (!$this->_bEnable) {
            return NULL;
        }

        // loop thru all cached entries and try to find a entry by it's property
        foreach ($this->_aItemsCache as $id => $aEntry) {
            $mFound = NULL;
            foreach ($aProperties as $key => $value) {
                if (isset($aEntry[$key]) && $aEntry[$key] == $value) {
                    if (NULL === $mFound) {
                        $mFound = true;
                    }
                } else {
                    $mFound = false;
                    break;
                }
            }
            if (true === $mFound) {
                return $aEntry;
            }
        }
        return NULL;
    }

    /**
     * Adds passed item data to internal cache
     *
     * @param mixed $mId
     * @param array $aData Usually the recordset
     */
    public function addItem($mId, array $aData) {
        if (!$this->_bEnable) {
            return NULL;
        }

        if ($this->_iMaxItemsToCache == count($this->_aItemsCache)) {
            // we have reached the maximum number of cached items, remove first
            // entry
            $keys = array_keys($this->_aItemsCache);
            $firstEntryKey = array_shift($keys);
            unset($this->_aItemsCache[$firstEntryKey]);
        }

        // add entry
        $this->_aItemsCache[$mId] = $aData;
    }

    /**
     * Removes existing cache entry by it's key
     *
     * @param mixed $mId
     */
    public function removeItem($mId) {
        if (!$this->_bEnable) {
            return NULL;
        }

        // remove entry
        if (isset($this->_aItemsCache[$mId])) {
            unset($this->_aItemsCache[$mId]);
        }
    }

    /**
     * Removes multiple existing cache entries by their keys
     *
     * @param array $aIds
     */
    public function removeItems(array $aIds) {
        if (!$this->_bEnable) {
            return NULL;
        }

        // remove entries
        foreach ($aIds as $mId) {
            if (isset($this->_aItemsCache[$mId])) {
                unset($this->_aItemsCache[$mId]);
            }
        }
    }
}

?>