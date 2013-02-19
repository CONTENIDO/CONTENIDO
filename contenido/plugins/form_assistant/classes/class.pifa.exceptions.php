<?php

/**
 *
 * @package Plugin
 * @subpackage PIFA Form Asistant
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') or die('Illegal call');

if (!class_exists('PifaException')) {

    /**
     *
     * @author marcus.gnass
     */
    class PifaException extends Exception {
    }

}

if (!class_exists('PifaDatabaseException')) {

    /**
     *
     * @author marcus.gnass
     */
    class PifaDatabaseException extends PifaException {
    }

}

if (!class_exists('NotImplementedException')) {

    /**
     *
     * @author marcus.gnass
     */
    class NotImplementedException extends PifaException {
    }

}

if (!class_exists('IllegalStateException')) {

    /**
     *
     * @author marcus.gnass
     */
    class IllegalStateException extends PifaException {
    }

}

if (!class_exists('PifaNotYetStoredException')) {

    /**
     *
     * @author marcus.gnass
     */
    class PifaNotYetStoredException extends PifaException {
    }

}

if (!class_exists('PifaValidationException')) {

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

}

if (!class_exists('PifaMailException')) {

    /**
     *
     * @author marcus.gnass
     */
    class PifaMailException extends PifaException {
    }

}

?>