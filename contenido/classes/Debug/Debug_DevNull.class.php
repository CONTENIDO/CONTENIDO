<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Debug object to not output info at all.
 * Note: Be careful when using $bExit = true as this will NOT cause a die() in this object!
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
 *   created 2008-05-07
 *   modified 2008-05-21 Added methods add(), reset(), showAll()
 *   modified 2008-06-25 Removed die() from show() method
 * 
 *   $Id: Debug_DevNull.class.php 295 2008-06-25 15:42:18Z thorsten.granz $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

include_once('IDebug.php');

class Debug_DevNull implements IDebug {
	
	static private $_instance;
	
	/** 
	* Constructor
	* @access private
	*/
	private function __construct() {
		
	}
	
	/** 
	* static
	* @access public
	*/
	static public function getInstance() {
		if (self::$_instance == null) {
			self::$_instance = new Debug_DevNull();
		}
		return self::$_instance;
	}
	
	/**
	 * Outputs contents of passed variable to /dev/null
	 *
	 * @access public
	 * @param mixed $mVariable The variable to be displayed
	 * @param string $sVariableDescription The variable's name or description
	 * @param boolean $bExit If set to true, your app will NOT die() after output of current var
	 * @return void
	 */
	public function show($mVariable, $sVariableDescription='', $bExit = false) {}
	
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