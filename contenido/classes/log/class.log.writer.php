<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * This file contains the log writer class.
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
 * This class contains the main functionalities for the logging writer in CONTENIDO.
 */
abstract class cLogWriter {
	/**
     * @var array Contains all options of the current writer instance.
     */
	protected $_options = array();
	
	/**
	 * Constructor of the writer instance.
	 * @param	array	$options	Array with options for the writer instance (optional)
	 */
	public function __construct($options = array()) {
		$this->setOptions($options);
	
		// Set all default options if they were not set already
		$this->setOption('default_priority', cLog::INFO, false);
		$this->setOption('line_ending', PHP_EOL, false);
	}
	
	/**
	 * Factory method for a new writer instance.
	 * 
	 * @param	string	$writerName		Name of the writer
	 * @param	array	$writerOptions	Options array for the writer instance
	 *
	 * @return	cLogWriter	Log writer instance
	 */
	public static function factory($writerName, array $writerOptions) {
		$logWriterClassName = 'cLogWriter' . ucfirst($writerName);
		if (!class_exists($logWriterClassName)) {
			cWarning(__FILE__, __LINE__, "Unknown writer class: " . $writerName);
			return false;
		}
		
		$writer = new $logWriterClassName($writerOptions);
		if (($writer instanceof cLogWriter) == false) {
			cWarning(__FILE__, __LINE__, "Provided class is not an instance of cLogWriter");
			return false;
		}
		
		return $writer;
	}
	
	/**
	 * Sets the whole options array.
	 * 
	 * @param	array	$options	Array with options
	 *
	 * @return	void
	 */
	public function setOptions(array $options) {
		$this->_options = $options;
	}
	
	/**
	 * Returns an array with all options.
	 * @return	array	Array with all options
	 */
	public function getOptions() {
		return $this->_options;
	}
	
	/**
	 * Sets a option. If option was set previously, it must be forced to overwrite the value.
	 *
	 * @param	string	$option	Name of the option
	 * @param	mixed	$value	Value of the option
	 * @param	boolean	$force	Flag to force setting the option value (optional, default: false)
	 *
	 * @return	void
	 */
	public function setOption($option, $value, $force = false) {
		if ($force == false && isset($this->_options[$option]) == true) {
			return;
		}
		
		$this->_options[$option] = $value;
	}
	
	/**
	 * Returns the value of an option entry.
	 * 
	 * @param	string	$option	Name of the option
	 * 
	 * @return	mixed	Value of the option entry
	 */
	public function getOption($option) {
		return $this->_options[$option];
	}
	
	/**
	 * Removes an option entry.
	 * 
	 * @param	string	$option	Name of the option
	 *
	 * @return	void
	 */
	public function removeOption($option) {
		unset($this->_options[$option]);
	}
	
	/**
	 * Abstract function for the write process.
	 * This method must be implemented in the specific writer.
	 *
	 * @param	string	$message	Message to write
	 * @param	int		$priority	Priority of the log entry
	 *
	 * @return	boolean	State of the write process
	 */
	abstract function write($message, $priority);
}