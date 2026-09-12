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
abstract class cDbDriverAbstract
{

    /**
     * @var array Local database configuration, see `$cfg['db']` configuration.
     */
    protected $_dbCfg = [];

    /**
     * @var ?cDbDriverHandler Driver handler instance.
     */
    protected $_handler = NULL;

    /**
     * Constructor to create an instance of this class.
     *
     * The given configuration will be aggregated.
     *
     * @param array $dbCfg Database configuration
     */
    public function __construct(array $dbCfg)
    {
        $this->_dbCfg = $dbCfg;
    }

    /**
     * Sets the database driver handler.
     *
     * @param cDbDriverHandler $handler Database driver handler instance
     */
    public function setHandler(cDbDriverHandler $handler)
    {
        $this->_handler = $handler;
    }

    /**
     * Returns the database driver handler instance.
     */
    public function getHandler(): ?cDbDriverHandler
    {
        return $this->_handler;
    }

    /**
     * Abstract method for checking database driver base functions.
     * If this check fails, the database connection will not be established.
     */
    abstract public function check(): bool;

    /**
     * Connects to the database.
     *
     * @return object|resource|int|NULL Value depends on used driver and is NULL in case of an error
     */
    abstract public function connect();

    /**
     * Builds an insert query.
     * String values in passed fields parameter will be escaped automatically.
     *
     * @param string $tableName The table name
     * @param array $fields Associative array of fields to insert
     * @return string The INSERT SQL query
     */
    abstract public function buildInsert(string $tableName, array $fields): string;

    /**
     * Builds an update query. String values in passed fields and whereClauses
     * parameter will be escaped automatically.
     *
     * @param string $tableName The table name
     * @param array $fields Associative array of fields to update
     * @param array $whereClauses Associative array of field in where clause.
     *      Multiple entries will be concatenated with AND.
     * @return string The UPDATE query
     */
    abstract public function buildUpdate(string $tableName, array $fields, array $whereClauses): string;

    /**
     * Executes the statement.
     *
     * @param string $statement The statement to execute
     * @return bool The query success status
     */
    abstract public function query(string $statement): bool;

    /**
     * Moves the result to the next record, if exists and returns the status of the movement
     *
     * @return bool Flag about move status true on success or false
     */
    abstract public function nextRecord(): bool;

    /**
     * This method returns the current result set as object or NULL if no result
     * set is left. If optional param $className is set, the result object is an
     * instance of class $className.
     *
     * @return object
     */
    abstract public function getResultObject(?string $className = NULL);

    /**
     * Returns number of affected rows from last executed query (update, delete)
     *
     * @return int Number of affected rows
     */
    abstract public function affectedRows(): int;

    /**
     * Returns the number of rows from last executed select query.
     *
     * @return int The number of rows from last select query result
     */
    abstract public function numRows(): int;

    /**
     * Returns the number of fields (columns) from current record set
     *
     * @return int Number of fields
     */
    abstract public function numFields(): int;

    /**
     * Discard the query result
     */
    abstract public function free();

    /**
     * Escape string for using in SQL-Statement.
     *
     * @param string|mixed $string The string to escape
     * @return string|mixed Escaped string
     */
    abstract public function escape($string);

    /**
     * Moves the cursor (position inside current result sets).
     *
     * @param int $pos The position to move to inside the current result set
     */
    abstract public function seek(int $pos = 0): int;

    /**
     * Parses the table structure and generates metadata from it.
     *
     * Due to compatibility problems with table we changed the behavior
     * of metadata(). Depending on $full, metadata returns the following values:
     *
     * - full is false (default):
     * $result[]:
     * [0]['table'] table name
     * [0]['name'] field name
     * [0]['type'] field type
     * [0]['len'] field length
     * [0]['flags'] field flags
     *
     * - full is true
     * $result[]:
     * ['num_fields'] number of metadata records
     * [0]['table'] table name
     * [0]['name'] field name
     * [0]['type'] field type
     * [0]['len'] field length
     * [0]['flags'] field flags
     * ['meta'][field name] index of field named "field name"
     * This last one could be used if you have a field name, but no index.
     * Test: if (isset($result['meta']['myfield'])) { ...
     *
     * @param string $tableName The table to get metadata or empty string to retrieve metadata of all tables.
     * @param bool $full Flag to load full metadata.
     * @return array Depends on used database and on parameter $full
     */
    abstract public function getMetaData(string $tableName, bool $full = false): array;

    /**
     * Fetches all table names.
     */
    abstract public function getTableNames(): array;

    /**
     * Returns the data-type of a specific table field.
     *
     * @since CONTENIDO 4.10.2
     */
    abstract public function getTableFieldDataType(string $table, string $field): ?string;

    /**
     * Fetches server information.
     *
     * @return ?array Array as follows or NULL:
     *      - $arr['description'] (string) Optional, server description
     *      - $arr['version'] (string) Optional, server version
     */
    abstract public function getServerInfo(): ?array;

    /**
     * Returns error code of last occurred error by using databases interface.
     */
    abstract public function getErrorNumber(): int;

    /**
     * Returns error message of last occurred error by using databases interface.
     */
    abstract public function getErrorMessage(): string;

    /**
     * Closes the connection and frees the query id.
     */
    abstract public function disconnect();

    /**
     * Prepares the options for the database driver.
     * @param mixed $options Database connection options (null, array, or string)
     */
    abstract protected function prepareOptionFlags(mixed $options): array;
}
