<?php
##
## Copyright (c) 1999-2000 Internet Images srl
##                    Massimiliano Masserelli
##
## $Id: ct_split_sql.inc,v 1.1 2007/07/11 15:37:35 wirths Exp $
##
## PHPLIB Data Storage Container using a SQL database and multiple
## rows for each element
##
## Every session-name pair will end up in one OR MORE table rows, thus
## allowing serialization of huge quantities of data.
##

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
        $timeout = time();
        $sqldate = date("YmdHis", $timeout - ($gc_time * 60));
        $this->db->query(sprintf("DELETE FROM %s ".
                    "WHERE ct_changed < '%s' AND ct_name = '%s'",
                    $this->database_table, 
                    $sqldate,
                    addslashes($name)));
    }

    function ac_store($id, $name, $str) {
        $ret = true;
        $str = base64_encode($str);
        $name = addslashes($name);
        $now = date("YmdHis", time());
        $this->db->query("BEGIN TRANSACTION");
        $this->db->query(sprintf("DELETE FROM %s WHERE ct_sid='%s' AND ct_name='%s'",
            $this->database_table,
            $id,
            $name
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
        $this->db->query(sprintf("DELETE FROM %s ".
                    "WHERE ct_name = '%s' AND ct_sid = '%s'",
                    $this->database_table,
                    addslashes($name),
                    $id));
    }

    function ac_get_value($id, $name) {
        $this->db->query(sprintf("SELECT ct_val, ct_pos FROM %s ".
                        "WHERE ct_sid  = '%s' AND ct_name = '%s' ".
                        "ORDER BY ct_pos",
                    $this->database_table,
                    $id,
                    addslashes($name)));
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
        $this->db->halt($s);
    }
}
?>
