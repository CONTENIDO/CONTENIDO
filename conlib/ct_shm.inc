<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * PHPLIB Data Storage Container using Shared Memory
 * 
 * Requirements: 
 * @con_php_req 5
 * @con_template <Templatefiles>
 * @con_notice <Notice>
 * 
 *
 * @package    Contenido Backend <Area>
 * @version    1.2
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
 *   $Id: ct_shm.inc 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 * 
 */

 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}

class CT_Shm {
	##
	## Define these parameters by overwriting or by
	## deriving your own class from it (recommened)
	##

	var $max_sessions = 500;	## maximum supported sessions
	var $shm_key = 900000;		## key of shared memory segment (unique)
	var $shm_size = 64000;		## size in bytes

	## end of configuration
	
	var $shmid;					## our shared memory handle
	var $semid;					## our semaphore handle

	function extract($id) {
		return substr($id, 0, strpos($id, "_"));
	}
	
	function ac_start() {
		$this->shmid = shm_attach($this->shm_key, $this->shm_size, 0600);
	}

	function ac_get_lock() {
		$this->semid = sem_get($this->shm_key + 1);
		sem_acquire($this->semid);
	}

	function ac_release_lock() {
		shm_detach($this->shmid);
		sem_release($this->semid);
	}

	function ac_newid($str, $name) {
		for($i = 1; $i <= $this->max_sessions &&
				(@shm_get_var($this->shmid, $i) != false); $i++);
		$id = $i."_".$str;
		$this->ac_store($id, $name, "");
		return $id;
	}

	function ac_store($id, $name, $str) {
		$val = "$id;".urlencode($name).";".urlencode($str).";".time();
		shm_put_var($this->shmid, $this->extract($id), $val);
		return true;
	}

	function ac_delete($id, $name) {
		shm_remove_var($this->shmid, $this->extract($id));
	}

	function ac_gc($gc_time, $name) {
		$cmp = time() - $gc_time * 60;
		for($i = 1; $i <= $this->max_sessions; $i++) {
			if(($val = @shm_get_var($this->shmid, $i)) != false) {
				$dat = explode(";", $val);
				if($name == $dat[1] && intval($dat[3]) < $cmp) {
					shm_remove_var($this->shmid, $i);
				}
			}
		}
	}

	function ac_halt($s) {
		echo "<b>$s</b>";
		exit;
	}

	function ac_get_value($id, $name) {
		$i = $this->extract($id);
		$var = shm_get_var($this->shmid, $i);
		if($var == "") return("");
		$dat = explode(";", $var);
		## if classname or md5 id does not match...
		if($name != urldecode($dat[1]) || $dat[0] != $id)
			$this->ac_halt("security stop");
		return urldecode($dat[2]);
	}
}
?>
