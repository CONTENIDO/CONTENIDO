<?php
##
## Copyright (c) 1999-2000 Daniel Lashua <daniel.lashua@gte.com>
##
## $Id: ct_dbm.inc,v 1.1 2007/07/11 15:37:35 wirths Exp $
##
## PHPLIB Data Storage Container using DBM Files
##
## Code inspired by ct_shm.inc v 1.1 

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
