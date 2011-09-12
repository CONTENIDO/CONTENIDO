<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * This class provides validation methods for HTTP parameters (GET and POST).
 * Originally based on work of kummer and started by discussion in CONTENIDO forum this class
 * is a little bit "re-writed" for better interaction with CONTENIDO.
 * Thanks to Andreas Kummer (aka kummer) for this great idea!
 * 
 * Requirements: 
 * @con_php_req 5.0
 * @con_notice ToDo: Error page re-direction?
 *
 *
 * @package    CONTENIDO Backend classes
 * @version    1.1.2
 * @author     Andreas Kummer, Holger Librenz
 * @copyright  atelierQ Kummer, four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * @TODO: Some features are the same as in Contenido_Security (see contenido/classes/class.security.php),
 *        merge them...
 *
 * {@internal 
 *   created 2008-02-06
 *   modified 2008-06-10, I. van Peeren, initially set $this->bLog as $bLog in config file
 *   modified 2008-07-02, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

#### FORMAT CONSTANTS ####
define('CON_CHECK_INTEGER', '/^[0-9]*$/'); // integer value
define('CON_CHECK_PRIMITIVESTRING', '/^[a-zA-Z0-9 -_]*$/'); // simple string
define('CON_CHECK_STRING', '/^[\w0-9 -_]*$/'); // more complex string
define('CON_CHECK_HASH32', '/^[a-zA-Z0-9]{32}$/'); // 32-character hash


/**
 * This class is the extended version of excelent 
 * code made by kummer.
 * 
 * @version 1.0.1
 * @see http://contenido.org/forum/viewtopic.php?p=113492#113492
 */
class HttpInputValidator {
	
	/**
	 * Associative array with available POST parameter name as
	 * key and flag whether this parameter is "clean" or not.
	 *
	 * @var array
	 */
	var $aPostVariables = array();

	/**
	 * Path and filename of logfile
	 *
	 * @var string
	 */
	var $sLogPath = '';

	/**
	 * Flag whether to write log or not.
	 *
	 * @var boolean
	 */
	var $bLog = false;

	/**
	 * Path to config file.
	 *
	 * @var string
	 */
	var $sConfigPath = '';

	/**
	 * Array with all possible parameters and parameter formats.
	 * Structure has to be:
	 * 
	 * <code>
	 * $check['GET']['param1']	= VALIDATE_FORMAT;
	 * $check['POST']['param2']	= VALIDATE_FORMAT;
	 * </code> 
	 *
	 * Possible formats are defined as constants in top of these class file.
	 * 
	 * @var array
	 */
	var $aCheck = array();

	/**
	 * Contains first invalid parameter name.
	 *
	 * @var string
	 */
	var $sFailure = '';

	/**
	 * Current mode
	 *
	 * @var string
	 */
	var $sMode = 'training';

	/**
	 * Constructor
	 * 
	 * Configuration path $sConfigPath is mandatory and has to contain the complete
	 * path to configuration file with defined parameters.
	 * 
	 * The class provides two modes: training and arcade.
	 * Training mode only logs violations - if log path is given into log file otherwise
	 * as comment into HTML output. Arcade mode is made for killing - every violation will 
	 * cause an hard exit!
	 *
	 * @param string $sConfigPath
	 * @return HttpInputValidator
	 */
	function HttpInputValidator($sConfigPath) {
		// check config and logging path
		if (!empty($sConfigPath) && file_exists($sConfigPath)) {
			$this->sConfigPath = realpath($sConfigPath);
		} else {
			die ('Could not load HttpInputValidator configuration! (invalid path)');
		}

		// include configuration
		require ($this->sConfigPath);

		// if custom config exists, include it also here
		if (file_exists(dirname($this->sConfigPath) . '/config.http_check.local.php')) {
			require (dirname($this->sConfigPath) . '/config.http_check.local.php');
		}
		
		$this->bLog = $bLog;

		if ($this->bLog === true) {
			if (!empty($sLogPath) && is_writable(dirname($sLogPath))) {
				$this->sLogPath = realpath($sLogPath);
			} else {
				die ('Could not log into not existing or empty log path!');
			}
		}
	
		$this->aCheck = $aCheck;

		// run GET check
		if ($this->checkGetParams()) {
			// logging is needed in both modes, training and arcade
			$this->logHackTrial();

			// stops here in case of arcade mode
			if ($sMode == 'arcade') {
				ob_end_clean();
				die ('Parameter check failed! (' . $this->sFailure . ')');
			}
		}
		
		// check POST params for further processings
		$this->checkPostParams();
	}
	
	/**
	 * Checks all GET params and returns true in case of a violation, otherwise false.
	 *
	 * @return bool
	 */
	function checkGetParams () {
		$bResult = false;

		foreach ($_GET as $sKey => $mValue) {
			if (!$this->checkParameter('GET', $sKey, $mValue)) {
				$this->sFailure = $sKey;
				$bResult = true;
				break;
			}
		}

		return $bResult;
	}

	/**
	 * Fills status-array $aPostVariables. Is POST-param known and valid mapped value is true.
	 *
	 */
	function checkPostParams () {
		foreach ($_POST as $sKey => $mValue) {
			$this->aPostVariables[$sKey] = ($this->checkParameter('POST', $sKey, $mValue)) ? (true) : (false);
		}
	}

	/**
	 * This method checks parameter of type $sType (currently GET and POST
	 * are supported) and name $sKey has valid value $mValue. In this case or
	 * in case of unknown but empty params, the method will also return true.
	 * 
	 * @param string $sType
	 * @param string $sKey
	 * @param mixed $mValue
	 * @return bool
	 */
	function checkParameter ($sType, $sKey, $mValue) {
		$bResult = false;

		if (in_array(strtoupper($sType), array('GET', 'POST'))) {
			if (!isset($this->aCheck[$sType][$sKey]) && (is_null($mValue) || empty($mValue))) {
				// if unknown but empty the value is unaesthetic but ok
				$bResult = true;
			} elseif (isset($this->aCheck[$sType][$sKey])) {
				// parameter is known, check it...
				$bResult = preg_match($this->aCheck[$sType][$sKey], $mValue);
			}
		}

		return $bResult;
	}
	
	/**
	 * Tries to log date, remote ip and the requested URI into log file.
	 */
	function logHackTrial() {
		if ($this->bLog === true && !empty($this->sLogPath)) { 
			if (($rLogFile = @fopen($this->sLogPath, 'a')) !== false) {
				fwrite($rLogFile, date('Y-m-d H:i:s'));
				fwrite($rLogFile, '  ');
				fwrite($rLogFile, $_SERVER['REMOTE_ADDR'] . str_repeat(' ', 17 - strlen($_SERVER['REMOTE_ADDR'])));
				fwrite($rLogFile, $_SERVER['QUERY_STRING']);
				fwrite($rLogFile, "\n");
				fclose($rLogFile);
			}
		} elseif ($this->sMode == 'training') {
			echo "\n<br />VIOLATION: URL contains invalid or undefined paramaters! URL: '" . 
				htmlentities($_SERVER['QUERY_STRING']) . "' <br />\n";
		}
	}
	
	/**
	 * Print html comment or returns (depending on flag $bReturn) all POST params.
	 * 
	 * @return string
	 */
	function showPosts($bReturn = false) {
		$sResult = '';

		foreach ($_POST as $sKey => $sValue) {
			$sResult .= $key . ": ";
			
			if (is_null($this->isRegularPost($sKey))) {
				$sResult .= "not defined";
			} else
				if ($this->isRegularPost($key) === false) {
					$sResult .= "iregular";
				} else {
					$sResult .= "ok";
				}
			$sResult .= "\n";
		}
		
		if ($bReturn === false) {
			$sResult = "\n\n<!--\n*** POST PARAMETER CHECK ***\n\n" . $sResult . "\n-->\n\n";
			echo $sResult;
		} else {
			return $sResult;
		}
	}

	/**
	 * Checks POST param $sKey is unknown (result is null), known but invalid (result is false)
	 * or it is known and valid (result is true).
	 * 
	 * @param string $sKey
	 * @return mixed
	 */
	function isRegularPost($sKey) {
		$mResult = null;

		if (isset ($this->postVariable[$sKey])) {
			$mResult = $this->postVariable[$sKey]; 
		}

		return $mResult;
	}

}
?>