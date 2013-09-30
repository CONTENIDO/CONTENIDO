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
     * @see cDbDriverAbstract::check()
     */
    public function check() {
        return function_exists("mysql_connect");
    }

    /**
     * @see cDbDriverAbstract::connect()
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
     * @see cDbDriverAbstract::buildInsert()
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
     * @see cDbDriverAbstract::buildUpdate()
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
     * @see cDbDriverAbstract::query()
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
     * @see cDbDriverAbstract::nextRecord()
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
     *
     * @param string $className
     * @return Ambigous <NULL, object, false>
     * @see cDbDriverAbstract::getResultObject()
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
     * @see cDbDriverAbstract::affectedRows()
     */
    public function affectedRows() {
        $linkId = $this->_handler->getLinkId();

        return ($linkId) ? mysql_affected_rows($linkId) : 0;
    }

    /**
     * @see cDbDriverAbstract::numRows()
     */
    public function numRows() {
        $queryId = $this->_handler->getQueryId();

        return ($queryId) ? mysql_num_rows($queryId) : 0;
    }

    /**
     * @see cDbDriverAbstract::numFields()
     */
    public function numFields() {
        $queryId = $this->_handler->getQueryId();

        return ($queryId) ? mysql_num_fields($queryId) : 0;
    }

    /**
     * @see cDbDriverAbstract::free()
     */
    public function free() {
        @mysql_free_result($this->_handler->getQueryId());
        $this->_handler->setQueryId(0);
    }

    /**
     * @see cDbDriverAbstract::escape()
     */
    public function escape($string) {
        $linkId = $this->_handler->getLinkId();

        return mysql_real_escape_string($string, $linkId);
    }

    /**
     * @param int $pos
     * @return int
     * @see cDbDriverAbstract::seek()
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
     * @see cDbDriverAbstract::getMetaData()
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
     * @see cDbDriverAbstract::getTableNames()
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
     * @see cDbDriverAbstract::getServerInfo()
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
     * @see cDbDriverAbstract::getErrorNumber()
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
     * @see cDbDriverAbstract::getErrorMessage()
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
     * @see cDbDriverAbstract::disconnect()
     */
    public function disconnect() {
        mysql_close($this->_handler->getLinkId());
    }

}