<?php

/**
 * This file contains the abstract database driver class.
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
 * This class contains abstract method definitions for each database driver in
 * CONTENIDO.
 *
 * @package    Core
 * @subpackage Database
 */
abstract class cDbDriverAbstract {

    /**
     * Local database configuration, see `$cfg['db']` configuration.
     *
     * @var array
     */
    protected $_dbCfg = [];

    /**
     * Driver handler instance.
     *
     * @var cDbDriverHandler
     */
    protected $_handler = NULL;

    /**
     * Constructor to create an instance of this class.
     *
     * The given configuration will be aggregated.
     *
     * @param array $dbCfg
     *         database configuration
     */
    public function __construct(array $dbCfg) {
        $this->_dbCfg = $dbCfg;
    }

    /**
     * Sets the database driver handler.
     *
     * @param cDbDriverHandler $handler
     *         database driver handler instance
     */
    public function setHandler(cDbDriverHandler $handler) {
        $this->_handler = $handler;
    }

    /**
     * Returns the database driver handler instance.
     *
     * @return cDbDriverHandler|NULL
     */
    public function getHandler() {
        return $this->_handler;
    }

    /**
     * Abstract method for checking database driver base functions.
     * If this check fails, the database connection will not be established.
     *
     * @return bool
     */
    abstract public function check();

    /**
     * Connects to the database.
     *
     * @return object|resource|int|NULL
     *         value depends on used driver and is NULL in case of an error
     */
    abstract public function connect();

    /**
     * Builds an insert query.
     * String values in passed fields parameter will be escaped automatically.
     *
     * @param string $tableName
     *         The table name
     * @param array $fields
     *         Associative array of fields to insert
     * @return string
     *         The INSERT SQL query
     */
    abstract public function buildInsert($tableName, array $fields);

    /**
     * Builds an update query. String values in passed fields and whereClauses
     * parameter will be escaped automatically.
     *
     * @param string $tableName
     *         The table name
     * @param array $fields
     *         Associative array of fields to update
     * @param array $whereClauses
     *         Associative array of field in where clause.
     *         Multiple entries will be concatenated with AND.
     * @return string
     *         The UPDATE query
     */
    abstract public function buildUpdate($tableName, array $fields, array $whereClauses);

    /**
     * Executes the query.
     *
     * @param string $statement
     *         The query to execute
     * @return bool
     *         The query success status
     */
    abstract public function query($statement);

    /**
     * Moves the result to the next record, if exists and returns the status of
     * the movement
     *
     * @return bool
     *         Flag about move status true on success or false
     */
    abstract public function nextRecord();

    /**
     * This method returns the current result set as object or NULL if no result
     * set is left. If optional param $className is set, the result object is an
     * instance of class $className.
     *
     * @param string $className [optional]
     * @return object
     */
    abstract public function getResultObject($className = NULL);

    /**
     * Returns number of affected rows from last executed query (update, delete)
     *
     * @return int
     *         Number of affected rows
     */
    abstract public function affectedRows();

    /**
     * Returns the number of rows from last executed select query.
     *
     * @return int
     *         The number of rows from last select query result
     */
    abstract public function numRows();

    /**
     * Returns the number of fields (columns) from current record set
     *
     * @return int
     *         Number of fields
     */
    abstract public function numFields();

    /**
     * Discard the query result
     */
    abstract public function free();

    /**
     * Escape string for using in SQL-Statement.
     *
     * @param string $string
     *         The string to escape
     * @return string
     *         Escaped string
     */
    abstract public function escape($string);

    /**
     * Moves the cursor (position inside current result sets).
     *
     * @param int $iPos [optional]
     *         The position to move to inside the current result set
     * @return int
     */
    abstract public function seek($iPos = 0);

    /**
     * Parses the table structure and generates metadata from it.
     *
     * Due to compatibility problems with table we changed the behavior
     * of metadata(). Depending on $full, metadata returns the following values:
     *
     * - full is false (default):
     * $result[]:
     * [0]["table"] table name
     * [0]["name"] field name
     * [0]["type"] field type
     * [0]["len"] field length
     * [0]["flags"] field flags
     *
     * - full is true
     * $result[]:
     * ["num_fields"] number of metadata records
     * [0]["table"] table name
     * [0]["name"] field name
     * [0]["type"] field type
     * [0]["len"] field length
     * [0]["flags"] field flags
     * ["meta"][field name] index of field named "field name"
     * This last one could be used if you have a field name, but no index.
     * Test: if (isset($result['meta']['myfield'])) { ...
     *
     * @param string $tableName
     *         The table to get metadata or empty string to retrieve metadata
     *         of all tables.
     * @param bool $full [optional]
     *         Flag to load full metadata.
     * @return array
     *         Depends on used database and on parameter $full
     */
    abstract public function getMetaData($tableName, $full = false);

    /**
     * Fetches all table names.
     *
     * @return array
     */
    abstract public function getTableNames();

    /**
     * Fetches server information.
     *
     * @return array|NULL
     *         array as follows or NULL:
     *         - $arr['description'] (string) Optional, server description
     *         - $arr['version'] (string) Optional, server version
     */
    abstract public function getServerInfo();

    /**
     * Returns error code of last occurred error by using databases interface.
     *
     * @return int
     */
    abstract public function getErrorNumber();

    /**
     * Returns error message of last occurred error by using databases interface.
     *
     * @return string
     */
    abstract public function getErrorMessage();

    /**
     * Closes the connection and frees the query id.
     */
    abstract public function disconnect();

}
