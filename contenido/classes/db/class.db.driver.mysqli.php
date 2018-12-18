<?php

/**
 * This file contains the MySQLi database driver class.
 *
 * @package Core
 * @subpackage Database
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions for database interaction based on MySQLi in
 * CONTENIDO.
 *
 * Configurable via global $cfg['db']['connection'] configuration as follows:
 * <pre>
 * - host (string) Hostname or ip
 * - database (string) Database name
 * - user (string) User name
 * - password (string) User password
 * - options (array) Optional, MySQLi options array
 * - socket (int) Optional, socket
 * - port (int) Optional, port
 * - flags (int) Optional, flags
 * - charset (string) Optional, connection charset
 * see http://www.php.net/manual/en/mysqli.real-connect.php
 * </pre>
 *
 * @package Core
 * @subpackage Database
 */
class cDbDriverMysqli extends cDbDriverAbstract {

    /**
     * List of data types.
     * @var array
     */
    protected $_dataTypes = array(
        0 => 'decimal',
        1 => 'tinyint',
        2 => 'smallint',
        3 => 'int',
        4 => 'float',
        5 => 'double',
        7 => 'timestamp',
        8 => 'bigint',
        9 => 'mediumint',
        10 => 'date',
        11 => 'time',
        12 => 'datetime',
        13 => 'year',
        252 => 'blob', // text, blob, tinyblob,mediumblob, etc...
        253 => 'string', // varchar and char
        254 => 'enum'
    );

    /**
     * Abstract method for checking database driver base functions.
     * If this check fails, the database connection will not be established.
     *
     * @see cDbDriverAbstract::check()
     * @return bool
     */
    public function check() {
        return extension_loaded('mysqli');
    }

    /**
     * Connects to the database.
     *
     * @see cDbDriverAbstract::connect()
     * @return object|resource|int|NULL
     *         value depends on used driver and is NULL in case of an error.
     * @throws cDbException
     */
    public function connect() {
        $dbHandler = @mysqli_init();
        if (!$dbHandler || $dbHandler->connect_error != "" || $dbHandler->error != "") {
            $this->_handler->halt('Can not initialize database connection.');
            return NULL;
        }

        if (isset($this->_dbCfg['connection'])) {
            $connectConfig = $this->_dbCfg['connection'];
        }
        if (empty($connectConfig) || !isset($connectConfig['host']) || !isset($connectConfig['user']) || !isset($connectConfig['password'])) {
            $this->_handler->halt('Database connection settings incomplete');
            return NULL;
        }

        // set existing option flags
        if (isset($connectConfig['options']) && is_array($connectConfig['options'])) {
            foreach ($connectConfig['options'] as $optKey => $optVal) {
                mysqli_options($dbHandler, $optKey, $optVal);
            }
        }

        if (($iPos = cString::findFirstPos($connectConfig['host'], ':')) !== false) {
            $hostData = explode(':', $connectConfig['host']);
            $connectConfig['host'] = $hostData[0];
            if (is_numeric($hostData[1])) {
                $connectConfig['port'] = $hostData[1];
            } else {
                $connectConfig['socket'] = $hostData[1];
            }
        }

        if (!isset($connectConfig['port'])) {
            $connectConfig['port'] = NULL;
        }
        if (!isset($connectConfig['socket'])) {
            $connectConfig['socket'] = NULL;
        }

        if (!isset($connectConfig['flags'])) {
            $connectConfig['flags'] = NULL;
        }
        if (!isset($connectConfig['database'])) {
            $connectConfig['database'] = NULL;
        }

        $res = mysqli_real_connect($dbHandler, $connectConfig['host'], $connectConfig['user'], $connectConfig['password'], $connectConfig['database'], $connectConfig['port'], $connectConfig['socket'], $connectConfig['flags']);

        // check if connection could be established
        if (false === $res) {
            $this->_handler->halt('MySQLi _connect() Error connecting to database ' . $connectConfig['database']);
            return NULL;
        }

        if ($res && $dbHandler && $connectConfig['database']) {
            if (!@mysqli_select_db($dbHandler, $connectConfig['database'])) {
                $this->_handler->halt('MySQLi _connect() Cannot use database ' . $connectConfig['database']);
                return NULL;
            } else {
                // set connection charset
                if (isset($connectConfig['charset']) && $connectConfig['charset'] != '') {
                    if (!@mysqli_set_charset($dbHandler, $connectConfig['charset'])) {
                        $this->_handler->halt('Could not set database charset to ' . $connectConfig['charset']);
                        return NULL;
                    }
                }
            }
        }

        return $dbHandler;
    }

    /**
     * Builds a insert query.
     * String values in passed fields parameter will be escaped automatically.
     *
     * @see cDbDriverAbstract::buildInsert()
     * @param string $tableName
     *         The table name
     * @param array $fields
     *         Associative array of fields to insert
     * @return string
     *         The INSERT SQL query
     */
    public function buildInsert($tableName, array $fields) {
        $fieldList = '';
        $valueList = '';

        foreach ($fields as $field => $value) {
            $fieldList .= '`' . $field . '`, ';
            if (is_int($value)) {
                $valueList .= $value . ', ';
            } else {
                $valueList .= "'" . $this->escape($value) . "', ";
            }
        }

        $fieldList = cString::getPartOfString($fieldList, 0, -2);
        $valueList = cString::getPartOfString($valueList, 0, -2);
        return sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $tableName, $fieldList, $valueList);
    }

    /**
     * Builds a update query. String values in passed fields and whereClauses
     * parameter will be escaped automatically.
     *
     * @see cDbDriverAbstract::buildUpdate()
     * @param string $tableName
     *         The table name
     * @param array $fields
     *         Assoziative array of fields to update
     * @param array $whereClauses
     *         Assoziative array of field in where clause.
     *         Multiple entries will be concatenated with AND.
     * @return string
     *         The UPDATE query
     */
    public function buildUpdate($tableName, array $fields, array $whereClauses) {
        $updateList = '';
        $whereList = '';

        foreach ($fields as $field => $value) {
            $updateList .= '`' . $field . '`=';
            if (is_int($value)) {
                $updateList .= $value . ', ';
            } else {
                $updateList .= "'" . $this->escape($value) . "', ";
            }
        }

        foreach ($whereClauses as $field => $value) {
            $whereList .= '`' . $field . '`=';
            if (is_int($value)) {
                $whereList .= $value . ' AND ';
            } else {
                $whereList .= "'" . $this->escape($value) . "' AND ";
            }
        }

        $updateList = cString::getPartOfString($updateList, 0, -2);
        $whereList = cString::getPartOfString($whereList, 0, -5);

        return sprintf('UPDATE `%s` SET %s WHERE %s', $tableName, $updateList, $whereList);
    }

    /**
     * Executes the query.
     *
     * @see cDbDriverAbstract::query()
     * @param string $query
     *         The query to execute
     */
    public function query($query) {
        $linkId = $this->_handler->getLinkId();
        $queryId = mysqli_query($linkId, $query);

        $this->_handler->setQueryId($queryId);
        $this->_handler->setRow(0);
        $this->_handler->setErrorNumber($this->getErrorNumber());
        $this->_handler->setErrorMessage($this->getErrorMessage());
    }

    /**
     * Moves the result to the next record, if exists and returns the status of
     * the movement
     *
     * @see cDbDriverAbstract::nextRecord()
     * @return int
     *         Flag about move status 1 on success or 0
     */
    public function nextRecord() {
        $queryId = $this->_handler->getQueryId();
        $record = mysqli_fetch_array($queryId, MYSQLI_BOTH);

        $this->_handler->setRecord($record);
        $this->_handler->incrementRow();
        $this->_handler->setErrorNumber($this->getErrorNumber());
        $this->_handler->setErrorMessage($this->getErrorMessage());

        return is_array($record);
    }

    /**
     * This method returns the current result set as object or NULL if no result
     * set is left. If optional param $className is set, the result object is an
     * instance of class $className.
     *
     * @see cDbDriverAbstract::getResultObject()
     * @param string $className [optional]
     * @return Ambigous <NULL, object, false>
     */
    public function getResultObject($className = NULL) {
        $result = NULL;
        $queryId = $this->_handler->getQueryId();

        if ($queryId) {
            if ($className == NULL) {
                $result = mysqli_fetch_object($queryId);
            } else {
                $result = mysqli_fetch_object($queryId, $className);
            }
        }

        return $result;
    }

    /**
     * Returns number of affected rows from last executed query (update, delete)
     *
     * @see cDbDriverAbstract::affectedRows()
     * @return int
     *         Number of affected rows
     */
    public function affectedRows() {
        $linkId = $this->_handler->getLinkId();
        return ($linkId) ? mysqli_affected_rows($linkId) : 0;
    }

    /**
     * Returns the number of rows from last executed select query.
     *
     * @see cDbDriverAbstract::numRows()
     * @return int
     *         The number of rows from last select query result
     */
    public function numRows() {
        $queryId = $this->_handler->getQueryId();
        return ($queryId) ? mysqli_num_rows($queryId) : 0;
    }

    /**
     * Returns the number of fields (columns) from current record set
     *
     * @see cDbDriverAbstract::numFields()
     * @return int
     *         Number of fields
     */
    public function numFields() {
        $queryId = $this->_handler->getQueryId();
        return ($queryId) ? mysqli_num_fields($queryId) : 0;
    }

    /**
     * Discard the query result
     *
     * @todo check if $this should be returned
     * @return void|cDbDriverMysqli
     *         If aggregated handler has no query id, this object is returned,
     *         otherwise void.
     * @see cDbDriverAbstract::free()
     */
    public function free() {
        if (!is_object($this->_handler->getQueryId())) {
            return $this;
        }

        mysqli_free_result($this->_handler->getQueryId());
        $this->_handler->setQueryId(0);
    }

    /**
     * Escape string for using in SQL-Statement.
     *
     * @see cDbDriverAbstract::escape()
     * @param string $string
     *         The string to escape
     * @return string
     *         Escaped string
     */
    public function escape($string) {
        $linkId = $this->_handler->getLinkId();
        return mysqli_real_escape_string($linkId, $string);
    }

    /**
     * Moves the cursor (position inside current result sets).
     *
     * @see cDbDriverAbstract::seek()
     * @param int $pos [optional]
     *         The positon to move to inside the current result set
     * @return int
     */
    public function seek($pos = 0) {
        $queryId = $this->_handler->getQueryId();

        $status = mysqli_data_seek($queryId, $pos);
        if ($status) {
            $this->_handler->setRow($pos);
        } else {
            return 0;
        }

        return 1;
    }

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
     * @see cDbDriverAbstract::getMetaData()
     *
     * @param string $tableName
     *                     The table to get metadata or empty string to retrieve metadata
     *                     of all tables.
     * @param bool   $full [optional]
     *                     Flag to load full metadata.
     *
     * @return array
     *         Depends on used database and on parameter $full
     * @throws cDbException
     */
    public function getMetaData($tableName, $full = false) {
        $res = array();

        $this->query(sprintf('SELECT * FROM `%s` LIMIT 1', $tableName));

        $id = $this->_handler->getQueryId();
        if (!$id) {
            $this->_handler->halt('Metadata query failed.');
            return false;
        }

        // made this IF due to performance (one if is faster than $count if's)
        $count = mysqli_num_fields($id);
        for ($i = 0; $i < $count; $i++) {
            $finfo = mysqli_fetch_field($id);
            $res[$i]['table'] = $finfo->table;
            $res[$i]['name'] = $finfo->name;
            $res[$i]['type'] = $this->_dataTypes[$finfo->type];
            $res[$i]['len'] = $finfo->max_length;
            $res[$i]['flags'] = $finfo->flags;
            if ($full) {
                $res['meta'][$res[$i]['name']] = $i;
            }
        }
        if ($full) {
            $res['num_fields'] = $count;
        }

        $this->free();

        return $res;
    }

    /**
     * Fetches all table names.
     *
     * @see cDbDriverAbstract::getTableNames()
     * @return array
     */
    public function getTableNames() {
        $return = array();
        if ($this->query('SHOW TABLES')) {
            while ($this->nextRecord()) {
                $record = $this->getRecord();
                $return[] = array(
                    'table_name' => $record[0],
                    'tablespace_name' => $this->_dbCfg['connection']['database'],
                    'database' => $this->_dbCfg['connection']['database']
                );
            }

            $this->free();
        }
        return $return;
    }

    /**
     * Fetches server information.
     *
     * @see cDbDriverAbstract::getServerInfo()
     * @return array
     */
    public function getServerInfo() {
        $linkId = $this->_handler->getLinkId();

        if ($linkId) {
            $arr = array();
            $arr['description'] = mysqli_get_server_info($linkId);
            return $arr;
        }

        return NULL;
    }

    /**
     * Returns error code of last occured error by using databases interface.
     *
     * @see cDbDriverAbstract::getErrorNumber()
     * @return int
     */
    public function getErrorNumber() {
        $linkId = $this->_handler->getLinkId();

        if ($linkId) {
            return @mysqli_errno($linkId);
        } else {
            return @mysqli_connect_errno();
        }
    }

    /**
     * Returns error message of last occured error by using databases interface.
     *
     * @see cDbDriverAbstract::getErrorMessage()
     * @return string
     */
    public function getErrorMessage() {
        $linkId = $this->_handler->getLinkId();

        if ($linkId) {
            return @mysqli_error($linkId);
        } else {
            return @mysqli_connect_error();
        }
    }

    /**
     * Closes the connection and frees the query id.
     *
     * @see cDbDriverAbstract::disconnect()
     */
    public function disconnect() {
        mysqli_close($this->_handler->getLinkId());
    }

}
