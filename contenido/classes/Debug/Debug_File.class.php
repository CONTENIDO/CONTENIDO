<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Debug object to write info to a file.
 * In case you cannot output directly to screen when debugging a live system, this object writes 
 * the info to a file located in /contenido/logs/debug.log.
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.1.1
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created  2007-01-01
 *   modified 2008-05-21 Added methods add(), reset(), showAll()
 *   modified 2010-05-20 Murat Purc, Hey, last change was nearly 2 years ago ;-)... Fixed generated warnings, see [#CON-309]
 *
 *   $Id: Debug_File.class.php 1158 2010-05-20 16:10:48Z xmurrix $:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


include_once('IDebug.php');

class Debug_File implements IDebug {
	
	static private $_instance;
	static private $_hFileHandle;
	private $_sPathToLogs;
	private $_sFileName;
	private $_sPathToFile;
	
	/** 
	* Constructor
	* Opens filehandle for debug-logfile
	* @access private
	* @return void
	*/
    private function __construct() {
		global $cfg; // omfg, I know... TODO
        $this->_sPathToLogs = $cfg['path']['contenido'].'logs'.DIRECTORY_SEPARATOR;
		$this->_sFileName = 'debug.log';
		$this->_sPathToFile = $this->_sPathToLogs.$this->_sFileName;
        if (file_exists($this->_sPathToLogs) && is_writeable($this->_sPathToLogs)) {
		    self::$_hFileHandle = @fopen($this->_sPathToFile, 'a+'); // keep it quiet, might be used in production systems
		}
	}
	
	/**
	 * Closes file handle upon destruction of object
	 * @access public
	 * @return void
	 */
	public function __destruct() {
        if (is_resource(self::$_hFileHandle)) {
	        fclose(self::$_hFileHandle);
	    }
	}
	
	/** 
	* static
	* @access public
	* @return void
	*/
	static public function getInstance() {
		if (self::$_instance == null) {
			self::$_instance = new Debug_File();
		}
		return self::$_instance;
	}
	
	/**
	 * Outputs contents of passed variable in a preformatted, readable way
	 *
	 * @access public
	 * @param mixed $mVariable The variable to be displayed
	 * @param string $sVariableDescription The variable's name or description
	 * @param boolean $bExit If set to true, your app will die() after output of current var
	 * @return void
	 */
	public function show($mVariable, $sVariableDescription='', $bExit = false)
	{
        if (is_resource(self::$_hFileHandle) && is_writeable($this->_sPathToFile)) {
            $sDate = date('Y-m-d H:i:s');
            fwrite(self::$_hFileHandle, '#################### '.$sDate.' ####################'."\n");
            fwrite(self::$_hFileHandle, $sVariableDescription."\n");
            fwrite(self::$_hFileHandle, print_r($mVariable, true)."\n");
            fwrite(self::$_hFileHandle, '#################### /'.$sDate.' ###################'."\n\n");
        }
	}
	
    /**
	 * Interface implementation
	 * @access public
	 * @param mixed $mVariable
	 * @param string $sVariableDescription
	 * @return void
	 */
	public function add($mVariable, $sVariableDescription = '') {}
	/**
	 * Interface implementation
	 * @access public
	 * @return void
	 */
	public function reset() {}
	/**
	 * Interface implementation
	 * @access public
	 * @return string Here an empty string
	 */
	public function showAll() {}
}
?>