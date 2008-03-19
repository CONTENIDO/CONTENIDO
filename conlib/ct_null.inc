<?php

##
## Copyright (c) 1998-2000 Sascha Schumann <sascha@schumann.cx>
##
## $Id: ct_null.inc,v 1.1 2007/07/11 15:37:35 wirths Exp $
##
## PHPLIB Data Storage Container using nothing
## 
## I wrote this for a quickie test of a website on a server
## where I could not use any other container
## 
## It's also a good skeleton for writing a new container


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
