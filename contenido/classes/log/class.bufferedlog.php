<?php
/**********************************************************************************
* File      :   $RCSfile: class.bufferedlog.php,v $
* Project   :   Contenido 
* Descr     :   Buffered Log facility
*
* Author    :   Timo A. Hummel
*               
* Created   :   28.09.2004
* Modified  :   $Date: 2006/04/28 09:20:55 $
*
* © four for business AG, www.4fb.de
*
* This file is part of the Contenido Content Management System. 
*
* $Id: class.bufferedlog.php,v 1.2 2006/04/28 09:20:55 timo.hummel Exp $
***********************************************************************************/


/** Examples **

Buffered logging

$log = new cBufferedLog;
$log->buffer("this is a log message");
$log->buffer("another log message");
$log->commit();

The commit call commits all messages on the stack.

$log = new cBufferedLog;
$log->buffer("this is a log message");
$log->buffer("another log message");
$log->revoke();

The revoke call revokes (Discards) all messages on the stack.

*/


cInclude("classes", "log/class.log.php");

class cBufferedLog extends cLog
{
	/**
	 * @var array Contains all buffered messages to be written
	 * @access private
	 */
	var $_aMessages;
	
    /**
     * cBufferedLog: Creates a new instance of the Contenido BufferedLog mechanism.
     *
     * cBufferedLog is a logging facility which uses cLog to do its logging,
     * and features buffered logging
     *
     * @param oLogger 	object	The object to use for logging, or false if a new one should be created.
     *
     * @return array Beschreibung
     *
     * @see cLog
     *
     * @access public
     */	
	function cBufferedLog ($oLogger = false)
	{
		$this->_aMessages = array();

		cLog::cLog($oLogger);
	}

    /**
     * buffer: Puts a log message on the buffering stack
     *
     * @param sMessage 	string 	Message to log
     * @param bPriority integer	PEAR Loglevel (or default if null / omitted)
     *
     * @return none
     * @access public
     */	
	function buffer ($sMessage, $iPriority = null)
	{
		array_push($this->_aMessages, array($sMessage, $iPriority));
	}
	
    /**
     * commit: Commits all messages which are queued on the stack
     *
     * @param none
     *
     * @return none
     * @access public
     */		
	function commit ()
	{
		foreach ($this->_aMessages as $aMessage)
		{
			list($sMessage, $iPriority) = $aMessage;
			$this->log($sMessage, $iPriority);
		}
	}

    /**
     * revoke: Revoke (discards) all messages which are queued on the stack
     *
     * @param none
     *
     * @return none
     * @access public
     */		
	function revoke ()
	{
		$this->_aMessages = array();	
	}
}

?>