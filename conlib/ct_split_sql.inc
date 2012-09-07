<?php
/**
 * Project: 
 * Contenido Content Management System
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
 * @package    Contenido Backend <Area>
 * @version    1.2
 * @author     Massimiliano Masserelli
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  2000-01-01
 *   modified 2008-07-03, bilal arslan, added security fix
 *
 *   $Id: ct_split_sql.inc 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 * 
 */

 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}


class CT_Split_Sql {
    ##
    ## Define these parameters by overwriting or by
    ## deriving your own class from it (recommened)
    ##
    
    var $database_table = "active_sessions_split";
    var $database_class = "";
    var $database_lock_semaphore = "";
    var $split_length = 4096;  ## Split data every xxx bytes

    ## The only supported storage method is base64 encoding
    ## end of configuration

    var $db;

    function ac_start() {
        $name = $this->database_class;
        $this->db = new $name;
    }

    function ac_get_lock() {
        if ( "" != $this->database_lock_semaphore ) {
            while ( ! $this->db->query("SELECT get_lock('%s')", 
                        $this->database_lock_semaphore) ) {
                $t = 1 + time(); while ( $t > time() ) { ; }
            }
        }
    }

    function ac_release_lock() {
        if ( "" != $this->database_lock_semaphore ) {
            $this->db->query("SELECT release_lock('%s')", 
                    $this->database_lock_semaphore);
        }
    }

    function ac_gc($gc_time, $name) {

//        Security Fix
        $timeout = time();
        $sqldate = date("YmdHis", $timeout - (Contenido_Security::escapeDB($gc_time, $this->db) * 60));
        $this->db->query(sprintf("DELETE FROM %s ".
                    "WHERE ct_changed < '%s' AND ct_name = '%s'",
                    $this->database_table, 
                    $sqldate,
                    Contenido_Security::escapeDB($name, $this->db)));
    }

    function ac_store($id, $name, $str) {

//        Security Fix
        $ret = true;
        $str = base64_encode($str);
        $name = addslashes($name);
        $now = date("YmdHis", time());
        $this->db->query("BEGIN TRANSACTION");
        $this->db->query(sprintf("DELETE FROM %s WHERE ct_sid='%s' AND ct_name='%s'",
            $this->database_table,
            Contenido_Security::escapeDB($id, $this->db),
            Contenido_Security::escapeDB($name, $this->db)
        ));
        $count = 0;
        while ($part = substr($str, 0, $this->split_length)) {
            $this->db->query(sprintf("INSERT INTO %s ".
                " (ct_sid, ct_name, ct_pos, ct_val, ct_changed) ".
                "  VALUES ".
                " ('%s','%s','%06d','%s','%s')",
                $this->database_table,
                $id,
                $name,
                $count++,
                $part,
                $now
            ));
            $str = substr($str, $this->split_length);
        }
        $this->db->query("END TRANSACTION");
        return $ret;
    }

    function ac_delete($id, $name) {

//        Security Fix
        $this->db->query(sprintf("DELETE FROM %s ".
                    "WHERE ct_name = '%s' AND ct_sid = '%s'",
                    $this->database_table,
                    Contenido_Security::escapeDB($name, $this->db),
                    Contenido_Security::escapeDB($id, $this->db)));
    }

    function ac_get_value($id, $name) {

    //        Security Fix
        $this->db->query(sprintf("SELECT ct_val, ct_pos FROM %s ".
                        "WHERE ct_sid  = '%s' AND ct_name = '%s' ".
                        "ORDER BY ct_pos",
                    $this->database_table,
                    Contenido_Security::escapeDB($id, $this->db),
                    Contenido_Security::escapeDB($name, $this->db)));
        $str="";
        while ($this->db->next_record()) {
          $str .= $this->db->f("ct_val");
        }
        if (! empty($str)) {
            $str = base64_decode($str);
        };
## DEB        echo $str;
        return $str;
    }

    function ac_newid($str, $name) {
        return $str;
    }

    function ac_halt($s) {
    //        Security Fix
        $s =  Contenido_Security::escapeDB($s, $this->db);
        $this->db->halt($s);
    }
}
?>
