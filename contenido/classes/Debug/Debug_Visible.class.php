<?php
/**
* $RCSfile$
*
* Description: Debug object to show info on screen.
*
* @version 1.1.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2007-01-01
* modified 2008-05-21 Added methods add(), reset(), showAll()
* }}
*
* $Id$:
*/
include_once('IDebug.php');

class Debug_Visible implements IDebug {
	
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
			self::$_instance = new Debug_Visible();
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
		$bTextarea = false;
		$bPlainText = false;
		if (is_array($mVariable)) {
			if (sizeof($mVariable) > 10) {
				$bTextarea = true;
			} else {
				$bPlainText = true;
			}
		}
		if (is_object($mVariable)) {
			$bTextarea = true;
		}
		if (is_string($mVariable)) {
			if (preg_match('/<(.*)>/', $mVariable)) {
				if (strlen($mVariable) > 40) {
					$bTextarea = true;
				} else {
					$bPlainText = true;
					$mVariable = htmlspecialchars($mVariable);
				}
			} else {
				$bPlainText = true;
			}
		}
		
		echo '<div style="margin:5px 0;padding:5px;border:1px solid #ccc;font-family:Georgia,serif;">'."\n";
		echo '<p style="font-size:80%;margin:5px 0;padding:5px;background-color:#ccc;color:#000;font-family:Verdana,sans-serif;"><b>DEBUG '.$sVariableDescription.'</b></p>'."\n";
		if ($bTextarea === true) {
			echo '<textarea rows="10" cols="100">';
		} elseif ($bPlainText === true) {
			echo '<pre>';
		} else {
			echo '<pre>';
		}
		
		if (is_array($mVariable)) {
			print_r($mVariable);
		} else {
			var_dump($mVariable);
		}
		
		if ($bTextarea === true) {
			echo '</textarea>';
		} elseif ($bPlainText === true) {
			echo '</pre>';
		} else {
			echo '</pre>';
		}
		echo '</div>';
		if ($bExit === true) {
			die('<p style="font-size:80%;margin:5px 0;padding:5px;background-color:#ccc;color:#000;"><b>debugg\'ed</b></p>'."\n");
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