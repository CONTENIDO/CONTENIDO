<?php

##
## Copyright (c) 1999-2000 Oliver Teuber <oliver@teuber.com>
##
## PHPLIB Data Storage Container using Files
##
## Code inspired by ct_dbm.inc 

class CT_File {
    ##
    ## Define these parameters by overwriting or by
    ## deriving your own class from it (recommened)
    ##

    var $file_path = "";  ## Path where to store the session files
                          ## writable by the web server UID

    ## end of configuration
    
    function ac_start() {
        # Not needed in this instance
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

        $f=fopen($this->file_path . "$id$name",'w+');
        if($f<0)
        {
            return false;
        }
        fputs($f,urlencode($str));
        fclose($f);

        return true;
    }

    function ac_delete($id, $name) {
        unlink($this->file_path."$id$name");
    }

    function ac_gc($gc_time, $name) {
    }

    function ac_halt($s) {
        echo "<b>$s</b>";
        exit;
    }

    function ac_get_value($id, $name) {
        if(file_exists($this->file_path."$id$name"))
        {

            $f=fopen($this->file_path."$id$name",'r');
            if($f<0)
                return '';

            $s=fgets($f,10240);
            fclose($f);
            return urldecode($s);
        }
        else
            return '';
    }
}
?>
