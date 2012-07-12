<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * MySQL database driver
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
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    CONTENIDO Core
 * @version    0.2.4
 * @author     Boris Erdmann, Kristian Koehntopp, Murat Purc <murat@purc>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <CONTENIDO Version>
 *
 * {@internal
 *   created  2002-07-21
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * MySQL database driver
 * @package    CONTENIDO Core
 * @subpackage Database
 */
class DB_Sql extends DB_Sql_Abstract {

    /**
     * Constructor.
     *
     * @param  array  $options  Optional assoziative options
     */
    public function __construct(array $options = array()) {
        $options = array_merge($options, array(
            'type' => 'mysql'
                ));
        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     */
    protected function _connect() {
        $aCon = $this->_aDbCfg['connection'];
        if (!isset($aCon['host']) || !isset($aCon['user']) || !isset($aCon['password'])) {
            $this->halt('MySQL _connect() Connection settings not complete');
            return null;
        }

        // establish connection, select database
        $dbh = @mysql_connect($aCon['host'], $aCon['user'], $aCon['password']);
        if (!$dbh || !is_resource($dbh)) {
            $this->halt('MySQL _connect() Failed');
            return null;
        }

        if (isset($aCon['database'])) {
            if (!@mysql_select_db($aCon['database'], $dbh)) {
                $this->halt('MySQL _connect() Cannot use database ' . $aCon['database']);
                return null;
            } else {
                //set connection charset
                if ($aCon['charset'] != '') {
                    if (!@mysql_set_charset($aCon['charset'], $dbh)) {
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
    public function free() {
        @mysql_free_result($this->Query_ID);
        $this->Query_ID = 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function _query($sQuery) {
        $this->Query_ID = @mysql_query($sQuery, $this->Link_ID);
        $this->Row = 0;
        $this->Error = $this->_getErrorMessage();
        $this->Errno = $this->_getErrorNumber();
        if (!$this->Query_ID) {
            $this->halt($sQuery);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function _insert($sTable, array $aFields) {
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
    protected function _update($sTable, array $aFields, array $aWhere) {
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
    public function next_record() {
        $this->Record = @mysql_fetch_array($this->Query_ID);
        $this->Row += 1;
        $this->Error = $this->_getErrorMessage();
        $this->Errno = $this->_getErrorNumber();

        $stat = is_array($this->Record);
        if (!$stat && $this->Auto_Free) {
            $this->free();
        }
        return $stat;
    }

    /**
     * {@inheritdoc}
     */
    public function seek($pos = 0) {
        $status = @mysql_data_seek($this->Query_ID, $pos);
        if ($status) {
            $this->Row = $pos;
        } else {
            $this->halt("seek($pos) failed: result has " . $this->num_rows() . " rows.");
            // half assed attempt to save the day, but do not consider this
            // documented or even desireable behaviour.
            @mysql_data_seek($this->Query_ID, $this->num_rows());
            $this->Row = $this->num_rows();
            return 0;
        }

        return 1;
    }

    /**
     * @deprecated [2012-07-05] No longer needed
     * {@inheritdoc}
     */
    public function lock($table, $mode = 'write') {
        cDeprecated("This function is no longer needed. Tables don't need to be locked.");

        if ($this->_bNolock == true) {
            return true;
        }
        $query = 'LOCK TABLES ';
        if (is_array($table)) {
            while (list($key, $value) = each($table)) {
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
     * @deprecated [2012-07-05] No longer needed
     * {@inheritdoc}
     */
    public function unlock() {
        cDeprecated("This function is no longer needed. Tables don't need to be locked.");

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
    public function affected_rows() {
        return ($this->Link_ID) ? mysql_affected_rows($this->Link_ID) : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function num_rows() {
        return ($this->Query_ID) ? mysql_num_rows($this->Query_ID) : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function num_fields() {
        return ($this->Query_ID) ? mysql_num_fields($this->Query_ID) : 0;
    }

    /**
     * get next possible id for requested db-table and update sequence table
     *
     * @deprecated [2012-07-05] No longer needed
     * @param string $seq_name name of db-table to get nextid
     * @return int  nextid for requested table or 0 if an error occured
     */
    public function nextid($seq_name) {
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
    public function disconnect() {
        $this->_debug("Debug: Disconnecting $this->Link_ID...");
        if (is_resource($this->Link_ID)) {
            mysql_close($this->Link_ID);
            $this->_removeConnection($this->Link_ID);
        }
        $this->Link_ID = 0;
        $this->Query_ID = 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function _metaData($table = '', $full = false) {
        $count = 0;
        $id = 0;
        $aRes = array();

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

        $count = @mysql_num_fields($id);

        // made this IF due to performance (one if is faster than $count if's)
        for ($i = 0; $i < $count; $i++) {
            $aRes[$i]['table'] = @mysql_field_table($id, $i);
            $aRes[$i]['name'] = @mysql_field_name($id, $i);
            $aRes[$i]['type'] = @mysql_field_type($id, $i);
            $aRes[$i]['len'] = @mysql_field_len($id, $i);
            $aRes[$i]['flags'] = @mysql_field_flags($id, $i);
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
    protected function _tableNames() {
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
    public function escape($sString) {
        $sResult = '';
        if (is_resource($this->Link_ID) || $this->connect()) {
            $sResult = mysql_real_escape_string($sString, $this->Link_ID);
        }
        return $sResult;
    }

    /**
     * {@inheritdoc}
     */
    protected function _serverInfo() {
        if (is_resource($this->Link_ID)) {
            $arr = array();
            $arr['description'] = mysql_get_server_info($this->Link_ID);
            return $arr;
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function _getErrorMessage() {
        if (is_resource($this->Link_ID)) {
            return mysql_error($this->Link_ID);
        } else {
            return mysql_error();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function _getErrorNumber() {
        if (is_resource($this->Link_ID)) {
            return mysql_errno($this->Link_ID);
        } else {
            return mysql_errno();
        }
    }

    /**
     * This method equates to mysql_fetch_object(). It returns the current
     * result set as object or null if no result set is left. If optional
     * param $sClassName is set, the result object is an instance of class
     * $sClassName.
     *
     * @return object
     *
     * @author Holger Librenz <holger.librenz@4fb.de>
     * @version 1.0
     */
    public function getResultObject($sClassName = null) {
        $oResult = null;

        if (is_resource($this->Link_ID) && is_resource($this->Query_ID)) {
            if ($sClassName == null) {
                $oResult = mysql_fetch_object($this->Query_ID);
            } else {
                $oResult = mysql_fetch_object($this->Query_ID, $sClassName);
            }
        }

        return $oResult;
    }

}
