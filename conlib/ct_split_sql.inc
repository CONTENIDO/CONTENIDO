<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * PHPLIB Data Storage Container using a SQL database and multiple
 * rows for each element
 *
 * Requirements:
 * @con_php_req 5
 * @con_notice
 * Every session-name pair will end up in one OR MORE table rows, thus
 * allowing serialization of huge quantities of data.
 *
 * @package    CONTENIDO Core
 * @subpackage Session
 * @version    1.3
 * @author     Massimiliano Masserelli
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <CONTENIDO Version>
 *
 * {@internal
 *   created  2000-01-01
 *   modified 2008-07-03, bilal arslan, added security fix
 *   modified 2011-10-09, Murat Purc, Partly ported to PHP 5 and formatted/documented code
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * @package    CONTENIDO Core
 * @subpackage Session
 */
class CT_Split_Sql
{
    ##
    ## Define these parameters by overwriting or by
    ## deriving your own class from it (recommened)
    ##

    /**
     * Database table name
     * @var string
     */
    public $database_table = 'active_sessions_split';

    /**
     * Class name
     * @var string
     */
    public $database_class = '';

    /**
     * Database lock semaphore
     * @var string
     */
    public $database_lock_semaphore = '';

    /**
     * Split data every xxx bytes
     * @var int
     */
    public $split_length = 4096;

    ## The only supported storage method is base64 encoding
    ## end of configuration

    /**
     * Database instance
     * @var DB_Contenido
     */
    public $db;


    public function ac_start()
    {
        $name = $this->database_class;
        $this->db = new $name;
    }

    public function ac_get_lock()
    {
        if ('' != $this->database_lock_semaphore) {
            while (!$this->db->query("SELECT get_lock('%s')", $this->database_lock_semaphore)) {
                $t = 1 + time();
                while ($t > time()) { ; }
            }
        }
    }

    public function ac_release_lock()
    {
        if ('' != $this->database_lock_semaphore) {
            $this->db->query("SELECT release_lock('%s')", $this->database_lock_semaphore);
        }
    }

    public function ac_gc($gc_time, $name)
    {
        // Security Fix
        $timeout = time();
        $sqldate = date('YmdHis', $timeout - ($this->db->escape($gc_time) * 60));
        $this->db->query(sprintf(
            "DELETE FROM %s WHERE ct_changed < '%s' AND ct_name = '%s'",
            $this->database_table, $sqldate, $this->db->escape($name)
        ));
    }

    public function ac_store($id, $name, $str)
    {
        // Security Fix
        $ret = true;
        $str = base64_encode($str);
        $name = addslashes($name);
        $now = date('YmdHis', time());
        $this->db->query('BEGIN TRANSACTION');
        $this->db->query(sprintf(
            "DELETE FROM %s WHERE ct_sid='%s' AND ct_name='%s'",
            $this->database_table, $this->db->escape($id), $this->db->escape($name)
        ));
        $count = 0;
        while ($part = substr($str, 0, $this->split_length)) {
            $this->db->query(sprintf(
                "INSERT INTO %s (ct_sid, ct_name, ct_pos, ct_val, ct_changed) "
              . "VALUES ('%s', '%s', '%06d', '%s', '%s')",
                $this->database_table, $id, $name, $count++, $part, $now
            ));
            $str = substr($str, $this->split_length);
        }
        $this->db->query('END TRANSACTION');
        return $ret;
    }

    public function ac_delete($id, $name)
    {
        // Security Fix
        $this->db->query(sprintf(
            "DELETE FROM %s WHERE ct_name='%s' AND ct_sid='%s'",
            $this->database_table, $this->db->escape($name), $this->db->escape($id)
        ));
    }

    public function ac_get_value($id, $name)
    {
        // Security Fix
        $this->db->query(sprintf(
            "SELECT ct_val, ct_pos FROM %s WHERE ct_sid='%s' AND ct_name='%s' ORDER BY ct_pos",
            $this->database_table, $this->db->escape($id), $this->db->escape($name)
        ));
        $str = '';
        while ($this->db->next_record()) {
          $str .= $this->db->f('ct_val');
        }
        if (!empty($str)) {
            $str = base64_decode($str);
        };
## DEB        echo $str;
        return $str;
    }

    public function ac_newid($str, $name)
    {
        return $str;
    }

    public function ac_halt($s)
    {
        // Security Fix
        $s =  $this->db->escape($s);
        $this->db->halt($s);
    }
}
?>
