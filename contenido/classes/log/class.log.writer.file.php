<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * This file contains the file writer class.
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

/**
 * This class contains the file writer class for the logging mechanism.
 */
class cLogWriterFile extends cLogWriter {
	/**
	 * @var	resource	Destination handle
	 */
	protected $_handle = null;

	/**
	 * Constructor of the writer instance.
	 * @param	array	$options	Array with options for the writer instance (optional)
	 */
	public function __construct($options = array()) {
		parent::__construct($options);
		
		$this->_createHandle();
	}
	
	/**
	 * Checks destination and creates the handle for the write process.
	 * @return	void
	 */
	protected function _createHandle() {
		$destination = $this->getOption('destination');
		if ($destination == '') {
			cWarning(__FILE__, __LINE__, "No destination was specified.");
            return false;
		}

		if (!$this->_handle = fopen($destination, 'a')) {
			cWarning(__FILE__, __LINE__, "Destination handle could not be created.");
			return false;
        }
	}
	
	/**
	 * Writes the content to file handle.
	 *
	 * @param	string	$message	Message to write
	 * @param	int		$priority	Priority of the log entry
	 *
	 * @return	boolean	State of the write process
	 */
	public function write($message, $priority) {
		if (fwrite($this->_handle, $message) == false) {
            cWarning(__FILE__, __LINE__, "Message could not be logged to destination.");
			return false;
        }
		
		return true;
	}
}
?>