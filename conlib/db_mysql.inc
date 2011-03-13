<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * MySQL database driver
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    Contenido database
 * @version    0.2.1
 * @author     Boris Erdmann, Kristian Koehntopp
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 *
 * {@internal
 *   created  2002-07-21
 *   modified 2008-07-04, bilal arslan, added security fix
 *   modified 2009-10-29, Murat Purc, removed deprecated functions (PHP 5.3 ready) extended DB_Sql_Abstract, added/optimized some functioms and some formatting
 *   modified 2009-12-18, Murat Purc, Replaced mysql_list_fields() against a SQL statement, see [#CON-262]
 *   modified 2011-03-03, Murat Purc, Some redesign and improvements (partial adaption to PHP 5 and extending DB_Sql_Abstract).
 *   modified 2011-03-13, Murat Purc, Cleanup and documentation.
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

    /**
     * Constructor.
     *
     * @param  array  $options  Optional assoziative options
     */
    public function __construct(array $options = array())
    {
        $options = array_merge($options, array(
            'type' => 'mysql'
        ));
        parent::__construct($options);
    }


    /**
     * @see DB_Sql_Abstract::_connect()
     */
    protected function _connect()
    {
        extract($this->_aDbCfg['connection']);

        // establish connection, select database
        $dbh = @mysql_connect($host, $user, $password);
        if (!$dbh || !is_resource($dbh)) {
            $this->halt('MySQL _connect() Failed');
            return null;
        }

        if ($database != '') {
            if (!@mysql_select_db($database, $dbh)) {
                $this->halt('MySQL _connect() Cannot use database ' . $database);
                return null;
            }
        }

        return $dbh;
    }


    /**
     * Discard the query result
     */
    public function free()
    {
        @mysql_free_result($this->Query_ID);
        $this->Query_ID = 0;
    }


    /**
     * @see DB_Sql_Abstract::_query()
     */
    protected function _query($sQuery)
    {
        $this->Query_ID = @mysql_query($sQuery, $this->Link_ID);
        $this->Row      = 0;
        $this->Error    = $this->_getErrorMessage();
        $this->Errno    = $this->_getErrorNumber();
        if (!$this->Query_ID) {
            $this->halt($sQuery);
        }
    }


    /**
     * @see DB_Sql_Abstract::next_record()
     */
    public function next_record()
    {
        $this->Record = @mysql_fetch_array($this->Query_ID);
        $this->Row   += 1;
        $this->Error    = $this->_getErrorMessage();
        $this->Errno    = $this->_getErrorNumber();

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
        $status = @mysql_data_seek($this->Query_ID, $pos);
        if ($status) {
            $this->Row = $pos;
        } else {
            $this->halt("seek($pos) failed: result has ".$this->num_rows()." rows.");
            // half assed attempt to save the day, but do not consider this
            // documented or even desireable behaviour.
            @mysql_data_seek($this->Query_ID, $this->num_rows());
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
            while (list($key,$value) = each($table)) {
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
        return ($this->Link_ID) ? mysql_affected_rows($this->Link_ID) : 0;
    }


    /**
     * @see DB_Sql_Abstract::num_rows()
     */
    public function num_rows()
    {
        return ($this->Query_ID) ? mysql_num_rows($this->Query_ID) : 0;
    }


    /**
     * @see DB_Sql_Abstract::num_fields()
     */
    public function num_fields()
    {
        return ($this->Query_ID) ? mysql_num_fields($this->Query_ID) : 0;
    }


    /**
     * @see DB_Sql_Abstract::num_fields()
     */
    public function nextid($nextid)
    {
        $this->connect();

        if ($this->lock($this->Seq_Table)) {
            /* get sequence number (locked) and increment */
            $q  = sprintf("SELECT nextid FROM `%s` WHERE seq_name = '%s'", $this->Seq_Table, $seq_name);
            $id  = @mysql_query($q, $this->Link_ID);
            $res = @mysql_fetch_array($id);

            /* No current value, make one */
            if (!is_array($res)) {
                $currentid = 0;
                $q = sprintf("INSERT INTO `%s` VALUES('%s', %s)", $this->Seq_Table, $seq_name, $currentid);
                $id = @mysql_query($q, $this->Link_ID);
            } else {
                $currentid = $res['nextid'];
            }
            $nextid = $currentid + 1;
            $q  = sprintf("UPDATE `%s` SET nextid = '%s' WHERE seq_name = '%s'", $this->Seq_Table, $nextid, $seq_name);
            $id = @mysql_query($q, $this->Link_ID);
            $this->unlock();
        } else {
            $this->halt('Cannot lock ' . $this->Seq_Table . ' - has it been created?');
            return 0;
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
            mysql_close($this->Link_ID);
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
        $res   = array();

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
            $this->connect();
            $id = mysql_query(sprintf('SELECT * FROM `%s` LIMIT 1', $table), $this->Link_ID); // PHP 5.3 fix 07.2009 O.Pinke, PHP 5.3 crashes with deprecated mysql_list_fields()
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
        for ($i=0; $i<$count; $i++) {
            $res[$i]['table'] = @mysql_field_table($id, $i);
            $res[$i]['name']  = @mysql_field_name($id, $i);
            $res[$i]['type']  = @mysql_field_type($id, $i);
            $res[$i]['len']   = @mysql_field_len($id, $i);
            $res[$i]['flags'] = @mysql_field_flags($id, $i);
            if ($full) {
                $res['meta'][$res[$i]['name']] = $i;
            }
        }
        if ($full) {
            $res['num_fields'] = $count;
        }

        // free the result only if we were called on a table
        if ($table) {
            @mysql_free_result($id);
        }
        return $res;
    }


    /**
     * @see DB_Sql_Abstract::_tableNames()
     */
    protected function _tableNames()
    {
        $return = array();
        $this->connect();
        $h = @mysql_query('SHOW TABLES', $this->Link_ID);
        $i = 0;
        if (isset($h) && @mysql_num_rows($h) > 0) {
            while ($info = @mysql_fetch_row($h)) {
                $return[$i]['table_name']      = $info[0];
                $return[$i]['tablespace_name'] = $this->Database;
                $return[$i]['database']        = $this->Database;
                $i++;
            }
            @mysql_free_result($h);
        }
        return $return;
    }


    /**
     * @see DB_Sql_Abstract::escape()
     */
    public function escape($sString)
    {
        $sResult = '';
        if (is_resource($this->Link_ID) || $this->connect()) {
            $sResult = mysql_real_escape_string($sString, $this->Link_ID);
        }
        return $sResult;
    }


    /**
     * @see DB_Sql_Abstract::_serverInfo()
     */
    protected function _serverInfo()
    {
        $arr['description'] = mysql_get_server_info();
        return $arr;
    }


    /**
     * @see DB_Sql_Abstract::_getErrorMessage()
     */
    protected function _getErrorMessage()
    {
        if ($this->Link_ID) {
            return mysql_error($this->Link_ID);
        } else {
            return mysql_error();
        }
    }


    /**
     * @see DB_Sql_Abstract::_getErrorNumber()
     */
    protected function _getErrorNumber()
    {
        if ($this->Link_ID) {
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
    public function getResultObject($sClassName = null)
    {
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
