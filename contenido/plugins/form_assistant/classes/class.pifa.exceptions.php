<?php

/**
 *
 * @package Plugin
 * @subpackage FormAssistant
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Base class for all PIFA related exceptions.
 *
 * @author marcus.gnass
 */
class PifaException extends cException {
}

/**
 * Exceptions indicating a problem when PIFA tries to access the database.
 *
 * @author marcus.gnass
 */
class PifaDatabaseException extends PifaException {
}

/**
 * Exceptions indicating that a certain PIFA feature is not yet implemented.
 *
 * @author marcus.gnass
 */
class PifaNotImplementedException extends PifaException {
}

/**
 * Exceptions indicating that PIFA reached an illegal state.
 * This happens e.g. if permissions for a certain action are missing.
 *
 * @author marcus.gnass
 */
class PifaIllegalStateException extends PifaException {
}

/**
 * Currently not in use.
 *
 * @author marcus.gnass
 */
class PifaNotYetStoredException extends PifaException {
}

/**
 * Exceptions indicating that invalid data was found when PIFA tried to process
 * posted data.
 *
 * @author marcus.gnass
 */
class PifaValidationException extends PifaException {

    /**
     *
     * @var array
     */
    private $_errors = array();

    /**
     *
     * @param array $errors
     */
    public function __construct(array $errors) {
        // parent::__construct($message, $code, $previous);
        $this->_errors = $errors;
    }

    /**
     *
     * @return array
     */
    public function getErrors() {
        return $this->_errors;
    }
}

/**
 * Exceptions indicating a problem when PIFA tries to send a mail.
 *
 * @author marcus.gnass
 */
class PifaMailException extends PifaException {
}
