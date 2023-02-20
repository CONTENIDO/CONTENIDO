<?php

/**
 * This file contains the trait for retrieving and or processing
 * chunks of database results.
 *
 * @package Core
 * @subpackage Database
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Chunk trait for usage in classes extending {@see ItemCollection}.
 *
 * This trait is meant only for usage in ItemCollection classes, therefore
 * it requires following methods to be implemented by a ItemCollection class
 * using this trait:
 * @method ItemCollection|string getTable
 * @method ItemCollection|string getPrimaryKeyName
 * @method ItemCollection|void loadByRecordSet(array)
 *
 * @package Core
 * @subpackage Database
 */
trait cItemCollectionChunkTrait
{

    /**
     * Database instance.
     *
     * @var cDb
     */
    private static $_db;

    /**
     * Loads chunks of results from the database, fills the results list
     * with the created Item instances, and calls the provided callback
     * function with each result block.
     *
     * @param array    $ids      List of ids (primary keys) to load the data
     * @param callable $callback The callback function
     *                           First parameter: (Item[]) Results
     *                           Second parameter: (int) page
     * @param int $size     The size for each block
     *
     * @return bool
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function fetchChunkObjectsByIds(array $ids, callable $callback, int $size = 100): bool
    {
        return $this->_fetchChunksByIds($ids, $callback, $size, true);
    }

    /**
     * Loads chunks of results from the database, fills the results list
     * with the records, and calls the provided callback
     * function with each result block.
     *
     * @param array    $ids      List of ids (primary keys) to load the data
     * @param callable $callback The callback function
     *                           First parameter: (array[]) Results
     *                           Second parameter: (int) page
     * @param int $size     The size for each block
     *
     * @return bool
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function fetchChunkResultsByIds(array $ids, callable $callback, int $size = 100): bool
    {
        return $this->_fetchChunksByIds($ids, $callback, $size, false);
    }

    /**
     * @param array $ids
     * @param callable $callback
     * @param int $size
     * @param bool $createObjects Flag to fill the result list with Item instances
     *                           (true) or to use the records (false).
     *
     * @return bool
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    protected function _fetchChunksByIds(
        array $ids, callable $callback, int $size, bool $createObjects
    ): bool
    {
        $chunks = array_chunk($ids, $size);

        $db = $this->getDbInstance();

        // Loop through each chunk, build and run the query,
        // fill the results with the created class instances,
        // or with the records, and call the callback.
        foreach ($chunks as $page => $chunk) {
            $this->_prepareChunkIds($chunk);
            $in = implode("', '", $chunk);
            $sql = "SELECT * FROM `%s` WHERE `%s` IN ('" . $in . "')";
            $db->query($sql, $this->getTable(), $this->getPrimaryKeyName());
            $results = [];
            while ($db->nextRecord()) {
                $record = $db->getRecord();
                if ($createObjects) {
                    /* @var $obj Item */
                    $obj = new $this->_itemClass();
                    $obj->loadByRecordSet($record);
                    $results[] = $obj;
                } else {
                    $results[] = $record;
                }
            }

            // Call the callback with the result block, and stop
            // further processing, if callback returns false.
            if ($callback($results, $page) === false) {
                return false;
            }

            unset($results);
        }

        return true;
    }

    /**
     * Ensures that ids of type string are properly escaped.
     *
     * @param array $ids
     *
     * @return void
     */
    protected function _prepareChunkIds(array &$ids)
    {
        $db = $this->getDbInstance();
        $ids = array_map(function($id) use ($db) {
            if (!empty($id) && !is_numeric($id) && is_string($id)) {
                return $db->escape($id);
            } else {
                return $id;
            }
        }, $ids);
    }

    /**
     * Returns the database instance, creates it once, if not done before.
     *
     * @return cDb
     */
    private function getDbInstance(): cDb
    {
        if (!self::$_db) {
            self::$_db = cRegistry::getDb();
        }
        return self::$_db;
    }

}