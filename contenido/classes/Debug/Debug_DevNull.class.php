<?php
/**
* $RCSfile$
*
* Description: Debug object to not output info at all.
*
* Note: Be careful when using $bExit = true as this will cause a die() with a message!
*
* @version 1.1.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2008-05-07
* modified 2008-05-21 Added methods add(), reset(), showAll()
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
	 * @param boolean $bExit If set to true, your app will die() after output of current var
	 * @return void
	 */
	public function show($mVariable, $sVariableDescription='', $bExit = false)
	{
		// no action taken, except
		if ($bExit === true) {
			die('<p style="font-size:80%;margin:5px 0;padding:5px;background-color:#ccc;color:#000;"><b>debugg\'ed to /dev/null</b></p>'."\n");
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