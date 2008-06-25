<?php
/**
* $RCSfile$
*
* Description: Debug object to not output info at all.
*
* Note: Be careful when using $bExit = true as this will NOT cause a die() in this object!
*
* @version 1.1.1
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2008-05-07
* modified 2008-05-21 Added methods add(), reset(), showAll()
* modified 2008-06-25 Removed die() from show() method
* }}
*
* $Id$:
*/
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