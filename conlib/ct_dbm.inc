<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * PHPLIB Data Storage Container using DBM Files
 * Code inspired by ct_shm.inc v 1.1 
 * 
 * Requirements: 
 * @con_php_req 5
 * @con_template <Templatefiles>
 * @con_notice <Notice>
 * 
 *
 * @package    Contenido Backend <Area>
 * @version    1.1
 * @author     Daniel Lashua <daniel.lashua@gte.com>
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
 *   $Id: ct_dbm.inc 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}

class CT_DBM {
  ##
  ## Define these parameters by overwriting or by
  ## deriving your own class from it (recommended)
  ##

  var $dbm_file = "";    ## PREEXISTING DBM File 
                         ## writable by the web server UID

  ## end of configuration
  
  var $dbmid;            ## our dbm resource handle
  
  function ac_start() {
    # Open DBM file for write access
    $this->dbmid = dbmopen($this->dbm_file, "w");
  }

  function ac_get_lock() {
    # Not needed in this instance
  }

  function ac_release_lock() {
    # Not needed in this instance
  }

  function ac_newid($str, $name) {
    return $str;
  }

  function ac_store($id, $name, $str) {
    dbmreplace($this->dbmid, "$id$name", urlencode($str).";".time());
    return true;
  }

  function ac_delete($id, $name) {
    dbmdelete($this->dbmid, "$id$name");
  }

  function ac_gc($gc_time, $name) {
    $cmp = time() - $gc_time * 60;
    $i = dbmfirstkey($this->dbmid);
    while ($i) {
      $val = @dbmfetch($this->dbmid, $i);
      $dat = explode(";", $val);
      if(strcmp($dat[1], $cmp) < 0) {
        dbmdelete($this->dbmid, $i);
      }
      $i = dbmnextkey($this->dbmid,$i);
    }
  }

  function ac_halt($s) {
    echo "<b>$s</b>";
    exit;
  }

  function ac_get_value($id, $name) {
    $dat = explode(";", dbmfetch($this->dbmid, "$id$name"));
    return urldecode($dat[0]);
  }
}
?>
