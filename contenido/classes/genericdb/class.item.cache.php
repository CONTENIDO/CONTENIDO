<?php

/**
 * This file contains the generic db item cache class.
 *
 * @package    Core
 * @subpackage GenericDB
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class cItemCache.
 *
 * Implements features to cache entries, usually result sets of Item classes.
 * Contains a list of self instances, where each instance contains cached Items
 * fore one specific table.
 *
 * @package    Core
 * @subpackage GenericDB
 */
class cItemCache
{

    /**
     * @var cItemCache[] List of self instances (cItemCache)
     */
    protected static $instances = [];

    /**
     * @var array Associative cache array
     */
    protected $itemsCache = [];

    /**
     * @var string Table name for current instance
     */
    protected $tableName = '';

    /**
     * @var int Max number of items to cache
     */
    protected $maxItemsToCache = 10;

    /**
     * @var bool Enable caching
     */
    protected $enable = false;

    /**
     * Constructor to create an instance of this class.
     *
     * @param string $tableName Table name
     * @param array $options Options array as follows:
     *         - $options['max_items_to_cache'] = (int) Number of items to cache
     *         - $options['enable'] = (bool) Flag to enable caching
     */
    protected function __construct(string $tableName, array $options = [])
    {
        $this->tableName = $tableName;
        if (isset($options['max_items_to_cache']) && (int)$options['max_items_to_cache'] > 0) {
            $this->maxItemsToCache = (int)$options['max_items_to_cache'];
        }
        if (isset($options['enable']) && is_bool($options['enable'])) {
            $this->enable = $options['enable'];
        }
    }

    /**
     * Prevent cloning
     */
    protected function __clone()
    {
    }

    /**
     * Returns item cache instance, creates it, if not done before.
     * Works as a singleton for one specific table.
     *
     * @param string $tableName Table name
     * @param array $options Options array as follows:
     *         - $options['max_items_to_cache'] = (int) Number of items to cache
     *         - $options['enable'] = (bool) Flag to enable caching
     */
    public static function getInstance(string $tableName, array $options = []): cItemCache
    {
        if (!isset(self::$instances[$tableName])) {
            self::$instances[$tableName] = new self($tableName, $options);
        }
        return self::$instances[$tableName];
    }

    /**
     * Returns items cache list.
     */
    public function getItemsCache(): array
    {
        return $this->itemsCache;
    }

    /**
     * Returns existing entry from cache by its id.
     *
     * @param mixed $id
     */
    public function getItem($id): ?array
    {
        if (!$this->enable) {
            return NULL;
        }

        return $this->itemsCache[$id] ?? null;
    }

    /**
     * Returns existing entry from cache by matching proper value.
     *
     * @param mixed $property
     * @param mixed $value
     */
    public function getItemByProperty($property, $value): ?array
    {
        if (!$this->enable) {
            return NULL;
        }

        // loop through all cached entries and try to find an entry by its property
        foreach ($this->itemsCache as $aEntry) {
            if (isset($aEntry[$property]) && $aEntry[$property] == $value) {
                return $aEntry;
            }
        }
        return NULL;
    }

    /**
     * Returns existing entry from cache by matching properties and their values.
     *
     * @param array $properties Associative key value pairs
     */
    public function getItemByProperties(array $properties): ?array
    {
        if (!$this->enable) {
            return NULL;
        }

        // loop through all cached entries and try to find an entry by its property
        foreach ($this->itemsCache as $aEntry) {
            $mFound = NULL;
            foreach ($properties as $key => $value) {
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
     * @param mixed $id
     * @param array $data Usually the recordset
     */
    public function addItem($id, array $data)
    {
        if (!$this->enable) {
            return;
        }

        if ($this->maxItemsToCache == count($this->itemsCache)) {
            // we have reached the maximum number of cached items, remove first entry
            $keys = array_keys($this->itemsCache);
            $firstEntryKey = array_shift($keys);
            unset($this->itemsCache[$firstEntryKey]);
        }

        // add entry
        $this->itemsCache[$id] = $data;
    }

    /**
     * Removes existing cache entry by its key
     *
     * @param mixed $id
     */
    public function removeItem($id)
    {
        if (!$this->enable) {
            return;
        }

        // remove entry
        if (isset($this->itemsCache[$id])) {
            unset($this->itemsCache[$id]);
        }
    }

    /**
     * Removes multiple existing cache entries by their keys
     *
     * @param array $ids
     */
    public function removeItems(array $ids)
    {
        if (!$this->enable) {
            return;
        }

        // remove entries
        foreach ($ids as $id) {
            if (isset($this->itemsCache[$id])) {
                unset($this->itemsCache[$id]);
            }
        }
    }

}
