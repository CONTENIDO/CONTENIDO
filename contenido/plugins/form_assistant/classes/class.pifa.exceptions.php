<?php

/**
 * This file contains several exception classes.
 *
 * @package    Plugin
 * @subpackage FormAssistant
 * @author     Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright  four for business AG
 * @link       https://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Base class for all PIFA related exceptions.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class PifaException extends cException
{
}

/**
 * Exceptions indicating a problem when PIFA tries to access the database.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class PifaDatabaseException extends PifaException
{
}

/**
 * Exceptions indicating that a certain PIFA feature is not yet implemented.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class PifaNotImplementedException extends PifaException
{
}

/**
 * Exceptions indicating that PIFA reached an illegal state.
 * This happens e.g. if permissions for a certain action are missing.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class PifaIllegalStateException extends PifaException
{
}

/**
 * Currently not in use.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class PifaNotYetStoredException extends PifaException
{
}

/**
 * Exceptions indicating that invalid data was found when PIFA tried to process
 * posted data.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class PifaValidationException extends PifaException
{
    /**
     * @var array
     */
    private $_errors = [];

    /**
     * @param array $errors
     */
    public function __construct(array $errors)
    {
        // parent::__construct($message, $code, $previous);
        $this->_errors = $errors;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }
}

/**
 * Exceptions indicating a problem when PIFA tries to send a mail.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class PifaMailException extends PifaException
{
}
