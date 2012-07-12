<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * PHPLIB Data Storage Container using a SQL database
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO Core
 * @subpackage Session
 * @version    1.2
 * @author     Boris Erdmann, Kristian Koehntopp, Sascha Schumann <sascha@schumann.cx>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <CONTENIDO Version>
 *
 * {@internal
 *   created  2002-07-21
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * @package    CONTENIDO Core
 * @subpackage Session
 */
class CT_Sql {

    /**
     * Database lock semaphore
     * @var string
     */
    public $database_lock_semaphore = '';

    /**
     * Encoding mode, feasible values are 'slashes' or 'base64'
     * @var string
     */
    public $encoding_mode = 'base64';

    ## end of configuration

    /**
     * Database instance
     * @var DB_Contenido
     */
    public $db;

    public function ac_start() {
        $name = $this->database_class;
        $this->db = new $name;
    }

    public function ac_get_lock() {
        if ('' != $this->database_lock_semaphore) {
            $query = sprintf("SELECT get_lock('%s')", $this->database_lock_semaphore);
            while (!$this->db->query($query)) {
                $t = 1 + time();
                while ($t > time()) {
;
                }
            }
        }
    }

    public function ac_release_lock() {
        if ('' != $this->database_lock_semaphore) {
            $query = sprintf("SELECT release_lock('%s')", $this->database_lock_semaphore);
            $this->db->query($query);
        }
    }

    public function ac_gc($gc_time, $name) {
        //  Security Fix
        $timeout = time();
        $sqldate = date('YmdHis', $timeout - ($this->db->escape($gc_time) * 60));
        $this->db->query(sprintf(
            "DELETE FROM %s WHERE changed < '%s' AND name = '%s'", $this->database_table, $sqldate, $this->db->escape($name)
        ));
    }

    public function ac_store($id, $name, $str) {
        // Security Fix
        $ret = true;

        switch ($this->encoding_mode) {
            case 'slashes':
                $str = addslashes($name . ':' . $str);
                break;
            case 'base64':
            default:
                $str = base64_encode($name . ':' . $str);
        };

        $name = addslashes($name);

        // Update duration of visit
        global $HTTP_REFERER, $HTTP_USER_AGENT, $REMOTE_ADDR;

        $now = date('YmdHis', time());
        $id = $this->db->escape($id);
        $name = $this->db->escape($name);

        $uquery = sprintf(
                "UPDATE %s SET val='%s', changed='%s' WHERE sid='%s' AND name='%s'", $this->database_table, $str, $now, $id, $name
        );

        $squery = sprintf(
                "SELECT COUNT(*) FROM %s WHERE val='%s' AND changed='%s' AND sid='%s' AND name='%s'", $this->database_table, $str, $now, $id, $name
        );

        $iquery = sprintf(
                "INSERT INTO %s (sid, name, val, changed) VALUES ('%s', '%s', '%s', '%s')", $this->database_table, $id, $name, $str, $now
        );

        $this->db->lock($this->database_table);
        $this->db->query($uquery);

        // FIRST test to see if any rows were affected.
        //   Zero rows affected could mean either there were no matching rows
        //   whatsoever, OR that the update statement did match a row but made
        //   no changes to the table data (i.e. UPDATE tbl SET col = 'x', when
        //   "col" is _already_ set to 'x') so then,
        // SECOND, query(SELECT...) on the sid to determine if the row is in
        //   fact there,
        // THIRD, verify that there is at least one row present, and if there
        //   is not, then
        // FOURTH, insert the row as we've determined that it does not exist.

        if ($this->db->affected_rows() == 0
                && $this->db->query($squery)
                && $this->db->next_record() && $this->db->f(0) == 0
                && !$this->db->query($iquery)) {
            $ret = false;
        }

        $this->db->unlock();

        return $ret;
    }

    public function ac_delete($id, $name) {
        //  Security Fix
        $this->db->query(sprintf(
            "DELETE FROM %s WHERE name = '%s' AND sid = '%s'", $this->database_table, $this->db->escape($name), $this->db->escape($id)
        ));
    }

    public function ac_get_value($id, $name) {
        //  Security Fix
        $this->db->query(sprintf(
            "SELECT val FROM %s WHERE sid = '%s' AND name = '%s'", $this->database_table, $this->db->escape($id), $this->db->escape($name)
        ));

        if ($this->db->next_record()) {
            $str = $this->db->f('val');
            $str2 = base64_decode($str);

            if (preg_match('/^' . $name . ':.*/', $str2)) {
                $str = preg_replace('/^' . $name . ':/', '', $str2);
            } else {
                $str3 = stripslashes($str);
                if (preg_match('/^' . $name . ':.*/', $str3)) {
                    $str = preg_replace('/^' . $name . ':/', '', $str3);
                } else {
                    switch ($this->encoding_mode) {
                        case 'slashes':
                            $str = stripslashes($str);
                            break;
                        case 'base64':
                        default:
                            $str = base64_decode($str);
                    }
                }
            }
            return $str;
        }
        return '';
    }

    public function ac_newid($str, $name) {
        return $str;
    }

    public function ac_halt($s) {
        $this->db->halt($s);
    }

}

?>