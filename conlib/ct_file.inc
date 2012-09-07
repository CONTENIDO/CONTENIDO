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
 * @author     Oliver Teuber <oliver@teuber.com>
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
 *   $Id: ct_file.inc 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}

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
