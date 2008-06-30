<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Log facility
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2004-09-28
 *   
 *   $Id: class.log.php,v 1.2 2006/04/28 09:20:55 timo.hummel Exp $
 * }}
 * 
 */

 /** Examples **

  Standard logging (file logger, to logs/contenido.log):

  $log = new cLog;
  $log->("this is a log message");

  => [2004-09-28 14:38:02] [2e8e00efa314c8c2c07ae7316b875529] [   info   ] this is a log message


  Standard logging (file logger, custom log format):
  $log = new cLog;
  $log->setLogFormat("%date %message");
  $log->("this log message just contains the date and the message");

  => [2004-09-28 14:38:02] this log message just contains the date and the message
*/

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("pear", "Log.php");
cInclude("includes", "functions.general.php");

class cLog
{
	/**
	 * @var object Contains the logger object
	 * @access private
	 */	
	var $_oLogger;
	
	/**
	 * @var string Contains the Log Format string
	 * @access private
	 */	
	var $_sLogFormat;	

	/**
	 * @var array Contains all shortcut handlers
	 * @access private
	 */		
	var $_aShortcutHandlers;

	/**
     * cLog: Creates a new instance of the Contenido Log mechanism.
     *
     * cLog is a logging facility which uses PEAR::Log to do its logging,
     * and features log categories.
     *
     * The log format interface of cLog is capable of being extended by subclasses. See the note about
     * the log shortcuts below.
     *
     *
     * About Log Shortcuts
     * -------------------
     * Log shortcuts are placeholders which are replaced when a log entry is created. Placeholders start with a
     * percentage sign (%) and contain one or more characters. Each placeholder is handled by an own function which
     * decides what to do.
     *
     * @param oLogger mixed Logger object (any subclass of PEAR::Log), or false if cLog
     *						should handle the logger creation
     *
     * @return none
     * @access public
     */		
	function cLog ($oLogger = false)
	{
		global $cfg;
		
		$this->_aShortcutHandlers = array();
		$this->_oLogger = null;
		
		$bCreateLogger = false;
		
		/* If no logger was given, create a new file based logger */
		if ($oLogger === false)
		{
			$bCreateLogger = true;
		} else {
			/* Check if the passed logger object is really an object */
			if (!is_object($oLogger))
			{
				cWarning(__FILE__, __LINE__, "The logger passed to cBufferedLog is not a class. Creating own logger.");	
				$bCreateLogger = true;
			}
			
			/* Check if the passed logger is really a subclass of PEAR::Log */
			if (!is_a($oLogger, "Log"))
			{
				cWarning(__FILE__, __LINE__, 	"The passed class to cBufferedLog is not a subclass of Log. " .
												"Creating own logger.");	
				$bCreateLogger = true;		
			}
		}
		
		if ($bCreateLogger === true)
		{
			$oLogger = &Log::factory("file", $cfg["path"]["contenido"]."/logs/contenido.log");	
			$oLogger->_lineFormat = '%4$s';
		}

		$this->_oLogger = $oLogger;
		$this->setLogFormat("[%date] [%session] [%level] %message");
		
		$this->_setShortcutHandler("%date", "_shDate");
		$this->_setShortcutHandler("%level", "_shLevel");
		$this->_setShortcutHandler("%session", "_shSession");
		$this->_setShortcutHandler("%message", "_shMessage");
	}
	
	/**
     * setLogFormat: Sets user-defined log formats
     *
     * The following placeholders are defined in this class:
     * %date	Date and Time
     * %session	Session-ID
     * %level	Log Level
     * %message	Message
     *
     * @param sLogFormat string Format string
     *
     * @return none
     * @access public
     */		
	function setLogFormat ($sLogFormat)
	{
		$this->_sLogFormat = $sLogFormat;
	}

	/**
     * _setShortcutHandler: Defines a custom shortcut handler.
     *
     * Each shortcut handler receives two parameters:
     * - The message
     * - The log level
     *
     * @param sShortcut string Shortcut name
     * @param sHandloer string Name of the function to call
     *
     * @return boolean True if set was successful
     * @access public
     */
	function _setShortcutHandler ($sShortcut, $sHandler)
	{
		if (substr($sShortcut, 0, 1) == "%")
		{
			$sShortcut = substr($sShortcut, 1);	
		}
		
		/* Check if the handler function exists */
		if (method_exists($this, $sHandler))
		{
			/* Check if the shortcut handler already exists */
			if (in_array($sShortcut, $this->_aShortcutHandlers))
			{
				/* The shortcut handler exists. If the passed handler is different, complain. */
				if ($this->_aShortcutHandlers[$sShortcut] != $sHandler)
				{
					cWarning(__FILE__, __LINE__, "The shortcut $sShortcut is already in use!");
					return false;
				}	
			} else {
				/* Add shortcut handler to stack */
				$this->_aShortcutHandlers[$sShortcut] = $sHandler;
			}
		} else {
			cWarning(__FILE__, __LINE__, "The specified shortcut handler does not exist.");	
			return false;
		}
		
		return true;
	}

	/**
     * log: Logs a message using the logger object
     *
     * @param sMessage 	string 	Message to log
     * @param bPriority integer	PEAR Loglevel (or default if null / omitted)
     *
     * @return none
     * @access public
     */	
	function log ($sMessage, $bPriority = null)
	{
		if ($bPriority === null)
		{
			$bPriority = $this->_oLogger->_priority;
		}
		$sLogMessage = $this->_sLogFormat;
		
		foreach ($this->_aShortcutHandlers as $sShortcut => $sHandler)
		{
			if (substr($sShortcut, 0, 1) != "%")
			{
				$sShortcut = "%" . $sShortcut;
			}
			
			$sValue = call_user_func(array($this, $sHandler), $sMessage, $bPriority);
			
			$sLogMessage = str_replace($sShortcut, $sValue, $sLogMessage);
		}
		
		$this->_oLogger->log($sLogMessage, $bPriority);
	}

	/**
     * _shDate: Returns the current date
     *
     * @return The current date
     * @access public
     */		
	function _shDate ()
	{
		return date("Y-m-d H:i:s");	
	}

	/**
     * _shSession: Returns the current session, if existant
     *
     * @return The current session
     * @access public
     */			
	function _shSession ()
	{
		global $sess;
		
		if (is_object($sess))
		{
			return $sess->id;	
		} else {
			return "";	
		}
	}

	/**
     * _shLevel: Returns the canonical name of the priority.
     *
     * The canonical name is padded to 10 characters to achieve a better formatting.
     *
     * @return The canonical log level
     * @access public
     */			
	function _shLevel ($message, $loglevel)
	{

		return str_pad($this->_oLogger->priorityToString($loglevel), 10, " ", STR_PAD_BOTH);
	}

	/**
     * _shMessage: Returns the log message.
     *
     * @return The log message
     * @access public
     */			
	function _shMessage ($message, $loglevel)
	{
		return $message;	
	}
}

?>