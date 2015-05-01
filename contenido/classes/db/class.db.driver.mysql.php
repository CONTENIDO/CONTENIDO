<?php

/**
 * This file contains the MySQL database driver class.
 *
 * @package    Core
 * @subpackage Database
 * @version    SVN Revision $Rev:$
 *
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class contains functions for database interaction based on MySQL in CONTENIDO.
 *
 * Configurable via global $cfg['db']['connection'] configuration as follows:
 * <pre>
 * - host      (string)  Hostname or ip (with port, e. g. "example.com:3307")
 * - database  (string)  Database name
 * - user      (string)  User name
 * - password  (string)  User password
 * - charset   (string)  Optional, connection charset
 * see http://php.net/manual/en/function.mysql-connect.php
 * </pre>
 *
 * @package    Core
 * @subpackage Database
 */
class cDbDriverMysql extends cDbDriverAbstract {

    /**
     * Abstract method for checking database driver base functions.
     * If this check fails, the database connection will not be established.
     *
     * @see cDbDriverAbstract::check()
     * @return bool
     */
    public function check() {
        return function_exists("mysql_connect");
    }

    /**
     * Connects to the database.
     *
     * @see cDbDriverAbstract::connect()
     * @return object|resource|int|NULL
     *         value depends on used driver and is NULL in case of an error
     */
    public function connect() {
        if (isset($this->_dbCfg['connection'])) {
            $connectConfig = $this->_dbCfg['connection'];
        }
        if (empty($connectConfig) || !isset($connectConfig['host']) || !isset($connectConfig['user']) || !isset($connectConfig['password'])) {
            $this->_handler->halt('Database connection settings incomplete');

            return NULL;
        }

        // establish connection, select database
        $dbHandler = mysql_connect($connectConfig['host'], $connectConfig['user'], $connectConfig['password']);
        if (!$dbHandler || !is_resource($dbHandler)) {
            $this->_handler->halt('Error during establishing a connection with database.');

            return NULL;
        }

        if (isset($connectConfig['database'])) {
            if (!mysql_select_db($connectConfig['database'], $dbHandler)) {
                $this->_handler->halt('Can not use database ' . $connectConfig['database']);

                return NULL;
            } else {
                //set connection charset
                if (isset($connectConfig['charset']) && $connectConfig['charset'] != '') {
                    if (!mysql_set_charset($connectConfig['charset'], $dbHandler)) {
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

        $fieldList = substr($fieldList, 0, -2);
        $valueList = substr($valueList, 0, -2);

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

        $updateList = substr($updateList, 0, -2);
        $whereList = substr($whereList, 0, -5);

        return sprintf('UPDATE `%s` SET %s WHERE %s', $tableName, $updateList, $whereList);
    }

    /**
     * Executes the query.
     *
     * @see cDbDriverAbstract::query()
     * @param string $statement
     *         The query to execute
     */
    public function query($query) {
        $linkId = $this->_handler->getLinkId();
        $queryId = @mysql_query($query, $linkId);

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
        $record = @mysql_fetch_array($queryId);

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
     * @param string $className
     * @return Ambigous <NULL, object, false>
     */
    public function getResultObject($className = NULL) {
        $result = NULL;
        $queryId = $this->_handler->getQueryId();

        if (is_resource($queryId)) {
            if ($className == NULL) {
                $result = mysql_fetch_object($queryId);
            } else {
                $result = mysql_fetch_object($queryId, $className);
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

        return ($linkId) ? mysql_affected_rows($linkId) : 0;
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

        return ($queryId) ? mysql_num_rows($queryId) : 0;
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

        return ($queryId) ? mysql_num_fields($queryId) : 0;
    }

    /**
     * Discard the query result
     *
     * @see cDbDriverAbstract::free()
     */
    public function free() {
        @mysql_free_result($this->_handler->getQueryId());
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

        return mysql_real_escape_string($string, $linkId);
    }

    /**
     * Moves the cursor (position inside current result sets).
     *
     * @see cDbDriverAbstract::seek()
     * @param int $iPos
     *         The positon to move to inside the current result set
     * @return int
     */
    public function seek($pos = 0) {
        $queryId = $this->_handler->getQueryId();

        $status = @mysql_data_seek($queryId, $pos);
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
     * @param string $tableName
     *         The table to get metadata or empty string to retrieve metadata
     *         of all tables.
     * @param bool $full
     *         Flag to load full metadata.
     * @return array
     *         Depends on used database and on parameter $full
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
        $count = @mysql_num_fields($id);
        for ($i = 0; $i < $count; $i++) {
            $res[$i]['table'] = @mysql_field_table($id, $i);
            $res[$i]['name'] = @mysql_field_name($id, $i);
            $res[$i]['type'] = @mysql_field_type($id, $i);
            $res[$i]['len'] = @mysql_field_len($id, $i);
            $res[$i]['flags'] = @mysql_field_flags($id, $i);
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
                    'table_name' => $record[0], 'tablespace_name' => $this->_dbCfg['connection']['database'], 'database' => $this->_dbCfg['connection']['database'],
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

        if (is_resource($linkId)) {
            $arr = array();
            $arr['description'] = mysql_get_server_info($linkId);

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

        if (is_resource($linkId)) {
            return mysql_errno($linkId);
        } else {
            return mysql_errno();
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

        if (is_resource($linkId)) {
            return mysql_error($linkId);
        } else {
            return mysql_error();
        }
    }

    /**
     * Closes the connection and frees the query id.
     *
     * @see cDbDriverAbstract::disconnect()
     */
    public function disconnect() {
        mysql_close($this->_handler->getLinkId());
    }

}
