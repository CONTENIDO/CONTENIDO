<?php

/**
 * This file contains the cException class.
 *
 * @package    Core
 * @subpackage Exception
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * cException is the base class for all exceptions.
 * You should use this CONTENIDO exception instead of the standard PHP
 * {@link Exception}.
 * This exception type is logged to data/logs/exception.txt.
 * If there is a more specific and more appropriate subclass, use the subclass!
 */
class cException extends Exception
{

    /**
     * Defines if an exception if this type should be logged.
     * May be defined by any exception individually.
     *
     * @see CON-1690
     * @var bool
     */
    protected $_log_exception = false;

    /**
     * Saves an instance of the logger class for logging exceptions in the
     * corresponding log.
     *
     * @var cLog the logger instance
     */
    protected $_logger = NULL;

    /**
     * Constructor to create an instance of this class.
     *
     * @param string $message
     *                            The Exception message to throw.
     * @param int $code [optional]
     *                            The Exception code.
     * @param Exception $previous [optional]
     *                            The previous exception used for the exception chaining.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct($message, $code = 0, Exception $previous = NULL)
    {
        parent::__construct($message, $code, $previous);

        // create a logger class and save it for all logging purposes
        $cfg = cRegistry::getConfig();
        $writer = cLogWriter::factory(
            "File",
            [
                'destination' => $cfg['path']['contenido_logs'] . 'exception.txt',
            ]
        );
        $this->_logger = new cLog($writer);

        // determine if exception should be logged
        if (false === $this->_log_exception
            && isset($cfg['debug']['log_exceptions'])) {
            $this->_log_exception = $cfg['debug']['log_exceptions'];
        }

        // log the exception if it should be logged
        if (true === $this->_log_exception) {
            $this->log();
        }
    }

    /**
     * Logs this exception no matter if the log flag is set or not.
     */
    public function log()
    {
        // construct the log message with all infos and write it via the logger
        $logMessage = get_class($this) . ' thrown at line ' . $this->getLine() . ' of file ' . $this->getFile() . ".\r\n";
        $logMessage .= 'Exception message: ' . $this->getMessage() . "\r\n";
        $logMessage .= "Call stack:\r\n";
        $logMessage .= $this->getTraceAsString();
        $logMessage .= "\r\n";
        $this->_logger->log($logMessage);
    }

}
