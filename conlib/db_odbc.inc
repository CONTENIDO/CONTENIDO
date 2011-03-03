<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * ODBC database driver.
 *
 * Requirements:
 * @con_php_req 5
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
 *   created  2002-07-21
 *   modified unknown, Modified by Guarneri carmelo (carmelo@melting-soft.com)
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
     * Flag to use odbc cursor.
     * @var  int
     */
    public $UseODBCCursor = 0;


    /**
     * Constructor.
     *
     * @param  array  $options  Optional assoziative options
     */
    public function __construct(array $options = array())
    {
        $options = array_merge($options, array(
            'type' => 'odbc',
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

        $dbh = odbc_pconnect($database, $user, $password, $this->UseODBCCursor);
        if (!$dbh) {
            $this->halt("Link-ID == false, odbc_pconnect failed");
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
        # rei@netone.com.br suggested that we use this instead of the odbc_exec().
        # He is on NT, connecting to a Unix MySQL server with ODBC. -- KK
        # $this->Query_ID = odbc_prepare($this->Link_ID,$sQuery);
        # $this->Query_Ok = odbc_execute($this->Query_ID);

        $this->Query_ID = odbc_exec($this->Link_ID, $sQuery);
        $this->Row      = 0;
        odbc_binmode($this->Query_ID, 1);
        odbc_longreadlen($this->Query_ID, 4096);

        if (!$this->Query_ID) {
            $this->Errno = 1;
            $this->Error = 'General Error (The ODBC interface cannot return detailed error messages).';
            $this->halt("ODBC error " . $this->Errno . ": " . $this->Error . "\n" . $sQuery);
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
        $this->Record = array();
        $stat         = (bool) odbc_fetch_into($this->Query_ID, ++$this->Row, &$this->Record);
        if (!$stat) {
            if ($this->Auto_Free) {
                odbc_free_result($this->Query_ID);
                $this->Query_ID = 0;
            }
        } else {
            // add to Record[<key>]
            $count = odbc_num_fields($this->Query_ID);
            for ($i=1; $i<=$count; $i++) {
                $this->Record[strtolower(odbc_field_name($this->Query_ID, $i))] = $this->Record[$i-1];
            }
        }
        return $stat;
    }


    public function seek($pos)
    {
        $this->Row = $pos;
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
            odbc_close($this->Link_ID);
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
        $id = odbc_exec($this->Link_ID, "select * from $table");
        if (!$id) {
            $this->Errno = 1;
            $this->Error = 'General Error (The ODBC interface cannot return detailed error messages).';
            $this->halt('Metadata query failed.');
        }
        $count = odbc_num_fields($id);

        for ($i=1; $i<=$count; $i++) {
            $res[$i]['table'] = $table;
            $name             = odbc_field_name ($id, $i);
            $res[$i]['name']  = $name;
            $res[$i]['type']  = odbc_field_type ($id, $name);
            $res[$i]['len']   = 0;  // can we determine the width of this column?
            $res[$i]['flags'] = ''; // any optional flags to report?
        }

        odbc_free_result($id);
        return $res;
    }


    public function affected_rows()
    {
        return odbc_num_rows($this->Query_ID);
    }


    public function num_rows()
    {
        # Many ODBC drivers don't support odbc_num_rows() on SELECT statements.
        $num_rows = odbc_num_rows($this->Query_ID);
        //printf ($num_rows."<br>");

        # This is a workaround. It is intended to be ugly.
        if ($num_rows < 0) {
            $i = 10;
            while (odbc_fetch_row($this->Query_ID, $i)) {
                $i *= 10;
            }

            $j = 0;
            while ($i != $j) {
                $k = $j + intval(($i-$j) / 2);
                if (odbc_fetch_row($this->Query_ID, $k)) {
                    $j = $k;
                } else {
                    $i = $k;
                }
                if (($i-$j) == 1) {
                    if (odbc_fetch_row($this->Query_ID, $i)) {
                        $j = $i;
                    } else {
                        $i = $j;
                    }
                }
                //printf("$i $j $k <br>");
            }
            $num_rows = $i;
        }

        return $num_rows;
    }


    public function num_fields()
    {
        return count($this->Record) / 2;
    }


    /* public: Escape string for using in SQL-Statement */
    public function escape($sString)
    {
        //ToDo implement specific database behaviour
        return addslashes($sString);
    }

}
