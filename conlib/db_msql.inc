<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * mSQL database driver
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    Contenido database
 * @version    0.2
 * @author     Sascha Schumann <sascha@schumann.cx>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release unknown
 *
 * {@internal
 *   created  unknown
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


/*
 * mSQL database driver
 *
 * Copyright (c) 1998-2000 NetUSE AG
 *                    Boris Erdmann, Kristian Koehntopp
 *
 * Derived from db_mysql.inc by Sascha Schumann <sascha@schumann.cx>
 */
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
            'type' => 'msql'
        ));
        parent::__construct($options);
    }


    /**
     * Establishes a connection to the database.
     *
     * @return  resource|bool  Returns a connection id on success, or false on error.f     */
    protected function _connect()
    {
        extract($this->_aDbCfg['connection']);

        // Check for local connect
        $dbh = empty($host) ? msql_pconnect() : msql_pconnect($host);

        // Still not connected? Raise error.
        if (!$dbh) {
            $this->halt('mSQL _connect() Failed');
            return false;
        }

        // Select current database
        if (!msql_select_db($database, $dbh)) {
            $this->halt('mSQL _connect() Cannot use database ' . $database);
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
        $this->Query_ID = msql_query($sQuery,$this->Link_ID);
        $this->Row      = 0;
        $this->Error    = msql_error();
        $this->Errno    = ($this->Error !== '') ? '1' : '0';
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
        $this->Record = msql_fetch_array($this->Query_ID);
        $this->Row   += 1;
        $this->Error  = msql_error();

        $stat = is_array($this->Record);
        if (!$stat && $this->Auto_Free) {
            msql_free_result($this->Query_ID);
            $this->Query_ID = 0;
        }
        return $stat;
    }


    public function seek($pos)
    {
        $status = msql_data_seek($this->Query_ID, $pos);
        if ($status) {
            $this->Row = $pos;
        }
        return;
    }



    /**
     * Closes the connection and frees the query id.
     *
     * @return  void
     */
    public function disconnect()
    {
        if (is_resource($this->Link_ID)) {
            msql_close($this->Link_ID);
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
        $id = @msql_list_fields($this->Database,  $table);
        if ($id < 0) {
            $this->Error = msql_error();
            $this->halt('Metadata query failed.');
        }
        $count = msql_num_fields($id);

        for ($i=0; $i<$count; $i++) {
            $res[$i]['table'] = msql_fieldtable($id, $i);
            $res[$i]['name']  = msql_fieldname($id, $i);
            $res[$i]['type']  = msql_fieldtype($id, $i);
            $res[$i]['len']   = msql_fieldlen($id, $i);
            $res[$i]['flags'] = msql_fieldflags($id, $i);
            $res['meta'][$res[$i]['name']] = $i;
            $res['num_fields']= $count;
        }

        msql_free_result($id);
        return $res;
    }


    public function affected_rows()
    {
        return msql_affected_rows($this->Query_ID);
    }


    /**
     * Returns the number of rows
     *
     * @return  int
     */
    public function num_rows()
    {
        return msql_num_rows($this->Query_ID);
    }


    public function num_fields()
    {
        return msql_num_fields($this->Query_ID);
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
