<?php

/**
 * This file contains the database driver handler class.
 *
 * @package    Core
 * @subpackage Database
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions for database driver handling in CONTENIDO.
 *
 * @package    Core
 * @subpackage Database
 */
abstract class cDbDriverHandler
{

    /**
     * @var string
     */
    public const HALT_YES = 'yes';

    /**
     * @var string
     */
    public const HALT_NO = 'no';

    /**
     * @var string
     */
    public const HALT_REPORT = 'report';

    /**
     * @var string
     */
    public const FETCH_NUMERIC = 'numeric';

    /**
     * @var string
     */
    public const FETCH_ASSOC = 'assoc';

    /**
     * @var string
     */
    public const FETCH_BOTH = 'both';

    /**
     * Loader database driver.
     */
    protected ?cDbDriverAbstract $_driver = NULL;

    /**
     * Logger instance.
     */
    protected ?cLog $logger = null;

    /**
     * Driver type
     */
    protected string $_driverType = '';

    /**
     * Default database connection for all instances
     */
    protected static array $_defaultDbCfg = [];

    /**
     * Associative list of database connections
     */
    protected static array $_connectionCache = [];

    /**
     * Associative list of database tables metadata
     */
    protected static array $_metaCache = [];

    /**
     * Database connection configuration for current instance
     */
    protected array $_dbCfg = [];

    /**
     * Halt status during occurred errors.
     * Feasible values are
     * - "yes" (halt with message)
     * - "no" (ignore errors quietly)
     * - "report" (ignore error, but spit a warning)
     */
    protected string $_haltBehaviour = 'no';

    /**
     * Text to prepend to the halt message
     */
    protected string $_haltMsgPrefix = '';

    /**
     * Flag to enable profiling.
     * @since CONTENIDO 4.10.2
     */
    protected bool $profilingEnabled = false;

    /**
     * Flag to log statements.
     * @since CONTENIDO 4.10.2
     */
    protected bool $loggingEnabled = false;

    /**
     * @var int|null|object|resource|mixed Database connection link id.
     */
    protected $_linkId = NULL;

    /**
     * Profile data array
     */
    protected static array $_profileData = [];

    /**
     * Constructor to create an instance of this class.
     *
     * Sets passed options and connects to the DBMS, if not done before.
     *
     * Uses default connection settings, passed $options['connection'] settings
     * will overwrite connection settings for current instance.
     *
     * @param array{
     *     connection: array{
     *         host: string,
     *         database: string,
     *         user: string,
     *         password: string,
     *         charset: string,
     *         options: array<int, string>
     *     },
     *     engine: string,
     *     haltBehavior: 'yes'|'no'|'report',
     *     haltMsgPrefix: string,
     *     enableProfiling: bool,
     *     logStatements: bool
     * } $options The database connection configuration array is optional,
     *      if ommited, it will be loaded from `$cfg['db']`.
     * @throws cDbException
     */
    public function __construct(array $options = [])
    {
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

        if (isset($this->_dbCfg['enableProfiling']) && is_bool($this->_dbCfg['enableProfiling'])) {
            $this->profilingEnabled = $this->_dbCfg['enableProfiling'];
        }

        if (isset($this->_dbCfg['logStatements']) && is_bool($this->_dbCfg['logStatements'])) {
            $this->loggingEnabled = $this->_dbCfg['logStatements'];
        }

        $cfg = cRegistry::getConfig();
        $this->_driverType = $cfg['database_extension'];

        $this->loadDriver();

        try {
            if (!$this->connect()) {
                $this->setErrorNumber(1);
                $this->setErrorMessage("Could not connect to database");

                $error = error_get_last();
                $message = $error['message'] ?? $this->getErrorMessage();
                throw new cDbException($message, $this->getErrorNumber());
            }
        } catch (Throwable $e) {
            // Catch all possible errors
            throw new cDbException($e->getMessage(), $e->getCode(), $e);
        }
    }

    #region ABSTRACT

    /**
     * Returns error code of last occurred error from database.
     */
    abstract public function getErrorNumber(): int;

    /**
     * Sets the current error number from database.
     */
    abstract public function setErrorNumber(int $errorNumber);

    /**
     * Returns error message of last occurred error from database.
     */
    abstract public function getErrorMessage(): string;

    /**
     * Sets the current error message from database.
     *
     * @param string $errorMessage Current error message
     */
    abstract public function setErrorMessage(string $errorMessage);

    /**
     * Returns the query ID resource.
     *
     * @return NULL|resource|mixed
     */
    abstract public function getQueryId();

    /**
     * Sets the query ID resource.
     * Do not set it manually unless you know what you are doing.
     *
     * @param NULL|resource|mixed $queryId Query ID resource
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
     * @param NULL|resource|mixed $linkId Link ID, resource, or any other type.
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
     * @param array|false|NULL $record Current record set data
     */
    abstract public function setRecord($record);

    #endregion ABSTRACT

    /**
     * Checks if profiling is enabled.
     */
    public function isProfilingEnabled(): bool
    {
        return $this->profilingEnabled;
    }

    /**
     * Checks if logging of queries is enabled.
     * @since CONTENIDO 4.10.2
     */
    public function isLoggingEnabled(): bool
    {
        return $this->loggingEnabled;
    }

    /**
     * Returns the halt behaviour setting.
     */
    public function getHaltBehaviour(): string
    {
        return $this->_haltBehaviour;
    }

    /**
     * Loads the database driver and checks its base functionality.
     *
     * @throws cDbException
     */
    public function loadDriver(): void
    {
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
     */
    public function getDriver(): ?cDbDriverAbstract
    {
        return $this->_driver;
    }

    /**
     * Setter for default database configuration, the connection values.
     */
    public static function setDefaultConfiguration(array $defaultDbCfg): void
    {
        self::$_defaultDbCfg = $defaultDbCfg;
    }

    /**
     * Returns connection from connection cache
     *
     * @param array $data^Connection data array
     * @return mixed Either The connection or `null`, see {@see cDbDriverAbstract::connect()}.
     */
    protected function _getConnection(array $data): mixed
    {
        if (empty($data)) {
            return NULL;
        }
        $hash = md5($this->_driverType . '-' . json_encode($data));

        return self::$_connectionCache[$hash] ?? NULL;
    }

    /**
     * Stores connection in connection cache
     *
     * @param array $data Connection data array
     * @param mixed $connection The connection to store in cache, see {@see cDbDriverAbstract::connect()}.
     */
    protected function _setConnection(array $data, mixed $connection): void
    {
        $hash = md5($this->_driverType . '-' . json_encode($data));
        self::$_connectionCache[$hash] = $connection;
    }

    /**
     * Removes connection from cache
     *
     * @param mixed $connection The connection to remove in cache, see {@see cDbDriverAbstract::connect()}.
     */
    protected function _removeConnection(mixed $connection): void
    {
        foreach (self::$_connectionCache as $hash => $res) {
            if ($res == $connection) {
                unset(self::$_connectionCache[$hash]);

                return;
            }
        }
    }

    /**
     * Adds an entry to the profile data.
     */
    protected static function _addProfileData(float|int $timeStart, float|int $timeEnd, string $statement): void
    {
        self::$_profileData[] = [
            'time' => $timeEnd - $timeStart,
            'query' => $statement
        ];
    }

    /**
     * Returns collected profile data.
     *
     * @return array<int, <array{
     *     time: float,
     *     query: string
     * }> Profile data array containing execution metrics.
     */
    public static function getProfileData(): array
    {
        return self::$_profileData;
    }

    /**
     * Prepares the statement for execution and returns it back.
     *
     * This method accepts a SQL statement in `$statement` and optional replacement values in `$params`.
     * Replacement values may be supplied in one of the following ways:
     *
     * - As multiple additional arguments:
     *     $db->prepare($sql, $val1, $val2, ...);
     * - As a single indexed array:
     *     $db->prepare($sql, [$val1, $val2]);
     * - As a single associative array (named parameters):
     *     $db->prepare('... :name ...', ['name' => $value]);
     *
     * For indexed parameters the values will be escaped and inserted using `sprintf`-style placeholders
     * (e.g. %s, %d). For associative (named) parameters the method substitutes occurrences of `:key`,
     * quoted `':key'` and backticked ```:key``` appropriately and applies escaping.
     *
     * Examples:
     * <pre>
     * $sql = $obj->prepare('SELECT * FROM `%s` WHERE id = %d', 'tablename', 123);
     * $sql = $obj->prepare('SELECT * FROM `%s` WHERE id = %d', ['tablename', 123]);
     * $sql = $obj->prepare('SELECT * FROM `:mytab` WHERE id = :myid', ['mytab' => 'tablename', 'myid' => 123]);
     * </pre>
     *
     * @param string $statement The SQL statement to prepare.
     * @param mixed ...$arguments Optional replacement parameters (variadic).
     *      Can be individual values or a single array (indexed or associative) as described above.
     * @return string The prepared SQL statement.
     * @throws cDbException If `$statement` is empty or required parameters are missing.
     */
    public function prepare(string $statement, ...$arguments): string
    {
        // No empty queries
        if (empty($statement)) {
            throw new cDbException('Empty statement!');
        }

        if (count($arguments) < 1) {
            throw new cDbException('Wrong number of parameter!');
        }

        return $this->_prepareStatement($statement, $arguments);
    }

    /**
     * Prepares the provided statement.
     * @see cDbDriverHandler::prepare()
     */
    protected function _prepareStatement(string $statement, array $arguments): string
    {
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
     * @param array $arguments Arguments array containing the query with formatting signs and the entries.
     */
    protected function _prepareStatementF(string $statement, array $arguments): string
    {
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
     * @param array $arguments Arguments array containing the query with named parameter and associative entries array
     */
    protected function _prepareStatementA(string $statement, array $arguments): string
    {
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

        return $statement;
    }

    /**
     * Sets the halt behaviour.
     */
    protected function _setHaltBehaviour(string $haltBehaviour): void
    {
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
    public function connect()
    {
        $connectionCfg = is_array($this->_dbCfg['connection']) ? $this->_dbCfg['connection'] : [];

        // Get connection from cache poll
        $this->_linkId = $this->_getConnection($connectionCfg);

        if (!$this->_linkId) {
            // Create new connection (fallback)
            $newConnection  = $this->getDriver()->connect();
            if ($newConnection) {
                $this->_linkId = $newConnection;
                $this->_setConnection($connectionCfg, $this->_linkId);

                return $this->_linkId;
            }
        }

        return $this->_linkId;
    }

    /**
     * @see cDbDriverAbstract::buildInsert()
     */
    public function buildInsert(string $tableName, array $fields): string
    {
        return $this->getDriver()->buildInsert($tableName, $fields);
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
     * $result = $db->insert(cDb::getTableName('code'), $fields);
     * </pre>
     *
     * @param string $tableName The table name
     * @param array $fields Associative array of fields to insert
     * @return mixed
     * @throws cDbException
     */
    public function insert(string $tableName, array $fields)
    {
        $statement = $this->buildInsert($tableName, $fields);

        return $this->query($statement);
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
     * $result = $db->update(cDb::getTableName('code'), $fields, $whereClauses);
     * </pre>
     *
     * @param string $tableName The table name
     * @param array $fields Associative array of fields to update
     * @param array $whereClauses Associative array of field in where clause.
     *         Multiple entries will be concatenated with AND.
     * @return mixed
     * @throws cDbException
     */
    public function update(string $tableName, array $fields, array $whereClauses)
    {
        $statement = $this->buildUpdate($tableName, $fields, $whereClauses);

        return $this->query($statement);
    }

    /**
     * @see cDbDriverAbstract::buildUpdate()
     */
    public function buildUpdate(string $tableName, array $fields, array $whereClauses): string
    {
        return $this->getDriver()->buildUpdate($tableName, $fields, $whereClauses);
    }

    /**
     * Executes the statement.
     *
     * If called with one parameter, it executes the statement directly.
     * If called with multiple parameters, it prepares the statement first and then executes it.
     *
     * This function behaves like {@see cDbDriverHandler::prepare()} when called with multiple parameters.
     *
     * Examples:
     * <pre>
     * $obj->query('SELECT * FROM `tablename` WHERE id = 123');
     * $obj->query('SELECT * FROM `%s` WHERE id = %d', 'tablename', 123);
     * $obj->query('SELECT * FROM `%s` WHERE id = %d', ['tablename', 123]);
     * $obj->query(
     *     'SELECT * FROM `:mytab` WHERE id = :myid', ['mytab' => 'tablename', 'myid' => 123]
     * );
     * </pre>
     *
     * @param string $statement The SQL statement to execute.
     * @param mixed ...$arguments Optional replacement parameters (variadic).
     *      Can be individual values or a single array (indexed or associative) as described above.
     * @return resource|int|object|bool Database driver, false on error
     * @throws cDbException
     */
    public function query(string $statement, ...$arguments)
    {
        // No empty queries, please, since PHP4 chokes on them
        if ($statement == '') {
            // The empty query string is passed on from the constructor, when calling
            // the class without a query, e.g. in situations '$db = new DB_Sql_Subclass;'
            return false;
        }

        if (count($arguments) > 0) {
            $statement = $this->_prepareStatement($statement, $arguments);
        }

        if (!$this->connect()) {
            return false;
        }

        // new query, discard previous result
        if ($this->getQueryId()) {
            $this->free();
        }

        if ($this->isLoggingEnabled()) {
            $this->logStatement($statement);
        }

        $timeStart = $this->isProfilingEnabled() ? microtime(true) : 0;

        $this->getDriver()->query($statement);

        if ($this->isProfilingEnabled()) {
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
     * @throws cDbException
     */
    public function nextRecord(): bool
    {
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
     * @see cDbDriverAbstract::getResultObject()
     * @return object
     */
    public function getResultObject(?string $className = NULL)
    {
        return $this->getDriver()->getResultObject($className);
    }

    /**
     * @see cDbDriverAbstract::affectedRows()
     */
    public function affectedRows(): int
    {
        return $this->getDriver()->affectedRows();
    }

    /**
     * @see cDbDriverAbstract::numRows()
     */
    public function numRows(): int
    {
        return $this->getDriver()->numRows();
    }

    /**
     * @see cDbDriverAbstract::numFields()
     */
    public function numFields(): int
    {
        return $this->getDriver()->numFields();
    }

    /**
     * @see cDbDriverAbstract::free()
     */
    public function free()
    {
        $this->getDriver()->free();
    }

    /**
     * @see cDbDriverAbstract::escape()
     */
    public function escape($string)
    {
        if (!$this->getLinkId()) {
            $this->connect();
        }

        return $this->getDriver()->escape($string);
    }

    /**
     * @see cDbDriverAbstract::seek()
     * @throws cDbException
     */
    public function seek(int $pos): int
    {
        $status = $this->getDriver()->seek($pos);
        if ($status == 0) {
            $this->halt("seek($pos) failed: result has " . $this->numRows() . " rows.");
        }

        return $status;
    }

    /**
     * Get last inserted id of given table name
     *
     * @return ?int Id of last inserted record
     * @throws cDbException
     */
    public function getLastInsertedId(): ?int
    {
        $lastId = NULL;

        $this->query('SELECT LAST_INSERT_ID() AS last_id');
        if ($this->nextRecord()) {
            $lastId = (int) $this->f('last_id');
        }

        return $lastId;
    }

    /**
     * @see cDbDriverAbstract::getMetaData()
     */
    public function getMetaData(string $tableName = '', bool $full = false): array
    {
        $databaseName = '';
        $key = $databaseName . '_' . $tableName . '_' . (($full) ? '1' : '0');

        if (!isset(self::$_metaCache[$key])) {
            // get meta data
            self::$_metaCache[$key] = $this->getDriver()->getMetaData($tableName, $full);
        }

        return self::$_metaCache[$key];
    }

    /**
     *
     * @return ?array {@see cDbDriverAbstract::getTableNames()}
     */
    public function getTableNames(): ?array
    {
        if (!$this->connect()) {
            return NULL;
        }

        return $this->getDriver()->getTableNames();
    }

    /**
     * @see cDbDriverAbstract::getTableFieldDataType()
     * @since CONTENIDO 4.10.2
     */
    public function getTableFieldDataType(string $table, string $field): ?string
    {
        if (!$this->connect()) {
            return NULL;
        }

        return $this->getDriver()->getTableFieldDataType($table, $field);
    }


    /**
     * @return ?array {@see cDbDriverAbstract::getServerInfo()}
     */
    public function getServerInfo(): ?array
    {
        if (!$this->connect()) {
            return NULL;
        }

        return $this->getDriver()->getServerInfo();
    }

    /**
     * Closes the connection and frees the query id.
     */
    public function disconnect()
    {
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
     * @param mixed $name The field name or index position
     * @param mixed $default The default value to return
     * @return mixed The value of field
     */
    public function f($name, $default = NULL)
    {
        $record = $this->getRecord();

        return $record[$name] ?? $default;
    }

    /**
     * Returns current record set as a associative and/or indexed array.
     *
     * @param string $fetchMode One of cDbDriverHandler::FETCH_* constants
     */
    public function toArray(string $fetchMode = self::FETCH_ASSOC): array
    {
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
                if ($fetchMode === self::FETCH_ASSOC && !is_numeric($key)) {
                    $result[$key] = $value;
                } elseif ($fetchMode === self::FETCH_NUMERIC && is_numeric($key)) {
                    $result[$key] = $value;
                } elseif ($fetchMode === self::FETCH_BOTH) {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Returns current record set as an object.
     */
    public function toObject(): stdClass
    {
        return (object) $this->toArray();
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
     * @param string $message The message to use for error handling
     * @throws cDbException
     */
    public function halt(string $message): void
    {
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
     */
    public function reportHalt(string $message): void
    {
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
     * Logs the database statement.
     * @since CONTENIDO 4.10.2
     */
    protected function logStatement(string $statement): void
    {
        if (!isset($this->logger)) {
            try {
                $cfg = cRegistry::getConfig();
                $this->logger = new cLog(
                    cLogWriter::factory(
                        'file',
                        ['destination' => $cfg['path']['contenido_logs'] . $cfg['log_file_names']['db_statements_log']],
                    )
                );
            } catch (cInvalidArgumentException $e) {
                return;
            }
        }

        $this->logger->log($this->_driverType . ': ' . $statement);
    }

    /**
     * @deprecated [2023-01-25] Since CONTENIDO 4.10.2, use {@see cDbDriverHandler::numRows} instead
     */
    public function num_rows()
    {
        cDeprecated("The function cDbDriverHandler::num_rows() is deprecated since CONTENIDO 4.10.2, use cDbDriverHandler::numRows() instead.");
        return $this->numRows();
    }

    /**
     * @deprecated [2023-01-25] Since CONTENIDO 4.10.2, use {@see cDbDriverHandler::affectedRows} instead
     */
    public function affected_rows()
    {
        cDeprecated("The function cDbDriverHandler::affected_rows() is deprecated since CONTENIDO 4.10.2, use cDbDriverHandler::affectedRows() instead.");
        return $this->affectedRows();
    }

    /**
     * @deprecated [2023-01-25] Since CONTENIDO 4.10.2, use {@see cDbDriverHandler::numFields} instead
     */
    public function num_fields()
    {
        cDeprecated("The function cDbDriverHandler::num_fields() is deprecated since CONTENIDO 4.10.2, use cDbDriverHandler::numFields() instead.");
        return $this->numFields();
    }

    /**
     * @deprecated [2023-01-25] Since CONTENIDO 4.10.2, use {@see cDbDriverHandler::nextRecord} instead
     */
    public function next_record()
    {
        cDeprecated("The function cDbDriverHandler::next_record() is deprecated since CONTENIDO 4.10.2, use cDbDriverHandler::nextRecord() instead.");
        return $this->nextRecord();
    }
}
