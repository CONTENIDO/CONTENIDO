<?php

/**
 * This file contains the path resolve cache collection and item class and its helper.
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
 * Pathresolve cache static helper class
 *
 * @package    Core
 * @subpackage Util
 */
class cApiPathresolveCacheHelper
{

    /**
     * Flag to state about created heap table.
     *
     * @var bool
     */
    protected static $_tableCreated = false;

    /**
     * Checks configuration of heap table creation, its existence and creates it if needed.
     *
     * @param array $cfg The CONTENIDO configuration array
     * @throws cDbException
     */
    public static function setup(array $cfg)
    {
        if (
            isset($cfg['pathresolve_heapcache'])
            && $cfg['pathresolve_heapcache'] === true
            && self::$_tableCreated === false
        ) {
            $db = cRegistry::getDb();
            $tableName = $cfg['sql']['sqlprefix'] . '_pathresolve_cache';

            $db->query("SHOW TABLES LIKE '%s'", $tableName);

            if (!$db->nextRecord()) {
                // Important: This is really a hack! Don't use
                // pathresolve_heapcache if you are
                // not sure what it does.
                // @TODO: pls insert to this create table statetment MAX_ROWS.
                $db->query('CREATE TABLE `%s` (
                           `idpathresolvecache` INT(10) NOT NULL AUTO_INCREMENT,
                           `path` VARCHAR(255) NOT NULL,
                           `idcat` INT(10) NOT NULL,
                           `idlang` INT(10) NOT NULL,
                           `lastcached` INT(10) NOT NULL,
                            PRIMARY KEY (`idpathresolvecache`)
                        ) ENGINE = HEAP;',
                    $tableName
                );
            }
            self::$_tableCreated = true;
        }
    }

}

/**
 * Pathresolve cache collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiPathresolveCache>
 */
class cApiPathresolveCacheCollection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function __construct()
    {
        $cfg = cRegistry::getConfig();
        cApiPathresolveCacheHelper::setup($cfg);
        parent::__construct($cfg['sql']['sqlprefix'] . '_pathresolve_cache', 'idpathresolvecache');
        $this->_setItemClass('cApiPathresolveCache');
    }

    /**
     * Creates a pathresolve cache entry.
     *
     * @param string $path
     * @param int $categoryId
     * @param int $languageId
     * @param string $lastCached [optional]
     * @return cApiPathresolveCache
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($path, $categoryId, $languageId, $lastCached = '')
    {
        $oItem = $this->createNewItem();

        if (empty($lastCached)) {
            $lastCached = time();
        }

        $oItem->set('path', $path, false);
        $oItem->set('idcat', $categoryId, false);
        $oItem->set('idlang', $languageId, false);
        $oItem->set('lastcached', $lastCached, false);
        $oItem->store();

        return $oItem;
    }

    /**
     * Returns a last cached entry by path and language.
     *
     * @param string $path
     * @param int $languageId
     * @throws cDbException|cException
     */
    public function fetchLatestByPathAndLanguage($path, $languageId): ?cApiPathresolveCache
    {
        $where = $this->db->prepare("path LIKE '%s' AND idlang = %d", $path, $languageId);
        $this->select($where, '', 'lastcached DESC', '1');
        return $this->next();
    }

    /**
     * Deletes entry by category and language.
     *
     * @param int $categoryId
     * @param int $languageId
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function deleteByCategoryAndLanguage($categoryId, $languageId)
    {
        $where = $this->db->prepare('idcat = %d AND idlang = %d', $categoryId, $languageId);
        $this->select($where);
        while ($oCode = $this->next()) {
            $this->delete($oCode->get('idpathresolvecache'));
        }
    }

}

/**
 * Pathresolve cache item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiPathresolveCache extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        $cfg = cRegistry::getConfig();
        cApiPathresolveCacheHelper::setup($cfg);
        parent::__construct($cfg['sql']['sqlprefix'] . '_pathresolve_cache', 'idpathresolvecache');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Checks if item's cache time has expired.
     *
     * @throws cException If item has not been loaded before
     */
    public function isCacheTimeExpired(): bool
    {
        if (!$this->isLoaded()) {
            throw new cException('Item not loaded!');
        }
        $cfg = cRegistry::getConfig();
        $cacheTime = $cfg['pathresolve_heapcache_time'] ?? 60 * 60 * 24;
        return $this->get('lastcached') + $cacheTime < time();
    }

    /**
     * User-defined setter for pathresolve cache fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idcat':
            case 'idlang':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

}
