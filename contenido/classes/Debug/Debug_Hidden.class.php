<?php
/**
* $RCSfile$
*
* Description: Debug object to show info hidden in HTML comment-blocks.
*
* @version 1.0.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2007-01-01
* }}
*
* $Id$
*/
include_once('IDebug.php');

class Debug_Hidden implements IDebug {
	
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
			self::$_instance = new Debug_Hidden();
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
		echo "\n <!-- dbg";
		if ($sVariableDescription != '') {
			echo ' ' . strval($sVariableDescription);
		}
		echo " -->\n";
		echo '<!--' . "\n";
		if (is_array($mVariable)) {
			print_r($mVariable);
		} else {
			var_dump($mVariable);
		}
		echo "\n" . '//-->' . "\n";
		echo "\n <!-- /dbg -->\n";
		
		if ($bExit === true) {
			die();
		}
	}
}
?>