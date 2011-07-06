<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Base (abstract) database class. Each database driver file should extend this!
 * @todo  Better error handling, use exceptions!
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    Contenido Database
 * @version    0.1.4
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since Contenido release 4.8.15
 *
 * {@internal
 *   created  2011-03-03
 *   modified 2011-03-13, Murat Purc, Added FETCH_* constants, extended toArray(),
 *                        moved close(), table_names() and server_info() from child
 *                        implementations.
 *   modified 2011-07-06, Murat Purc, Fixed fetch modes in toArray() and haltmsg(),
 *                        added new methods to build insert/update queries, added
 *                        method _getInstanceClone(), documented code
 *   $Id$
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


abstract class DB_Sql_Abstract
{

    const HALT_YES    = 'yes';
    const HALT_NO     = 'no';
    const HALT_REPORT = 'report';

    const FETCH_NUMERIC = 'numeric';
    const FETCH_ASSOC   = 'assoc';
    const FETCH_BOTH    = 'both';

    /**
     * A clone of self instance, meant for internal use to work with additonal
     * query results without overwriting an existing query result.
     *
     * @DB_Sql_Abstract
     */
    protected $_instanceClone = null;

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
     * - "yes"    (halt with message)
     * - "no"     (ignore errors quietly)
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
     * Uses default connection settings, passed $aOptions['db_connection'] settings
     * will overwrite connection settings for current instance.
     *
     * @param  array  $aOptions  Assoziative options as follows:
     *                          - $aOptions['sequenceTable']  (string)  Optional, the sequence table name
     *                          - $aOptions['nolock']  (bool)  Optional, not lock table
     *                          - $aOptions['haltBehavior']  (string)  Optional, halt behavior on occured errors
     *                          - $aOptions['haltMsgPrefix']  (string)  Optional, Text to prepend to the halt message
     *                          - $aOptions['type']  (string)  Database driver name
     *                          - $aOptions['enableProfiling']  (bool)  Optional, flag to enable profiling
     *                          - $aOptions['connection']  (array)  Optional, assoziative connection settings
     * @return  void
     */
    protected function __construct(array $aOptions)
    {
        // use default connection configuration, but overwrite it by passed options
        $this->_aDbCfg = array_merge(self::$_aDefaultDbCfg, $aOptions);

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
     * @param  array  $aDefaultDbCfg
     */
    public static function setDefaultConfiguration(array $aDefaultDbCfg)
    {
        self::$_aDefaultDbCfg = $aDefaultDbCfg;
    }


    /**
     * Establishes a connection to the database server.
     *
     * @return  object|resource|int|null  Connection handler. Return value depends on 
     *                                    used driver and is null in case of an error.
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
     * @param   mixed  $mData  Connection data array or variable
     * @return  mixed  Either  The connection (object, resource, integer) or null
     */
    protected function _getConnection($mData)
    {
        $hash = md5($this->type . '-' . (is_array($mData) ? implode('-', $mData) : (string) $mData));
        return (isset(self::$_connectionCache[$hash])) ? self::$_connectionCache[$hash] : null;
    }


    /**
     * Stores connection in connection cache
     *
     * @param   mixed  $mData        Connection data array
     * @param   mixed  $mConnection  The connection to store in cache
     * @return  void
     */
    protected function _setConnection($mData, $mConnection)
    {
        $hash = md5($this->type . '-' . (is_array($mData) ? implode('-', $mData) : (string) $mData));
        self::$_connectionCache[$hash] = $mConnection;
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
     * @return  object|resource|int|null  Connection handler. Return value depends on 
     *                                    used driver and is null in case of an error.
     */
    abstract protected function _connect();


    /**
     * Executes the query. Accepts multiple parameter, where the first parameter
     * should be the query and any additional parameter should be the values to
     * replace in format definitions.
     *
     * Examples:
     * <pre>
     * // call with one parameter
     * $obj->query('SELECT * FROM `tablename` WHERE id = 123');
     * // call with multple parameter
     * $obj->query('SELECT * FROM `%s` WHERE id = %d', 'tablename', 123);
     * $obj->query('SELECT * FROM `%s` WHERE id = %d AND user = %d', 'tablename', 123, 3);
     * </pre>
     *
     * @param   string    $sQuery  The query to execute.
     * @param   mixed     Accepts additional unlimited parameter, where the parameter 
     *                    will be replaced against formatting sign in query.
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
     * @return  void
     */
    abstract protected function _query($sQuery);


    /**
     * Prepares a query with parameter for execution.
     *
     * @param   string  $sQuery
     * @param   array   $aArgs  Arguments array containing the query with formatting
     *                          signs and the entries.
     *                          Examples:
     *                          <pre>
     *                          $obj->_prepareQueryf('SELECT * FROM `%s` WHERE id = %d', 'tablename', 123);
     *                          $obj->_prepareQueryf('SELECT * FROM `%s` WHERE id = %d AND user = %d', 'tablename', 123, 3);
     *                          </pre>
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
     * Builds and executes a insert query. String values in passed aFields
     * parameter will be escaped automatically.
     *
     * Example:
     * <pre>
     * $oDB = new DB_Contenido();
     * $aFields = array(
     *     'idcode' => $oDB->nextid($cfg['tab']['code']),
     *     'idcatart' => $idcatart,
     *     'idlang' => $lang,
     *     'idclient' => $client,
     *     'code' => "<html>... code n' fun ...</html>",
     * );
     * $bResult = $oDB->insert($cfg['tab']['code'], $aFields);
     * </pre>
     *
     * @param   string   $sTable   The table name
     * @param   array    $aFields  Assoziative array of fields to insert
     * @return  bool
     */
    public function insert($sTable, array $aFields)
    {
        $sQuery = $this->_insert($sTable, $aFields);
        return $this->query($sQuery);
    }


    /**
     * Builds and returns a insert query. String values in passed aFields
     * parameter will be escaped automatically.
     *
     * Example:
     * <pre>
     * $oDB = new DB_Contenido();
     * $aFields = array(
     *     'idcode' => $oDB->nextid($cfg['tab']['code']),
     *     'idcatart' => $idcatart,
     *     'idlang' => $lang,
     *     'idclient' => $client,
     *     'code' => "<html>... code n' fun ...</html>",
     * );
     * $sQuery = $oDB->buildInsert($cfg['tab']['code'], $aFields);
     * $oDB->query($sQuery);
     * </pre>
     *
     * @param   string   $sTable   The table name
     * @param   array    $aFields  Assoziative array of fields to insert
     * @return  string
     */
    public function buildInsert($sTable, array $aFields)
    {
        return $this->_insert($sTable, $aFields);
    }


    /**
     * Builds and executes a insert query. String values in passed aFields
     * parameter will be escaped automatically.
     *
     * @param   string   $sTable   The table name
     * @param   array    $aFields  Assoziative array of fields to insert
     * @return  bool
     */
    abstract protected function _insert($sTable, array $aFields);


    /**
     * Builds and executes a update query. String values in passed aFields
     * and aWhere parameter will be escaped automatically.
     *
     * Example:
     * <pre>
     * $oDB = new DB_Contenido();
     * $aFields = array('code' => "<html>... some new code n' fun ...</html>");
     * $aWhere = array('idcode' => 123);
     * $bResult = $oDB->update($cfg['tab']['code'], $aFields, $aWhere);
     * </pre>
     *
     * @param   string   $sTable   The table name
     * @param   array    $aFields  Assoziative array of fields to update
     * @param   array    $aWhere   Assoziative array of field in where clause.
     *                             Multiple entries will be concatenated with AND
     * @return  bool
     */
    public function update($sTable, array $aFields, array $aWhere)
    {
        $sQuery = $this->_update($sTable, $aFields, $aWhere);
        return $this->query($sQuery);
    }


    /**
     * Builds and returns a update query. String values in passed aFields
     * and aWhere parameter will be escaped automatically.
     *
     * Example:
     * <pre>
     * $oDB = new DB_Contenido();
     * $aFields = array('code' => "<html>... some new code n' fun ...</html>");
     * $aWhere = array('idcode' => 123);
     * $sQuery = $oDB->buildUpdate($cfg['tab']['code'], $aFields, $aWhere);
     * $oDB->query($sQuery);
     * </pre>
     *
     * @param   string   $sTable   The table name
     * @param   array    $aFields  Assoziative array of fields to update
     * @param   array    $aWhere   Assoziative array of field in where clause.
     *                             Multiple entries will be concatenated with AND
     * @return  string
     */
    public function buildUpdate($sTable, array $aFields, array $aWhere)
    {
        return $this->_update($sTable, $aFields, $aWhere);
    }


    /**
     * Builds and executes a update query. String values in passed aFields
     * and aWhere parameter will be escaped automatically.
     *
     * @param   string   $sTable   The table name
     * @param   array    $aFields  Assoziative array of fields to update
     * @param   array    $aWhere   Assoziative array of field in where clause.
     *                             Multiple entries will be concatenated with AND
     * @return  bool
     */
    abstract protected function _update($sTable, array $aFields, array $aWhere);


    /**
     * Moves the result to the next record, if exists and returns the status of the movement
     *
     * @return  int  Flag about move status 1 on success or 0
     */
    abstract public function next_record();


    /**
     * Moves the cursor (position inside current result sets).
     *
     * @param   int  $iPos  The positon to move to inside the current result set
     * @return  void
     */
    abstract public function seek($iPos = 0);


    /**
     * Locks the desired table.
     *
     * @param   string  $sTable  The table to lock
     * @param   string  $sMode   The lock mode. Only mode 'write' is supported!
     * @return  int     Status of lock success (1 or 0)
     */
    abstract public function lock($sTable, $sMode = 'write');


    /**
     * Frees a previous locked result.
     *
     * @return  int  Status of lock success (1 or 0)
     */
    abstract public function unlock();


    /**
     * Parses te table structure and generates a metadata from it.
     *
     * @param   string  $sTable  The table to get metadata or empty string to retrieve
     *                           metadata of all tables
     * @param   bool    $bFull   Flag to load full metada
     * @return  array   Depends on used database and on parameter $full
     */
    public function metadata($sTable = '', $bFull = false)
    {
        $sKey = (string) $this->Database . '_' . $sTable . '_' . (($bFull) ? '1' : '0');

        if (!isset(self::$_metaCache[$sKey])) {
            // get meta data
            self::$_metaCache[$sKey] = $this->_metaData($sTable, $bFull);
        }

        return self::$_metaCache[$sKey];
    }

    /**
     * Parses te table structure and generates a metadata from it.
     *
     * @param   string  $sTable  The table to get metadata or empty string to retrieve
     *                          metadata of all tables
     * @param   bool    $full   Flag to load full metada
     * @return  array   Depends on used database and on parameter $full
     */
    abstract protected function _metaData($sTable = '', $full = false);


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
     * @param   mixed  $mName  The field name or index position
     * @param   mixed  $mDefault  The default value to return
     * @return  mixed  The value of field
     */
    public function f($mName, $mDefault = null)
    {
        return (isset($this->Record[$mName])) ? $this->Record[$mName] : $mDefault;
    }


    /**
     * Displays the desired field value from current record set using print.
     *
     * @param   mixed  $mName  The field name or index position
     * @return  void
     */
    public function p($mName)
    {
        if (isset($this->Record[$mName])) {
            print $this->Record[$mName];
        }
    }

    /**
     * Returns current recordset as a assoziative and/or indexed array.
     *
     * @param   string  $sFetchMode  One of DB_SQL_Abstract::FETCH_* constants
     * @return  array
     */
    public function toArray($sFetchMode = DB_SQL_Abstract::FETCH_ASSOC)
    {
        switch ($sFetchMode) {
            case self::FETCH_NUMERIC:
            case self::FETCH_ASSOC:
            case self::FETCH_BOTH:
                // donut
                break;
            default:
                $sFetchMode = self::FETCH_ASSOC;
                break;
        }

        $aResult = array();
        if (is_array($this->Record)) {
            foreach ($this->Record as $key => $value) {
                if ($sFetchMode == self::FETCH_ASSOC && !is_numeric($key)) {
                    $aResult[$key] = $value;
                } elseif ($sFetchMode == self::FETCH_NUMERIC && is_numeric($key)) {
                    $aResult[$key] = $value;
                } elseif ($sFetchMode == self::FETCH_BOTH) {
                    $aResult[$key] = $value;
                }
            }
        }
        return $aResult;
    }


    /**
     * Returns current recordset as a object
     *
     * @return  stdClass
     */
    public function toObject()
    {
        return (object) $this->toArray(self::FETCH_ASSOC);
    }


    /**
     * Returns the next id (sequence number).
     *
     * @param   string  $sSeqName  The sequence name to get the next id from
     * @return  int  The next id or 0 on error
     */
    abstract public function nextid($sSeqName);


    /**
     * Wrapper method for disconnect()
     * @see DB_Sql_Abstract::disconnect()
     */
    public function close()
    {
        $this->disconnect();
    }


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
     * Concatenates a detailed error message and invokey PHP's error_log() method.
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

        $sMsg  = sprintf("%s error: %s (%s) - info: %s\n", $this->type, $this->Errno, $this->Error, $sMsg);
        error_log($sMsg);
    }


    /**
     * Returns names of existing tables.
     *
     * @return  array|null  Indexed array containing assoziative table data as 
     *                      follows or null:
     *                      - $info[$i]['table_name']
     *                      - $info[$i]['tablespace_name']
     *                      - $info[$i]['database']
     */
    public function table_names()
    {
        if (!$this->connect()) {
            return null;
        }
        if (method_exists($this, '_tableNames')) {
            return $this->_tableNames();
        }
        return null;
    }

    /**
     * Implemetation for table_names() in child classes
     * @see DB_Sql_Abstract::table_names()
     */
    abstract protected function _tableNames();


    /**
     * Escape string for using in SQL-Statement.
     *
     * @param   string  $sString  The string to escape
     * @return  string  Escaped string
     */
    abstract public function escape($sString);


    /**
     * Returns information about DB server. The return value depends allways on
     * used DBMS.
     *
     * @return  array|null  Assoziative array as follows or null:
     *                      - $arr['description']  (string)  Optional, server description
     *                      - $arr['version']      (string)  Optional, server version
     */
    public function server_info()
    {
        if (!$this->connect()) {
            return null;
        }
        if (method_exists($this, '_serverInfo')) {
            return $this->_serverInfo();
        }
        return null;
    }

    /**
     * Implemetation for server_info() in child classes.
     * @see DB_Sql_Abstract::server_info()
     */
    abstract protected function _serverInfo();


    /**
     * Returns error message of last occured error.
     *
     * @return  string
     */
    public function getErrorMessage()
    {
        return $this->Error;
    }


    /**
     * Returns error message of last occured error by using databases interface.
     *
     * @return  string
     */
    abstract protected function _getErrorMessage();


    /**
     * Returns error code of last occured error by using databases interface.
     *
     * @return  int
     */
    public function getErrorNumber()
    {
        return $this->Errno;
    }


    /**
     * Returns error code of last occured error by using databases interface.
     *
     * @return  int
     */
    abstract protected function _getErrorNumber();


    /**
     * Adds a entry to the profile data.
     *
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
     * Returns collected profile data.
     *
     * @return  array  Profile data array like:
     *                 - $arr[$i]['time']   (float)   Elapsed time to execute the query
     *                 - $arr[$i]['query']  (string)  The query itself
     */
    public static function getProfileData()
    {
        return self::$_aProfileData;
    }


    /**
     * Returns a cloned version of self. Clones itself only at first call.
     * Further calls will return the cloned version.
     *
     * @return DB_Sql_Abstract
     */
    protected function _getInstanceClone()
    {
        if (null === $this->_instanceClone) {
            $this->_instanceClone = clone $this;
        }
        return $this->_instanceClone;
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
