<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Oracle database driver
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    Contenido database
 * @version    1.3.1
 * @author     Luis Francisco Gonzalez Hernandez
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 *
 * {@internal
 *   created  2002-07-21
 *   modified 2008-07-04, bilal arslan, added security fix
 *   modified 2009-10-29, Murat Purc, removed deprecated function (PHP 5.3 ready), some comments and formatting
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
     * Oracle home directory, used to set environment variable ORACLE_HOME
     * @var  string
     */
    public $Home = '/u01/app/oracle/product/8.0.4';

    /**
     * Flag to use remote connection (with username). Accepted values ate 1 or 0
     * @var  int
     */
    public $Remote = 1;

    /**
     * Due to a strange error with Oracle 8.0.5, Apache and PHP3.0.6
     * you don't need to set the ENV - on my system Apache
     * will change to a zombie, if I don't set this to FALSE!
     * If unsure try it out, if it works.
     * @var  bool
     */
    public $OraPutEnv   = true;

    /**
     * Flag to not auitomatically fetch next record
     * FIXME: Is not used!
     * @var bool
     */
    public $ora_no_next_fetch = false;


    /**
     * Constructor.
     *
     * @param  array  $options  Optional assoziative options
     */
    public function __construct(array $options = array())
    {
        $options = array_merge($options, array(
            'type' => 'oracle',
        ));
        parent::__construct($options);
    }


    /**
     * @see DB_Sql_Abstract::_connect()
     */
    protected function _connect()
    {
        extract($this->_aDbCfg['connection']);

        ## see above why we do this
        if ($this->OraPutEnv) {
            PutEnv("ORACLE_SID=$database");
            PutEnv("ORACLE_HOME=$home");
        }

        $this->_debug("Connect()ing to $database...");
        if ($this->Remote) {
            $dbh = ora_plogon("$user/$password@$database","");
            /************** (comment by SSilk)
            this dosn't work on my system:
            $dbh = ora_plogon("$user@$tdatabase.world","$password");
            ***************/
        } else {
            $dbh = ora_plogon("$user","$password");
            /* (comment by SSilk: don't know how this could work, but I leave this untouched!) */
        }
        $this->_debug("connect() Link_ID: $dbh");

        if (!$dbh) {
            $this->halt('ORACLE _connect() Failed');
            return null;
        }

        ora_commiton($dbh);

        return $dbh;
    }


    /**
     * @see DB_Sql_Abstract::_query()
     *
     * In order to increase the # of cursors per system/user go edit the
     * init.ora file and increase the max_open_cursors parameter. Yours is on
     * the default value, 100 per user.
     * We tried to change the behaviour of query() in a way, that it tries
     * to safe cursors, but on the other side be carefull with this, that you
     * don't use an old result.
     *
     * You can also make extensive use of ->disconnect()!
     * The unused QueryIDs will be recycled sometimes.
     *
     * @param   string    $sQuery  The query to execute
     */
    protected function _query($sQuery)
    {
        if (!$this->Query_ID) {
            $this->Query_ID = ora_open($this->Link_ID);
        }

        if (!@ora_parse($this->Query_ID, $sQuery)) {
            $this->Errno = ora_errorcode($this->Query_ID);
            $this->Error = ora_error($this->Query_ID);
            $this->halt("ora_parse() failed:\n$sQuery\nSnap & paste this to sqlplus!");
        } elseif (!@ora_exec($this->Query_ID)) {
            $this->Errno = ora_errorcode($this->Query_ID);
            $this->Error = ora_error($this->Query_ID);
            $this->halt("$sQuery\nSnap & paste this to sqlplus!");
        }

        $this->Row=0;
    }


    /**
     * @see DB_Sql_Abstract::next_record()
     */
    public function next_record()
    {
        if (!$this->no_next_fetch && 0 == ora_fetch($this->Query_ID)) {
            $this->_debug("next_record(): ID: $this->Query_ID, Rows: " . $this->num_rows());
            $this->Row += 1;

            $errno = ora_errorcode($this->Query_ID);
            if(1403 == $errno) { # 1043 means no more records found
                $this->Errno = 0;
                $this->Error = '';
                $this->disconnect();
                $stat = false;
            } else {
                $this->Error = ora_error($this->Query_ID);
                $this->Errno = $errno;
                $this->_debug("$this->Errno Error: $this->Error");
                $stat = false;
            }
        } else {
            $this->no_next_fetch = false;
            for ($ix=0; $ix<ora_numcols($this->Query_ID); $ix++) {
                $col = strtolower(ora_columnname($this->Query_ID,$ix));
                $value = ora_getcolumn($this->Query_ID,$ix);
                $this->Record["$col"] = $value;
#               echo"<b>[$col]</b>: $value <br>\n";
            }
            $stat = true;
        }

        return $stat;
    }


    /**
     * @see DB_Sql_Abstract::seek()
     *
     * Works only for $pos - 1 and $pos
     * Perhaps I make a own implementation, but my
     * opinion is, that this should be done by PHP3
     *
     * @param   int  $pos  The positon to move to inside the current result set
     * @return  void
     */
    public function seek($pos)
    {
        if ($this->Row - 1 == $pos) {
            $this->no_next_fetch = true;
        } elseif ($this->Row == $pos) {
            ## do nothing
        } else {
            $this->halt("Invalid seek(): Position is cannot be handled by API.<BR>".
                "Difference too big. Wanted: $pos Current pos: $this->Row");
        }
        $this->_debug("Debug: seek = $pos");
        $this->Row = $pos;
    }


    /**
     * @see DB_Sql_Abstract::lock()
     */
    public function lock($table, $mode = 'write')
    {
        if ($mode == 'write') {
            $result = ora_do($this->Link_ID, "lock table $table in row exclusive mode");
        } else {
            $result = 1;
        }
        return $result;
    }


    /**
     * @see DB_Sql_Abstract::unlock()
     */
    public function unlock()
    {
        return ora_do($this->Link_ID, 'commit');
    }


    /**
     * @see DB_Sql_Abstract::_metaData()
     *
     * Parses te table structure and generates a metadata from it.
     *
     * Due to compatibility problems with Table we changed the behavior
     * of metadata();
     *
     * @param   string  $table  The table to get metadata
     * @param   bool    $full   Flag to load full metada
     * @return  array   Depending on $full, metadata returns the following values:
     *  - full is false (default):
     *    $result[]:
     *      [0]["table"]  table name
     *      [0]["name"]   field name
     *      [0]["type"]   field type
     *      [0]["len"]    field length
     *      [0]["flags"]  field flags ("NOT NULL", "INDEX")
     *      [0]["format"] precision and scale of number (eg. "10,2") or empty
     *      [0]["index"]  name of index (if has one)
     *      [0]["chars"]  number of chars (if any char-type)
     *
     * - full is true
     *   $result[]:
     *     ["num_fields"] number of metadata records
     *     [0]["table"]  table name
     *     [0]["name"]   field name
     *     [0]["type"]   field type
     *     [0]["len"]    field length
     *     [0]["flags"]  field flags ("NOT NULL", "INDEX")
     *     [0]["format"] precision and scale of number (eg. "10,2") or empty
     *     [0]["index"]  name of index (if has one)
     *     [0]["chars"]  number of chars (if any char-type)
     *     ["meta"][field name]  index of field named "field name"
     *     The last one is used, if you have a field name, but no index.
     *     Test:  if (isset($result['meta']['myfield'])) {} ...
     */
    protected function _metaData($table, $full = false)
    {
        $count = 0;
        $id    = 0;
        $res   = array();

        $this->connect();

        ## This is a RIGHT OUTER JOIN: "(+)", if you want to see, what
        ## this query results try the following:
        ## $table = new Table; $db = new my_DB_Sql; # you have to make
        ##                                          # your own class
        ## $table->show_results($db->query(see query vvvvvv))
        ##
        $this->query("SELECT T.table_name,T.column_name,T.data_type,".
            "T.data_length,T.data_precision,T.data_scale,T.nullable,".
            "T.char_col_decl_length,I.index_name".
            " FROM ALL_TAB_COLUMNS T,ALL_IND_COLUMNS I".
            " WHERE T.column_name=I.column_name (+)".
            " AND T.table_name=I.table_name (+)".
            " AND T.table_name=UPPER('$table') ORDER BY T.column_id"
        );

        $i = 0;
        while ($this->next_record()) {
            $res[$i]['table'] =  $this->Record[table_name];
            $res[$i]['name']  =  strtolower($this->Record[column_name]);
            $res[$i]['type']  =  $this->Record[data_type];
            $res[$i]['len']   =  $this->Record[data_length];
            if ($this->Record[index_name]) {
                $res[$i]['flags'] = 'INDEX ';
            }
            $res[$i]['flags'] .= ($this->Record[nullable] == 'N') ? '' : 'NOT NULL';
            $res[$i]['format'] = (int) $this->Record[data_precision] . ',' . (int) $this->Record[data_scale];
            if ('0,0' == $res[$i]['format']) {
                $res[$i]['format'] = '';
            }
            $res[$i]['index'] = $this->Record[index_name];
            $res[$i]['chars'] = $this->Record[char_col_decl_length];
            if ($full) {
                $j = $res[$i]['name'];
                $res['meta'][$j] = $i;
                $res['meta'][strtoupper($j)] = $i;
            }
            if ($full) {
                $res['meta'][$res[$i]['name']] = $i;
            }
            $i++;
        }
        if ($full) {
            $res['num_fields'] = $i;
        }
#        $this->disconnect();
        return $res;
    }


    /**
     * @see DB_Sql_Abstract::affected_rows()
     * @FIXME: THIS FUNCTION IS UNSTESTED!
     */
    public function affected_rows()
    {
        $this->_debug("Debug: affected_rows=". ora_numrows($this->Query_ID));
        return ora_numrows($this->Query_ID);
    }


    /**
     * @see DB_Sql_Abstract::num_rows()
     *
     * Known bugs: It will not work for SELECT DISTINCT and any other constructs 
     * which are depending on the resulting rows. So you *really need* to check 
     * every query you make, if it will work with it.
     *
     * Also, for a qualified replacement you need to parse the selection, cause 
     * this will fail: "SELECT id, from FROM ..."). "FROM" is - as far as I know 
     * a keyword in Oracle, so it can only be used in this way. But you have been 
     * warned.
     */
    public function num_rows()
    {
        $curs = ora_open($this->Link_ID);

        ## this is the important part and it is also the HACK!
        if (preg_match('/^\s*SELECT\s/i', $this->lastQuery)) {
            $from_pos = strpos(strtoupper($this->lastQuery), 'FROM');
            $q = 'SELECT count(*) ' . substr($this->lastQuery, $from_pos);
            ora_parse($curs, $q);
            ora_exec($curs);
            ora_fetch($curs);
            $this->_debug("Debug: num_rows=" . ora_getcolumn($curs,0));
            return(ora_getcolumn($curs, 0));
        } else {
            $this->halt("Last Query was not a SELECT: $this->lastQuery");
        }
    }


    /**
     * @see DB_Sql_Abstract::num_fields()
     */
    public function num_fields()
    {
        $this->_debug("Debug: num_fields=". ora_numcols($this->Query_ID));
        return ora_numcols($this->Query_ID);
    }


    /**
     * @see DB_Sql_Abstract::nextid()
     */
    public function nextid($seq_name)
    {
        $this->connect();

        /* Independent Query_ID */
        $Query_ID = ora_open($this->Link_ID);

        if (!@ora_parse($Query_ID, "SELECT $seq_name.NEXTVAL FROM DUAL")) {
            // There is no such sequence yet, then create it
            if (!@ora_parse($Query_ID, "CREATE SEQUENCE $seq_name") || !@ora_exec($Query_ID)) {
                $this->halt("<BR> nextid() function - unable to create sequence");
                return 0;
            }
            @ora_parse($Query_ID, "SELECT $seq_name.NEXTVAL FROM DUAL");
        }
        if (!@ora_exec($Query_ID)) {
            $this->halt("<BR>ora_exec() failed:<BR>nextID function");
        }
        if (@ora_fetch($Query_ID)) {
            $next_id = ora_getcolumn($Query_ID, 0);
        } else {
            $next_id = 0;
        }
        if ($Query_ID > 0) {
            ora_close($Query_ID);
        }

        return $next_id;
    }


    /**
     * @see DB_Sql_Abstract::disconnect()
     */
    public function disconnect()
    {
        $this->_debug("Debug: Disconnecting $this->Link_ID...");
        if ($this->Query_ID > 0) {
            ora_close($this->Query_ID);
        }

        if (is_resource($this->Link_ID)) {
            ora_logoff($this->Link_ID);
            $this->_removeConnection($this->Link_ID);
        }

        $this->Link_ID  = 0;
        $this->Query_ID = 0;
    }


    /**
     * @see DB_Sql_Abstract::escape()
     */
    public function escape($sString)
    {
        //@ToDo: implement specific database behaviour
        return addslashes($sString);
    }


    /**
     * @see DB_Sql_Abstract::_tableNames()
     */
    protected function _tableNames()
    {
        $return = array();
        $this->query('SELECT table_name, tablespace_name FROM user_tables');
        $i = 0;
        while ($this->next_record()) {
            $return[$i]['table_name']      = $this->Record['table_name'];
            $return[$i]['tablespace_name'] = $this->Record['tablespace_name'];
            $return[$i]['database']        = $this->Record['tablespace_name'];
            $i++;
        }
        return $return;
    }


    /**
     * @see DB_Sql_Abstract::_serverInfo()
     */
    protected function _serverInfo()
    {
        // @todo implement me
        return null;
    }


    /**
     * @see DB_Sql_Abstract::_getErrorMessage()
     */
    protected function _getErrorMessage()
    {
        // @todo implement me
    }


    /**
     * @see DB_Sql_Abstract::_getErrorNumber()
     */
    protected function _getErrorNumber()
    {
        // @todo implement me
    }

}
