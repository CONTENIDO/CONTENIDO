<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Oracle/OCI8 database driver
 *
 * Requirements:
 * @con_php_req 5
 * @con_notice
 * based on db_oracle.inc by Luis Francisco Gonzalez Hernandez
 * contains metadata() from db_oracle.inc 1.10
 *
 * @package    Contenido database
 * @version    1.3
 * @author     Stefan Sels phplib@sels.com
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
 *   modified 2011-03-03, Murat Purc, Some redesign and improvements (partial adaption to PHP 5 and extending DB_Sql_Abstract).
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
     * Flag to show query on error.
     * @var  int
     */
    public $sqoe =  1;


    /**
     * Error array
     * @var  array
     */
    public $_Error;


    /**
     * Constructor.
     *
     * @param  array  $options  Optional assoziative options
     */
    public function __construct(array $options = array())
    {
        $options = array_merge($options, array(
            'type' => 'oci8'
        ));
        parent::__construct($options);
    }


    /**
     * Establishes a connection to the database server
     *
     * @return  resource|bool  Returns a connection id or false on error.
     */
    protected function _connect()
    {
        extract($this->_aDbCfg['connection']);

        $dbh = OCIplogon($user, $password, $database);

        if (!$dbh) {
            $this->halt('OCI8 _connect() Failed');
            return false;
        }

        return $dbh;
    }


    /**
     * Performs a query.
     *
     * @param   string  $sQuery
     */
    protected function _query($sQuery)
    {
        $this->Query_ID = OCIParse($this->Link_ID, $sQuery);
        if (!$this->Query_ID) {
            $this->_Error = OCIError($this->Query_ID);
        } else {
            OCIExecute($this->Query_ID);
            $this->_Error = OCIError($this->Query_ID);
        }

        $this->Row = 0;

        if ($this->_Error['code'] != 1403 && $this->_Error['code'] != 0 && $this->sqoe) {
            $this->Errno = $this->_Error['code'];
            $this->Error = $this->_Error['message'];
            $this->halt($sQuery);
        } else {
            $this->Errno = 0;
            $this->Error = '';
        }
    }


    /**
     * Fetches the next recordset from result set
     *
     * @param  bool
     */
    public function next_record()
    {
        if (0 == OCIFetchInto($this->Query_ID, $result, OCI_ASSOC+OCI_RETURN_NULLS)) {
            $this->_debug("ID: $this->Link_ID,Rows: " . $this->num_rows());
            $this->Row +=1;

            $errno = OCIError($this->Query_ID);
            if(1403 == $errno) { # 1043 means no more records found
                $this->_Error = '';
                $this->disconnect();
                $stat = false;
            } else {
                $this->_Error = OCIError($this->Query_ID);
                $this->_debug('Error: ' . $this->_Error['message']);
                $stat = false;
            }
        } else {
            for ($ix=1; $ix<=OCINumcols($this->Query_ID); $ix++) {
                $col       = strtoupper(OCIColumnname($this->Query_ID, $ix));
                $colreturn = strtolower($col);
                $this->Record["$colreturn"] = $result["$col"];
                $this->_debug("<b>[$col]</b>:".$result["$col"]);
            }
            $stat = true;
        }

        return $stat;
    }


    public function seek($pos)
    {
        $this->Row = $pos;
    }


    protected function _metadata($table, $full = false)
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
         *   [0]["flags"]  field flags ("NOT NULL", "INDEX")
         *   [0]["format"] precision and scale of number (eg. "10,2") or empty
         *   [0]["index"]  name of index (if has one)
         *   [0]["chars"]  number of chars (if any char-type)
         *
         * - full is true
         * $result[]:
         *   ["num_fields"] number of metadata records
         *   [0]["table"]  table name
         *   [0]["name"]   field name
         *   [0]["type"]   field type
         *   [0]["len"]    field length
         *   [0]["flags"]  field flags ("NOT NULL", "INDEX")
         *   [0]["format"] precision and scale of number (eg. "10,2") or empty
         *   [0]["index"]  name of index (if has one)
         *   [0]["chars"]  number of chars (if any char-type)
         *   ["meta"][field name]  index of field named "field name"
         *   The last one is used, if you have a field name, but no index.
         *   Test:  if (isset($result['meta']['myfield'])) {} ...
         */

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
            $res[$i]['index'] =  $this->Record[index_name];
            $res[$i]['chars'] =  $this->Record[char_col_decl_length];
            if ($full) {
                $j=$res[$i]['name'];
                $res['meta'][$j] = $i;
                $res['meta'][strtoupper($j)] = $i;
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


    public function affected_rows()
    {
        return $this->num_rows();
    }


    public function num_rows()
    {
        return OCIrowcount($this->Query_ID);
    }


    public function num_fields()
    {
        return OCINumcols($this->Query_ID);
    }


    public function f($Name, $default = null)
    {
        $record = parent::f($Name, $default);
        if (is_object($record)) {
            $record->load();
        }
        return $record;
    }


    public function nextid($seqname)
    {
        $this->connect();

        $Query_ID = @ociparse($this->Link_ID, "SELECT $seqname.NEXTVAL FROM DUAL");

        if (!@ociexecute($Query_ID)) {
            $this->_Error = @OCIError($Query_ID);
            if ($this->_Error['code'] == 2289) {
                $Query_ID = ociparse($this->Link_ID,"CREATE SEQUENCE $seqname");
                if (!ociexecute($Query_ID)) {
                    $this->_Error = OCIError($Query_ID);
                    $this->halt("<BR> nextid() function - unable to create sequence<br>".$this->_Error['message']);
                } else {
                    $Query_ID = ociparse($this->Link_ID, "SELECT $seqname.NEXTVAL FROM DUAL");
                    ociexecute($Query_ID);
                }
            }
        }

        if (ocifetch($Query_ID)) {
            $next_id = ociresult($Query_ID,"NEXTVAL");
        } else {
            $next_id = 0;
        }
        ocifreestatement($Query_ID);
        return $next_id;
    }


    /**
     * Closes the connection.
     *
     * @return  void
     */
    public function disconnect()
    {
        $this->_debug("Disconnecting...");
        if (is_resource($this->Link_ID)) {
            oci_close($this->Link_ID);
            $this->_removeConnection($this->Link_ID);
        }
        $this->Link_ID  = 0;
        $this->Query_ID = 0;
    }


    public function lock($table, $mode = 'write')
    {
        $this->connect();
        if ($mode == 'write') {
            $Parse = OCIParse($this->Link_ID,"lock table $table in row exclusive mode");
            OCIExecute($Parse);
        } else {
            $result = 1;
        }
        return $result;
    }


    public function unlock()
    {
        return $this->query('commit');
    }


    public function table_names()
    {
        $this->connect();
        $this->query('SELECT table_name, tablespace_name FROM user_tables');
        $i = 0;
        while ($this->next_record()) {
            $info[$i]['table_name']     = $this->Record['table_name'];
            $info[$i]['tablespace_name']= $this->Record['tablespace_name'];
            $i++;
        }
        return $info;
    }


    /* public: Escape string for using in SQL-Statement */
    public function escape($sString)
    {
        //ToDo implement specific database behaviour
        return addslashes($sString);
    }


    public function add_specialcharacters($sQuery)
    {
        return str_replace("'", "''", $sQuery);
    }


    public function split_specialcharacters($sQuery)
    {
        return str_replace("''", "'", $sQuery);
    }


    /**
     * Returns error message of last occured error by using databases interface
     */
    protected function _getErrorMessage()
    {
        // @todo implement me
    }


    /**
     * Returns error code of last occured error by using databases interface
     */
    protected function _getErrorNumber()
    {
        // @todo implement me
    }

}
