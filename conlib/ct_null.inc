<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * PHPLIB Data Storage Container using nothing
 *
 * Requirements:
 * @con_php_req 5
 * @con_notice
 * I wrote this for a quickie test of a website on a server
 * where I could not use any other container
 * It's also a good skeleton for writing a new container
 *
 *
 * @package    CONTENIDO Backend Session
 * @version    1.2
 * @author     Sascha Schumann <sascha@schumann.cx>
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

class CT_Null
{
    public function ac_start()
    {
    }

    public function ac_get_lock()
    {
    }

    public function ac_release_lock()
    {
    }

    public function ac_newid($str, $name)
    {
        return $str;
    }

    public function ac_store($id, $name, $str)
    {
        return true;
    }

    public function ac_delete($id, $name)
    {
    }

    public function ac_gc($gc_time, $name)
    {
    }

    public function ac_halt($s) {
        echo "<b>$s</b>";
        exit;
    }

    public function ac_get_value($id, $name)
    {
        return '';
    }
}

?>