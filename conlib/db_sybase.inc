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
 * @version    1.3.1
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
 *   modified 2011-03-13, Murat Purc, Cleanup and documentation.
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
            'type' => 'sybase',
        ));
        parent::__construct($options);
    }

    /**
     * @see DB_Sql_Abstract::_connect()
     */
    protected function _connect()
    {
        extract($this->_aDbCfg['connection']);

        $dbh = sybase_pconnect($host, $user, $password);
        if (!$dbh) {
            $this->halt('Sybase _connect() Failed');
            return null;
        }

        if (!sybase_select_db($database, $dbh)) {
            $this->halt('cannot use database ' . $database);
            return null;
        }

        return $dbh;
    }


    /**
     * @see DB_Sql_Abstract::_query()
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
     * @see DB_Sql_Abstract::next_record()
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


    /**
     * @see DB_Sql_Abstract::seek()
     */
    public function seek($pos)
    {
        $status = sybase_data_seek($this->Query_ID, $pos);
        if ($status) {
            $this->Row = $pos;
        }
        return;
    }


    /**
     * @see DB_Sql_Abstract::lock()
     */
    public function lock($table, $mode = 'write')
    {
        // @todo
    }


    /**
     * @see DB_Sql_Abstract::unlock()
     */
    public function unlock()
    {
        // @todo
    }


    /**
     * @see DB_Sql_Abstract::disconnect()
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


    /**
     * @see DB_Sql_Abstract::_metaData()
     */
    protected function _metaData($table, $full = false)
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


    /**
     * @see DB_Sql_Abstract::affected_rows()
     */
    public function affected_rows()
    {
        return sybase_affected_rows($this->Query_ID);
    }


    /**
     * @see DB_Sql_Abstract::num_rows()
     */
    public function num_rows()
    {
        return sybase_num_rows($this->Query_ID);
    }


    /**
     * @see DB_Sql_Abstract::num_fields()
     */
    public function num_fields()
    {
        return sybase_num_fields($this->Query_ID);
    }


    /**
     * @see DB_Sql_Abstract::escape()
     */
    public function escape($sString)
    {
        //@todo implement specific database behaviour
        return addslashes($sString);
    }


    /**
     * @see DB_Sql_Abstract::_tableNames()
     */
    protected function _tableNames()
    {
        // @todo implement me
        return null;
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
