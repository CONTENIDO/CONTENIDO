<?php

include_once("Debug_VisibleAdv.class.php");

class Debug_FileAndVisAdv extends Debug_VisibleAdv {
	
	static protected $_instance;
	private $_aItems;
	private $_buffer;
	
	private function __construct() {
		$this->_aItems = array();
	}
	
	static public function getInstance() {
		if (self::$_instance == null) {
			self::$_instance = new Debug_FileAndVisAdv();
		}
		return self::$_instance;
	}
	
	public function out($msg) {
		global $cfg;
		
		parent::out($msg);
		
		$file = $cfg['path']['contenido'].'logs'.DIRECTORY_SEPARATOR.'debug.log';
		if(is_writeable($file)) {
			$sDate = date('Y-m-d H:i:s');
			file_put_contents($file, $sDate.": ".$msg."\n", FILE_APPEND);
		}
	}
	
	public function show($mVariable, $sVariableDescription='', $bExit = false)
	{
		global $cfg;
		
		parent::show($mVariable, $sVariableDescription, $bExit);
		
		$file = $cfg['path']['contenido'].'logs'.DIRECTORY_SEPARATOR.'debug.log';
		
		if(is_writeable($file)) {
			$sDate = date('Y-m-d H:i:s');
	    	file_put_contents($file, '#################### '.$sDate.' ####################'."\n", FILE_APPEND);
	    	file_put_contents($file, $sVariableDescription."\n", FILE_APPEND);
	    	file_put_contents($file, print_r($mVariable, true)."\n", FILE_APPEND);
	    	file_put_contents($file, '#################### /'.$sDate.' ###################'."\n\n", FILE_APPEND);
			}
		}
}
