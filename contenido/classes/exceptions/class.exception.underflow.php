<?php
/**
 * This file contains the cUnderflowException class.
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
 * Exception thrown when performing an invalid operation on an empty container,
 * such as removing an element.
 * You should use this CONTENIDO exception instead of the standard PHP
 * {@link UnderflowException}.
 * This exception type is logged to data/logs/exception.txt.
 */
class cUnderflowException extends cRuntimeException {

}
