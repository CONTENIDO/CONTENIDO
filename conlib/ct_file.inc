<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * PHPLIB Data Storage Container using DBM Files
 * Code inspired by ct_shm.inc v 1.1
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    CONTENIDO Core
 * @version    1.2
 * @author     Oliver Teuber <oliver@teuber.com>
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
class CT_File
{
    /**
     * Path where to store the session files writable by the web server UID
     * @vsr string
     */
    public $file_path = '';


    public function ac_start()
    {
        // Not needed in this instance
    }

    public function ac_get_lock()
    {
        // Not needed in this instance
    }

    public function ac_release_lock()
    {
        // Not needed in this instance
    }

    public function ac_newid($str, $name)
    {
        return $str;
    }

    public function ac_store($id, $name, $str)
    {
        return (bool) cFileHandler::write($this->file_path . "$id$name", true);
    }

    public function ac_delete($id, $name)
    {
        unlink($this->file_path . "$id$name");
    }

    public function ac_gc($gc_time, $name)
    {
        if (!$hDir = opendir($this->file_path)) {
            return;
        }

        $time = time() - $gc_time;
        while (false !== ($sDirEntry = readdir($hDir))) {
            if ($sDirEntry != '.' && $sDirEntry != '..') {
                $pathName = $this->file_path . '/' . $sDirEntry;
                if (is_file($pathName) && strpos($sDirEntry, $name) !== false
                    && fileatime($pathName) < $time)
                {
                    unlink($pathName);
                }
            }
        }
        closedir($hDir);
    }

    public function ac_halt($s)
    {
        echo "<b>$s</b>";
        exit;
    }

    public function ac_get_value($id, $name)
    {
        if (cFileHandler::exists($this->file_path . "$id$name")) {
            $content = cFileHandler::read($this->file_path . "$id$name");
            return urldecode($content);
        } else {
            return '';
        }
    }
}

?>