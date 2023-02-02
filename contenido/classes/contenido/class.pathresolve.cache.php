<?php

/**
 * This file contains the path resolve cache collection and item class and its helper.
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
 * Pathresolve cache static helper class
 *
 * @package Core
 * @subpackage Util
 */
class cApiPathresolveCacheHelper {

    /**
     * Flag to state state about created heap table.
     *
     * @var bool
     */
    protected static $_tableCreated = false;

    /**
     * Checks configuration of heap table creation, it's existance and creates
     * it if needed.
     *
     * @param array $cfg
     *         Global CONTENIDO config array
     *
     * @throws cDbException
     */
    public static function setup($cfg) {
        if (isset($cfg['pathresolve_heapcache']) && true === $cfg['pathresolve_heapcache'] && false === self::$_tableCreated) {
            $db = cRegistry::getDb();
            $tableName = $cfg['sql']['sqlprefix'] . '_pathresolve_cache';

            $sql = "SHOW TABLES LIKE '" . $db->escape($tableName) . "'";
            $db->query($sql);

            if (!$db->nextRecord()) {
                // Important: This is really a hack! Don't use
                // pathresolve_heapcache if you are
                // not sure what it does.
                // @TODO: pls insert to this create table statetment MAX_ROWS.
                $sql = 'CREATE TABLE `' . $db->escape($tableName) . '` (
                           `idpathresolvecache` INT(10) NOT NULL AUTO_INCREMENT,
                           `path` VARCHAR(255) NOT NULL,
                           `idcat` INT(10) NOT NULL,
                           `idlang` INT(10) NOT NULL,
                           `lastcached` INT(10) NOT NULL,
                            PRIMARY KEY (`idpathresolvecache`)
                        ) ENGINE = HEAP;';
                $db->query($sql);
            }
            self::$_tableCreated = true;
        }
    }

}

/**
 * Pathresolve cache collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @method cApiPathresolveCache createNewItem
 * @method cApiPathresolveCache|bool next
 */
class cApiPathresolveCacheCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        $cfg = cRegistry::getConfig();
        cApiPathresolveCacheHelper::setup($cfg);
        parent::__construct($cfg['sql']['sqlprefix'] . '_pathresolve_cache', 'idpathresolvecache');
        $this->_setItemClass('cApiPathresolveCache');
    }

    /**
     * Creates a pathresolve cache entry.
     *
     * @param string $path
     * @param int    $idcat
     * @param int    $idlang
     * @param string $lastcached [optional]
     * @return cApiPathresolveCache
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($path, $idcat, $idlang, $lastcached = '') {
        $oItem = $this->createNewItem();

        if (empty($lastcached)) {
            $lastcached = time();
        }

        $oItem->set('path', $path, false);
        $oItem->set('idcat', $idcat, false);
        $oItem->set('idlang', $idlang, false);
        $oItem->set('lastcached', $lastcached, false);
        $oItem->store();

        return $oItem;
    }

    /**
     * Returns a last cached entry by path and language.
     *
     * @param string $path
     * @param int    $idlang
     * @return cApiPathresolveCache|NULL
     * @throws cDbException
     * @throws cException
     */
    public function fetchLatestByPathAndLanguage($path, $idlang) {
        $where = $this->db->prepare("path LIKE '%s' AND idlang = %d", $path, $idlang);
        $this->select($where, '', 'lastcached DESC', '1');
        return $this->next();
    }

    /**
     * Deletes entry by category and language.
     *
     * @param int $idcat
     * @param int $idlang
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function deleteByCategoryAndLanguage($idcat, $idlang) {
        $where = $this->db->prepare('idcat = %d AND idlang = %d', $idcat, $idlang);
        $this->select($where);
        while (($oCode = $this->next()) !== false) {
            $this->delete($oCode->get('idpathresolvecache'));
        }
    }

}

/**
 * Pathresolve cache item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiPathresolveCache extends Item
{
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
        $cfg = cRegistry::getConfig();
        cApiPathresolveCacheHelper::setup($cfg);
        parent::__construct($cfg['sql']['sqlprefix'] . '_pathresolve_cache', 'idpathresolvecache');
        $this->setFilters([], []);
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Checks if item's cache time has expired.
     *
     * @throws cException If item has not been loaded before
     * @return bool
     */
    public function isCacheTimeExpired() {
        if (!$this->isLoaded()) {
            throw new cException('Item not loaded!');
        }
        $cfg = cRegistry::getConfig();
        $cacheTime = (isset($cfg['pathresolve_heapcache_time'])) ? $cfg['pathresolve_heapcache_time'] : 60 * 60 * 24;
        return $this->get('lastcached') + $cacheTime < time();
    }

    /**
     * User-defined setter for pathresolve cache fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
     *
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idcat':
            case 'idlang':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

}
