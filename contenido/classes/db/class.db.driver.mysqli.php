<?php

/**
 * This file contains the MySQLi database driver class.
 *
 * @package Core
 * @subpackage Database
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
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
 * see https://www.php.net/manual/en/mysqli.real-connect.php
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
    protected $_dataTypes = [
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
    ];

    /**
     * @inheritdoc
     */
    public function check() {
        return extension_loaded('mysqli');
    }

    /**
     * @inheritdoc
     * @return mysqli|object|resource|int|NULL
     * @throws cDbException|cInvalidArgumentException
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

        $connectConfig['port'] = $connectConfig['port'] ?? NULL;
        $connectConfig['socket'] = $connectConfig['socket'] ?? NULL;
        $connectConfig['flags'] = cSecurity::toInteger($connectConfig['flags'] ?? '0');
        $connectConfig['database'] = $connectConfig['database'] ?? NULL;

        $res = mysqli_real_connect(
            $dbHandler, $connectConfig['host'], $connectConfig['user'], $connectConfig['password'],
            $connectConfig['database'], $connectConfig['port'], $connectConfig['socket'], $connectConfig['flags']
        );

        // check if connection could be established
        if (!$res) {
            $this->_handler->halt('MySQLi _connect() Error connecting to database ' . $connectConfig['database']);
            return NULL;
        }

        if ($connectConfig['database']) {
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
     * @inheritdoc
     */
    public function buildInsert($tableName, array $fields) {
        $fieldList = '';
        $valueList = '';

        foreach ($fields as $field => $value) {
            $fieldList .= '`' . $field . '`, ';
            if (is_int($value)) {
                $valueList .= $value . ', ';
            } elseif (is_null($value)) {
                $valueList .= 'NULL, ';
            } else {
                $valueList .= "'" . $this->escape($value) . "', ";
            }
        }

        $fieldList = cString::getPartOfString($fieldList, 0, -2);
        $valueList = cString::getPartOfString($valueList, 0, -2);
        return sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $tableName, $fieldList, $valueList);
    }

    /**
     * @inheritdoc
     */
    public function buildUpdate($tableName, array $fields, array $whereClauses) {
        $updateList = '';
        $whereList = '';

        foreach ($fields as $field => $value) {
            $updateList .= '`' . $field . '` = ';
            if (is_int($value)) {
                $updateList .= $value . ', ';
            } elseif (is_null($value)) {
                $updateList .= 'NULL, ';
            } else {
                $updateList .= "'" . $this->escape($value) . "', ";
            }
        }

        foreach ($whereClauses as $field => $value) {
            $whereList .= '`' . $field . '`';
            if (is_int($value)) {
                $whereList .= ' = ' . $value . ' AND ';
            } elseif (is_null($value)) {
                $whereList .=  ' IS NULL AND ';
            } else {
                $whereList .= " = '" . $this->escape($value) . "' AND ";
            }
        }

        $updateList = cString::getPartOfString($updateList, 0, -2);
        $whereList = cString::getPartOfString($whereList, 0, -5);

        return sprintf('UPDATE `%s` SET %s WHERE %s', $tableName, $updateList, $whereList);
    }

    /**
     * @inheritdoc
     */
    public function query($query) {
        $linkId = $this->_handler->getLinkId();
        $queryId = mysqli_query($linkId, $query);

        $this->_handler->setQueryId($queryId);
        $this->_handler->setRow(0);
        $this->_handler->setErrorNumber($this->getErrorNumber());
        $this->_handler->setErrorMessage($this->getErrorMessage());

        return $queryId !== false;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     * @return false|null|object|stdClass
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
     * @inheritdoc
     */
    public function affectedRows() {
        $linkId = $this->_handler->getLinkId();
        return ($linkId) ? mysqli_affected_rows($linkId) : 0;
    }

    /**
     * @inheritdoc
     */
    public function numRows() {
        $queryId = $this->_handler->getQueryId();
        return ($queryId) ? mysqli_num_rows($queryId) : 0;
    }

    /**
     * @inheritdoc
     */
    public function numFields() {
        $queryId = $this->_handler->getQueryId();
        return ($queryId) ? mysqli_num_fields($queryId) : 0;
    }

    /**
     * @inheritdoc
     * @todo check if $this should be returned
     * @return void|cDbDriverMysqli
     *         If aggregated handler has no query id, this object is returned,
     *         otherwise void.
     */
    public function free() {
        if (!is_object($this->_handler->getQueryId())) {
            return $this;
        }

        mysqli_free_result($this->_handler->getQueryId());
        $this->_handler->setQueryId(NULL);
    }

    /**
     * @inheritdoc
     */
    public function escape($string) {
        if (is_string($string)) {
            $linkId = $this->_handler->getLinkId();
            return mysqli_real_escape_string($linkId, $string);
        } else {
            return $string;
        }
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     * @throws cDbException|cInvalidArgumentException
     */
    public function getMetaData($tableName, $full = false) {
        $res = [];

        $this->query(sprintf('SELECT * FROM `%s` LIMIT 1', $tableName));

        $id = $this->_handler->getQueryId();
        if (!$id) {
            $this->_handler->halt('Metadata query failed.');
            return [];
        }

        // made this IF due to performance (one if is faster than $count if's)
        $count = mysqli_num_fields($id);
        for ($i = 0; $i < $count; $i++) {
            $fieldInfo = mysqli_fetch_field($id);
            $res[$i]['table'] = $fieldInfo->table;
            $res[$i]['name'] = $fieldInfo->name;
            $res[$i]['type'] = $this->_dataTypes[$fieldInfo->type];
            $res[$i]['len'] = $fieldInfo->max_length;
            $res[$i]['flags'] = $fieldInfo->flags;
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
     * @inheritdoc
     */
    public function getTableNames() {
        $return = [];

        $linkId = $this->_handler->getLinkId();

        if ($result = mysqli_query($linkId, 'SHOW TABLES')) {
            while ($record = mysqli_fetch_row($result)) {
                $return[] = [
                    'table_name' => $record[0],
                    'tablespace_name' => $this->_dbCfg['connection']['database'],
                    'database' => $this->_dbCfg['connection']['database']
                ];

            }
            mysqli_free_result($result);
        }
        return $return;
    }

    /**
     * @inheritdoc
     */
    public function getServerInfo() {
        $linkId = $this->_handler->getLinkId();

        if ($linkId) {
            return [
                'description' => mysqli_get_server_info($linkId),
                'version' => mysqli_get_server_version($linkId),
            ];
        }

        return NULL;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
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
     * @inheritdoc
     */
    public function disconnect() {
        mysqli_close($this->_handler->getLinkId());
    }

}
