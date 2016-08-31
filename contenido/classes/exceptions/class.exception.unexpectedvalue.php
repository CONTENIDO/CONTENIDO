<?php
/**
 * This file contains the cUnexpectedValueException class.
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
 * Exception thrown if a value does not match with a set of values.
 * Typically this happens when a function calls another function and expects the
 * return value to be of a certain type or value not including arithmetic or
 * buffer related errors.
 * You should use this CONTENIDO exception instead of the standard PHP
 * {@link UnexpectedValueException}.
 * This exception type is logged to data/logs/exception.txt.
 */
class cUnexpectedValueException extends cRuntimeException {

}
