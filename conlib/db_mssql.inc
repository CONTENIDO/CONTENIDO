<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Mssql database driver
 *
 * Requirements:
 * @con_php_req 5
 * @con_notice
 * This is using the MSSQL class
 *
 *
 * @package    Contenido database
 * @version    1.3
 * @author     Cameron Taggart
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 *
 * {@internal
 *   created  1998-01-01
 *   modified unknown, Guarneri carmelo
 *   modified unknown, Cameron Just
 *   modified 2008-07-03, bilal arslan, added security fix
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
     * Constructor.
     *
     * @param  array  $options  Optional assoziative options
     */
    public function __construct(array $options = array())
    {
        $options = array_merge($options, array(
            'type' => 'mssql'
        ));
        parent::__construct($options);
    }


    /**
     * Establishes a connection to the database server.
     *
     * @return  resource|bool  Returns a connection id on success, or false on error.f     */
    protected function _connect()
    {
        extract($this->_aDbCfg['connection']);

        $dbh = mssql_pconnect($host, $user, $password);

        if (!$dbh) {
            $this->halt('Link-ID == false, mssql_pconnect failed');
            return false;
        }
        mssql_select_db($database, $dbh);

        return $dbh;
    }


    public function free_result()
    {
        mssql_free_result($this->Query_ID);
        $this->Query_ID = 0;
    }


    /**
     * Performs a query.
     *
     * @param   string  $sQuery
     */
    protected function _query($sQuery)
    {
        $this->Query_ID = mssql_query($sQuery, $this->Link_ID);
        $this->Row      = 0;
        if (!$this->Query_ID) {
            $this->Errno = 1;
            $this->Error = 'General Error (The MSSQL interface cannot return detailed error messages).';
            $this->halt("Mssql error " . $this->Errno . ": " . $this->Error . "\n" . $sQuery);
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
        if ($this->Record = mssql_fetch_row($this->Query_ID)) {
            // add to Record[<key>]
            $count = mssql_num_fields($this->Query_ID);
            for ($i=0; $i<$count; $i++){
                $fieldinfo = mssql_fetch_field($this->Query_ID,$i);
                $this->Record[strtolower($fieldinfo->name)] = $this->Record[$i];
            }
            $this->Row += 1;
            $stat = true;
        } else {
            if ($this->Auto_Free) {
                $this->free_result();
            }
            $stat = false;
        }
        return $stat;
    }


    public function seek($pos)
    {
        mssql_data_seek($this->Query_ID,$pos);
        $this->Row = $pos;
    }


    /**
     * Closes the connection and frees the query id.
     *
     * @return  void
     */
    public function disconnect()
    {
        if (is_resource($this->Link_ID)) {
            mssql_close($this->Link_ID);
            $this->_removeConnection($this->Link_ID);
        }
        $this->Link_ID  = 0;
        $this->Query_ID = 0;
    }


    protected function _metadata($table, $full = false)
    {
        $count = 0;
        $id    = 0;
        $res   = array();

        $this->connect();
        $id = mssql_query("SELECT * FROM $table", $this->Link_ID);
        if (!$id) {
            $this->Errno = 1;
            $this->Error = 'General Error (The MSSQL interface cannot return detailed error messages).';
            $this->halt('Metadata query failed.');
        }
        $count = mssql_num_fields($id);

        for ($i=0; $i<$count; $i++) {
            $info = mssql_fetch_field($id, $i);
            $res[$i]['table'] = $table;
            $res[$i]['name']  = $info['name'];
            $res[$i]['len']   = $info['max_length'];
            $res[$i]['flags'] = $info['numeric'];
        }
        $this->free_result();
        return $res;
    }


    public function affected_rows()
    {
        // Not a supported function in PHP3/4.  Chris Johnson, 16May2001.
        //    return mssql_affected_rows($this->Query_ID);
        $rsRows = mssql_query('SELECT @@rowcount AS rows', $this->Link_ID);
        if ($rsRows) {
            return mssql_result($rsRows, 0, 'rows');
        }
    }


    /**
     * Returns the number of rows
     *
     * @return  int
     */
    public function num_rows()
    {
        return mssql_num_rows($this->Query_ID);
    }

    public function num_fields()
    {
        return mssql_num_fields($this->Query_ID);
    }


    /**
     * Escape string for using in SQL-Statement
     *
     * @param   string  $sString
     * @return  string
     */
    public function escape($sString)
    {
        //ToDo implement specific database behaviour
        return addslashes($sString);
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
