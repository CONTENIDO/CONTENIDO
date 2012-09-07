<?php
/**
 * Project: 
 * Contenido Content Management System
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
 * @package    Contenido Backend <Area>
 * @version    <version>
 * @author     Sascha Schumann <sascha@schumann.cx>
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
 *   $Id: ct_null.inc 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}

class CT_Null {
	function ac_start() {
	}

	function ac_get_lock() {
	}

	function ac_release_lock() {
	}

	function ac_newid($str, $name) {
		return $str;
	}

	function ac_store($id, $name, $str) {
		return true;
	}

	function ac_delete($id, $name) {
	}

	function ac_gc($gc_time, $name) {
	}

	function ac_halt($s) {
		echo "<b>$s</b>";
		exit;
	}

	function ac_get_value($id, $name) {
		return "";
	}
}
?>
