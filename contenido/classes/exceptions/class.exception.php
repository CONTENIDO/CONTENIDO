<?php
/**
 * This file contains the cException class.
 *
 * @package Core
 * @subpackage Exception
 * @version SVN Revision $Rev:$
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cException is the base class for all exceptions.
 * You should use this CONTENIDO exception instead of the standard PHP
 * {@link Exception}.
 * This exception type is logged to data/logs/exception.txt.
 * If there is a more specific and more appropriate subclass, use the subclass!
 */
class cException extends Exception {

    /**
     * Saves an instance of the logger class for logging exceptions in the
     * corresponding log.
     *
     * @var cLog the logger instance
     */
    protected $_logger = NULL;

    /**
     * Saves whether the exception should be logged - defaults to true.
     *
     * @var boolean whether the exception should be logged
     */
    protected $_log = true;

    /**
     * Constructs the Exception.
     *
     * @param string $message The Exception message to throw.
     * @param int $code The Exception code.
     * @param Exception $previous The previous exception used for the exception
     *            chaining.
     */
    public function __construct($message, $code = 0, Exception $previous = NULL) {
        parent::__construct($message, $code, $previous);

        // create a logger class and save it for all logging purposes
        $cfg = cRegistry::getConfig();
        $writer = cLogWriter::factory("File", array(
            'destination' => $cfg['path']['contenido_logs'] . 'exception.txt'
        ));
        $this->_logger = new cLog($writer);

        // log the exception if it should be logged
        if ($this->_log) {
            $this->log();
        }
    }

    /**
     * Logs this exception no matter if the log flag is set or not.
     */
    public function log() {
        // construct the log message with all infos and write it via the logger
        $logMessage = get_class($this) . ' thrown at line ' . $this->getLine() . ' of file ' . $this->getFile() . ".\r\n";
        $logMessage .= 'Exception message: ' . $this->getMessage() . "\r\n";
        $logMessage .= "Call stack:\r\n";
        $logMessage .= $this->getTraceAsString();
        $logMessage .= "\r\n";
        $this->_logger->log($logMessage);
    }

}
