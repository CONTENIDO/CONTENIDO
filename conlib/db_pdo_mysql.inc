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
 * @version    0.1.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.15
 *
 * {@internal
 *   created  2011-02-28
 *   modified 2011-03-13, Murat Purc, Cleanup and documentation.
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
    public $Query_ID;

    /**
     * PDO statement
     * @var PDOStatement
     */
    public $Link_ID;

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
     * @see DB_Sql_Abstract::_connect()
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
            return null;
        }

        return $dbh;
    }


    /**
     * Discard the query result
     */
    public function free()
    {
        if ($this->Query_ID) {
            $this->Query_ID->closeCursor();
            unset($this->Query_ID);
        }
    }


    /**
     * @see DB_Sql_Abstract::_query()
     */
    protected function _query($sQuery)
    {
        $this->Query_ID = $this->Link_ID->query($sQuery);
        $this->Row      = 0;
        $this->Errno    = $this->_getErrorNumber();
        $this->Error    = $this->_getErrorMessage();
        if (!$this->Query_ID) {
            $this->halt($sQuery);
        }
    }


    /**
     * @see DB_Sql_Abstract::next_record()
     */
    public function next_record()
    {
        $this->Record = $this->Query_ID->fetch(PDO::FETCH_BOTH);
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
        throw new Exception('seek not supported');
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
        return ($this->Query_ID) ? $this->Query_ID->rowCount() : 0;
    }


    /**
     * @see DB_Sql_Abstract::num_rows()
     */
    public function num_rows()
    {
        if ($this->Query_ID) {
            // clone statement and get count by using fetchAll
            $stmt = clone $this->Query_ID;
            $res  = $stmt->fetchAll();
            return (is_array($res)) ? count($stmt->fetchAll()) : 0;
        } else {
            return 0;
        }
    }


    /**
     * @see DB_Sql_Abstract::num_fields()
     */
    public function num_fields()
    {
        return count($this->Record / 2);
    }


    /**
     * @see DB_Sql_Abstract::nextid()
     */
    public function nextid($seq_name)
    {
        $this->connect();

        if ($this->lock($this->Seq_Table)) {
            /* get sequence number (locked) and increment */
            $q = sprintf("SELECT nextid FROM `%s` WHERE seq_name = '%s'", $this->Seq_Table, $seq_name);
            $stmt = $this->Link_ID->query($q);
            $res = ($stmt) ? $stmt->fetch(PDO::FETCH_BOTH) : null;

            /* No current value, make one */
            if (!is_array($res)) {
                $currentid = 0;
                $q = sprintf("INSERT INTO `%s` VALUES('%s', %s)", $this->Seq_Table, $seq_name, $currentid);
                $stmt = $this->Link_ID->query($q);
            } else {
                $currentid = $res['nextid'];
            }
            $nextid = $currentid + 1;
            $q = sprintf("UPDATE `%s` set nextid = '%s' WHERE seq_name = '%s'", $this->Seq_Table, $nextid, $seq_name);
            $stmt = $this->Link_ID->query($q);
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
        // Destroy the PDO and PDOStatement object
        $this->_removeConnection($this->Link_ID);
        $this->Link_ID  = null;
        $this->Query_ID = null;
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
        if ($table) {
            $this->connect();
            $stmt = $this->Link_ID->query(sprintf("DESCRIBE `%s`", $table));
            if (!$stmt) {
                $this->halt('Metadata query failed.');
                return false;
            }
        } else {
            $stmt = $this->Query_ID;
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


    /**
     * @see DB_Sql_Abstract::escape()
     */
    public function escape($sString)
    {
        $sResult = '';
        $sResult = str_replace("'", "''", $sString);
        // @todo adapt pdo quote method to own requirements
#        if ($this->connect()) {
#            $sResult = $this->Link_ID->quote($sString);
#        }
        return $sResult;
    }


    /**
     * @see DB_Sql_Abstract::_tableNames()
     */
    protected function _tableNames()
    {
        $return = array();

        $stmt = $this->Link_ID->query('SHOW TABLES');
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


    /**
     * @see DB_Sql_Abstract::_serverInfo()
     */
    protected function _serverInfo()
    {
        $arr['description'] = $this->Link_ID->getAttribute(PDO::ATTR_SERVER_INFO);
        $arr['version'] = $this->Link_ID->getAttribute(PDO::ATTR_SERVER_VERSION);
        return $arr;
    }


    /**
     * @see DB_Sql_Abstract::_getErrorMessage()
     */
    protected function _getErrorMessage()
    {
        $err = null;
        if ($this->Query_ID) {
            $err = $this->Query_ID->errorInfo();
        } elseif ($this->Link_ID) {
            $err = $this->Link_ID->errorInfo();
        }
        if (null !== $err && (int) $err[0] > 0) {
            return $err[2];
        }
    }


    /**
     * @see DB_Sql_Abstract::_getErrorNumber()
     */
    protected function _getErrorNumber()
    {
        $err = null;
        if ($this->Query_ID) {
            $err = $this->Query_ID->errorCode();
        } elseif ($this->Link_ID) {
            $err = $this->Link_ID->errorCode();
        }
        if (null !== $err && (int) $err[0] > 0) {
            return $err[0];
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

        if ($this->Link_ID && $this->Query_ID) {
            if ($rs = $this->Query_ID->fetch(PDO::FETCH_ASSOC)) {
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
