<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * MySQL Driver for GenericDB 
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.3
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2004-09-28
 *   
 *   $Id: class.modulelog.php,v 1.3 2006/04/28 09:20:55 timo.hummel Exp $
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("classes", "contenido/class.module.php");
cInclude("classes", "log/class.bufferedlog.php");

class cModuleLog extends cBufferedLog
{
	var $_oModule;
	
    /**
     * cModuleLog: Creates a new instance of the Contenido ModuleLog mechanism.
     *
     * cModuleLog is a logging facility which uses cBufferedLog to do its logging,
     * and features automatic module handling.
     *
     * @param oLogger 	object	The object to use for logging, or false if a new one should be created.
     * @param idmod		integer	The module ID to use
     *
     * @see cBufferedLog
     *
     * @access public
     */		
	function cModuleLog ($oLogger = false, $idmod = 0)
	{
		cBufferedLog::cBufferedLog($oLogger);

		$this->_setShortcutHandler("module", "_shModule");
		$this->setLogFormat("[%date] [%module] [%session] [%level] %message");

		if ($idmod != 0)
		{		
			$this->setModule($idmod);
		}
		
	}	

    /**
     * setModule: Sets the module to use.
     *
     * setModule automatically buffers basic module information to the log to assist the
	 * developer in debugging his modules.
     *
     * @param idmod		integer	The module ID to use
     *
     * @access public
     */			
	function setModule ($idmod)
	{
		global $client, $lang, $idcat, $idart;
				
		$this->_oModule = new cApiModule($idmod);

		$this->buffer("-- REQUEST START --", PEAR_LOG_INFO);
		$this->buffer("-- MODULE INFO --", PEAR_LOG_DEBUG);		
		$this->buffer("idmod   : ". $this->_oModule->get("idmod"),PEAR_LOG_DEBUG);
		$this->buffer("idclient: ". $client,PEAR_LOG_DEBUG);
		$this->buffer("idlang  : ". $lang,PEAR_LOG_DEBUG);
		$this->buffer("idcat   : ". $idcat,PEAR_LOG_DEBUG);
		$this->buffer("idart   : ". $idart,PEAR_LOG_DEBUG);
		$this->buffer("-- MODULE INFO END --", PEAR_LOG_DEBUG);				
	}

    /**
     * _shModule: shortcut handler for the module id / name
     *
     * @param none
     * @return id and name of the module
     *
     * @access public
     */		
	function _shModule ()
	{
		return ($this->_oModule->get("idmod").": ".$this->_oModule->get("name"));
	}

    /**
     * commit: Appends "REQUEST END" to the stack and commits all messages which are queued on the stack
     *
     * @param none
     *
     * @return none
     * @access public
     */		
	function commit ()
	{
		$this->buffer("-- REQUEST END --");
		parent::commit();	
	}
}

?>