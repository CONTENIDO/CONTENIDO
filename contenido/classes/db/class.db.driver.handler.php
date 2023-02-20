<?php

/**
 * This file contains the database driver handler class.
 *
 * @package    Core
 * @subpackage Database
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions for database driver handling in CONTENIDO.
 *
 * @package    Core
 * @subpackage Database
 */
abstract class cDbDriverHandler {

    /**
     *
     * @var string
     */
    const HALT_YES = 'yes';

    /**
     *
     * @var string
     */
    const HALT_NO = 'no';

    /**
     *
     * @var string
     */
    const HALT_REPORT = 'report';

    /**
     *
     * @var string
     */
    const FETCH_NUMERIC = 'numeric';

    /**
     *
     * @var string
     */
    const FETCH_ASSOC = 'assoc';

    /**
     *
     * @var string
     */
    const FETCH_BOTH = 'both';

    /**
     * Loader database driver.
     *
     * @var cDbDriverAbstract|NULL
     */
    protected $_driver = NULL;

    /**
     * Driver type
     *
     * @var string
     */
    protected $_driverType = '';

    /**
     * Default database connection for all instances
     *
     * @var array
     */
    protected static $_defaultDbCfg = [];

    /**
     * Associative list of database connections
     * @var array
     */
    protected static $_connectionCache = [];

    /**
     * Associative list of database tables metadata
     * @var array
     */
    protected static $_metaCache = [];

    /**
     * Database connection configuration for current instance
     *
     * @var array
     */
    protected $_dbCfg = [];

    /**
     * Halt status during occurred errors.
     * Feasible values are
     * - "yes" (halt with message)
     * - "no" (ignore errors quietly)
     * - "report" (ignore error, but spit a warning)
     *
     * @var string
     */
    protected $_haltBehaviour = 'no';

    /**
     * Text to prepend to the halt message
     *
     * @var string
     */
    protected $_haltMsgPrefix = '';

    /**
     * Database connection link id.
     *
     * @var int|null|object|resource
     */
    protected $_linkId = NULL;

    /**
     * Profile data array
     *
     * @var array
     */
    protected static $_profileData = [];

    /**
     * Constructor to create an instance of this class.
     *
     * Sets passed options and connects to the DBMS, if not done before.
     *
     * Uses default connection settings, passed $options['connection'] settings
     * will overwrite connection settings for current instance.
     *
     * @param array $options [optional]
     *         Associative options as follows:
     *         - $options['haltBehavior'] (string) Optional, halt behavior on
     *             occurred errors
     *         - $options['haltMsgPrefix'] (string) Optional, Text to prepend to
     *             the halt message
     *         - $options['enableProfiling'] (bool) Optional, flag to enable
     *             profiling
     *         - $options['connection'] (array) Optional, associative connection
     *             settings
     *         - $options['connection']['host'] (string) Hostname or ip
     *         - $options['connection']['database'] (string) Database name
     *         - $options['connection']['user'] (string) User name
     *         - $options['connection']['password'] (string) User password
     * @throws cDbException
     */
    public function __construct($options = []) {
        // use default connection configuration, but overwrite it by passed options
        $this->_dbCfg = array_merge(self::$_defaultDbCfg, $options);

        // in case we do not have any configuration for database, try to load it from configuration
        if (count($this->_dbCfg) == 0) {
            $cfg = cRegistry::getConfig();
            if (isset($cfg['db']) && count($cfg['db']) > 0) {
                $this->_dbCfg = $cfg['db'];
            } else {
                throw new cDbException("Unable to establish a database connection without options!");
            }
        }

        $this->_setHaltBehaviour($this->_dbCfg['haltBehavior'] ?? '');

        if (isset($this->_dbCfg['haltMsgPrefix']) && is_string($this->_dbCfg['haltMsgPrefix'])) {
            $this->_haltMsgPrefix = $this->_dbCfg['haltMsgPrefix'];
        }

        $cfg = cRegistry::getConfig();
        $this->_driverType = $cfg['database_extension'];

        $this->loadDriver();

        try {
            if ($this->connect() == NULL) {
                $this->setErrorNumber(1);
                $this->setErrorMessage("Could not connect to database");

                throw new cDbException($this->getErrorMessage());
            }
        } catch (Exception $e) {
            throw new cDbException($e->getMessage());
        }
    }

    #region ABSTRACT

    /**
     * Returns error code of last occurred error from database.
     *
     * @return int
     *         database error code
     */
    abstract public function getErrorNumber();

    /**
     * Sets the current error number from database.
     *
     * @param int $errorNumber
     *         current error number
     */
    abstract public function setErrorNumber($errorNumber);

    /**
     * Returns error message of last occurred error from database.
     *
     * @return string
     *         database error message
     */
    abstract public function getErrorMessage();

    /**
     * Sets the current error message from database.
     *
     * @param string $errorMessage
     *         current error message
     */
    abstract public function setErrorMessage($errorMessage);

    /**
     * Returns the query ID resource.
     *
     * @return NULL|resource
     */
    abstract public function getQueryId();

    /**
     * Sets the query ID resource.
     * Do not set it manually unless you know what you are doing.
     *
     * @param NULL|resource $queryId
     *         query ID resource
     */
    abstract public function setQueryId($queryId);

    /**
     * Returns the link ID resource.
     *
     * @return NULL|resource
     */
    abstract public function getLinkId();

    /**
     * Sets the link ID resource.
     * Do not set it manually unless you know what you are doing.
     *
     * @param NULL|resource $linkId
     *         link ID resource
     */
    abstract public function setLinkId($linkId);

    /**
     * Returns the current record data.
     *
     * @return array|false|NULL
     */
    abstract public function getRecord();

    /**
     * Sets the current record data set.
     * Do not set it manually unless you know what you are doing.
     *
     * @param array|false|NULL $record
     *         current record set data
     */
    abstract public function setRecord($record);

    #endregion ABSTRACT

    /**
     * Checks if profiling was enabled via configuration.
     *
     * @return bool
     */
    public function isProfilingEnabled() {
        return cSecurity::toBoolean($this->_dbCfg['enableProfiling'] ?? '0');
    }

    /**
     * Returns the halt behaviour setting.
     *
     * @return string
     */
    public function getHaltBehaviour() {
        return $this->_haltBehaviour;
    }

    /**
     * Loads the database driver and checks its base functionality.
     *
     * @throws cDbException
     */
    public function loadDriver() {
        if ($this->_driver != NULL) {
            return;
        }

        $classNameSuffix = ucfirst($this->_driverType);

        // Driver name, e.g. cDbDriverMysql, cDbDriverMysqli
        $driverName = 'cDbDriver' . $classNameSuffix;

        if (class_exists($driverName) === false) {
            throw new cDbException("Database driver was not found.");
        }

        $this->_driver = new $driverName($this->_dbCfg);

        if (($this->getDriver() instanceof cDbDriverAbstract) === false) {
            $this->_driver = NULL;
            throw new cDbException("Database driver must extend cDbDriverAbstract");
        }

        $this->getDriver()->setHandler($this);

        if ($this->getDriver()->check() === false) {
            throw new cDbException("Database driver check failed.");
        }
    }

    /**
     * Returns the database driver instance.
     *
     * @return cDbDriverAbstract
     */
    public function getDriver() {
        return $this->_driver;
    }

    /**
     * Setter for default database configuration, the connection values.
     *
     * @param array $defaultDbCfg
     */
    public static function setDefaultConfiguration(array $defaultDbCfg) {
        self::$_defaultDbCfg = $defaultDbCfg;
    }

    /**
     * Returns connection from connection cache
     *
     * @param mixed $data
     *         Connection data array or variable
     * @return mixed
     *         Either The connection (object, resource, integer) or NULL
     */
    protected function _getConnection($data) {
        $hash = md5($this->_driverType . '-' . (is_array($data) ? json_encode($data) : (string)$data));

        return self::$_connectionCache[$hash] ?? NULL;
    }

    /**
     * Stores connection in connection cache
     *
     * @param mixed $data
     *         Connection data array
     * @param mixed $connection
     *         The connection to store in cache
     */
    protected function _setConnection($data, $connection) {
        $hash = md5($this->_driverType . '-' . (is_array($data) ? json_encode($data) : (string)$data));
        self::$_connectionCache[$hash] = $connection;
    }

    /**
     * Removes connection from cache
     *
     * @param mixed $connection
     *         The connection to remove in cache
     */
    protected function _removeConnection($connection) {
        foreach (self::$_connectionCache as $hash => $res) {
            if ($res == $connection) {
                unset(self::$_connectionCache[$hash]);

                return;
            }
        }
    }

    /**
     * Adds a entry to the profile data.
     *
     * @param float  $timeStart
     * @param float  $timeEnd
     * @param string $statement
     */
    protected static function _addProfileData($timeStart, $timeEnd, $statement) {
        self::$_profileData[] = [
            'time' => $timeEnd - $timeStart,
            'query' => $statement
        ];
    }

    /**
     * Returns collected profile data.
     *
     * @return array
     *         Profile data array like:
     *         - $arr[$i]['time'] (float) Elapsed time to execute the query
     *         - $arr[$i]['query'] (string) The query itself
     */
    public static function getProfileData() {
        return self::$_profileData;
    }

    /**
     * Prepares the statement for execution and returns it back.
     * Accepts multiple parameter, where the first parameter should be the query
     * and any additional parameter should be the values to replace in format
     * definitions.
     * As an alternative the second parameter could be also a indexed array with
     * values to replace in format definitions.
     *
     * Other option is to call this function with the statement containing named
     * parameter
     * and the second parameter as a associative array with key/value pairs to
     * set in statement.
     *
     * Examples:
     * <pre>
     * // multiple parameter
     * $sql = $obj->prepare('SELECT * FROM `%s` WHERE id = %d', 'tablename', 123);
     *
     * // 2 parameter where the first is the statement with formatting signs and
     * the second the entries array
     * $sql = $obj->prepare('SELECT * FROM `%s` WHERE id = %d', ['tablename', 123]);
     *
     * // 2 parameter where the first is the statement with named parameter and
     * the second the associative entries array
     * $sql = $obj->prepare('SELECT * FROM `:mytab` WHERE id = :myid', ['mytab' => 'tablename', 'myid' => 123]);
     * </pre>
     *
     * Accepts additional unlimited parameter, where the parameter will be
     * replaced against formatting sign in query.
     *
     * @param string $statement
     *         The sql statement to prepare.
     * @return string
     *         The prepared sql statement
     * @throws cDbException
     *         If statement is empty or function is called with less than 2 parameters
     */
    public function prepare($statement, ...$params) {
        // No empty queries
        if (empty($statement)) {
            throw new cDbException('Empty statement!');
        }

        $arguments = func_get_args();
        if (count($arguments) <= 1) {
            throw new cDbException('Wrong number of parameter!');
        }

        array_shift($arguments);

        return $this->_prepareStatement($statement, $arguments);
    }

    /**
     * Prepares the passed statement.
     *
     * @param string $statement
     * @param array  $arguments
     *
     * @return string
     */
    protected function _prepareStatement($statement, array $arguments) {
        if (count($arguments) == 1 && is_array($arguments[0])) {
            $arguments = $arguments[0];
            if (count(array_filter(array_keys($arguments), 'is_string')) > 0) {
                // we have at least one key being string, it is an assoc array
                $statement = $this->_prepareStatementA($statement, $arguments);
            } else {
                // it is an indexed array
                $statement = $this->_prepareStatementF($statement, $arguments);
            }
        } else {
            $statement = $this->_prepareStatementF($statement, $arguments);
        }

        return $statement;
    }

    /**
     * Prepares a statement with parameter for execution.
     *
     * Examples:
     * <pre>
     * $obj->_prepareStatementF('SELECT * FROM `%s` WHERE id = %d', 'tablename', 123);
     * $obj->_prepareStatementF('SELECT * FROM `%s` WHERE id = %d AND user = %d', 'tablename', 123, 3);
     * </pre>
     *
     * @param string $statement
     * @param array  $arguments
     *         Arguments array containing the query with formatting signs and
     *         the entries.
     *
     * @return string
     */
    protected function _prepareStatementF($statement, array $arguments) {
        if (count($arguments) > 0) {
            $arguments = array_map([
                $this, 'escape'
            ], $arguments);
            array_unshift($arguments, $statement);
            $statement = call_user_func_array('sprintf', $arguments);
        }

        return $statement;
    }

    /**
     * Prepares a statement with named parameter for execution.
     *
     * Examples:
     * <pre>
     * // named parameter and associative entries array
     * $sql = $obj->_prepareStatementA(
     *     'SELECT * FROM `:mytab` WHERE id = :myid', ['mytab' => 'tablename', 'myid' => 123]
     * );
     * $sql = $obj->_prepareStatementA(
     *     'SELECT * FROM `:mytab` WHERE id = :myid AND user = :myuser',
     *     ['mytab' => 'tablename', 'myid' => 123, 'myuser' => 3]
     * );
     * </pre>
     *
     * @param string $statement
     * @param array  $arguments
     *         Arguments array containing the query with named parameter and
     *         associative entries array
     *
     * @return string
     */
    protected function _prepareStatementA($statement, array $arguments) {
        if (count($arguments) > 0) {
            foreach ($arguments as $key => $value) {
                $param = ':' . $key;
                if (cSecurity::isInteger($value)) {
                    $statement = preg_replace('/' . $param . '/', cSecurity::toString($value), $statement);
                    $statement = preg_replace('/\'' . $param . '\'/', '\'' . cSecurity::toString($value) . '\'', $statement);
                } else {
                    $param = cSecurity::toString($param);
                    $statement = preg_replace('/' . $param . '/', cSecurity::escapeString($value), $statement);
                    $statement = preg_replace('/\'' . $param . '\'/', '\'' . cSecurity::escapeString($value) . '\'', $statement);
                    $statement = preg_replace('/`' . $param . '`/', '`' . cSecurity::escapeString($value) . '`', $statement);
                }
            }
        }

        return $statement;
    }

    /**
     * Sets the halt behaviour.
     *
     * @param string $haltBehaviour
     * @return void
     */
    protected function _setHaltBehaviour($haltBehaviour) {
        switch ($haltBehaviour) {
            case self::HALT_YES:
                $this->_haltBehaviour = self::HALT_YES;
                break;
            case self::HALT_REPORT:
                $this->_haltBehaviour = self::HALT_REPORT;
                break;
            case self::HALT_NO:
            default:
                $this->_haltBehaviour = self::HALT_NO;
                break;
        }
    }

    /**
     * Establishes a connection to the database server.
     *
     * @return object|resource|int|NULL
     *         value depends on used driver and is NULL in case of an error.
     */
    public function connect() {
        if (isset($this->_dbCfg['connection']) && $this->_linkId = $this->_getConnection($this->_dbCfg['connection'])) {
            return $this->_linkId;
        } else {
            if ($this->_linkId = $this->getDriver()->connect()) {
                $this->_setConnection($this->_dbCfg['connection'], $this->_linkId);

                return $this->_linkId;
            }
        }

        return NULL;
    }

    /**
     * Builds and executes an insert query.
     * String values in passed fields parameter will be escaped automatically.
     *
     * Example:
     * <pre>
     * $db = cRegistry::getDb();
     * $fields = [
     *     'idcatart' => $idcatart,
     *     'idlang' => $lang,
     *     'idclient' => $client,
     *     'code' => "<html>... code n' fun ...</html>",
     * ];
     * $result = $db->insert($cfg['tab']['code'], $fields);
     * </pre>
     *
     * @param string $tableName
     *         The table name
     * @param array  $fields
     *         Associative array of fields to insert
     *
     * @return bool
     * @throws cDbException
     */
    public function insert($tableName, array $fields) {
        $statement = $this->buildInsert($tableName, $fields);

        return $this->query($statement);
    }

    /**
     * Builds and returns a insert query.
     * String values in passed fields parameter will be escaped automatically.
     *
     * Example:
     * <pre>
     * $db = cRegistry::getDb();
     * $fields = [
     *     'idcode' => $idcode,
     *     'idcatart' => $idcatart,
     *     'idlang' => $lang,
     *     'idclient' => $client,
     *     'code' => "<html>... code n' fun ...</html>",
     * ];
     * $statement = $db->buildInsert($cfg['tab']['code'], $fields);
     * $db->query($statement);
     * </pre>
     *
     * @param string $tableName
     *         The table name
     * @param array  $fields
     *         Associative array of fields to insert
     *
     * @return string
     */
    public function buildInsert($tableName, array $fields) {
        return $this->getDriver()->buildInsert($tableName, $fields);
    }

    /**
     * Builds and executes a update query.
     * String values in passed fields and where parameter will be escaped automatically.
     *
     * Example:
     * <pre>
     * $db = cRegistry::getDb();
     * $fields = ['code' => "<html>... some new code n' fun ...</html>"];
     * $whereClauses = ['idcode' => 123];
     * $result = $db->update($cfg['tab']['code'], $fields, $whereClauses);
     * </pre>
     *
     * @param string $tableName
     *         The table name
     * @param array  $fields
     *         Associative array of fields to update
     * @param array  $whereClauses
     *         Associative array of field in where clause.
     *         Multiple entries will be concatenated with AND.
     * @return bool
     * @throws cDbException
     */
    public function update($tableName, array $fields, array $whereClauses) {
        $statement = $this->buildUpdate($tableName, $fields, $whereClauses);

        return $this->query($statement);
    }

    /**
     * Builds and returns an update query.
     * String values in passed fields and where parameter will be escaped automatically.
     *
     * Example:
     * <pre>
     * $db = cRegistry::getDb();
     * $fields = ['code' => "<html>... some new code n' fun ...</html>"];
     * $whereClauses = ['idcode' => 123];
     * $statement = $db->buildUpdate($cfg['tab']['code'], $fields, $whereClauses);
     * $db->query($statement);
     * </pre>
     *
     * @param string $tableName
     *         The table name
     * @param array  $fields
     *         Associative array of fields to update
     * @param array  $whereClauses
     *         Associative array of field in where clause.
     *         Multiple entries will be concatenated with AND
     * @return string
     */
    public function buildUpdate($tableName, array $fields, array $whereClauses) {
        return $this->getDriver()->buildUpdate($tableName, $fields, $whereClauses);
    }

    /**
     * Executes the statement.
     * If called with one parameter, it executes the statement directly.
     *
     * Accepts multiple parameter, where the first parameter should be the query
     * and any additional parameter should be the values to replace in format
     * definitions.
     * As an alternative the second parameter could be also a indexed array with
     * values to replace in format definitions.
     *
     * Other option is to call this function with the statement containing named
     * parameter
     * and the second parameter as a associative array with key/value pairs to
     * set in statement.
     *
     * Examples:
     * <pre>
     * // call with one parameter
     * $obj->query('SELECT * FROM `tablename` WHERE id = 123');
     *
     * // call with multiple parameter
     * $obj->query('SELECT * FROM `%s` WHERE id = %d', 'tablename', 123);
     *
     * // 2 parameter where the first is the statement with formatting signs and
     * the second the entries array
     * $obj->query('SELECT * FROM `%s` WHERE id = %d', ['tablename', 123]);
     *
     * // 2 parameter where the first is the statement with named parameter and
     * the second the associative entries array
     * $obj->query(
     *     'SELECT * FROM `:mytab` WHERE id = :myid', ['mytab' => 'tablename', 'myid' => 123]
     * );
     * </pre>
     *
     * Accepts additional unlimited parameter, where the parameter will be
     * replaced against formatting sign in query.
     *
     * @param string $statement
     *         The SQL statement to execute.
     *
     * @return resource|int|object|bool
     *         database driver, false on error
     * @throws cDbException
     */
    public function query($statement, ...$params) {
        // No empty queries, please, since PHP4 chokes on them
        if ($statement == '') {
            // The empty query string is passed on from the constructor, when
            // calling
            // the class without a query, e.g. in situations '$db = new
            // DB_Sql_Subclass;'
            return false;
        }

        $arguments = func_get_args();
        if (count($arguments) > 1) {
            array_shift($arguments);
            $statement = $this->_prepareStatement($statement, $arguments);
        }

        if (!$this->connect()) {
            return false;
        }

        // new query, discard previous result
        if ($this->getQueryId()) {
            $this->free();
        }

        $timeStart = $this->isProfilingEnabled() === true ? microtime(true) : 0;

        $this->getDriver()->query($statement);

        if ($this->isProfilingEnabled() === true) {
            $timeEnd = microtime(true);
            $this->_addProfileData($timeStart, $timeEnd, $statement);
        }

        if (!$this->getQueryId()) {
            $this->halt($statement);
        }

        // Will return nada if it fails. That's fine.
        return $this->getQueryId();
    }

    /**
     * Fetches the next record set from result set
     *
     * @return bool
     * @throws cDbException|cInvalidArgumentException
     */
    public function nextRecord() {
        if (!$this->getQueryId()) {
            $currentModule = cRegistry::getCurrentModuleId();
            if ($currentModule > 0) {
                $this->halt('next_record called with no query pending in Module ID ' . $currentModule . '.');
            } else {
                $this->halt('next_record called with no query pending.');
            }

            return false;
        }

        return $this->getDriver()->nextRecord();
    }

    /**
     * This method returns the current result set as object or NULL if no result
     * set is left.
     * If optional param $className is set, the result object is an instance of
     * class $className.
     *
     * @param string $className [optional]
     * @return object
     */
    public function getResultObject($className = NULL) {
        return $this->getDriver()->getResultObject($className);
    }

    /**
     * Returns number of affected rows from last executed query (update, delete)
     *
     * @return int
     *         Number of affected rows
     */
    public function affectedRows() {
        return $this->getDriver()->affectedRows();
    }

    /**
     * Returns the number of rows from last executed select query.
     *
     * @return int
     *         The number of rows from last select query result
     */
    public function numRows() {
        return $this->getDriver()->numRows();
    }

    /**
     * Returns the number of fields (columns) from current record set
     *
     * @return int
     *         Number of fields
     */
    public function numFields() {
        return $this->getDriver()->numFields();
    }

    /**
     * Discard the query result
     *
     * @return int
     */
    public function free() {
        return $this->getDriver()->free();
    }

    /**
     * Escape string for using in SQL-Statement.
     *
     * @param string $string
     *         The string to escape
     * @return string
     *         Escaped string
     */
    public function escape($string) {
        if (!$this->getLinkId()) {
            $this->connect();
        }

        return $this->getDriver()->escape($string);
    }

    /**
     * Moves the cursor (position inside current result sets).
     *
     * @param int $pos
     *         The position to move to inside the current result set
     * @return int
     * @throws cDbException|cInvalidArgumentException
     */
    public function seek($pos) {
        $status = $this->getDriver()->seek($pos);
        if ($status == 0) {
            $this->halt("seek($pos) failed: result has " . $this->numRows() . " rows.");
        }

        return $status;
    }

    /**
     * Get last inserted id of given table name
     *
     * @return int
     *         NULL id of table
     * @throws cDbException|cInvalidArgumentException
     */
    public function getLastInsertedId() {
        $lastId = NULL;

        $this->query('SELECT LAST_INSERT_ID() as last_id');
        if ($this->nextRecord()) {
            $lastId = $this->f('last_id');
        }

        return $lastId;
    }

    /**
     * Parses te table structure and generates a metadata from it.
     *
     * @param string $tableName [optional]
     *         The table to get metadata or empty string to retrieve metadata
     *         of all tables
     * @param bool $full [optional]
     *         Flag to load full metadata
     * @return array
     *         Depends on used database and on parameter $full
     */
    public function getMetaData($tableName = '', $full = false) {
        $databaseName = '';
        $key = (string)$databaseName . '_' . $tableName . '_' . (($full) ? '1' : '0');

        if (!isset(self::$_metaCache[$key])) {
            // get meta data
            self::$_metaCache[$key] = $this->getDriver()->getMetaData($tableName, $full);
        }

        return self::$_metaCache[$key];
    }

    /**
     * Returns names of existing tables.
     *
     * @return array|NULL
     *         array containing associative table data as follows or NULL:
     *         - $info[$i]['table_name']
     *         - $info[$i]['tablespace_name']
     *         - $info[$i]['database']
     */
    public function getTableNames() {
        if (!$this->connect()) {
            return NULL;
        }

        return $this->getDriver()->getTableNames();
    }

    /**
     * Returns information about DB server.
     * The return value depends always on used DBMS.
     *
     * @return array|NULL {@see cDbDriverAbstract::getServerInfo()}
     */
    public function getServerInfo() {
        if (!$this->connect()) {
            return NULL;
        }

        return $this->getDriver()->getServerInfo();
    }

    /**
     * Closes the connection and frees the query id.
     */
    public function disconnect() {
        $linkId = $this->getLinkId();

        if (is_resource($linkId)) {
            $this->getDriver()->disconnect();
            $this->_removeConnection($linkId);
        }

        $this->setLinkId(NULL);
        $this->setQueryId(NULL);
    }

    /**
     * Returns the desired field value from current record set.
     *
     * @param mixed $name
     *         The field name or index position
     * @param mixed $default [optional]
     *         The default value to return
     * @return mixed
     *         The value of field
     */
    public function f($name, $default = NULL) {
        $record = $this->getRecord();

        return $record[$name] ?? $default;
    }

    /**
     * Returns current record set as a associative and/or indexed array.
     *
     * @param string $fetchMode [optional]
     *         One of cDbDriverHandler::FETCH_* constants
     * @return array
     */
    public function toArray($fetchMode = self::FETCH_ASSOC) {
        switch ($fetchMode) {
            case self::FETCH_NUMERIC:
            case self::FETCH_ASSOC:
            case self::FETCH_BOTH:
                // donut
                break;
            default:
                $fetchMode = self::FETCH_ASSOC;
                break;
        }

        $result = [];
        if (is_array($this->getRecord())) {
            foreach ($this->getRecord() as $key => $value) {
                if ($fetchMode == self::FETCH_ASSOC && !is_numeric($key)) {
                    $result[$key] = $value;
                } elseif ($fetchMode == self::FETCH_NUMERIC && is_numeric($key)) {
                    $result[$key] = $value;
                } elseif ($fetchMode == self::FETCH_BOTH) {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Returns current record set as a object
     *
     * @return stdClass
     */
    public function toObject() {
        return (object) $this->toArray(self::FETCH_ASSOC);
    }

    /**
     * Error handling
     *
     * Error handler function, delegates passed message to the function
     * reportHalt() if property
     * $this->_haltBehaviour is not set to self::HALT_REPORT.
     *
     * Terminates further script execution if $this->_haltBehaviour is set to
     * self::HALT_YES
     *
     * @param string $message
     *         The message to use for error handling
     * @throws cDbException|cInvalidArgumentException
     */
    public function halt($message) {
        if ($this->_haltBehaviour == self::HALT_REPORT) {
            $this->reportHalt($this->_haltMsgPrefix . $message);
        }

        if ($this->_haltBehaviour == self::HALT_YES) {
            throw new cDbException($message);
        }
    }

    /**
     * Logs passed message, basically the last db error to the error log.
     * Concatenates a detailed error message and invoke PHP's error_log()
     * method.
     *
     * @param string $message
     * @throws cInvalidArgumentException
     */
    public function reportHalt($message) {
        $errorNumber = $this->getErrorNumber();
        $errorMessage = $this->getErrorMessage();

        if (!$errorMessage) {
            $errorMessage = $this->getDriver()->getErrorMessage();
        }

        if (!$errorNumber) {
            $errorNumber = $this->getDriver()->getErrorNumber();
        }

        $message = sprintf("Database failure: %s (%s) - %s\n", $errorNumber, $errorMessage, $message);
        cWarning(__FILE__, __LINE__, $message);
    }

    /**
     * @deprecated Since 4.10.2, use {@see cDbDriverHandler::numRows} instead
     */
    public function num_rows() {
        cDeprecated("The function cDbDriverHandler::num_rows() is deprecated since CONTENIDO 4.10.2, use cDbDriverHandler::numRows() instead.");
        return $this->numRows();
    }

    /**
     * @deprecated Since 4.10.2, use {@see cDbDriverHandler::affectedRows} instead
     */
    public function affected_rows() {
        cDeprecated("The function cDbDriverHandler::affected_rows() is deprecated since CONTENIDO 4.10.2, use cDbDriverHandler::affectedRows() instead.");
        return $this->affectedRows();
    }

    /**
     * @deprecated Since 4.10.2, use {@see cDbDriverHandler::numFields} instead
     */
    public function num_fields() {
        cDeprecated("The function cDbDriverHandler::num_fields() is deprecated since CONTENIDO 4.10.2, use cDbDriverHandler::numFields() instead.");
        return $this->numFields();
    }

    /**
     * @deprecated Since 4.10.2, use {@see cDbDriverHandler::nextRecord} instead
     */
    public function next_record() {
        cDeprecated("The function cDbDriverHandler::next_record() is deprecated since CONTENIDO 4.10.2, use cDbDriverHandler::nextRecord() instead.");
        return $this->nextRecord();
    }
}
