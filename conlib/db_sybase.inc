<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Sybase database driver
 *
 * Requirements:
 * @con_php_req 5
 * @con_notice
 * Adapted from db_mysql.inc by Sascha Schumann <sascha@schumann.cx>
 * metadata() contributed by Adelino Monteiro <adelino@infologia.pt>
 *
 *
 * @package    Contenido database
 * @version    1.3
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
            'type' => 'sybase',
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

        $dbh = sybase_pconnect($host, $user, $password);
        if (!$dbh) {
            $this->halt('Sybase _connect() Failed');
            return false;
        }

        if (!sybase_select_db($database, $dbh)) {
            $this->halt('cannot use database ' . $database);
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
        $this->Query_ID = sybase_query($sQuery, $this->Link_ID);
        $this->Row      = 0;
        if (!$this->Query_ID) {
            $this->Errno = 1;
            $this->Error = 'General Error (The MSSQL interface cannot return detailed error messages).';
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
        $this->Record = sybase_fetch_array($this->Query_ID);
        $this->Row   += 1;

        $stat = is_array($this->Record);
        if (!$stat && $this->Auto_Free) {
            sybase_free_result($this->Query_ID);
            $this->Query_ID = 0;
        }
        return $stat;
    }


    public function seek($pos)
    {
        $status = sybase_data_seek($this->Query_ID, $pos);
        if ($status) {
            $this->Row = $pos;
        }
        return;
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
            sybase_close($this->Link_ID);
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
        $result = $this->query("exec sp_columns $table");
        if ($result < 0) {
            $this->Errno = 1;
            $this->Error = 'Metadata query failed';
            $this->halt('Metadata query failed.');
        }
        $count = sybase_num_rows($result);

        for ($i=0; $i<$count; $i++) {
            $res[$i]['table']    = $table;
            $res[$i]['name']     = sybase_result($result, $i, 'COLUMN_NAME');
            $res[$i]['type']     = sybase_result($result, $i, 'TYPE_NAME');
            $res[$i]['len']      = sybase_result($result, $i, 'LENGTH');
            $res[$i]['position'] = sybase_result($result, $i, 'ORDINAL_POSITION');
            $res[$i]['flags']    = sybase_result($result, $i, 'REMARKS');
        }
    }


    public function affected_rows()
    {
        return sybase_affected_rows($this->Query_ID);
    }


    public function num_rows()
    {
        return sybase_num_rows($this->Query_ID);
    }


    public function num_fields()
    {
        return sybase_num_fields($this->Query_ID);
    }


    /* public: Escape string for using in SQL-Statement */
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
