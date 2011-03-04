<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Base (abstract) database class. Each database driver file should extend this!
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    Contenido Database
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since Contenido release 4.8.15
 *
 * {@internal
 *   created  2011-03-03
 *
 *   $Id:$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


abstract class DB_Sql_Abstract
{

    const HALT_YES = 'yes';
    const HALT_NO  = 'no';
    const HALT_REPORT  = 'report';

    /**
     * Assoziative list of database connections
     * @array
     */
    protected static $_connectionCache = array();

    /**
     * Assoziative list of database tables metadata
     * @array
     */
    protected static $_metaCache = array();

    /**
     * Sequence table name
     * @var  string
     */
    public $Seq_Table = '';

    /**
     * Flag to print debug messages
     * @var  bool
     */
    public $Debug = false;

    /**
     * Database name
     * @var  string
     */
    public $Database = '';

    /**
     * Database connection user name
     * @var  string
     */
    public $User = '';

    /**
     * Database connection password
     * @var  string
     */
    public $Password = '';

    /**
     * Database connection resource
     * @var  resource|int
     */
    public $Link_ID = 0;

    /**
     * Resource identifier of last executed query
     * @var  resource
     */
    public $Query_ID  = 0;

    /**
     * Recordset data array. Could contain either indexed or assoziative result set (or both)
     * @var  array
     */
    public $Record = array();

    /**
     * The row position inside last executed select result
     * @var  int
     */
    public $Row;

    /**
     * Database error number
     * @var  int
     */
    public $Errno = 0;

    /**
     * Database error message
     * @var  string
     */
    public $Error = '';

    /**
     * Flag to automatic free results
     * @var  int
     */
    public $Auto_Free = 0;

    /**
     * Database identification string.
     * @var  string
     */
    public $type = '';

    /**
     * Revision. This is an api revision, not a CVS revision
     * @var  string
     */
    public $revision = '1.3';

    /**
     * Halt status during occured errors. Feasible values are
     * - "yes" (halt with message)
     * - "no" (ignore errors quietly)
     * - "report" (ignore errror, but spit a warning)
     * @var  string
     */
    public $Halt_On_Error = 'no';

    /**
     * Text to prepend to the halt message
     * @var  string
     */
     protected $_sHaltMsgPrefix = '';

    /**
     * Default database connection for all instances
     * @var  array
     */
    protected static $_aDefaultDbCfg = array();

    /**
     * Database connection configuration for current instance
     * @var  array
     */
    protected $_aDbCfg;

    /**
     * Enable profiling
     * @var  bool
     */
    protected $_bProfile = false;

    /**
     * Don't lock tables
     * @var  bool
     */
    protected $_bNolock = false;

    /**
     * Profile data array
     * @var  array
     */
    protected static $_aProfileData = array();

    // @todo intended to map new protected properties against old public
    // properties by using magic __call method
    protected $_oldProperties = array(
        'Seq_Table' => '_sSeqTable',
        'Debug' => '_bDebug',
        'Database' => '_sDatabase',
        'User' => '_sUser',
        'Password' => '_sPassword',
        'Link_ID' => '_mLinkId',
        'Query_ID' => '_mQueryId',
        'Auto_Free' => '_iAutoFree',
        'type' => '_sType',
        'revision' => '_sRevision',
        'Halt_On_Error' => '_sHaltOnError',
    );


    /**
     * Constructor, sets passed options and connects to the DBMS, if not done before.
     *
     * Uses default connection settings, passed $options['db_connection'] settings
     * will overwrite connection settings for current instance.
     *
     * @param  array  $options  Assoziative options as follows:
     *                          - $options['sequenceTable']  (string)  Optional, the sequence table name
     *                          - $options['nolock']  (bool)  Optional, not lock table
     *                          - $options['haltBehavior']  (string)  Optional, halt behavior on occured errors
     *                          - $options['haltMsgPrefix']  (string)  Optional, Text to prepend to the halt message
     *                          - $options['type']  (string)  Database driver name
     *                          - $options['enableProfiling']  (bool)  Optional, flag to enable profiling
     *                          - $options['connection']  (array)  Optional, assoziative connection settings
     * @return  void
     */
    protected function __construct(array $options)
    {
        // use default connection configuration, but overwrite it by passed options
        $this->_aDbCfg = array_merge(self::$_aDefaultDbCfg, $options);

        if (isset($this->_aDbCfg['sequenceTable']) && is_string($this->_aDbCfg['sequenceTable'])) {
            $this->Seq_Table = $this->_aDbCfg['sequenceTable'];
        }

        if (isset($this->_aDbCfg['haltBehavior'])) {
            switch ($this->_aDbCfg['haltBehavior']) {
                case self::HALT_YES:
                    $this->Halt_On_Error = self::HALT_YES;
                    break;
                case self::HALT_NO:
                    $this->Halt_On_Error = self::HALT_NO;
                    break;
                case self::HALT_REPORT:
                    $this->Halt_On_Error = self::HALT_REPORT;
                    break;
            }
        }

        if (isset($this->_aDbCfg['haltMsgPrefix']) && is_string($this->_aDbCfg['haltMsgPrefix'])) {
            $this->_sHaltMsgPrefix = $this->_aDbCfg['haltMsgPrefix'];
        }

        if (isset($this->_aDbCfg['type']) && is_string($this->_aDbCfg['type'])) {
            $this->type = $this->_aDbCfg['type'];
        }

        if (isset($this->_aDbCfg['nolock']) && is_bool($this->_aDbCfg['nolock'])) {
            $this->_bNolock = (bool) $this->_aDbCfg['nolock'];
        }
        if (isset($this->_aDbCfg['enableProfiling']) && is_bool($this->_aDbCfg['enableProfiling'])) {
            $this->_bProfile = (bool) $this->_aDbCfg['enableProfiling'];
        }

        $this->connect();
    }

    /**
     * Setter for default database configuration, the connection values.
     *
     * @param  array  $defaultDbCfg
     */
    public static function setDefaultConfiguration(array $defaultDbCfg)
    {
        self::$_aDefaultDbCfg = $defaultDbCfg;
    }

    /**
     * Establishes a connection to the database server.
     *
     * @return  object  Connection object
     */
    public function connect()
    {
        if ($this->Link_ID = $this->_getConnection($this->_aDbCfg['connection'])) {
            return $this->Link_ID;
        } else {
            if ($this->Link_ID = $this->_connect()) {
                $this->_setConnection($this->_aDbCfg['connection'], $this->Link_ID);
                return $this->Link_ID;
            }
        }
        return null;
    }

    /**
     * Resource id/object of current connection
     *
     * @return  mixed  The resource id/object of current connection
     */
    public function link_id()
    {
        return $this->Link_ID;
    }


    /**
     * Returns connection from connection cache
     *
     * @param   mixed  $data  Connection data array
     * @return  mixed  Either  The connection (object, resource, integer) or null
     */
    protected function _getConnection($data)
    {
        $hash = md5($this->type . '-' . (is_array($data) ? implode('-', $data) : (string) $data));
        return (isset(self::$_connectionCache[$hash])) ? self::$_connectionCache[$hash] : null;
    }


    /**
     * Stores connection in connection cache
     *
     * @param   mixed  $data        Connection data array
     * @param   mixed  $connection  The connection to store in cache
     * @return  void
     */
    protected function _setConnection($data, $connection)
    {
        $hash = md5($this->type . '-' . (is_array($data) ? implode('-', $data) : (string) $data));
        self::$_connectionCache[$hash] = $connection;
    }


    /**
     * Removes connection from cache
     *
     * @param   mixed  $connection  The connection to remove in cache
     * @return  void
     */
    protected function _removeConnection($connection)
    {
        foreach (self::$_connectionCache as $hash => $res) {
            if ($res == $connection) {
                unset(self::$_connectionCache[$hash]);
                return;
            }
        }
    }


    /**
     * Returns the current cursor
     *
     * @return  resource  Current cursor
     */
    public function query_id()
    {
        return $this->Query_ID;
    }


    /**
     * Connects to the database.
     *
     * @return  void
     */
    abstract protected function _connect();


    /**
     * Executes the query.
     *
     * @param   string    $sQuery  The query to execute
     * @return  resource|int|object|bool  Depends on used database driver, false on error
     */
    public function query($sQuery)
    {
        // No empty queries, please, since PHP4 chokes on them
        if ($sQuery == '') {
            // The empty query string is passed on from the constructor, when calling
            // the class without a query, e.g. in situations '$db = new DB_Sql_Subclass;'
            return false;
        }

        $aArgs = func_get_args();
        if (count($aArgs) > 1) {
            array_shift($aArgs);
            $sQuery = $this->_prepareQueryf($sQuery, $aArgs);
        }

        if (!$this->connect()) {
            return false;
        }

        // new query, discard previous result
        if ($this->Query_ID) {
            $this->free();
        }

        $this->_debug('Debug: query = ' . $sQuery);

        if ($this->_bProfile) {
            $fStart = microtime(true);
        }

        $this->_query($sQuery);

        if ($this->_bProfile) {
            $fEnd = microtime(true);
            $this->_addProfileData($fStart, $fEnd, $sQuery);
        }

        // Will return nada if it fails. That's fine.
        return $this->Query_ID;
    }

    /**
     * Executes the query.
     *
     * @param   string    $sQuery  The query to execute
     * @return  resource|int  The query result id (ressource id) or 0 on error
     */
    abstract protected function _query($sQuery);


    /**
     * Prepares a query with parameter for execution.
     *
     * @param   string  $sQuery
     * @param   array  $aArgs  Arguments array containing the query with formatting
     *                         signs and the entries.
     *                         Examples:
     *                         $obj->_prepareQueryf('SELECT * FROM `%s` WHERE id = %i', 'tablename', 123);
     *                         $obj->_prepareQueryf('SELECT * FROM `%s` WHERE id = %i AND user = %i', 'tablename', 123, 3);
     * @return  string
     */
    protected function _prepareQueryf($sQuery, array $aArgs)
    {
        if (count($aArgs) > 0) {
            $aArgs = array_map(array($this, 'escape'), $aArgs);
            array_unshift($aArgs, $sQuery);
            $sQuery = call_user_func_array('sprintf', $aArgs);
        }
        return $sQuery;
    }


    /**
     * Moves the result to the next record, if exists and returns the status of the movement
     *
     * @return  int  Flag about move status 1 on success or 0
     */
    abstract public function next_record();


    /**
     * Moves the cursor (position inside current result sets).
     *
     * @param   int  $pos  The positon to move to inside the current result set
     * @return  void
     */
    abstract public function seek($pos);


    /**
     * Locks the desired table.
     *
     * @param   string  $table  The table to lock
     * @param   string  $mode   The lock mode. Only mode 'write' is supported!
     * @return  int     Status of lock success (1 or 0)
     */
    abstract public function lock($table, $mode = 'write');


    /**
     * Frees a previous locked result.
     *
     * @return  int  Status of lock success (1 or 0)
     */
    abstract public function unlock();


    /**
     * Parses te table structure and generates a metadata from it.
     *
     * @param   string  $table  The table to get metadata or empty string to retrieve
     *                          metadata of all tables
     * @param   bool    $full   Flag to load full metada
     * @return  array   Depends on used database and on parameter $full
     */
    public function metadata($table = '', $full = false)
    {
        $key = (string) $this->Database . '_' . $table . '_' . (($full) ? '1' : '0');

        if (!isset(self::$_metaCache[$key])) {
            // get meta data
            self::$_metaCache[$key] = $this->_metadata($table, $full);
        }

        return self::$_metaCache[$key];
    }

    /**
     * Parses te table structure and generates a metadata from it.
     *
     * @param   string  $table  The table to get metadata or empty string to retrieve
     *                          metadata of all tables
     * @param   bool    $full   Flag to load full metada
     * @return  array   Depends on used database and on parameter $full
     */
    abstract protected function _metadata($table = '', $full = false);


    /**
     * Returns number of affected rows from last executed query (update, delete)
     *
     * @return  int  Number of affected rows
     */
    abstract public function affected_rows();


    /**
     * Returns the number of rows from last executed select query.
     *
     * @return  int  The number of rows from last select query result
     */
    abstract public function num_rows();


    /**
     * Returns the number of fields (colums) from current record set
     *
     * @return  int  Number of fields
     */
    abstract public function num_fields();


    /**
     * Returns the number of rows (record set).
     * Shortcut for function num_rows().
     *
     * @return  int  Number of rows
     */
    public function nf()
    {
        return $this->num_rows();
    }


    /**
     * Displays the number of rows (record set) using print.
     *
     * @return  void
     */
    public function np()
    {
        print $this->num_rows();
    }


    /**
     * Returns the desired field value from current record set.
     *
     * @param   mixed  The field name or index position
     * @param   mixed  The default value to return
     * @return  mixed  The value of field
     */
    public function f($Name, $default = null)
    {
        return (isset($this->Record[$Name])) ? $this->Record[$Name] : $default;
    }


    /**
     * Displays the desired field value from current record set using print.
     *
     * @param   mixed  The field name or index position
     * @return  void
     */
    public function p($Name)
    {
        if (isset($this->Record[$Name])) {
            print $this->Record[$Name];
        }
    }

    /**
     * Returns current recordset as a assoziative array
     *
     * @return  array
     */
    public function toArray()
    {
        $aResult = array();
        if (is_array($this->Record)) {
            foreach ($this->Record as $key => $value) {
                if (!is_numeric($key)) {
                    $aResult[$key] = $value;
                }
            }
        }
        return $aResult;
    }

    /**
     * Returns current recordset as object
     *
     * @return  stdClass
     */
    public function toObject()
    {
        return (object) $this->toArray();
    }

    /**
     * Returns the next id (sequence number).
     *
     * @param   string  $seq_name  The sequence name to get the next id from
     * @return  int  The next id or 0 on error
     */
    abstract public function nextid($seq_name);


    /**
     * Closes the connection and frees the query id.
     *
     * @return  void
     */
    abstract public function disconnect();


    /**
     * Error handling
     *
     * Error handler function, delegates passed message to the function haltmsg() if propery
     * $this->Halt_On_Error is not set to self::HALT_REPORT.
     *
     * Terminates further script execution if $this->Halt_On_Error is set to self::HALT_YES
     *
     * @param   string  $sMsg  The message to use for error handling
     * @return  void
     */
    public function halt($sMsg)
    {
        if ($this->Halt_On_Error == self::HALT_REPORT) {
            $this->haltmsg($this->_sHaltMsgPrefix . $sMsg);
        }

        if ($this->Halt_On_Error == self::HALT_YES) {
            die('Session halted.');
        }
    }


    /**
     * Logs passed message, basically the last db error to the error log.
     *
     * @param   string  $sMsg
     * @return  void
     */
    public function haltmsg($sMsg)
    {
        if (!$this->Error) {
            $this->Error = $this->_getErrorMessage();
        }
        if (!$this->Errno) {
            $this->Errno = $this->_getErrorNumber();
        }

        $sMsg  = sprintf("%s error: %s (%s) - info: %s\n", $sName, $this->Errno, $this->Error, $sMsg);
        error_log($sMsg);
    }


    /**
     * Returns names of existing tables.
     *
     * @return  array  Indexed array containing assoziative table data like:
     *                 - $info[$i]['table_name']
     *                 - $info[$i]['tablespace_name']
     */
    abstract public function table_names();


    /**
     * Escape string for using in SQL-Statement.
     *
     * @param   string  $sString  The string to escape
     * @return  string  Escaped string
     */
    abstract public function escape($sString);


    /**
     * Returns error message of last occured error
     *
     * @return  string
     */
    public function getErrorMessage()
    {
        return $this->Error;
    }


    /**
     * Returns error message of last occured error by using databases interface
     *
     * @return  string
     */
    abstract protected function _getErrorMessage();


    /**
     * Returns error code of last occured error by using databases interface
     *
     * @return  int
     */
    public function getErrorNumber()
    {
        return $this->Errno;
    }


    /**
     * Returns error code of last occured error by using databases interface
     *
     * @return  int
     */
    abstract protected function _getErrorNumber();


    /**
     * Adds a entry to the profile data
     * @param   float   $fStartTime
     * @param   float   $fEndTime
     * @param   string  $sQuery
     * @return  void
     */
    protected static function _addProfileData($fStartTime, $fEndTime, $sQuery)
    {
        self::$_aProfileData[] = array(
            'time'  => $fEndTime - $fStartTime,
            'query' => $sQuery
        );
    }


    /**
     * Returns collected profile data
     * @return  array  Profile data array like:
     *                 - $arr[$i]['time']  (float)
     *                 - $arr[$i]['query']  (string)
     */
    public static function getProfileData()
    {
        return self::$_aProfileData;
    }


    /**
     * Displays the passed message, if debug is enabled (see $this->Debug)
     *
     * @param   string  $sMsg  The message to display
     * @return  void
     */
    protected function _debug($sMsg)
    {
        if ($this->Debug) {
            printf("<pre>" . $sMsg . "</pre>\n");
        }
    }
}
