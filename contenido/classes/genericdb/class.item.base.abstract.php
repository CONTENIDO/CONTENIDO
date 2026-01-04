<?php

/**
 * This file contains the abstract base item class of the generic db.
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
 * Class cItemBaseAbstract.
 * Base class with common features for database based items and item
 * collections.
 *
 * NOTE:
 * Because of required downwards compatibility all protected/private member
 * variables or methods don't have a leading underscore.
 *
 * @package    Core
 * @subpackage GenericDB
 */
abstract class cItemBaseAbstract extends cGenericDb
{

    /**
     * @var cDb Database instance, contains the database object
     */
    protected $db;

    /**
     * @var cDb Second DB instance, is required for some additional queries without
     *     losing a current existing query result.
     */
    protected $secondDb;

    /**
     * @var cApiPropertyCollection Property collection instance
     */
    protected $properties;

    /**
     * @var cItemCache Item cache instance
     */
    protected $_oCache;

    /**
     * @var array GenericDB settings, see `$cfg['sql']`
     */
    protected $_settings;

    /**
     * @var string Storage of the source table to use for the information
     */
    protected $table;

    /**
     * @deprecated [2015-05-04] Class variable primaryKey is deprecated, use getPrimaryKeyName() instead
     * @var string Setting of primaryKey name (deprecated)
     */
    private $primaryKey;

    /**
     * @var string Storage of the primary key name
     */
    protected $_primaryKeyName;

    /**
     * @deprecated [2015-05-05] Class variable virgin is deprecated, use negated result of isLoaded() instead
     * @var bool
     */
    private $virgin = true;

    /**
     * @var bool
     */
    protected $_loaded = false;

    /**
     * @var string Storage of the last occurred error
     */
    protected $lasterror = '';

    /**
     * @var string Classname of current instance
     */
    protected $_className;

    /**
     * Constructor to create an instance of this class.
     *
     * Sets some common properties.
     *
     * @param string $table Name of table
     * @param string $primaryKey Primary key of table
     * @param string $className Name of parent class
     * @throws cInvalidArgumentException If table name or primary key is not set
     */
    protected function __construct($table, $primaryKey, $className)
    {
        $cfg = cRegistry::getConfig();
        $table = cSecurity::toString($table);
        $primaryKey = cSecurity::toString($primaryKey);
        $className = cSecurity::toString($className);

        $this->db = cRegistry::getDb();

        if ($table == '') {
            throw new cInvalidArgumentException(sprintf(
                '%s: No table specified. Inherited classes *need* to set a table',
                $className
            ));
        } elseif ($primaryKey == '') {
            throw new cInvalidArgumentException(
                'No primary key specified. Inherited classes *need* to set a primary key'
            );
        }

        $this->_settings = $cfg['sql'];

        // instantiate caching
        $this->_oCache = cItemCache::getInstance($table, $this->_settings['cache'] ?? []);

        $this->table = $table;
        static::_setPrimaryKeyName($primaryKey);
        $this->_className = $className;
    }

    /**
     * Resets class variables back to default
     * This is handy in case a new item is tried to be loaded into this class instance.
     */
    protected function _resetItem()
    {
        $this->_setLoaded(false);
        $this->properties = null;
        $this->lasterror = '';
    }

    /**
     * Escape string for using in SQL-Statement.
     *
     * @param string|mixed $string The string to escape
     * @return string|mixed Escaped string
     */
    public function escape($string)
    {
        return $this->db->escape($string);
    }

    /**
     * Checks if an object is loaded
     * If it is true an object is loaded
     * If it is false then no object is loaded and only load-functions are allowed to be used
     * @return bool Whether an object has been loaded
     */
    public function isLoaded(): bool
    {
        return (bool)$this->_loaded;
    }

    /**
     * Sets loaded state of class
     * If it is true an object is loaded
     * If it is false then no object is loaded and only load-functions are allowed to be used
     *
     * @param bool $loaded Whether an object is loaded
     */
    protected function _setLoaded(bool $loaded)
    {
        $this->_loaded = $loaded;
    }

    /**
     * Magic getter function for deprecated variables primaryKey and virgin
     * This function will be removed when the variables are no longer supported
     *
     * @param string $name Name of the variable that should be accessed
     * @return mixed|null
     */
    public function __get(string $name)
    {
        if ($name === 'primaryKey') {
            return static::getPrimaryKeyName();
        } elseif ($name === 'virgin') {
            return !static::isLoaded();
        }

        return null;
    }

    /**
     * Magic setter function for deprecated variables primaryKey and virgin
     * This function will be removed when the variables are no longer supported
     *
     * @param string $name Name of the variable that should be accessed
     * @param mixed $value Value that should be assigned to variable
     */
    public function __set(string $name, $value)
    {
        if ('primaryKey' === $name) {
            static::_setPrimaryKeyName($value);
        } elseif ('virgin' === $name) {
            static::_setLoaded(!(bool)$value);
        }
    }

    /**
     * Get the table name.
     * @since CONTENIDO 4.10.2
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the primary key name of the corresponding table
     */
    public function getPrimaryKeyName(): string
    {
        return $this->_primaryKeyName;
    }

    /**
     * Prepares the statement for execution and returns it back.
     * The function can be called with a statement and replacement parameters,
     * see {@see cDbDriverHandler::prepare()} for more details.
     *
     * @param ... Multiple parameters where the first is the statement and the further ones the replacements.
     *     See {@see cDbDriverHandler::prepare()} for more details.
     * @return string
     * @throws cDbException
     * @since CONTENIDO 4.10.2
     */
    public function prepare(): string
    {
        $arguments = func_get_args();
        $statement = count($arguments) ? array_shift($arguments) : '';

        return $this->db->prepare($statement, $arguments);
    }

    /**
     * Set the primary key name for class
     * The name must always match the primary key name in database
     */
    protected function _setPrimaryKeyName(string $keyName)
    {
        $this->_primaryKeyName = cSecurity::toString($keyName);
    }

    /**
     * Returns the second database instance, usable to run additional statements
     * without losing current query results.
     */
    protected function _getSecondDBInstance(): cDb
    {
        if (!isset($this->secondDb) || !($this->secondDb instanceof cDb)) {
            $this->secondDb = cRegistry::getDb();
        }
        return $this->secondDb;
    }

    /**
     * Returns properties instance, instantiates it if not done before.
     * NOTE: This function changes always the client variable of property collection instance.
     *
     * @param int $clientId Id of client to use in property collection.
     *         If not passed it uses global variable
     */
    protected function _getPropertiesCollectionInstance(int $clientId = 0): cApiPropertyCollection
    {
        if ($clientId <= 0) {
            $clientId = cRegistry::getClientId();
        }

        // Runtime on-demand allocation of the properties object
        if (!isset($this->properties) || !($this->properties instanceof cApiPropertyCollection)) {
            $this->properties = new cApiPropertyCollection();
        }

        if ($clientId > 0) {
            $this->properties->changeClient($clientId);
        }

        return $this->properties;
    }

}
