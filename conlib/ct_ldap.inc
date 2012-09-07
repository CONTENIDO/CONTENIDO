<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * PHPLIB Data Storage Container a LDAP database
 * 
 * Requirements: 
 * @con_php_req 5
 * 
 *
 * @package    Contenido Backend <Area>
 * @version    1.1
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
 *   $Id: ct_ldap.inc 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}

class CT_Ldap {
	##
	## Configurable parameters
	##
	var $ldap_host = "localhost";
	var $ldap_port = 389;
	var $basedn    = "dc=your-domain, dc=com";
	var $rootdn    = "cn=root, dc=your-domain, dc=com";
	var $rootpw    = "secret";
	var $objclass  = "phplibdata";

	## end of configuration

	var $ds;
	
	function ac_start() {
		$this->ds = ldap_connect($this->ldap_host, $this->ldap_port);
		if(!$this->ds) {
			$this->ac_halt("LDAP connect failed");
		}
		if(!ldap_bind($this->ds, $this->rootdn, $this->rootpw)) {
			$this->ac_halt("LDAP bind failed");
		}
	}

	function ac_halt($msg="") {
		echo "Session_ldap failed: <b>".htmlentities($msg)."</b><p>\n";
		exit;
	}

	function ac_store($id, $name, $str) {
		$dn = "cn=$id_$name, ".$this->basedn;
		$entry = array(
				"cn" => "$id_$name",
				"str" => $str,
				"objectclass" => $this->objclass
				);
		if(!@ldap_modify($this->ds, $dn, $entry)) {
			if(!ldap_add($this->ds, $dn, $entry)) {
				$this->ac_halt("LDAP add failed");
			}
		}
	}
	
	function ac_delete($id, $name) {
		ldap_delete($this->ds, "cn=$id_$name, ".$this->basedn);
	}

	function ac_get_value($id, $name) {
		$sr = ldap_search($this->ds, $this->basedn, "cn=$id_$name");
		$inf = ldap_get_entries($this->ds, $sr);
		$str = $inf[0]["str"][0];
		ldap_free_result($sr);
		return $str;
	}

	function ac_release_lock() {

	}

	function ac_get_lock() {

	}

	function ac_newid($str, $name) {
		return $str;
	}
	
	function ac_auth($username, $password) {
		## we need a username and a md5() encrypted password
		$sr = ldap_search($this->ds, $this->basedn, "username=$username");
		if(ldap_count_entries($this->ds, $sr) > 0) {
			$inf = ldap_get_entries($this->ds, $sr);
			$passmd5 = $inf[0]["password"][0];
			if(md5($password) == $passmd5) {
				return array($inf[0]["uid"][0],
						$inf[0]["perms"][0]);
			}
		}
		return array();
	}
};
?>
