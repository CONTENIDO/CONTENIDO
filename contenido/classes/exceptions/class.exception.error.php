<?php

/**
 * This file contains the cErrorException class.
 *
 * @package Core
 * @subpackage Exception
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

/**
 * An Error Exception.
 * You should use this CONTENIDO exception instead of the standard PHP
 * {@link ErrorException}.
 * This exception type is logged to data/logs/exception.txt.
 */
class cErrorException extends cException {

    /**
     * Constructs the Exception.
     *
     * @param string $message
     *         The Exception message to throw.
     * @param int $code [optional]
     *         The Exception code.
     * @param Exception $previous [optional]
     *         The previous exception used for the exception chaining.
     */
    public function __construct($message, $code = 0, Exception $previous = NULL) {
        $cfg = cRegistry::getConfig();

        // determine if exception should be logged
        if (false === isset($cfg['debug']['log_error_exceptions'])) {
            $this->_log_exception = true;
        }

        if (false === $this->_log_exception) {
            $this->_log_exception = $cfg['debug']['log_error_exceptions'];
        }

        parent::__construct($message, $code, $previous);

    }

}
