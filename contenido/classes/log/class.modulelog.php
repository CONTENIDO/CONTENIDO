<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * This file contains the module log class.
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.0.0
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * This class contains the main functionalities for the module logging in CONTENIDO.
 * The funcationality is almost the same like normal logging with the exception, that
 * log entries contains an additional information about the used module.
 *
 * Example:
 * $writer = cLogWriter::factory("File", array('destination' => 'contenido.log'));
 *
 * $log = new cModuleLog($writer);
 * $log->setModule(1);
 * $log->log("Anything you want to log.");
 */
class cModuleLog extends cLog {

	/**
	 * @var cApiModule	Object instance of module model
	 */
	private $_module;

	/**
	 * Constructor of the module log.
     *
     * @param  mixed  writer   Writer object (any subclass of cLogWriter), or false if cLog should handle the writer creation
	 *
	 */
	public function __construct($writer = false) {
		parent::__construct($writer);

		$this->setShortcutHandler('module', array($this, 'shModule'));
		$this->getWriter()->setOption("log_format", "[%date] [%level] [%module] %message", true);
	}

    /**
     * Sets the module to use.
     *
     * setModule automatically buffers basic module information to the log to assist the
     * developer in debugging his modules.
     *
     * @param  int  idmod  The module ID to use
	 *
	 * @return	void
     */
    public function setModule($idmod) {
        $this->_module = new cApiModule($idmod);
		if ($this->_module->isLoaded() == false) {
			cWarning(__FILE__, __LINE__, "Could not load module information.");
			return false;
		}
    }

    /**
     * Shortcut Handler Module.
	 * Returns the ID and the name of the module.
     * @return	string	 ID and name of the module
     */
    public function shModule() {
		if ($this->_module->isLoaded() == false) {
			return '';
		}

        return $this->_module->get("idmod") . ": " . $this->_module->get("name");
    }
}

?>