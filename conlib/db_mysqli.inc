<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * MySQLi database driver.
 *
 * Configurable via global $cfg['db']['connection'] configuration as follows:
 * <pre>
 * - host      (string)  Hostname or ip
 * - database  (string)  Database name
 * - user      (string)  User name
 * - password  (string)  User password
 * - options   (array)   Optional, MySQLi options array
 * - socket    (int)     Optional, socket
 * - port      (int)     Optional, port
 * - flags     (int)     Optional, flags
 * - charset  (string)   Optional, connection charset
 * see http://www.php.net/manual/en/mysqli.real-connect.php
 * </pre>
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    CONTENIDO Core
 * @subpackage Database
 * @version    0.3.2
 * @author     Boris Erdmann, Kristian Koehntopp, Murat Purc <murat@purc>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <CONTENIDO Version>
 *
 * {@internal
 *   created  2000-01-01
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * MySQLi database driver
 * @package    CONTENIDO Core
 * @subpackage Database
 */
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
     * {@inheritdoc}
     */
    protected function _connect()
    {
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
            } else {
                //set connection charset
                if ($aCon['charset'] != '') {
                    if (!@mysqli_set_charset($dbh, $aCon['charset'])) {
                        $this->halt('Could not set database charset to ' . $aCon['charset']);
                        return null;
                    }
                }
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function affected_rows()
    {
        return ($this->Link_ID) ? mysqli_affected_rows($this->Link_ID) : 0;
    }


    /**
     * {@inheritdoc}
     */
    public function num_rows()
    {
        return ($this->Query_ID) ? mysqli_num_rows($this->Query_ID) : 0;
    }


    /**
     * {@inheritdoc}
     */
    public function num_fields()
    {
        return ($this->Query_ID) ? mysqli_num_fields($this->Query_ID) : 0;
    }


    /**
     * @deprecated [2012-07-05] No longer needed
     * {@inheritdoc}
     */
    public function nextid($seq_name)
    {
    	cDeprecated("This function is no longer needed. The ids get updated by the DB");

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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    protected function _serverInfo()
    {
        if ($this->Link_ID) {
            $arr = array();
            $arr['description'] = mysqli_get_server_info($this->Link_ID);
            return $arr;
        }
        return null;
    }


    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
