<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * PDO MySQL database driver
 *
 * NOTE: Is still in development state, don't use it!
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    Contenido database
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.15
 *
 * {@internal
 *   created  2011-02-28
 *
 *   $Id: $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class DB_Sql extends DB_Sql_Abstract
{

    /**
     * PDO connection
     * @var PDO
     */
    protected $_con;

    /**
     * PDO statement
     * @var PDOStatement
     */
    protected $_stmt;

    protected $_aDataTypes = array(
        PDO::PARAM_BOOL     => 'bool',
        PDO::PARAM_NULL     => 'null',
        PDO::PARAM_INT      => 'int',
        PDO::PARAM_STR      => 'string',
        PDO::PARAM_LOB      => 'blob',
        PDO::PARAM_STMT     => 'statement'
    );


    /**
     * Constructor.
     *
     * @param  array  $options  Optional assoziative options
     */
    public function __construct(array $options = array())
    {
        $options = array_merge($options, array(
            'type' => 'pdo_mysql',
        ));
        parent::__construct($options);
    }


    /**
     * Establishes a connection to the database server.
     *
     * @return  object  Connection object
     */
    protected function _connect()
    {
        extract($this->_aDbCfg['connection']);

        $dsn = 'mysql:dbname=' . $database . ';host=' . $host;

        if (!isset($driver_options) || !is_array($driver_options)) {
            $driver_options = array();
        }

        try {
            // Create a new PDO connection
            $dbh = new PDO($dsn, $user, $password, $driver_options);
        } catch (PDOException $e) {
            $this->Errno = $e->getCode();
            $this->Error = $e->getMessage();
        }

        if (!$dbh) {
            $this->halt('PDO_MySQL _connect() Failed');
            return false;
        }

        $this->_con = $dbh;
        return $dbh;
    }


    /**
     * Discard the query result
     */
    public function free()
    {
        if ($this->_stmt) {
            $this->_stmt->closeCursor();
            unset($this->_stmt);
        }
    }


    /**
     * Performs a query.
     *
     * @param   string  $sQuery
     */
    protected function _query($sQuery)
    {
        $this->_stmt = $this->_con->prepare($sQuery);
        $this->_stmt->execute();

        $this->Query_ID = $this->_stmt;
        $this->Row      = 0;
        $this->Errno    = $this->_getErrorNumber();
        $this->Error    = $this->_getErrorMessage();

        if (!$this->Query_ID) {
            $this->halt($sQuery);
        }
    }


    /**
     * Fetches the next recordset from result set
     *
     * @param  bool
     */
    public function next_record()
    {
        $this->Record = $this->_stmt->fetch(PDO::FETCH_BOTH);
        $this->Row   += 1;
        $this->Errno  = $this->_getErrorNumber();
        $this->Error  = $this->_getErrorMessage();

        $stat = is_array($this->Record);
        if (!$stat && $this->Auto_Free) {
            $this->free();
        }
        return $stat;
    }


    /* public: position in result set */
    public function seek($pos = 0)
    {
        throw new Exception('seek not supported');
    }


    /* public: table locking */
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


    /* public: evaluate the result (size, width) */
    public function affected_rows()
    {
        return $this->_stmt->rowCount();
    }


    /**
     * Returns the number of rows
     *
     * @return  int
     */
    public function num_rows()
    {
        if ($this->_stmt) {
            // clone statement and get count by using fetchAll
            $stmt = clone $this->_stmt;
            return (count($stmt->fetchAll()));
        } else {
            return 0;
        }
    }


    public function num_fields()
    {
        return count($this->Record / 2);
    }


    /* public: sequence numbers */
    public function nextid($seq_name)
    {
        $this->connect();

        if ($this->lock($this->Seq_Table)) {
            /* get sequence number (locked) and increment */
            $q = sprintf("SELECT nextid FROM `%s` WHERE seq_name = '%s'", $this->Seq_Table, $seq_name);
            $stmt = $this->_con->prepare($g);
            $stmt->execute();
            $res = $stmt->fetch(PDO::FETCH_BOTH);

            /* No current value, make one */
            if (!is_array($res)) {
                $currentid = 0;
                $q = sprintf("INSERT INTO `%s` values('%s', %s)", $this->Seq_Table, $seq_name, $currentid);
                $stmt = $this->_con->prepare($q);
                $stmt->execute();
            } else {
                $currentid = $res['nextid'];
            }
            $nextid = $currentid +1;
            $q = sprintf("UPDATE `%s` set nextid = '%s' WHERE seq_name = '%s'", $this->Seq_Table, $nextid, $seq_name);
            $stmt = $this->_con->prepare($q);
            $stmt->execute();
            $this->unlock();
        } else {
            $this->halt('Cannot lock ' . $this->Seq_Table . ' - has it been created?');
            return 0;
        }
        return $nextid;
    }


    /**
     * Closes the connection.
     *
     * @return  void
     */
    public function disconnect()
    {
        $this->_debug("Debug: Disconnecting $this->Link_ID...");
        // Destroy the PDO and PDOStatement object
        $this->_removeConnection($this->_con);
        $this->_con = null;
        $this->_stmt = null;
        $this->Link_ID  = null;
        $this->Query_ID = null;
    }


    /* public: return table metadata */
    protected function _metadata($table = '', $full = false)
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
        if ($table) {
            $this->connect();
            $stmt = $this->_con->prepare(sprintf("DESCRIBE `%s`", $table));
            $stmt->execute();
            if (!$stmt) {
                $this->halt('Metadata query failed.');
                return false;
            }
        } else {
            $stmt = $this->_stmt;
            if (!$stmt) {
                $this->halt('No query specified.');
                return false;
            }
        }

        // loop thru the result and collect meta data
        $res = array();
        while ($rs = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $field = $this->_getFieldTypeDetails($rs['Type']);
            $item = array();
            $item['table'] = $table;
            $item['name']  = $rs['Field'];
            $item['type']  = $field['type'];
            $item['len']   = $field['size'];
            $item['flags'] = null; // @todo  detect field flags
            $res[$count] = $item;
            if ($full) {
                $res['meta'][$item['name']] = $count;
            }
            $count++;
        }
        if ($full) {
            $res['num_fields'] = $count;
        }

        unset($stmt);

        return $res;
    }


    /* public: Escape string for using in SQL-Statement */
    public function escape($sString)
    {
        $sResult = '';
        if ($this->connect()) {
            $sResult = $this->_con->quote($sString);
        }
        return $sResult;
    }


    /* public: find available table names */
    public function table_names()
    {
        $return = array();
        if (!$this->connect()) {
            return $return;
        }

        $stmt = $this->_con->prepare('SHOW TABLES');
        $stmt->execute();
        $i = 0;
        while ($rs = $stmt->fetch(PDO::FETCH_NUM)) {
            $return[$i]['table_name']      = $rs[0];
            $return[$i]['tablespace_name'] = $this->Database;
            $return[$i]['database']        = $this->Database;
            $i ++;
        }

        unset($stmt);

        return $return;
    }


    // FIXME we have disconnect() for this
    public function close()
    {
        $this->disconnect();
    }


    public function server_info()
    {
        if (!$this->connect()) {
            return null;
        }
        $arr['description'] = $this->_con->getAttribute(PDO::ATTR_SERVER_INFO);
        $arr['version'] = $this->_con->getAttribute(PDO::ATTR_SERVER_VERSION);
        return $arr;
    }


    /**
     * Returns error message of last occured error by using databases interface
     */
    protected function _getErrorMessage()
    {
        if ($this->_stmt) {
            return $this->_stmt->errorInfo();
        } elseif ($this->_con) {
            return $this->_con->errorInfo();
        } else {
            return '';
        }
    }


    /**
     * Returns error code of last occured error by using databases interface
     */
    protected function _getErrorNumber()
    {
        if ($this->_stmt) {
            return $this->_stmt->errorCode();
        } elseif ($this->_con) {
            return $this->_con->errorCode();
        } else {
            return 0;
        }
    }


    protected function _getFieldTypeDetails($field)
    {
        $ret = array('type' => null, 'size' => null);
        if (!$field) {
            return $ret;
        }
        if (preg_match('/^([a-z].*)\(([0-9].*)\)/', $field, $matches)) {
            $ret = array('type' => $matches[1], 'size' => (int) $matches[2]);
        } else {
            $ret['type'] = $field;
        }
        return $ret;
    }

    /**
     * This method festches the current result set and returns the it as object or
     * null if no result set is left. If optional param $sClassName is set, the
     * result object is an instance of class $sClassName.
     *
     * @param   string  sClassName  Optional the classname to instantiate.
     * @return  object|null
     */
    public function getResultObject($sClassName = null)
    {
        $oResult = null;

        if ($this->_con && $this->_stmt) {
            if ($rs = $this->_stmt->fetch(PDO::FETCH_ASSOC)) {
                if ($sClassName == null) {
                    $oResult = (object) $rs;
                } else {
                    $oResult = new $sClassName();
                    foreach ($rs as $key => $value) {
                        $oResult->{$key} = $value;
                    }
                }
            }
        }

        return $oResult;
    }

}
