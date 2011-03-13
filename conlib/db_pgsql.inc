<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * PostgreSQL database driver
 *
 * Requirements:
 * @con_php_req 5
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
            'type' => 'pgsql',
        ));
        parent::__construct($options);
    }


    public function ifadd($add, $me)
    {
        if ('' != $add) {
            return ' ' . $me . $add;
        }
    }


    /**
     * @see DB_Sql_Abstract::_connect()
     */
    protected function _connect()
    {
        extract($this->_aDbCfg['connection']);

        $cstr = 'dbname=' . $database .
            $this->ifadd($host, 'host=') .
            $this->ifadd($port, 'port=') .
            $this->ifadd($user, 'user=') .
            $this->ifadd($password, 'password=');

        $dbh = pg_pconnect($cstr);
        if (!$dbh) {
            $this->halt('PostgreSQL _connect() Failed');
            return null;
        }

        return $dbh;
    }


    /**
     * @see DB_Sql_Abstract::_query()
     */
    protected function _query($sQuery)
    {
        $this->Query_ID = pg_execute($this->Link_ID, $sQuery);
        $this->Row      = 0;
        $this->Error    = pg_last_error($this->Link_ID);
        $this->Errno    = ($this->Error == '') ? 0 : 1;
        if (!$this->Query_ID) {
            $this->halt($sQuery);
        }
    }


    /**
     * @see DB_Sql_Abstract::next_record()
     */
    public function next_record()
    {
        $this->Record = @pg_fetch_array($this->Query_ID, $this->Row++);
        $this->Error  = pg_last_error($this->Link_ID);
        $this->Errno  = ($this->Error == '') ? 0 : 1;

        $stat = is_array($this->Record);
        if (!$stat && $this->Auto_Free) {
            pg_free_result($this->Query_ID);
            $this->Query_ID = 0;
        }
        return $stat;
    }


    /**
     * @see DB_Sql_Abstract::seek()
     */
    public function seek($pos)
    {
        $this->Row = $pos;
    }


    /**
     * @see DB_Sql_Abstract::lock()
     */
    public function lock($table, $mode = 'write')
    {
        if ($mode == 'write') {
            $result = pg_execute($this->Link_ID, 'LOCK TABLE ' . $table);
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
        return pg_execute($this->Link_ID, 'commit');
    }


    /* public: sequence numbers */
    public function nextid($seq_name)
    {
        $this->connect();

        if ($this->lock($this->Seq_Table)) {
            /* get sequence number (locked) and increment */
            $q  = sprintf("SELECT nextid FROM %s WHERE seq_name = '%s'", $this->Seq_Table, $seq_name);
            $id  = @pg_execute($this->Link_ID, $q);
            $res = @pg_fetch_array($id, 0);

            /* No current value, make one */
            if (!is_array($res)) {
                $currentid = 0;
                $q = sprintf("INSERT INTO %s VALUES('%s', %s)", $this->Seq_Table, $seq_name, $currentid);
                $id = @pg_execute($this->Link_ID, $q);
            } else {
                $currentid = $res['nextid'];
            }
            $nextid = $currentid + 1;
            $q = sprintf("UPDATE %s SET nextid = '%s' WHERE seq_name = '%s'", $this->Seq_Table, $nextid, $seq_name);
            $id = @pg_execute($this->Link_ID, $q);
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
        $this->_debug("Disconnecting...");
        if (is_resource($this->Link_ID)) {
            pg_close($this->Link_ID);
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
        $id = pg_execute($this->Link_ID, "SELECT * FROM $table");
        if ($id < 0) {
            $this->Error = pg_last_error($id);
            $this->Errno = 1;
            $this->halt('Metadata query failed.');
        }
        $count = pg_num_fields($id);

        for ($i=0; $i<$count; $i++) {
            $res[$i]['table'] = $table;
            $res[$i]['name']  = pg_field_name($id, $i);
            $res[$i]['type']  = pg_field_type($id, $i);
            $res[$i]['len']   = pg_field_size($id, $i);
            $res[$i]['flags'] = '';
        }

        pg_free_result($id);
        return $res;
    }


    /**
     * @see DB_Sql_Abstract::affected_rows()
     */
    public function affected_rows()
    {
        return pg_affected_rows($this->Query_ID);
    }


    /**
     * @see DB_Sql_Abstract::num_rows()
     */
    public function num_rows()
    {
        return pg_num_rows($this->Query_ID);
    }


    /**
     * @see DB_Sql_Abstract::num_fields()
     */
    public function num_fields()
    {
        return pg_num_fields($this->Query_ID);
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
        $return = array();
        $this->query("SELECT relname FROM pg_class WHERE relkind = 'r' AND NOT relname LIKE 'pg_%'");
        $i = 0;
        while ($this->next_record()) {
            $return[$i]['table_name']      = $this->f(0);
            $return[$i]['tablespace_name'] = $this->Database;
            $return[$i]['database']        = $this->Database;
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
        return array();
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
