<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * MySQLi database driver
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    Contenido database
 * @version    0.3.2
 * @author     Boris Erdmann, Kristian Koehntopp
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 *
 * {@internal
 *   created  2000-01-01
 *   modified 2008-07-04, bilal arslan, added security fix
 *   modified 2009-10-29, Murat Purc, removed deprecated functions (PHP 5.3 ready) extended DB_Sql_Abstract, added/optimized some functioms and some formatting
 *   modified 2009-12-29, Murat Purc, replaced is_resource() against mysqli compatible check [#CON-290]
 *   modified 2011-03-03, Murat Purc, Some redesign and improvements (partial adaption to PHP 5 and extending DB_Sql_Abstract).
 *   modified 2011-03-13, Murat Purc, Cleanup and documentation.
 *   modified 2011-04-22, Murat Purc, Connect to DB server without database and more 
 *                        readable connection settings.
 *   modified 2011-07-07, Murat Purc, New _update(), _insert() methods, unified
 *                        execution of multiple queries by using cloned instance.
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class DB_Sql extends DB_Sql_Abstract
{
    protected $_aDataTypes = array(
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
        254 => 'enum',
    );


    /**
     * Constructor.
     *
     * @param  array  $options  Optional assoziative options
     */
    public function __construct(array $options = array())
    {
        $options = array_merge($options, array(
            'type' => 'mysqli',
        ));
        parent::__construct($options);
    }


    /**
     * @see DB_Sql_Abstract::_connect()
     */
    protected function _connect()
    {
        // feasible connection values are:
        // - $options['connection']['host']  (string) Hostname or ip
        // - $options['connection']['database']  (string) Database name
        // - $options['connection']['user']  (string) User name
        // - $options['connection']['password']  (string)  User password
        // - $options['connection']['options']  (array)  Optional, MySQLi options array
        // - $options['connection']['socket']  (int)  Optional, socket
        // - $options['connection']['port']  (int)  Optional, port
        // - $options['connection']['flags']  (int)  Optional, flags
        // see http://www.php.net/manual/en/mysqli.real-connect.php

//        extract($this->_aDbCfg['connection']);

        if (!extension_loaded('mysqli')) {
            $this->halt('MySQLi _connect() extension not loaded!');
            return null;
        }

        $dbh = @mysqli_init();
        if (!$dbh) {
            $this->halt('MySQLi _connect() Init failed');
            return null;
        }

        $aCon = $this->_aDbCfg['connection'];
        if (!isset($aCon['host']) || !isset($aCon['user']) || !isset($aCon['password'])) {
            $this->halt('MySQLi _connect() Connection settings not complete');
            return null;
        }

        // set existing option flags
        if (isset($aCon['options']) && is_array($aCon['options'])) {
            foreach ($aCon['options'] as $optKey => $optVal) {
                mysqli_options($dbh, $optKey, $optVal);
            }
        }

        if (($iPos = strpos($aCon['host'], ':')) !== false) {
            list($aCon['host'], $aCon['port']) = explode(':', $aCon['host']);
        } else {
            $aCon['port'] = null;
        }

        if (!isset($aCon['socket'])) {
            $aCon['socket'] = null;
        }
        if (!isset($aCon['flags'])) {
            $aCon['flags'] = null;
        }
        if (!isset($aCon['database'])) {
            $aCon['database'] = null;
        }

        $res = mysqli_real_connect(
            $dbh, $aCon['host'], $aCon['user'], $aCon['password'], $aCon['database'], 
            $aCon['port'], $aCon['socket'], $aCon['flags']
        );

        if ($res && $dbh && $aCon['database']) {
            if (!@mysqli_select_db($dbh, $aCon['database'])) {
                $this->halt('MySQLi _connect() Cannot use database ' . $aCon['database']);
                return null;
            }
            $this->Database = $aCon['database'];
        }

        $this->User = $aCon['user'];

        return $dbh;
    }


    /**
     * Discard the query result
     */
    public function free()
    {
        if (is_object($this->Query_ID)) {
            mysqli_free_result($this->Query_ID);
        }
        $this->Query_ID = 0;
    }


    /**
     * @see DB_Sql_Abstract::_query()
     */
    protected function _query($sQuery)
    {
        $this->Query_ID = mysqli_query($this->Link_ID, $sQuery);
        $this->Row      = 0;
        $this->Errno    = $this->_getErrorNumber();
        $this->Error    = $this->_getErrorMessage();
        if (!$this->Query_ID) {
            $this->halt($sQuery);
        }
    }


    /**
     * @see DB_Sql_Abstract::_insert()
     */
    protected function _insert($sTable, array $aFields)
    {
        $sFields = '';
        $sValues = '';
        foreach ($aFields as $field => $value) {
            $sFields .= '`' . $field . '`, ';
            if (is_int($value)) {
                $sValues .= $value . ', ';
            } else {
                $sValues .= "'" . $this->escape($value) . "', ";
            }
        }
        $sFields = substr($sFields, 0, -2);
        $sValues = substr($sValues, 0, -2);
        return sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $sTable, $sFields, $sValues);
    }


    /**
     * @see DB_Sql_Abstract::_update()
     */
    protected function _update($sTable, array $aFields, array $aWhere)
    {
        $sUpdate = '';
        $sWhere = '';
        foreach ($aFields as $field => $value) {
            $sUpdate .= '`' . $field . '`=';
            if (is_int($value)) {
                $sUpdate .= $value . ', ';
            } else {
                $sUpdate .= "'" . $this->escape($value) . "', ";
            }
        }

        foreach ($aWhere as $field => $value) {
            $sWhere .= '`' . $field . '`=';
            if (is_int($value)) {
                $sWhere .= $value . ' AND ';
            } else {
                $sWhere .= "'" . $this->escape($value) . "' AND ";
            }
        }

        $sUpdate = substr($sUpdate, 0, -2);
        $sWhere = substr($sWhere, 0, -5);
        return sprintf('UPDATE `%s` SET %s WHERE %s', $sTable, $sUpdate, $sWhere);
    }


    /**
     * @see DB_Sql_Abstract::next_record()
     */
    public function next_record()
    {
        $this->Record = mysqli_fetch_array($this->Query_ID, MYSQLI_BOTH);
        $this->Row   += 1;
        $this->Errno  = $this->_getErrorNumber();
        $this->Error  = $this->_getErrorMessage();

        $stat = is_array($this->Record);
        if (!$stat && $this->Auto_Free) {
            $this->free();
        }
        return $stat;
    }


    /**
     * @see DB_Sql_Abstract::seek()
     */
    public function seek($pos = 0)
    {
        $status = mysqli_data_seek($this->Query_ID, $pos);
        if ($status) {
            $this->Row = $pos;
        } else {
            $this->halt("seek($pos) failed: result has " . $this->num_rows() . " rows.");
            // half assed attempt to save the day, but do not consider this
            // documented or even desireable behaviour.
            mysqli_data_seek($this->Query_ID, $this->num_rows());
            $this->Row = $this->num_rows();
            return 0;
        }

        return 1;
    }


    /**
     * @see DB_Sql_Abstract::lock()
     */
    public function lock($table, $mode = 'write')
    {
        if ($this->_bNolock == true) {
            return true;
        }
        $query = 'LOCK TABLES ';
        if (is_array($table)) {
            while (list ($key, $value) = each($table)) {
                if (!is_int($key)) {
                    // texts key are "read", "read local", "write", "low priority write"
                    $query .= "$value $key, ";
                } else {
                    $query .= "$value $mode, ";
                }
            }
            $query = substr($query, 0, -2);
        } else {
            $query .= "$table $mode";
        }
        $res = $this->query($query);
        if (!$res) {
            $this->halt('lock() failed.');
            return 0;
        }
        return $res;
    }


    /**
     * @see DB_Sql_Abstract::unlock()
     */
    public function unlock()
    {
        if ($this->_bNolock == true) {
            return true;
        }

        $res = $this->query('UNLOCK TABLES');
        if (!$res) {
            $this->halt('unlock() failed.');
        }
        return $res;
    }


    /**
     * @see DB_Sql_Abstract::affected_rows()
     */
    public function affected_rows()
    {
        return ($this->Link_ID) ? mysqli_affected_rows($this->Link_ID) : 0;
    }


    /**
     * @see DB_Sql_Abstract::num_rows()
     */
    public function num_rows()
    {
        return ($this->Query_ID) ? mysqli_num_rows($this->Query_ID) : 0;
    }


    /**
     * @see DB_Sql_Abstract::num_fields()
     */
    public function num_fields()
    {
        return ($this->Query_ID) ? mysqli_num_fields($this->Query_ID) : 0;
    }


    /**
     * @see DB_Sql_Abstract::nextid()
     */
    public function nextid($seq_name)
    {
        $nextid = 0;

        if ($this->lock($this->Seq_Table)) {
            $oDb = $this->_getInstanceClone();
            // get sequence number (locked) and increment
            $oDb->query("SELECT nextid FROM `%s` WHERE seq_name='%s'", $this->Seq_Table, $seq_name);
            $res = ($oDb->next_record()) ? $oDb->toArray() : null;

            // No current value, make one
            if (!is_array($res)) {
                $currentid = 0;
                $oDb->query("INSERT INTO `%s` VALUES('%s', %s)", $this->Seq_Table, $seq_name, $currentid);
            } else {
                $currentid = $res['nextid'];
            }
            $nextid = $currentid + 1;
            $oDb->query("UPDATE `%s` SET nextid=%s WHERE seq_name='%s'", $this->Seq_Table, $nextid, $seq_name);
            $oDb->free();
            $this->unlock();
        } else {
            $this->halt('Cannot lock ' . $this->Seq_Table . ' - has it been created?');
        }
        return $nextid;
    }


    /**
     * @see DB_Sql_Abstract::disconnect()
     */
    public function disconnect()
    {
        $this->_debug("Debug: Disconnecting $this->Link_ID...");
        if (is_resource($this->Link_ID)) {
            mysqli_close($this->Link_ID);
            $this->_removeConnection($this->Link_ID);
        }
        $this->Link_ID  = 0;
        $this->Query_ID = 0;
    }


    /**
     * @see DB_Sql_Abstract::_metaData()
     */
    protected function _metaData($table = '', $full = false)
    {
        $count = 0;
        $id    = 0;
        $aRes   = array();

        /*
         * Due to compatibility problems with Table we changed the behavior
         * of metadata();
         * depending on $full, metadata returns the following values:
         *
         * - full is false (default):
         * $result[]:
         *   [0]["table"]  table name
         *   [0]["name"]   field name
         *   [0]["type"]   field type
         *   [0]["len"]    field length
         *   [0]["flags"]  field flags
         *
         * - full is true
         * $result[]:
         *   ["num_fields"] number of metadata records
         *   [0]["table"]  table name
         *   [0]["name"]   field name
         *   [0]["type"]   field type
         *   [0]["len"]    field length
         *   [0]["flags"]  field flags
         *   ["meta"][field name]  index of field named "field name"
         *   This last one could be used if you have a field name, but no index.
         *   Test:  if (isset($result['meta']['myfield'])) { ...
         */

        // if no $table specified, assume that we are working with a query
        // result
        if (!empty($table)) {
            $oDb = $this->_getInstanceClone();
            $oDb->query('SELECT * FROM `%s` LIMIT 1', $table);
            $id = $oDb->Query_ID;
            if (!$id) {
                $this->halt('Metadata query failed.');
                return false;
            }
        } else {
            $id = $this->Query_ID;
            if (!$id) {
                $this->halt('No query specified.');
                return false;
            }
        }

        $count = mysqli_num_fields($id);

        // made this IF due to performance (one if is faster than $count if's)
        for ($i = 0; $i < $count; $i ++) {
            $finfo = mysqli_fetch_field($id);
            $aRes[$i]['table'] = $finfo->table;
            $aRes[$i]['name']  = $finfo->name;
            $aRes[$i]['type']  = $this->_aDataTypes[$finfo->type];
            $aRes[$i]['len']   = $finfo->max_length;
            $aRes[$i]['flags'] = $finfo->flags;
            if ($full) {
                $aRes['meta'][$aRes[$i]['name']] = $i;
            }
        }
        if ($full) {
            $aRes['num_fields'] = $count;
        }

        // free the result only if we were called on a table
        if (!empty($table)) {
            $oDb->free();
        }
        return $aRes;
    }


    /**
     * @see DB_Sql_Abstract::escape()
     */
    public function escape($sString)
    {
        $sResult = '';
        if (is_resource($this->Link_ID) || $this->connect()) {
            $sResult = mysqli_real_escape_string($this->Link_ID, $sString);
        };
        return $sResult;
    }


    /**
     * @see DB_Sql_Abstract::_tableNames()
     */
    protected function _tableNames()
    {
        $aReturn = array();
        $oDb = $this->_getInstanceClone();
        if ($oDb->query('SHOW TABLES')) {
            while ($oDb->next_record()) {
                $aReturn[] = array(
                    'table_name' => $oDb->Record[0],
                    'tablespace_name' => $this->Database,
                    'database' => $this->Database,
                );
            }
            $oDb->free();
        }
        return $aReturn;
    }


    /**
     * @see DB_Sql_Abstract::_serverInfo()
     */
    protected function _serverInfo()
    {
        $arr = array();
        $arr['description'] = mysqli_get_server_info($this->Link_ID);
        return $arr;
    }


    /**
     * @see DB_Sql_Abstract::_getErrorMessage()
     */
    protected function _getErrorMessage()
    {
        if ($this->Link_ID) {
            return @mysqli_error($this->Link_ID);
        } else {
            return @mysqli_connect_error();
        }
    }


    /**
     * @see DB_Sql_Abstract::_getErrorNumber()
     */
    protected function _getErrorNumber()
    {
        if ($this->Link_ID) {
            return @mysqli_errno($this->Link_ID);
        } else {
            return @mysqli_connect_errno();
        }
    }


    /**
     * This method equates to mysqli_fetch_object(). It returns the current
     * result set as object or null if no result set is left. If optional
     * param $sClassName is set, the result object is an instance of class
     * $sClassName.
     *
     * @return object|null
     *
     * @author Holger Librenz <holger.librenz@4fb.de>
     * @version 1.0
     */
    public function getResultObject($sClassName = null)
    {
        $oResult = null;

        if (is_resource($this->Link_ID) && is_resource($this->Query_ID)) {
            if ($sClassName == null) {
                $oResult = mysqli_fetch_object($this->Query_ID);
            } else {
                $oResult = mysqli_fetch_object($this->Query_ID, $sClassName);
            }
        }

        return $oResult;
    }
}
