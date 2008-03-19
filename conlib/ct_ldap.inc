<?php
##
## Copyright (c) 1999-2000 Sascha Schumann <sascha@schumann.cx>
##
## $Id: ct_ldap.inc,v 1.1 2007/07/11 15:37:35 wirths Exp $
##
## PHPLIB Data Storage Container using a LDAP database
##

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
