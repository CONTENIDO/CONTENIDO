<?php

/**
 *
 * @package Plugin
 * @subpackage FormAssistant
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 *
 * @author marcus.gnass
 */
class PifaException extends Exception {
}

/**
 *
 * @author marcus.gnass
 */
class PifaDatabaseException extends PifaException {
}

/**
 *
 * @author marcus.gnass
 */
class PifaNotImplementedException extends PifaException {
}

/**
 *
 * @author marcus.gnass
 */
class PifaIllegalStateException extends PifaException {
}

/**
 *
 * @author marcus.gnass
 */
class PifaNotYetStoredException extends PifaException {
}

/**
 *
 * @author marcus.gnass
 */
class PifaValidationException extends PifaException {

    /**
     *
     * @var array
     */
    private $_errors = NULL;

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
     * @return multitype:
     */
    public function getErrors() {
        return $this->_errors;
    }
}

/**
 *
 * @author marcus.gnass
 */
class PifaMailException extends PifaException {
}
