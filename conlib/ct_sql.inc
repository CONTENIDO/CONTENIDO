<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * PHPLIB Data Storage Container using a SQL database
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    Contenido Backend <Area>
 * @version    1.1.1
 * @author     Boris Erdmann, Kristian Koehntopp, Sascha Schumann <sascha@schumann.cx>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 *
 * {@internal
 *   created  2002-07-21
 *   modified 2008-07-03, bilal arslan, added security fix
 *   modified 2009-11-06, Murat Purc, replaced deprecated functions (PHP 5.3 ready)
 *
 *   $Id: ct_sql.inc 1094 2009-11-06 01:22:13Z xmurrix $:
 * }}
 *
 */

 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}

class CT_Sql {
  ##
  ## Define these parameters by overwriting or by
  ## deriving your own class from it (recommened)
  ##


  var $database_lock_semaphore = "";

  var $encoding_mode = "base64";

  ## end of configuration

  var $db;

  function ac_start() {
    $name = $this->database_class;
    $this->db = new $name;
  }

  function ac_get_lock() {
    if ( "" != $this->database_lock_semaphore ) {
      $query = sprintf("SELECT get_lock('%s')", $this->database_lock_semaphore);
      while ( ! $this->db->query($query)) {
        $t = 1 + time(); while ( $t > time() ) { ; }
      }
    }
  }

  function ac_release_lock() {
    if ( "" != $this->database_lock_semaphore ) {
      $query = sprintf("SELECT release_lock('%s')", $this->database_lock_semaphore);
      $this->db->query($query);
    }
  }

  function ac_gc($gc_time, $name) {

//  Security Fix
    $timeout = time();
    $sqldate = date("YmdHis", $timeout - (Contenido_Security::escapeDB($gc_time, $this->db) * 60));
    $this->db->query(sprintf("DELETE FROM %s WHERE changed < '%s' AND name = '%s'",
                    $this->database_table,
                    $sqldate,
                    Contenido_Security::escapeDB($name, $this->db)));
    }

    function ac_store($id, $name, $str) {

//  Security Fix
    $ret = true;

    switch ( $this->encoding_mode ) {
     case "slashes":
      $str = addslashes($name . ":" . $str);
     break;

     case "base64":
     default:
      $str = base64_encode($name . ":" . $str);
    };

    $name = addslashes($name);

    ## update duration of visit
    global $HTTP_REFERER, $HTTP_USER_AGENT, $REMOTE_ADDR;

    $now = date("YmdHis", time());
    $uquery = sprintf("update %s set val='%s', changed='%s' where sid='%s' and name='%s'",
     $this->database_table,
     $str,
     $now,
     Contenido_Security::escapeDB($id, $this->db),
     Contenido_Security::escapeDB($name, $this->db));
    $squery = sprintf("select count(*) from %s where val='%s' and changed='%s' and sid='%s' and name='%s'",
     $this->database_table,
     $str,
     $now,
     Contenido_Security::escapeDB($id, $this->db),
     Contenido_Security::escapeDB($name, $this->db));
    $iquery = sprintf("insert into %s ( sid, name, val, changed ) values ('%s', '%s', '%s', '%s')",
     $this->database_table,
     Contenido_Security::escapeDB($id, $this->db),
     Contenido_Security::escapeDB($name, $this->db),
     $str,
     $now);

    $this->db->lock($this->database_table);
    $this->db->query($uquery);

    # FIRST test to see if any rows were affected.
    #   Zero rows affected could mean either there were no matching rows
    #   whatsoever, OR that the update statement did match a row but made
    #   no changes to the table data (i.e. UPDATE tbl SET col = 'x', when
    #   "col" is _already_ set to 'x') so then,
    # SECOND, query(SELECT...) on the sid to determine if the row is in
    #   fact there,
    # THIRD, verify that there is at least one row present, and if there
    #   is not, then
    # FOURTH, insert the row as we've determined that it does not exist.

    if ( $this->db->affected_rows() == 0
      && $this->db->query($squery)
      && $this->db->next_record() && $this->db->f(0) == 0
      && !$this->db->query($iquery)) {

      $ret = false;
    }

    $this->db->unlock();

    return $ret;
  }

  function ac_delete($id, $name) {
//  Security Fix
    $this->db->query(sprintf("delete from %s where name = '%s' and sid = '%s'",
      $this->database_table,
       Contenido_Security::escapeDB($name, $this->db),
      Contenido_Security::escapeDB($id, $this->db)));
  }

  function ac_get_value($id, $name) {

//  Security Fix
    $this->db->query(sprintf("select val from %s where sid  = '%s' and name = '%s'",
      $this->database_table,
      Contenido_Security::escapeDB($id, $this->db),
      Contenido_Security::escapeDB($name, $this->db)));

    if ($this->db->next_record()) {
      $str  = $this->db->f("val");
      $str2 = base64_decode( $str );

      if (preg_match('/^' . $name . ':.*/', $str2)) {
         $str = preg_replace('/^' . $name . ':/', '', $str2);
      } else {

        $str3 = stripslashes( $str );

        if (preg_match('/^' . $name . ':.*/', $str3)) {
          $str = preg_replace('/^' . $name . ':/', '', $str3);
        } else {

          switch ( $this->encoding_mode ) {
            case "slashes":
              $str = stripslashes($str);
            break;

            case "base64":
            default:
              $str = base64_decode($str);
          }
        }
      };
      return $str;
    };
    return "";
  }

  function ac_newid($str, $name) {
    return $str;
  }

  function ac_halt($s) {
    $this->db->halt($s);
  }
}
?>