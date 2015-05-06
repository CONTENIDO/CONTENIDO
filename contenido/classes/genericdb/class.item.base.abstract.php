<?php

/**
 * This file contains the abstract base item class of the generic db.
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

// Try to load GenericDB database driver
// TODO: check if this is needed any longer because we have autoloading feature
global $cfg;
$driver_filename = cRegistry::getBackendPath() . $cfg['path']['classes'] . 'drivers/' . $cfg['sql']['gdb_driver'] . '/class.gdb.' . $cfg['sql']['gdb_driver'] . '.php';
if (cFileHandler::exists($driver_filename)) {
    include_once($driver_filename);
}

/**
 * Class cItemBaseAbstract.
 * Base class with common features for database based items and item
 * collections.
 *
 * NOTE:
 * Because of required downwards compatibilitiy all protected/private member
 * variables or methods don't have an leading underscore.
 *
 * @package Core
 * @subpackage GenericDB
 */
abstract class cItemBaseAbstract extends cGenericDb {

    /**
     * Database instance, contains the database object
     *
     * @var cDb
     */
    protected $db;

    /**
     * Second DB instance, is required for some additional queries without
     * losing an current existing query result.
     *
     * @var cDb
     */
    protected $secondDb;

    /**
     * Property collection instance
     *
     * @var cApiPropertyCollection
     */
    protected $properties;

    /**
     * Item cache instance
     *
     * @var cItemCache
     */
    protected $_oCache;

    /**
     * GenericDB settings, see $cfg['sql']
     *
     * @var array
     */
    protected $_settings;

    /**
     * Storage of the source table to use for the information
     *
     * @var string
     */
    protected $table;

    /**
     * Setting of primaryKey name (deprecated)
     *
     * @deprecated [2015-05-04] Class variable primaryKey is deprecated, use getPrimaryKeyName() instead
     * @var string
     */
    private $primaryKey;

    /**
     * Storage of the primary key name
     *
     * @var string
     */
    protected $_primaryKeyName;

    /**
     * Checks for the virginity of created objects.
     * If true, the object
     * is virgin and no operations on it except load-functions are allowed.
     *
     * @deprecated [2015-05-05] Class variable virgin is deprecated, use negated result of isLoaded() instead
     * @var bool
     */
    private $virgin = true;

    /**
     * Checks if an object is loaded
     * If it is true an object is loaded
     * If it is false then no object is loaded and only load-functions are allowed to be used
     * @var bool
     */
    protected $_loaded = false;

    /**
     * Storage of the last occured error
     *
     * @var string
     */
    protected $lasterror = '';

    /**
     * Classname of current instance
     *
     * @var string
     */
    protected $_className;

    /**
     * Sets some common properties
     *
     * @param string $sTable
     *         Name of table
     * @param string $sPrimaryKey
     *         Primary key of table
     * @param string $sClassName
     *         Name of parent class
     * @throws cInvalidArgumentException
     *         If table name or primary key is not set
     */
    protected function __construct($sTable, $sPrimaryKey, $sClassName) {
        global $cfg;

        $this->db = cRegistry::getDb();

        if ($sTable == '') {
            $sMsg = "$sClassName: No table specified. Inherited classes *need* to set a table";
            throw new cInvalidArgumentException($sMsg);
        } elseif ($sPrimaryKey == '') {
            $sMsg = "No primary key specified. Inherited classes *need* to set a primary key";
            throw new cInvalidArgumentException($sMsg);
        }

        $this->_settings = $cfg['sql'];

        // instantiate caching
        $aCacheOpt = (isset($this->_settings['cache'])) ? $this->_settings['cache'] : array();
        $this->_oCache = cItemCache::getInstance($sTable, $aCacheOpt);

        $this->table = $sTable;
        static::_setPrimaryKeyName($sPrimaryKey);
        $this->_className = $sClassName;
    }

    /**
     * Resets class variables back to default
     * This is handy in case a new item is tried to be loaded into this class instance.
     */
    protected function _resetItem() {
        $this->_setLoaded(false);
        $this->properties = null;
        $this->lasterror = '';
    }

    /**
     * Escape string for using in SQL-Statement.
     *
     * @param string $sString
     *         The string to escape
     * @return string
     *         Escaped string
     */
    public function escape($sString) {
        return $this->db->escape($sString);
    }

    /**
     * Checks if an object is loaded
     * If it is true an object is loaded
     * If it is false then no object is loaded and only load-functions are allowed to be used
     * @return bool Whether an object has been loaded
     */
    public function isLoaded() {
        return (bool) $this->_loaded;
    }

    /**
     * Sets loaded state of class
     * If it is true an object is loaded
     * If it is false then no object is loaded and only load-functions are allowed to be used
     * @param bool $value Whether an object is loaded
     */
    protected function _setLoaded($value) {
        $this->_loaded = (bool) $value;
    }

    /**
     * Magic getter function for deprecated variables primaryKey and virgin
     * This function will be removed when the variables are no longer supported
     * @param string $name Name of the variable that should be accessed
     * @return mixed
     */
    public function __get($name) {
        if ('primaryKey' === $name) {
            return static::getPrimaryKeyName();
        }
        if ('virgin' === $name) {
            return !static::isLoaded();
        }
    }

    /**
     * Magic setter function for deprecated variables primaryKey and virgin
     * This function will be removed when the variables are no longer supported
     * @param string $name Name of the variable that should be accessed
     * @param mixed $value Value that should be assigned to variable
     */
    public function __set($name, $value) {
        if ('primaryKey' === $name) {
            static::_setPrimaryKeyName($value);
        } else if ('virgin' === $name) {
            static::_setLoaded(!(bool) $value);
        }
    }

    /**
     * Get the primary key name in database
     * @return string
     *         Name of primary key
     */
    public function getPrimaryKeyName() {
        return (string) $this->_primaryKeyName;
    }

    /**
     * Set the primary key name for class
     * The name must always match the primary key name in database
     */
    protected function _setPrimaryKeyName($keyName) {
        $this->_primaryKeyName = (string) $keyName;
    }

    /**
     * Returns the second database instance, usable to run additional statements
     * without losing current query results.
     *
     * @return cDb
     */
    protected function _getSecondDBInstance() {
        if (!isset($this->secondDb) || !($this->secondDb instanceof cDb)) {
            $this->secondDb = cRegistry::getDb();
        }
        return $this->secondDb;
    }

    /**
     * Returns properties instance, instantiates it if not done before.
     * NOTE: This funtion changes always the client variable of property
     * collection instance.
     *
     * @param int $idclient [optional]
     *         Id of client to use in property collection.
     *         If not passed it uses global variable
     * @return cApiPropertyCollection
     */
    protected function _getPropertiesCollectionInstance($idclient = 0) {
        global $client;

        if ((int) $idclient <= 0) {
            $idclient = $client;
        }

        // Runtime on-demand allocation of the properties object
        if (!isset($this->properties) || !($this->properties instanceof cApiPropertyCollection)) {
            $this->properties = new cApiPropertyCollection();
        }

        if ((int) $idclient > 0) {
            $this->properties->changeClient($idclient);
        }

        return $this->properties;
    }
}
